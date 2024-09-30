<?php

use Composer\Cache;
use Composer\Config;
use Composer\IO\BufferIO;
use Composer\Package\Package;
use Composer\PartialComposer;
use PortlandLabs\MatomoMarketplacePlugin\MatomoInstaller;

beforeEach(function (): void {
    $this->io = new BufferIO();
    $this->config = new Config();
    $this->composer = new PartialComposer();
    $this->composer->setConfig($this->config);
    $this->cache = new Cache($this->io, 'test-cache', 'a-z0-9._');
    $this->installer = new MatomoInstaller($this->cache, $this->io, $this->composer);
});

it('supports our custom types', function (string $type, bool $supported): void {
    expect($this->installer->supports($type))->toBe($supported);
})->with([
    ['mpl-matomo', true],
    ['mpl-plugin', true],
    ['mpl-theme', true],
    ['mpl-foo', false],
    ['library', false],
]);

it('provides the correct paths', function (string $type, string $expect): void {
    $pkg = new Package('foo', '1.0.0.0', '1');
    $pkg->setType($type);
    $cwd = getcwd();
    $path = $this->installer->getInstallPath($pkg);
    expect($path)->toStartWith($cwd);
    $path = substr($path, strlen($cwd) + 1);
    expect($path)->toBe($expect);
})->with([
    ['unsupported-type', 'vendor/foo'],
    ['mpl-plugin', 'mpl-matomo/plugins/foo'],
    ['mpl-theme', 'mpl-matomo/plugins/foo'],
    ['mpl-matomo', 'mpl-matomo'],
]);
