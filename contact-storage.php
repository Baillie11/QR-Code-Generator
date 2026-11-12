<?php
function openContactDatabase(): array
{
    // Store personal details outside the public website directory.
    $configFile = __DIR__ . '/storage-config.php';
    $configuredDirectory = is_file($configFile) ? require $configFile : null;
    $directory = getenv('QR_CONTACTS_DIR') ?: ($configuredDirectory ?: dirname(__DIR__) . '/qr-code-generator-private');
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create contact storage.');
    }
    $directory = realpath($directory);
    $publicRoot = realpath(($_SERVER['DOCUMENT_ROOT'] ?? '') ?: __DIR__) ?: __DIR__;
    $normalizePath = static fn(string $path): string => strtolower(str_replace('\\', '/', $path));
    $privatePath = $normalizePath($directory);
    $publicPath = rtrim($normalizePath($publicRoot), '/');
    if ($privatePath === $publicPath || str_starts_with($privatePath, $publicPath . '/')) {
        throw new RuntimeException('Contact storage must be outside the document root.');
    }
    $database = new PDO('sqlite:' . $directory . '/contacts.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $database->exec('PRAGMA busy_timeout = 5000');
    $database->exec('CREATE TABLE IF NOT EXISTS contacts (
        id INTEGER PRIMARY KEY,
        request_id TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        business TEXT NOT NULL,
        email TEXT NOT NULL,
        website TEXT NOT NULL,
        created_at TEXT NOT NULL
    )');
    return [$database, $directory];
}
