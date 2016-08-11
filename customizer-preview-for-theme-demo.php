<?php
	/**
	 * Plugin Name:  Customizer Preview for Theme Demo
	 * Plugin URI:   https://wordpress.org/plugins/customizer-preview-for-theme-demo/
	 * Description:  Customizer Preview to show activated theme demo
	 * Version:      1.0.0
	 * Author:       Emran
	 * Author URI:   https://emran.me/
	 * License:      GPLv2.0+
	 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
	 *
	 * Text Domain:  customizer-preview-for-theme-demo
	 * Domain Path:  /languages/
	 */

	defined( 'ABSPATH' ) or die( 'Keep Quit' );

	if ( ! class_exists( 'Customizer_Preview_For_Theme_Demo' ) ):

		class Customizer_Preview_For_Theme_Demo {

			public function __construct() {
				$this->constants();
				$this->hooks();
				do_action( 'customizer_preview_for_theme_demo_loaded', $this );
			}

			public function constants() {
				define( 'CPTD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
				define( 'CPTD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
				define( 'CPTD_PLUGIN_INCLUDE_DIR', trailingslashit( plugin_dir_path( __FILE__ ) . 'includes' ) );
				define( 'CPTD_PLUGIN_DIRNAME', dirname( plugin_basename( __FILE__ ) ) );
				define( 'CPTD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
				define( 'CPTD_PLUGIN_FILE', __FILE__ );
			}

			public function hooks() {

				do_action( 'before_customizer_preview_for_theme_demo_init' );

				register_activation_hook( __FILE__, array( $this, 'register_activation' ) );

				register_deactivation_hook( __FILE__, array( $this, 'register_deactivation' ) );

				// Auth and Show Customizer
				add_action( 'plugins_loaded', array( $this, 'show_customizer' ), 1 );

				// Load our preview
				add_action( 'admin_init', array( $this, 'load_customizer' ) );

				// Init languages
				add_action( 'init', array( $this, 'language' ) );

				// To disable wp-admin view
				add_action( 'init', array( $this, 'clear_user_auth' ) );

				// Add Settings Menu
				add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

				// Add settings
				add_action( 'admin_init', array( $this, 'add_admin_settings' ) );

				// Customizer Header
				add_action( 'customizer_preview_header', array( $this, 'header_button' ) );

				add_filter( 'plugin_action_links_' . CPTD_PLUGIN_BASENAME, array( $this, 'plugin_settings_link' ), 999 );

				do_action( 'customizer_preview_for_theme_demo_init' );
			}

			public function plugin_settings_link( $links ) {
				if ( is_plugin_active( CPTD_PLUGIN_BASENAME ) ) {
					$action_links = array(
						'settings' => sprintf( '<a href="' . esc_url( admin_url( 'options-general.php?page=%1$s' ) ) . '" title="' . esc_attr__( 'Settings', 'customizer-preview-for-theme-demo' ) . '">' . esc_html__( 'Settings', 'customizer-preview-for-theme-demo' ) . '</a>', CPTD_PLUGIN_DIRNAME ),
					);

					return array_merge( $action_links, $links );
				}

				return (array) $links;
			}

			// Show button on customizer window
			public function header_button() {
				$options = get_option( 'customizer_preview_option' );
				printf( '<a class="button button-primary" href="%s">%s</a>', esc_url( $options[ 'button_link' ] ), esc_attr( $options[ 'button_text' ] ) );
			}

			// Add admin menu
			public function add_admin_menu() {
				add_options_page(
					esc_html__( 'Customizer Preview Settings', 'customizer-preview-for-theme-demo' ),
					esc_html__( 'Customizer Preview', 'customizer-preview-for-theme-demo' ),
					'manage_options',
					CPTD_PLUGIN_DIRNAME,
					array( $this, 'show_settings_form' ) );
			}

			// Settings Form
			public function show_settings_form() {

				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die(
						'<h1>' . esc_html__( 'Cheatin&#8217; uh?', 'customizer-preview-for-theme-demo' ) . '</h1>' .
						'<p>' . esc_html__( 'You are not allowed to this section.', 'customizer-preview-for-theme-demo' ) . '</p>',
						403
					);
				}

				?>
				<div class="wrap">
					<h2><?php echo esc_html( get_admin_page_title() ) ?></h2>
					<p><?php esc_html_e( 'Customizer Preview settings', 'customizer-preview-for-theme-demo' ) ?></p>
					<form method="post" action="options.php">
						<?php
							settings_fields( 'customizer_preview_settings_group' );
							do_settings_sections( CPTD_PLUGIN_DIRNAME );
							submit_button();
						?>
					</form>
				</div>
				<?php
			}

			// Register Admin Settings
			public function add_admin_settings() {
				register_setting(
					'customizer_preview_settings_group', // option_group
					'customizer_preview_option', // option_name
					array( $this, 'settings_sanitize' ) // sanitize_callback
				);

				add_settings_section(
					'customizer_preview_settings_section', // id
					'Thumbnail Settings', // title
					array( $this, 'section_info' ), // callback
					CPTD_PLUGIN_DIRNAME // page
				);

				add_settings_field(
					'button_text', // id
					'Button Text', // title
					array( $this, 'settings_button_text_field' ), // callback
					CPTD_PLUGIN_DIRNAME, // page
					'customizer_preview_settings_section' // section
				);

				add_settings_field(
					'button_link', // id
					'Button Link', // title
					array( $this, 'settings_button_link_field' ), // callback
					CPTD_PLUGIN_DIRNAME, // page
					'customizer_preview_settings_section' // section
				);
			}

			// Settings sanitize before save
			public function settings_sanitize( $input ) {
				$sanitary_values = array();
				if ( isset( $input[ 'button_link' ] ) ) {
					$sanitary_values[ 'button_link' ] = esc_url( $input[ 'button_link' ] );
				}
				if ( isset( $input[ 'button_text' ] ) ) {
					$sanitary_values[ 'button_text' ] = sanitize_text_field( $input[ 'button_text' ] );
				}

				return $sanitary_values;
			}

			// Button Field
			public function settings_button_text_field() {

				$options = get_option( 'customizer_preview_option' );
				?>
				<input type='text' name='customizer_preview_option[button_text]' value='<?php echo esc_html( $options[ 'button_text' ] ); ?>'>
				<?php
			}

			// Link Field
			public function settings_button_link_field() {

				$options = get_option( 'customizer_preview_option' );
				?>
				<input type='text' name='customizer_preview_option[button_link]' value='<?php echo esc_url( $options[ 'button_link' ] ); ?>'>
				<?php
			}

			public function section_info() {
				echo '';
			}

			// Load Language
			public function language() {
				load_plugin_textdomain( 'customizer-preview-for-theme-demo', FALSE, CPTD_PLUGIN_DIRNAME . '/languages' );
			}

			// Logout User
			public function clear_user_auth() {
				if ( ! is_customize_preview() && $this->is_customizer_user() ) {
					wp_logout();
					wp_safe_redirect( esc_url( home_url( '/' ) ) );
					die();
				}
			}

			// Accessing customizer
			public function show_customizer() {

				if ( is_admin() && 'customize.php' == basename( $_SERVER[ 'PHP_SELF' ] ) ) {
					if ( ! is_user_logged_in() ) {
						$this->customizer_user_auth();
						wp_safe_redirect( esc_url( admin_url( 'customize.php' ) ) );
						die();
					}
				}
			}

			// Load customizer
			public function load_customizer() {
				if ( 'customize.php' == basename( $_SERVER[ 'PHP_SELF' ] ) ) {
					if ( $this->is_customizer_user() ) {

						// If there are enough hooks for customizer.php then we really don't need to use this :D but....
						include_once CPTD_PLUGIN_INCLUDE_DIR . 'customizer-preview.php';
						die;
					}
				}
			}

			// Is this user allowed to see customizer
			public function is_customizer_user() {

				if ( ! is_user_logged_in() ) {
					return FALSE;
				}
				$user = wp_get_current_user();

				return in_array( 'theme-customizer-preview', $user->roles );
			}

			// Auto login user
			public function customizer_user_auth( $username = 'customizer_user' ) {
				$user    = get_user_by( 'login', trim( $username ) );
				$user_id = $user->ID;
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id );
				do_action( 'wp_login', trim( $username ), $user );
			}

			// Create customizer user if user not exists
			public function create_customizer_user() {
				if ( ! username_exists( 'customizer_user' ) ) {

					$password = wp_generate_password();

					$new_user_data = array(
						'user_login' => 'customizer_user',
						'user_pass'  => $password,
						'role'       => 'theme-customizer-preview'
					);

					wp_insert_user( $new_user_data );
				}
			}

			public function register_activation() {
				if ( ! get_role( "theme-customizer-preview" ) ) {
					add_role( "theme-customizer-preview", esc_html__( "Customizer Preview", 'customizer-preview-for-theme-demo' ), array(
						'read'               => TRUE,
						'edit_posts'         => FALSE,
						'delete_posts'       => FALSE,
						'edit_theme_options' => TRUE,
					) );
				}

				$this->create_customizer_user();
			}

			public function register_deactivation() {
				if ( get_role( "theme-customizer-preview" ) ) {
					remove_role( "theme-customizer-preview" );
				}
			}
		}

		new Customizer_Preview_For_Theme_Demo();
	endif;