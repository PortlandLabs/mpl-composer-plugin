<?php

namespace PortlandLabs\MatomoMarketplacePlugin\Repository;

use Composer\Cache;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Repository\ArrayRepository;
use Composer\Util\HttpDownloader;
use PortlandLabs\MatomoMarketplacePlugin\Util\Http;

class MatomoPluginRepository extends ArrayRepository
{
    protected const API = 'https://plugins.matomo.org';
    protected const API_VERSION = '2.0';

    /**
     * A list of packages names and their versions that we know are incompatible with composer
     */
    protected const KNOWN_FAILURES = [
        'SARAVANA1501/TrackerJsCdnSync' => [
            '0.0.7-2',
            '0.0.7-3',
        ],
    ];

    private string $auth;

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

        $boundary = '----MatomoBoundaryW3XDKIM3ZOBE9SKYHINXT5QO';
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => ['Content-Type: multipart/form-data; boundary=' . $boundary],
                'content' => Http::multipartBody($boundary, ['access_token' => $this->config->get('matomo-auth')['plugins'] ?? '']),
            ],
        ];

        $json = $this->fetchJson($this->apiUrl('/plugins', []), $options)['plugins'] ?? [];

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
                        'type' => 'zip',
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
                    $pkg->setTransportOptions([
                        'matomo' => [
                            'name' => $name,
                            'owner' => $owner,
                        ],
                        ...$options,
                    ]);

                    $this->addPackage($pkg);
                } catch (\Throwable $e) {
                    $this->io->debug('Unable to load ' . "{$owner}/{$name}@{$package['version']}" . ': ' . $e->getMessage());
                }
            }
        }
    }

    protected function fetchJson(string $uri, array $options = [], $ttl = 10): array
    {
        $cacheKey = 'mpl/' . hash('sha256', $uri) . '.json';
        $cacheAge = $this->cache->getAge($cacheKey);
        if ($cacheAge !== false && $cacheAge < $ttl) {
            return json_decode($this->cache->read($cacheKey), true);
        }

        $this->io->notice('Loading matomo plugins, this usually takes about 10 seconds...');
        $result = $this->downloader->get($uri, $options)->getBody();

        $this->cache->write($cacheKey, $result);
        return json_decode($result, true);
    }

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
