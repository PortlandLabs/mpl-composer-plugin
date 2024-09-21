<?php

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Cache;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Plugin\PluginInterface;
use Composer\Repository\VcsRepository;

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

        /**
         * public function __construct(IOInterface $io, Config $config, HttpDownloader $httpDownloader, ?EventDispatcher $eventDispatcher = null, ?Cache $cache = null, ?Filesystem $filesystem = null, ?ProcessExecutor $process = null)
 */
        $downloader = $composer->getLoop()->getHttpDownloader();
        $dispatcher = $composer->getEventDispatcher();
        $process = $composer->getLoop()->getProcessExecutor();
        $downloader = new MatomoPluginDownloader($io, $config, $downloader, $dispatcher, $cache, null, $process);
        $composer->getDownloadManager()->setDownloader('mpl-plugin', $downloader);

        $composer->getEventDispatcher()->addSubscriber(new MatomoEventSubscriber());
    }

    public function deactivate(Composer $composer, IOInterface $io) {}
    public function uninstall(Composer $composer, IOInterface $io) {}
}
