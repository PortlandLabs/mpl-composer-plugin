<?php

declare(strict_types=1);

namespace PortlandLabs\MatomoMarketplacePlugin\Model;

final readonly class MatomoPlugin
{
    protected function __construct(
        public string $owner,
        public string $name,
        public string $description,
        public string $homepage,
        /** @var string[] */
        public array $keywords,
        public bool $isDownloadable,
        public bool $isBundle,
        public MatomoPluginType $type,
        /** @var MatomoPluginVersion[] */
        public array $versions,
    ) {}

    /**
     * @param array{
     *     owner?: string,
     *     name?: string,
     *     description?: string,
     *     keywords?: string[],
     *     homepage?: string,
     *     isDownloadable?: bool,
     *     isBundle?: bool,
     *     isTheme?: bool,
     *     isPaid?: bool,
     *     versions?: array<string, mixed>
     * } $data
     * @return MatomoPlugin
     */
    public static function fromJsonData(array $data): MatomoPlugin
    {
        return new MatomoPlugin(
            $data['owner'] ?? '',
            $data['name'] ?? '',
            $data['description'] ?? '',
            $data['homepage'] ?? '',
            $data['keywords'] ?? [],
            $data['isDownloadable'] ?? false,
            $data['isBundle'] ?? false,
            MatomoPluginType::create($data['isTheme'] ?? false, $data['isPaid'] ?? false),
            array_map(MatomoPluginVersion::fromJsonData(...), $data['versions'] ?? []),
        );
    }

    public function getMplHandle(): string
    {
        return 'mpl/' . strtolower($this->owner) . '-' . strtolower($this->name);
    }
}
