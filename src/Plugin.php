<?php

declare(strict_types=1);

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Cache;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Plugin\PluginInterface;

final class Plugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $config = $composer->getConfig();
        $cache = $this->createCache($io, $config);

        // Add plugin repository
        $pluginRepository = new MatomoPluginRepository($config, $cache, $io, new ArrayLoader(), $composer->getLoop()->getHttpDownloader());
        $composer->getRepositoryManager()->addRepository($pluginRepository);

        $matomoInstaller = new MatomoInstaller($cache, $io, $composer);
        $composer->getInstallationManager()->addInstaller($matomoInstaller);

        $downloader = $composer->getLoop()->getHttpDownloader();
        $dispatcher = $composer->getEventDispatcher();
        $process = $composer->getLoop()->getProcessExecutor();
        $downloader = new MatomoPluginDownloader($io, $config, $downloader, $dispatcher, $cache, null, $process);
        $composer->getDownloadManager()->setDownloader('mpl-plugin', $downloader);

        $composer->getEventDispatcher()->addSubscriber(new MatomoEventSubscriber());
    }

    public function deactivate(Composer $composer, IOInterface $io) {}
    public function uninstall(Composer $composer, IOInterface $io) {}

    /**
     * @param IOInterface $io
     * @param \Composer\Config $config
     * @return Cache
     */
    protected function createCache(IOInterface $io, \Composer\Config $config): Cache
    {
        return new Cache($io, $config->get('cache-repo-dir') . '/mpl', 'a-z0-9.$~_');
    }
}
