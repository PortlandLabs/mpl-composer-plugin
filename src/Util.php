<?php

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Config;

class Util
{
    public static function multipartBody(string $boundary, array $data): string
    {
        $body = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $body = [...$body, ...array_map(fn($i) => Util::multipartBody($key . '[]', $i), $value)];
            } else {
                $body[] = Util::multipartBlock($key, $value);
            }
        }

        return "--{$boundary}\n" . implode("\n{$boundary}\n", $body) . "\n--{$boundary}--";
    }

    private static function multipartBlock(string $key, string $value): string
    {
        return <<<BODY
            Content-Disposition: form-data; name="{$key}"
            
            {$value}
            BODY;
    }

    public static function authOptions(Config $config): array
    {
        $boundary = '----MatomoBoundaryW3XDKIM3ZOBE9SKYHINXT5QO';
        return [
            'http' => [
                'method' => 'POST',
                'header' => ['Content-Type: multipart/form-data; boundary=' . $boundary],
                'content' => Util::multipartBody($boundary, ['access_token' => $config->get('mpl-auth')['plugins'] ?? '']),
            ],
        ];
    }
}
