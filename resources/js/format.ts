export function formatUsd(amount: string | number): string {
    const value = Number(amount);

    if (Number.isNaN(value)) {
        return 'USD —';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    if (!year || !month || !day) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-EC', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(year, month - 1, day));
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-EC', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
