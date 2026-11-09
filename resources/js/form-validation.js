/*
 * FRONT-END VALIDATION (instant feedback). The server ALWAYS validates again (Laravel rules / Form Requests):
 * this file only saves the user a round trip and tells them at once what to complete.
 *
 * How it works - no extra markup per field:
 *   <form novalidate data-validate> turns it on (novalidate hides the browser's own bubbles).
 *   Fields use the STANDARD attributes: required, minlength, maxlength, pattern, type="email".
 *   Extra: data-strong (password policy), data-match="<id of the other field>", data-pattern-message="...".
 *   <x-searchable-select required> mirrors its value into a hidden input so it can be required too.
 * Messages come from window.validationMessages (translated in the layout by <x-validation-messages>).
 */
const messages = () => window.validationMessages || {};
const fill = (text, values) => Object.entries(values).reduce((t, [k, v]) => t.replace(':' + k, v), text || '');

function strongPassword(value) {
    return value.length >= 10 && /[a-z]/.test(value) && /[A-Z]/.test(value) && /[0-9]/.test(value) && /[^A-Za-z0-9]/.test(value);
}

/** The message for the first broken rule of a field, or '' when it is fine. */
function problem(el) {
    const m = messages();
    const value = el.value ?? '';
    const empty = value.trim() === '';

    if (el.required && empty) return m.required;
    if (empty) return '';                                            // optional and empty: nothing to check

    if (el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return m.email;
    if (el.minLength > 0 && value.length < el.minLength) return fill(m.minlength, { min: el.minLength });
    if (el.maxLength > 0 && value.length > el.maxLength) return fill(m.maxlength, { max: el.maxLength });

    const pattern = el.getAttribute('pattern');
    if (pattern && !new RegExp('^(?:' + pattern + ')$').test(value)) return el.dataset.patternMessage || m.pattern;

    if (el.hasAttribute('data-strong') && !strongPassword(value)) return m.strong;

    const other = el.dataset.match && el.form?.querySelector('#' + CSS.escape(el.dataset.match));
    if (other && other.value !== value) return m.match;

    return '';
}

/** Fields that take part: real inputs/textareas (and the hidden mirrors of searchable selects). */
const fields = (form) => [...form.querySelectorAll('input, textarea')].filter((el) => !['hidden', 'checkbox', 'radio', 'file', 'button', 'submit'].includes(el.type) && !el.disabled && !el.closest('[data-skip-validation]'));

function show(el, text) {
    const host = el.closest('[data-select-host]') || el;
    const ring = host === el ? el : (host.querySelector('button') || host);
    let note = host.parentElement.querySelector(':scope > p[data-client-error="' + (el.id || el.name) + '"]');

    if (!text) {
        note?.remove();
        ring.classList.remove('!ring-rose-400');
        el.removeAttribute('aria-invalid');
        return;
    }

    if (!note) {
        note = document.createElement('p');
        note.dataset.clientError = el.id || el.name;
        note.className = 'mt-1 text-sm text-rose-600';
        note.setAttribute('role', 'alert');
        host.after(note);
    }
    note.textContent = text;
    ring.classList.add('!ring-rose-400');
    el.setAttribute('aria-invalid', 'true');
}

const check = (el) => { const text = problem(el); show(el, text); return text === ''; };

// leaving a field validates it; afterwards every keystroke re-checks it (so the message disappears as soon as it is fixed)
document.addEventListener('focusout', (e) => {
    const form = e.target.closest?.('form[data-validate]');
    // moving to the submit button: let the submit handler check everything (a message appearing now would shift the button away from the click)
    if (e.relatedTarget?.matches?.('[type=submit]')) return;
    if (form && fields(form).includes(e.target)) { e.target.dataset.touched = '1'; check(e.target); }
}, true);

document.addEventListener('input', (e) => {
    const form = e.target.closest?.('form[data-validate]');
    if (form && e.target.dataset.touched && fields(form).includes(e.target)) check(e.target);
}, true);

// submitting checks everything; if anything is wrong the request is NOT sent (the server would refuse it anyway)
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!form.matches?.('form[data-validate]')) return;

    const bad = fields(form).filter((el) => { el.dataset.touched = '1'; return !check(el); });
    if (bad.length === 0) return;

    e.preventDefault();
    e.stopImmediatePropagation();                                    // Livewire's wire:submit never sees it
    const first = bad[0].closest('[data-select-host]')?.querySelector('button') || bad[0];
    first.scrollIntoView({ block: 'center', behavior: 'smooth' });
    first.focus({ preventScroll: true });
}, true);
