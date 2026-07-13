<?php
/**
 * Rebecca panel adapter for Susanoo / Faoxima.
 *
 * Rebecca (https://github.com/rebeccapanel/Rebecca) is a Marzban fork with a
 * Marzban-compatible REST API for classic user management:
 *   POST   /api/admin/token
 *   POST   /api/user
 *   GET    /api/user/{username}
 *   PUT    /api/user/{username}
 *   DELETE /api/user/{username}
 *   POST   /api/user/{username}/reset
 *   POST   /api/user/{username}/revoke_sub
 *   GET    /api/system
 *   GET    /api/inbounds
 *
 * Store panel rows with type = "rebecca". Credentials reuse url_panel /
 * username_panel / password_panel. Configure proxies + inbounds like Marzban
 * (legacy / no-service mode). Keep version_panel = 0 (not Pasargard).
 *
 * Future: optional service_id mode can be added without changing the type string.
 */
require_once __DIR__ . '/Marzban.php';

if (!function_exists('rx_is_rebecca_family')) {
    function rx_is_rebecca_family(?string $type): bool
    {
        return $type === 'rebecca';
    }
}

if (!function_exists('rx_is_marzban_api_compatible')) {
    /** Marzban + Rebecca share the classic /api/user surface. */
    function rx_is_marzban_api_compatible(?string $type): bool
    {
        return $type === 'marzban' || $type === 'rebecca';
    }
}
