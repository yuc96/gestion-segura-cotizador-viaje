export function maskDayMonthYear(input: string): string {
    const digits = input.replace(/\D/g, '').slice(0, 8);
    const day = digits.slice(0, 2);
    const month = digits.slice(2, 4);
    const year = digits.slice(4, 8);
    let masked = day;

    if (digits.length > 2) {
        masked += `/${month}`;
    }

    if (digits.length > 4) {
        masked += `/${year}`;
    }

    return masked;
}

export function isoFromDisplay(display: string): string | null {
    const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(display);

    if (!match) {
        return null;
    }

    const day = Number(match[1]);
    const month = Number(match[2]);
    const year = Number(match[3]);
    const date = new Date(year, month - 1, day);

    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
        return null;
    }

    return `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

export function displayFromIso(iso: string): string {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso);

    if (!match) {
        return '';
    }

    return `${match[3]}/${match[2]}/${match[1]}`;
}

export const MONTHS_ES = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
];

export function daysInMonth(year: number, monthIndex: number): number {
    return new Date(year, monthIndex + 1, 0).getDate();
}
