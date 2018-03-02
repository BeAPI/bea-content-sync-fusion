## Changelog ##

## 3.1-beta - Nov/Dec 2017
* Soon :)

## 3.0.8 - 30 Oct 2017
* Feature: Allow to exclude a content for future updates, useful when restriction is deactivated.

## 3.0.7 - 20 Oct 2017
* Fix P2P synchronisation.
* Fix resync all content tools for CPT excluded from search.

## 3.0.6 - 15 Oct 2017
* Add Post Type Order addon.

## 3.0.5 - 07 Oct 2017
* Allow content resync during blog/site creation.
* Refactoring code for all CLI tools.
* Add blog widget for counter info, and add button for force sync.
* Fix media sync, use a shared folder between blogs.
* Add link action for "resync content" into sites list.
* Add button on queue network page for exec CRON (for debug usage).
* Fix restriction for attachment.
* Update POT/POT.
* Use string for i18n instead PHP constant.

## 3.0.4 - 27 Sept 2017
* Fix display admin emitters / receivers column

## 3.0.3 - 27 Sept 2017
* Fix infinite loop insertion for taxonomies.

## 3.0.2 - 5 Sept 2017
* Handle multiple networks for admin option.

## 3.0.1 - 26 July 2017
* Fix unserialised data of media after sync.
* Fix conflict with Polylang on sync terms.

## 3.0.0 - 29 June 2017
* Work only on relations table, do not use old `meta_origin_key`.
* Synchronisations are bidirectional.
* Remove old code from notifications.

## 2.0.2
* Add filter bea_csf.client.post_type.allow_bidirectional_sync to allow bidirectional synchronisation.

## 2.0.1
Fix P2P synchronisation

## 2.0.0
* Remove media synchronisation using symlink. Use shared uploads folder.
* Remove old code for old term meta API.
* Use term_id instead tt_id.

## 1.1
* Stable version using WordPress metadata API for Taxonomy.