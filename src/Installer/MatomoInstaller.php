<?php

namespace PortlandLabs\MatomoMarketplacePlugin\Installer;

use Composer\Cache;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\PartialComposer;
use Composer\Util\Filesystem;

class MatomoInstaller extends LibraryInstaller
{
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
        $this->io->writeError($packageType);

        return match ($packageType) {
            'mpl-plugin', 'mpl-theme', 'mpl-matomo' => true,
            default => false,
        };
    }

    public function getInstallPath(PackageInterface $package): string
    {
        return match ($package->getType()) {
            'mpl-plugin', 'mpl-theme' => 'public/plugins/' . ($package->getTransportOptions()['matomo']['name'] ?? $package->getName()),
            'mpl-matomo' => 'public/',
            default => parent::getInstallPath($package),
        };
    }
}
