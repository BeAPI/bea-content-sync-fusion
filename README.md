<a href="https://beapi.fr">![Be API Github Banner](.wordpress.org/banner-github.png)</a>

# BEA - Content Sync Fusion

Synchronize contents across your Multisite.

# How ? 

# Requirements

- [WordPress](https://wordpress.org/) 4.6.x+ in order to use `WP_Site_Query`.
- WordPress should be installed as [multisite](https://codex.wordpress.org/Create_A_Network).
- Tested up to 4.9.x. 

# Installation

## WordPress

Installation should be managed from Network Area.

- Download and install using the built-in WordPress plugin installer.
- Network activate in the "Plugins" area of the network-admin of the main site of your installation (phew!)
- Optionally drop the entire `bea-content-sync-fusion` directory into mu-plugins.
- Configure you site's contents synchronizations.

## [Composer](http://composer.rarst.net/)

- Add repository source : `{ "type": "vcs", "url": "https://github.com/BeAPI/bea-content-sync-fusion" }`.
- Include `"bea/bea-content-sync-fusion": "dev-master"` in your composer file for last master's commits or a tag released.
- Configure you site's contents synchronizations.

# What ?

## Features

### Third Party Support

This plugin has third party support with following plugins :

* [BEA - Media Analytics](https://wordpress.org/plugins/bea-media-analytics/)
* [Post Types Order](https://fr.wordpress.org/plugins/post-types-order/)

## More features to come

## Next Roadmap

## Contributing

Please refer to the [contributing guidelines](.github/CONTRIBUTING.md) to increase the chance of your pull request to be merged and/or receive the best support for your issue.

### Issues & features request / proposal

If you identify any errors or have an idea for improving the plugin, feel free to open an [issue](../../issues/new). Please provide as much info as needed in order to help us resolving / approve your request.

### Translation request / proposal

If you want to translate BEA - Content Sync Fusion, the best way is to use the official way :
[WordPress.org GlotPress](https://translate.wordpress.org/projects/wp-plugins/bea-content-sync-fusion).

You can, of course, just [create a pull request](../../compare) to our repository if you already done the translation.

## For developers

### WP-Cli

# Who ?

Created by [Be API](https://beapi.fr), the French WordPress leader agency since 2009. Based in Paris, we are more than 30 people and always [hiring](https://beapi.workable.com) some fun and talented guys. So we will be pleased to work with you.

This plugin is only maintained, which means we do not guarantee some free support. Consider reporting an [issue](#issues--features-request--proposal) and be patient. 

If you really like what we do or want to thank us for our quick work, feel free to [donate](https://www.paypal.me/BeAPI) as much as you want / can, even 1€ is a great gift for buying cofee :)

## License

BEA - Media Analytics is licensed under the [GPLv3 or later](LICENSE.md).