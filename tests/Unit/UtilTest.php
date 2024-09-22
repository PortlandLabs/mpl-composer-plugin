<?php

it('loads authenticates requests properly', function () {
    $token = bin2hex(random_bytes(16));
    $config = $this->createStub(\Composer\Config::class);
    $config->method('get')->willReturnMap([['bearer', ['mpl' => $token]]]);

    $options = \PortlandLabs\MatomoMarketplacePlugin\Util::authOptions($config);
    $boundary = explode('=', $options['http']['header'][0] ?? '')[1] ?? null;
    expect($boundary)->not->toBeNull()
        ->and($options['http']['method'])->toBe('POST')
        ->and($options['http']['header'][0] ?? null)->toStartWith('Content-Type: multipart/form-data; boundary=')
        ->and($options['http']['content'] ?? null)->toBe(<<<MULTI
            --{$boundary}
            Content-Disposition: form-data; name="access_token"
            
            {$token}
            --{$boundary}--
            MULTI);

});
