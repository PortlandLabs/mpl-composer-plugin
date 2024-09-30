<?php

declare(strict_types=1);

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Downloader\ZipDownloader;
use Composer\Package\PackageInterface;
use React\Promise\PromiseInterface;

final class MatomoPluginDownloader extends ZipDownloader
{
    /**
     * @param callable():PromiseInterface<string|void|null>|null $downloadHandler
     */
    #[\Override]
    public function download(
        PackageInterface $package,
        string $path,
        ?PackageInterface $prevPackage = null,
        bool $output = true,
        ?callable $downloadHandler = null,
    ): PromiseInterface {
        $type = $package->getType();
        $finally = null;

        // Add our transport options to plugins that match our type
        if ($type === 'mpl-plugin' || $type === 'mpl-theme') {
            $options = $package->getTransportOptions();
            $package->setTransportOptions(array_merge_recursive($options, Util::authOptions($this->config)));
            $finally = fn() => $package->setTransportOptions($options);
        }

        $download = $downloadHandler ? $downloadHandler() : parent::download($package, $path, $prevPackage, $output);
        return $finally === null ? $download : $download->finally($finally);
    }
}
