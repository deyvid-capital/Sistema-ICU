import json

from conftest import CONTRASENA, TOKEN, crear_pdf

CAMPOS = [
    {"nombre": "firma_1", "pagina": 1, "x": 60, "y": 60, "ancho": 200, "alto": 95},
    {"nombre": "firma_2", "pagina": 1, "x": 340, "y": 60, "ancho": 200, "alto": 95},
]
SELLO = json.dumps({"nombre": "Juanito Pérez", "cargo": "Oficial de Infraestructura", "fecha": "26/10/2026 10:30 BOT"})


def preparar(cliente, pdf: bytes, campos=CAMPOS):
    return cliente.post(
        "/pdf/preparar", headers=TOKEN,
        files={"pdf": ("doc.pdf", pdf, "application/pdf")}, data={"campos": json.dumps(campos)},
    )


def firmar(cliente, pdf: bytes, p12: bytes, campo: str, contrasena: str = CONTRASENA):
    return cliente.post(
        "/pdf/firmar", headers=TOKEN,
        files={"pdf": ("doc.pdf", pdf, "application/pdf"), "p12": ("firma.p12", p12, "application/x-pkcs12")},
        data={"contrasena": contrasena, "campo": campo, "sello": SELLO},
    )


def verificar(cliente, pdf: bytes):
    return cliente.post("/pdf/verificar", headers=TOKEN, files={"pdf": ("doc.pdf", pdf, "application/pdf")})


# --- Seguridad -------------------------------------------------------------


def test_salud_no_requiere_token(cliente):
    assert cliente.get("/salud").json() == {"estado": "ok"}


def test_rechaza_peticiones_sin_token(cliente):
    respuesta = cliente.post("/pdf/inspeccionar", files={"pdf": ("doc.pdf", crear_pdf(), "application/pdf")})
    assert respuesta.status_code == 401
    assert respuesta.json()["codigo"] == "no_autorizado"


def test_rechaza_token_incorrecto(cliente):
    respuesta = cliente.post(
        "/pdf/inspeccionar", headers={"X-Firmador-Token": "otro"},
        files={"pdf": ("doc.pdf", crear_pdf(), "application/pdf")},
    )
    assert respuesta.status_code == 401


# --- Certificados ------------------------------------------------------------


def test_inspecciona_certificado_valido(cliente, p12_juanito):
    respuesta = cliente.post(
        "/certificados/inspeccionar", headers=TOKEN,
        files={"p12": ("firma.p12", p12_juanito)}, data={"contrasena": CONTRASENA},
    )
    assert respuesta.status_code == 200
    datos = respuesta.json()
    assert datos["titular"]["cn"] == "JUANITO PEREZ"
    assert datos["titular"]["documento"] == "8347375"
    assert datos["titular"]["tipo_documento"] == "CI"
    assert datos["titular"]["cargo"] == "OFICIAL DE INFRAESTRUCTURA"
    assert datos["clave_corresponde"] is True
    assert datos["vigente"] is True
    assert len(datos["huella_sha256"]) == 64
    assert "digital_signature" in datos["uso_clave"]


def test_acepta_p12_con_bytes_extra_como_digicert(cliente, p12_juanito):
    respuesta = cliente.post(
        "/certificados/inspeccionar", headers=TOKEN,
        files={"p12": ("firma.p12", p12_juanito + b"\x00\x00")}, data={"contrasena": CONTRASENA},
    )
    assert respuesta.status_code == 200


def test_contrasena_incorrecta(cliente, p12_juanito):
    respuesta = cliente.post(
        "/certificados/inspeccionar", headers=TOKEN,
        files={"p12": ("firma.p12", p12_juanito)}, data={"contrasena": "incorrecta"},
    )
    assert respuesta.status_code == 422
    assert respuesta.json()["codigo"] == "contrasena_incorrecta"


def test_archivo_que_no_es_p12(cliente):
    respuesta = cliente.post(
        "/certificados/inspeccionar", headers=TOKEN,
        files={"p12": ("firma.p12", b"esto no es un certificado")}, data={"contrasena": CONTRASENA},
    )
    assert respuesta.json()["codigo"] == "p12_invalido"


def test_certificado_vencido_se_informa(cliente, p12_vencido):
    respuesta = cliente.post(
        "/certificados/inspeccionar", headers=TOKEN,
        files={"p12": ("firma.p12", p12_vencido)}, data={"contrasena": CONTRASENA},
    )
    assert respuesta.status_code == 200
    assert respuesta.json()["vigente"] is False


# --- PDF ---------------------------------------------------------------------


def test_inspecciona_pdf(cliente):
    respuesta = cliente.post(
        "/pdf/inspeccionar", headers=TOKEN, files={"pdf": ("doc.pdf", crear_pdf(paginas=2), "application/pdf")}
    )
    datos = respuesta.json()
    assert len(datos["paginas"]) == 2
    assert datos["paginas"][0] == {"numero": 1, "ancho": 595.28, "alto": 841.89, "rotacion": 0}
    assert datos["firmas"] == 0


def test_rechaza_archivo_que_no_es_pdf(cliente):
    respuesta = cliente.post("/pdf/inspeccionar", headers=TOKEN, files={"pdf": ("doc.pdf", b"hola")})
    assert respuesta.json()["codigo"] == "pdf_invalido"


def test_rechaza_pdf_cifrado(cliente):
    respuesta = cliente.post(
        "/pdf/inspeccionar", headers=TOKEN, files={"pdf": ("doc.pdf", crear_pdf(cifrado=True))}
    )
    assert respuesta.json()["codigo"] == "pdf_cifrado"


def test_prepara_campos_vacios(cliente):
    respuesta = preparar(cliente, crear_pdf())
    assert respuesta.status_code == 200
    inspeccion = cliente.post(
        "/pdf/inspeccionar", headers=TOKEN, files={"pdf": ("doc.pdf", respuesta.content)}
    ).json()
    assert set(inspeccion["campos_firma"]) == {"firma_1", "firma_2"}
    assert not inspeccion["campos_firma"]["firma_1"]["firmado"]
    assert inspeccion["campos_firma"]["firma_2"]["rect"] == [340, 60, 540, 155]


def test_preparar_rechaza_pagina_inexistente(cliente):
    campos = [{**CAMPOS[0], "pagina": 3}]
    assert preparar(cliente, crear_pdf(), campos).json()["codigo"] == "pagina_invalida"


def test_preparar_rechaza_campo_duplicado(cliente):
    preparado = preparar(cliente, crear_pdf()).content
    assert preparar(cliente, preparado, [CAMPOS[0]]).json()["codigo"] == "campo_duplicado"


# --- Firma -------------------------------------------------------------------


def test_firma_en_orden_libre_y_ambas_firmas_quedan_validas(cliente, p12_juanito, p12_carlos):
    preparado = preparar(cliente, crear_pdf()).content

    # Carlos firma primero el segundo recuadro; después Juanito el primero.
    con_carlos = firmar(cliente, preparado, p12_carlos, "firma_2")
    assert con_carlos.status_code == 200
    con_ambos = firmar(cliente, con_carlos.content, p12_juanito, "firma_1")
    assert con_ambos.status_code == 200

    firmas = verificar(cliente, con_ambos.content).json()["firmas"]
    assert [f["titular"]["cn"] for f in firmas] == ["CARLOS RUIZ", "JUANITO PEREZ"]
    assert all(f["intacta"] and f["valida"] for f in firmas)
    assert firmas[-1]["cobertura"] == "ENTIRE_FILE"


def test_firmar_conserva_el_documento_original_intacto(cliente, p12_juanito):
    preparado = preparar(cliente, crear_pdf()).content
    firmado = firmar(cliente, preparado, p12_juanito, "firma_1").content
    # Firma incremental: el archivo previo queda como prefijo exacto.
    assert firmado.startswith(preparado)


def test_firmar_con_contrasena_incorrecta(cliente, p12_juanito):
    preparado = preparar(cliente, crear_pdf()).content
    respuesta = firmar(cliente, preparado, p12_juanito, "firma_1", contrasena="mala")
    assert respuesta.json()["codigo"] == "contrasena_incorrecta"


def test_no_firma_con_certificado_vencido(cliente, p12_vencido):
    preparado = preparar(cliente, crear_pdf()).content
    assert firmar(cliente, preparado, p12_vencido, "firma_1").json()["codigo"] == "certificado_vencido"


def test_no_firma_campo_inexistente(cliente, p12_juanito):
    preparado = preparar(cliente, crear_pdf()).content
    assert firmar(cliente, preparado, p12_juanito, "firma_9").json()["codigo"] == "campo_inexistente"


def test_no_firma_dos_veces_el_mismo_campo(cliente, p12_juanito, p12_carlos):
    preparado = preparar(cliente, crear_pdf()).content
    firmado = firmar(cliente, preparado, p12_juanito, "firma_1").content
    assert firmar(cliente, firmado, p12_carlos, "firma_1").json()["codigo"] == "campo_ya_firmado"


def test_firma_con_logo(cliente, p12_juanito):
    with open("/logo/Logo-Firma.png", "rb") as archivo:
        logo = archivo.read()
    preparado = preparar(cliente, crear_pdf()).content
    respuesta = cliente.post(
        "/pdf/firmar", headers=TOKEN,
        files={
            "pdf": ("doc.pdf", preparado), "p12": ("firma.p12", p12_juanito),
            "logo": ("logo.png", logo, "image/png"),
        },
        data={"contrasena": CONTRASENA, "campo": "firma_1", "sello": SELLO},
    )
    assert respuesta.status_code == 200
    assert verificar(cliente, respuesta.content).json()["firmas"][0]["valida"]


def test_verificar_pdf_sin_firmas(cliente):
    assert verificar(cliente, crear_pdf()).json() == {"firmas": []}


def test_el_sello_se_reduce_si_el_recuadro_es_chico(cliente, p12_juanito):
    import pymupdf  # noqa: PLC0415

    campos = [{"nombre": "firma_1", "pagina": 1, "x": 60, "y": 60, "ancho": 150, "alto": 80}]
    preparado = preparar(cliente, crear_pdf(), campos).content
    with open("/logo/Logo-Firma.png", "rb") as archivo:
        logo = archivo.read()
    respuesta = cliente.post(
        "/pdf/firmar", headers=TOKEN,
        files={"pdf": ("doc.pdf", preparado), "p12": ("firma.p12", p12_juanito), "logo": ("logo.png", logo)},
        data={"contrasena": CONTRASENA, "campo": "firma_1", "sello": SELLO},
    )
    assert respuesta.status_code == 200

    # Todo el texto del sello queda dentro del recuadro de 150 × 80.
    pagina = pymupdf.open(stream=respuesta.content, filetype="pdf")[0]
    pagina.clean_contents()
    texto = pagina.get_text(clip=pymupdf.Rect(60, pagina.rect.height - 140, 210, pagina.rect.height - 60))
    for esperado in ("Firmado digitalmente por:", "Juanito", "26/10/2026"):
        assert esperado in texto
