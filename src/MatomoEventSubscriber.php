<?php

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\SyncHelper;

class MatomoEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'reinstallMissingPlugins',
            ScriptEvents::POST_UPDATE_CMD => 'reinstallMissingPlugins',
        ];
    }

    public function reinstallMissingPlugins(Event $event): void
    {
        $composer = $event->getComposer();
        $locker = $composer->getLocker();
        $installer = $composer->getInstallationManager();

        $missing = [];
        foreach ($locker->getLockedRepository($event->isDevMode())->getPackages() as $lockedPackage) {
            $name = $lockedPackage->getName();
            if ($name === 'mpl/matomo' || $name === 'mpl/composer-plugin' || !str_starts_with($name, 'mpl/')) {
                continue;
            }

            $metadata = $lockedPackage->getExtra()['mpl'] ?? [];
            $pluginName = (string) ($metadata['name'] ?? '');
            if ($pluginName === '') {
                continue;
            }

            $path = $installer->getInstallPath($lockedPackage);
            if ($path === null || (file_exists($path) && (!is_link($path) || file_exists((string) readlink($path))))) {
                continue;
            }

            $missing[$lockedPackage->getName()] = [$lockedPackage, $path];
        }

        if ($missing) {
            $event->getIO()->write('<info>Installing missing Matomo plugins</info>');
        }

        array_map(fn(array $data) => $this->reinstallMissingPlugin($composer, ...$data), $missing);
    }


    private function reinstallMissingPlugin(Composer $composer, PackageInterface $lockedPackage, string $path): void
    {
        SyncHelper::downloadAndInstallPackageSync(
            $composer->getLoop(),
            $composer->getDownloadManager(),
            $path,
            $lockedPackage,
        );
    }
}
