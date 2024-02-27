<?php

declare(strict_types = 1);

namespace Pamald\PamaldYarn\Tests\Unit;

use Pamald\Pamald\LockDiffEntry;
use Pamald\Pamald\LockDiffer;
use Pamald\Pamald\Reporter\ConsoleTableReporter;
use Pamald\PamaldYarn\NormalPackage;
use Pamald\PamaldYarn\PackageCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Siketyan\YarnLock\YarnLock;
use Sweetchuck\Utils\Filter\CustomFilter;

#[CoversClass(PackageCollector::class)]
#[CoversClass(NormalPackage::class)]
class ConsoleTableReporterTest extends TestBase
{

    /**
     * @var resource[]
     */
    protected array $streams = [];

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->streams as $stream) {
            fclose($stream);
        }
        $this->streams = [];
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function casesGenerate(): array
    {
        $projectDir = static::fixturesDir('project-01');

        return [
            'basic' => [
                'expected' => <<< 'TEXT'
                    +---------------+-----------+-----------+----------------+----------------+---------+---------+
                    | Name          | L Version | R Version | L Relationship | R Relationship | L Depth | R Depth |
                    +---------------+-----------+-----------+----------------+----------------+---------+---------+
                    | Direct prod                                                                                 |
                    | find-versions | 5.0.0     | 5.1.0     | dependencies   | dependencies   | direct  | direct  |
                    | Direct dev                                                                                  |
                    | Other                                                                                       |
                    | semver-regex  | 4.0.1     | 4.2.0     | ?              | ?              | child   | child   |
                    +---------------+-----------+-----------+----------------+----------------+---------+---------+

                    TEXT,
                'leftLock' => YarnLock::toArray(file_get_contents("$projectDir/01.yarnlock") ?: ''),
                'leftJson' => YarnLock::toArray(file_get_contents("$projectDir/01.json") ?: ''),
                'rightLock' => YarnLock::toArray(file_get_contents("$projectDir/02.yarnlock") ?: ''),
                'rightJson' => YarnLock::toArray(file_get_contents("$projectDir/02.json") ?: ''),
                'options' => [
                    'groups' => [
                        'direct-prod' => [
                            'enabled' => true,
                            'id' => 'direct-prod',
                            'title' => 'Direct prod',
                            'weight' => 0,
                            'showEmpty' => false,
                            'emptyContent' => '-- empty --',
                            'filter' => (new CustomFilter())
                                ->setOperator(function (LockDiffEntry $entry): bool {
                                    return $entry->right?->isDirectDependency()
                                        && $entry->right->typeOfRelationship() === 'dependencies';
                                }),
                            'comparer' => null,
                        ],
                        'direct-dev' => [
                            'enabled' => true,
                            'id' => 'direct-dev',
                            'title' => 'Direct dev',
                            'weight' => 1,
                            'showEmpty' => false,
                            'emptyContent' => '-- empty --',
                            'filter' => (new CustomFilter())
                                ->setOperator(function (LockDiffEntry $entry): bool {
                                    return $entry->right?->isDirectDependency()
                                        && $entry->right->typeOfRelationship() === 'devDependencies';
                                }),
                            'comparer' => null,
                        ],
                        'other' => [
                            'enabled' => true,
                            'id' => 'other',
                            'title' => 'Other',
                            'weight' => 999,
                            'showEmpty' => false,
                            'emptyContent' => '-- empty --',
                            'filter' => null,
                            'comparer' => null,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param null|array<string, mixed> $leftLock
     * @param null|array<string, mixed> $leftJson
     * @param null|array<string, mixed> $rightLock
     * @param null|array<string, mixed> $rightJson
     * @phpstan-param pamald-console-table-reporter-options $options
     */
    #[DataProvider('casesGenerate')]
    public function testGenerate(
        string $expected,
        ?array $leftLock = null,
        ?array $leftJson = null,
        ?array $rightLock = null,
        ?array $rightJson = null,
        array $options = [],
    ): void {
        if (!isset($options['stream'])) {
            $options['stream'] = static::createStream();
        }
        $this->streams[] = $options['stream'];

        $packageCollector = new PackageCollector();
        $differ = new LockDiffer();
        $entries = $differ->diff(
            $packageCollector->collect($leftLock, $leftJson),
            $packageCollector->collect($rightLock, $rightJson),
        );
        (new ConsoleTableReporter())
            ->setOptions($options)
            ->generate($entries);
        rewind($options['stream']);
        static::assertSame(
            $expected,
            stream_get_contents($options['stream']),
        );
    }

    /**
     * @return resource
     */
    protected static function createStream()
    {
        $filePath = 'php://memory';
        $resource = fopen($filePath, 'rw');
        if ($resource === false) {
            throw new \RuntimeException("file $filePath could not be opened");
        }

        return $resource;
    }
}
