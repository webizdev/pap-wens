<?php
/**
 * PAPWENS API Router
 * File ini menjadi pintu masuk semua request /api/*
 */
require_once __DIR__ . '/config.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Routing
if (preg_match('#^/api/menu#', $uri)) {
    $_GET['id'] = preg_match('#/api/menu/(\d+)#', $uri, $m) ? (int)$m[1] : 0;
    require __DIR__ . '/menu.php';

} elseif (preg_match('#^/api/gallery#', $uri)) {
    $_GET['id'] = preg_match('#/api/gallery/(\d+)#', $uri, $m) ? (int)$m[1] : 0;
    require __DIR__ . '/gallery.php';

} elseif (preg_match('#^/api/testimonials#', $uri)) {
    $_GET['id'] = preg_match('#/api/testimonials/(\d+)#', $uri, $m) ? (int)$m[1] : 0;
    require __DIR__ . '/testimonials.php';

} elseif (preg_match('#^/api/orders/track/(\d+)#', $uri, $m)) {
    $_GET['track_id'] = (int)$m[1];
    require __DIR__ . '/orders.php';

} elseif (preg_match('#^/api/orders#', $uri)) {
    $_GET['id'] = preg_match('#/api/orders/(\d+)#', $uri, $m) ? (int)$m[1] : 0;
    require __DIR__ . '/orders.php';

} elseif (preg_match('#^/api/newsletter#', $uri)) {
    $_GET['id'] = preg_match('#/api/newsletter/(\d+)#', $uri, $m) ? (int)$m[1] : 0;
    require __DIR__ . '/newsletter.php';

} elseif (preg_match('#^/api/settings#', $uri)) {
    require __DIR__ . '/settings.php';

} elseif (preg_match('#^/api/upload#', $uri)) {
    require __DIR__ . '/upload.php';

} else {
    sendJSON(['error' => 'API endpoint not found', 'uri' => $uri], 404);
}
