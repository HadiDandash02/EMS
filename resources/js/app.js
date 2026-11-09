import './bootstrap';
import './form-validation';   // instant front-end validation (the server still validates everything)
import flatpickr from 'flatpickr';
import { Arabic } from 'flatpickr/dist/l10n/ar.js';
import 'flatpickr/dist/flatpickr.min.css';

// Used by <x-date-input>: dates are SHOWN as dd/mm/yyyy but stored/sent as yyyy-mm-dd.
// The calendar follows the interface language (Arabic month and day names when the page is Arabic).
if (document.documentElement.lang === 'ar') {
    flatpickr.localize({ ...Arabic, firstDayOfWeek: 6 });
}
window.flatpickr = flatpickr;

// An expired session/CSRF token (HTTP 419) makes Livewire show a browser popup by default.
// Reload quietly instead: the user lands on the login page or the refreshed page with a fresh token.
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault();
                window.location.reload();
            }
        });
    });
});

// Charts (dashboard): Chart.js is a separate file that is downloaded only when a page really shows a chart.
let chartLib;
window.loadChart = () => (chartLib ??= import('chart.js/auto').then((m) => m.default));
