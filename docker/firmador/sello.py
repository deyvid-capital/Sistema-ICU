"""Apariencia visible de la firma (aprobada en la prueba de concepto).

Se dibuja con reportlab como un mini-PDF del tamaño del recuadro y pyHanko la usa
como apariencia fija (StaticStampStyle). TextStampStyle no sirve: admite una sola
fuente y un solo color, y espacia mal las fuentes TrueType.
"""

import binascii
import io
import uuid
from dataclasses import dataclass

from reportlab.lib.colors import HexColor
from reportlab.lib.utils import ImageReader
from reportlab.pdfgen import canvas

from pyhanko import stamp
from pyhanko.pdf_utils import layout
from pyhanko.pdf_utils.content import ImportedPdfPage
from pyhanko.pdf_utils.layout import BoxConstraints
from pyhanko.pdf_utils.reader import PdfFileReader

ALTO_LOGO = 35
TAMANO_LETRA = 9.5
INTERLINEADO = 1.4
COLOR_TEXTO = HexColor("#1F3A8A")
FUENTE_TITULO = "Times-Bold"
FUENTE_TEXTO = "Times-Roman"
TITULO = "Firmado digitalmente por:"


@dataclass(frozen=True)
class DatosSello:
    nombre: str
    cargo: str
    fecha: str
    ancho: float = 200
    alto: float = 95


class PaginaEnMemoria(ImportedPdfPage):
    """Como ImportedPdfPage, pero desde bytes: el sello nunca toca el disco."""

    def __init__(self, contenido: bytes):
        self.contenido = contenido
        super().__init__(file_name=None)

    def render(self) -> bytes:
        escritor = self._ensure_writer
        xobj = escritor.import_page_as_xobject(PdfFileReader(io.BytesIO(self.contenido)), page_ix=0)
        nombre = b"/Import" + binascii.hexlify(uuid.uuid4().bytes)
        self.resources.xobject[nombre.decode("ascii")] = xobj
        x1, y1, x2, y2 = xobj.get_object()["/BBox"]
        self.box = BoxConstraints(width=abs(x1 - x2), height=abs(y1 - y2))
        return nombre + b" Do"


def dibujar_sello(datos: DatosSello, logo: bytes | None) -> bytes:
    """Logo centrado arriba, título en negrita y bloque de datos alineado a la izquierda.

    Se dibuja a su tamaño natural (letra de 9,5 pt); pyHanko lo reduce
    proporcionalmente si el recuadro configurado es más chico, así nunca se corta.
    """
    lineas = [linea for linea in (datos.nombre, datos.cargo, datos.fecha) if linea]
    paso = TAMANO_LETRA * INTERLINEADO
    medir = canvas.Canvas(io.BytesIO())
    ancho_texto = max(
        [medir.stringWidth(TITULO, FUENTE_TITULO, TAMANO_LETRA)]
        + [medir.stringWidth(t, FUENTE_TEXTO, TAMANO_LETRA) for t in lineas]
    )
    alto_natural = 2 + (ALTO_LOGO if logo else 0) + paso * (1 + len(lineas)) + 4
    ancho = max(datos.ancho, ancho_texto + 4)
    alto = max(datos.alto, alto_natural)

    salida = io.BytesIO()
    c = canvas.Canvas(salida, pagesize=(ancho, alto))

    y = alto - 2
    if logo:
        imagen = ImageReader(io.BytesIO(logo))
        ancho_img, alto_img = imagen.getSize()
        ancho_logo = ALTO_LOGO * ancho_img / alto_img
        y -= ALTO_LOGO
        c.drawImage(imagen, (ancho - ancho_logo) / 2, y, ancho_logo, ALTO_LOGO, mask="auto")

    c.setFillColor(COLOR_TEXTO)
    y -= paso
    c.setFont(FUENTE_TITULO, TAMANO_LETRA)
    c.drawCentredString(ancho / 2, y, TITULO)

    if lineas:
        ancho_bloque = max(c.stringWidth(t, FUENTE_TEXTO, TAMANO_LETRA) for t in lineas)
        x = max((ancho - ancho_bloque) / 2, 2)
        c.setFont(FUENTE_TEXTO, TAMANO_LETRA)
        for linea in lineas:
            y -= paso
            c.drawString(x, y, linea)

    c.save()
    return salida.getvalue()


def estilo_sello(datos: DatosSello, logo: bytes | None) -> stamp.StaticStampStyle:
    return stamp.StaticStampStyle(
        background=PaginaEnMemoria(dibujar_sello(datos, logo)),
        border_width=0,
        background_opacity=1.0,
        background_layout=layout.SimpleBoxLayoutRule(
            x_align=layout.AxisAlignment.ALIGN_MID,
            y_align=layout.AxisAlignment.ALIGN_MID,
        ),
    )
