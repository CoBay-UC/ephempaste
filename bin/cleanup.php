<?php

declare(strict_types=1);

use App\Config\AppConfig;
use App\Database\Database;
use App\Repositories\PasteRepository;

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

try {
    $config = new AppConfig();
    $database = new Database($config);
    $repository = new PasteRepository($database->connection());
    $deleted = $repository->deleteExpired();

    fwrite(STDOUT, sprintf("[%s] Deleted %d expired paste(s).\n", date(DATE_ATOM), $deleted));
} catch (Throwable $exception) {
    fwrite(STDERR, sprintf("[%s] Cleanup failed: %s\n", date(DATE_ATOM), $exception->getMessage()));
    exit(1);
}
