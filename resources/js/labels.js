export const typeLabels = {
    fault: 'Fault',
    provisioning: 'Provisioning',
    number_port: 'Number port',
};

export const statusLabels = {
    open: 'Open',
    in_progress: 'In progress',
    waiting: 'Waiting',
    resolved: 'Resolved',
};

const locale = 'en-AU';

export function formatWhen(iso) {
    if (!iso) {
        return '—';
    }
    return new Date(iso).toLocaleString(locale, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

/** Calendar dates from API (`YYYY-MM-DD` or ISO datetime). */
export function formatDate(value) {
    if (!value) {
        return '—';
    }
    const str = String(value);
    const parts = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
    const date = parts
        ? new Date(Number(parts[1]), Number(parts[2]) - 1, Number(parts[3]))
        : new Date(str);
    if (Number.isNaN(date.getTime())) {
        return str;
    }
    return date.toLocaleDateString(locale, { dateStyle: 'medium' });
}

export function formatAud(value) {
    return new Intl.NumberFormat(locale, { style: 'currency', currency: 'AUD' }).format(Number(value));
}

export function statusLabel(code) {
    if (code === null || code === undefined || code === '') {
        return 'Created';
    }
    return statusLabels[code] ?? code;
}
