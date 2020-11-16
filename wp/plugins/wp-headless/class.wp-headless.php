<?php
/**
 * Class for handling Headless Wordpress
 *
 */
class WP_Headless {
	/**
	 * Add various hooks.
	 *
	 * @access public
	 * @static
	 */
	public static function init() {
        add_action('admin_init', array( __CLASS__, 'admin_init' ));

        add_filter('preview_post_link', array( __CLASS__, 'set_post_preview_link' ));
        add_filter('post_link', array( __CLASS__, 'set_post_link' ));
        WP_Headless_Api::init();
        WP_Headless_Redirect::init();
    }

    public static function activate() {
        flush_rewrite_rules();

        $secret_key = WP_Headless_Constants::get_secret_key_option();

        if(isset($secret_key)) {
            update_option(WP_Headless_Constants::SECRET_KEY, wp_generate_uuid4());
        } else {
            add_option(WP_Headless_Constants::SECRET_KEY, wp_generate_uuid4());
        }

        $frontend_uri = WP_Headless_Constants::get_frontend_uri_option();

        if(isset($frontend_uri)) {
            update_option(WP_Headless_Constants::FRONTEND_URI, '');
        } else {
            add_option(WP_Headless_Constants::FRONTEND_URI, '');
        }
    }

    public static function set_post_preview_link() {
        $base_uri = WP_Headless_Constants::get_frontend_uri_option();
        $post = get_post();

        return $base_uri . base64_encode('post:' . $post->ID) . '/?status=' . $post->post_status . '&preview=true';
    }

    public static function set_post_link() {
        $base_uri = WP_Headless_Constants::get_frontend_uri_option();
        $post = get_post();

        if ($post->post_status === 'draft') {
            return $base_uri . base64_encode('post:' . $post->ID) . '/?status=' . $post->post_status . '&preview=true';
        }

        return $base_uri . $post->post_name;
    }

    public static function deactivate() {
        flush_rewrite_rules();

        delete_option(WP_Headless_Constants::SECRET_KEY);
        delete_option(WP_Headless_Constants::FRONTEND_URI);

        remove_filter( 'preview_post_link', array( __CLASS__, 'set_post_preview_link' ) );
    }

    public static function admin_init() {
        // register a new setting for "general" page
        register_setting('general', WP_Headless_Constants::SECRET_KEY);
        register_setting('general', WP_Headless_Constants::FRONTEND_URI);

        // register a new section in the "general" page
        add_settings_section(
            'wp_Headlesss_settings',
            'WP Authentication Codes', array( __CLASS__, 'settings_section_callback' ),
            'general'
        );

        // register a new field in the "wp_Headlesss_settings" section, inside the "general" page
        add_settings_field(
            WP_Headless_Constants::SECRET_KEY,
            'API Secret', array( __CLASS__, 'settings_api_secret_callback' ),
            'general',
            'wp_Headlesss_settings'
        );

        add_settings_field(
            WP_Headless_Constants::FRONTEND_URI,
            'Preview Base Address (URL)', array( __CLASS__, 'settings_base_uri_callback' ),
            'general',
            'wp_Headlesss_settings'
        );
    }

    // section content cb
    public static function settings_section_callback() {
        // echo '<p>Settings for WP Authentication Codes.</p>';
    }

    // section content cb
    public static function settings_api_secret_callback() {
        // get the value of the setting we've registered with register_setting()
        $setting = WP_Headless_Constants::get_secret_key_option();
        // output the field
        ?>


        <input type="text" disabled class="regular-text" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <input type="hidden" class="regular-text" name="<?php echo WP_Headless_Constants::SECRET_KEY ?>" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    // section content cb
    public static function settings_base_uri_callback() {
        // get the value of the setting we've registered with register_setting()
        $setting = WP_Headless_Constants::get_frontend_uri_option();
        // output the field
        ?>

        <input type="text" class="regular-text" name="<?php echo WP_Headless_Constants::FRONTEND_URI ?>" value="<?php echo $setting ?>">
        <?php
    }
}