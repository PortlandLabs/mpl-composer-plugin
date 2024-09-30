<?php

use Pest\Expectation;
use PortlandLabs\MatomoMarketplacePlugin\Util;

covers(Util::class);

it('authenticates requests properly', function (): void {

    $token = bin2hex(random_bytes(16));
    $config = $this->createStub(\Composer\Config::class);
    $config->method('get')->willReturnMap([['bearer', ['mpl' => $token]]]);

    $options = Util::authOptions($config);
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

it('handles empty tokens', function (): void {
    $config = $this->createStub(\Composer\Config::class);
    $config->method('get')->willReturnMap([['bearer', []]]);

    expect(Util::authOptions($config))->toBe(['http' => ['method' => 'POST']]);
});

function parse_multipart(string $boundary, string $body): array
{
    if (!str_ends_with($body, "--{$boundary}--") || !str_starts_with($body, "--{$boundary}")) {
        return [];
    }

    $boundaryLen = strlen($boundary);
    $body = trim(substr($body, $boundaryLen + 2, -1 * ($boundaryLen + 4)));

    $result = [];
    $segments = array_map(trim(...), explode("--{$boundary}", $body));
    foreach ($segments as $segment) {
        [$keySet, $value] = explode("\n\n", $segment, 2);
        $key = preg_match('/name="(.+)"$/', $keySet, $match);
        $result[] = [$match[1], $value];
    }
    return $result;
}

it('builds multipart bodies properly', function (array $payload, callable $expect): void {
    $boundary = bin2hex(random_bytes(16));
    $expect(expect(parse_multipart($boundary, Util::multipartBody($boundary, $payload))));
})->with([
    [
        ['foo' => ['a', 'b'], 'bar' => 'test', 'baz' => ['123', '456']],
        fn(Expectation $expect) => $expect
            ->toHaveCount(5)
            ->{0}->toBe(['foo[]', 'a'])
            ->{1}->toBe(['foo[]', 'b'])
            ->{2}->toBe(['bar', 'test'])
            ->{3}->toBe(['baz[]', '123'])
            ->{4}->toBe(['baz[]', '456']),
    ],
]);

it('loads license from env', function (string $name) {
    try {
        $config = $this->createStub(\Composer\Config::class);
        expect(Util::authOptions($config)['http']['content'] ?? null)
            ->toBe(null);

        // Ensure `_ENV` works
        $token = bin2hex(random_bytes(16));
        $_ENV[$name] = $token;
        expect(Util::authOptions($config)['http']['content'] ?? null)
            ->toContain($token);
        unset($_ENV[$name]);

        // Ensure putenv works
        $token = bin2hex(random_bytes(16));
        putenv("{$name}={$token}");
        expect(Util::authOptions($config)['http']['content'] ?? null)
            ->toContain($token);
    } finally {
        putenv($name . '=');
        unset($_ENV[$name]);
    }
})->with(['MATOMO_TOKEN', 'MPL_TOKEN']);
