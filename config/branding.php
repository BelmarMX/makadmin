<?php

$brandName = env('APP_BRAND_NAME', 'Makadmin');
$apexDomain = env('APP_APEX_DOMAIN', 'makadmin.test');
$superadminSubdomain = env('SUPERADMIN_SUBDOMAIN', 'admin');
$publicSubdomain = env('PUBLIC_SUBDOMAIN', 'www');
$portalSubdomain = env('PORTAL_SUBDOMAIN', 'portal');
$scheme = env('APP_SCHEME', env('APP_ENV') === 'production' ? 'https' : 'http');

/**
 * Configuración centralizada de marca, dominio y subdominios reservados
 *
 * Este archivo centraliza todos los valores que pueden cambiar:
 * - Nombre de la marca/plataforma
 * - Dominio apex (root domain)
 * - Subdominio reservado para superadmin
 *
 * LUGAR: config/branding.php
 *
 * Todas las referencias a hardcoded "vetfollow" o "admin" en el código deben
 * consultar estos valores en lugar de usar strings fijos.
 */

return [
    /**
     * Nombre de la marca/plataforma
     * Ejemplos: 'VetFollow', 'MiVet', 'ClinicManager'
     * Usado en: UI, emails, documentación, logs
     */
    'name' => $brandName,

    /**
     * Dominio apex (sin subdominios)
     * Ejemplos: 'vetfollow.com', 'mivet.com.mx', 'localhost'
     * Usado para: resolución de subdominios, cookies, CORS
     */
    'apex_domain' => $apexDomain,

    /**
     * Subdominio reservado para el superadmin
     * Ejemplos: 'admin', 'control', 'super', 'panel'
     * IMPORTANTE: Cambiar esto a algo único/específico de tu negocio
     * para evitar ser target obvio de ataques de fuerza bruta
     *
     * NO debe ser 'www', 'mail', 'api', 'app', 'portal' (subdominios estándar)
     */
    'superadmin_subdomain' => $superadminSubdomain,

    /**
     * Subdominio reservado para landing/home pública (sin autenticación)
     * Ejemplos: 'www', 'home', 'landing'
     */
    'public_subdomain' => $publicSubdomain,

    /**
     * Subdominio reservado para el portal de clientes
     */
    'portal_subdomain' => $portalSubdomain,

    /**
     * Subdominios que NUNCA pueden ser asignados a clínicas
     * Se agregan aquí los específicos de tu setup
     */
    'reserved_subdomains' => [
        $superadminSubdomain,
        $publicSubdomain,
        'mail',
        'ftp',
        'sftp',
        'ssh',
        'api',
        'app',
        'dashboard',
        'portal',  // reservado para cliente, pero no clínicas
        'docs',
        'status',
        'support',
        'help',
        'contact',
        'blog',
        'news',
        'localhost',
    ],

    /**
     * URLs construidas dinámicamente
     * Usar estos helpers en lugar de hardcodear
     */
    'urls' => [
        'superadmin_base' => $superadminSubdomain.'.'.$apexDomain,
        'clinic_base' => '{clinic}.'.$apexDomain,
        'public_base' => $publicSubdomain.'.'.$apexDomain,
        'portal_base' => $portalSubdomain.'.'.$apexDomain,
    ],

    /**
     * Esquema (http o https)
     * En dev: http, en prod: https siempre
     */
    'scheme' => $scheme,
];
