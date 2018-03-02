=== BEA - Content Sync Fusion ===
Contributors: beapi, momo360modena, asadowski10, petitphp, maximeculea
Donate link: http://paypal.me/BeAPI
Tags: content, synchronization, multisite
Requires at least: 4.6.4
Requires php: 5.4
Tested up to: 4.9.4
Stable tag: trunk
License: GPLv3 or later
License URI: https://github.com/BeAPI/bea-content-sync-fusion/blob/master/LICENSE.md

Synchronize contents across your Multisite.

== Description ==

= Features

### Third party support

This plugin has third party support with following plugins :

- [BEA - Media Analytics](https://fr.wordpress.org/plugins/bea-media-analytics/)
- [Post Types Order](https://fr.wordpress.org/plugins/post-types-order/)

= More features to come

= Next Roadmap

= Who ?

Created by [Be API](https://beapi.fr), the French WordPress leader agency since 2009. Based in Paris, we are more than 30 people and always [hiring](https://beapi.workable.com) some fun and talented guys. So we will be pleased to work with you.

This plugin is only maintained, which means we do not guarantee some free support. Consider reporting an [issue](https://github.com/BeAPI/bea-content-sync-fusion/issues) and be patient.

To facilitate the process of submitting an issue and quicker answer, we only use Github, so don't use WP.Org support, it will not be considered.

== Installation ==

= Requirements =
- [WordPress](https://wordpress.org/) 4.6.x+ in order to use `WP_Site_Query`.
- WordPress should be installed as [multisite](https://codex.wordpress.org/Create_A_Network).
- Tested up to 4.9.x.

= WordPress =
Installation should be managed from Network Area.
- Download and install using the built-in WordPress plugin installer.
- Network activate in the "Plugins" area of the network-admin of the main site of your installation (phew!)
- Optionally drop the entire `bea-content-sync-fusion` directory into mu-plugins.
- Configure you site's contents synchronizations.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 3.1-beta - Nov/Dec 2017
- Soon :)

= 3.0.8 - 30 Oct 2017
- Feature: Allow to exclude a content for future updates, useful when restriction is deactivated.

= 3.0.7 - 20 Oct 2017
- Fix P2P synchronisation.
- Fix resync all content tools for CPT excluded from search.

= 3.0.6 - 15 Oct 2017
- Add Post Type Order addon.

= 3.0.5 - 07 Oct 2017
- Allow content resync during blog/site creation.
- Refactoring code for all CLI tools.
- Add blog widget for counter info, and add button for force sync.
- Fix media sync, use a shared folder between blogs.
- Add link action for "resync content" into sites list.
- Add button on queue network page for exec CRON (for debug usage).
- Fix restriction for attachment.
- Update POT/POT.
- Use string for i18n instead PHP constant.

= 3.0.4 - 27 Sept 2017
- Fix display admin emitters / receivers column

= 3.0.3 - 27 Sept 2017
- Fix infinite loop insertion for taxonomies.

= 3.0.2 - 5 Sept 2017
- Handle multiple networks for admin option.

= 3.0.1 - 26 July 2017
- Fix unserialised data of media after sync.
- Fix conflict with Polylang on sync terms.

= 3.0.0 - 29 June 2017
- Work only on relations table, do not use old `meta_origin_key`.
- Synchronisations are bidirectional.
- Remove old code from notifications.

= 2.0.2
- Add filter bea_csf.client.post_type.allow_bidirectional_sync to allow bidirectional synchronisation.

= 2.0.1
Fix P2P synchronisation

= 2.0.0
- Remove media synchronisation using symlink. Use shared uploads folder.
- Remove old code for old term meta API.
- Use term_id instead tt_id.

= 1.1
- Stable version using WordPress metadata API for Taxonomy.