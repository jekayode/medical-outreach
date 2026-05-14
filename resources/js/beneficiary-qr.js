import { Html5Qrcode } from 'html5-qrcode';

window.MedicalOutreachQr = {
    /** @type {Html5Qrcode|null} */
    _scanner: null,

    stop() {
        if (! this._scanner) {
            return;
        }

        const scanner = this._scanner;
        this._scanner = null;

        scanner
            .stop()
            .then(() => scanner.clear())
            .catch(() => {});
    },

    /**
     * @param {string} elementId
     * @param {(decodedText: string) => void} onDecoded
     * @returns {Promise<void>}
     */
    start(elementId, onDecoded) {
        this.stop();

        const scanner = new Html5Qrcode(elementId);
        this._scanner = scanner;

        return scanner
            .start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    if (decodedText) {
                        this.stop();
                        onDecoded(decodedText);
                    }
                },
                () => {},
            )
            .catch((error) => {
                this.stop();
                throw error;
            });
    },
};
