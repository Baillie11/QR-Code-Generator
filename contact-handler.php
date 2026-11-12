<?php
// This header prevents this page and its frames from requesting display capture.
header('Permissions-Policy: display-capture=()');
header('Cache-Control: no-store');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'use_strict_mode' => true,
]);
$_SESSION['contact_csrf'] ??= bin2hex(random_bytes(32));
$contactCsrf = $_SESSION['contact_csrf'];
session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

header('Content-Type: application/json; charset=utf-8');
function contactResponse(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if (!is_string($_POST['csrf'] ?? null) || !hash_equals($contactCsrf, $_POST['csrf'])) {
    contactResponse(403, ['error' => 'Your session has expired. Refresh the page and try again.']);
}

$contact = [];
foreach (['name' => 150, 'business' => 150, 'email' => 254, 'website' => 2048, 'requestId' => 64] as $field => $limit) {
    $value = $_POST[$field] ?? '';
    if (!is_string($value) || trim($value) === '' || strlen($value) > $limit || preg_match('/[\x00-\x1f\x7f]/', $value)) {
        contactResponse(422, ['error' => 'Please complete all four fields with valid contact details.']);
    }
    $contact[$field] = trim($value);
}
if (!filter_var($contact['email'], FILTER_VALIDATE_EMAIL)
    || !filter_var($contact['website'], FILTER_VALIDATE_URL)
    || !in_array(strtolower(parse_url($contact['website'], PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)
    || !preg_match('/^[a-f0-9]{32}$/', $contact['requestId'])) {
    contactResponse(422, ['error' => 'Enter a valid email address and an http or https website address.']);
}

try {
    require_once __DIR__ . '/contact-storage.php';
    require_once __DIR__ . '/contact-sync.php';
    [$database, $directory] = openContactDatabase();
    $statement = $database->prepare('INSERT INTO contacts (request_id, name, business, email, website, created_at)
        VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT(request_id) DO NOTHING');
    $statement->execute([$contact['requestId'], $contact['name'], $contact['business'], $contact['email'], $contact['website'], gmdate('c')]);
    // A CRM outage must not prevent a free download after local persistence.
    try {
        syncContacts($database, $directory, $contact['requestId']);
    } catch (Throwable $error) {
        error_log('QR contact sync deferred; contact remains queued.');
    }
    contactResponse(200, ['saved' => true]);
} catch (Throwable $error) {
    error_log('QR contact storage failed: ' . $error->getMessage());
    contactResponse(503, ['error' => 'We could not save your details right now. Please try downloading again in a moment.']);
}
