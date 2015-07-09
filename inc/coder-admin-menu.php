<?php
if ( ! class_exists( 'Coder_Limit_Login_Admin_Menu_Setting' ) ){
    /**
     * Class for Limit login Admin Menu and Setting
     *
     * @package Coder Limit Login Framework
     * @subpackage Coder Limit Login Admin Menu Setting
     * @since 1.0
     */
    class Coder_Limit_Login_Admin_Menu_Setting{
        /*Basic variables for class*/

        /**
         * Option saved value.
         *
         * @var array
         * @access protected
         * @since 1.0
         *
         */
        protected $coder_limit_login_options = array();

        /**
         * Coder_Limit_Login_Admin_Menu_Setting instance.
         *
         * @see coder_get_instance()
         * @var object
         * @access protected
         * @since 1.0
         *
         */
        protected static $coder_instance = NULL;


        /**
         * Access Coder_Limit_Login_Admin_Menu_Setting working coder_instance
         *
         * @access public
         * @since 1.0
         * @return object of this class
         */
        public static function coder_get_instance() {
            NULL === self::$coder_instance and self::$coder_instance = new self;
            return self::$coder_instance;
        }

        /**
         * Used for regular plugin work.
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        public function coder_admin_menu_init() {

            /*Hook before any function of class start */
            do_action( 'coder_admin_menu_before');

            $this->coder_limit_login_options = get_option( 'coder-limit-login-options' );

            /*Adding menu page*/
            add_action( 'admin_menu', array($this,'coder_admin_submenu') ,12 );

            /*Adding coder register setting*/
            add_action( 'admin_init', array($this,'coder_register_setting') ,12 );

            /*Hook before any function of class end */
            do_action( 'coder_admin_menu_after');
        }

        /**
         * Constructor. Intentionally left empty and public.
         *
         * @access public
         * @since 1.0
         *
         */
        public function __construct(){ }

        /**
         * Add submenu in general options
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        public function coder_admin_submenu() {
            add_submenu_page(
                "options-general.php",
                __('Coder limit login','coder_limit_login'),
                __('Coder limit login','coder_limit_login'),
                'manage_options',
                'coder-limit-login-setting',
                array($this, 'coder_submenu_page' )
            );
            /*add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );*/
        }
        /**
         * Add form fields in Coder limit login page
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        public function coder_submenu_page() {
            ?>
            <div class="wrap">
                <h2><?php _e('Coder limit login Settings','coder_limit_login');?></h2>
                <br />
                <form method="post" enctype="multipart/form-data" action="options.php">
                    <?php
                    settings_fields( 'coder-limit-login-optiongroup' );
                    do_settings_sections( 'coder_limit_login_page' );
                    submit_button();
                    ?>
                </form>
            </div>
        <?php
        }

        /**
         * Add setting sections and fields
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        public function coder_register_setting(){
            register_setting(
                'coder-limit-login-optiongroup',
                'coder-limit-login-options',
                array($this, 'coder_sanitize_callback' )
            );
            /*register_setting( $option_group, $option_name, $sanitize_callback )*/

            add_settings_section(
                'coder-limit-login-general',
                __('Coder limit login general setting','coder_limit_login'),
                array($this, 'coder_print_section_info' ),
                'coder_limit_login_page'
            );
            /*add_settings_section( $id, $title, $callback, $page )*/

            add_settings_field(
                'coder_logout_duration',
                '<label for="coder_logout_duration">'.__('Auto Logout Duration','coder_limit_login').'</label>',
                array($this, 'coder_logout_duration' ),
                'coder_limit_login_page',
                'coder-limit-login-general'
            );
            // add_settings_field ( id , field title , callback function , menu page slug , settings section , [arguments] )
            
            add_settings_field(
                'coder_enable_admin',
                '<label for="coder_enable_admin">'.__('Enable for Admin','coder_limit_login').'</label>',
                array($this, 'coder_enable_admin'),
                'coder_limit_login_page',
                'coder-limit-login-general'
            );

            add_settings_field(
                'coder_force_logout_message',
                '<label for="coder_force_logout_message">'.__('Already logout message','coder_limit_login').'</label>',
                array($this, 'coder_force_logout_message'),
                'coder_limit_login_page',
                'coder-limit-login-general'
            );

            add_settings_field(
                'coder_already_login_message',
                '<label for="coder_already_login_message">'.__('Already login message','coder_limit_login').'</label>',
                array($this, 'coder_already_login_message'),
                'coder_limit_login_page',
                'coder-limit-login-general'
            );
        }

        /**
         * Validate options values
         *
         * @access public
         * @since 1.0
         *
         * @param object $coder_input
         * @return Array
         *
         */
        public function coder_sanitize_callback($coder_input){
//            $coder_valid_input = array();
//            $coder_valid_input['coder_logout_duration'] = intval($coder_input['coder_logout_duration']);
            return $coder_input;
        }

        /**
         * Display section info
         *
         * @access public
         * @since 1.0
         *
         * @param null
         * @return void
         *
         */
        public function coder_print_section_info(){
            echo '<hr />';
        }

        /**
         * Display coder logout duration fields
         *
         * @access public
         * @since 1.0
         *
         * @param null
         * @return void
         *
         */
        public function coder_logout_duration(){
            $coder_value = isset( $this->coder_limit_login_options['coder_logout_duration'] ) ? esc_attr( $this->coder_limit_login_options['coder_logout_duration']) : '';
            echo '<input type="text" id="coder_logout_duration" name="coder-limit-login-options[coder_logout_duration]" value="'.$coder_value.'" /> minutes';
        }

        /**
         * Display coder enable admin fields
         *
         * @access public
         * @since 1.0
         *
         * @param null
         * @return void
         *
         */
        public function coder_enable_admin(){
            $coder_value = isset( $this->coder_limit_login_options['coder_enable_admin'] ) ? esc_attr( $this->coder_limit_login_options['coder_enable_admin']) : '';
            echo '<input type="checkbox" id="coder_enable_admin" name="coder-limit-login-options[coder_enable_admin]" '.checked(!empty($coder_value), true, false).' />';
        }

        /**
         * Display coder force logout info fields
         *
         * @access public
         * @since 1.0
         *
         * @param null
         * @return void
         *
         */
        public function coder_force_logout_message(){
            $coder_value = isset( $this->coder_limit_login_options['coder_force_logout_message'] ) ? esc_attr( $this->coder_limit_login_options['coder_force_logout_message']) : '';
            echo "<textarea name='coder-limit-login-options[coder_force_logout_message]' id='coder-limit-login-options[coder_force_logout_message]' cols='50' rows='8'>{$coder_value}</textarea>";
        }

        /**
         * Display coder force logout info fields
         *
         * @access public
         * @since 1.0
         *
         * @param null
         * @return void
         *
         */
        public function coder_already_login_message(){
            $coder_value = isset( $this->coder_limit_login_options['coder_already_login_message'] ) ? esc_attr( $this->coder_limit_login_options['coder_already_login_message']) : '';
            echo "<textarea name='coder-limit-login-options[coder_already_login_message]' id='coder-limit-login-options[coder_already_login_message]' cols='50' rows='8'>{$coder_value}</textarea>";
        }



    } /*END class Coder_Limit_Login_Admin_Menu_Setting*/

    /*Initialize class after admin_init*/
    $coder_admin_menu =  new Coder_Limit_Login_Admin_Menu_Setting();
    $coder_admin_menu->coder_admin_menu_init();
//    add_action( 'admin_init', array ( Coder_Limit_Login_Admin_Menu_Setting::coder_get_instance(), 'coder_admin_menu_init' ));

}/*END if(!class_exists('Coder_Limit_Login_Admin_Menu_Setting'))*/