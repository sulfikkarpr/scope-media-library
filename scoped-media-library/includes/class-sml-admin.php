<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Scoped_Media_Library
 * @subpackage Scoped_Media_Library/includes
 */

class SML_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        
        // Load on settings page and media modal
        if ( isset( $screen->id ) && ( $screen->id === 'settings_page_scoped-media-library' || $screen->id === 'upload' ) ) {
            wp_enqueue_style( 
                $this->plugin_name, 
                SCOPED_MEDIA_LIBRARY_PLUGIN_URL . 'assets/css/sml-admin.css', 
                array(), 
                $this->version, 
                'all' 
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        
        // Load on settings page and media modal
        if ( isset( $screen->id ) && ( $screen->id === 'settings_page_scoped-media-library' || $screen->id === 'upload' ) ) {
            wp_enqueue_script( 
                $this->plugin_name, 
                SCOPED_MEDIA_LIBRARY_PLUGIN_URL . 'assets/js/sml-admin.js', 
                array( 'jquery' ), 
                $this->version, 
                false 
            );
            
            // Pass settings to JavaScript
            $settings = get_option( 'sml_settings', array() );
            wp_localize_script( $this->plugin_name, 'smlSettings', array(
                'enabled' => isset( $settings['enabled'] ) ? $settings['enabled'] : false,
                'minWidth' => isset( $settings['min_width'] ) ? $settings['min_width'] : '',
                'maxWidth' => isset( $settings['max_width'] ) ? $settings['max_width'] : '',
                'minHeight' => isset( $settings['min_height'] ) ? $settings['min_height'] : '',
                'maxHeight' => isset( $settings['max_height'] ) ? $settings['max_height'] : '',
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'sml_sync_nonce' )
            ) );
        }
    }

    /**
     * Add the plugin admin menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        add_options_page(
            __( 'Scoped Media Library Settings', 'scoped-media-library' ),
            __( 'Scoped Media Library', 'scoped-media-library' ),
            'manage_options',
            'scoped-media-library',
            array( $this, 'display_plugin_settings_page' )
        );
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'sml_settings_group',
            'sml_settings',
            array( $this, 'sanitize_settings' )
        );

        add_settings_section(
            'sml_general_section',
            __( 'Dimension Rules', 'scoped-media-library' ),
            array( $this, 'general_section_callback' ),
            'scoped-media-library'
        );

        add_settings_field(
            'sml_enabled',
            __( 'Enable Filtering', 'scoped-media-library' ),
            array( $this, 'enabled_callback' ),
            'scoped-media-library',
            'sml_general_section'
        );

        add_settings_field(
            'sml_min_width',
            __( 'Minimum Width (px)', 'scoped-media-library' ),
            array( $this, 'min_width_callback' ),
            'scoped-media-library',
            'sml_general_section'
        );

        add_settings_field(
            'sml_max_width',
            __( 'Maximum Width (px)', 'scoped-media-library' ),
            array( $this, 'max_width_callback' ),
            'scoped-media-library',
            'sml_general_section'
        );

        add_settings_field(
            'sml_min_height',
            __( 'Minimum Height (px)', 'scoped-media-library' ),
            array( $this, 'min_height_callback' ),
            'scoped-media-library',
            'sml_general_section'
        );

        add_settings_field(
            'sml_max_height',
            __( 'Maximum Height (px)', 'scoped-media-library' ),
            array( $this, 'max_height_callback' ),
            'scoped-media-library',
            'sml_general_section'
        );

        add_settings_section(
            'sml_fallback_section',
            __( 'Fallback Mode', 'scoped-media-library' ),
            array( $this, 'fallback_section_callback' ),
            'scoped-media-library'
        );

        add_settings_field(
            'sml_fallback_mode',
            __( 'Enable Fallback Mode', 'scoped-media-library' ),
            array( $this, 'fallback_mode_callback' ),
            'scoped-media-library',
            'sml_fallback_section'
        );

        add_settings_field(
            'sml_fallback_roles',
            __( 'Fallback User Roles', 'scoped-media-library' ),
            array( $this, 'fallback_roles_callback' ),
            'scoped-media-library',
            'sml_fallback_section'
        );
    }

    /**
     * Sanitize settings.
     *
     * @since    1.0.0
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        $sanitized['enabled'] = isset( $input['enabled'] ) ? (bool) $input['enabled'] : false;
        
        $sanitized['min_width'] = isset( $input['min_width'] ) && is_numeric( $input['min_width'] ) 
            ? absint( $input['min_width'] ) 
            : '';
        
        $sanitized['max_width'] = isset( $input['max_width'] ) && is_numeric( $input['max_width'] ) 
            ? absint( $input['max_width'] ) 
            : '';
        
        $sanitized['min_height'] = isset( $input['min_height'] ) && is_numeric( $input['min_height'] ) 
            ? absint( $input['min_height'] ) 
            : '';
        
        $sanitized['max_height'] = isset( $input['max_height'] ) && is_numeric( $input['max_height'] ) 
            ? absint( $input['max_height'] ) 
            : '';

        $sanitized['fallback_mode'] = isset( $input['fallback_mode'] ) ? (bool) $input['fallback_mode'] : false;
        
        $sanitized['fallback_roles'] = isset( $input['fallback_roles'] ) && is_array( $input['fallback_roles'] ) 
            ? array_map( 'sanitize_text_field', $input['fallback_roles'] ) 
            : array( 'administrator' );

        return $sanitized;
    }

    /**
     * Section callbacks.
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__( 'Define the dimension rules for filtering images in the media library. Leave fields empty to disable that specific constraint.', 'scoped-media-library' ) . '</p>';
    }

    public function fallback_section_callback() {
        echo '<p>' . esc_html__( 'Fallback mode allows specific user roles to see all images alongside scoped results.', 'scoped-media-library' ) . '</p>';
    }

    /**
     * Field callbacks.
     */
    public function enabled_callback() {
        $settings = get_option( 'sml_settings', array() );
        $enabled = isset( $settings['enabled'] ) ? $settings['enabled'] : false;
        ?>
        <label>
            <input type="checkbox" name="sml_settings[enabled]" value="1" <?php checked( $enabled, true ); ?> />
            <?php esc_html_e( 'Enable dimension-based filtering for the media library', 'scoped-media-library' ); ?>
        </label>
        <?php
    }

    public function min_width_callback() {
        $settings = get_option( 'sml_settings', array() );
        $value = isset( $settings['min_width'] ) ? $settings['min_width'] : '';
        ?>
        <input type="number" name="sml_settings[min_width]" value="<?php echo esc_attr( $value ); ?>" 
               min="0" step="1" class="small-text" />
        <p class="description"><?php esc_html_e( 'Minimum image width in pixels (leave empty for no minimum)', 'scoped-media-library' ); ?></p>
        <?php
    }

    public function max_width_callback() {
        $settings = get_option( 'sml_settings', array() );
        $value = isset( $settings['max_width'] ) ? $settings['max_width'] : '';
        ?>
        <input type="number" name="sml_settings[max_width]" value="<?php echo esc_attr( $value ); ?>" 
               min="0" step="1" class="small-text" />
        <p class="description"><?php esc_html_e( 'Maximum image width in pixels (leave empty for no maximum)', 'scoped-media-library' ); ?></p>
        <?php
    }

    public function min_height_callback() {
        $settings = get_option( 'sml_settings', array() );
        $value = isset( $settings['min_height'] ) ? $settings['min_height'] : '';
        ?>
        <input type="number" name="sml_settings[min_height]" value="<?php echo esc_attr( $value ); ?>" 
               min="0" step="1" class="small-text" />
        <p class="description"><?php esc_html_e( 'Minimum image height in pixels (leave empty for no minimum)', 'scoped-media-library' ); ?></p>
        <?php
    }

    public function max_height_callback() {
        $settings = get_option( 'sml_settings', array() );
        $value = isset( $settings['max_height'] ) ? $settings['max_height'] : '';
        ?>
        <input type="number" name="sml_settings[max_height]" value="<?php echo esc_attr( $value ); ?>" 
               min="0" step="1" class="small-text" />
        <p class="description"><?php esc_html_e( 'Maximum image height in pixels (leave empty for no maximum)', 'scoped-media-library' ); ?></p>
        <?php
    }

    public function fallback_mode_callback() {
        $settings = get_option( 'sml_settings', array() );
        $enabled = isset( $settings['fallback_mode'] ) ? $settings['fallback_mode'] : false;
        ?>
        <label>
            <input type="checkbox" name="sml_settings[fallback_mode]" value="1" <?php checked( $enabled, true ); ?> />
            <?php esc_html_e( 'Show all images to selected user roles (in addition to filtered results)', 'scoped-media-library' ); ?>
        </label>
        <?php
    }

    public function fallback_roles_callback() {
        $settings = get_option( 'sml_settings', array() );
        $selected_roles = isset( $settings['fallback_roles'] ) ? $settings['fallback_roles'] : array( 'administrator' );
        $roles = wp_roles()->get_names();
        ?>
        <fieldset>
            <?php foreach ( $roles as $role_key => $role_name ) : ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" name="sml_settings[fallback_roles][]" 
                           value="<?php echo esc_attr( $role_key ); ?>" 
                           <?php checked( in_array( $role_key, $selected_roles, true ), true ); ?> />
                    <?php echo esc_html( $role_name ); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <p class="description"><?php esc_html_e( 'Users with these roles will see all images when fallback mode is enabled', 'scoped-media-library' ); ?></p>
        <?php
    }

    /**
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function display_plugin_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div class="sml-settings-header">
                <p class="description">
                    <?php esc_html_e( 'Control which images appear in the WordPress media library selector by defining dimension rules. This is especially useful for ACF, Beaver Builder, and other page builders.', 'scoped-media-library' ); ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'sml_settings_group' );
                do_settings_sections( 'scoped-media-library' );
                submit_button( __( 'Save Settings', 'scoped-media-library' ) );
                ?>
            </form>

            <hr>

            <div class="sml-sync-section">
                <h2><?php esc_html_e( 'Sync Image Dimensions', 'scoped-media-library' ); ?></h2>
                <p class="description">
                    <?php esc_html_e( 'If you have existing images in your media library, click the button below to sync their dimensions. This may take a few minutes for large libraries.', 'scoped-media-library' ); ?>
                </p>
                <button type="button" id="sml-sync-dimensions" class="button button-secondary">
                    <?php esc_html_e( 'Sync All Image Dimensions', 'scoped-media-library' ); ?>
                </button>
                <span class="spinner" style="float: none; margin-left: 10px;"></span>
                <div id="sml-sync-result" style="margin-top: 10px;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Add action links to the plugin list.
     *
     * @since    1.0.0
     */
    public function add_action_links( $links ) {
        $settings_link = array(
            '<a href="' . admin_url( 'options-general.php?page=scoped-media-library' ) . '">' . __( 'Settings', 'scoped-media-library' ) . '</a>',
        );
        return array_merge( $settings_link, $links );
    }
}