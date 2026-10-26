/**
 * Canvas de posiciones de firma (Docs/firma-digital/05-pantallas.md#canvas-de-posiciones).
 *
 * Renderiza cada PDF con pdf.js y permite arrastrar un recuadro de tamaño fijo por
 * firmante. Las posiciones se guardan en puntos PDF (origen abajo-izquierda), que es
 * lo que espera el firmador; la conversión la hace el viewport de pdf.js.
 *
 * Se carga bajo demanda con `x-load` (solo en esta pantalla).
 */
import * as pdfjsLib from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

const COLORES = ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#4b5563'];

/** Aplica la matriz del viewport de pdf.js: puntos PDF → píxeles de pantalla. */
function aPantalla([a, b, c, d, e, f], x, y) {
    return [a * x + c * y + e, b * x + d * y + f];
}

/** Inversa de la matriz del viewport: píxeles de pantalla → puntos PDF. */
function aPdf([a, b, c, d, e, f], x, y) {
    const determinante = a * d - b * c;

    return [(d * (x - e) - c * (y - f)) / determinante, (-b * (x - e) + a * (y - f)) / determinante];
}

export default function canvasFirmas({ posiciones, documentos, firmantes, recuadro, sugeridas }) {
    // Fuera del estado reactivo: pdf.js usa campos privados que el proxy de Alpine rompe.
    const pdfs = {};

    return {
        /** Lista sincronizada con Livewire: [{documento_id, firmante_id, pagina, x, y}] */
        posiciones,
        documentos,
        firmantes: firmantes.map((firmante, indice) => ({ ...firmante, color: COLORES[indice % COLORES.length] })),
        recuadro,
        sugeridas,
        documentoActivo: documentos[0]?.id ?? null,
        firmanteActivo: firmantes[0]?.id ?? null,
        zoom: 'ancho',
        cargando: false,
        error: null,
        paginas: [],
        arrastre: null,

        init() {
            this.$nextTick(() => this.renderizar());

            this.$watch('documentoActivo', () => this.renderizar());
            this.$watch('zoom', () => this.renderizar());

            this._alRedimensionar = () => {
                if (this.zoom === 'ancho') {
                    this.renderizar();
                }
            };
            window.addEventListener('resize', this._alRedimensionar);
        },

        destroy() {
            window.removeEventListener('resize', this._alRedimensionar);
        },

        documento() {
            return this.documentos.find((documento) => documento.id === this.documentoActivo);
        },

        pdf(documento) {
            pdfs[documento.id] ??= pdfjsLib.getDocument({ url: documento.url, withCredentials: true }).promise;

            return pdfs[documento.id];
        },

        async renderizar() {
            const documento = this.documento();
            const contenedor = this.$refs.paginas;

            if (! documento || ! contenedor) {
                return;
            }

            this.cargando = true;
            this.error = null;

            try {
                const pdf = await this.pdf(documento);
                const anchoDisponible = contenedor.clientWidth - 16;
                const renderizables = [];

                for (let numero = 1; numero <= pdf.numPages; numero++) {
                    const pagina = await pdf.getPage(numero);
                    const base = pagina.getViewport({ scale: 1 });
                    const escala = this.zoom === 'ancho' ? anchoDisponible / base.width : Number(this.zoom);
                    const viewport = pagina.getViewport({ scale: escala });

                    renderizables.push({ pagina, viewport });
                }

                // Al estado reactivo solo pasan datos simples (tamaño, escala y matriz).
                this.paginas = renderizables.map(({ viewport }, indice) => ({
                    numero: indice + 1,
                    ancho: viewport.width,
                    alto: viewport.height,
                    escala: viewport.scale,
                    matriz: [...viewport.transform],
                }));
                await this.$nextTick();

                for (const [indice, { pagina, viewport }] of renderizables.entries()) {
                    const lienzo = contenedor.querySelector(`canvas[data-pagina="${indice + 1}"]`);
                    const ratio = window.devicePixelRatio || 1;

                    lienzo.width = Math.floor(viewport.width * ratio);
                    lienzo.height = Math.floor(viewport.height * ratio);
                    lienzo.style.width = `${viewport.width}px`;
                    lienzo.style.height = `${viewport.height}px`;

                    await pagina.render({
                        canvas: lienzo,
                        canvasContext: lienzo.getContext('2d'),
                        viewport,
                        transform: ratio !== 1 ? [ratio, 0, 0, ratio, 0, 0] : null,
                    }).promise;
                }
            } catch (error) {
                console.error(error);
                this.error = 'No se pudo mostrar el documento.';
            } finally {
                this.cargando = false;
            }
        },

        // --- Posiciones -----------------------------------------------------

        posicion(documentoId, firmanteId) {
            return this.posiciones.find((p) => p.documento_id === documentoId && p.firmante_id === firmanteId) ?? null;
        },

        firmante(firmanteId) {
            return this.firmantes.find((firmante) => firmante.id === firmanteId);
        },

        recuadrosDePagina(numero) {
            return this.posiciones.filter((p) => p.documento_id === this.documentoActivo && p.pagina === numero);
        },

        estiloPagina(pagina) {
            return `width:${pagina.ancho}px;height:${pagina.alto}px`;
        },

        /** Esquina superior izquierda del recuadro en píxeles de pantalla. */
        estiloRecuadro(posicion, pagina) {
            const [izquierda, arriba] = aPantalla(pagina.matriz, posicion.x, posicion.y + this.recuadro.alto);
            const color = this.firmante(posicion.firmante_id)?.color ?? '#2563eb';

            return [
                `left:${izquierda}px`,
                `top:${arriba}px`,
                `width:${this.recuadro.ancho * pagina.escala}px`,
                `height:${this.recuadro.alto * pagina.escala}px`,
                `border-color:${color}`,
                `background:${color}1a`,
                `font-size:${Math.max(7, 9.5 * pagina.escala)}px`,
            ].join(';');
        },

        /** Clic en una página: ubica (o mueve) el recuadro del firmante activo centrado en el clic. */
        colocar(evento, infoPagina) {
            if (this.arrastre || ! this.firmanteActivo) {
                return;
            }

            const rect = evento.currentTarget.getBoundingClientRect();
            const escala = infoPagina.escala;
            const izquierda = evento.clientX - rect.left - (this.recuadro.ancho * escala) / 2;
            const arriba = evento.clientY - rect.top - (this.recuadro.alto * escala) / 2;

            this.fijar(this.documentoActivo, this.firmanteActivo, infoPagina, izquierda, arriba);
            this.siguienteFirmanteSinUbicar();
        },

        iniciarArrastre(evento, posicion, infoPagina) {
            evento.preventDefault();
            evento.stopPropagation();

            const recuadro = evento.currentTarget;
            recuadro.setPointerCapture(evento.pointerId);

            // No cambia el firmante activo: el siguiente clic sigue ubicando al que falta.
            this.arrastre = {
                posicion,
                infoPagina,
                inicioX: evento.clientX,
                inicioY: evento.clientY,
                izquierda: recuadro.offsetLeft,
                arriba: recuadro.offsetTop,
            };
        },

        arrastrar(evento) {
            if (! this.arrastre) {
                return;
            }

            const { posicion, infoPagina, inicioX, inicioY, izquierda, arriba } = this.arrastre;

            this.fijar(
                posicion.documento_id,
                posicion.firmante_id,
                infoPagina,
                izquierda + evento.clientX - inicioX,
                arriba + evento.clientY - inicioY,
            );
        },

        terminarArrastre() {
            // Se suelta en el siguiente ciclo para que el clic de la página no reubique el recuadro.
            setTimeout(() => (this.arrastre = null), 0);
        },

        /** Convierte píxeles de pantalla a puntos PDF, sin dejar que el recuadro salga de la página. */
        fijar(documentoId, firmanteId, { numero, ancho, alto, escala, matriz }, izquierda, arriba) {
            const anchoPx = this.recuadro.ancho * escala;
            const altoPx = this.recuadro.alto * escala;

            izquierda = Math.min(Math.max(izquierda, 0), ancho - anchoPx);
            arriba = Math.min(Math.max(arriba, 0), alto - altoPx);

            const [x, ySuperior] = aPdf(matriz, izquierda, arriba);
            const nueva = {
                documento_id: documentoId,
                firmante_id: firmanteId,
                pagina: numero,
                x: Math.round(x * 100) / 100,
                y: Math.round((ySuperior - this.recuadro.alto) * 100) / 100,
            };

            this.posiciones = [
                ...this.posiciones.filter((p) => ! (p.documento_id === documentoId && p.firmante_id === firmanteId)),
                nueva,
            ];
        },

        quitar(firmanteId) {
            this.posiciones = this.posiciones.filter(
                (p) => ! (p.documento_id === this.documentoActivo && p.firmante_id === firmanteId),
            );
        },

        siguienteFirmanteSinUbicar() {
            const pendiente = this.firmantes.find((firmante) => ! this.posicion(this.documentoActivo, firmante.id));

            if (pendiente) {
                this.firmanteActivo = pendiente.id;
            }
        },

        // --- Atajos -----------------------------------------------------------

        ubicadosEn(documentoId) {
            return this.firmantes.filter((firmante) => this.posicion(documentoId, firmante.id)).length;
        },

        documentoCompleto(documentoId) {
            return this.ubicadosEn(documentoId) === this.firmantes.length;
        },

        /** Copia las posiciones del documento activo a los demás (si la página existe). */
        copiarATodos() {
            const origen = this.posiciones.filter((p) => p.documento_id === this.documentoActivo);
            const copias = [];

            for (const documento of this.documentos) {
                if (documento.id === this.documentoActivo) {
                    continue;
                }

                for (const posicion of origen) {
                    if (posicion.pagina <= documento.paginas) {
                        copias.push({ ...posicion, documento_id: documento.id });
                    }
                }
            }

            const destinos = new Set(copias.map((p) => `${p.documento_id}:${p.firmante_id}`));

            this.posiciones = [
                ...this.posiciones.filter((p) => ! destinos.has(`${p.documento_id}:${p.firmante_id}`)),
                ...copias,
            ];
        },

        usarSugeridas() {
            const destinos = new Set(this.sugeridas.map((p) => `${p.documento_id}:${p.firmante_id}`));

            this.posiciones = [
                ...this.posiciones.filter((p) => ! destinos.has(`${p.documento_id}:${p.firmante_id}`)),
                ...this.sugeridas,
            ];
        },
    };
}
