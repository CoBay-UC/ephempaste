<?php

declare(strict_types=1);

use App\Config\AppConfig;
use App\Database\Database;
use App\Http\App;
use App\Repositories\PasteRepository;
use App\Security\Csrf;
use App\Security\PasswordHasher;
use App\Services\PasteService;
use App\Support\View;

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

// Application responses contain temporary/private text and must not be cached by
// browsers or intermediary proxies.
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header(
    "Content-Security-Policy: default-src 'self'; "
    . "style-src 'self' https://cdn.jsdelivr.net; "
    . "script-src 'self'; connect-src 'self'; img-src 'self' data:; base-uri 'none'; "
    . "frame-ancestors 'none'; form-action 'self'"
);

try {
    $config = new AppConfig();
    $baseUrlScheme = strtolower((string) parse_url($config->get('appBaseUrl'), PHP_URL_SCHEME));

    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $baseUrlScheme === 'https',
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);

    $database = new Database($config);
    $repository = new PasteRepository($database->connection());
    $passwordHasher = new PasswordHasher();
    $pasteService = new PasteService($config, $repository, $passwordHasher);
    $csrf = new Csrf();
    $view = new View(dirname(__DIR__) . '/resources/views');

    $app = new App($config, $pasteService, $csrf, $view);
    $app->run();
} catch (Throwable $exception) {
    error_log(sprintf(
        "%s: %s in %s:%d\n%s",
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    ));

    http_response_code(500);

    $debugValue = strtolower(trim((string) getenv('APP_DEBUG')));
    $debug = in_array($debugValue, ['1', 'true', 'yes', 'on'], true);
    $message = $debug
        ? $exception->getMessage()
        : 'The application encountered an unexpected error.';

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Application Error</title></head><body><main>'
        . '<h1>Application Error</h1><p>'
        . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</p></main></body></html>';
}
