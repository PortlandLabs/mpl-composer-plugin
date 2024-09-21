<?php

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Downloader\ZipDownloader;
use Composer\Package\PackageInterface;
use React\Promise\PromiseInterface;

class MatomoPluginDownloader extends ZipDownloader
{
    public function download(
        PackageInterface $package,
        string $path,
        ?PackageInterface $prevPackage = null,
        bool $output = true,
    ): PromiseInterface {
        $type = $package->getType();
        $options = $package->getTransportOptions();

        // Add our transport options to plugins that match our type
        if ($type === 'mpl-plugin' || $type === 'mpl-theme') {
            $package->setTransportOptions(array_merge_recursive($options, Util::authOptions($this->config)));
        }

        return parent::download($package, $path, $prevPackage, $output)->then(fn() => $package->setTransportOptions($options));
    }
}
