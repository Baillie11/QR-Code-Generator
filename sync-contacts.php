<?php
// Invoke from a scheduler or shell; never expose a public queue-drain endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/contact-storage.php';
require __DIR__ . '/contact-sync.php';
try {
    [$database, $directory] = openContactDatabase();
    $result = syncContacts($database, $directory);
    echo json_encode($result) . PHP_EOL;
    exit($result['status'] === 'ok' ? 0 : 1);
} catch (Throwable $error) {
    fwrite(STDERR, "Contact sync failed. Check private storage and integration configuration.\n");
    exit(1);
}
