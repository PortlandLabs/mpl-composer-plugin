<?php

use PortlandLabs\MatomoMarketplacePlugin\Model\MatomoPluginVersion;

covers(MatomoPluginVersion::class);

it('builds properly from array', function ($data) {
    expect(MatomoPluginVersion::fromJsonData($data))
        ->name->toBe($data['name'] ?? '')
        ->download->toBe($data['download'] ?? '')
        ->release->toBe($data['release'] ?? '')
        ->requires->toBe($data['requires'] ?? [])
        ->license->toBe($data['license']['name'] ?? '');
})->with(array_map(
    fn($i) => [[...$i, 'license' => ['name' => $i['license'] ?? null]]],
    iterator_to_array(simple_fuzz([
        'name' => FuzzType::String,
        'download' => FuzzType::String,
        'release' => FuzzType::String,
        'requires' => FuzzType::Array,
        'license' => FuzzType::String,
    ])),
));
