<?php

use PortlandLabs\MatomoMarketplacePlugin\Model\MatomoPlugin;
use PortlandLabs\MatomoMarketplacePlugin\Model\MatomoPluginType;
use PortlandLabs\MatomoMarketplacePlugin\Model\MatomoPluginVersion;

covers(MatomoPlugin::class);

it('creates as expected from different arrays', function (array $data, MatomoPluginType $type) {
    expect(MatomoPlugin::fromJsonData($data))
        ->owner->toBe($data['owner'] ?? '')
        ->name->toBe($data['name'] ?? '')
        ->description->toBe($data['description'] ?? '')
        ->homepage->toBe($data['homepage'] ?? '')
        ->keywords->toBe($data['keywords'] ?? [])
        ->isDownloadable->toBe($data['isDownloadable'] ?? false)
        ->isBundle->toBe($data['isBundle'] ?? false)
        ->versions->toHaveCount(count($data['versions'] ?? []))
        ->versions->toContainOnlyInstancesOf(MatomoPluginVersion::class)
        ->type->toBe($type)
        ->getMplHandle()->not->toMatch('/[A-Z]/')
    ;
})->with([
    [[], MatomoPluginType::FreePlugin],
    ...array_map(
        fn($i) => [$i, MatomoPluginType::create($i['isTheme'] ?? false, $i['isPaid'] ?? false)],
        iterator_to_array(simple_fuzz([
            'owner' => FuzzType::String,
            'name' => FuzzType::String,
            'description' => FuzzType::String,
            'homepage' => FuzzType::String,
            'keywords' => FuzzType::Array,
            'isDownloadable' => FuzzType::Bool,
            'isBundle' => FuzzType::Bool,
            'isPaid' => FuzzType::Bool,
            'isTheme' => FuzzType::Bool,
            'versions' => [[], [[]], [[], []]],
        ])),
    ),
]);

it('creates with versions populated', function () {
    $versions = MatomoPlugin::fromJsonData(['versions' => [
        ['name' => '1.1.1'],
        ['name' => '2.2.2'],
        ['name' => '3.3.3'],
    ]])->versions;
    expect($versions)
        ->toHaveCount(3)
        ->toContainOnlyInstancesOf(MatomoPluginVersion::class)
        ->sequence(
            fn($i) => $i->name->toBe('1.1.1'),
            fn($i) => $i->name->toBe('2.2.2'),
            fn($i) => $i->name->toBe('3.3.3'),
        )
    ;
});

it('handles real matomo api response', function (array|string $response) {
    if (is_string($response)) {
        $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /** @var array<string, MatomoPlugin> $map */
    $map = [];
    foreach ($response['plugins'] as $plugin) {
        $plugin = MatomoPlugin::fromJsonData($plugin);
        $map[$plugin->getMplHandle()] = $plugin;
    }

    expect($map['mpl/matomo-org-abtesting'])
        ->name->toBe('AbTesting')
        ->type->toBe(MatomoPluginType::PaidPlugin)
        ->versions->{0}->name->toBe('5.2.1')
        ->and($map['mpl/matomo-org-forcessl'])
        ->name->toBe('ForceSSL')
        ->type->toBe(MatomoPluginType::FreePlugin)
        ->versions->sequence(
            fn($i) => $i->name->toBe('3.0.0'),
            fn($i) => $i->name->toBe('3.0.1'),
            fn($i) => $i->name->toBe('3.0.2'),
            fn($i) => $i->name->toBe('4.0.0'),
            fn($i) => $i->name->toBe('4.0.1'),
            fn($i) => $i->name->toBe('5.0.0'),
            fn($i) => $i->name->toBe('5.0.1'),
        )
    ;
})->with([
    fn() => file_get_contents(__DIR__ . '/fixtures/real-matomo-plugin-api-response.json'),
]);
