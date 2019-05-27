<?php

namespace ThirstyAffiliates\Models;

use ThirstyAffiliates\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates\Interfaces\Model_Interface;
use ThirstyAffiliates\Interfaces\Activatable_Interface;
use ThirstyAffiliates\Interfaces\Initiable_Interface;

use ThirstyAffiliates\Helpers\Plugin_Constants;
use ThirstyAffiliates\Helpers\Helper_Functions;

/**
 * Model that houses the logic of Marketing of old versions of TA to version 3.0.0
 *
 * @since 3.0.0
 */
class Marketing implements Model_Interface , Activatable_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Marketing.
     *
     * @since 3.0.0
     * @access private
     * @var Redirection
     */
    private static $_instance;

    /**
     * Model that houses the main plugin object.
     *
     * @since 3.0.0
     * @access private
     * @var Redirection
     */
    private $_main_plugin;

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
     * Property that holds the list of all affiliate links.
     *
     * @since 3.0.0
     * @access private
     * @var array
     */
    private $_all_affiliate_links;

    /**
     * Variable that holds the mapping between options from old version of the plugin to the new version of the plugin.
     *
     * @since 3.0.0
     * @access public
     * @var array
     */
    private $_old_new_options_mapping;

    /**
     * Variable that holds the mapping between post meta from old version of the plugin to the new version of the plugin.
     *
     * @since 3.0.0
     * @access public
     * @var array
     */
    private $_old_new_meta_mapping;




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
    public function __construct( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        $this->_constants           = $constants;
        $this->_helper_functions    = $helper_functions;

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
     * @return Redirection
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }

    /**
     * Flag to show review request.
     *
     * @since 3.0.0
     * @access public
     */
    public function flag_show_review_request() {

        update_option( Plugin_Constants::SHOW_REQUEST_REVIEW , 'yes' );

    }

    /**
     * Flag to show TA Pro notice.
     *
     * @since 3.0.0
     * @access public
     */
    public function flag_show_tapro_notice() {

        // prevent the notice showing up again when the plugin is deactivated/activated.
        if ( get_option( Plugin_Constants::SHOW_TAPRO_NOTICE ) )
            return;

        update_option( Plugin_Constants::SHOW_TAPRO_NOTICE , 'yes' );

    }

    /**
     * Record the user's review request response.
     *
     * @since 3.0.0
     * @access public
     */
    public function ajax_request_review_response() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'rwcdplm' ) );
        elseif ( !isset( $_POST[ 'review_request_response' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Required parameter not passed' , 'rwcdplm' ) );
        else {

            update_option( Plugin_Constants::REVIEW_REQUEST_RESPONSE , $_POST[ 'review_request_response' ] );

            if ( $_POST[ 'review_request_response' ] === 'review-later' )
                wp_schedule_single_event( time() + 1209600 , Plugin_Constants::CRON_REQUEST_REVIEW );

            delete_option( Plugin_Constants::SHOW_REQUEST_REVIEW );

            $response = array( 'status' => 'success' , 'success_msg' => __( 'Review request response saved' , 'rwcdplm' ) );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();

    }

    /**
     * Display the review request admin notice.
     *
     * @since 3.0.0
     * @access public
     */
    public function show_review_request_notice() {

        $screen = get_current_screen();

        $post_type = get_post_type();
        if ( !$post_type && isset( $_GET[ 'post_type' ] ) )
            $post_type = $_GET[ 'post_type' ];

        $review_request_response = get_option( Plugin_Constants::REVIEW_REQUEST_RESPONSE );

        if ( ! is_admin() || ! current_user_can( 'manage_options' ) || $post_type !== Plugin_Constants::AFFILIATE_LINKS_CPT || get_option( Plugin_Constants::SHOW_REQUEST_REVIEW ) !== 'yes' || ( $review_request_response !== 'review-later' && ! empty( $review_request_response ) ) )
            return;

        if ( $this->_helper_functions->is_plugin_active( 'thirstyaffiliates-pro/thirstyaffiliates-pro.php' ) ) {

            $msg = sprintf( __( '<p>We see you have been using ThirstyAffiliates for a couple of weeks now – thank you once again for your purchase and we hope you are enjoying it so far!</p>
                                 <p>We\'d really appreciate it if you could take a few minutes to write a 5-star review of our free plugin on WordPress.org!</p>
                                 <p>Your comment will go a long way to helping us grow and giving new users the confidence to give us a try.</p>
                                 <p>Thanks in advance, we are looking forward to reading it!</p>
                                 <p>PS. If you ever need support, please just <a href="%1$s" target="_blank">get in touch here.</a></p>' , "thirstyaffiliates" ) , 'https://goo.gl/SsDbYD' );

        } else {

            $msg = __( "<p>Thanks for using our free ThirstyAffiliates plugin – we hope you are enjoying it so far.</p>
                        <p>We’d really appreciate it if you could take a few minutes to write a 5-star review of our plugin on WordPress.org!</p>
                        <p>Your comment will go a long way to helping us grow and giving new users the confidence to give us a try.</p>
                        <p>Thanks in advance, we are looking forward to reading it!</p>" , "thirstyaffiliates" );

        } ?>
        <div class="ta-review-request notice notice-info">

            <div style="padding: 4px 0;">
                <a href="https://thirstyaffiliates.com/" target="_blank"><img src="<?php echo $this->_constants->IMAGES_ROOT_URL() . 'admin-review-notice-logo.png'; ?>"></a>
            </div>

            <?php echo $msg; ?>

            <p class="actions">
                <a href="#" class="button" data-response="never-show"><?php _e( 'Don\'t show again' , 'thirstyaffiliates' ); ?></a>
                <a href="#" class="button" data-response="review-later"><?php _e( 'Review later' , 'thirstyaffiliates' ); ?></a>
                <a href="https://goo.gl/RAsxVu" class="button button-primary" data-response="review"><?php _e( 'Review' , 'thirstyaffiliates' ); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Display the TA Pro promotional admin notice.
     *
     * @since 3.0.0
     * @access public
     */
    public function show_tapro_admin_notice() {

        $post_type = get_post_type();
        if ( !$post_type && isset( $_GET[ 'post_type' ] ) )
            $post_type = $_GET[ 'post_type' ];

        if ( ! is_admin() || ! current_user_can( 'manage_options' ) || $post_type !== Plugin_Constants::AFFILIATE_LINKS_CPT || get_option( Plugin_Constants::SHOW_TAPRO_NOTICE ) !== 'yes' || $this->_helper_functions->is_plugin_active( 'thirstyaffiliates-pro/thirstyaffiliates-pro.php' ) )
            return;

        $tapro_url = esc_url( 'https://thirstyaffiliates.com/pricing/?utm_source=Free%20Plugin&utm_medium=Pro&utm_campaign=Admin%20Notice' ); ?>
        <div class="notice notice-error is-dismissible ta_tapro_admin_notice">
            <?php
                echo sprintf( __( '<h4>Hi there, we hope you\'re enjoying ThirstyAffiliates!</h4>
                     <p>Did you know we also have a Pro addon that can help you:</p>
                     <ul><li>Automatically link up affiliate links to keywords throughout your site (monetize your site faster!)</li>
                     <li>Give you more amazing advanced reports (see what is working and what is not!)</li>
                     <li>Let you link to different places depending on the visitor\'s country (geolocation links)</li>
                     <li>Let you import Amazon products as links (+ CSV import/export and more premium importing options)</li>
                     <li>... plus a whole lot more!</li></ul>
                     <p><a href="%s" target="_blank">Check out the ThristyAffiliates Pro features here →</a></p>' , 'thirstyaffiliates' ) , $tapro_url );
            ?>
        </div>

        <script>
        ( function( $ ) {
            $( '.ta_tapro_admin_notice' ).on( 'click' , '.notice-dismiss' , function() {
                $.ajax( ajaxurl , {
                    type: 'POST',
                    data: { action: 'ta_dismiss_marketing_notice' , notice : 'tapro_notice' }
                } );
            } );
        } )( jQuery );
        </script>
        <?php
    }

    /**
     * AJAX dismiss marketing notice.
     *
     * @since 3.2.5
     * @access public
     */
    public function ajax_dismiss_marketing_notice() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates' ) );
        elseif ( ! isset( $_POST[ 'notice' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Missing required post data' , 'thirstyaffiliates' ) );
        else {

            $notice = sanitize_text_field( $_POST[ 'notice' ] );

            switch( $notice ) {

                case 'tapro_notice' :
                    $option = Plugin_Constants::SHOW_TAPRO_NOTICE;
                    break;
                case 'enable_js_redirect_notice' :
                    $option = 'ta_show_enable_js_redirect_notice';
                    break;
                default :
                    $option = apply_filters( 'ta_dismiss_marketing_notice_option' , null );
                    break;
            }

            if ( $option ) update_option( $option , 'no' );
            $response = array( 'status' => 'success' );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Add the Pro Features menu link
     *
     * @since 3.0.0
     * @access public
     */
    public function add_pro_features_menu_link() {

        if ( !is_plugin_active( 'thirstyaffiliates-pro/thirstyaffiliates-pro.php' ) && current_user_can( 'manage_options' ) ) {

            global $submenu;

            array_push( $submenu['edit.php?post_type=thirstylink'] , array( '<div id="spfmlt">Pro Features →</div>' , 'manage_options', 'https://thirstyaffiliates.com/pricing/?utm_source=Free%20Plugin&utm_medium=Pro&utm_campaign=Admin%20Menu' ) );

        }

    }

    /**
     * Add the Pro Features menu link target
     *
     * @since 3.0.0
     * @access public
     */
    public function add_pro_features_menu_link_target() {

        ?>
        <script type="text/javascript">
        jQuery(document).ready( function($) {
            $( '#spfmlt' ).parent().attr( 'target' , '_blank' );
        });
        </script>
        <?php

    }

    /**
     * Display enable js redirect notice.
     *
     * @since 3.3.0
     * @since 3.3.5 Only show notice when statistics module is enabled.
     * @access public
     */
    public function display_enable_js_redirect_notice() {

        if ( get_option( 'ta_enable_stats_reporting_module' , 'yes' ) !== 'yes' ) return;

        $screen = get_current_screen();

        $post_type = get_post_type();
        if ( !$post_type && isset( $_GET[ 'post_type' ] ) )
            $post_type = $_GET[ 'post_type' ];

        if ( ! is_admin() || ! current_user_can( 'manage_options' ) || $post_type !== Plugin_Constants::AFFILIATE_LINKS_CPT || get_option( 'ta_show_enable_js_redirect_notice' , 'yes' ) !== 'yes' )
            return;

        ?>
        <div class="notice notice-error is-dismissible ta_enable_javascript_redirect_notice">
            <?php
                echo _e( "<h4>Enable Enhanced Javascript Redirect</h4>
                     <p>ThirstyAffiliates version 3.2.5 introduces a new method of redirecting via javascript which will only run on your website's frontend.
                     We've added this so the plugin can provide more accurate tracking data of your affiliate link clicks.
                     This feature is turned on automatically for <strong>new installs</strong>, but for this install we would like to give you the choice of enabling the feature or not.</p>" , 'thirstyaffiliates' );
            ?>
            <p>
                <button type="button" class="button-primary" id="ta_enable_js_redirect_trigger">
                    <?php _e( 'Enable enhanced javascript redirect feature' , 'thirstyaffiliates' ); ?>
                </button>
            </p>
        </div>
        <script type="text/javascript">
            ( function( $ ) {

                // dismiss notice.
                $( '.ta_enable_javascript_redirect_notice' ).on( 'click' , '.notice-dismiss' , function() {
                    $.ajax( ajaxurl , {
                        type: 'POST',
                        data: { action: 'ta_dismiss_marketing_notice' , notice : 'enable_js_redirect_notice' }
                    } );
                } );

                // trigger enable enhanced javascript redirect feature
                $( '.ta_enable_javascript_redirect_notice' ).on( 'click' , '#ta_enable_js_redirect_trigger' , function() {
                    $( '.ta_enable_javascript_redirect_notice .notice-dismiss' ).trigger( 'click' );
                    $.ajax( ajaxurl , {
                        type: 'POST',
                        data: { action: 'ta_enable_js_redirect' }
                    } );
                } );

            } )( jQuery );
        </script>
        <?php
    }

    /**
     * Hide Enable JS redirect setting notice when setting value is changed.
     * 
     * @since 3.3.0
     * @access public
     * 
     * @param string $value Option value.
     * @return string Filtered option value. 
     */
    public function hide_notice_on_enable_js_redirect_setting_change( $value ) {

        update_option( 'ta_show_enable_js_redirect_notice' , 'no' );
        return $value;
    }

    /**
     * AJAX enable JS redirect setting.
     *
     * @since 3.2.5
     * @access public
     */
    public function ajax_enable_js_redirect() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates' ) );
        elseif ( ! current_user_can( 'manage_options' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates' ) );
        else {

            update_option( 'ta_enable_javascript_frontend_redirect' , 'yes' );
            $response = array( 'status' => 'success' );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Add advanced feautures marketing metabox in the sidebar.
     *
     * @since 3.3.0
     * @access public
     *
     * @param array $metabox TA registered metaboxes.
     * @return array Filtered TA registered metaboxes.
     */
    public function add_advanced_features_marketing_metabox( $metaboxes ) {

        if ( ! $this->_helper_functions->is_plugin_active( 'thirstyaffiliates-pro/thirstyaffiliates-pro.php' ) ) {

            $metaboxes[] = array(
                'id'       => 'ta-advanced-features-metabox',
                'title'    => __( 'Advanced Features', 'thirstyaffiliates' ),
                'cb'       => array( $this , 'advanced_features_marketing_metabox_cb' ),
                'sort'     => 40,
                'priority' => 'default'
            );
        }

        return $metaboxes;
    }

    /**
     * Display "Advanced Features" metabox
     *
     * @since 3.3.0
     * @access public
     *
     * @param WP_Post $post Affiliate link WP_Post object.
     */
    public function advanced_features_marketing_metabox_cb( $post ) {

        $url = esc_url( 'https://thirstyaffiliates.com/pricing/?utm_source=Free%20Plugin&utm_medium=Pro&utm_campaign=Sidebar' );
        $img = esc_url( $this->_constants->IMAGES_ROOT_URL() . 'sidebar.jpg' );
        echo '<a href="' . $url . '" target="_blank"><img src="' . $img . '"></a>';
    }
    
    
    
    
    /*
    |--------------------------------------------------------------------------
    | Fulfill Implemented Interface Contracts
    |--------------------------------------------------------------------------
    */

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 3.0.0
     * @access public
     * @implements ThirstyAffiliates\Interfaces\Activatable_Interface
     */
    public function activate() {

        wp_schedule_single_event( time() + 1209600 , Plugin_Constants::CRON_REQUEST_REVIEW );
        wp_schedule_single_event( time() + 172800 , Plugin_Constants::CRON_TAPRO_NOTICE );

    }

    /**
     * Execute codes that needs to run on plugin initialization.
     *
     * @since 3.0.0
     * @access public
     * @implements ThirstyAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        add_action( 'wp_ajax_ta_request_review_response'    , array( $this , 'ajax_request_review_response' ) );
        add_action( 'wp_ajax_ta_dismiss_tapro_admin_notice' , array( $this , 'ajax_dismiss_tapro_admin_notice' ) );
        add_action( 'wp_ajax_ta_dismiss_marketing_notice'   , array( $this , 'ajax_dismiss_marketing_notice' ) );
        add_action( 'wp_ajax_ta_enable_js_redirect'         , array( $this , 'ajax_enable_js_redirect' ) );

    }

    /**
     * Execute Marketing class.
     *
     * @since 3.0.0
     * @access public
     * @implements ThirstyAffiliates\Interfaces\Model_Interface
     */
    public function run() {

        add_action( Plugin_Constants::CRON_REQUEST_REVIEW , array( $this , 'flag_show_review_request' ) );
        add_action( Plugin_Constants::CRON_TAPRO_NOTICE , array( $this , 'flag_show_tapro_notice' ) );
        add_action( 'admin_notices' , array( $this , 'show_review_request_notice' ) );
        add_action( 'admin_notices' , array( $this , 'show_tapro_admin_notice' ) );
        add_action( 'admin_notices' , array( $this , 'display_enable_js_redirect_notice' ) );
        add_action( 'admin_menu' , array( $this , 'add_pro_features_menu_link' ) , 20 );
        add_action( 'admin_head', array( $this , 'add_pro_features_menu_link_target' ) );
        add_filter( 'option_ta_enable_javascript_frontend_redirect' , array( $this , 'hide_notice_on_enable_js_redirect_setting_change' ) );
        add_filter( 'ta_register_side_metaboxes' , array( $this , 'add_advanced_features_marketing_metabox' ) );
    }

}
