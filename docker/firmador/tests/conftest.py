import io
import os
import sys
from datetime import datetime, timedelta, timezone

import pytest
from cryptography import x509
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric import rsa
from cryptography.hazmat.primitives.serialization import BestAvailableEncryption, pkcs12
from cryptography.x509.oid import NameOID
from fastapi.testclient import TestClient
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
os.environ["FIRMADOR_TOKEN"] = "token-de-prueba"

from app import app  # noqa: E402

TOKEN = {"X-Firmador-Token": "token-de-prueba"}
CONTRASENA = "clave-p12"


def crear_p12(nombre: str, dias_desde: int = -1, dias_hasta: int = 365) -> bytes:
    clave = rsa.generate_private_key(public_exponent=65537, key_size=2048)
    sujeto = x509.Name([
        x509.NameAttribute(x509.ObjectIdentifier("1.3.6.1.1.1.1.0"), "8347375"),
        x509.NameAttribute(NameOID.COUNTRY_NAME, "BO"),
        x509.NameAttribute(NameOID.ORGANIZATION_NAME, "CAPITAL + SAFI S.A."),
        x509.NameAttribute(NameOID.TITLE, "OFICIAL DE INFRAESTRUCTURA"),
        x509.NameAttribute(NameOID.COMMON_NAME, nombre),
        x509.NameAttribute(NameOID.DN_QUALIFIER, "CI"),
    ])
    ahora = datetime.now(timezone.utc)
    certificado = (
        x509.CertificateBuilder()
        .subject_name(sujeto)
        .issuer_name(sujeto)
        .public_key(clave.public_key())
        .serial_number(x509.random_serial_number())
        .not_valid_before(ahora + timedelta(days=dias_desde))
        .not_valid_after(ahora + timedelta(days=dias_hasta))
        .add_extension(
            x509.KeyUsage(
                digital_signature=True, content_commitment=True, key_encipherment=False,
                data_encipherment=False, key_agreement=False, key_cert_sign=False,
                crl_sign=False, encipher_only=False, decipher_only=False,
            ),
            critical=True,
        )
        .sign(clave, hashes.SHA256())
    )
    return pkcs12.serialize_key_and_certificates(
        nombre.encode(), clave, certificado, None, BestAvailableEncryption(CONTRASENA.encode())
    )


def crear_pdf(paginas: int = 1, cifrado: bool = False) -> bytes:
    salida = io.BytesIO()
    c = canvas.Canvas(salida, pagesize=A4, encrypt="secreto" if cifrado else None)
    for numero in range(paginas):
        c.drawString(72, 750, f"Formulario de prueba - pagina {numero + 1}")
        c.showPage()
    c.save()
    return salida.getvalue()


@pytest.fixture(scope="session")
def cliente() -> TestClient:
    return TestClient(app)


@pytest.fixture(scope="session")
def p12_juanito() -> bytes:
    return crear_p12("JUANITO PEREZ")


@pytest.fixture(scope="session")
def p12_carlos() -> bytes:
    return crear_p12("CARLOS RUIZ")


@pytest.fixture(scope="session")
def p12_vencido() -> bytes:
    return crear_p12("PEDRO VENCIDO", dias_desde=-400, dias_hasta=-1)
