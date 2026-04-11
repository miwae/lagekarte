<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PoiStoreTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'poi_store_');
        if ($this->tmpFile === false) {
            $this->fail('Temp file creation failed');
        }
    }

    protected function tearDown(): void
    {
        if (is_string($this->tmpFile) && file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
    }

    public function testReadStoreReturnsEmptyCacheWhenFileMissing(): void
    {
        $missing = $this->tmpFile . '_missing';
        $data = read_store($missing);

        $this->assertSame(['cache' => []], $data);
    }

    public function testReadStoreReturnsEmptyCacheOnInvalidJson(): void
    {
        file_put_contents($this->tmpFile, '{invalid json');
        $data = read_store($this->tmpFile);

        $this->assertSame(['cache' => []], $data);
    }

    public function testWriteStoreAndReadBackRoundtrip(): void
    {
        $payload = [
            'cache' => [
                'hydrant' => [
                    '10,10,11,11' => [
                        'ts' => '2026-01-01T00:00:00Z',
                        'elements' => [['id' => 1], ['id' => 2]],
                    ],
                ],
            ],
        ];

        $ok = write_store($this->tmpFile, $payload);
        $this->assertTrue($ok);

        $read = read_store($this->tmpFile);
        $this->assertSame($payload, $read);
    }

    public function testCleanKeyStripsUnsafeCharacters(): void
    {
        $clean = clean_key('../../x?<script>alert(1)</script>');
        $this->assertSame('....xscriptalert1script', $clean);
    }

    public function testCleanKeyKeepsAllowedCharacters(): void
    {
        $clean = clean_key('amenity,shop_1-2.3');
        $this->assertSame('amenity,shop_1-2.3', $clean);
    }
}
