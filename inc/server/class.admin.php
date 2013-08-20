<?php
class BEA_CSF_Server_Admin {
	const admin_slug = 'bea-css-settings';
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Register hooks
		add_action( 'network_admin_menu', array(__CLASS__, 'network_admin_menu'), 9 );
		add_action( 'admin_init', array(__CLASS__, 'admin_init') );
		add_action( 'admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts') );
		
		// Ajax Actions
		add_action( 'wp_ajax_'.'cps_getTermsList', array( __CLASS__, 'ajax_get_terms_list' ) );
		add_action( 'wp_ajax_'.'cps_UpdateTerm', array( __CLASS__, 'ajax_update_term' ) );
		
		add_action( 'wp_ajax_'.'cps_getPostsList', array( __CLASS__, 'ajax_get_posts_list' ) );
		add_action( 'wp_ajax_'.'cps_UpdatePost', array( __CLASS__, 'ajax_update_post' ) );
	}

	public static function admin_enqueue_scripts( $hook_suffix = '' ) {
		if( isset( $hook_suffix ) && $hook_suffix == 'settings_page_'.self::admin_slug ) {
			wp_enqueue_script( 'bea-css-jquery-ui',  BEA_CSF_URL.'/ressources/js/jquery-ui-1.8.16.custom.min.js', array('jquery'), '1.8.16' );
			wp_enqueue_script( 'bea-css-admin',  BEA_CSF_URL.'/ressources/js/bea-css-admin.js', array( 'jquery', 'bea-css-jquery-ui' ), BEA_CSF_VERSION, true );
			wp_enqueue_style( 'bea-css-jquery-ui',  BEA_CSF_URL.'/ressources/css/smoothness/jquery-ui-1.8.16.custom.css', array(), '1.8.16' );
			wp_enqueue_style( 'bea-css-admin',  BEA_CSF_URL.'/ressources/css/bea-css-admin.css', array(), BEA_CSF_VERSION );
		}
	}
	/**
	 * Add settings menu page
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function network_admin_menu() {
		add_submenu_page( 'settings.php', __('Content Sync', BEA_CSF_LOCALE), __('Content Sync', BEA_CSF_LOCALE), 'manage_options', self::admin_slug, array( __CLASS__, 'page_manage' ) );
	}

	/**
	 * Display options on admin
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function page_manage() {
		global $wpdb;
		
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		
		// Get current sum
		$current_sum = self::get_local_sum();
		
		// Get blogs
		$blogs = $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id ASC", $wpdb->siteid), ARRAY_A );

		// Display message
		settings_errors(BEA_CSF_LOCALE);
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e("Content Sync: Settings", BEA_CSF_LOCALE); ?></h2>
			
			<p></p>
			<div id="col-container">
				<form action="" method="post">
					<table class="widefat tag fixed" cellspacing="0">
						<thead>
							<tr>
								<th scope="col" class="manage-column column-name"><?php _e('Website', BEA_CSF_LOCALE); ?></th>
								<th scope="col" style="width:5%;text-align: center;"><?php _e('Master', BEA_CSF_LOCALE); ?></th>
								<th scope="col" style="width:5%;text-align: center;"><?php _e('Client', BEA_CSF_LOCALE); ?></th>
								<th scope="col" style="width:5%;text-align: center;"><?php _e('State', BEA_CSF_LOCALE); ?></th>
							</tr>
						</thead>
						<tbody id="the-list" class="list:clients">
							<?php
							if ( $blogs == false || empty($blogs) ) :
								echo '<tr><td colspan="2">'.__('No blogs.', BEA_CSF_LOCALE).'</td></tr>';
							else :
								$class = 'alternate';
								$i = 0;
								foreach( $blogs as $blog ) :
									$i++;
									$class = ( $class == 'alternate' ) ? '' : 'alternate';
									?>
									<tr data-blog-id="<?php echo $blog['blog_id']; ?>" id="blog-<?php echo $blog['blog_id']; ?>" class="<?php echo $class; ?>">
										<td class="name column-name">
											<strong><?php echo esc_html($blog['domain'].$blog['path']); ?></strong>
											<br />
											<div class="row-actions">
												<?php if ( isset($current_options['clients']) && in_array($blog['blog_id'], $current_options['clients']) ) : ?>
												<span class="edit"><a class="cps-resync" href="<?php echo network_admin_url( 'settings.php?page='.self::admin_slug ); ?>&amp;action=resync&amp;blog_id=<?php echo esc_attr($blog['blog_id']); ?>">Resync</a> | </span>
												<span class="edit"><a class="cps-flush" href="<?php echo wp_nonce_url(network_admin_url( 'settings.php?page='.self::admin_slug ).'&amp;action=flush&amp;blog_id='. esc_attr($blog['blog_id']), 'flush-client-'.$blog['blog_id']); ?>">Flush</a></span>
												<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'resync-client-'.$blog['blog_id'] ); ?>" />
												<?php endif; ?>
											</div>
											<div class="progressbar" ></div>
										</td>
										<td style="text-align: center;"><input type="radio" name="master" value="<?php echo $blog['blog_id']; ?>" <?php checked( $blog['blog_id'], $current_options['master'] ); ?> /></td>
										<td style="text-align: center;"><input type="checkbox" name="client[]" value="<?php echo $blog['blog_id']; ?>" <?php checked(true, in_array($blog['blog_id'], $current_options['clients'])); ?> /></td>
										<td style="text-align: center;">
											<?php
											if ( isset($current_options['clients']) && in_array($blog['blog_id'], $current_options['clients']) ) {
												echo self::check_client_sum( $blog['blog_id'], $current_sum );
											}
											?>	
										</td>
									</tr>
								<?php
								endforeach;
							endif;
							?>
						</tbody>
					</table>
				
					<p class="submit">
						<?php wp_nonce_field('update-bea-csf-settings'); ?>
						<input type="submit" class="button-primary" name="update-bea-csf-settings" value="<?php _e('Save settings', BEA_CSF_LOCALE); ?>" />
						<input type="submit" class="button delete" name="flush-all-bea-csf-settings" value="<?php _e('Flush all blogs', BEA_CSF_LOCALE); ?>" onclick="if ( confirm('<?php echo esc_js('Are you sure ?', BEA_CSF_LOCALE); ?>') ) return true; return false;" />
					</p>
				</form>
				
				<div class="sync_messages"></div>
			</div><!-- /col-container -->
		</div>
		<?php
		return true;
	}
	
	/**
	 * Check for update clients list
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function admin_init() {
		if ( isset($_POST['update-bea-csf-settings']) ) { // Save
		
			check_admin_referer( 'update-bea-csf-settings' );
		
			$option = array('master' => 0, 'clients' => array());
			
			$option['master']  = (isset($_POST['master'])) ? $_POST['master'] : 0;
			$option['clients'] = (isset($_POST['client'])) ? (array) $_POST['client'] : array();
			
			// Remove master from clients
			if ( ($pos = array_search($option['master'], $option['clients'])) !== false ) {
				unset($option['clients'][$pos]);
			}
			
			update_site_option( BEA_CSF_OPTION, $option );
			
		} elseif( isset($_GET['action']) && $_GET['action'] == 'flush' && isset($_GET['blog_id']) && (int) $_GET['blog_id'] > 0 ) { // Resync
			
			check_admin_referer( 'flush-client-'.urlencode($_GET['blog_id']) );
			
			// Get current options
			$current_options = get_site_option( BEA_CSF_OPTION );
			
			// URL Exist on DB ?
			if ( !in_array($_GET['blog_id'], $current_options['clients']) ) {
				add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __('This blog ID are not a client... Tcheater ?', BEA_CSF_LOCALE), 'error' );
			} else {
				self::flush_client( $_GET['blog_id'] );
				add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __('Blog flushed with success.', BEA_CSF_LOCALE), 'updated' );
			}
		} elseif ( isset($_POST['flush-all-bea-csf-settings']) ) { // Flush all
			
			check_admin_referer( 'update-bea-csf-settings' );
			
			// Get current options
			$current_options = get_site_option( BEA_CSF_OPTION );
			foreach( (array) $current_options['clients'] as $blog_id ) {
				self::flush_client( $blog_id, true );
			}
			
			add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __('All blogs flushed with success.', BEA_CSF_LOCALE), 'updated' );
		
		}
		
		return true;
	}
	
	/**
	 * Calcul SUM MD5 for all content to sync, use IDs and hash !
	 */
	public static function get_local_sum() {
		global $wpdb;
		
		// Post types objects
		$objects = $wpdb->get_col( "
			SELECT ID 
			FROM $wpdb->posts 
			WHERE post_type IN ('".implode("', '", BEA_CSF_Server_Client::get_post_types())."') 
			AND post_status = 'publish' 
			AND ID NOT IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'exclude_from_sync' AND meta_value = '1' )
			ORDER BY post_parent ASC
		" );
		
		// Terms objects
		$terms = get_terms( BEA_CSF_Server_Client::get_taxonomies(), array('hide_empty' => false, 'fields' => 'all') );
		
		// Keep only ID !
		$term_ids = array();
		foreach( $terms as $term ) {
			$term_ids[] = $term->term_taxonomy_id;
		}
		
		return md5( implode('', $objects) . implode('', $term_ids) );
	}

	/**
	 * Get SUM for a client
	 */
	public static function check_client_sum( $blog_id = 0, $master_sum = '' ) {
		switch_to_blog($blog_id);
		$blog_sum = BEA_CSF_Client_Base::integrity();
		restore_current_blog();
		
		// Test SUM ?
		if( $blog_sum != $master_sum ) {
			echo __('KO', BEA_CSF_LOCALE);
		} else {
			echo __('OK', BEA_CSF_LOCALE);
		}
	}
	
	/**
	 * Flush client datas
	 */
	public static function flush_client( $blog_id = 0, $silent = false ) {
		switch_to_blog($blog_id);
		$result = BEA_CSF_Client_Base::flush();
		restore_current_blog();
		
		// Client is valid ?
		if ( $result == false && $silent == false ) {
			add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __('Nothing to flush for this client !', BEA_CSF_LOCALE), 'error' );
		} elseif ( $silent == false ) {
			add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __('Client flushed with success !', BEA_CSF_LOCALE), 'updated' );
		}
	}
	
	/***** AJAX Features ******/
	public static function check_ajax_nonce() {
		if( !wp_verify_nonce( $_POST['nonce'],'resync-client-'. $_POST['blog_id'] ) ) {
			echo json_encode( array() );
			die();
		}
	}

	public static function ajax_get_terms_list() {
		header( 'Content-type: application/jsonrequest' );
		self::check_ajax_nonce();
		
		// init array for output
		$output = array();
		
		// Get objects
		$terms = get_terms( BEA_CSF_Server_Client::get_taxonomies(), array('hide_empty' => false) );
		foreach( $terms as $term ) {
			$output[] = array( 't_id' => $term->term_id ,'tt_id' => $term->term_taxonomy_id, 'taxonomy' => $term->taxonomy );
		}
		
		echo json_encode( $output );
		exit;
	}

	public static function ajax_update_term() {
		header( 'Content-type: application/jsonrequest' );
		self::check_ajax_nonce();
		
		// Check params
		if( !isset( $_POST['blog_id'] ) || !isset( $_POST['tt_id'] ) || !isset( $_POST['term_id'] ) || absint( $_POST['tt_id'] ) == 0 || absint( $_POST['term_id'] ) == 0 || !isset( $_POST['taxonomy'] ) ) {
			echo json_encode( array( 'status' => 'error', 'message' => 'Missing tt_id or term_id' ) );
			exit;
		}
		
		$response = BEA_CSF_Server_Taxonomy::merge_term( $_POST['term_id'], $_POST['tt_id'], $_POST['taxonomy'], (int) $_POST['blog_id'] );
		if( is_numeric( $response ) ) {
			$output = array( 'status' => 'success', 'message' => 'Success' );
		} else {
			if ( is_wp_error($response) ) {
				$output = array( 'status' => 'error', 'message' => $response->get_error_message() );
			} else {
				$output = array( 'status' => 'error', 'message' => 'An unidentified error' );
			}
		}
		
		echo json_encode( $output );
		exit;
	}
	
	public static function ajax_get_posts_list(){
		global $wpdb;
		
		header( 'Content-type: application/jsonrequest' );
		self::check_ajax_nonce();
		
		// init array for output
		$output = array();
		
		// Get objects
		$objects = $wpdb->get_col( $wpdb->prepare( "
			SELECT ID 
			FROM $wpdb->posts 
			WHERE post_type IN ('".implode("', '", BEA_CSF_Server_Client::get_post_types())."') 
			AND post_status = 'publish'
			AND ID NOT IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'exclude_from_sync' AND meta_value = '1' )
			ORDER BY post_parent ASC
		" ) );
		foreach( $objects as $object_id ) {
			$output[] = array( 'post_id' => $object_id );
		}
		
		echo json_encode( $output );
		exit;
	}
	
	public static function ajax_update_post(){
		header( 'Content-type: application/jsonrequest' );
		self::check_ajax_nonce();
		
		// Check params
		if( !isset( $_POST['blog_id'] ) || !isset( $_POST['post_id'] ) || absint( $_POST['post_id'] ) == 0 ) {
			echo json_encode( array( 'status' => 'error', 'message' => 'Missing post_id or site url' ) );
			exit;
		}

		$response = BEA_CSF_Server_PostType::wp_insert_post( $_POST['post_id'], null, (int) $_POST['blog_id'] );
		if( is_numeric( $response ) ) {
			$output = array( 'status' => 'success', 'message' => 'Sucess' );
		} else {
			if ( is_wp_error($response) ) {
				$output = array( 'status' => 'error', 'message' => $response->get_error_message() );
			} else {
				$output = array( 'status' => 'error', 'message' => 'An unidentified error' );
			}
		}
		
		echo json_encode( $output );
		exit;
	}
}