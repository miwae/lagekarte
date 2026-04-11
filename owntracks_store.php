<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-OT-KEY');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$storePath = __DIR__ . DIRECTORY_SEPARATOR . 'owntracks_cache.json';
$maxPoints = 12000;
$maxDaysStored = 30;
$defaultKey = 'OT-LRZ8IAH2-J2JMRIES';
$serverKey = getenv('OWNTRACKS_KEY');
$otKey = is_string($serverKey) && $serverKey !== '' ? $serverKey : $defaultKey;

function ot_json(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ot_read_store(string $path): array {
    if (!file_exists($path)) {
        return ['points' => []];
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return ['points' => []];
    }
    $json = json_decode($raw, true);
    if (!is_array($json) || !isset($json['points']) || !is_array($json['points'])) {
        return ['points' => []];
    }
    return $json;
}

function ot_write_store(string $path, array $data): bool {
    $tmp = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($tmp === false) {
        return false;
    }
    return @file_put_contents($path, $tmp, LOCK_EX) !== false;
}

function ot_clean_key(string $v): string {
    return preg_replace('/[^a-z0-9_.,\-]/i', '', $v) ?? '';
}

function ot_extract_tid(array $body): string {
    $tid = (string)($body['tid'] ?? $body['tracker_id'] ?? '');
    if ($tid !== '') {
        return trim($tid);
    }

    $device = (string)($body['device'] ?? $body['deviceId'] ?? $body['device_id'] ?? '');
    if ($device !== '') {
        return trim($device);
    }

    $topic = (string)($body['topic'] ?? '');
    if ($topic !== '') {
        $parts = explode('/', trim($topic, '/'));
        $last = end($parts);
        return trim((string)$last);
    }

    return '';
}

function ot_extract_number(array $body, array $keys, float $fallback = 0.0): float {
    foreach ($keys as $k) {
        if (isset($body[$k]) && is_numeric($body[$k])) {
            return (float)$body[$k];
        }
    }
    return $fallback;
}

function ot_extract_int(array $body, array $keys, int $fallback = 0): int {
    foreach ($keys as $k) {
        if (isset($body[$k]) && is_numeric($body[$k])) {
            return (int)$body[$k];
        }
    }
    return $fallback;
}

function ot_format_response(array $points, int $days): array {
    $now = time();
    $cutoff = $days > 0 ? $now - ($days * 86400) : 0;

    $filtered = [];
    foreach ($points as $p) {
        if (!is_array($p)) {
            continue;
        }
        $tst = (int)($p['tst'] ?? 0);
        if ($tst <= 0) {
            continue;
        }
        if ($cutoff > 0 && $tst < $cutoff) {
            continue;
        }
        $filtered[] = $p;
    }

    usort($filtered, static function (array $a, array $b): int {
        return ((int)($a['tst'] ?? 0)) <=> ((int)($b['tst'] ?? 0));
    });

    $byTracker = [];
    foreach ($filtered as $p) {
        $tid = (string)($p['tracker_id'] ?? '');
        if ($tid === '') {
            continue;
        }
        if (!isset($byTracker[$tid])) {
            $byTracker[$tid] = [];
        }
        $byTracker[$tid][] = $p;
    }

    $tracks = [];
    $trackers = [];
    $last = [];

    foreach ($byTracker as $tid => $list) {
        $count = count($list);
        if ($count === 0) {
            continue;
        }

        $lastPoint = $list[$count - 1];
        $userName = (string)($lastPoint['user_name'] ?? '');
        $deviceName = (string)($lastPoint['device_name'] ?? '');

        $trackers[] = [
            'tracker_id' => $tid,
            'user_name' => $userName,
            'device_name' => $deviceName,
            'points' => $count,
            'last_seen' => (int)($lastPoint['tst'] ?? 0),
        ];

        $last[] = [
            'tracker_id' => $tid,
            'lat' => (float)($lastPoint['lat'] ?? 0),
            'lon' => (float)($lastPoint['lon'] ?? 0),
            'last_seen' => (int)($lastPoint['tst'] ?? 0),
            'battery' => (int)($lastPoint['battery'] ?? -1),
            'velocity' => (int)($lastPoint['velocity'] ?? 0),
            'accuracy' => (int)($lastPoint['accuracy'] ?? 0),
            'user_name' => $userName,
            'device_name' => $deviceName,
        ];

        foreach ($list as $p) {
            $tracks[] = [
                'tracker_id' => $tid,
                'lat' => (float)($p['lat'] ?? 0),
                'lon' => (float)($p['lon'] ?? 0),
                'tst' => (int)($p['tst'] ?? 0),
                'velocity' => (int)($p['velocity'] ?? 0),
                'battery' => (int)($p['battery'] ?? -1),
                'accuracy' => (int)($p['accuracy'] ?? 0),
            ];
        }
    }

    usort($trackers, static function (array $a, array $b): int {
        return ((int)$b['last_seen']) <=> ((int)$a['last_seen']);
    });

    return [
        'ok' => true,
        'trackers' => $trackers,
        'tracks' => $tracks,
        'last' => $last,
    ];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $action = (string)($_GET['action'] ?? 'get');
    if ($action !== 'get') {
        ot_json(['ok' => false, 'error' => 'invalid action'], 400);
    }

    $key = (string)($_GET['key'] ?? '');
    if ($key === '' || !hash_equals($otKey, $key)) {
        ot_json(['ok' => false, 'error' => 'unauthorized'], 401);
    }

    $days = (int)($_GET['days'] ?? 1);
    if ($days < 1) {
        $days = 1;
    }
    if ($days > $maxDaysStored) {
        $days = $maxDaysStored;
    }

    $store = ot_read_store($storePath);
    ot_json(ot_format_response($store['points'] ?? [], $days));
}

if ($method === 'POST') {
    $raw = (string)file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body) && isset($_POST['payload'])) {
        $body = json_decode((string)$_POST['payload'], true);
    }
    if (!is_array($body)) {
        ot_json(['ok' => false, 'error' => 'invalid json'], 400);
    }

    $action = (string)($body['action'] ?? ($_GET['action'] ?? 'ingest'));
    if ($action !== 'ingest' && $action !== 'publish' && $action !== 'save') {
        ot_json(['ok' => false, 'error' => 'invalid action'], 400);
    }

    $requestKey = (string)($_GET['key'] ?? ($_SERVER['HTTP_X_OT_KEY'] ?? ($body['key'] ?? '')));
    if ($requestKey === '' || !hash_equals($otKey, $requestKey)) {
        ot_json(['ok' => false, 'error' => 'unauthorized'], 401);
    }

    $lat = ot_extract_number($body, ['lat', 'latitude'], 0.0);
    $lon = ot_extract_number($body, ['lon', 'lng', 'longitude'], 0.0);
    if ($lat === 0.0 && $lon === 0.0) {
        ot_json(['ok' => false, 'error' => 'missing coordinates'], 400);
    }

    $tid = trim(ot_extract_tid($body));
    if ($tid === '') {
        ot_json(['ok' => false, 'error' => 'missing tid'], 400);
    }
    $tid = ot_clean_key($tid);
    if ($tid === '') {
        ot_json(['ok' => false, 'error' => 'invalid tid'], 400);
    }

    $tst = ot_extract_int($body, ['tst', 'timestamp'], time());
    if ($tst <= 0) {
        $tst = time();
    }

    $point = [
        'tracker_id' => $tid,
        'user_name' => trim((string)($body['username'] ?? $body['user'] ?? $body['uname'] ?? '')),
        'device_name' => trim((string)($body['device'] ?? $body['deviceId'] ?? $body['device_id'] ?? '')),
        'lat' => $lat,
        'lon' => $lon,
        'tst' => $tst,
        'velocity' => ot_extract_int($body, ['vel', 'velocity'], 0),
        'battery' => ot_extract_int($body, ['batt', 'battery'], -1),
        'accuracy' => ot_extract_int($body, ['acc', 'accuracy'], 0),
    ];

    $store = ot_read_store($storePath);
    $points = $store['points'] ?? [];
    if (!is_array($points)) {
        $points = [];
    }

    $duplicate = false;
    foreach ($points as $existing) {
        if (!is_array($existing)) {
            continue;
        }
        if ((string)($existing['tracker_id'] ?? '') === $point['tracker_id']
            && (int)($existing['tst'] ?? 0) === $point['tst']
            && (float)($existing['lat'] ?? 0) === (float)$point['lat']
            && (float)($existing['lon'] ?? 0) === (float)$point['lon']) {
            $duplicate = true;
            break;
        }
    }

    if (!$duplicate) {
        $points[] = $point;
    }

    $cutoff = time() - ($maxDaysStored * 86400);
    $points = array_values(array_filter($points, static function ($p) use ($cutoff): bool {
        if (!is_array($p)) {
            return false;
        }
        $tst = (int)($p['tst'] ?? 0);
        return $tst >= $cutoff;
    }));

    usort($points, static function (array $a, array $b): int {
        return ((int)($a['tst'] ?? 0)) <=> ((int)($b['tst'] ?? 0));
    });

    if (count($points) > $maxPoints) {
        $points = array_slice($points, -$maxPoints);
    }

    $store['points'] = $points;
    if (!ot_write_store($storePath, $store)) {
        ot_json(['ok' => false, 'error' => 'write failed'], 500);
    }

    ot_json(['ok' => true, 'stored' => !$duplicate, 'tracker_id' => $point['tracker_id'], 'tst' => $point['tst']]);
}

ot_json(['ok' => false, 'error' => 'method not allowed'], 405);
