<?php

namespace PortlandLabs\MatomoMarketplacePlugin;

use Composer\Config;

class Util
{
    /**
     * @param string $boundary
     * @param array<string, string|string[]> $data
     * @return string
     */
    public static function multipartBody(string $boundary, array $data): string
    {
        $body = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $body = [
                    ...$body,
                    ...array_map(fn($i) => Util::multipartBlock($key . '[]', $i), $value),
                ];
            } else {
                $body[] = Util::multipartBlock($key, $value);
            }
        }

        return "--{$boundary}\n" . implode("\n--{$boundary}\n", $body) . "\n--{$boundary}--";
    }

    private static function multipartBlock(string $key, string $value): string
    {
        return <<<BODY
            Content-Disposition: form-data; name="{$key}"
            
            {$value}
            BODY;
    }

    /**
     * @param Config $config
     * @return array{http: array{method: string, header?: string[], content?: string}}
     */
    public static function authOptions(Config $config): array
    {
        $boundary = '----MplBoundaryW3XDKIM3ZOBE9SKYHINXT5QO';
        $result = ['http' => ['method' => 'POST']];

        $token = $config->get('bearer')['mpl'] ?? null;
        if ($token !== null) {
            $result['http']['header'] = ['Content-Type: multipart/form-data; boundary=' . $boundary];
            $result['http']['content'] = self::multipartBody($boundary, ['access_token' => $token]);
        }

        return $result;
    }
}
