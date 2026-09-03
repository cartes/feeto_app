<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Fuente única de verdad para las páginas públicas de marketing cuyo SEO
 * es editable desde el panel de super-admin (Landing SEO).
 */
final class MarketingSeoPages
{
    public const SITE_NAME = 'TallerFlow';

    public const LOCALE = 'es_CL';

    public const TWITTER_SITE = '@tallerflow';

    public const DEFAULT_ROBOTS = 'index, follow, max-image-preview:large, max-snippet:-1';

    public const NOINDEX_ROBOTS = 'noindex, nofollow';

    /** Imagen social por defecto (public/images). Dimensiones reales del archivo. */
    public const DEFAULT_SOCIAL_IMAGE = '/images/tallerflow-social-share.png';

    public const DEFAULT_SOCIAL_IMAGE_WIDTH = 942;

    public const DEFAULT_SOCIAL_IMAGE_HEIGHT = 494;

    /** Logo usado en los schemas Organization / publisher. */
    public const LOGO_IMAGE = '/images/taller-flow-isotipo.png';

    /**
     * @var array<string, array{label: string, route: string, og_type: string, default_title: string, default_description: string}>
     */
    private const PAGES = [
        'home' => [
            'label' => 'Página de Inicio',
            'route' => 'home',
            'og_type' => 'website',
            'default_title' => 'TallerFlow · Software para Talleres Mecánicos en Chile',
            'default_description' => 'TallerFlow digitaliza la gestión de tu taller mecánico en Chile. Kanban en vivo, recepción con IA, inventario inteligente y WhatsApp automatizado. Prueba gratis 14 días.',
        ],
        'services' => [
            'label' => 'Características y Módulos',
            'route' => 'servicios',
            'og_type' => 'website',
            'default_title' => 'Características y Módulos · TallerFlow — Sistema para Talleres',
            'default_description' => 'Conoce todas las características de TallerFlow. Recepción inteligente de patentes con IA, tablero Kanban, agenda integrada, inventario, cotizaciones por WhatsApp y reportes.',
        ],
        'pricing' => [
            'label' => 'Página de Precios',
            'route' => 'pricing',
            'og_type' => 'website',
            'default_title' => 'Planes y Precios · TallerFlow — Software para Talleres',
            'default_description' => 'Elige el plan TallerFlow ideal para tu taller mecánico. Desde $19.990/mes con 14 días de prueba gratis. Sin tarjeta de crédito requerida.',
        ],
        'trial' => [
            'label' => 'Solicitar Prueba Gratuita',
            'route' => 'trial.create',
            'og_type' => 'website',
            'default_title' => 'Prueba Gratis 14 días · TallerFlow — Software para Talleres',
            'default_description' => 'Solicita tu acceso gratuito de 14 días a TallerFlow. Sin tarjeta de crédito. Sin compromisos. Activa tu taller digital hoy.',
        ],
        'blog' => [
            'label' => 'Blog',
            'route' => 'blog.index',
            'og_type' => 'website',
            'default_title' => 'Blog · TallerFlow — Recursos para Talleres Mecánicos',
            'default_description' => 'Aprende prácticas para optimizar tiempos, fidelizar clientes y aumentar la rentabilidad de tu taller mecánico en Chile.',
        ],
        'orden_trabajo' => [
            'label' => 'Guía: Orden de Trabajo',
            'route' => 'orden-de-trabajo-taller-mecanico',
            'og_type' => 'article',
            'default_title' => 'Orden de Trabajo para Taller Mecánico: Guía Completa | TallerFlow',
            'default_description' => 'Aprende qué es una orden de trabajo para taller mecánico, qué debe incluir y cómo digitalizarla. Incluye template gratuito.',
        ],
        'talleres' => [
            'label' => 'Directorio de Talleres',
            'route' => 'talleres.index',
            'og_type' => 'website',
            'default_title' => 'Directorio de Talleres Mecánicos en Chile · TallerFlow',
            'default_description' => 'Encuentra talleres mecánicos confiables en tu comuna. Agenda tu hora directamente con cada taller asociado a TallerFlow.',
        ],
    ];

    /**
     * @return array<string, array{label: string, route: string, og_type: string, default_title: string, default_description: string}>
     */
    public static function all(): array
    {
        return self::PAGES;
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::PAGES);
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::PAGES);
    }

    /**
     * @return array{label: string, route: string, og_type: string, default_title: string, default_description: string}
     */
    public static function get(string $key): array
    {
        if (! self::has($key)) {
            throw new \InvalidArgumentException("Página SEO desconocida: {$key}");
        }

        return self::PAGES[$key];
    }
}
