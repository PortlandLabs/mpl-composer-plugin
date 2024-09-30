<?php

use Composer\Cache;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Util\HttpDownloader;
use PortlandLabs\MatomoMarketplacePlugin\MatomoPluginDownloader;
use React\Promise\Deferred;

covers(MatomoPluginDownloader::class);

it('downloads with credentials', function (bool $output, string $type, string $method) {
    $token = bin2hex(random_bytes(16));

    $io = $this->createStub(IOInterface::class);

    $config = $this->createStub(Config::class);
    $config->method('get')->willReturnMap([
        ['bearer', ['mpl' => $token]],
    ]);

    $downloader = $this->createStub(HttpDownloader::class);
    $dispatcher = $this->createStub(EventDispatcher::class);
    $cache = $this->createStub(Cache::class);
    $fs = $this->createStub(Filesystem::class);

    $package = new Composer\Package\Package('foo', '1.0.1.0', '1.0.1');
    $package->setType($type);
    $package->setDistUrl('foo');
    $previousPackage = new Composer\Package\Package('foo', '1.0.0.0', '1.0.0');

    $test = new MatomoPluginDownloader($io, $config, $downloader, $dispatcher, $cache, $fs);

    // Try with resolve
    $deferred = new Deferred();
    $test->download($package, 'foo', $previousPackage, $output, fn() => $deferred->promise());
    expect($package->getTransportOptions()['http']['content'] ?? '')->toContain($token);

    if ($method === 'reject') {
        \React\Promise\set_rejection_handler(fn() => null);
        $deferred->reject(new \RuntimeException());
    } else {
        $deferred->resolve(null);
    }
    expect($package->getTransportOptions())->toBe([]);
})->with(
    [true, false],
    ['mpl-plugin', 'mpl-theme'],
    ['resolve', 'reject'],
);
