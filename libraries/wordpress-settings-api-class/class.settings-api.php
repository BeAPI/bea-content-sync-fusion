<?php
/**
 * weDevs Settings API wrapper class
 *
 * @version 0.4
 * @author Tareq Hasan <tareq@weDevs.com>
 * @link http://tareq.weDevs.com Tareq's Planet
 * @example settings-api.php How to use the class
 */
if ( ! class_exists( 'WeDevs_Settings_API' ) ) :
	class WeDevs_Settings_API {
		/**
		 * settings sections array
		 *
		 * @var array
		 */
		private $settings_sections = array();

		/**
		 * Settings fields array
		 *
		 * @var array
		 */
		private $settings_fields = array();

		/**
		 * Constructor, do nothing here
		 */
		public function __construct() {
		}

		/**
		 * Set settings sections
		 *
		 * @param array   $sections setting sections array
		 */
		public function set_sections( $sections ) {
			$this->settings_sections = $sections;
			return true;
		}

		/**
		 * Add a single section
		 *
		 * @param array   $section
		 */
		public function add_section( $section ) {
			$this->settings_sections[] = $section;
			return true;
		}

		/**
		 * Set settings fields
		 *
		 * @param array   $fields settings fields array
		 */
		public function set_fields( $fields ) {
			$this->settings_fields = $fields;
			return true;
		}

		/**
		 * Add setting field
		 *
		 * @param array   $section settings name
		 * @param array   $fields settings fields array
		 */
		public function add_field( $section, $field ) {
			// Check if section exist before add
			if ( ! isset( $this->settings_fields[ $section ] ) || ! is_array( $this->settings_fields[ $section ] ) ) {
				$this->settings_fields[ $section ] = array();
			}

			// Set default values
			$args = wp_parse_args(
				$field,
				array(
					'name' => '',
					'label' => '',
					'desc' => '',
					'type' => 'text',
				)
			);

			// Add settings into section array
			$this->settings_fields[ $section ][] = $args;

			return true;
		}

		/**
		 * Initialize and registers the settings sections and fileds to WordPress
		 *
		 * Usually this should be called at `admin_init` hook.
		 *
		 * This function gets the initiated settings sections and fields. Then
		 * registers them to WordPress and ready for use.
		 */
		public function admin_init() {
			// register settings sections
			foreach ( $this->settings_sections as $section ) {
				if ( empty( $section['id'] ) ) {
					continue;
				}

				if ( false == get_option( $section['id'] ) ) {
					add_option( $section['id'] );
				}

				if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
					$section['desc'] = '<div class="inside">' . $section['desc'] . '</div>' . PHP_EOL;
					$callback = create_function( '', 'echo "' . str_replace( '"', '\"', $section['desc'] ) . '";' );
				} else {
					$callback = '__return_false';
				}

				add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
			}

			// register settings fields
			foreach ( $this->settings_fields as $section => $field ) {
				foreach ( $field as $option ) {
					if ( empty( $option['name'] ) ) {
						continue;
					}

					$option['type']  = isset( $option['type'] ) ? $option['type'] : 'text';
					$option['label'] = isset( $option['label'] ) ? $option['label'] : '';

					$args = array(
						'id' => $option['name'],
						'desc' => isset( $option['desc'] ) ? $option['desc'] : '',
						'name' => $option['label'],
						'section' => $section,
						'size' => isset( $option['size'] ) ? $option['size'] : null,
						'options' => isset( $option['options'] ) ? $option['options'] : '',
						'std' => isset( $option['default'] ) ? $option['default'] : '',
						'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
					);

					if ( in_array( $option['type'], array( 'metabox', 'html' ) ) ) {
						$args['callback_only'] = true;
					}

					add_settings_field( $section . '[' . $option['name'] . ']', $option['label'], array( $this, 'callback_' . $option['type'] ), $section, $section, $args );
				}
			}

			// creates our settings in the options table
			foreach ( $this->settings_sections as $section ) {
				if ( empty( $section['id'] ) ) {
					continue;
				}

				register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
			}
		}

		/**
		 * Displays a text field for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_text( $args ) {
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<input type="text" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value ) . PHP_EOL;

			if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['desc'] ) . PHP_EOL;
			}

			echo $html;
		}

		/**
		 * Displays a checkbox for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_checkbox( $args ) {
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

			if ( isset( $args['options'] ) && ! empty( $args['options'] ) && is_string( $args['options'] ) ) {
				$label = $args['options'];
				$description = $args['desc'];
			} else {
				$label = $args['desc'];
				$description = '';
			}

			$html  = sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] ) . PHP_EOL;
			$html .= sprintf( '<input type="checkbox" class="checkbox" id="%1$s[%2$s]" name="%1$s[%2$s]" value="on"%4$s />', $args['section'], $args['id'], $value, checked( $value, 'on', false ) ) . PHP_EOL;
			$html .= sprintf( '<label for="%1$s[%2$s]">%3$s</label>', $args['section'], $args['id'], $label ) . PHP_EOL;

			if ( ! empty( $description ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $description ) . PHP_EOL;
			}

			echo $html;
		}

		/**
		 * Displays a multicheckbox a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_multicheck( $args ) {
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

			$html = '';
			foreach ( $args['options'] as $key => $label ) {
				$checked = isset( $value[ $key ] ) ? $value[ $key ] : '0';
				$html .= sprintf( '<input type="checkbox" class="checkbox" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s"%4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) ) . PHP_EOL;
				$html .= sprintf( '<label for="%1$s[%2$s][%4$s]">%3$s</label><br />', $args['section'], $args['id'], $label, $key ) . PHP_EOL;
			}

			if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['desc'] ) . PHP_EOL;
			}
			echo $html;
		}

		/**
		 * Displays a multicheckbox a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_radio( $args ) {
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

			$html = '';
			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) ) . PHP_EOL;
				$html .= sprintf( '<label for="%1$s[%2$s][%4$s]">%3$s</label><br />', $args['section'], $args['id'], $label, $key ) . PHP_EOL;
			}

			if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['desc'] ) . PHP_EOL;
			}

			echo $html;
		}

		/**
		 * Displays a selectbox for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_select( $args ) {
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] ) . PHP_EOL;
			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $value, $key, false ), $label ) . PHP_EOL;
			}
			$html .= sprintf( '</select>' ) . PHP_EOL;

			if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['desc'] ) . PHP_EOL;
			}

			echo $html;
		}

		/**
		 * Displays a textarea for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_textarea( $args ) {
			$value = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]">%4$s</textarea>', $size, $args['section'], $args['id'], $value ) . PHP_EOL;

			if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['desc'] ) . PHP_EOL;
			}

			echo $html;
		}

		/**
		 * Displays a textarea for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_html( $args ) {
			echo '<tr valign="top"><td colspan="2">';
			echo $args['desc'];
			echo '</td></tr>';
		}

		/**
		 * Displays a metabox for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_metabox( $args ) {
			echo '</tbody></table>';
			echo '</div>';

			echo '<div class="postbox" id="postbox-field-' . $args['id'] . '">';
			if ( isset( $args['name'] ) && ! empty( $args['name'] ) ) {
				echo '<h3>' . $args['name'] . '</h3>';
			}

			if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
				echo '<div class="inside">' . $args['desc'] . '</div>';
			}
			echo '<table class="form-table"><tbody>';
		}

		/**
		 * Displays a rich text textarea for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_wysiwyg( $args ) {
			$value = wpautop( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : '500px';

			echo '<div style="width: ' . $size . ';">' . PHP_EOL;
			wp_editor(
				$value,
				$args['section'] . '[' . $args['id'] . ']',
				array(
					'teeny' => false,
					'textarea_rows' => 10,
				)
			);
			echo '</div>' . PHP_EOL;

			if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
				printf( '<p class="description">%s</p>', $args['desc'] ) . PHP_EOL;
			}
		}

		/**
		 * Displays a file upload field for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_file( $args ) {
			// Enqueue JS for medias uploader
			wp_enqueue_media();
			add_action( 'admin_footer', array( __CLASS__, 'script_medias' ) );

			// Build HTML
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<input type="text" class="%1$s-text wpsf-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value ) . PHP_EOL;
			$html .= '<input type="button" class="button wpsf-browse hide-if-no-js" value="' . __( 'Browse' ) . '" />' . PHP_EOL;

			if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['desc'] ) . PHP_EOL;
			}

			echo $html;
		}

		/**
		 * Displays a password field for a settings field
		 *
		 * @param array   $args settings field args
		 */
		public function callback_password( $args ) {
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value ) . PHP_EOL;

			if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['desc'] ) . PHP_EOL;
			}

			echo $html;
		}

		/**
		 * Sanitize callback for Settings API
		 */
		public function sanitize_options( $options ) {
			if ( empty( $options ) ) {
				return $options;
			}

			foreach ( $options as $option_slug => $option_value ) {
				$sanitize_callback = $this->get_sanitize_callback( $option_slug );

				// If callback is set, call it
				if ( $sanitize_callback ) {
					$options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
					continue;
				}

				// Treat everything that's not an array as a string
				if ( ! is_array( $option_value ) ) {
					$options[ $option_slug ] = sanitize_text_field( $option_value );
					continue;
				}
			}

			return $options;
		}

		/**
		 * Get sanitization callback for given option slug
		 *
		 * @param string $slug option slug
		 *
		 * @return mixed string or bool false
		 */
		public function get_sanitize_callback( $slug = '' ) {
			if ( empty( $slug ) ) {
				return false;
			}

			// Iterate over registered fields and see if we can find proper callback
			foreach ( $this->settings_fields as $options ) {
				foreach ( $options as $option ) {
					if ( $option['name'] != $slug ) {
						continue;
					}

					// Return the callback name
					return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
				}
			}

			return false;
		}

		/**
		 * Get the value of a settings field
		 *
		 * @param string  $option  settings field name
		 * @param string  $section the section name this field belongs to
		 * @param string  $default default text if it's not found
		 * @return string
		 */
		public function get_option( $option, $section, $default = '' ) {
			$options = get_option( $section );
			if ( isset( $options[ $option ] ) ) {
				return $options[ $option ];
			}

			return $default;
		}

		/**
		 * Show navigations as tab
		 *
		 * Shows all the settings section labels as tab
		 */
		public function show_navigation() {
			$current_tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : '';

			$html = '<h2 class="nav-tab-wrapper">' . PHP_EOL;
			$i = 0;
			foreach ( $this->settings_sections as $section ) {
				if ( empty( $section['id'] ) ) {
					continue;
				}

				$i++;

				$class = ( $current_tab == $section['id'] || ( $current_tab == '' && $i == 1 ) ) ? 'nav-tab-active' : '';
				$title = ( isset( $section['tab_label'] ) && ! empty( $section['tab_label'] ) ) ? $section['tab_label'] : $section['title'];

				$html .= sprintf( '<a href="%1$s" class="nav-tab %2$s" id="%3$s-tab">%4$s</a>' . PHP_EOL, esc_url( add_query_arg( array( 'tab' => $section['id'] ) ) ), $class, $section['id'], $title );
			}
			$html .= '</h2>' . PHP_EOL;

			echo $html;
		}

		/**
		 * Show the section settings forms
		 *
		 * This function displays every sections in a different form
		 */
		public function show_forms() {
			$form = false;

			// Load tab specify on URL ?
			if ( isset( $_GET['tab'] ) ) {
				foreach ( $this->settings_sections as $section ) {
					if ( $section['id'] == $_GET['tab'] ) {
						$form = $section;
						break;
					}
				}
				reset( $this->settings_sections );
			}

			// No current tab ? Take first
			if ( empty( $form ) ) {
				foreach ( $this->settings_sections as $section ) {
					$form = $section;
					break;
				}
			}

			// No form to display, no valid section
			if ( empty( $form ) ) {
				wp_die( __( 'No section available' ) );
			}

			echo '<form method="post" action="options.php">' . PHP_EOL;
			 echo '<div class="metabox-holder">' . PHP_EOL;
				 echo '<div class="postbox" id="postbox-first-' . $form['id'] . '">' . PHP_EOL;
					do_action( 'wsa_form_top_' . $form['id'], $form );
					settings_fields( $form['id'] );
					$this->do_settings_sections( $form['id'] );
					do_action( 'wsa_form_bottom_' . $form['id'], $form );
				 echo '</div>' . PHP_EOL;
			 echo '</div>' . PHP_EOL;
			submit_button();
			echo '</form>' . PHP_EOL;

			echo '<style type="text/css">.metabox-holder .postbox:last-child { margin-bottom:0px; }</style>' . PHP_EOL;
		}

		/**
		 * Prints out all settings sections added to a particular settings page
		 *
		 * Part of the Settings API. Use this in a settings page callback function
		 * to output all the sections and fields that were added to that $page with
		 * add_settings_section() and add_settings_field()
		 *
		 * @global $wp_settings_sections Storage array of all settings sections added to admin pages
		 * @global $wp_settings_fields Storage array of settings fields and info about their pages/sections
		 * @since 2.7.0
		 *
		 * @param string $page The slug name of the page whos settings sections you want to output
		 */
		function do_settings_sections( $page ) {
			global $wp_settings_sections, $wp_settings_fields;

			if ( ! isset( $wp_settings_sections ) || ! isset( $wp_settings_sections[ $page ] ) ) {
				return;
			}

			foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
				if ( $section['title'] ) {
					echo "<h3>{$section['title']}</h3>\n";
				}

				if ( $section['callback'] ) {
					call_user_func( $section['callback'], $section );
				}

				if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
					continue;
				}
				echo '<table class="form-table">';
				$this->do_settings_fields( $page, $section['id'] );
				echo '</table>';
			}
		}

		/**
		 * Print out the settings fields for a particular settings section
		 *
		 * Part of the Settings API. Use this in a settings page to output
		 * a specific section. Should normally be called by do_settings_sections()
		 * rather than directly.
		 *
		 * @global $wp_settings_fields Storage array of settings fields and their pages/sections
		 *
		 * @since 2.7.0
		 *
		 * @param string $page Slug title of the admin page who's settings fields you want to show.
		 * @param section $section Slug title of the settings section who's fields you want to show.
		 */
		function do_settings_fields( $page, $section ) {
			global $wp_settings_fields;

			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
				return;
			}

			foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
				if ( isset( $field['args']['callback_only'] ) ) {
					call_user_func( $field['callback'], $field['args'] );
					continue;
				}

				echo '<tr valign="top">';
				if ( ! empty( $field['args']['label_for'] ) ) {
					echo '<th scope="row"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label></th>';
				} else {
					echo '<th scope="row">' . $field['title'] . '</th>';
				}
				echo '<td>';
					call_user_func( $field['callback'], $field['args'] );
				echo '</td>';
				echo '</tr>';
			}
		}

		/**
		 * Register JS action for new media uploader from WP 3.5
		 *
		 */
		public static function script_medias() {
			?>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			var file_frame;
			jQuery(".form-table").on("click", "input.wpsf-browse", function (event) {
				event.preventDefault();
				current_button = jQuery(this);

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					title: current_button.data("uploader_title"),
					button: {
						text: current_button.data("uploader_button_text"),
					},
					multiple: false // Set to true to allow multiple files to be selected
				});

				// When an image is selected, run a callback.
				file_frame.on("select", function () {
					// We set multiple to false so only get one image from the uploader
					attachment = file_frame.state().get("selection").first().toJSON();

					// Do something with attachment.id and/or attachment.url here
					current_button.prev('input.wpsf-url').val(attachment.url);
				});

				// Finally, open the modal
				file_frame.open();
			});
		});
		</script>
			<?php
		}
	}
endif;
