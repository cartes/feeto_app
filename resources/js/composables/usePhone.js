/**
 * Composable para manejo, detección inteligente, formateo y normalización
 * de números telefónicos internacionales para Feeto.
 */

export const PHONE_COUNTRY_CONFIGS = {
    CL: {
        code: 'CL',
        name: 'Chile',
        flag: '🇨🇱',
        prefix: '+56',
        dialCode: '56',
        placeholder: '9 1234 5678',
        nationalLength: 9,
    },
    AR: {
        code: 'AR',
        name: 'Argentina',
        flag: '🇦🇷',
        prefix: '+54',
        dialCode: '54',
        placeholder: '9 11 1234 5678',
        nationalLength: 10,
    },
    CO: {
        code: 'CO',
        name: 'Colombia',
        flag: '🇨🇴',
        prefix: '+57',
        dialCode: '57',
        placeholder: '300 123 4567',
        nationalLength: 10,
    },
    PE: {
        code: 'PE',
        name: 'Perú',
        flag: '🇵🇪',
        prefix: '+51',
        dialCode: '51',
        placeholder: '912 345 678',
        nationalLength: 9,
    },
    BR: {
        code: 'BR',
        name: 'Brasil',
        flag: '🇧🇷',
        prefix: '+55',
        dialCode: '55',
        placeholder: '11 91234 5678',
        nationalLength: 11,
    },
    EC: {
        code: 'EC',
        name: 'Ecuador',
        flag: '🇪🇨',
        prefix: '+593',
        dialCode: '593',
        placeholder: '99 123 4567',
        nationalLength: 9,
    },
    MX: {
        code: 'MX',
        name: 'México',
        flag: '🇲🇽',
        prefix: '+52',
        dialCode: '52',
        placeholder: '55 1234 5678',
        nationalLength: 10,
    },
    BO: {
        code: 'BO',
        name: 'Bolivia',
        flag: '🇧🇴',
        prefix: '+591',
        dialCode: '591',
        placeholder: '7123 4567',
        nationalLength: 8,
    },
    PY: {
        code: 'PY',
        name: 'Paraguay',
        flag: '🇵🇾',
        prefix: '+595',
        dialCode: '595',
        placeholder: '981 123 456',
        nationalLength: 9,
    },
    UY: {
        code: 'UY',
        name: 'Uruguay',
        flag: '🇺🇾',
        prefix: '+598',
        dialCode: '598',
        placeholder: '91 234 567',
        nationalLength: 8,
    },
};

export const PHONE_COUNTRY_LIST = Object.values(PHONE_COUNTRY_CONFIGS);

// Ordenados por longitud descendente de dialCode para evitar colisiones (ej: 593 antes de 59)
const SORTED_DIAL_CODES = [...PHONE_COUNTRY_LIST].sort(
    (a, b) => b.dialCode.length - a.dialCode.length
);

/**
 * Detecta el país aproximado según la zona horaria o el idioma del navegador.
 */
export function detectBrowserCountry() {
    try {
        if (typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
            const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
            if (timeZone.includes('Santiago') || timeZone.includes('Chile')) return 'CL';
            if (timeZone.includes('Argentina') || timeZone.includes('Buenos_Aires') || timeZone.includes('Cordoba') || timeZone.includes('Mendoza')) return 'AR';
            if (timeZone.includes('Bogota')) return 'CO';
            if (timeZone.includes('Lima')) return 'PE';
            if (timeZone.includes('Sao_Paulo') || timeZone.includes('Bahia') || timeZone.includes('Manaus')) return 'BR';
            if (timeZone.includes('Guayaquil') || timeZone.includes('Galapagos')) return 'EC';
            if (timeZone.includes('Mexico') || timeZone.includes('Cancun') || timeZone.includes('Monterrey') || timeZone.includes('Tijuana')) return 'MX';
            if (timeZone.includes('La_Paz')) return 'BO';
            if (timeZone.includes('Asuncion')) return 'PY';
            if (timeZone.includes('Montevideo')) return 'UY';
        }

        if (typeof navigator !== 'undefined') {
            const languages = navigator.languages || [navigator.language || ''];
            for (const lang of languages) {
                const upper = (lang || '').toUpperCase();
                for (const code of Object.keys(PHONE_COUNTRY_CONFIGS)) {
                    if (upper.endsWith(`-${code}`) || upper.endsWith(`_${code}`)) {
                        return code;
                    }
                }
            }
        }
    } catch {
        // En caso de error en entornos restringidos
    }

    return 'CL';
}

/**
 * Extrae sólo los dígitos numéricos de un texto.
 */
export function cleanDigits(value) {
    if (!value) return '';
    return String(value).replace(/\D/g, '');
}

/**
 * Formatea visualmente los dígitos locales según el país.
 */
export function formatNationalNumber(digits, countryCode = 'CL') {
    const d = cleanDigits(digits);
    if (!d) return '';

    const code = (countryCode || 'CL').toUpperCase();

    switch (code) {
        case 'CL': {
            // Chile móvil: 9 dígitos (9 XXXX XXXX)
            if (d.length <= 1) return d;
            if (d.length <= 5) return `${d.slice(0, 1)} ${d.slice(1)}`;
            return `${d.slice(0, 1)} ${d.slice(1, 5)} ${d.slice(5, 9)}`;
        }
        case 'CO': {
            // Colombia móvil: 10 dígitos (300 123 4567)
            if (d.length <= 3) return d;
            if (d.length <= 6) return `${d.slice(0, 3)} ${d.slice(3)}`;
            return `${d.slice(0, 3)} ${d.slice(3, 6)} ${d.slice(6, 10)}`;
        }
        case 'AR': {
            // Argentina: 9 11 1234 5678 o 11 1234 5678
            if (d.startsWith('9')) {
                if (d.length <= 3) return d;
                if (d.length <= 7) return `${d.slice(0, 1)} ${d.slice(1, 3)} ${d.slice(3)}`;
                return `${d.slice(0, 1)} ${d.slice(1, 3)} ${d.slice(3, 7)} ${d.slice(7, 11)}`;
            }
            if (d.length <= 2) return d;
            if (d.length <= 6) return `${d.slice(0, 2)} ${d.slice(2)}`;
            return `${d.slice(0, 2)} ${d.slice(2, 6)} ${d.slice(6, 10)}`;
        }
        case 'PE': {
            // Perú móvil: 9 dígitos (912 345 678)
            if (d.length <= 3) return d;
            if (d.length <= 6) return `${d.slice(0, 3)} ${d.slice(3)}`;
            return `${d.slice(0, 3)} ${d.slice(3, 6)} ${d.slice(6, 9)}`;
        }
        case 'BR': {
            // Brasil móvil: 11 dígitos (11 91234 5678)
            if (d.length <= 2) return d;
            if (d.length <= 7) return `${d.slice(0, 2)} ${d.slice(2)}`;
            return `${d.slice(0, 2)} ${d.slice(2, 7)} ${d.slice(7, 11)}`;
        }
        case 'EC': {
            // Ecuador: 9 dígitos (99 123 4567)
            if (d.length <= 2) return d;
            if (d.length <= 5) return `${d.slice(0, 2)} ${d.slice(2)}`;
            return `${d.slice(0, 2)} ${d.slice(2, 5)} ${d.slice(5, 9)}`;
        }
        case 'MX': {
            // México: 10 dígitos (55 1234 5678)
            if (d.length <= 2) return d;
            if (d.length <= 6) return `${d.slice(0, 2)} ${d.slice(2)}`;
            return `${d.slice(0, 2)} ${d.slice(2, 6)} ${d.slice(6, 10)}`;
        }
        case 'BO': {
            // Bolivia: 8 dígitos (7123 4567)
            if (d.length <= 4) return d;
            return `${d.slice(0, 4)} ${d.slice(4, 8)}`;
        }
        case 'PY': {
            // Paraguay: 9 dígitos (981 123 456)
            if (d.length <= 3) return d;
            if (d.length <= 6) return `${d.slice(0, 3)} ${d.slice(3)}`;
            return `${d.slice(0, 3)} ${d.slice(3, 6)} ${d.slice(6, 9)}`;
        }
        case 'UY': {
            // Uruguay: 8 dígitos (91 234 567)
            if (d.length <= 2) return d;
            if (d.length <= 5) return `${d.slice(0, 2)} ${d.slice(2)}`;
            return `${d.slice(0, 2)} ${d.slice(2, 5)} ${d.slice(5, 8)}`;
        }
        default: {
            // Formato estándar por bloques de 3/4
            if (d.length <= 4) return d;
            if (d.length <= 8) return `${d.slice(0, 4)} ${d.slice(4)}`;
            return `${d.slice(0, 4)} ${d.slice(4, 8)} ${d.slice(8)}`;
        }
    }
}

/**
 * Parsea cualquier formato previo (+569..., 569..., 9890...) extrayendo país y número local.
 */
export function parsePhoneNumber(value, defaultCountry = 'CL') {
    const raw = String(value || '').trim();
    const digits = cleanDigits(raw);

    if (!digits) {
        const cfg = PHONE_COUNTRY_CONFIGS[defaultCountry] || PHONE_COUNTRY_CONFIGS.CL;
        return {
            countryCode: cfg.code,
            dialCode: cfg.dialCode,
            prefix: cfg.prefix,
            nationalDigits: '',
            formattedNational: '',
            international: '',
        };
    }

    // 1. Si comienza explícitamente con '+' o con el dialCode internacional
    const startsWithPlus = raw.startsWith('+');

    for (const country of SORTED_DIAL_CODES) {
        if (
            (startsWithPlus && digits.startsWith(country.dialCode)) ||
            (!startsWithPlus && digits.startsWith(country.dialCode) && digits.length > country.nationalLength)
        ) {
            const nationalDigits = digits.slice(country.dialCode.length);
            return {
                countryCode: country.code,
                dialCode: country.dialCode,
                prefix: country.prefix,
                nationalDigits,
                formattedNational: formatNationalNumber(nationalDigits, country.code),
                international: nationalDigits ? `+${country.dialCode}${nationalDigits}` : '',
            };
        }
    }

    // 2. Si no tiene prefijo reconocible, asumir el país por defecto
    const defCfg = PHONE_COUNTRY_CONFIGS[defaultCountry] || PHONE_COUNTRY_CONFIGS.CL;
    return {
        countryCode: defCfg.code,
        dialCode: defCfg.dialCode,
        prefix: defCfg.prefix,
        nationalDigits: digits,
        formattedNational: formatNationalNumber(digits, defCfg.code),
        international: digits ? `+${defCfg.dialCode}${digits}` : '',
    };
}

/**
 * Composable usePhone
 */
export function usePhone() {
    return {
        PHONE_COUNTRY_CONFIGS,
        PHONE_COUNTRY_LIST,
        detectBrowserCountry,
        cleanDigits,
        formatNationalNumber,
        parsePhoneNumber,
    };
}
