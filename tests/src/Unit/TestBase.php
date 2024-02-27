<?php

declare(strict_types = 1);

namespace Pamald\PamaldYarn\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

class TestBase extends TestCase
{

    protected static function selfProjectRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    protected static function fixturesDir(string ...$parts): string
    {
        return Path::join(
            static::selfProjectRoot(),
            'tests',
            'fixtures',
            ...$parts,
        );
    }

    protected function createTempDir(): string
    {
        $dir = $this->randomTempDirName();
        mkdir($dir, 0777 - umask(), true);

        return $dir;
    }

    protected function randomTempDirName(): string
    {
        return implode('/', [
            sys_get_temp_dir(),
            'pamald',
            'pamald-yarn',
            'test-' . $this->randomId(),
        ]);
    }

    protected function randomId(): string
    {
        return md5((string) (microtime(true) * rand(0, 10000)));
    }
}
