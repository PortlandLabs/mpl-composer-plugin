# 🍁MPL🍁 Composer Plugin
Use composer to manage your matomo plugins.

## Getting Started

```shell
# Install the `mpl/composer-plugin` to enable the matomo plugin library
composer require mpl/composer-plugin

# Show available plugins
composer search mpl/
composer search mpl-free
composer search mpl-paid

# Add a plugin from the matomo package repository
# Names follow the following lowercase format: `mpl/{owner}-{name}`
composer require mpl/matomo-org-forcessl
```

## Authenticating
If you'd like to manage your paid plugins with composer, you must provide your token via the `bearer.mpl` config option

```shell
composer config --global --auth bearer.mpl {YOUR_TOKEN}
```

## FAQ
- **Q: I deleted composer.lock and my vendor directory and now I can't run composer install**
- A: `mpl/composer-plugin` use a custom repository, so it must be present for any other `mpl/` dependencies to resolve.
Try:
  - Removing any `mpl/*` `require` or `require-dev` entries other than `mpl/composer-plugin`
  - Run `composer update`
  - Add back any removed requirements.
