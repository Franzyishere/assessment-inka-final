import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.flatpickr = flatpickr;

export function registerDateTimePicker() {
    Alpine.data('dateTimePicker', (initialDate = '', initialTime = '', defaultTime = '08:00') => ({
        date: initialDate || '',
        time: initialTime || '',
        fp: null,
        get combinedValue() {
            if (!this.date) return '';
            const t = this.time && this.time.trim() ? this.time.trim() : defaultTime;
            return `${this.date} ${t}`;
        },
        init() {
            this.$nextTick(() => {
                const targetInput = this.$refs.dateInput;
                if (!targetInput) return;

                this.fp = flatpickr(targetInput, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd M Y',
                    altInputClass: targetInput.className,
                    allowInput: false,
                    clickOpens: true,
                    monthSelectorType: 'static',
                    defaultDate: this.date || null,
                    onChange: (selectedDates, dateStr) => {
                        this.date = dateStr;
                        if (!this.time) {
                            this.time = defaultTime;
                        }
                    },
                });
            });
        },
        clearDate() {
            this.date = '';
            this.time = '';
            if (this.fp) {
                this.fp.clear();
            }
        },
        formatTime(e) {
            let val = e.target.value.replace(/[^0-9:]/g, '');
            this.time = val;
        },
        normalizeTime() {
            if (!this.time) return;
            // Preserve the hours/minutes boundary before handling compact HHmm input.
            // Stripping ':' first turns 12:3 into 0123 instead of 12:03.
            let raw = this.time.trim();
            if (raw.includes(':')) {
                const [hours, minutes] = raw.split(':');
                const h = Math.min(23, Math.max(0, parseInt(hours, 10) || 0));
                const m = Math.min(59, Math.max(0, parseInt(minutes, 10) || 0));
                this.time = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                return;
            }
            raw = raw.replace(/[^0-9]/g, '');
            if (raw.length === 3) {
                raw = '0' + raw;
            }
            if (raw.length === 4) {
                let h = Math.min(23, Math.max(0, parseInt(raw.substring(0, 2), 10)));
                let m = Math.min(59, Math.max(0, parseInt(raw.substring(2, 4), 10)));
                this.time = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                return;
            }
            let parts = this.time.split(':');
            let h = parts[0] !== undefined && parts[0] !== '' ? parseInt(parts[0], 10) : 0;
            let m = parts[1] !== undefined && parts[1] !== '' ? parseInt(parts[1], 10) : 0;
            if (isNaN(h)) h = 0;
            if (isNaN(m)) m = 0;
            h = Math.min(23, Math.max(0, h));
            m = Math.min(59, Math.max(0, m));
            this.time = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
        },
    }));
}
