/**
 * Composable para detección de origen y validación de placas vehiculares (patentes).
 * Espejo de las reglas de negocio de App\Enums\Country y App\Rules\LicensePlate.
 */

export const COUNTRY_CONFIGS = {
    CL: { code: 'CL', name: 'Chile', flag: '🇨🇱' },
    CO: { code: 'CO', name: 'Colombia', flag: '🇨🇴' },
    AR: { code: 'AR', name: 'Argentina', flag: '🇦🇷' },
    BR: { code: 'BR', name: 'Brasil', flag: '🇧🇷' },
    BO: { code: 'BO', name: 'Bolivia', flag: '🇧🇴' },
    PE: { code: 'PE', name: 'Perú', flag: '🇵🇪' },
    EC: { code: 'EC', name: 'Ecuador', flag: '🇪🇨' },
    MX: { code: 'MX', name: 'México', flag: '🇲🇽' },
    PY: { code: 'PY', name: 'Paraguay', flag: '🇵🇾' },
    UY: { code: 'UY', name: 'Uruguay', flag: '🇺🇾' },
};

export const COUNTRY_PLATE_PATTERNS = {
    CL: [
        '^[BCDFGHJKLPRSTVWXYZ]{4}\\d{2}$',
        '^[A-Z]{2}\\d{4}$',
        '^[BCDFGHJKLPRSTVWXYZ]{3}\\d{2}$',
        '^[A-Z]{2}\\d{3}$',
        '^[A-Z0-9]{6}$',
        '^[A-Z]{3}[0-9]{2}$',
        '^[A-Z]{2}[0-9]{3}$',
    ],
    AR: [
        '^[A-Z]{2}\\d{3}[A-Z]{2}$',
        '^[A-Z]{3}\\d{3}$',
        '^[A-Z]\\d{3}[A-Z]{3}$',
        '^\\d{3}[A-Z]{3}$',
    ],
    BR: [
        '^[A-Z]{3}\\d[A-Z]\\d{2}$',
        '^[A-Z]{3}\\d{4}$',
    ],
    BO: [
        '^\\d{4}[A-Z]{3}$',
        '^\\d{3}[A-Z]{3}$',
    ],
    CO: [
        '^[A-Z]{3}\\d{3}$',
        '^[A-Z]{3}\\d{2}[A-Z]$',
        '^\\d{3}[A-Z]{3}$',
        '^[A-Z]{2}\\d{4}$',
        '^[A-Z]\\d{5}$',
        '^[A-Z]{2}\\d{3}$',
    ],
    EC: [
        '^[A-Z]{3}\\d{4}$',
        '^[A-Z]{3}\\d{3}$',
    ],
    MX: [
        '^[A-Z]{3}\\d{4}$',
        '^[A-Z]{3}\\d{3}[A-Z]$',
    ],
    PY: [
        '^[A-Z]{4}\\d{3}$',
        '^\\d{3}[A-Z]{4}$',
        '^[A-Z]{3}\\d{3}$',
    ],
    PE: [
        '^[A-Z][A-Z0-9]{2}\\d{3}$',
        '^\\d{4}[A-Z]{2}$',
        '^[A-Z]{2}\\d{4}$',
    ],
    UY: [
        '^[A-Z]{3}\\d{4}$',
    ],
};

export function cleanPlate(plate) {
    return (plate || '').replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
}

export function matchesCountryPlate(cleanPlateValue, countryCode) {
    const patterns = COUNTRY_PLATE_PATTERNS[countryCode] || [];
    return patterns.some((pattern) => new RegExp(pattern).test(cleanPlateValue));
}

export function detectPlateCountries(cleanPlateValue) {
    if (!cleanPlateValue) return [];
    return Object.keys(COUNTRY_PLATE_PATTERNS)
        .filter((code) => matchesCountryPlate(cleanPlateValue, code))
        .map((code) => COUNTRY_CONFIGS[code]);
}

export function analyzePlate(plate, tenantCountryCode = 'CL') {
    const clean = cleanPlate(plate);
    const localCountry = COUNTRY_CONFIGS[tenantCountryCode] || COUNTRY_CONFIGS.CL;

    if (!clean) {
        return {
            clean: '',
            isValid: false,
            isLocal: false,
            isForeign: false,
            detectedCountries: [],
            message: null,
            error: null,
        };
    }

    // 1. ¿Calza con el país del taller?
    const isLocal = matchesCountryPlate(clean, tenantCountryCode);
    if (isLocal) {
        return {
            clean,
            isValid: true,
            isLocal: true,
            isForeign: false,
            detectedCountries: [localCountry],
            message: null,
            error: null,
        };
    }

    // 2. ¿Calza con algún otro país reconocido (patente extranjera)?
    const allMatches = detectPlateCountries(clean);
    const foreignMatches = allMatches.filter((c) => c.code !== tenantCountryCode);

    if (foreignMatches.length > 0) {
        let countryText = '';
        if (foreignMatches.length === 1) {
            countryText = `${foreignMatches[0].name} ${foreignMatches[0].flag}`;
        } else if (foreignMatches.length === 2) {
            countryText = `${foreignMatches[0].name} ${foreignMatches[0].flag} o ${foreignMatches[1].name} ${foreignMatches[1].flag}`;
        } else {
            const last = foreignMatches[foreignMatches.length - 1];
            const rest = foreignMatches.slice(0, -1).map((c) => `${c.name} ${c.flag}`).join(', ');
            countryText = `${rest} o ${last.name} ${last.flag}`;
        }

        return {
            clean,
            isValid: true,
            isLocal: false,
            isForeign: true,
            detectedCountries: foreignMatches,
            message: foreignMatches.length === 1
                ? `Esta patente no corresponde a ${localCountry.name} ${localCountry.flag}. Corresponde al formato de ${countryText}.`
                : `Esta patente no corresponde a ${localCountry.name} ${localCountry.flag}. Podría corresponder a ${countryText}.`,
            shortNotice: foreignMatches.length === 1
                ? `Vehículo de ${foreignMatches[0].name}`
                : `Vehículo extranjero (${foreignMatches.map((c) => c.name).join(' / ')})`,
            error: null,
        };
    }

    // 3. No calza ni con el país del taller ni con países extranjeros reconocidos
    return {
        clean,
        isValid: false,
        isLocal: false,
        isForeign: false,
        detectedCountries: [],
        message: null,
        error: `La patente no corresponde al formato de ${localCountry.name} ${localCountry.flag} ni a ningún país extranjero reconocido (ej. Argentina 🇦🇷, Brasil 🇧🇷, Perú 🇵🇪).`,
    };
}

export function useLicensePlate() {
    return {
        cleanPlate,
        matchesCountryPlate,
        detectPlateCountries,
        analyzePlate,
        COUNTRY_CONFIGS,
        COUNTRY_PLATE_PATTERNS,
    };
}
