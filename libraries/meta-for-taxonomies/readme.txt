=== Meta for taxonomies ===
Contributors: momo360modena
Donate link: http://www.beapi.fr/donate/
Tags: tags, taxonomies, custom taxonomies, termmeta, meta, term meta, taxonomy, meta taxonomy
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 1.1.5

Metadatas is attached to taxonomy context and not terms, this way allow to have metas different for the same term on 2 different taxonomies !

== Description ==

Add meta for any taxonomies. 
Meta is attached to taxonomy context and not terms, this way allow to have metas different for the same term on 2 different taxonomies. 
**It's the better implementation for this feature.**

This plugin don't any interface on WordPress ! Only somes methods for developpers.

This plugin propose many functions for terms :

* add_term_meta( $taxonomy = '', $term_id = 0, $meta_key = '', $meta_value = '', $unique = false )
* delete_term_meta( $taxonomy = '', $term_id = 0, $meta_key = '', $meta_value = '')
* get_term_meta( $taxonomy = '', $term_id = 0, $meta_key = '', $single = false )
* update_term_meta( $taxonomy = '', $term_id = 0, $meta_key, $meta_value, $prev_value = '' )
	
And many others functions term taxonomy context :

* add_term_taxonomy_meta( $term_taxonomy_id = 0, $meta_key = '', $meta_value = '', $unique = false )
* delete_term_taxonomy_meta( $term_taxonomy_id = 0, $meta_key = '', $meta_value = '')
* get_term_taxonomy_meta( $term_taxonomy_id = 0, $meta_key = '', $single = false )
* update_term_taxonomy_meta( $term_taxonomy_id = 0, $meta_key, $meta_value, $prev_value = '' )
	
And many others...
	
For full info go the [Meta for taxonomies](http://redmine.beapi.fr/projects/show/meta-for-taxonomies) page.

== Installation ==

1. Download, unzip and upload to your WordPress plugins directory
2. Activate the plugin within you WordPress Administration Backend
3. Develop your plugin for used it !

== Changelog ==

* 1.1.5
	* Fix bug with hook delation, use right name field "term_taxo_id"
* 1.1.4
	* Add tables in var class WPDB for multisite compatibility (thanks njuen)
* 1.1.3
	* Improve format readme.txt
	* Check compatibility WP 3.2
* 1.1.2
	* Fix a fucking bug with the meta key... (bis)
* 1.1.1
	* Remove a conflict function with Simple Taxonomy
* 1.1
	* Fix a fucking bug with the meta key...
* 1.0.0
	* Initial version
	
== Frequently Asked Questions ==

== Screenshots ==

== Upgrade Notice ==