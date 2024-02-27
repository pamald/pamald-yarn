<?php

declare(strict_types = 1);

namespace Pamald\PamaldYarn;

use Pamald\Pamald\PackageCollectorInterface;

class PackageCollector implements PackageCollectorInterface
{

    /**
     * @var array<string, mixed>
     */
    protected array $lock;

    /**
     * @var array<string, mixed>
     */
    protected ?array $json;

    /**
     * @var array<string, \Pamald\Pamald\PackageInterface>
     */
    protected array $packages;

    /**
     * {@inheritdoc}
     */
    public function collect(?array $lock, ?array $json): array
    {
        if (!$lock) {
            return [];
        }

        $this->lock = $lock;
        $this->json = $json;
        $this->packages = [];
        foreach ($this->lock as $lockKey => $lockEntry) {
            $satisfiesList = $this->parseLockKey($lockKey);
            $name = array_key_first($satisfiesList);
            $typeOfRelationship = $this->getTypeOfRelationship((string) $name, $json);

            $package = new NormalPackage(
                $satisfiesList,
                $lockEntry,
                $typeOfRelationship['type'] ?? null,
                $typeOfRelationship['versionConstraint'] ?? null,
                [],
            );

            $idCandidates = [
                $package->name(),
            ];

            $version = $package->version();
            if ($version) {
                $idCandidates[] = sprintf('%s@%s', $package->name(), $version->formatMA2);
                $idCandidates[] = sprintf('%s@%s', $package->name(), $version->formatMA0DMI0);
                $idCandidates[] = sprintf('%s@%s', $package->name(), $version->formatMA0DMI0DP0R);
            }

            $idCandidates[] = sprintf('%s@%s', $package->name(), $package->versionString());
            foreach ($idCandidates as $idCandidate) {
                if (!isset($this->packages[$idCandidate])) {
                    $this->packages[$idCandidate] = $package;

                    break;
                }
            }
        }

        return $this->packages;
    }

    /**
     * @return array<string, string[]>
     */
    public function parseLockKey(string $lockKey): array
    {
        $satisfies = [];
        foreach (preg_split('/\s*,\s*/', $lockKey) ?: [] as $item) {
            $item = trim($item, '"');
            /** @var string[] $parts */
            $parts = (array) preg_split('/(?<=.)@/', $item, 2);
            $satisfies[$parts[0]][] = $parts[1];
        }

        return $satisfies;
    }

    /**
     * @param string $name
     * @param null|array<string, mixed> $json
     *
     * @phpstan-return null|pamald-yarn-relationship
     */
    public function getTypeOfRelationship(string $name, ?array $json): ?array
    {
        if (!$json) {
            return null;
        }

        $dependencyTypes = [
            'dependencies',
            'optionalDependencies',
            'peerDependencies',
            'devDependencies',
        ];

        foreach ($dependencyTypes as $type) {
            if (!empty($json[$type][$name])) {
                return [
                    'type' => $type,
                    'versionConstraint' => $json[$type][$name],
                ];
            }
        }

        return null;
    }
}
