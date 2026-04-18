<?php
/**
 * PAPWENS Compatibility Bridge (PHP 7.4 to 8.0)
 */
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        return (string) $needle !== '' && mb_strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        return $needle !== '' && mb_substr($haystack, -mb_strlen($needle)) === (string) $needle;
    }
}
// =============================================
// KONFIGURASI DATABASE (DYNAMIS)
// =============================================
$is_docker = getenv('IS_DOCKER') === 'true';

define('DB_HOST', getenv('DB_HOST') ?: ($is_docker ? 'db' : 'localhost'));
define('DB_USER', getenv('DB_USER') ?: 'papt1362_papwensmyid');
define('DB_PASS', getenv('DB_PASSWORD') ?: 'VUqjwFArtB285XhNxRZc');
define('DB_NAME', getenv('DB_NAME') ?: 'papt1362_papwens.my.id');

// =============================================
// KONFIGURASI ADMIN
// =============================================
define('ADMIN_PASSWORD', 'Papwens!!31@@');       // Ganti dengan password admin Anda

// =============================================
// Koneksi Database
// =============================================
function getDB(): mysqli
{
    // Suppress warnings for a cleaner JSON response on failure
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'error' => 'Database connection failed',
            'detail' => $conn->connect_error,
            'host' => DB_HOST,
            'user' => DB_USER
        ]);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// =============================================
// Helper: Send JSON Response
// =============================================
function sendJSON($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
    header('Pragma: no-cache');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * =============================================
 * Helper: Clear settings cache and purge server cache
 * =============================================
 */
function clearCache(): void
{
    $cacheFile = __DIR__ . '/../uploads/settings_cache.json';
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }

    // Purge signals for common shared hosting cache layers (LiteSpeed, Nginx, etc)
    header('X-LiteSpeed-Purge: *');
    header('X-Accel-Expires: 0');
    header('Clear-Site-Data: "cache"');
}

// =============================================
// Helper: Get Request Body as Array
// =============================================
function getBody(): array
{
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// =============================================
// Handle OPTIONS (CORS Preflight)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(204);
    exit;
}
