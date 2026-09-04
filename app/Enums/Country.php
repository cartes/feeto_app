<?php

declare(strict_types=1);

namespace App\Enums;

use App\Rules\ChileanPlate;
use App\Rules\ColombianPlate;

/**
 * Países soportados por la plataforma.
 *
 * Los países "operacionales" (donde pueden registrarse talleres) son un
 * subconjunto; el resto existe para reconocer patentes extranjeras que
 * lleguen a un taller (ej: un vehículo argentino en un taller chileno).
 */
enum Country: string
{
    case Chile = 'CL';
    case Colombia = 'CO';
    case Argentina = 'AR';
    case Bolivia = 'BO';
    case Brazil = 'BR';
    case Ecuador = 'EC';
    case Mexico = 'MX';
    case Paraguay = 'PY';
    case Peru = 'PE';
    case Uruguay = 'UY';

    /**
     * Países donde la plataforma opera comercialmente (registro de talleres).
     *
     * @return list<self>
     */
    public static function operational(): array
    {
        return [self::Chile, self::Colombia];
    }

    /**
     * Nombre legible del país.
     */
    public function label(): string
    {
        return match ($this) {
            self::Chile => 'Chile',
            self::Colombia => 'Colombia',
            self::Argentina => 'Argentina',
            self::Bolivia => 'Bolivia',
            self::Brazil => 'Brasil',
            self::Ecuador => 'Ecuador',
            self::Mexico => 'México',
            self::Paraguay => 'Paraguay',
            self::Peru => 'Perú',
            self::Uruguay => 'Uruguay',
        };
    }

    /**
     * Nombre comercial / legal del impuesto al valor agregado en el país.
     */
    public function taxName(): string
    {
        return match ($this) {
            self::Peru => 'IGV',
            self::Brazil => 'Impostos',
            default => 'IVA',
        };
    }

    /**
     * Tasa impositiva por defecto (porcentaje).
     */
    public function defaultTaxRate(): float
    {
        return match ($this) {
            self::Chile => 19.0,
            self::Colombia => 19.0,
            self::Argentina => 21.0,
            self::Bolivia => 13.0,
            self::Brazil => 20.0,
            self::Ecuador => 15.0,
            self::Mexico => 16.0,
            self::Paraguay => 10.0,
            self::Peru => 18.0,
            self::Uruguay => 22.0,
        };
    }

    /**
     * Emoji de bandera para UI.
     */
    public function flag(): string
    {
        return match ($this) {
            self::Chile => '🇨🇱',
            self::Colombia => '🇨🇴',
            self::Argentina => '🇦🇷',
            self::Bolivia => '🇧🇴',
            self::Brazil => '🇧🇷',
            self::Ecuador => '🇪🇨',
            self::Mexico => '🇲🇽',
            self::Paraguay => '🇵🇾',
            self::Peru => '🇵🇪',
            self::Uruguay => '🇺🇾',
        };
    }

    /**
     * Prefijo telefónico internacional.
     */
    public function phonePrefix(): string
    {
        return match ($this) {
            self::Chile => '+56',
            self::Colombia => '+57',
            self::Argentina => '+54',
            self::Bolivia => '+591',
            self::Brazil => '+55',
            self::Ecuador => '+593',
            self::Mexico => '+52',
            self::Paraguay => '+595',
            self::Peru => '+51',
            self::Uruguay => '+598',
        };
    }

    /**
     * Placeholder de teléfono para formularios.
     */
    public function phonePlaceholder(): string
    {
        return match ($this) {
            self::Chile => '+56 9 1234 5678',
            self::Colombia => '+57 300 123 4567',
            self::Argentina => '+54 9 11 1234 5678',
            self::Bolivia => '+591 7123 4567',
            self::Brazil => '+55 11 91234 5678',
            self::Ecuador => '+593 99 123 4567',
            self::Mexico => '+52 55 1234 5678',
            self::Paraguay => '+595 981 123 456',
            self::Peru => '+51 912 345 678',
            self::Uruguay => '+598 91 234 567',
        };
    }

    /**
     * Nombre comercial / legal del documento de identificación en el país.
     */
    public function identificationName(): string
    {
        return match ($this) {
            self::Chile => 'RUT',
            self::Colombia => 'Cédula / NIT',
            self::Argentina => 'DNI / CUIT',
            self::Bolivia => 'CI / NIT',
            self::Brazil => 'CPF / CNPJ',
            self::Ecuador => 'Cédula / RUC',
            self::Mexico => 'RFC / CURP',
            self::Paraguay => 'CI / RUC',
            self::Peru => 'DNI / RUC',
            self::Uruguay => 'CI / RUT',
        };
    }

    /**
     * Placeholder de identificación sin puntos para formularios.
     */
    public function identificationPlaceholder(): string
    {
        return match ($this) {
            self::Chile => '12345678-K',
            self::Colombia => '900123456-1',
            self::Argentina => '20-12345678-9',
            self::Bolivia => '1234567',
            self::Brazil => '123456789-00',
            self::Ecuador => '1712345678',
            self::Mexico => 'XAXX010101000',
            self::Paraguay => '1234567-8',
            self::Peru => '12345678',
            self::Uruguay => '1234567-8',
        };
    }

    /**
     * Limpia un número de identificación (sin puntos, espacios ni diagonales).
     */
    public static function cleanIdentification(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(strtoupper(str_replace(['.', ' ', '/'], '', $value)));
    }

    /**
     * Formatea un número de identificación sin puntos (ej: 12345678-9 en Chile).
     */
    public function formatIdentification(?string $value): string
    {
        $clean = self::cleanIdentification($value);
        if ($clean === '') {
            return '';
        }

        return match ($this) {
            self::Chile => $this->formatChileanRut($clean),
            self::Colombia => $this->formatColombianIdentification($clean),
            self::Argentina => $this->formatArgentineIdentification($clean),
            self::Brazil => $this->formatBrazilianIdentification($clean),
            self::Paraguay => $this->formatParaguayanIdentification($clean),
            self::Uruguay => $this->formatUruguayanIdentification($clean),
            default => $clean,
        };
    }

    /**
     * Valida un número de identificación aplicando las reglas y dígito verificador del país.
     */
    public function validateIdentification(?string $value): bool
    {
        $clean = self::cleanIdentification($value);
        if ($clean === '') {
            return false;
        }

        return match ($this) {
            self::Chile => $this->validateChileanRut($clean),
            self::Colombia => $this->validateColombianIdentification($clean),
            self::Argentina => $this->validateArgentineIdentification($clean),
            self::Bolivia => $this->validateBolivianIdentification($clean),
            self::Brazil => $this->validateBrazilianIdentification($clean),
            self::Ecuador => $this->validateEcuadorianIdentification($clean),
            self::Mexico => $this->validateMexicanIdentification($clean),
            self::Paraguay => $this->validateParaguayanIdentification($clean),
            self::Peru => $this->validatePeruvianIdentification($clean),
            self::Uruguay => $this->validateUruguayanIdentification($clean),
        };
    }

    private function formatChileanRut(string $clean): string
    {
        $stripped = preg_replace('/[^0-9K]/', '', $clean) ?? '';
        if (strlen($stripped) < 4) {
            return $stripped;
        }

        $body = substr($stripped, 0, -1);
        $dv = substr($stripped, -1);

        return $body.'-'.$dv;
    }

    private function formatColombianIdentification(string $clean): string
    {
        $hasDash = str_contains($clean, '-');
        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';

        if ($hasDash && strlen($digits) >= 8) {
            $parts = explode('-', $clean, 2);
            $body = preg_replace('/[^0-9]/', '', $parts[0]) ?? '';
            $dv = preg_replace('/[^0-9]/', '', $parts[1]) ?? '';

            return $body.($dv !== '' ? '-'.$dv : '');
        }

        return $digits;
    }

    private function formatArgentineIdentification(string $clean): string
    {
        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';
        if (strlen($digits) >= 11) {
            return substr($digits, 0, 2).'-'.substr($digits, 2, 8).'-'.substr($digits, 10, 1);
        }
        if (strlen($digits) >= 3) {
            return substr($digits, 0, 2).'-'.substr($digits, 2);
        }

        return $digits;
    }

    private function formatBrazilianIdentification(string $clean): string
    {
        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';
        if (strlen($digits) === 11) {
            return substr($digits, 0, 9).'-'.substr($digits, 9, 2);
        }
        if (strlen($digits) === 14) {
            return substr($digits, 0, 12).'-'.substr($digits, 12, 2);
        }

        return $digits;
    }

    private function formatParaguayanIdentification(string $clean): string
    {
        $hasDash = str_contains($clean, '-');
        if ($hasDash) {
            $parts = explode('-', $clean, 2);
            $body = preg_replace('/[^0-9]/', '', $parts[0]) ?? '';
            $dv = preg_replace('/[^0-9]/', '', $parts[1]) ?? '';

            return $body.($dv !== '' ? '-'.$dv : '');
        }

        return preg_replace('/[^0-9]/', '', $clean) ?? '';
    }

    private function formatUruguayanIdentification(string $clean): string
    {
        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';
        if (strlen($digits) === 8) {
            return substr($digits, 0, 7).'-'.substr($digits, 7, 1);
        }

        return $digits;
    }

    private function validateChileanRut(string $clean): bool
    {
        $stripped = preg_replace('/[^0-9K]/', '', $clean) ?? '';
        if (strlen($stripped) < 7 || strlen($stripped) > 10) {
            return false;
        }

        $body = substr($stripped, 0, -1);
        $dv = substr($stripped, -1);

        if (! ctype_digit($body)) {
            return false;
        }

        $sum = 0;
        $factor = 2;
        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += ((int) $body[$i]) * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $res = 11 - ($sum % 11);
        $expected = match ($res) {
            11 => '0',
            10 => 'K',
            default => (string) $res,
        };

        return $expected === $dv;
    }

    private function validateColombianIdentification(string $clean): bool
    {
        // Si contiene guión, validamos como NIT con DV Módulo 11 DIAN
        if (str_contains($clean, '-')) {
            $parts = explode('-', $clean, 2);
            $body = preg_replace('/[^0-9]/', '', $parts[0]) ?? '';
            $dv = preg_replace('/[^0-9]/', '', $parts[1]) ?? '';

            if (strlen($body) < 5 || strlen($body) > 15 || strlen($dv) !== 1) {
                return false;
            }

            $weights = [41, 37, 29, 23, 19, 17, 13, 7, 3];
            $bodyLen = strlen($body);
            $sum = 0;

            for ($i = 0; $i < $bodyLen; $i++) {
                $posFromRight = $bodyLen - 1 - $i;
                $weight = $posFromRight < count($weights) ? $weights[count($weights) - 1 - $posFromRight] : 0;
                $sum += ((int) $body[$i]) * $weight;
            }

            $remainder = $sum % 11;
            $expected = ($remainder === 0 || $remainder === 1) ? $remainder : 11 - $remainder;

            return (int) $dv === $expected;
        }

        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';

        // Cédula de Ciudadanía: 5 a 11 dígitos numéricos
        return strlen($digits) >= 5 && strlen($digits) <= 11 && ctype_digit($digits);
    }

    private function validateArgentineIdentification(string $clean): bool
    {
        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';

        // CUIT / CUIL: 11 dígitos con módulo 11
        if (strlen($digits) === 11) {
            $multipliers = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $sum += ((int) $digits[$i]) * $multipliers[$i];
            }
            $res = 11 - ($sum % 11);
            $expected = match ($res) {
                11 => 0,
                10 => 9,
                default => $res,
            };

            return (int) $digits[10] === $expected;
        }

        // DNI: 7 u 8 dígitos numéricos
        return (strlen($digits) === 7 || strlen($digits) === 8) && ctype_digit($digits);
    }

    private function validateBolivianIdentification(string $clean): bool
    {
        // CI boliviana: 5 a 10 caracteres alfanuméricos (puede incluir complemento ej 1234567-1A)
        $stripped = preg_replace('/[^0-9A-Z-]/', '', $clean) ?? '';

        return strlen($stripped) >= 5 && strlen($stripped) <= 12;
    }

    private function validateBrazilianIdentification(string $clean): bool
    {
        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';

        if (strlen($digits) === 11) {
            // CPF
            if (preg_match('/^(\d)\1{10}$/', $digits) === 1) {
                return false;
            }

            for ($t = 9; $t < 11; $t++) {
                $d = 0;
                for ($c = 0; $c < $t; $c++) {
                    $d += ((int) $digits[$c]) * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ((int) $digits[$t] !== $d) {
                    return false;
                }
            }

            return true;
        }

        if (strlen($digits) === 14) {
            // CNPJ
            if (preg_match('/^(\d)\1{13}$/', $digits) === 1) {
                return false;
            }

            $calc = function (int $len) use ($digits): int {
                $multipliers = $len === 12
                    ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
                    : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
                $sum = 0;
                for ($i = 0; $i < $len; $i++) {
                    $sum += ((int) $digits[$i]) * $multipliers[$i];
                }
                $remainder = $sum % 11;

                return $remainder < 2 ? 0 : 11 - $remainder;
            };

            return (int) $digits[12] === $calc(12) && (int) $digits[13] === $calc(13);
        }

        return false;
    }

    private function validateEcuadorianIdentification(string $clean): bool
    {
        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';

        if (strlen($digits) === 10 || strlen($digits) === 13) {
            $province = (int) substr($digits, 0, 2);
            if (($province < 1 || $province > 24) && $province !== 30) {
                return false;
            }

            $third = (int) $digits[2];
            if ($third < 6) {
                // Cédula persona natural
                $coefficients = [2, 1, 2, 1, 2, 1, 2, 1, 2];
                $sum = 0;
                for ($i = 0; $i < 9; $i++) {
                    $val = ((int) $digits[$i]) * $coefficients[$i];
                    $sum += $val >= 10 ? $val - 9 : $val;
                }
                $res = 10 - ($sum % 10);
                $expected = $res === 10 ? 0 : $res;

                if ((int) $digits[9] !== $expected) {
                    return false;
                }

                if (strlen($digits) === 13) {
                    return substr($digits, 10, 3) === '001';
                }

                return true;
            }

            if (strlen($digits) === 13) {
                return true;
            }
        }

        return false;
    }

    private function validateMexicanIdentification(string $clean): bool
    {
        $cleanUpper = strtoupper($clean);

        // RFC: 12 caracteres (persona moral) o 13 caracteres (persona física)
        if (preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/', $cleanUpper) === 1) {
            return true;
        }

        // CURP: 18 caracteres
        return preg_match('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/', $cleanUpper) === 1;
    }

    private function validateParaguayanIdentification(string $clean): bool
    {
        if (str_contains($clean, '-')) {
            $parts = explode('-', $clean, 2);
            $body = preg_replace('/[^0-9]/', '', $parts[0]) ?? '';
            $dv = preg_replace('/[^0-9]/', '', $parts[1]) ?? '';

            if (strlen($body) < 4 || strlen($dv) !== 1) {
                return false;
            }

            $sum = 0;
            $factor = 2;
            for ($i = strlen($body) - 1; $i >= 0; $i--) {
                $sum += ((int) $body[$i]) * $factor;
                $factor = $factor === 11 ? 2 : $factor + 1;
            }
            $remainder = $sum % 11;
            $expected = $remainder > 1 ? 11 - $remainder : 0;

            return (int) $dv === $expected;
        }

        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';

        return strlen($digits) >= 5 && strlen($digits) <= 9 && ctype_digit($digits);
    }

    private function validatePeruvianIdentification(string $clean): bool
    {
        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';

        // DNI: 8 dígitos numéricos
        if (strlen($digits) === 8 && ctype_digit($digits)) {
            return true;
        }

        // RUC: 11 dígitos con módulo 11
        if (strlen($digits) === 11 && ctype_digit($digits)) {
            $prefix = substr($digits, 0, 2);
            if (! in_array($prefix, ['10', '15', '16', '17', '20'], true)) {
                return false;
            }

            $multipliers = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $sum += ((int) $digits[$i]) * $multipliers[$i];
            }
            $res = 11 - ($sum % 11);
            $expected = match ($res) {
                10 => 0,
                11 => 1,
                default => $res,
            };

            return (int) $digits[10] === $expected;
        }

        return false;
    }

    private function validateUruguayanIdentification(string $clean): bool
    {
        $digits = preg_replace('/[^0-9]/', '', $clean) ?? '';

        // CI Uruguaya: 7 u 8 dígitos con dígito verificador
        if (strlen($digits) === 7 || strlen($digits) === 8) {
            $padded = str_pad($digits, 8, '0', STR_PAD_LEFT);
            $multipliers = [2, 9, 8, 7, 6, 3, 4];
            $sum = 0;
            for ($i = 0; $i < 7; $i++) {
                $sum += ((int) $padded[$i]) * $multipliers[$i];
            }
            $remainder = $sum % 10;
            $expected = $remainder === 0 ? 0 : 10 - $remainder;

            return (int) $padded[7] === $expected;
        }

        // RUT Uruguayo: 12 dígitos
        return strlen($digits) === 12 && ctype_digit($digits);
    }

    /**
     * Código ISO 3166-1 alpha-2 (alias del value).
     */
    public function isoCode(): string
    {
        return $this->value;
    }

    /**
     * Formatos de patente característicos del país (sin anclas ni separadores,
     * sobre patentes normalizadas: mayúsculas y solo alfanuméricos).
     *
     * Usados para *detectar* el país de origen de una patente, por lo que son
     * más estrictos que los patrones de validación local (que aceptan además
     * placas especiales o provisorias).
     *
     * @return list<string>
     */
    public function platePatterns(): array
    {
        return match ($this) {
            // LLLL·NN (nuevo, sin vocales ni MNÑQ), LL·NNNN (antiguo),
            // motos LLL·NN (nueva) / LL·NNN (antigua)
            self::Chile => ['[BCDFGHJKLPRSTVWXYZ]{4}\d{2}', '[A-Z]{2}\d{4}', '[BCDFGHJKLPRSTVWXYZ]{3}\d{2}', '[A-Z]{2}\d{3}'],
            // ABC123, ABC12D, 123ABC, CD1234, R12345, AB123
            self::Colombia => ['[A-Z]{3}\d{3}', '[A-Z]{3}\d{2}[A-Z]', '\d{3}[A-Z]{3}', '[A-Z]{2}\d{4}', '[A-Z]\d{5}', '[A-Z]{2}\d{3}'],
            // Mercosur AB123CD, antiguo ABC123, motos A123BCD / 123ABC
            self::Argentina => ['[A-Z]{2}\d{3}[A-Z]{2}', '[A-Z]{3}\d{3}', '[A-Z]\d{3}[A-Z]{3}', '\d{3}[A-Z]{3}'],
            // 1234ABC, 123ABC
            self::Bolivia => ['\d{4}[A-Z]{3}', '\d{3}[A-Z]{3}'],
            // Mercosur ABC1D23, antiguo ABC1234
            self::Brazil => ['[A-Z]{3}\d[A-Z]\d{2}', '[A-Z]{3}\d{4}'],
            // ABC1234, ABC123
            self::Ecuador => ['[A-Z]{3}\d{4}', '[A-Z]{3}\d{3}'],
            // ABC1234, ABC123D
            self::Mexico => ['[A-Z]{3}\d{4}', '[A-Z]{3}\d{3}[A-Z]'],
            // Mercosur AAAA123, motos 123AAAA, antiguo ABC123
            self::Paraguay => ['[A-Z]{4}\d{3}', '\d{3}[A-Z]{4}', '[A-Z]{3}\d{3}'],
            // ABC123 / A1B234 (2° y 3° pueden ser dígito), motos 1234AB / AB1234
            self::Peru => ['[A-Z][A-Z0-9]{2}\d{3}', '\d{4}[A-Z]{2}', '[A-Z]{2}\d{4}'],
            // ABC1234
            self::Uruguay => ['[A-Z]{3}\d{4}'],
        };
    }

    /**
     * Patrón de validación para patentes locales del país. Más permisivo que
     * los patrones de detección (acepta placas especiales/provisorias).
     */
    public function plateValidationPattern(): string
    {
        return match ($this) {
            self::Chile => ChileanPlate::PATTERN,
            self::Colombia => ColombianPlate::PATTERN,
            default => '/^('.implode('|', $this->platePatterns()).')$/',
        };
    }

    /**
     * ¿La patente calza con el formato local del país?
     */
    public function matchesPlate(string $plate): bool
    {
        return preg_match($this->plateValidationPattern(), $plate) === 1;
    }

    /**
     * Detecta a qué países podría pertenecer una patente normalizada
     * (mayúsculas, sin guiones ni espacios).
     *
     * @return list<self> Países cuyos formatos calzan con la patente.
     */
    public static function detectFromPlate(string $plate): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $country): bool => preg_match(
                '/^('.implode('|', $country->platePatterns()).')$/',
                $plate
            ) === 1,
        ));
    }
}
