<?php

declare(strict_types=1);

namespace PortlandLabs\MatomoMarketplacePlugin\Model;

final readonly class MatomoPluginVersion
{
    protected function __construct(
        public string $name,
        public string $download,
        public string $release,
        /** @var array<string, string> */
        public array $requires,
        public string $license,
    ) {}

    /**
     * @param array{
     *     name?: string,
     *     download?: string,
     *     release?: string,
     *     requires?: array<string, string>,
     *     license?: array{name?: string}
     * } $data
     * @return MatomoPluginVersion
     */
    public static function fromJsonData(array $data): MatomoPluginVersion
    {
        return new MatomoPluginVersion(
            $data['name'] ?? '',
            $data['download'] ?? '',
            $data['release'] ?? '',
            $data['requires'] ?? [],
            $data['license']['name'] ?? '',
        );
    }
}
