/** Shared Operate UI classes (ConnectWave desk). */
export const focusRing =
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950';

export const field = 'flex min-w-0 flex-col gap-1.5';

export const fieldLabel = 'text-sm text-slate-300';

export const control = `desk-control ${focusRing}`;

export const selectControl = `desk-control desk-select ${focusRing}`;

export const textareaControl = `desk-control desk-textarea ${focusRing}`;

/** Inline filters (queue, bids, provisioning). */
export const selectControlCompact = `desk-control desk-select min-w-[11rem] sm:w-44 ${focusRing}`;

export const inputControlCompact = `desk-control min-w-[11rem] flex-1 sm:w-52 sm:flex-none ${focusRing}`;

export const toolbar = 'desk-toolbar';

export const btnPrimary = `inline-flex h-11 shrink-0 items-center justify-center rounded-md bg-cyan-600 px-4 text-sm font-medium text-white hover:bg-cyan-500 disabled:cursor-not-allowed disabled:opacity-50 ${focusRing}`;

export const btnGhost = `inline-flex h-11 shrink-0 items-center justify-center rounded-md px-3 text-sm text-cyan-300 hover:bg-slate-800/80 disabled:cursor-not-allowed disabled:opacity-40 ${focusRing}`;

/** Desk area tabs (Support queue · Corporate bids · Provisioning). */
export const btnDeskTab = 'text-xs uppercase tracking-widest';

export const linkInline = `text-cyan-300 underline-offset-2 hover:underline ${focusRing} rounded-sm`;

/** Demo stack switcher (Laravel Desk · Legacy PHP · …). */
export const linkStack = `text-xs uppercase tracking-widest ${linkInline}`;

export const linkRow = `inline-flex min-h-11 items-center rounded-sm text-cyan-200 hover:text-cyan-100 hover:underline ${focusRing}`;

export const alertError =
    'rounded border border-rose-500/40 bg-rose-950/40 px-4 py-3 text-sm text-rose-100';

const formControlSelector = 'input, select, textarea, button, label';

/** Scroll to document top (tab switches, first paint). */
export function scrollToPageTop(headingId) {
    if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
    }
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
    window.scrollTo(0, 0);
    if (headingId) {
        const heading = document.getElementById(headingId);
        if (heading) {
            heading.scrollIntoView({ behavior: 'auto', block: 'start' });
        }
    }
    requestAnimationFrame(() => {
        window.scrollTo(0, 0);
    });
}

/** Scroll so the section `<h2 id="…">` sits at the top of the viewport. */
export function scrollToSectionHeading(headingId) {
    if (!headingId) {
        return;
    }
    const heading = document.getElementById(headingId);
    if (!heading) {
        return;
    }
    if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
    }
    heading.scrollIntoView({ behavior: 'auto', block: 'start' });
}

function isFormInteractionTarget(el) {
    if (!(el instanceof HTMLElement)) {
        return false;
    }
    if (el.matches(formControlSelector)) {
        return true;
    }
    return Boolean(el.closest(formControlSelector));
}

function isSelectControl(el) {
    if (!(el instanceof HTMLElement)) {
        return false;
    }
    if (el.matches('select') || el.closest('select')) {
        return true;
    }
    return Boolean(el.closest('[data-desk-select]'));
}

function isTextEntryControl(el) {
    if (!(el instanceof HTMLElement)) {
        return false;
    }
    const field = el.closest('input, textarea');
    if (field instanceof HTMLTextAreaElement) {
        return true;
    }
    if (field instanceof HTMLInputElement) {
        const type = field.type.toLowerCase();
        return !['button', 'submit', 'reset', 'checkbox', 'radio', 'file', 'hidden'].includes(type);
    }
    return false;
}

/** Use on section wrappers (`@click`) for inputs/buttons — never on focusin (breaks native selects). */
export function onSectionFormInteract(headingId, event) {
    if (!isFormInteractionTarget(event.target)) {
        return;
    }
    if (isSelectControl(event.target) || isTextEntryControl(event.target)) {
        return;
    }
    scrollToSectionHeading(headingId);
}

/** @deprecated DeskSelect handles menus in-DOM; avoid scrolling on pick. */
export function onSelectSectionScroll(_headingId) {}
