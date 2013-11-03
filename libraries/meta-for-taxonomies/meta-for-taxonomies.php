<?php
/*
Plugin Name: Meta for Taxonomies
Plugin URI: http://www.beapi.fr
Description: Add table for term taxonomy meta and some methods for use it. Inspiration from core post meta.
Author: Be API
Author URI: http://beapi.fr
Version: 1.2
*/

// 1. Setup table name for term taxonomy meta
global $wpdb;
$wpdb->tables[] = 'term_taxometa';
$wpdb->term_taxometa = $wpdb->prefix . 'term_taxometa';

// 2. Library
require_once( dirname(__FILE__) . '/inc/functions.meta.php' );
require_once( dirname(__FILE__) . '/inc/functions.meta.ext.php' );
require_once( dirname(__FILE__) . '/inc/functions.meta.terms.php' );

// 3. Functions
require_once( dirname(__FILE__) . '/inc/functions.hook.php' );
require_once( dirname(__FILE__) . '/inc/functions.inc.php' );
require_once( dirname(__FILE__) . '/inc/functions.tpl.php' );

// 4. Meta API hook
register_activation_hook( __FILE__, 'install_table_termmeta' );
add_action ( 'delete_term', 'remove_meta_during_delete', 10, 3 );