<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$storePath = __DIR__ . DIRECTORY_SEPARATOR . 'poi_cache.json';
$maxElements = 2000;

function read_store(string $path): array {
    if (!file_exists($path)) {
        return ['cache' => []];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return ['cache' => []];
    }
    $json = json_decode($raw, true);
    if (!is_array($json) || !isset($json['cache']) || !is_array($json['cache'])) {
        return ['cache' => []];
    }
    return $json;
}

function write_store(string $path, array $data): bool {
    $tmp = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($tmp === false) {
        return false;
    }
    return @file_put_contents($path, $tmp, LOCK_EX) !== false;
}

function clean_key(string $v): string {
    return preg_replace('/[^a-z0-9_.,\-]/i', '', $v) ?? '';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $action = (string)($_GET['action'] ?? '');
    if ($action !== 'get') {
        echo json_encode(['ok' => false, 'error' => 'invalid action']);
        exit;
    }

    $type = clean_key((string)($_GET['type'] ?? ''));
    $bbox = clean_key((string)($_GET['bbox'] ?? ''));
    if ($type === '' || $bbox === '') {
        echo json_encode(['ok' => false, 'error' => 'missing params']);
        exit;
    }

    $store = read_store($storePath);
    $elements = $store['cache'][$type][$bbox]['elements'] ?? [];
    if (!is_array($elements)) {
        $elements = [];
    }

    echo json_encode([
        'ok' => true,
        'elements' => $elements,
        'count' => count($elements)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode((string)$raw, true);
    if (!is_array($body)) {
        echo json_encode(['ok' => false, 'error' => 'invalid json']);
        exit;
    }

    $action = (string)($body['action'] ?? '');
    if ($action !== 'save') {
        echo json_encode(['ok' => false, 'error' => 'invalid action']);
        exit;
    }

    $type = clean_key((string)($body['type'] ?? ''));
    $bbox = clean_key((string)($body['bbox'] ?? ''));
    $elements = $body['elements'] ?? [];

    if ($type === '' || $bbox === '' || !is_array($elements)) {
        echo json_encode(['ok' => false, 'error' => 'invalid payload']);
        exit;
    }

    if (count($elements) > $maxElements) {
        $elements = array_slice($elements, 0, $maxElements);
    }

    $store = read_store($storePath);
    if (!isset($store['cache'][$type]) || !is_array($store['cache'][$type])) {
        $store['cache'][$type] = [];
    }
    $store['cache'][$type][$bbox] = [
        'ts' => gmdate('c'),
        'elements' => $elements,
    ];

    if (!write_store($storePath, $store)) {
        echo json_encode(['ok' => false, 'error' => 'write failed']);
        exit;
    }

    echo json_encode(['ok' => true, 'count' => count($elements)]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'method not allowed']);
