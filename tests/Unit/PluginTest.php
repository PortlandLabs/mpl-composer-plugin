<?php

use Composer\IO\IOInterface;
use Composer\Repository\RepositoryManager;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use PortlandLabs\MatomoMarketplacePlugin\MatomoEventSubscriber;
use PortlandLabs\MatomoMarketplacePlugin\MatomoInstaller;
use PortlandLabs\MatomoMarketplacePlugin\MatomoPluginDownloader;
use PortlandLabs\MatomoMarketplacePlugin\MatomoPluginRepository;
use PortlandLabs\MatomoMarketplacePlugin\Plugin;

covers(Plugin::class);

it('registers expected stuff', function () {
    // Must register repository
    $repositoryManager = $this->creatEMock(RepositoryManager::class);
    $repositoryManager->expects($this->once())->method('addRepository')->with(new IsInstanceOf(MatomoPluginRepository::class));

    // Must register installer
    $installerManager = $this->createMock(\Composer\Installer\InstallationManager::class);
    $installerManager->expects($this->once())->method('addInstaller')->with(new IsInstanceOf(MatomoInstaller::class));

    // Must register downloader
    $downloader = $this->createMock(\Composer\Downloader\DownloadManager::class);
    $downloader->expects($this->once())->method('setDownloader')
        ->with('mpl-plugin', new IsInstanceOf(MatomoPluginDownloader::class));

    // Must register event dispatcher
    $eventDispatcher = $this->createMock(\Composer\EventDispatcher\EventDispatcher::class);
    $eventDispatcher->expects($this->once())->method('addSubscriber')
        ->with(new IsInstanceOf(MatomoEventSubscriber::class));

    $config = $this->createMock(\Composer\Config::class);
    $config->expects($this->any())->method('get')->willReturnMap([
        ['vendor-dir', 'vendor'],
        ['bin-dir', 'bin'],
        ['bin-compat', 'stable'],
    ]);
    $composer = $this->createConfiguredMock(\Composer\Composer::class, [
        'getRepositoryManager' => $repositoryManager,
        'getInstallationManager' => $installerManager,
        'getDownloadManager' => $downloader,
        'getEventDispatcher' => $eventDispatcher,
        'getConfig' => $config,
    ]);

    $io = $this->createStub(IOInterface::class);
    expect((new Plugin())->activate($composer, $io))->toBeNull();
});
