"""Servicio firmador: firma PAdES de PDFs con certificados .p12 (pyHanko).

Solo es accesible desde la red interna de Docker y exige el header X-Firmador-Token.
Todo se procesa en memoria: no se guardan PDFs, certificados ni contraseñas, y el
contenido de las peticiones nunca se registra.
"""

import hmac
import io
import json
import os
import warnings
from datetime import datetime, timezone

from asn1crypto import pkcs12 as asn1_pkcs12
from asn1crypto import x509
from cryptography.hazmat.primitives import serialization
from fastapi import APIRouter, Depends, FastAPI, File, Form, Header, Request, UploadFile
from fastapi.responses import JSONResponse, Response

from pyhanko.pdf_utils.incremental_writer import IncrementalPdfFileWriter
from pyhanko.pdf_utils.misc import PdfError
from pyhanko.pdf_utils.reader import PdfFileReader
from pyhanko.sign import fields, signers
from pyhanko.sign.validation.pdf_embedded import async_validate_pdf_signature
from pyhanko_certvalidator import ValidationContext

from sello import DatosSello, estilo_sello

# Los .p12 de Digicert traen bytes extra tras la estructura ASN.1; pyHanko los lee igual.
warnings.filterwarnings("ignore", message="PKCS#12 bundle could not be parsed as DER")

MAX_PDF_BYTES = int(os.environ.get("FIRMADOR_MAX_PDF_BYTES", 20 * 1024 * 1024))
MAX_P12_BYTES = int(os.environ.get("FIRMADOR_MAX_P12_BYTES", 100 * 1024))
MAX_LOGO_BYTES = 2 * 1024 * 1024

# Atributo del sujeto donde Digicert Bolivia guarda el número de documento (uidNumber).
OID_NUMERO_DOCUMENTO = "1.3.6.1.1.1.1.0"


class ErrorFirmador(Exception):
    """Error de negocio: se responde 422 con un código estable que Laravel traduce."""

    def __init__(self, codigo: str, mensaje: str, estado: int = 422):
        super().__init__(mensaje)
        self.codigo = codigo
        self.mensaje = mensaje
        self.estado = estado


def verificar_token(x_firmador_token: str = Header(default="")) -> None:
    esperado = os.environ.get("FIRMADOR_TOKEN", "")
    if not esperado or not hmac.compare_digest(x_firmador_token.encode(), esperado.encode()):
        raise ErrorFirmador("no_autorizado", "Token del firmador inválido.", 401)


app = FastAPI(title="Firmador ICU", docs_url=None, redoc_url=None, openapi_url=None)
api = APIRouter(dependencies=[Depends(verificar_token)])


@app.exception_handler(ErrorFirmador)
async def manejar_error_firmador(_: Request, error: ErrorFirmador) -> JSONResponse:
    return JSONResponse({"codigo": error.codigo, "mensaje": error.mensaje}, status_code=error.estado)


@app.exception_handler(Exception)
async def manejar_error_inesperado(_: Request, error: Exception) -> JSONResponse:
    # Solo el tipo: el mensaje podría incluir datos del documento.
    print(f"Error inesperado en firmador: {type(error).__name__}", flush=True)
    return JSONResponse(
        {"codigo": "error_interno", "mensaje": "Error interno del servicio de firma."},
        status_code=500,
    )


# ---------------------------------------------------------------------------
# Utilidades
# ---------------------------------------------------------------------------


async def leer(archivo: UploadFile, maximo: int, nombre: str) -> bytes:
    contenido = await archivo.read(maximo + 1)
    if len(contenido) > maximo:
        raise ErrorFirmador("archivo_muy_grande", f"El archivo {nombre} supera el tamaño permitido.", 413)
    if not contenido:
        raise ErrorFirmador("archivo_vacio", f"El archivo {nombre} está vacío.")
    return contenido


def abrir_pdf(contenido: bytes) -> PdfFileReader:
    if not contenido.lstrip()[:5] == b"%PDF-":
        raise ErrorFirmador("pdf_invalido", "El archivo no es un PDF.")
    try:
        lector = PdfFileReader(io.BytesIO(contenido), strict=False)
        lector.root  # noqa: B018 - fuerza el parseo del catálogo
    except (PdfError, ValueError, KeyError) as error:
        raise ErrorFirmador("pdf_invalido", "El PDF está dañado o no se puede leer.") from error
    if lector.encrypted:
        raise ErrorFirmador("pdf_cifrado", "El PDF está protegido con contraseña y no se puede firmar.")
    return lector


def cargar_certificado(p12: bytes, contrasena: str) -> signers.SimpleSigner:
    try:
        pfx = asn1_pkcs12.Pfx.load(p12, strict=False)
        pfx["auth_safe"]["content_type"].native  # noqa: B018 - valida la estructura PKCS#12
    except (ValueError, TypeError) as error:
        raise ErrorFirmador("p12_invalido", "El archivo no es un certificado .p12 válido.") from error

    try:
        firmante = signers.SimpleSigner.load_pkcs12_data(p12, other_certs=[], passphrase=contrasena.encode())
    except ValueError as error:
        raise ErrorFirmador("contrasena_incorrecta", "La contraseña del certificado .p12 no es correcta.") from error

    if firmante is None or firmante.signing_key is None or firmante.signing_cert is None:
        raise ErrorFirmador("p12_sin_clave", "El .p12 no contiene una clave privada con su certificado.")
    return firmante


def clave_corresponde(firmante: signers.SimpleSigner) -> bool:
    clave = serialization.load_der_private_key(firmante.signing_key.dump(), password=None)
    publica = clave.public_key().public_bytes(
        serialization.Encoding.DER, serialization.PublicFormat.SubjectPublicKeyInfo
    )
    return publica == firmante.signing_cert.public_key.dump()


def esta_vigente(certificado: x509.Certificate) -> bool:
    ahora = datetime.now(timezone.utc)
    validez = certificado["tbs_certificate"]["validity"]
    return validez["not_before"].native <= ahora < validez["not_after"].native


def datos_sujeto(nombre: x509.Name) -> dict:
    datos = nombre.native
    return {
        "cn": datos.get("common_name"),
        "documento": datos.get(OID_NUMERO_DOCUMENTO),
        "tipo_documento": datos.get("dn_qualifier"),
        "numero_serie": datos.get("serial_number"),
        "cargo": datos.get("title"),
        "organizacion": datos.get("organization_name"),
        "unidad": datos.get("organizational_unit_name"),
        "pais": datos.get("country_name"),
    }


def describir_certificado(certificado: x509.Certificate) -> dict:
    validez = certificado["tbs_certificate"]["validity"]
    uso = certificado.key_usage_value
    return {
        "titular": datos_sujeto(certificado.subject),
        "emisor": datos_sujeto(certificado.issuer),
        "numero_serie": format(certificado.serial_number, "x"),
        "vigente_desde": validez["not_before"].native.isoformat(),
        "vigente_hasta": validez["not_after"].native.isoformat(),
        "vigente": esta_vigente(certificado),
        "huella_sha256": certificado.sha256.hex(),
        "uso_clave": sorted(uso.native) if uso is not None else [],
    }


def paginas(lector: PdfFileReader) -> list[dict]:
    """Recorre el árbol de páginas heredando MediaBox, CropBox y Rotate."""
    resultado: list[dict] = []

    def recorrer(nodo, heredado: dict) -> None:
        nodo = nodo.get_object()
        actual = dict(heredado)
        for clave in ("/MediaBox", "/CropBox", "/Rotate"):
            if clave in nodo:
                actual[clave] = nodo[clave]
        if nodo.get("/Type") == "/Pages":
            for hijo in nodo["/Kids"]:
                recorrer(hijo, actual)
            return
        caja = [float(v) for v in actual.get("/CropBox", actual.get("/MediaBox"))]
        resultado.append({
            "numero": len(resultado) + 1,
            "ancho": round(abs(caja[2] - caja[0]), 2),
            "alto": round(abs(caja[3] - caja[1]), 2),
            "rotacion": int(actual.get("/Rotate", 0)) % 360,
        })

    recorrer(lector.root["/Pages"], {})
    return resultado


def campos_de_firma(lector: PdfFileReader) -> dict[str, dict]:
    campos = {}
    for nombre, valor, referencia in fields.enumerate_sig_fields(lector):
        rect = referencia.get_object().get("/Rect")
        campos[nombre] = {
            "firmado": valor is not None,
            "rect": [float(v) for v in rect] if rect is not None else None,
        }
    return campos


# ---------------------------------------------------------------------------
# Endpoints
# ---------------------------------------------------------------------------


@app.get("/salud")
def salud() -> dict:
    return {"estado": "ok"}


@api.post("/certificados/inspeccionar")
async def inspeccionar_certificado(p12: UploadFile = File(...), contrasena: str = Form(...)) -> dict:
    firmante = cargar_certificado(await leer(p12, MAX_P12_BYTES, ".p12"), contrasena)
    return {
        **describir_certificado(firmante.signing_cert),
        "clave_corresponde": clave_corresponde(firmante),
        "certificados_en_cadena": len(list(firmante.cert_registry)),
    }


@api.post("/pdf/inspeccionar")
async def inspeccionar_pdf(pdf: UploadFile = File(...)) -> dict:
    lector = abrir_pdf(await leer(pdf, MAX_PDF_BYTES, "PDF"))
    return {
        "paginas": paginas(lector),
        "campos_firma": campos_de_firma(lector),
        "firmas": len(lector.embedded_signatures),
    }


@api.post("/pdf/preparar")
async def preparar_pdf(pdf: UploadFile = File(...), campos: str = Form(...)) -> Response:
    """Agrega campos de firma vacíos. Todos se crean antes de la primera firma."""
    contenido = await leer(pdf, MAX_PDF_BYTES, "PDF")
    lector = abrir_pdf(contenido)
    total_paginas = len(paginas(lector))
    existentes = campos_de_firma(lector)

    try:
        definiciones = json.loads(campos)
        assert isinstance(definiciones, list) and definiciones
    except (ValueError, AssertionError) as error:
        raise ErrorFirmador("campos_invalidos", "La lista de campos de firma no es válida.") from error

    escritor = IncrementalPdfFileWriter(io.BytesIO(contenido), strict=False)
    nombres: set[str] = set()
    for campo in definiciones:
        try:
            nombre = str(campo["nombre"])
            pagina = int(campo["pagina"])
            x, y = float(campo["x"]), float(campo["y"])
            ancho, alto = float(campo["ancho"]), float(campo["alto"])
        except (KeyError, TypeError, ValueError) as error:
            raise ErrorFirmador("campos_invalidos", "Cada campo necesita nombre, pagina, x, y, ancho y alto.") from error
        if nombre in existentes or nombre in nombres:
            raise ErrorFirmador("campo_duplicado", f"El campo de firma '{nombre}' ya existe.")
        if not 1 <= pagina <= total_paginas:
            raise ErrorFirmador("pagina_invalida", f"El documento no tiene la página {pagina}.")
        nombres.add(nombre)
        fields.append_signature_field(
            escritor,
            fields.SigFieldSpec(nombre, box=(x, y, x + ancho, y + alto), on_page=pagina - 1),
        )

    salida = io.BytesIO()
    escritor.write(salida)
    return Response(salida.getvalue(), media_type="application/pdf")


@api.post("/pdf/firmar")
async def firmar_pdf(
    pdf: UploadFile = File(...),
    p12: UploadFile = File(...),
    contrasena: str = Form(...),
    campo: str = Form(...),
    sello: str = Form(...),
    logo: UploadFile | None = File(None),
    motivo: str = Form("Firma digital de documento"),
    ubicacion: str = Form("Bolivia"),
) -> Response:
    contenido = await leer(pdf, MAX_PDF_BYTES, "PDF")
    lector = abrir_pdf(contenido)
    firmante = cargar_certificado(await leer(p12, MAX_P12_BYTES, ".p12"), contrasena)

    if not esta_vigente(firmante.signing_cert):
        raise ErrorFirmador("certificado_vencido", "El certificado no está vigente.")

    existentes = campos_de_firma(lector)
    if campo not in existentes:
        raise ErrorFirmador("campo_inexistente", f"El documento no tiene el campo de firma '{campo}'.")
    if existentes[campo]["firmado"]:
        raise ErrorFirmador("campo_ya_firmado", f"El campo '{campo}' ya está firmado.")

    try:
        datos = json.loads(sello)
        rect = existentes[campo]["rect"]
        ancho = abs(rect[2] - rect[0]) if rect else 200
        alto = abs(rect[3] - rect[1]) if rect else 95
        datos_sello = DatosSello(
            nombre=str(datos["nombre"]),
            cargo=str(datos.get("cargo") or ""),
            fecha=str(datos["fecha"]),
            ancho=ancho,
            alto=alto,
        )
    except (ValueError, KeyError, TypeError) as error:
        raise ErrorFirmador("sello_invalido", "Los datos del sello no son válidos.") from error

    imagen = await leer(logo, MAX_LOGO_BYTES, "logo") if logo is not None else None

    escritor = IncrementalPdfFileWriter(io.BytesIO(contenido), strict=False)
    pdf_signer = signers.PdfSigner(
        signers.PdfSignatureMetadata(field_name=campo, reason=motivo, location=ubicacion),
        signer=firmante,
        stamp_style=estilo_sello(datos_sello, imagen),
    )
    salida = io.BytesIO()
    await pdf_signer.async_sign_pdf(escritor, output=salida)
    return Response(salida.getvalue(), media_type="application/pdf")


@api.post("/pdf/verificar")
async def verificar_pdf(pdf: UploadFile = File(...)) -> dict:
    """Integridad y validez criptográfica de cada firma.

    No se valida contra una lista de entidades raíz (decisión 8): la cadena embebida
    en cada firma se toma como confiable.
    """
    lector = abrir_pdf(await leer(pdf, MAX_PDF_BYTES, "PDF"))
    resultado = []
    for firma in lector.embedded_signatures:
        cadena = [firma.signer_cert, *firma.other_embedded_certs]
        estado = await async_validate_pdf_signature(
            firma, ValidationContext(trust_roots=cadena, allow_fetching=False)
        )
        fecha = firma.self_reported_timestamp
        resultado.append({
            "campo": firma.field_name,
            "titular": datos_sujeto(firma.signer_cert.subject),
            "emisor": datos_sujeto(firma.signer_cert.issuer),
            "fecha_firma": fecha.isoformat() if fecha else None,
            "intacta": bool(estado.intact),
            "valida": bool(estado.valid),
            "cobertura": estado.coverage.name if estado.coverage else None,
            "modificaciones": estado.modification_level.name if estado.modification_level else None,
        })
    return {"firmas": resultado}


app.include_router(api)
