<?php
/*
Plugin Name: Only one device login limit
Plugin URI: http://codersantosh.com
Description: Limit login to one device at a time for a user
Version: 1.1
Author: CoderSantosh
Author URI: http://codersantosh.com
License: GPL
Copyright: Santosh Kunwar (CoderSantosh)
*/

/*Make sure we don't expose any info if called directly*/
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

if ( ! class_exists( 'Coder_Limit_Login' ) ){
    /**
     * Class for Limit login to one device at a time for a user
     *
     * @package Coder Limit Framework
     * @since 1.0
     */
    class Coder_Limit_Login{
        /*Basic variables for class*/

        /**
         * Variable to hold this plugin version
         *
         * @var string
         * @access protected
         * @since 1.0
         *
         */
        private  $coder_limit_login_version = '1.0';

        /**
         * Variable to hold this plugin minimum wp version
         *
         * @var string
         * @access protected
         * @since 1.0
         *
         */
        protected $coder_limit_login_minimum_wp_version = '3.1';

        /**
         * Coder_Limit_Login Plugin instance.
         *
         * @see coder_get_instance()
         * @var object
         * @access protected
         * @since 1.0
         *
         */
        protected static $coder_instance = NULL;

        /**
         * Variable to hold this framework url
         *
         * @var string
         * @access protected
         * @since 1.0
         *
         */
        protected $coder_limit_login_url = '';

        /**
         * Variable to hold this framework path
         *
         * @var string
         * @access protected
         * @since 1.0
         *
         */
        protected $coder_limit_login_path = '';

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
         * Stored logout in minutes
         *
         * @var int
         * @access protected
         * @since 1.0
         *
         */
        protected $coder_logout_duration = 30;

        /**
         * Stored logout in seconds
         *
         * @var int
         * @access protected
         * @since 1.0
         *
         */
        protected $coder_logout_duration_seconds = 1800;

        /**
         * Stored already login message
         *
         * @var string
         * @access protected
         * @since 1.0
         *
         */
        protected $coder_already_login_message = '<h1>The User with this username already login! </h1>';

        /**
         * Stored logout message
         *
         * @var string
         * @access protected
         * @since 1.0
         *
         */
        protected $coder_force_logout_message = '<h1>The User with this username already logout ! Please Login to Continue. </h1>';

        /**
         * check if admin is enable
         *
         * @var boolean
         * @access protected
         * @since 1.0
         *
         */
        protected $coder_enable_admin = false;

        /**
         * Access this plugin’s working coder_instance
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
         * Used for regular plugin work
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        public function coder_limit_login_init() {

            /*Basic variables initialization with filter*/
            $this->coder_limit_login_url = plugin_dir_url( __FILE__ ) ;
            $this->coder_limit_login_path = plugin_dir_path( __FILE__ );
            $this->coder_limit_login_url = apply_filters( 'coder_limit_login_url', $this->coder_limit_login_url );
            $this->coder_limit_login_path = apply_filters( 'coder_limit_login_path', $this->coder_limit_login_path );
            $this->coder_limit_login_options =  apply_filters( 'coder_limit_login_options', get_option( 'coder-limit-login-options' ));
            if(isset($this->coder_limit_login_options['coder_logout_duration'])){
                $this->coder_logout_duration =  apply_filters( 'coder_logout_duration', $this->coder_limit_login_options['coder_logout_duration'] );
            }
            if(isset($this->coder_limit_login_options['coder_already_login_message'])){
                $this->coder_already_login_message =  apply_filters( 'coder_logout_duration', $this->coder_limit_login_options['coder_already_login_message'] );
            }
            if(isset($this->coder_limit_login_options['coder_force_logout_message'])){
                $this->coder_force_logout_message =  apply_filters( 'coder_logout_duration', $this->coder_limit_login_options['coder_force_logout_message'] );
            }

            $this->coder_logout_duration_seconds =  (int)$this->coder_logout_duration * 60;
            if(isset($this->coder_limit_login_options['coder_enable_admin'])){
                $this->coder_enable_admin =  apply_filters( 'coder_enable_admin', $this->coder_limit_login_options['coder_enable_admin']);
            }
            /*load translation*/
            add_action('init', array($this,'coder_load_textdomain') , 12);

            /*Hook before any function of class start */
            do_action( 'coder_limit_login_before');

            /*Enqueue necessary styles and scripts.*/
            add_action( 'wp_enqueue_scripts', array($this,'coder_limit_login_enqueue_scripts') ,12 );
            add_action( 'admin_enqueue_scripts', array($this,'coder_limit_login_enqueue_scripts') ,12 );

            /*check if user is already login*/
            add_action( 'wp_login', array($this,'coder_check_if_already_user_active') ,0 ,1 );


            /*set current user active time*/
            add_action( 'init', array($this,'coder_set_current_user_active_time') ,0 );

            /*make sure user is logout*/
            add_action( 'wp_logout', array($this,'coder_set_logout') ,0 ,1 );

            /*user profile show/edit*/
            add_action( 'show_user_profile', array($this,'coder_add_custom_user_profile_fields') ,12 ,1 );
            add_action( 'edit_user_profile', array($this,'coder_add_custom_user_profile_fields') ,12 ,1 );

            /*user profile save*/
            add_action( 'personal_options_update', array($this,'coder_save_custom_user_profile_fields') ,12  );
            add_action( 'edit_user_profile_update', array($this,'coder_save_custom_user_profile_fields') ,12 );

            /*adding column to user listing*/
            add_filter( 'manage_users_columns', array($this,'coder_modify_user_columns') ,12 );

            /*adding content to new custom column*/
            add_filter( 'manage_users_custom_column', array($this,'coder_modify_user_column_content') ,12 ,3 );

            /*make column sortable*/
            add_filter( 'manage_users_sortable_columns', array($this,'coder_make_sortable_column') ,12 );

            /*Actual function to make column sortable*/
            add_filter( 'pre_user_query', array($this,'coder_sortable_column_query') ,12 );

            /*wp ajax handling*/
            add_action( 'wp_ajax_coder_destroy_sessions_ajax', array($this,'coder_destroy_sessions_ajax_callback') ,12 );

            /*Setting admin menu*/
            require_once trailingslashit( $this->coder_limit_login_path ) . 'inc/coder-admin-menu.php';

            /*Hook before any function of class end */
            do_action( 'coder_limit_login_after');
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
         * Load_textdomain
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        public function coder_load_textdomain(){
            /*Added filter for text domain path*/
            $coder_limit_login_textdomain_path = apply_filters( 'coder_limit_login_textdomain_path', $this->coder_limit_login_path );
            load_textdomain( 'coder_limit_login', $coder_limit_login_textdomain_path . '/languages/' . get_locale() .'.mo' );
        }


        /**
         * Enqueue style and scripts at Theme Limit
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        public function coder_limit_login_enqueue_scripts(){

            wp_register_style( 'coder-limit-login-style', $this->coder_limit_login_url . '/assets/css/coder-limit-login.css', false, $this->coder_limit_login_version );
            wp_enqueue_style( 'coder-limit-login-style' );

            /*localizing the script start*/
            /*Register the script*/
            wp_register_script( 'coder-limit-login', $this->coder_limit_login_url . '/assets/js/coder-limit-login.js', array( 'jquery' ), $this->coder_limit_login_version, true );
            /*Localize the script with new data*/
            $coder_customizer_localization_array = array(
                'coder_limit_login_url' => $this->coder_limit_login_url
            );
            wp_localize_script( 'coder-limit-login', 'coder_limit_login', $coder_customizer_localization_array );
            /*enqueue script with localized data.*/

            wp_enqueue_script( 'coder-limit-login' );
            /*localizing the script end*/
        }

        /**
         * Logout function
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        public function coder_force_logout($coder_logout_message ){
            nocache_headers();
            wp_clear_auth_cookie();
            do_action('wp_logout');
            wp_die($coder_logout_message, '', array( 'back_link' => true ));
        }

        /**
         * Coder Allow login
         *
         * @access public
         * @since 1.0
         *
         * @return int
         *
         */
        public function coder_allow_login( $coder_login_user_id){
            $coder_current_time = current_time( 'timestamp');
            update_user_meta( $coder_login_user_id, 'coder_first_time_login', 'no' );
            update_user_meta( $coder_login_user_id, 'coder_is_logout', 'no' );
            update_user_meta( $coder_login_user_id, 'coder_last_active_time', $coder_current_time );
            return $coder_login_user_id;
        }

        /**
         * Logout function
         *
         * @access public
         * @since 1.1
         *
         * @return void
         *
         */
        public function coder_destroy_sessions ($coder_user_id){
            if(class_exists('WP_Session_Tokens')){
                $coder_sessions = WP_Session_Tokens::get_instance( $coder_user_id );
                if ( $coder_user_id === get_current_user_id() ) {
                    $coder_sessions->destroy_others( wp_get_session_token() );
                } else {
                    $coder_sessions->destroy_all();
                }
            }

        }
        /**
         * Function to handle callback ajax
         *
         * @since 1.1
         *
         * @param null
         * @return null
         *
         */
        function coder_destroy_sessions_ajax_callback(){
            $coder_user_id = $_POST['user_id'];
            $this->coder_destroy_sessions($coder_user_id);
            _e('User Is InActive', 'coder_limit_login');
            exit;
        }
        /**
         * Store Last active time
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        public function coder_set_current_user_active_time(){

            $coder_login_user_data = wp_get_current_user();
            $coder_login_user_id = $coder_login_user_data->ID;
            if( 'on' != $this->coder_enable_admin && in_array('administrator',$coder_login_user_data->roles) ){
                $this->coder_allow_login( $coder_login_user_id );
                return;
            }
            if ( is_user_logged_in() ) {
                /*destroying other session*/
                $this->coder_destroy_sessions(get_current_user_id());

                $coder_current_time = current_time( 'timestamp');
                $coder_last_active_time = get_user_meta( $coder_login_user_id, 'coder_last_active_time', 'true' );

                update_user_meta( $coder_login_user_id, 'coder_last_active_time', $coder_current_time );
                $coder_is_logout = get_user_meta( $coder_login_user_id, 'coder_is_logout', 'true' );

                update_user_meta( $coder_login_user_id, 'coder_first_time_login', 'no' );

                if( 'yes' == $coder_is_logout ){
                    $this->coder_force_logout( $this->coder_force_logout_message );
                }
                elseif( ($coder_current_time - $coder_last_active_time) > $this->coder_logout_duration_seconds ){
                    $this->coder_force_logout( $this->coder_force_logout_message );
                }
                else {
                    //nothing to do
                }

            }

        }

        /**
         * Check if user already active
         *
         * @access public
         * @since 1.0
         *
         * @return void|array
         *
         */
        function coder_check_if_already_user_active( $coder_user_login_name ) {
            $coder_login_user_data = get_user_by( 'login', $coder_user_login_name );

            $coder_login_user_id = $coder_login_user_data->ID;

            $coder_first_time_login = get_user_meta( $coder_login_user_id, 'coder_first_time_login', 'true' );
            $coder_last_active_time = get_user_meta( $coder_login_user_id, 'coder_last_active_time', 'true' );
            $coder_is_logout = get_user_meta( $coder_login_user_id, 'coder_is_logout', 'true' );
            $coder_current_time = current_time( 'timestamp');

            if( 'on' != $this->coder_enable_admin && in_array('administrator',$coder_login_user_data->roles) ){
                $this->coder_allow_login( $coder_login_user_id );
                return $coder_user_login_name;
            }
            elseif( 'no' != $coder_first_time_login ) {
                $this->coder_allow_login( $coder_login_user_id );
                return $coder_user_login_name;
            }
            elseif( 'yes' == $coder_is_logout ){
                $this->coder_allow_login( $coder_login_user_id );
                return $coder_user_login_name;
            }
            elseif( $coder_current_time - $coder_last_active_time > $this->coder_logout_duration_seconds ){
                $this->coder_allow_login( $coder_login_user_id );
                return $coder_user_login_name;
            }
            else{
                if(class_exists('WP_Session_Tokens')){
                    $coder_sessions = WP_Session_Tokens::get_instance( $coder_login_user_id );
                    /*Get all sessions of a user.*/

                    $coder_get_sessions = $coder_sessions->get_all();
                    if(count($coder_get_sessions) > 1){
                        $this->coder_force_logout($this->coder_already_login_message );
                    }
                }
                else{
                    $this->coder_force_logout($this->coder_already_login_message );
                }
            }
        }

        /**
         * Set value when user logout
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        function coder_set_logout(){
            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;
            update_user_meta( $user_id, 'coder_is_logout', 'yes' );
        }

        /**
         * Add fields while showing/editing user profile
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        function coder_add_custom_user_profile_fields( $coder_login_user_data ) {
            ?>
            <h3><?php _e('Is User Active', 'coder_limit_login'); ?></h3>
            <table class="form-table">
                <tr>
                    <th>
                        <label><?php _e('Is User Active', 'coder_limit_login'); ?></label>
                    </th>
                    <td id="coder-login-logout-status">
                        <?php
                        $coder_is_logout = get_user_meta( $coder_login_user_data->ID, 'coder_is_logout', 'true' );
                        if($coder_is_logout != 'yes'){
                            _e('Currently User Is Active', 'coder_limit_login');?>
                            <br>
                            <label for="coder_is_logout">
                                <input type="checkbox" value="yes" id="coder_is_logout" name="coder_is_logout">
                                <?php _e('Force User to Logout', 'coder_limit_login'); ?>
                            </label>
                        <?php
                        }
                        else{
                            _e('User Is InActive', 'coder_limit_login');
                        }
                        ?>
                    </td>
                </tr>
            </table>
        <?php
        }

        /**
         * Save added custom fields
         *
         * @access public
         * @since 1.0
         *
         * @return void|integer
         *
         */
        function coder_save_custom_user_profile_fields( $user_id ) {
            if ( !current_user_can( 'edit_user', $user_id ) || !isset($_POST['coder_is_logout']))
                return $user_id;
            update_user_meta( $user_id, 'coder_is_logout', $_POST['coder_is_logout'] );

            $this->coder_destroy_sessions($user_id);
        }

        /**
         * Adding additional columns to the users.php admin page
         *
         * @access public
         * @since 1.0
         *
         * @return array
         *
         */
        function coder_modify_user_columns($column) {
            $column['coder_is_logout'] = __("User Status",'coder_limit_login');//the new column
            return $column;
        }

        /**
         * adding content to new custom column
         *
         * @access public
         * @since 1.0
         *
         * @return array
         *
         */
        function coder_modify_user_column_content( $val, $column_name, $user_id ) {
            $user = get_userdata($user_id);
            switch ($column_name) {
                case 'coder_is_logout':
                    if($user->coder_is_logout == 'no'){
                        $coder_message = sprintf( __( 'Currently Login. %s Last Active Time : %s', 'coder_limit_login' ), '<br />',date('Y-m-d, H:i:s', $user->coder_last_active_time) );
                    }
                    elseif(empty($user->coder_last_active_time)){
                        $coder_message = sprintf( __( 'User Currently Inactive. %s Last Active Time : Unavailable', 'coder_limit_login' ), '<br />' );
                    }
                    else{
                        $coder_message = sprintf( __( 'User Currently Inactive. %s Last Active Time : %s', 'coder_limit_login' ), '<br />', date('Y-m-d, H:i:s', $user->coder_last_active_time) );
                    }
                    return $coder_message;
                    break;
            }
            return $val;
        }

        /**
         * Make the new column sortable
         *
         * @access public
         * @since 1.0
         *
         * @return array
         *
         */
        function coder_make_sortable_column( $columns ) {
            $columns['coder_is_logout'] = 'coder_is_logout';
            return $columns;
        }

        /**
         * Set query to sort the new column
         *
         * @access public
         * @since 1.0
         *
         * @return void
         *
         */
        function coder_sortable_column_query($userquery){
            if( ! is_admin() ){
                return;
            }
            if('coder_is_logout'== $userquery->query_vars['orderby']) {//check if coder_is_logout is the column being sorted
                global $wpdb;
                $userquery->query_from .= " LEFT OUTER JOIN $wpdb->usermeta AS alias ON ($wpdb->users.ID = alias.user_id) ";//note use of alias
                $userquery->query_where .= " AND alias.meta_key = 'coder_is_logout' ";//which meta are we sorting with?
                $userquery->query_orderby = " ORDER BY alias.meta_value ".($userquery->query_vars["order"] == "ASC" ? "asc " : "desc ");//set sort order
            }
        }

        /**
         * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
         *
         * @access public static
         * @since 1.0
         *
         * @return void
         *
         */
        public static function plugin_activation() {
            $coder_default_options = array(
                'coder_logout_duration'=>30,
                'coder_enable_admin' => false,
                'coder_already_login_message' => sprintf( __( '%sThe User with this username already login!%s', 'coder_limit_login' ), '<h1>','</h1>' ),
                'coder_force_logout_message' => sprintf( __( '%sThe User with this username already logout ! Please Login to Continue.%s', 'coder_limit_login' ), '<h1>','</h1>' ),
            );
            if( get_option( 'coder-limit-login-options' ) ) {
                update_option( 'coder-limit-login-options', $coder_default_options );
            } else {
                add_option( 'coder-limit-login-options', $coder_default_options );
            }
        }

        /**
         * Removes all connection options
         *
         * @access public static
         * @since 1.0
         *
         * @return void
         *
         */
        public static function plugin_deactivation( ) {
            delete_option( 'coder-limit-login-options' );
        }
    } /*END class Coder_Limit_Login*/

    /*Initialize class in after_setup_theme*/
    add_action( 'after_setup_theme', array ( Coder_Limit_Login::coder_get_instance(), 'coder_limit_login_init' ));

    register_activation_hook( __FILE__, array( 'Coder_Limit_Login', 'plugin_activation' ) );
    register_deactivation_hook( __FILE__, array( 'Coder_Limit_Login', 'plugin_deactivation' ) );

}/*END if(!class_exists('Coder_Limit_Login'))*/