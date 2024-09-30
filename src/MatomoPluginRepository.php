<?php

declare(strict_types=1);

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Cache;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Package;
use Composer\Repository\ArrayRepository;
use Composer\Util\HttpDownloader;
use PortlandLabs\MatomoMarketplacePlugin\Model\MatomoPlugin;

final class MatomoPluginRepository extends ArrayRepository
{
    protected const API = 'https://plugins.matomo.org';
    protected const API_VERSION = '2.0';

    /**
     * A list of packages names and versions that we don't complain about
     */
    protected const KNOWN_FAILURES = [
        'SARAVANA1501/TrackerJsCdnSync' => [
            '0.0.7-2',
            '0.0.7-3',
        ],
    ];

    public function __construct(
        protected Config $config,
        protected Cache $cache,
        protected IOInterface $io,
        protected ArrayLoader $loader,
        protected HttpDownloader $downloader,
    ) {
        parent::__construct([]);
    }

    #[\Override]
    protected function initialize(): void
    {
        $this->packages = [];
        $json = $this->fetchJson($this->apiUrl('/plugins', []), Util::authOptions($this->config))['plugins'] ?? [];
        $packages = array_map(MatomoPlugin::fromJsonData(...), $json);

        /** @var MatomoPlugin $package */
        foreach ($packages as $package) {
            /**
             * @TODO Support bundles
             */
            if (!$package->isDownloadable || $package->isBundle) {
                continue;
            }

            if ($package->owner === '' || $package->name === '' || $package->versions === []) {
                continue;
            }

            $handle = $package->getMplHandle();
            foreach ($package->versions as $version) {
                $pkgName = "{$package->owner}/{$package->name}";
                $debugName = "{$pkgName}@{$version->name}";
                $knownFailures = $this::KNOWN_FAILURES[$pkgName] ?? [];
                if (in_array($version->name, $knownFailures, true)) {
                    $this->io->debug("Skipping known failure {$debugName}");
                    continue;
                }

                if ($version->download === '') {
                    continue;
                }

                $packageData = [
                    'name' => $handle,
                    'extra' => [
                        'mpl' => [
                            'name' => $package->name,
                            'owner' => $package->owner,
                        ],
                    ],
                    'description' => $package->description,
                    'version' => $version->name,
                    'keywords' => [
                        ...$package->keywords,
                        ...$package->type->keywords(),
                    ],
                    'homepage' => $package->homepage,
                    'type' => $package->type->composerType(),
                    'time' => $version->release,
                    'dist' => [
                        'url' => $this::API . $version->download,
                        'type' => 'mpl-plugin', // This is the download type, not the package type
                    ],
                    'require' => [
                        'php' => $version->requires['php'] ?? '*',
                    ],
                ];

                if ($version->license !== '') {
                    $packageData['license'] = $version->license;
                }

                $matomoRequirement = $version->requires['matomo'] ?? $version->requires['piwik'] ?? null;
                if ($matomoRequirement !== null) {
                    $packageData['require']['mpl/matomo'] = $matomoRequirement;
                }

                try {
                    $pkg = $this->loader->load($packageData);
                    if (!$pkg instanceof Package) {
                        throw new \RuntimeException('Invalid type returned from loading plugin package json.');
                    }

                    $this->addPackage($pkg);
                } catch (\Throwable $e) {
                    $this->io->debug('Unable to load ' . $debugName . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $options
     * @return array<array-key, mixed>
     */
    protected function fetchJson(string $uri, array $options = [], int $ttl = 600): array
    {
        $cacheKey = 'mpl/' . hash('sha256', $uri) . '.json';
        $cacheAge = $this->cache->getAge($cacheKey);
        if ($cacheAge !== false && $cacheAge < $ttl) {
            return json_decode((string) $this->cache->read($cacheKey), true, flags: JSON_THROW_ON_ERROR);
        }

        $this->io->write('<info>Loading matomo plugins, this usually takes about 10 seconds...</info>');
        $result = $this->downloader->get($uri, $options)->getBody();
        if ($result === null) {
            throw new \RuntimeException('Failed to fetch json.');
        }

        $this->cache->write($cacheKey, $result);
        return json_decode($result, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $path
     * @param array<string, string> $query
     * @return string
     */
    protected function apiUrl(string $path, array $query = []): string
    {
        $separator = str_contains((string) $this::API, '?') ? '&' : '?';
        $path = ltrim($path, '/');
        $api = rtrim((string) $this::API, '/') . '/api';
        $version = $this::API_VERSION;
        return "{$api}/{$version}/{$path}{$separator}" . http_build_query($query);
    }

    #[\Override]
    public function getRepoName(): string
    {
        return 'Matomo';
    }
}
