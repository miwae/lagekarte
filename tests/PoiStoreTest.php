<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for poi_store.php.
 *
 * Because poi_store.php exits after every code-path, we cannot simply require()
 * it as a whole.  Instead we test the three pure helper functions in isolation
 * by defining them here in the same way they are defined in the source file,
 * and then we test the HTTP layer by spawning PHP's built-in web server in a
 * helper method.
 */
final class PoiStoreTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers: pure functions copied verbatim from poi_store.php
    // -----------------------------------------------------------------------

    private function read_store(string $path): array
    {
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

    private function write_store(string $path, array $data): bool
    {
        $tmp = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($tmp === false) {
            return false;
        }
        return @file_put_contents($path, $tmp, LOCK_EX) !== false;
    }

    private function clean_key(string $v): string
    {
        return preg_replace('/[^a-z0-9_.,\-]/i', '', $v) ?? '';
    }

    // -----------------------------------------------------------------------
    // read_store tests
    // -----------------------------------------------------------------------

    public function test_read_store_missing_file(): void
    {
        $result = $this->read_store('/tmp/this_file_does_not_exist_' . uniqid() . '.json');
        $this->assertSame(['cache' => []], $result);
    }

    public function test_read_store_empty_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'poi_test_');
        file_put_contents($path, '');
        $result = $this->read_store($path);
        unlink($path);
        $this->assertSame(['cache' => []], $result);
    }

    public function test_read_store_invalid_json(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'poi_test_');
        file_put_contents($path, 'not valid json {{{{');
        $result = $this->read_store($path);
        unlink($path);
        $this->assertSame(['cache' => []], $result);
    }

    public function test_read_store_missing_cache_key(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'poi_test_');
        file_put_contents($path, json_encode(['foo' => 'bar']));
        $result = $this->read_store($path);
        unlink($path);
        $this->assertSame(['cache' => []], $result);
    }

    public function test_read_store_cache_not_array(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'poi_test_');
        file_put_contents($path, json_encode(['cache' => 'string']));
        $result = $this->read_store($path);
        unlink($path);
        $this->assertSame(['cache' => []], $result);
    }

    public function test_read_store_valid(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'poi_test_');
        $data = ['cache' => ['hospital' => ['52.1,7.1,52.2,7.2' => ['ts' => 'now', 'elements' => [['id' => 1]]]]]];
        file_put_contents($path, json_encode($data));
        $result = $this->read_store($path);
        unlink($path);
        $this->assertSame($data, $result);
    }

    // -----------------------------------------------------------------------
    // write_store tests
    // -----------------------------------------------------------------------

    public function test_write_store_creates_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'poi_test_');
        unlink($path); // remove so write creates it
        $data = ['cache' => ['test' => []]];
        $ok = $this->write_store($path, $data);
        $this->assertTrue($ok);
        $this->assertFileExists($path);
        unlink($path);
    }

    public function test_write_store_roundtrip(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'poi_test_');
        $data = ['cache' => ['hospital' => ['bbox' => ['ts' => 'now', 'elements' => [['id' => 42, 'name' => 'Klinikum']]]]]];
        $this->write_store($path, $data);
        $result = $this->read_store($path);
        unlink($path);
        $this->assertSame($data, $result);
    }

    public function test_write_store_unicode_preserved(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'poi_test_');
        $data = ['cache' => ['note' => ['bbox' => ['ts' => 'now', 'elements' => [['name' => 'Mühlstraße']]]]]];
        $this->write_store($path, $data);
        $raw = file_get_contents($path);
        unlink($path);
        // JSON_UNESCAPED_UNICODE: ü should NOT be \u00fc in the file
        $this->assertStringContainsString('Mühlstraße', $raw);
    }

    // -----------------------------------------------------------------------
    // clean_key tests
    // -----------------------------------------------------------------------

    public function test_clean_key_allows_alphanumeric(): void
    {
        $this->assertSame('abc123XYZ', $this->clean_key('abc123XYZ'));
    }

    public function test_clean_key_allows_permitted_special_chars(): void
    {
        $this->assertSame('52.1,7.1,52.2,7.2', $this->clean_key('52.1,7.1,52.2,7.2'));
        $this->assertSame('type_name', $this->clean_key('type_name'));
        $this->assertSame('type-name', $this->clean_key('type-name'));
    }

    public function test_clean_key_strips_html_special_chars(): void
    {
        // < > / are stripped; letters remain
        $this->assertSame('scriptsafescript', $this->clean_key('<script>safe</script>'));
        // Parentheses and quotes are stripped; letters remain
        $this->assertSame('alertxss', $this->clean_key('alert("xss")'));
    }

    public function test_clean_key_strips_spaces(): void
    {
        $this->assertSame('nospace', $this->clean_key('no space'));
    }

    public function test_clean_key_strips_path_traversal(): void
    {
        // Dots are in the allowed set; forward slashes are stripped.
        // '../../etc/passwd' → dots kept, slashes removed → '....etcpasswd'
        $this->assertSame('....etcpasswd', $this->clean_key('../../etc/passwd'));
    }

    public function test_clean_key_returns_empty_for_all_special(): void
    {
        $this->assertSame('', $this->clean_key('!"£$%^&*()'));
    }

    // -----------------------------------------------------------------------
    // HTTP endpoint tests via PHP built-in server
    // -----------------------------------------------------------------------

    /** @var string */
    private static string $baseUrl;

    /** @var int */
    private static int $serverPid;

    /** @var string */
    private static string $cacheFile;

    public static function setUpBeforeClass(): void
    {
        $dir = realpath(__DIR__ . '/..');
        $host = '127.0.0.1';
        $port = 19876;
        self::$baseUrl = "http://{$host}:{$port}/poi_store.php";
        self::$cacheFile = $dir . '/poi_cache.json';

        // Remove any leftover cache from previous runs
        if (file_exists(self::$cacheFile)) {
            unlink(self::$cacheFile);
        }

        $cmd = sprintf(
            'php -S %s:%d -t %s > /dev/null 2>&1 & echo $!',
            $host,
            $port,
            escapeshellarg($dir)
        );
        $output = shell_exec($cmd);
        self::$serverPid = (int) trim((string) $output);

        // Give the server a moment to start
        usleep(300000);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$serverPid > 0) {
            posix_kill(self::$serverPid, SIGTERM);
        }
        if (file_exists(self::$cacheFile)) {
            unlink(self::$cacheFile);
        }
    }

    private function httpGet(string $url): array
    {
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'ignore_errors' => true]]);
        $body = file_get_contents($url, false, $ctx);
        $json = json_decode((string) $body, true);
        return is_array($json) ? $json : [];
    }

    private function httpPost(string $url, mixed $payload): array
    {
        $body = json_encode($payload);
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/json',
                'content' => $body,
                'ignore_errors' => true,
            ],
        ]);
        $response = file_get_contents($url, false, $ctx);
        $json = json_decode((string) $response, true);
        return is_array($json) ? $json : [];
    }

    // ---- GET tests ----

    public function test_get_invalid_action(): void
    {
        $r = $this->httpGet(self::$baseUrl . '?action=wrong&type=hospital&bbox=1,2,3,4');
        $this->assertFalse($r['ok']);
        $this->assertSame('invalid action', $r['error']);
    }

    public function test_get_missing_type(): void
    {
        $r = $this->httpGet(self::$baseUrl . '?action=get&bbox=1,2,3,4');
        $this->assertFalse($r['ok']);
        $this->assertSame('missing params', $r['error']);
    }

    public function test_get_missing_bbox(): void
    {
        $r = $this->httpGet(self::$baseUrl . '?action=get&type=hospital');
        $this->assertFalse($r['ok']);
        $this->assertSame('missing params', $r['error']);
    }

    public function test_get_empty_store_returns_no_elements(): void
    {
        $r = $this->httpGet(self::$baseUrl . '?action=get&type=hospital&bbox=52.1,7.1,52.2,7.2');
        $this->assertTrue($r['ok']);
        $this->assertIsArray($r['elements']);
        $this->assertSame(0, $r['count']);
    }

    // ---- POST tests ----

    public function test_post_invalid_json(): void
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/json',
                'content' => 'not json',
                'ignore_errors' => true,
            ],
        ]);
        $body = file_get_contents(self::$baseUrl, false, $ctx);
        $r = json_decode((string) $body, true);
        $this->assertFalse($r['ok']);
        $this->assertSame('invalid json', $r['error']);
    }

    public function test_post_invalid_action(): void
    {
        $r = $this->httpPost(self::$baseUrl, ['action' => 'wrong', 'type' => 'hospital', 'bbox' => '1,2,3,4', 'elements' => []]);
        $this->assertFalse($r['ok']);
        $this->assertSame('invalid action', $r['error']);
    }

    public function test_post_missing_type(): void
    {
        $r = $this->httpPost(self::$baseUrl, ['action' => 'save', 'bbox' => '1,2,3,4', 'elements' => []]);
        $this->assertFalse($r['ok']);
        $this->assertSame('invalid payload', $r['error']);
    }

    public function test_post_missing_bbox(): void
    {
        $r = $this->httpPost(self::$baseUrl, ['action' => 'save', 'type' => 'hospital', 'elements' => []]);
        $this->assertFalse($r['ok']);
        $this->assertSame('invalid payload', $r['error']);
    }

    public function test_post_elements_not_array(): void
    {
        $r = $this->httpPost(self::$baseUrl, ['action' => 'save', 'type' => 'hospital', 'bbox' => '1,2,3,4', 'elements' => 'bad']);
        $this->assertFalse($r['ok']);
        $this->assertSame('invalid payload', $r['error']);
    }

    public function test_post_save_and_get_roundtrip(): void
    {
        $elements = [['id' => 101, 'name' => 'Klinikum'], ['id' => 102, 'name' => 'Rettungswache']];
        $bbox = '52.100,7.100,52.200,7.200';
        $type = 'hospital';

        $save = $this->httpPost(self::$baseUrl, ['action' => 'save', 'type' => $type, 'bbox' => $bbox, 'elements' => $elements]);
        $this->assertTrue($save['ok']);
        $this->assertSame(2, $save['count']);

        $get = $this->httpGet(self::$baseUrl . '?action=get&type=' . urlencode($type) . '&bbox=' . urlencode($bbox));
        $this->assertTrue($get['ok']);
        $this->assertSame(2, $get['count']);
        $this->assertCount(2, $get['elements']);
    }

    public function test_post_truncates_elements_to_max_2000(): void
    {
        $elements = array_fill(0, 2500, ['id' => 1]);
        $bbox = '50.0,10.0,51.0,11.0';
        $type = 'fire_station';

        $save = $this->httpPost(self::$baseUrl, ['action' => 'save', 'type' => $type, 'bbox' => $bbox, 'elements' => $elements]);
        $this->assertTrue($save['ok']);
        $this->assertSame(2000, $save['count']);

        $get = $this->httpGet(self::$baseUrl . '?action=get&type=' . urlencode($type) . '&bbox=' . urlencode($bbox));
        $this->assertSame(2000, $get['count']);
    }

    public function test_post_overwrites_existing_entry(): void
    {
        $bbox  = '48.0,11.0,49.0,12.0';
        $type  = 'pharmacy';

        $this->httpPost(self::$baseUrl, ['action' => 'save', 'type' => $type, 'bbox' => $bbox, 'elements' => [['id' => 1]]]);
        $this->httpPost(self::$baseUrl, ['action' => 'save', 'type' => $type, 'bbox' => $bbox, 'elements' => [['id' => 2], ['id' => 3]]]);

        $get = $this->httpGet(self::$baseUrl . '?action=get&type=' . urlencode($type) . '&bbox=' . urlencode($bbox));
        $this->assertSame(2, $get['count']);
    }

    public function test_unsupported_method_returns_error(): void
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'PUT',
                'header'  => 'Content-Type: application/json',
                'content' => '{}',
                'ignore_errors' => true,
            ],
        ]);
        $body = file_get_contents(self::$baseUrl, false, $ctx);
        $r = json_decode((string) $body, true);
        $this->assertFalse($r['ok']);
        $this->assertSame('method not allowed', $r['error']);
    }

    public function test_xss_chars_stripped_from_keys(): void
    {
        // Payload keys containing HTML special characters must be sanitised
        $bbox = '52.0<script>,7.0,52.1,7.1';
        $type = 'hospital<img onerror=alert(1)>';

        $save = $this->httpPost(self::$baseUrl, [
            'action'   => 'save',
            'type'     => $type,
            'bbox'     => $bbox,
            'elements' => [['id' => 999]],
        ]);
        $this->assertTrue($save['ok']);

        // The cache file must not contain any raw HTML characters
        $raw = file_get_contents(self::$cacheFile);
        $this->assertStringNotContainsString('<script>', (string) $raw);
        $this->assertStringNotContainsString('<img', (string) $raw);
    }
}
