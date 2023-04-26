<a href="https://beapi.fr">![Be API Github Banner](.github/banner-github.png)</a>


BEA - Content Sync Fusion
=======================

Manage content synchronisation across a WordPress multisite.

## Requirements

* WordPress should be installed as [multisite](https://codex.wordpress.org/Create_A_Network).
* Require at least WordPress 4.6.x, in order to use the `WP_Site_Query`.
* Know and understand the implementation of CRON tasks on a Linux server

## Installation

3 possibilities.

 * Take the ZIP archive proposed in the Github releases
 * Add "bea/bea-content-sync-fusion" on composer json project file
 * Take this GIT repository and call "composer install" command

## Compatibility

Compatible up to WordPress 5.x

### Third plugins compatibility

 * **[WooCommerce](https://wordpress.org/plugins/woocommerce/)**
 * [Polylang](https://wordpress.org/plugins/polylang/)
 * [Post Types Order](https://wordpress.org/plugins/post-types-order/)
 * [Advanced Custom Fields](https://www.advancedcustomfields.com/)
 * [The Events Calendar Family](https://theeventscalendar.com/)
 * [Revisionize](https://wordpress.org/plugins/revisionize/)
 * [Multisite Clone Duplicator](https://wordpress.org/plugins/multisite-clone-duplicator/)

## Who ?

Created by [Be API](https://beapi.fr), the French WordPress leader agency since 2009. Based in Paris, we are more than 30 people and always [hiring](https://beapi.workable.com) some fun and talented guys. So we will be pleased to work with you.

This plugin is only maintained, which means we do not guarantee some free support. Consider reporting an [issue](#issues--features-request--proposal) and be patient.

If you really like what we do or want to thank us for our quick work, feel free to [donate](https://www.paypal.me/BeAPI) as much as you want / can, even 1€ is a great gift for buying coffee :)

## Changelog

### 3.10.0

* Performance : Do not flush object cache after each pull of content, this needs to be manually done. The query cache layer is cleared/added on right actions.
* Added : Allow to pass non positionnal arguments to the cron query.
* Added : Add each command missing phpdocbloc.
* Added : Allow to filter the "fields" field before inserting it.

### 3.9.11

* Fixed : warning when merging taxonomies

### 3.9.10

* Fixed : clean cache when a site is deleted

### 3.9.9
* Changed: Order terms by ID when doing a resync

### 3.9.8
* Fixed: Replace comma with the AND keyword in SQL queries

### 3.9.7

* Added: Add the cast for the arguments of the method 

### 3.9.6

* Fixed: fix a bug by ensure "post_name" is set with wp_post_update() instead wp_publish_post()
* Added: object cache on relations

### 3.9.5

* Fixed: #136 When post does not exists on filter bea_csf.server.xxxx.merge
* Updating composer.json allowed plugin

### 3.9.4

* Fixed: Change the return type of `attachment_fields_to_edit` to array to match the filter type attended
* Added: Add the minimum WordPress version to plugin header

### 3.9.3

* Fixed: Correction of the syntax for the creation of the relations table. (Missing commas)

### 3.9.2

* Feature: Added synchronisation of Gutenberg ACF blocks
* Fixed: Remove double all on admin widget

### 3.9.1

* Enhancement: Add new indexes to speedup the queries on receivers and emitters queries.

### 3.9

* Feature: New "exclude_default" mode for attachments. Excludes attachments from the default synchronisation and adds a meta box to include them.
* Added:  Add "--posttype=true", "--taxonomies=true" and "--attachments=true" arguments for the flush site command
* Changed: Update of the .pot file and the FR translations
* Fixed : Fix warning on term has no view (publicly_queryable=false)
* Fixed : Fixed warning for "deletes_tag" and "taxonomy" values that are undefined.

### 3.8.2

* Added: Hook to change ID on save_post (for revisionary metabox)
* Fixed: fix a bug on delete sync term by remove relation
* Fixed: fix a bug with encode chars with get_the_title
* Fixed: fix a fatal error with attrs or inner content null for gutenberg
* Fixed: fix parent on term exist function for polylang
* Fixed: fix a bug with same terms slugs for polylang
* Fixed: fix wrong type when checking for attachment parent
* Changed: handle the alternate queue if argument passed for cronjob
* Changed: bump tools

### 3.8.1

* Fixed: fix a possible bug with nested calls to switch_to_blog()
* Fixed: fix a bug with escapging data & gurenberg (thanks @petitphp)
* Added: Add tools for testing (thanks @Rahe)

### 3.8

* Fixed: fix terms synchronization, a bug introduced on 3.0.1
* Added: Add tooling for quality (phpcs/cbf/psalm)
* Added: New algorithm for reciprocal relations, or derivated relations
* Changed: Update JavaScript libraries
* Removed: old PHP libraries
* Removed: custom flag & custom fields columns on relation object

### 3.7

* Feature: full support of Gutenberg
* Improvement: Polylang addon (slug conflict)
* Improvement: Restriction for terms

### 3.6

* Feature: add new CLI command for mirror 2 sites on network, populate relations, useful after a site duplication
* Feature: full support of Polylang
* Feature: add support of ACF group field
* Feature: add some new filter for capibility check bea_csf_taxonomy_caps / bea_csf_post_caps
* Bugfix: fix canonical feature for WPseo
* Bugfix: save db version on network ID : 1, instead 0 (not exists)
* Bugfix: fix potential loop with ACF/save_post hook on rare case

### 3.5.1 - 02 march 2020

* Feature: add Polylang addon (thanks to @asadowski10)
* Improvement: add filters to allow emitter posts lock on receiver blog
* Improvement: add filter to admin metabox

### 3.5.0 - 07 august 2019

* Feature: Add WooCommerce addon (support product, product variations)
* Change: add a column on queue table (**you must desactive AND reactive plugin on network**)
* Bugfix: fix a bug with post_parent sync 
* Bugfix: fix unwanted publication with pending status
* Bugfix: fix a bug with manual mode and gutenberg, with unwanted publication on all sites
* Bugfix: fix a bug with publish/unpublish/publish sequences

### 3.4.7 - 17 november 2018

* Bugfix: Create relation when a term already exists with the same on target

### 3.4.6 - 16 november 2018

* New: add --quantity param for WP-CLI content-sync-fusion queue pull method
* Bugfix: Manage fatal error for media sync on specific case
* Bugfix: Fix notices/warning PHP with admin restriction feature

### 3.4.5 - 3 october 2018

* Bugfix: Deleting a debug condition that checks the structure of the table all the time and therefore loss of performance ...

### 3.4.4 - 2 october 2018

* Changes: rename 2 hooks name for extend post status for publish/delete
* Improvement: improve performance for bash CRON with smart param
* New: add --smart=true param for WP-CLI resync command for improve performance by loop on only emitters blogs from synchronization settings

### 3.4.3 - 1 october 2018

* Bugfix: A bug occurred when post is delete, only the first blog work, an orphan is generated for others blogs (introduced on 3.0.1)
* Improvement: replace error message by warning message on WP-CLI commands
* Improvement: some code styling / strict tests

### 3.4.2.1 - 26 september 2018

* Bugfix: Stop to kill CRON process when an error is occurred

### 3.4.2 - 26 september 2018

* Bugfix: trash post is now functionnal (item is deleted from synchro)

### 3.4.1 - 24 september 2018

* Feature: add WP-CLI command for get URL of sites with contents synchronized (flush part)

### 3.4.0 - 20 september 2018

* Feature: A full refactoring for the bash script to be use with cronjob
* **MAJOR Bugfix**: stop to delete emitter file when media is deleted from a receiver
* Bugfix: allow DB schema change with WP-CLI
* Bugfix: priorize the synchro in this order: Terms, Attachments and after post type
* Improvement: some code styling

### 3.3.2 - September 2018

* Bugfix: Allow progressbar to be accurate for the resync command
* Bugfix: Make the synchronisation of terms with parents using the parent receiver id

### 3.3.1 - July 2018

* Feature: add WP-CLI command get queue status, stats
* Feature: add WP-CLI command for get URL of sites with contents need sync (better performance)

### 3.3 - July 2018

 * Feature: Add param for allow resync for specific receivers on WP-CLI commands
 * Feature: Allow initial sync content during site creation (async process)

### 3.2.2 - June 2018

 * Feature: Allow use hidden (not public) taxonomy

### 3.2.1 - June 2018

 * Feature: Add a constant for allow deactivate media synchro

### 3.2 - Novembre-December 2017 => April 2018
 * Feature: BREAKING, remove old bootstrap script for CRON, now use WP-CLI commands
 * Feature: SEO, add a canonical for synchronized contents to original content
 * Feature: Allow media library shared without files duplication or symlinks
 * Feature: add support for ACF plugin and support complex fields
 * Feature: add support for Revisionize plugin
 * Feature: add support for Multisite Clone Duplicator plugin
 * Feature: add support for The Events Calendar plugins (event/tickets/locations)
 * Feature: add a selector on admin for filter local or remote contents
 * Feature: add a "internal admin notes" into metabox
 * Feature: add a WP-CLI command for allow migration from old plugin version
 * Feature: add an "all" option for emitters and receivers
 * Feature: allow to exclude some ACF fields or ACF group fields or ACF repeater/flexible fields from future updates
 * Bugfix : fix index length for utf8mb4
 * Bugfix : delete relation from custom table when a post is deleted
 * Bugfix : deenqueue non-existent css on admin
 * Bugfix : Fix compat with old WP version <4.6
 * Bugfix : Fix code register synchros

### 3.0.8 - 30 Oct 2017
 * Feature: Allow to exclude a content for future updates, useful when restriction is deactivated

### 3.0.7 - 20 Oct 2017
 * Fix P2P synchronisation
 * Fix resync all content tools for CPT excluded from search

### 3.0.6 - 15 Oct 2017
 * Add Post Type Order addon.

### 3.0.5 - 07 Oct 2017
 * Allow content resync during blog/site creation
 * Refactoring code for all CLI tools
 * Add blog widget for counter info, and add button for force sync
 * Fix media sync, use a shared folder between blogs
 * Add link action for "resync content" into sites list
 * Add button on queue network page for exec CRON (for debug usage)
 * Fix restriction for attachment
 * Update POT/POT
 * Use string for i18n instead PHP constant

### 3.0.4 - 27 Sept 2017
 * Fix display admin emitters / receivers column
 
### 3.0.3 - 27 Sept 2017
 * Fix infinite loop insertion for taxonomies
 
### 3.0.2 - 5 Sept 2017
 * Handle multiple networks for admin option

### 3.0.1 - 26 July 2017
 * Fix unserialised datas of media after synch
 * Fix conflict with polylang on sync terms
 
### 3.0.0 - 29 June 2017
 * Work only on relations table, do not use old meta _origin_key
 * Synchronisations are bidirectional
 * Remove old code from notifications

### 2.0.2
 * Add filter bea_csf.client.post_type.allow_bidirectional_sync to allow bidirectional synchronisation

### 2.0.1
 * Fix P2P synchronisation

### 2.0.0
 * Remove media synchronisation using symlink. Use shared uploads folder.
 * Remove old code for old term meta API.
 * Use term_id instead tt_id.

### 1.1
 * Stable version using WordPress metadata API for Taxonomy.
