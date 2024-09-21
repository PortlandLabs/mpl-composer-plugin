<?php

namespace PortlandLabs\MatomoMarketplacePlugin\Util;

class Http
{
    public static function multipartBody(string $boundary, array $data): string
    {
        $body = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $body = [...$body, ...array_map(fn($i) => Http::multipartBody($key . '[]', $i), $value)];
            } else {
                $body[] = Http::multipartBlock($key, $value);
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
}
