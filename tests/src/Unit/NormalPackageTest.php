<?php

declare(strict_types = 1);

namespace Pamald\PamaldYarn\Tests\Unit;

use Pamald\PamaldYarn\NormalPackage;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NormalPackage::class)]
class NormalPackageTest extends TestBase
{
    public function testGetters(): void
    {
        $satisfiesList = [
            'my-pack1' => [
                '^1.2',
            ],
        ];
        $lockEntry = [
            'version' => '1.2.3',
        ];
        $package = new NormalPackage(
            $satisfiesList,
            $lockEntry,
            'dependencies',
            '^1.2',
        );

        static::assertSame('my-pack1', $package->name());
        static::assertSame(null, $package->type());
        static::assertSame('1.2.3', $package->versionString());
        static::assertSame(true, $package->isDirectDependency());
        static::assertSame(null, $package->issueTracker());
        static::assertSame('dependencies', $package->typeOfRelationship());
        static::assertSame('1.2.3', (string) $package->version());
        static::assertSame(null, $package->homepage());
        static::assertSame(null, $package->vcsInfo());
    }
}
