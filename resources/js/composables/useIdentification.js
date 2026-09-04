/**
 * Composable para normalización, formateo (sin puntos) y validación
 * de documentos de identidad con dígito verificador para todos los países soportados.
 */

const COUNTRY_CONFIGS = {
    CL: {
        code: 'CL',
        name: 'Chile',
        docName: 'RUT',
        placeholder: '12345678-K',
        flag: '🇨🇱',
        phonePrefix: '+56',
        phonePlaceholder: '+56 9 1234 5678',
    },
    CO: {
        code: 'CO',
        name: 'Colombia',
        docName: 'Cédula / NIT',
        placeholder: '900123456-1',
        flag: '🇨🇴',
        phonePrefix: '+57',
        phonePlaceholder: '+57 300 123 4567',
    },
    AR: {
        code: 'AR',
        name: 'Argentina',
        docName: 'DNI / CUIT',
        placeholder: '20-12345678-9',
        flag: '🇦🇷',
        phonePrefix: '+54',
        phonePlaceholder: '+54 9 11 1234 5678',
    },
    PE: {
        code: 'PE',
        name: 'Perú',
        docName: 'DNI / RUC',
        placeholder: '12345678',
        flag: '🇵🇪',
        phonePrefix: '+51',
        phonePlaceholder: '+51 912 345 678',
    },
    BR: {
        code: 'BR',
        name: 'Brasil',
        docName: 'CPF / CNPJ',
        placeholder: '123456789-00',
        flag: '🇧🇷',
        phonePrefix: '+55',
        phonePlaceholder: '+55 11 91234 5678',
    },
    EC: {
        code: 'EC',
        name: 'Ecuador',
        docName: 'Cédula / RUC',
        placeholder: '1712345678',
        flag: '🇪🇨',
        phonePrefix: '+593',
        phonePlaceholder: '+593 99 123 4567',
    },
    MX: {
        code: 'MX',
        name: 'México',
        docName: 'RFC / CURP',
        placeholder: 'XAXX010101000',
        flag: '🇲🇽',
        phonePrefix: '+52',
        phonePlaceholder: '+52 55 1234 5678',
    },
    BO: {
        code: 'BO',
        name: 'Bolivia',
        docName: 'CI / NIT',
        placeholder: '1234567',
        flag: '🇧🇴',
        phonePrefix: '+591',
        phonePlaceholder: '+591 7123 4567',
    },
    PY: {
        code: 'PY',
        name: 'Paraguay',
        docName: 'CI / RUC',
        placeholder: '1234567-8',
        flag: '🇵🇾',
        phonePrefix: '+595',
        phonePlaceholder: '+595 981 123 456',
    },
    UY: {
        code: 'UY',
        name: 'Uruguay',
        docName: 'CI / RUT',
        placeholder: '1234567-8',
        flag: '🇺🇾',
        phonePrefix: '+598',
        phonePlaceholder: '+598 91 234 567',
    },
};

export function useIdentification() {
    /**
     * Limpia cualquier formato previo (puntos, espacios, slashes).
     */
    const cleanIdentification = (value) => {
        if (!value) return '';
        return String(value)
            .replace(/[.\s/]/g, '')
            .toUpperCase()
            .trim();
    };

    /**
     * Validación Módulo 11 para RUT chileno.
     */
    const validateRut = (value) => {
        const clean = cleanIdentification(value);
        const stripped = clean.replace(/[^0-9K]/g, '');
        if (stripped.length < 7 || stripped.length > 10) return false;

        const body = stripped.slice(0, -1);
        const dv = stripped.slice(-1);

        if (!/^\d+$/.test(body)) return false;

        let sum = 0;
        let factor = 2;
        for (let i = body.length - 1; i >= 0; i--) {
            sum += parseInt(body[i], 10) * factor;
            factor = factor === 7 ? 2 : factor + 1;
        }

        const res = 11 - (sum % 11);
        let expected = '';
        if (res === 11) expected = '0';
        else if (res === 10) expected = 'K';
        else expected = String(res);

        return expected === dv;
    };

    /**
     * Formatea RUT chileno sin puntos: "1234" -> "123-4", "12345678-9" o "12345678-K".
     * Si tiene menos de 4 dígitos (ej: "123"), se mantiene limpio para permitir escritura fluida.
     */
    const formatRut = (value) => {
        const clean = cleanIdentification(value);
        const stripped = clean.replace(/[^0-9K]/g, '');
        if (stripped.length < 4) return stripped;

        const body = stripped.slice(0, -1);
        const dv = stripped.slice(-1);

        return `${body}-${dv}`;
    };

    /**
     * Validación de Cédula o NIT colombiano (Módulo 11 DIAN).
     */
    const validateColombianId = (value) => {
        const clean = cleanIdentification(value);
        if (!clean) return false;

        if (clean.includes('-')) {
            const parts = clean.split('-');
            const body = parts[0].replace(/\D/g, '');
            const dv = parts[1]?.replace(/\D/g, '');

            if (body.length < 5 || body.length > 15 || dv?.length !== 1) return false;

            const weights = [41, 37, 29, 23, 19, 17, 13, 7, 3];
            const bodyLen = body.length;
            let sum = 0;

            for (let i = 0; i < bodyLen; i++) {
                const posFromRight = bodyLen - 1 - i;
                const weight = posFromRight < weights.length ? weights[weights.length - 1 - posFromRight] : 0;
                sum += parseInt(body[i], 10) * weight;
            }

            const remainder = sum % 11;
            const expected = remainder === 0 || remainder === 1 ? remainder : 11 - remainder;

            return parseInt(dv, 10) === expected;
        }

        const digits = clean.replace(/\D/g, '');
        return digits.length >= 5 && digits.length <= 11 && /^\d+$/.test(digits);
    };

    /**
     * Formatea identificación colombiana sin puntos.
     */
    const formatColombianId = (value) => {
        const clean = cleanIdentification(value);
        if (clean.includes('-')) {
            const [body, dv] = clean.split('-');
            const cleanBody = body.replace(/\D/g, '');
            const cleanDv = (dv || '').replace(/\D/g, '');
            return cleanDv ? `${cleanBody}-${cleanDv}` : cleanBody;
        }
        return clean.replace(/\D/g, '');
    };

    /**
     * Validación de DNI o CUIT argentino (Módulo 11).
     */
    const validateArgentineId = (value) => {
        const digits = cleanIdentification(value).replace(/\D/g, '');

        if (digits.length === 11) {
            const multipliers = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
            let sum = 0;
            for (let i = 0; i < 10; i++) {
                sum += parseInt(digits[i], 10) * multipliers[i];
            }
            const res = 11 - (sum % 11);
            let expected = res;
            if (res === 11) expected = 0;
            else if (res === 10) expected = 9;

            return parseInt(digits[10], 10) === expected;
        }

        return (digits.length === 7 || digits.length === 8) && /^\d+$/.test(digits);
    };

    /**
     * Formatea CUIT argentino sin puntos progresivamente: 20-12345678-9.
     */
    const formatArgentineId = (value) => {
        const digits = cleanIdentification(value).replace(/\D/g, '');
        if (digits.length >= 11) {
            return `${digits.slice(0, 2)}-${digits.slice(2, 10)}-${digits.slice(10, 11)}`;
        }
        if (digits.length >= 3) {
            return `${digits.slice(0, 2)}-${digits.slice(2)}`;
        }
        return digits;
    };

    /**
     * Validación DNI / RUC peruano.
     */
    const validatePeruvianId = (value) => {
        const digits = cleanIdentification(value).replace(/\D/g, '');

        if (digits.length === 8) return true;

        if (digits.length === 11) {
            const prefix = digits.slice(0, 2);
            if (!['10', '15', '16', '17', '20'].includes(prefix)) return false;

            const multipliers = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
            let sum = 0;
            for (let i = 0; i < 10; i++) {
                sum += parseInt(digits[i], 10) * multipliers[i];
            }
            const res = 11 - (sum % 11);
            let expected = res;
            if (res === 10) expected = 0;
            else if (res === 11) expected = 1;

            return parseInt(digits[10], 10) === expected;
        }

        return false;
    };

    /**
     * Validación CPF / CNPJ brasileño.
     */
    const validateBrazilianId = (value) => {
        const digits = cleanIdentification(value).replace(/\D/g, '');

        if (digits.length === 11) {
            if (/^(\d)\1{10}$/.test(digits)) return false;

            for (let t = 9; t < 11; t++) {
                let d = 0;
                for (let c = 0; c < t; c++) {
                    d += parseInt(digits[c], 10) * (t + 1 - c);
                }
                d = ((10 * d) % 11) % 10;
                if (parseInt(digits[t], 10) !== d) return false;
            }
            return true;
        }

        if (digits.length === 14) {
            if (/^(\d)\1{13}$/.test(digits)) return false;

            const calc = (len) => {
                const multipliers = len === 12
                    ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
                    : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
                let sum = 0;
                for (let i = 0; i < len; i++) {
                    sum += parseInt(digits[i], 10) * multipliers[i];
                }
                const remainder = sum % 11;
                return remainder < 2 ? 0 : 11 - remainder;
            };

            return parseInt(digits[12], 10) === calc(12) && parseInt(digits[13], 10) === calc(13);
        }

        return false;
    };

    /**
     * Formatea CPF o CNPJ sin puntos (123456789-00 / 123456780001-90).
     */
    const formatBrazilianId = (value) => {
        const digits = cleanIdentification(value).replace(/\D/g, '');
        if (digits.length === 11) {
            return `${digits.slice(0, 9)}-${digits.slice(9, 11)}`;
        }
        if (digits.length === 14) {
            return `${digits.slice(0, 12)}-${digits.slice(12, 14)}`;
        }
        return digits;
    };

    /**
     * Validación Cédula / RUC ecuatoriano.
     */
    const validateEcuadorianId = (value) => {
        const digits = cleanIdentification(value).replace(/\D/g, '');

        if (digits.length === 10 || digits.length === 13) {
            const province = parseInt(digits.slice(0, 2), 10);
            if ((province < 1 || province > 24) && province !== 30) return false;

            const third = parseInt(digits[2], 10);
            if (third < 6) {
                const coefficients = [2, 1, 2, 1, 2, 1, 2, 1, 2];
                let sum = 0;
                for (let i = 0; i < 9; i++) {
                    const val = parseInt(digits[i], 10) * coefficients[i];
                    sum += val >= 10 ? val - 9 : val;
                }
                const res = 10 - (sum % 10);
                const expected = res === 10 ? 0 : res;

                if (parseInt(digits[9], 10) !== expected) return false;
                if (digits.length === 13) return digits.slice(10, 13) === '001';
                return true;
            }
            if (digits.length === 13) return true;
        }
        return false;
    };

    /**
     * Validación RFC / CURP mexicano.
     */
    const validateMexicanId = (value) => {
        const clean = cleanIdentification(value);
        if (/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/.test(clean)) return true;
        return /^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/.test(clean);
    };

    /**
     * Validador universal por código de país.
     */
    const validateIdentification = (value, countryCode = 'CL') => {
        const code = (countryCode || 'CL').toUpperCase().trim();
        const clean = cleanIdentification(value);
        if (!clean) return false;

        switch (code) {
            case 'CL':
                return validateRut(clean);
            case 'CO':
                return validateColombianId(clean);
            case 'AR':
                return validateArgentineId(clean);
            case 'PE':
                return validatePeruvianId(clean);
            case 'BR':
                return validateBrazilianId(clean);
            case 'EC':
                return validateEcuadorianId(clean);
            case 'MX':
                return validateMexicanId(clean);
            case 'BO':
                return clean.length >= 5 && clean.length <= 12;
            case 'PY':
                return clean.length >= 5 && clean.length <= 11;
            case 'UY':
                return clean.length >= 7 && clean.length <= 12;
            default:
                return clean.length >= 5;
        }
    };

    /**
     * Formateador universal por código de país (sin puntos).
     */
    const formatIdentification = (value, countryCode = 'CL') => {
        const code = (countryCode || 'CL').toUpperCase().trim();
        const clean = cleanIdentification(value);
        if (!clean) return '';

        switch (code) {
            case 'CL':
                return formatRut(clean);
            case 'CO':
                return formatColombianId(clean);
            case 'AR':
                return formatArgentineId(clean);
            case 'BR':
                return formatBrazilianId(clean);
            case 'PY':
            case 'UY':
                if (clean.length === 8) {
                    return `${clean.slice(0, 7)}-${clean.slice(7)}`;
                }
                return clean;
            default:
                return clean;
        }
    };

    /**
     * Obtiene la configuración y metadatos de identificación del país.
     */
    const getCountryConfig = (countryCode = 'CL') => {
        const code = (countryCode || 'CL').toUpperCase().trim();
        return COUNTRY_CONFIGS[code] || COUNTRY_CONFIGS.CL;
    };

    return {
        cleanIdentification,
        validateRut,
        formatRut,
        validateColombianId,
        formatColombianId,
        validateArgentineId,
        formatArgentineId,
        validatePeruvianId,
        validateBrazilianId,
        formatBrazilianId,
        validateEcuadorianId,
        validateMexicanId,
        validateIdentification,
        formatIdentification,
        getCountryConfig,
        COUNTRY_CONFIGS,
    };
}
