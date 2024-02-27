<?php

declare(strict_types = 1);

namespace Pamald\PamaldYarn\Tests\Unit;

use Pamald\PamaldYarn\PackageCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Siketyan\YarnLock\YarnLock;

#[CoversClass(PackageCollector::class)]
class PackageCollectorTest extends TestBase
{

    /**
     * @return array<string, mixed>
     */
    public static function casesParseLockKey(): array
    {
        return [
            'basic 1' => [
                [
                    'foo' => [
                        '^1.2.3',
                    ],
                ],
                'foo@^1.2.3',
            ],
            'all in one' => [
                [
                    'pack1' => [
                        '^1.2.3'
                    ],
                    'cssom' => [
                        '>= 0.3.0 < 0.4.0',
                    ],
                    'pack2' => [
                        '*',
                    ],
                    'pack3' => [
                        '~4.5',
                    ],
                ],
                'pack1@^1.2.3,"cssom@>= 0.3.0 < 0.4.0",pack2@*,pack3@~4.5',
            ],
        ];
    }

    /**
     * @param array<string, string[]> $expected
     *
     * @return void
     */
    #[DataProvider('casesParseLockKey')]
    public function testParseLockKey(array $expected, string $lockKey): void
    {
        $collector = new PackageCollector();
        static::assertSame($expected, $collector->parseLockKey($lockKey));
    }

    /**
     * @return array<string, mixed>
     */
    public static function casesCollect(): array
    {
        $projectDir = static::fixturesDir('project-01');

        return [
            'empty' => [
                'expected' => [],
                'lock' => [],
                'json' => null,
            ],
            'basic' => [
                'expected' => [
                    'find-versions' => [
                        'name' => 'find-versions',
                        'versionString' => '5.0.0',
                        'typeOfRelationship' => 'dependencies',
                        'isDirectDependency' => true,
                    ],
                    'semver-regex' => [
                        'name' => 'semver-regex',
                        'versionString' => '4.0.1',
                        'typeOfRelationship' => null,
                        'isDirectDependency' => false,
                    ],
                ],
                'lock' => YarnLock::toArray(file_get_contents("$projectDir/01.yarnlock") ?: ''),
                'json' => json_decode(file_get_contents("$projectDir/01.json") ?: '{}', true),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $lock
     * @param null|array<string, mixed> $json
     */
    #[DataProvider('casesCollect')]
    public function testCollect(array $expected, array $lock, ?array $json): void
    {
        $collector = new PackageCollector();
        $actual = $collector->collect($lock, $json);
        static::assertSame(array_keys($expected), array_keys($actual));
        foreach ($expected as $id => $expectedValues) {
            if (array_key_exists('name', $expectedValues)) {
                static::assertSame(
                    $expectedValues['name'],
                    $actual[$id]->name(),
                    "$id::name",
                );
            }

            if (array_key_exists('versionString', $expectedValues)) {
                static::assertSame(
                    $expectedValues['versionString'],
                    $actual[$id]->versionString(),
                    "$id::versionString",
                );
            }

            if (array_key_exists('typeOfRelationship', $expectedValues)) {
                static::assertSame(
                    $expectedValues['typeOfRelationship'],
                    $actual[$id]->typeOfRelationship(),
                    "$id::typeOfRelationship",
                );
            }

            if (array_key_exists('isDirectDependency', $expectedValues)) {
                static::assertSame(
                    $expectedValues['isDirectDependency'],
                    $actual[$id]->isDirectDependency(),
                    "$id::isDirectDependency",
                );
            }
        }
    }
}
