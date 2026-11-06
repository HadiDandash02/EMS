import './bootstrap';
import flatpickr from 'flatpickr';
import { Arabic } from 'flatpickr/dist/l10n/ar.js';
import 'flatpickr/dist/flatpickr.min.css';

// Used by <x-date-input>: dates are SHOWN as dd/mm/yyyy but stored/sent as yyyy-mm-dd.
// The calendar follows the interface language (Arabic month and day names when the page is Arabic).
if (document.documentElement.lang === 'ar') {
    flatpickr.localize({ ...Arabic, firstDayOfWeek: 6 });
}
window.flatpickr = flatpickr;
