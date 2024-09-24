<?php

use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

test('example installs correctly', function () {
    echo "Integration tests require a valid matomo license capable of installing AbTesting 5.0.0";

    $baseDir = __DIR__ . '/../../example';
    $fs = new Filesystem();
    $fs->remove($baseDir . '/vendor');
    $fs->remove($baseDir . '/mpl-matomo');
    expect($baseDir . '/vendor')->not->toBeFile('Unable to delete vendor directory');
    expect($baseDir . '/mpl-matomo')->not->toBeFile('Unable to delete mpl-matomo directory');

    $install = new Symfony\Component\Process\Process(['composer', 'install', '--no-progress'], $baseDir);
    $buffer = '';
    $install->run(function ($type, $chunk) use (&$buffer) {
        $buffer .= "[$type] $chunk\n";
    });

    if ($install->getExitCode() !== 0) {
        echo $buffer;
    }

    expect($install->getExitCode())->toBe(0)
        // Main matomo directory exsits
        ->and($baseDir . '/mpl-matomo')->toBeDirectory()
        // Composer installed plugin exists
        ->and($baseDir . '/mpl-matomo/plugins/AbTesting')->toBeDirectory()->not->toBeLink()
        // Linked config file exists
        ->and($baseDir . '/mpl-matomo/config/common.config.ini.php')->toBeLinkedTo("../../config/common.config.ini.php")
        // Linked plugin
        ->and($baseDir . '/mpl-matomo/plugins/Foo')->toBeLinkedTo("../../plugins/Foo")
        // Composer vendor proxy exists
        ->and($baseDir . '/mpl-matomo/vendor/autoload.php')->toBeFile()
        ->andContents()->toBe(file_get_contents(__DIR__ . '/../../template/vendor/autoload.php'))
    ;
});


test('setup from scratch works', function () {
    $tmp = __DIR__ . '/../.test-cache/mpl-' . bin2hex(random_bytes(8));
    $pluginDir = $tmp . '/plugins/Test';
    $fs = new Filesystem();
    $fs->emptyDirectory($tmp);
    $fs->emptyDirectory($pluginDir);

    $steps = [
        // Initialize a composer.json file
        'echo {} > composer.json',
        // Add our project as a local path repo
        'composer config repositories.local path ../../../',
        // Set the minimum stability to dev so it will install whatever is checked out
        'composer config minimum-stability dev',
        // Prefer stable, this isn't strictly necessary
        'composer config prefer-stable true',
        // Manually allow our plugin before trying to install since we can't interact
        'composer config allow-plugins.mpl/composer-plugin true',
        // Link our plugins directory
        'composer config extra.mpl.link --json \'["plugins"]\'',
        // Install our plugin
        'composer require mpl/composer-plugin --no-interaction',
        // Install a free matomo plugin
        'composer require mpl/dominik-th-loginoidc --no-interaction',
        // Install a paid matomo plugin
        'composer require mpl/matomo-org-abtesting --no-interaction',
    ];

    try {
        foreach ($steps as $step) {
            $output = '';
            $process = Process::fromShellCommandline($step, $tmp);
            $process->run(function ($type, $chunk) use (&$output) {
                $output .= "[{$type}] {$chunk}\n";
            });

            if ($process->getExitCode() !== 0) {
                echo $output;
            }

            expect($process)->getExitCode()->toBe(0);
        }

        // Validate the result
        // Matomo itself is in place
        expect("{$tmp}/mpl-matomo/config/global.ini.php")->toBeFile()
            // Our extra.mpl.link worked
            ->and("{$tmp}/mpl-matomo/plugins/Test")->toBeLinkedTo("../../plugins/Test")
            // Our free matomo plugin was installed
            ->and("{$tmp}/mpl-matomo/plugins/LoginOIDC")->toBeDirectory()->not->toBeLink()
            ->and("{$tmp}/mpl-matomo/plugins/LoginOIDC/plugin.json")->toBeFile()
            // Out paid matomo plugin was installed
            ->and("{$tmp}/mpl-matomo/plugins/AbTesting")->toBeDirectory()->not->toBeLink()
            ->and("{$tmp}/mpl-matomo/plugins/AbTesting/plugin.json")->toBeFile();
    } finally {
        $fs->removeDirectory($tmp);
    }
})->only();
