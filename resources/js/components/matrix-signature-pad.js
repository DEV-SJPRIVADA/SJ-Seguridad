/**
 * Captura de firma por dibujo o reutilización de la firma del perfil.
 *
 * Escribe dos inputs ocultos: `signature_mode[<fieldKey>]` y, si se dibuja,
 * `signature_canvas[<fieldKey>]` con un PNG en base64. La validación real
 * (formato y obligatoriedad) la repite el backend.
 */
export default function matrixSignaturePad(config = {}) {
    return {
        fieldKey: config.fieldKey || '',
        profileUri: config.profileUri || '',
        required: Boolean(config.required),
        existingUrl: config.existingUrl || '',
        mode: config.mode || '',
        dataUrl: '',
        canvas: null,
        context: null,
        drawing: false,
        empty: true,

        init() {
            this.$nextTick(() => {
                this.canvas = this.$root.querySelector('canvas');

                if (! this.canvas) {
                    return;
                }

                this.context = this.canvas.getContext('2d');
                this.prepareCanvas();
            });
        },

        get hasSignature() {
            return this.mode === 'profile' ? Boolean(this.profileUri) : Boolean(this.dataUrl);
        },

        prepareCanvas() {
            const ratio = window.devicePixelRatio || 1;
            const rect = this.canvas.getBoundingClientRect();
            const width = rect.width || 600;
            const height = 180;

            this.canvas.width = width * ratio;
            this.canvas.height = height * ratio;
            this.canvas.style.height = `${height}px`;

            this.context.setTransform(ratio, 0, 0, ratio, 0, 0);
            this.context.lineWidth = 2.2;
            this.context.lineCap = 'round';
            this.context.lineJoin = 'round';
            this.context.strokeStyle = '#10233f';
            this.clearCanvas();
        },

        useProfile() {
            this.mode = 'profile';
            this.dataUrl = '';
            this.empty = ! this.profileUri;
        },

        startDraw() {
            this.mode = 'draw';

            if (this.empty) {
                this.clearCanvas();
            }
        },

        clearSelection() {
            this.mode = '';
            this.dataUrl = '';
            this.empty = true;
            this.clearCanvas();
        },

        clearCanvas() {
            if (! this.context || ! this.canvas) {
                return;
            }

            this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.empty = true;
            this.dataUrl = '';
        },

        point(event) {
            const rect = this.canvas.getBoundingClientRect();

            return {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top,
            };
        },

        begin(event) {
            if (this.mode !== 'draw') {
                this.mode = 'draw';
            }

            this.drawing = true;
            const { x, y } = this.point(event);

            this.context.beginPath();
            this.context.moveTo(x, y);
            this.canvas.setPointerCapture?.(event.pointerId);
        },

        move(event) {
            if (! this.drawing) {
                return;
            }

            const { x, y } = this.point(event);
            this.context.lineTo(x, y);
            this.context.stroke();
            this.empty = false;
        },

        end() {
            if (! this.drawing) {
                return;
            }

            this.drawing = false;
            this.dataUrl = this.canvas.toDataURL('image/png');
        },
    };
}
