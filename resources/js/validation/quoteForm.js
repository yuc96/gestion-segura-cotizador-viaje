function parseDate(value) {
    if (!value) {
        return null;
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    const date = new Date(year, month - 1, day);

    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
        return null;
    }

    return date;
}

function startOfToday() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return today;
}

function ageAt(birth, onDate) {
    let age = onDate.getFullYear() - birth.getFullYear();
    const month = onDate.getMonth() - birth.getMonth();

    if (month < 0 || (month === 0 && onDate.getDate() < birth.getDate())) {
        age -= 1;
    }

    return age;
}

const namePattern = /^[\p{L}\s'.-]+$/u;

export function validateQuoteForm(form) {
    const errors = {};

    if (!form.first_name || form.first_name.trim().length < 2) {
        errors.first_name = 'Ingresa los nombres del asegurado.';
    } else if (!namePattern.test(form.first_name.trim())) {
        errors.first_name = 'Los nombres solo pueden contener letras.';
    }

    if (!form.last_name || form.last_name.trim().length < 2) {
        errors.last_name = 'Ingresa los apellidos del asegurado.';
    } else if (!namePattern.test(form.last_name.trim())) {
        errors.last_name = 'Los apellidos solo pueden contener letras.';
    }

    if (!form.identification || form.identification.trim().length < 5) {
        errors.identification = 'Ingresa el número de identificación.';
    } else if (!/^[A-Za-z0-9]+$/.test(form.identification.trim())) {
        errors.identification = 'La identificación solo puede contener letras y números.';
    }

    if (!form.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
        errors.email = 'Ingresa un correo electrónico válido.';
    }

    const birth = parseDate(form.birth_date);
    const departure = parseDate(form.departure_date);
    const returning = parseDate(form.return_date);
    const today = startOfToday();

    if (!birth) {
        errors.birth_date = 'Ingresa la fecha de nacimiento.';
    } else if (birth >= today) {
        errors.birth_date = 'La fecha de nacimiento debe ser anterior a hoy.';
    }

    if (!form.destination_code) {
        errors.destination_code = 'Selecciona el país de destino.';
    }

    if (!departure) {
        errors.departure_date = 'Ingresa la fecha de salida.';
    } else if (departure < today) {
        errors.departure_date = 'La salida no puede ser anterior a hoy.';
    }

    if (!returning) {
        errors.return_date = 'Ingresa la fecha de regreso.';
    } else if (departure && returning < departure) {
        errors.return_date = 'El regreso no puede ser anterior a la salida.';
    } else if (departure) {
        const days = Math.round((returning - departure) / 86400000) + 1;

        if (days > 365) {
            errors.return_date = 'El viaje no puede superar 365 días.';
        }
    }

    if (birth && departure && !errors.birth_date) {
        const age = ageAt(birth, departure);

        if (age < 18) {
            errors.birth_date = 'El asegurado debe tener al menos 18 años en la fecha de salida.';
        } else if (age > 85) {
            errors.birth_date = 'El asegurado no puede superar los 85 años en la fecha de salida.';
        }
    }

    return errors;
}
