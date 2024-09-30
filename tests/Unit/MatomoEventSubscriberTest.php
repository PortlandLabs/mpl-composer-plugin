<?php

use Composer\Script\ScriptEvents;

it('subscribes to the expected events', function () {
    expect(\PortlandLabs\MatomoMarketplacePlugin\MatomoEventSubscriber::getSubscribedEvents())
        ->sequence(fn($value) => $value->toBe('reinstallMissingPlugins'))
        ->toHaveCount(2)
        ->toHaveKeys([ScriptEvents::POST_INSTALL_CMD, ScriptEvents::POST_UPDATE_CMD])
    ;
});
