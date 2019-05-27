<?php
namespace ThirstyAffiliates\Models;

use ThirstyAffiliates\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates\Interfaces\Model_Interface;

use ThirstyAffiliates\Helpers\Plugin_Constants;
use ThirstyAffiliates\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Script_Loader implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Bootstrap.
     *
     * @since 3.0.0
     * @access private
     * @var Bootstrap
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 3.0.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 3.0.0
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;

    /**
     * Property that houses the Guided_Tour model.
     *
     * @since 3.0.0
     * @access private
     * @var Guided_Tour
     */
    private $_guided_tour;




    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Class constructor.
     *
     * @since 3.0.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions , Guided_Tour $guided_tour ) {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;
        $this->_guided_tour      = $guided_tour;

        $main_plugin->add_to_all_plugin_models( $this );

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 3.0.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return Bootstrap
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions , Guided_Tour $guided_tour ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions , $guided_tour );

        return self::$_instance;

    }

    /**
     * Load backend js and css scripts.
     *
     * @since 3.0.0
     * @since 3.4.0 Moved the code to load scripts for the wp editor to its own function.
     * @access public
     *
     * @global WP_Post $post WP_Post object of the current screen.
     *
     * @param string $handle Unique identifier of the current backend page.
     */
    public function load_backend_scripts( $handle ) {

        global $post;

        $screen = get_current_screen();

        $post_type = get_post_type();
        if ( !$post_type && isset( $_GET[ 'post_type' ] ) )
            $post_type = $_GET[ 'post_type' ];

        $review_request_response = get_option( Plugin_Constants::REVIEW_REQUEST_RESPONSE );

        // Show review request popup
        if ( is_admin() && current_user_can( 'manage_options' ) && $post_type === Plugin_Constants::AFFILIATE_LINKS_CPT && get_option( Plugin_Constants::SHOW_REQUEST_REVIEW ) === 'yes' && ( $review_request_response === 'review-later' || empty( $review_request_response ) ) ) {

            wp_enqueue_script( 'review-request' , $this->_constants->JS_ROOT_URL() . 'app/ta-review-request.js' , array( 'jquery' ) , Plugin_Constants::VERSION , true );
        }

        // Link picker styles and scripts.
        if ( is_admin() && current_user_can( 'edit_posts' ) && ! in_array( $screen->base , array( 'customize' ) ) ) {

            $this->load_wp_editor_scripts();

            if ( in_array( $screen->base , array( 'widgets' ) ) ) {
                wp_enqueue_script( 'ta_tinymce_widget_editor', $this->_constants->JS_ROOT_URL() . 'lib/thirstymce/widget-editor.js', array( 'jquery' , 'thickbox' ), Plugin_Constants::VERSION , true );
                wp_localize_script( 'ta_tinymce_widget_editor' , 'ta_widget_editor_url' , $this->_constants->JS_ROOT_URL() . 'lib/thirstymce' );
            }
        }

        if ( $screen->base === 'thirstylink_page_thirsty-settings' ) {

            // Settings

            wp_enqueue_style( 'selectize' , $this->_constants->JS_ROOT_URL() . 'lib/selectize/selectize.default.css' , array() , Plugin_Constants::VERSION , 'all' );
            wp_enqueue_style( 'ta_settings_css' , $this->_constants->CSS_ROOT_URL() . 'admin/ta-settings.css' , array() , Plugin_Constants::VERSION , 'all' );

            wp_enqueue_script( 'selectize', $this->_constants->JS_ROOT_URL() . 'lib/selectize/selectize.min.js' , array( 'jquery' , 'jquery-ui-core' , 'jquery-ui-sortable' ) , Plugin_Constants::VERSION , true );
            wp_enqueue_script( 'ta_settings_js', $this->_constants->JS_ROOT_URL() . 'app/ta-settings.js', array( 'jquery' ), Plugin_Constants::VERSION , true );
            wp_localize_script( 'ta_settings_js' , 'ta_settings_var' , array(
                'i18n_custom_link_prefix_valid_val' => __( 'Please provide a value for "Custom Link Prefix" option' , 'thirstyaffiliates' )
            ) );

            if ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === 'ta_import_export_settings' ) {

                // Import/Export

                wp_enqueue_style( 'ta_import_export_css' , $this->_constants->JS_ROOT_URL() . 'app/import_export/dist/import-export.css' , array() , 'all' );

                wp_enqueue_script( 'ta_import_export_js' , $this->_constants->JS_ROOT_URL() . 'app/import_export/dist/import-export.js' , array() , true );
                wp_localize_script( 'ta_import_export_js' , 'import_export_var' , array(
                    'please_input_settings_string' => __( 'Please input settings string' , 'thirstyaffiliates' ),
                    'settings_string_copied'       => __( 'Settings string copied' , 'thirstyaffiliates' ),
                    'failed_copy_settings_string'  => __( 'Failed to copy settings string' , 'thirstyaffiliates' )
                ) );

            } elseif ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] === 'ta_help_settings' ) {

                // Migration

                wp_enqueue_style( 'ta_migration_css' , $this->_constants->JS_ROOT_URL() . 'app/migration/dist/migration.css' , array() , 'all' );

                wp_enqueue_script( 'ta_migration_js' , $this->_constants->JS_ROOT_URL() . 'app/migration/dist/migration.js' , array() , true );
                wp_localize_script( 'ta_migration_js' , 'migration_var' , array(
                    'i18n_migration_failed' => __( 'Failed to do data migration' , 'thirstyaffiliates' ),
                    'i18n_confirm_migration' => __( 'Are you sure you want to migrate your ThirstyAffiliates data to version 3 format?' , 'thirstyaffiliates' )
                ) );

            }

        } elseif ( $screen->base == 'post' && $post_type == Plugin_Constants::AFFILIATE_LINKS_CPT ) {

            // Single Affiliate Link Edit Page

            wp_enqueue_style( 'thickbox' );
            wp_enqueue_style( 'jquery_tiptip' , $this->_constants->CSS_ROOT_URL() . 'lib/jquery-tiptip/jquery-tiptip.css' , array() , Plugin_Constants::VERSION , 'all' );
            wp_enqueue_style( 'ta_affiliate-link-page_css' , $this->_constants->JS_ROOT_URL() . 'app/affiliate_link_page/dist/affiliate-link-page.css' , array() , Plugin_Constants::VERSION , 'all' );

            wp_enqueue_media();
            wp_dequeue_script( 'autosave' ); // Disable autosave
            wp_enqueue_script( 'thickbox' , true );
            wp_enqueue_script( 'jquery_tiptip' , $this->_constants->JS_ROOT_URL() . 'lib/jquery-tiptip/jquery.tipTip.min.js' , array() , Plugin_Constants::VERSION , true );
            wp_enqueue_script( 'ta_affiliate-link-page_js' , $this->_constants->JS_ROOT_URL() . 'app/affiliate_link_page/dist/affiliate-link-page.js' , array() , Plugin_Constants::VERSION , true );

        } elseif ( $screen->id == 'thirstylink_page_thirsty-reports' ) {

            wp_enqueue_style( 'jquery-ui-styles' , '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.min.css' , array() , '1.11.4', 'all' );
            wp_enqueue_style( 'ta_reports_css' , $this->_constants->CSS_ROOT_URL() . 'admin/ta-reports.css' , array( 'jquery-ui-styles' ) , Plugin_Constants::VERSION , 'all' );

            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_script( 'ta_reports_js', $this->_constants->JS_ROOT_URL() . 'app/ta-reports.js', array( 'jquery' , 'jquery-ui-core' , 'jquery-ui-datepicker' ), Plugin_Constants::VERSION , true );

            if ( ! isset( $_GET[ 'tab' ] ) || $_GET[ 'tab' ] == 'link_performance' ) {

                wp_enqueue_script( 'jquery-flot', $this->_constants->JS_ROOT_URL() . 'lib/flot/jquery.flot.min.js', array( 'jquery' ), Plugin_Constants::VERSION , true );
                wp_enqueue_script( 'jquery-flot-time', $this->_constants->JS_ROOT_URL() . 'lib/flot/jquery.flot.time.min.js', array( 'jquery' , 'jquery-flot' ), Plugin_Constants::VERSION , true );

            }

        } elseif ( $screen->id === 'edit-thirstylink' ) {

            wp_enqueue_style( 'ta_affiliate_link_list' , $this->_constants->CSS_ROOT_URL() . 'admin/ta-affiliate-link-list.css' , array() , Plugin_Constants::VERSION , 'all' );
        }

        if ( get_option( 'ta_guided_tour_status' ) == 'open' && array_key_exists( $screen->id , $this->_guided_tour->get_screens() ) ) {

            wp_enqueue_style( 'ta-guided-tour_css' , $this->_constants->CSS_ROOT_URL() . 'admin/ta-guided-tour.css' , array( 'wp-pointer' ) , Plugin_Constants::VERSION , 'all' );
            wp_enqueue_script( 'ta-guided-tour_js' , $this->_constants->JS_ROOT_URL() . 'app/ta-guided-tour.js' , array( 'wp-pointer' , 'thickbox' ) , Plugin_Constants::VERSION , true );

            wp_localize_script( 'ta-guided-tour_js',
                'ta_guided_tour_params',
                array(
                    'actions'  => array( 'close_tour' => 'ta_close_guided_tour' ),
                    'nonces'   => array( 'close_tour' => wp_create_nonce( 'ta-close-guided-tour' ) ),
                    'screen'   => $this->_guided_tour->get_current_screen(),
                    'screenid' => $screen->id,
                    'height'   => 640,
                    'width'    => 640,
                    'texts'    => array(
                                     'btn_prev_tour'  => __( 'Previous', 'thirstyaffiliates' ),
                                     'btn_next_tour'  => __( 'Next', 'thirstyaffiliates' ),
                                     'btn_close_tour' => __( 'Close', 'thirstyaffiliates' ),
                                     'btn_start_tour' => __( 'Start Tour', 'thirstyaffiliates' )
                                 ),
                    'urls'     => array( 'ajax' => admin_url( 'admin-ajax.php' ) ),
                    'post'     => isset( $post ) && isset( $post->ID ) ? $post->ID : 0
                )
            );
        }

    }

    /**
     * Load frontend js and css scripts.
     *
     * @since 3.0.0
     * @since 3.3.0 Add enable JS redirect TA global var.
     * @since 3.3.5 Add parameter for checking if statistics module is enabled or not.
     * @since 3.4.0 Add support for page builders.
     * @access public
     */
    public function load_frontend_scripts() {

        global $post, $wp;

        if ( apply_filters( 'ta_enqueue_tajs_script' , ( get_option( 'ta_enable_link_fixer' , 'yes' ) === 'yes' || get_option( 'ta_enable_stats_reporting_module' , 'yes' ) === 'yes' ) ) ) {

            // load main frontend script that holds the link fixer and stat record JS code
            wp_enqueue_script( 'ta_main_js' , $this->_constants->JS_ROOT_URL() . 'app/ta.js' , array( 'jquery' ) , Plugin_Constants::VERSION , true );
            wp_localize_script( 'ta_main_js' , 'thirsty_global_vars' , array(
                'home_url'                  => $this->_get_absolute_home_url( true ),
                'ajax_url'                  => admin_url( 'admin-ajax.php' ),
                'link_fixer_enabled'        => get_option( 'ta_enable_link_fixer' , 'yes' ),
                'link_prefix'               => $this->_helper_functions->get_thirstylink_link_prefix(),
                'link_prefixes'             => maybe_unserialize( get_option( 'ta_used_link_prefixes' ) ),
                'post_id'                   => isset( $post->ID ) ? $post->ID : 0,
                'enable_record_stats'       => get_option( 'ta_enable_stats_reporting_module' , 'yes' ),
                'enable_js_redirect'        => get_option( 'ta_enable_javascript_frontend_redirect' ),
                'disable_thirstylink_class' => get_option( 'ta_disable_thirsty_link_class' )
            ) );
        }

        // Page builder support.
        if ( $this->_helper_functions->is_page_builder_active() )
            $this->load_wp_editor_scripts();
    }

    /**
     * Load scripts for WP Editor.
     * 
     * @since 3.4.0
     * @access public
     */
    public function load_wp_editor_scripts() {

        wp_enqueue_style( 'thirstyaffiliates-tinymce' , $this->_constants->CSS_ROOT_URL() . 'admin/ta-editor.css' , array( 'thickbox' ) , Plugin_Constants::VERSION , 'screen' );

        wp_enqueue_script( 'ta_editor_js', $this->_constants->JS_ROOT_URL() . 'app/ta-editor.js', array( 'jquery' , 'thickbox' ), Plugin_Constants::VERSION , true );
        wp_localize_script( 'ta_editor_js' , 'ta_editor_var' , apply_filters(  'ta_editor_linkpicker_jsvars' , array(
            'insertion_type'                   => $this->_helper_functions->get_option( 'ta_link_insertion_type' , 'link' ),
            'disable_qtag_buttons'             => get_option( 'ta_disable_text_editor_buttons' , 'no' ),
            'html_editor_enable_aff_link_btn'  => current_user_can( apply_filters( 'ta_enable_advance_link_picker' , 'edit_posts' ) ),
            'html_editor_enable_quick_add_btn' => current_user_can( apply_filters( 'ta_enable_quick_add_affiliate_link' , 'publish_posts' ) ),
            'html_editor_affiliate_link_btn'   => __( 'affiliate link' , 'thirstyaffiliates' ),
            'html_editor_quick_add_btn'        => __( 'quick add affiliate link' , 'thirstyaffiliates' ),
            'html_editor_affiliate_link_title' => __( 'Open the ThirstyAffiliates link picker' , 'thirstyaffiliates' ),
            'html_editor_quick_add_title'      => __( 'Open quick add affiliate link dialog' , 'thirstyaffiliates' ),
            'simple_search_placeholder'        => __( 'Type to search affiliate link' , 'thirstyaffiliates' )
        ) ) );
    }

    /**
     * Gutenberg backend scripts.
     * 
     * @since 3.6
     * @access public
     */
    public function gutenberg_backend_scripts() {

        wp_enqueue_style( 'ta-gutenberg-support' , $this->_constants->JS_ROOT_URL() . 'app/gutenberg_support/dist/gutenberg-support.css' , Plugin_Constants::VERSION , 'screen' );

        wp_enqueue_script( 'ta-gutenberg-support' , $this->_constants->JS_ROOT_URL() . 'app/gutenberg_support/dist/gutenberg-support.js' , array( 'wp-blocks' , 'wp-element' , 'wp-components' , 'wp-i18n' ) , '1.0.0' , true );
    }

    /**
     * Get absolute home_url by recreating it and removing unneeded information.
     *
     * @since 3.2.4
     * @access public
     *
     * @param bool $no_scheme Toggle to check if need to include scheme or not.
     * @return string Recreated home_url.
     */
    private function _get_absolute_home_url( $no_scheme = false ) {

        $home_url = parse_url( home_url() );
        $scheme   = $no_scheme ? '//' : $home_url[ 'scheme' ] . '://';
        $path     = isset( $home_url[ 'path' ] ) ? $home_url[ 'path' ] : '';
        $port     = isset( $home_url[ 'port' ] ) ? ':' . $home_url[ 'port' ] : '';

        return $scheme . $home_url[ 'host' ] . $port . $path;
    }

    /**
     * Execute plugin script loader.
     *
     * @since 3.0.0
     * @access public
     */
    public function run () {

        add_action( 'admin_enqueue_scripts' , array( $this , 'load_backend_scripts' ) , 10 , 1 );
        add_action( 'wp_enqueue_scripts' , array( $this , 'load_frontend_scripts' ) );

        // Add support for elementor.
        add_action( 'elementor/editor/before_enqueue_scripts' , array( $this , 'load_wp_editor_scripts' ) );

        // Gutenberg support
        add_action( 'enqueue_block_editor_assets' , array( $this , 'gutenberg_backend_scripts' ) );
    }

}
