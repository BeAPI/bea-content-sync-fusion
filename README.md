BEA Content Sync Fusion
=======================

Manage content synchronisation across a WordPress multisite.

## Requirements

* WordPress should be installed as [multisite](https://codex.wordpress.org/Create_A_Network).
* Require at least WordPress 4.6.x, in order to use the `WP_Site_Query`.

## Compatibility

Compatible up to WordPress 4.9.x

### Third plugins compatibility

 * [Post Types Order](https://wordpress.org/plugins/post-types-order/)
 * [Advanced Custom Fields](https://www.advancedcustomfields.com/)
 * [The Events Calendar Family](https://theeventscalendar.com/)
 * [Revisionize](https://wordpress.org/plugins/revisionize/)
 * [Multisite Clone Duplicator](https://wordpress.org/plugins/multisite-clone-duplicator/)
 
## Changelog

### 3.4.0 - End september 2018

* Feature: A full refactoring for the bash script to be use with cronjob
* **MAJOR Bugfix**: stop to delete emitter file when media is deleted from a receiver
* Bugfix: allow DB schema change with WP-CLI
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
