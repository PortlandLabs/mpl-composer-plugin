<?php


it('installs correctly', function () {
    echo "Integration tests require a valid matomo license capable of installing AbTesting 5.0.0";

    $baseDir = __DIR__ . '/../../example';
    $fs = new \Composer\Util\Filesystem();
    $fs->remove($baseDir . '/vendor');
    $fs->remove($baseDir . '/mpl-matomo');
    expect($baseDir . '/vendor')->not->toBeFile('Unable to delete vendor directory');
    expect($baseDir . '/mpl-matomo')->not->toBeFile('Unable to delete mpl-matomo directory');

    $install = new Symfony\Component\Process\Process(['composer', 'install'], $baseDir);
    $install->run(function ($type, $buffer) {
        echo "[$type] $buffer \n";
    });

    expect($install)->toBe(0)
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
