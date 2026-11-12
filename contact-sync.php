<?php
function syncContacts(PDO $database, string $directory, ?string $requestId = null): array
{
    $configPath = $directory . '/click-marketing.json';
    $config = is_file($configPath) ? json_decode(file_get_contents($configPath), true, 512, JSON_THROW_ON_ERROR) : [];
    $baseUrl = rtrim(getenv('CLICK_MARKETING_URL') ?: ($config['url'] ?? ''), '/');
    $token = getenv('CLICK_MARKETING_TOKEN') ?: ($config['token'] ?? '');
    $businessId = getenv('CLICK_MARKETING_BUSINESS_ID') ?: ($config['businessId'] ?? '');
    if (!$baseUrl || !$token || !$businessId) return ['status' => 'not-configured', 'synced' => 0];
    $parsed = parse_url($baseUrl);
    $local = in_array($parsed['host'] ?? '', ['localhost', '127.0.0.1', '[::1]'], true);
    if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || isset($parsed['user']) || isset($parsed['pass'])
        || isset($parsed['query']) || isset($parsed['fragment'])
        || (($parsed['scheme'] ?? '') !== 'https' && !(($parsed['scheme'] ?? '') === 'http' && $local))
        || preg_match('/[\r\n]/', $token)) {
        throw new RuntimeException('Invalid Click Marketing Agent configuration.');
    }
    $lock = fopen($directory . '/contact-sync.lock', 'c');
    if (!$lock) throw new RuntimeException('Cannot lock sync queue.');
    try {
        if (!flock($lock, LOCK_EX | LOCK_NB)) return ['status' => 'busy', 'synced' => 0];
        $database->exec('CREATE TABLE IF NOT EXISTS contact_sync (
            request_id TEXT PRIMARY KEY, synced_at TEXT NOT NULL
        )');
        $query = $database->prepare('SELECT contacts.* FROM contacts LEFT JOIN contact_sync USING (request_id)
            WHERE contact_sync.request_id IS NULL' . ($requestId !== null ? ' AND contacts.request_id = ?' : '') . '
            ORDER BY contacts.id LIMIT 20');
        $query->execute($requestId !== null ? [$requestId] : []);
        $contacts = $query->fetchAll(PDO::FETCH_ASSOC);
        $synced = 0;
        foreach ($contacts as $contact) {
            $body = json_encode([
                'businessId' => $businessId,
                'email' => $contact['email'],
                'contactName' => $contact['name'],
                'businessName' => $contact['business'],
                'website' => $contact['website'],
                'source' => 'qr-code-generator',
                'sourceId' => 'qr:' . $contact['request_id'],
            ], JSON_THROW_ON_ERROR);
            $context = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . $token . "\r\n",
                'content' => $body, 'timeout' => 5, 'ignore_errors' => true,
                'follow_location' => 0,
            ], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
            $http_response_header = [];
            $response = @file_get_contents($baseUrl . '/api/integrations/qr-code-generator/contacts', false, $context);
            preg_match('/^HTTP\/\S+ (\d{3})/', $http_response_header[0] ?? '', $match);
            $status = (int) ($match[1] ?? 0);
            $result = is_string($response) ? json_decode($response, true) : null;
            if ($status !== 204 && !($status === 200 && is_string($result['id'] ?? null))) {
                // Do not log the response, token, or personal details.
                error_log('QR contact sync deferred (HTTP ' . $status . ').');
                return ['status' => 'pending', 'synced' => $synced];
            }
            $mark = $database->prepare('INSERT OR IGNORE INTO contact_sync (request_id, synced_at) VALUES (?, ?)');
            $mark->execute([$contact['request_id'], gmdate('c')]);
            ++$synced;
        }
        return ['status' => 'ok', 'synced' => $synced];
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
