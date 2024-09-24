<?php

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Cache;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Package;
use Composer\Repository\ArrayRepository;
use Composer\Util\HttpDownloader;

class MatomoPluginRepository extends ArrayRepository
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

    protected function initialize(): void
    {
        $this->packages = [];
        $json = $this->fetchJson($this->apiUrl('/plugins', []), Util::authOptions($this->config))['plugins'] ?? [];

        foreach ($json as $packageData) {
            if (!($packageData['isDownloadable'] ?? false) || ($packageData['isBundle'] ?? true)) {
                continue;
            }
            $owner = $packageData['owner'] ?? null;
            $name = $packageData['name'] ?? null;
            $description = $packageData['description'] ?? null;
            $versions = $packageData['versions'] ?? [];
            $keywords = $packageData['keywords'] ?? [];
            $type = $packageData['isTheme'] ? 'mpl-theme' : 'mpl-plugin';
            $homepage = $packageData['homepage'] ?? null;

            if (!$owner || !$name || !$versions) {
                continue;
            }

            $handle = 'mpl/' . strtolower($owner) . '-' . strtolower($name);

            foreach ($versions as $versionData) {
                $knownFailures = $this::KNOWN_FAILURES["{$owner}/{$name}"] ?? [];
                if (in_array($versionData['name'], $knownFailures)) {
                    $this->io->debug("Skipping known failure {$owner}/{$name}@{$versionData['name']}");
                    continue;
                }
                $download = $versionData['download'] ?? null;
                if (!$download) {
                    continue;
                }

                $package = [
                    'name' => $handle,
                    'matomo_name' => $name,
                    'matomo_owner' => $owner,
                    'description' => $description,
                    'version' => $versionData['name'] ?? null,
                    'keywords' => $keywords,
                    'homepage' => $homepage,
                    'type' => $type,
                    'time' => $versionData['release'] ?? null,
                    'dist' => [
                        'url' => $this::API . ($versionData['download'] ?? ''),
                        'type' => 'mpl-plugin',
                    ],
                    'require' => [
                        'php' => $versionData['requires']['php'] ?? '*',
                    ],
                ];

                $license = $versionData['license']['name'] ?? null;
                if ($license) {
                    $package['license'] = $license;
                }

                $matomoRequirement = $versionData['requires']['matomo'] ?? $versionData['requires']['piwik'] ?? null;
                if ($matomoRequirement !== null) {
                    $package['require']['mpl/matomo'] = $matomoRequirement;
                }

                try {
                    $pkg = $this->loader->load($package);
                    if (!$pkg instanceof Package) {
                        throw new \RuntimeException('Invalid type returned from loading plugin package json.');
                    }

                    $pkg->setExtra([
                        ...$pkg->getExtra(),
                        'mpl' => [
                            'name' => $name,
                            'owner' => $owner,
                        ],
                    ]);

                    $this->addPackage($pkg);
                } catch (\Throwable $e) {
                    $this->io->debug('Unable to load ' . "{$owner}/{$name}@{$package['version']}" . ': ' . $e->getMessage());
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
            return json_decode((string) $this->cache->read($cacheKey), true);
        }

        $this->io->notice('Loading matomo plugins, this usually takes about 10 seconds...');
        $result = $this->downloader->get($uri, $options)->getBody();
        if ($result === null) {
            throw new \RuntimeException('Failed to fetch json.');
        }

        $this->cache->write($cacheKey, $result);
        return json_decode($result, true);
    }

    /**
     * @param string $path
     * @param array<string, string> $query
     * @return string
     */
    protected function apiUrl(string $path, array $query = []): string
    {
        $separator = str_contains($this::API, '?') ? '&' : '?';
        $path = ltrim($path, '/');
        $query = http_build_query($query);
        $api = rtrim($this::API, '/') . '/api';
        $version = $this::API_VERSION;
        return "{$api}/{$version}/{$path}{$separator}{$query}";
    }

    public function getRepoName(): string
    {
        return 'Matomo';
    }
}
