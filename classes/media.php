<?php

class BEA_CSF_Media {
    static $blogs_wp_upload_dir = array();

    public function __construct() {
        // Force site/id/ for the medias
        add_filter( 'site_option_' . 'ms_files_rewriting', '__return_false', 99999999 );

        // Fix URL on many WP cases
        add_filter( 'wp_get_attachment_url', array( __CLASS__, 'wp_get_attachment_url' ), 99999999, 2 );
        add_filter( 'get_attached_file', array( __CLASS__, 'get_attached_file' ), 99999999, 2 );
        add_filter( 'wp_calculate_image_srcset', array( __CLASS__, 'wp_calculate_image_srcset' ), 99999999, 5 );
    }

    /**
     * Fixed URL for emitter base_url for srcset thumbs !
     *
     * @param array $sources
     * @param array$size_array
     * @param $image_src
     * @param $image_meta
     * @param integer $attachment_id
     * @return array
     */
    public static function wp_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id  ) {
        $external = BEA_CSF_Relations::current_object_is_synchronized( 'attachment', get_current_blog_id(), $attachment_id );
        if ( empty( $external ) ) {
            return $sources;
        }

        // Fix URL for each sizes
        foreach( (array) $sources as $key => &$source ) {
            $sources[$key]['url'] = self::replace_base_upload_dir( $source['url'], get_current_blog_id(), $external->emitter_blog_id );
        }

        return $sources;
    }

    /**
     * Edit attachment URL on fly for synchronized media
     *
     * @param string $url
     * @param integer $post_id
     * @return string
     */
    public static function wp_get_attachment_url( $url, $post_id ) {
        $external = BEA_CSF_Relations::current_object_is_synchronized( 'attachment', get_current_blog_id(), $post_id );
        if ( empty( $external ) ) {
            return $url;
        }

        return self::replace_base_upload_dir( $url, get_current_blog_id(), $external->emitter_blog_id );
    }

    /**
     * Filter the attachment file path
     *
     * @param string $file
     * @param integer $attachment_id
     *
     * @return string
     */
    public static function get_attached_file( $file, $attachment_id ) {;
        $external = BEA_CSF_Relations::current_object_is_synchronized( 'attachment', get_current_blog_id(), $attachment_id );
        if ( empty( $external ) ) {
            return $file;
        }

        return self::replace_base_upload_dir( $file, get_current_blog_id(), $external->emitter_blog_id, 'basedir' );
    }

    /**
     * An kind of str_replace function with helper wp_upload_dir replacement
     *
     * @param string $string
     * @param integer $receiver_blog_id
     * @param integer $emitter_blog_id
     * @param string $wp_upload_dir_key
     * @return string
     */
    public static function replace_base_upload_dir( &$string, $receiver_blog_id, $emitter_blog_id, $wp_upload_dir_key = 'baseurl' ) {
        $receiver_wp_upload_dir = self::get_blog_wp_upload_dir($receiver_blog_id);
        $emitter_wp_upload_dir  = self::get_blog_wp_upload_dir($emitter_blog_id);

        return str_replace( [ $receiver_wp_upload_dir[$wp_upload_dir_key], '/sites/1/' ], [ $emitter_wp_upload_dir[$wp_upload_dir_key], '/' ], $string );
    }

    /**
     * Get wp_upload_dir data with a blog_id param and a local cache with static variable
     *
     * @param int $blog_id
     * @return bool|array
     */
    public static function get_blog_wp_upload_dir( $blog_id = 0 ) {
        if ( 0 === $blog_id ) {
            $blog_id = get_current_blog_id();
        }

        // Check on static variables for get cache data
        if ( isset(self::$blogs_wp_upload_dir[$blog_id]) ) {
            return self::$blogs_wp_upload_dir[$blog_id];
        }

        // Need to switch for get this value for another blog
        if( $blog_id != get_current_blog_id() ) {
            switch_to_blog( $blog_id );
            self::$blogs_wp_upload_dir[$blog_id] = wp_upload_dir();
            restore_current_blog();
        } else {
            self::$blogs_wp_upload_dir[$blog_id] = wp_upload_dir();
        }

        return self::$blogs_wp_upload_dir[$blog_id];
    }
}
