<?php

declare(strict_types=1);

namespace PortlandLabs\MatomoMarketplacePlugin\Model;

enum MatomoPluginType
{
    case FreeTheme;
    case FreePlugin;
    case PaidTheme;
    case PaidPlugin;

    public static function create(bool $isTheme, bool $isPaid): MatomoPluginType
    {
        return match ($isTheme) {
            true => $isPaid ? self::PaidTheme : self::FreeTheme,
            false => $isPaid ? self::PaidPlugin : self::FreePlugin,
        };
    }

    public function composerType(): string
    {
        return match ($this) {
            self::FreeTheme, self::PaidTheme => 'mpl-theme',
            self::FreePlugin, self::PaidPlugin => 'mpl-plugin',
        };
    }

    /**
     * @return string[]
     */
    public function keywords(): array
    {
        $type = match ($this) {
            self::FreeTheme, self::PaidTheme => 'theme',
            default => 'plugin',
        };
        $cost = match ($this) {
            self::FreeTheme, self::FreePlugin => 'free',
            default => 'paid',
        };
        return ["mpl-{$cost}", "mpl-{$cost}-{$type}"];
    }
}
