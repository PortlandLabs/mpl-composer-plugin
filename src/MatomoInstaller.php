<?php

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Cache;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\PartialComposer;
use Composer\Util\Filesystem;
use React\Promise\PromiseInterface;

class MatomoInstaller extends LibraryInstaller
{
    /**
     * Directories and files that we'll automatically try to symlink in
     */
    protected const MATOMO_SYMLINKS = [
        'plugins',
        'common.config.ini.php',
    ];

    public function __construct(
        protected Cache $cache,
        IOInterface $io,
        PartialComposer $composer,
        ?string $type = 'library',
        ?Filesystem $filesystem = null,
        ?BinaryInstaller $binaryInstaller = null,
    ) {
        parent::__construct($io, $composer, $type, $filesystem, $binaryInstaller);
    }

    public function supports(string $packageType): bool
    {
        return match ($packageType) {
            'mpl-plugin', 'mpl-theme', 'mpl-matomo' => true,
            default => false,
        };
    }

    public function getInstallPath(PackageInterface $package): string
    {
        return match ($package->getType()) {
            'mpl-plugin', 'mpl-theme' => 'mpl-matomo/plugins/' . ($package->getExtra()['mpl']['name'] ?? $package->getName()),
            'mpl-matomo' => 'mpl-matomo/',
            default => parent::getInstallPath($package),
        };
    }

    public function cleanup($type, PackageInterface $package, ?PackageInterface $prevPackage = null)
    {
        $cleanup = parent::cleanup($type, $package, $prevPackage);
        if (!$cleanup instanceof PromiseInterface) {
            throw new \RuntimeException('Unexpected cleanup output.');
        }

        return (match ($type) {
            'install', 'update' => match ($package->getType()) {
                'mpl-matomo' => $cleanup->then(fn() => $this->createMatomoSymlinks(getcwd() . '/' . $this->getInstallPath($package))),
                default => $cleanup,
            },
            default => $cleanup,
        })->then(
            fn() => $package->setTransportOptions([...$package->getTransportOptions(), 'http' => []]),
        );
    }

    private function createMatomoSymlinks(string $matomoPath): void
    {
        $links = $this->composer->getPackage()->getExtra()['mpl']['link'] ?? [];
        foreach ($links as $path) {
            $this->io->debug("Creating symlinks for {$path}");
            $this->createSymlink(getcwd() . "/{$path}", "{$matomoPath}/{$path}");
        }
    }

    private function createSymlink(string $cwd, string $matomo): void
    {
        if (!file_exists($cwd)) {
            return;
        }

        if (!file_exists($matomo)) {
            $this->filesystem->relativeSymlink($cwd, $matomo);
            return;
        }

        if (!is_dir($matomo) || !is_dir($cwd)) {
            return;
        }

        foreach ((scandir($cwd) ?: []) as $item) {
            if (ltrim($item, '.') === '') {
                continue;
            }

            $this->createSymlink($cwd . '/' . $item, $matomo . '/' . $item);
        }
    }
}
