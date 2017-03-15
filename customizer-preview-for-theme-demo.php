<?php
    /**
     * Plugin Name:  Customizer Preview for Theme Demo
     * Plugin URI:   https://wordpress.org/plugins/customizer-preview-for-theme-demo/
     * Description:  Customizer Preview to show activated theme demo
     * Version:      1.0.3
     * Author:       Emran Ahmed
     * Author URI:   https://themehippo.com/
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

                do_action( 'before_customizer_preview_for_theme_demo_init', $this );

                register_activation_hook( __FILE__, array( $this, 'register_activation' ) );

                register_deactivation_hook( __FILE__, array( $this, 'register_deactivation' ) );

                // Auth and Show Customizer
                add_action( 'plugins_loaded', array( $this, 'show_customizer' ), 1 );

                // Load our custom preview
                // add_action( 'admin_init', array( $this, 'load_customizer' ) );

                // Init languages
                add_action( 'init', array( $this, 'language' ) );

                // To disable wp-admin view
                add_action( 'init', array( $this, 'clear_user_auth' ) );
                add_action( 'admin_init', array( $this, 'clear_user_auth' ) );

                add_filter( 'woocommerce_prevent_admin_access', array( $this, 'wc_prevent_admin_access' ) );

                // Add Settings Menu
                add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

                // Add settings
                add_action( 'admin_init', array( $this, 'add_admin_settings' ) );

                // Remove Ajax Save Action
                add_action( 'admin_init', array( $this, 'remove_save_action' ) );

                // Add CSS / JS
                add_action( 'customize_controls_print_scripts', array( $this, 'customize_controls_script' ) );
                add_action( 'customize_controls_print_styles', array( $this, 'customize_controls_style' ) );
                add_action( 'customize_controls_print_footer_scripts', array( $this, 'customize_controls_templates' ) );

                add_filter( 'plugin_action_links_' . CPTD_PLUGIN_BASENAME, array( $this, 'plugin_settings_link' ), 999 );

                do_action( 'customizer_preview_for_theme_demo_init', $this );
            }

            public function wc_prevent_admin_access( $default ) {
                return ( is_customize_preview() && $this->is_customizer_user() ) ? FALSE : $default;
            }

            public function customize_controls_templates() { ?>
                <script type="text/html" id="tmpl-customizer-preview-for-demo-notice">
                    <div id="customizer-preview-notice" class="accordion-section customize-info">
                        <div class="accordion-section-title"><span class="preview-notice">{{ data.preview_notice || "You can't upload images and save settings." }}</span></div>
                    </div>
                </script>
                <script type="text/html" id="tmpl-customizer-preview-for-demo-button">
                    <a class="button button-primary" target="{{ data.button_target }}" href="{{ data.button_link }}">{{ data.button_text }}</a>
                </script>
                <?php
            }

            public function customize_controls_style() {
                if ( $this->is_customizer_user() ) {
                    wp_enqueue_style( 'customizer-preview-for-theme-demo', plugins_url( '/assets/css/customizer-preview.css', __FILE__ ), array(), '20160811' );
                }
            }

            public function customize_controls_script() {

                if ( $this->is_customizer_user() ) {
                    $options = get_option( 'customizer_preview_option' );
                    wp_enqueue_script( 'customizer-preview-for-theme-demo', plugins_url( '/assets/js/customizer-preview.js', __FILE__ ), array( 'jquery' ), '20160811', TRUE );
                    wp_localize_script( 'customizer-preview-for-theme-demo', 'CustomizerDemoPreview', array(
                        'button_text'    => esc_attr( $options[ 'button_text' ] ),
                        'button_link'    => esc_url( $options[ 'button_link' ] ),
                        'button_target'  => esc_attr( $options[ 'button_target' ] ),
                        'preview_notice' => esc_html( $options[ 'preview_notice' ] ),
                    ) );
                }
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
                    <p><?php esc_html_e( 'Customizer Preview Button Settings', 'customizer-preview-for-theme-demo' ) ?></p>
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
                    'Button Settings', // title
                    array( $this, 'section_info' ), // callback
                    CPTD_PLUGIN_DIRNAME // page
                );

                add_settings_field(
                    'preview_notice', // id
                    'Preview Notice', // title
                    array( $this, 'settings_preview_notice_field' ), // callback
                    CPTD_PLUGIN_DIRNAME, // page
                    'customizer_preview_settings_section' // section
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

                add_settings_field(
                    'button_target', // id
                    'Button Link Target', // title
                    array( $this, 'settings_button_target_field' ), // callback
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
                if ( isset( $input[ 'button_target' ] ) ) {
                    $sanitary_values[ 'button_target' ] = sanitize_text_field( $input[ 'button_target' ] );
                }
                if ( isset( $input[ 'preview_notice' ] ) ) {
                    $sanitary_values[ 'preview_notice' ] = sanitize_text_field( $input[ 'preview_notice' ] );
                }

                return $sanitary_values;
            }

            // Button Field
            public function settings_preview_notice_field() {

                $options        = get_option( 'customizer_preview_option' );
                $preview_notice = isset( $options[ 'preview_notice' ] ) ? $options[ 'preview_notice' ] : "You can't upload images and save settings.";
                ?>
                <input type="text" class="regular-text" name="customizer_preview_option[preview_notice]" value="<?php echo esc_html( $preview_notice ); ?>">
                <?php
            }

            // Button Field
            public function settings_button_text_field() {

                $options     = get_option( 'customizer_preview_option' );
                $button_text = isset( $options[ 'button_text' ] ) ? $options[ 'button_text' ] : '';
                ?>
                <input type="text" class="regular-text" name="customizer_preview_option[button_text]" value="<?php echo esc_html( $button_text ); ?>">
                <?php
            }

            // Link Field
            public function settings_button_link_field() {

                $options     = get_option( 'customizer_preview_option' );
                $button_link = isset( $options[ 'button_link' ] ) ? $options[ 'button_link' ] : '';
                ?>
                <input type="url" class="regular-text" name="customizer_preview_option[button_link]" value="<?php echo esc_url( $button_link ); ?>">
                <?php
            }

            // Link Target
            public function settings_button_target_field() {

                $options       = get_option( 'customizer_preview_option' );
                $button_target = isset( $options[ 'button_target' ] ) ? $options[ 'button_target' ] : '';
                ?>

                <select name="customizer_preview_option[button_target]">
                    <option <?php selected( '_self', $button_target ) ?> value="_self"><?php esc_html_e( 'Default (_self)', 'customizer-preview-for-theme-demo' ) ?></option>
                    <option <?php selected( '_blank', $button_target ) ?>
                        value="_blank"><?php esc_html_e( 'Opens link in a new window / tab (_blank)', 'customizer-preview-for-theme-demo' ) ?></option>
                    <option <?php selected( '_parent', $button_target ) ?>
                        value="_parent"><?php esc_html_e( 'Opens link in the parent frameset (_parent)', 'customizer-preview-for-theme-demo' ) ?></option>
                    <option <?php selected( '_top', $button_target ) ?>
                        value="_top"><?php esc_html_e( 'Opens link in the full body of the window (_top)', 'customizer-preview-for-theme-demo' ) ?></option>
                </select>
                <?php
            }

            // Section Information
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

                if ( is_admin() and 'customize.php' == basename( $_SERVER[ 'PHP_SELF' ] ) ) {

                    if ( ! is_user_logged_in() ) {
                        $this->customizer_user_auth();
                        wp_safe_redirect( esc_url( admin_url( 'customize.php' ) ) );
                        die();
                    }
                }
            }

            // Remove ajax save action for security reason
            public function remove_save_action() {
                if ( $this->is_customizer_user() ) {
                    global $wp_customize;
                    remove_action( 'wp_ajax_customize_save', array( $wp_customize, 'save' ) );

                    // To remove some custom action like reset
                    // ========================================================
                    // add_action('customizer_preview_remove_action', function(){
                    // $fs = Flatsome_Customizer_Reset::get_instance();
                    // remove_action( 'wp_ajax_customizer_reset', array( $fs, 'ajax_customizer_reset' ) );
                    // });
                    do_action( 'customizer_preview_remove_action' );
                }
            }

            // Load customizer
            public function load_customizer() {
                if ( 'customize.php' == basename( $_SERVER[ 'PHP_SELF' ] ) ) {
                    if ( $this->is_customizer_user() ) {
                        ////// require_once CPTD_PLUGIN_INCLUDE_DIR . 'customizer-preview.php';
                        include ABSPATH . 'wp-admin/customize.php';
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
                $user          = get_user_by( 'login', trim( $username ) );
                $secure_cookie = is_ssl() ? TRUE : FALSE;
                if ( $user ) {
                    $user_id = $user->ID;
                    wp_set_current_user( $user_id );

                    if ( function_exists( 'wc_set_customer_auth_cookie' ) ):
                        //    wc_set_customer_auth_cookie( $user_id );
                    endif;

                    wp_set_auth_cookie( $user_id, TRUE, $secure_cookie );
                    do_action( 'wp_login', $user->user_login, $user );
                }
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

                    // Customizer Preview user capabilities
                    $user_capabilities = apply_filters( 'customizer_preview_user_capabilities', array(
                        'read'               => TRUE,
                        'edit_posts'         => FALSE,
                        'delete_posts'       => FALSE,
                        'edit_pages'         => FALSE,
                        'edit_theme_options' => TRUE,
                        'manage_options'     => TRUE, // Some themes like FlatSome adds "customize.php" into their admin menu
                        'customize'          => TRUE,
                    ) );

                    add_role( "theme-customizer-preview", esc_html__( "Customizer Preview", 'customizer-preview-for-theme-demo' ), $user_capabilities );
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