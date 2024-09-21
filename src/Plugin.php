<?php

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Cache;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Plugin\PluginInterface;
use Composer\Repository\VcsRepository;
use PortlandLabs\MatomoMarketplacePlugin\Installer\MatomoInstaller;
use PortlandLabs\MatomoMarketplacePlugin\Repository\MatomoPluginRepository;

class Plugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $config = $composer->getConfig();
        $cache = new Cache($io, $config->get('cache-repo-dir') . '/matomo', 'a-z0-9.$~_');

        // Add plugin repository
        $pluginRepository = new MatomoPluginRepository($config, $cache, $io, new ArrayLoader(), $composer->getLoop()->getHttpDownloader());
        $composer->getRepositoryManager()->addRepository($pluginRepository);

        // Add matomo repository
        $composer->getRepositoryManager()->addRepository(new VcsRepository(
            ['url' => 'https://github.com/PortlandLabs/mpl-matomo', 'type' => 'github'],
            $io,
            $config,
            $composer->getLoop()->getHttpDownloader(),
        ));

        $matomoInstaller = new MatomoInstaller($cache, $io, $composer);
        $composer->getInstallationManager()->addInstaller($matomoInstaller);
    }

    public function deactivate(Composer $composer, IOInterface $io) {}
    public function uninstall(Composer $composer, IOInterface $io) {}
}
