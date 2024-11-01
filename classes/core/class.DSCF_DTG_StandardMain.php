<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 8/17/17
 * Time: 3:49 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_StandardMain{

    static $classname = "";

    static $cron_handlers = array();

    static $widgets = array();

    static $shortcode_handlers = array();

    static $cpt_handlers = array();

    static $metaboxes = array();

    static $ajax_public_mappings = array();
    static $ajax_admin_mappings = array();

    static $enqueue_css_public = array();
    static $enqueue_script_public = array();
    static $enqueue_css_admin = array();
    static $enqueue_script_admin = array();

    const CURRENT_VERSION = 1;      // Database default version number

    // Enqueue Resource Types
    const ERT_PUBLIC_CSS = 1;
    const ERT_PUBLIC_SCRIPT = 2;
    const ERT_ADMIN_CSS = 3;
    const ERT_ADMIN_SCRIPT = 4;

    const AMT_PUBLIC = 1;
    const AMT_ADMIN = 2;

    const SETTINGS_PLUGIN_ENABLED = "plugin_enabled";
    const SETTINGS_SLUG_BASE = "slug_base";
    const SLUG_BASE = "plugin_slug_base";

    const SWITCH_ENQUEUE_PUBLIC_RESOURCES_MAIN_CSS = true;
    const SWITCH_ENQUEUE_ADMIN_RESOURCES_ADMIN_JS = true;
    const SWITCH_USE_SETTINGS_OBJECT = true;
    const SWITCH_USE_DATABASE = true;



    /*
     * WordPress Action Hook for init :: Generally the first action available to the Plugins
     */
    public static function wpActionInit(){

        // Some basic setup validations

        // Make sure the classname has been properly setup
        if(empty(static::$classname)){
            DSCF_DTG_Utilities::logError("Static DSCF_DTG_StandardMain::classname is empty and must be set");
        }

        // Register common hooks and filters
        add_action( 'admin_enqueue_scripts', array(static::$classname, 'wpActionAdminEnqueueScripts'));
        add_action( 'wp_enqueue_scripts', array(static::$classname,'wpActionEnqueueScripts') );
        add_action( 'admin_menu', array(static::$classname, 'wpActionAdminMenu'));

        // Add a shortcode handler
        $dscfl = 'wpdevhub-dtg_';
        $dscfl = str_replace("_","",$dscfl);
        add_shortcode( $dscfl , array( static::$classname , 'shortcodeHandler' ) );

        // LEGACY SUPPORT FOR RENDERING OF OLD SHORTCODES
        $dscfl = 'dimbal-dtg_';
        $dscfl = str_replace("_","",$dscfl);
        add_shortcode( $dscfl , array( static::$classname , 'shortcodeHandler' ) );

        // Activation / Deactivation hooks
        register_activation_hook(__FILE__, array(static::$classname, 'wpActionActivate'));
        register_deactivation_hook(__FILE__, array(static::$classname, 'wpActionDeactivate'));

        // Check for a Database Upgrade
        if(static::SWITCH_USE_DATABASE){
            DSCF_DTG_Utilities::checkForUpgrade(static::CURRENT_VERSION, array(static::$classname,'installDatabase'));
        }

        // Register the settings
        if(static::SWITCH_USE_SETTINGS_OBJECT){
            DSCF_DTG_StandardSetting::initSettings(static::buildSettingsEditorOptions());
        }

        // Make sure the PHP session has been started if not yet already
        DSCF_DTG_Utilities::startSession();

    }

    public static function isPluginEnabled(){
        $isEnabled = DSCF_DTG_StandardSetting::getSetting(static::SETTINGS_PLUGIN_ENABLED);
        if(empty($isEnabled)){
            return false;
        }
        return true;
    }

    public static function getBaseUrl(){
        $url = static::getSlugBase();
        $url = trim($url, '/');
        $url = '/'.$url.'/';
        $url = site_url() . $url;
        return $url;

    }

    public static function getSlugBase(){
        $slug = DSCF_DTG_StandardSetting::getSetting(static::SETTINGS_SLUG_BASE);
        if(empty($slug)){
            $slug = static::SLUG_BASE;
        }
        return $slug;
    }

    /*
     * WordPress Action Hook for register_activation_hook
     */
    public static function wpActionActivate(){
        static::installDatabase();

        // Setup any crons
        if(!empty(static::$cron_handlers)){
            foreach(static::$cron_handlers as $key=>$schedule){
                if ( ! wp_next_scheduled( $key ) ) {
                    wp_schedule_event( time(), $schedule, $key );
                }
            }
        }

        // Flush the rewrite rules on activation
        // https://codex.wordpress.org/Function_Reference/register_post_type
        self::flushRewriteRules();

    }

    /*
     * WordPress Action Hook for
     */
    public static function wpActionDeactivate(){

        // Cleanout any crons
        if(!empty(static::$cron_handlers)){
            foreach(static::$cron_handlers as $key=>$schedule){
                $timestamp = wp_next_scheduled( $key );
                if(!empty($timestamp)){
                    wp_unschedule_event( $timestamp, $key );
                }
                wp_clear_scheduled_hook( $key );
            }
        }

        // Flush the rewrite rules on deactivation
        // https://codex.wordpress.org/Function_Reference/register_post_type
        self::flushRewriteRules();

    }


    ////////////////////////////////////////////////////// RESOURCE QUEUEING /////////////////////////////

    public static function addResourceToEnqueue($name, $url, $type){
        switch($type){
            case self::ERT_PUBLIC_CSS:
                static::$enqueue_css_public[$name]=$url;
                break;
            case self::ERT_PUBLIC_SCRIPT:
                static::$enqueue_script_public[$name]=$url;
                break;
            case self::ERT_ADMIN_CSS:
                static::$enqueue_css_admin[$name]=$url;
                break;
            case self::ERT_ADMIN_SCRIPT:
                static::$enqueue_script_admin[$name]=$url;
                break;
        }

    }

    public static function addActionEnqueueScripts(){
        add_action( 'wp_enqueue_scripts', array(static::$classname,'wpActionEnqueueScripts') );
    }

    /*
    * WordPress Action Hook for widgets_init
    */
    public static function wpActionEnqueueScripts(){

        if(static::SWITCH_ENQUEUE_PUBLIC_RESOURCES_MAIN_CSS){
            wp_enqueue_style( WPDEVHUB_CONST_DTG_SLUG.'-main-css', WPDEVHUB_CONST_DTG_URL.'/css/wpdevhub-main.css' );
        }

        foreach(static::$enqueue_css_public as $key=>$url){
            wp_enqueue_style( $key, $url );
        }

        foreach(static::$enqueue_script_public as $key=>$url){
            wp_enqueue_script( $key, $url, array('jquery') );
        }

        static::enqueuePublicResources();

    }

    /*
    * Designed to be overwritten by plugins and define custom resources to enqueue for public pages
    */
    public static function enqueuePublicResources(){}

    /*
    * WordPress Action Hook for widgets_init
    */
    public static function wpActionAdminEnqueueScripts(){

        // Don't forget to localize the data objects we want
        $dimbalVars = array(
            'slug' => WPDEVHUB_CONST_DTG_SLUG,
            'url' => WPDEVHUB_CONST_DTG_URL,
            'url_ajax' => admin_url('admin-ajax.php'),
        );

        if(static::SWITCH_ENQUEUE_PUBLIC_RESOURCES_MAIN_CSS){
            wp_enqueue_style( WPDEVHUB_CONST_DTG_SLUG.'-main-css', WPDEVHUB_CONST_DTG_URL.'/css/wpdevhub-main.css' );
        }

        if(static::SWITCH_ENQUEUE_ADMIN_RESOURCES_ADMIN_JS){
            wp_enqueue_script( WPDEVHUB_CONST_DTG_SLUG.'-admin-js', WPDEVHUB_CONST_DTG_URL.'/js/wpdevhub-admin.js', array( 'jquery' ) );
            wp_localize_script( WPDEVHUB_CONST_DTG_SLUG.'-admin-js', 'DSCF_DTG_vars', $dimbalVars );
        }

        foreach(static::$enqueue_css_admin as $key=>$url){
            wp_enqueue_style( $key, $url );
        }

        foreach(static::$enqueue_script_admin as $key=>$url){
            wp_enqueue_script( $key, $url, array( 'jquery' ) );
            wp_localize_script( WPDEVHUB_CONST_DTG_SLUG.'-admin-js', 'DSCF_DTG_vars', $dimbalVars );
        }

        // Enqueue plugin specific resources
        static::enqueuePublicResources();
        static::enqueueAdminResources();
    }

    /*
     * Designed to be overwritten by plugins and define custom resources to enqueue for admin
     */
    public static function enqueueAdminResources(){}

    // Structure for Install Database
    public static function installDatabase(){

        // If the child classes do not override this function -- then as a minimum add in the version
        add_option( DSCF_DTG_Utilities::buildDatabaseVersionString(), static::CURRENT_VERSION );

    }

    // Structure for Init-ing Settings
    public static function buildSettingsEditorOptions($object=null){
        return array();
    }

    ////////////////////////////////////////////////////// AJAX HANDLERS /////////////////////////////

    public static function addAjaxMapping($name, $callable, $type){
        //DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__."::".__FUNCTION__);
        //$name = strtolower($name);
        switch($type){
            case self::AMT_PUBLIC:
                static::$ajax_public_mappings[$name]=$callable;
                add_action( 'wp_ajax_nopriv_'.$name, array( static::$classname , 'ajaxProcessHandlerWrapper' ) );
                //DSCF_DTG_Utilities::logMessage("Public Mapping Added.  Name[".$name."] Callable[".static::$classname."][ajaxProcessHandlerWrapper] ");
                //break;
            case self::AMT_ADMIN:
                static::$ajax_admin_mappings[$name]=$callable;
                add_action( 'wp_ajax_'.$name, array( static::$classname , 'ajaxProcessHandlerWrapper' ) );
                //DSCF_DTG_Utilities::logMessage("Private Mapping Added.  Name[".$name."] Callable[".static::$classname."][ajaxProcessHandlerWrapper] ");
                break;
        }
    }

    public static function ajaxGetAllHandlerMappings(){
        //DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__."::".__FUNCTION__);
        $public = static::ajaxGetPublicHandlerMappings();
        $private = static::ajaxGetAdminHandlerMappings();
        $handlers = array_merge($public, $private);
        return $handlers;
    }

    /*
     * Designed to be overridden in the following format
     */
    public static function ajaxGetPublicHandlerMappings(){
        //DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__."::".__FUNCTION__);
        return static::$ajax_public_mappings;
    }

    /*
     * Designed to be overridden in the following format
     */
    public static function ajaxGetAdminHandlerMappings(){
        //DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__."::".__FUNCTION__);
        return static::$ajax_admin_mappings;
    }

    public static function ajaxProcessHandlerWrapper(){
        //DSCF_DTG_Utilities::logMessage("Inside ajaxProcessHandlerWrapper: ".print_r(static::ajaxGetAllHandlerMappings(), true));
        DSCF_DTG_Utilities::ajaxProcessHandler(static::ajaxGetAllHandlerMappings());
    }

    ////////////////////////////////////////////////////// CRON HANDLING /////////////////////////////

    public static function addCronHandler($key, $callable, $schedule){

        // register the action
        add_action( $key, $callable);

        // Add it internally for activation and deactivation
        static::$cron_handlers[$key]=$schedule;

    }

    ////////////////////////////////////////////////////// WIDGET HANDLING /////////////////////////////

    public static function addWidget($classname){

        // register the widget
        register_widget( $classname );

        // Add it to the internal list
        static::$widgets[$classname]=$classname;

    }

    ////////////////////////////////////////////////////// SHORTCODE HANDLING /////////////////////////////

    public static function addShortcode($sc_id, $callable){

        static::$shortcode_handlers[$sc_id]=$callable;

    }

    /*
     * Handles a short code call and returns the resulting html content
     */
    public static function shortcodeHandler($atts){

        $html = "";
        $sc_id = 0;
        extract( shortcode_atts( array(
            'sc_id' => 0
        ), $atts ) );

        foreach(static::$shortcode_handlers as $key=>$callable){
            if($sc_id == $key){
                $html = call_user_func_array($callable, array('atts'=>$atts));
            }
        }

        return $html;
    }

    ////////////////////////////////////////////////////// CUSTOM META BOX HANDLING /////////////////////////////

    public static function addMetaBox( $classname ){

        call_user_func(array($classname, 'registerActions'));

        static::$metaboxes[$classname]=$classname;

    }


    ////////////////////////////////////////////////////// CUSTOM POST TYPE HANDLING /////////////////////////////

    public static function addCustomPostType($cpt_key, $classname){

        $cpt_key = strtolower($cpt_key);

        call_user_func(array($classname, 'registerTaxonomies'));

        $object = new $classname();

        add_filter( 'the_content', array($classname, 'wpFilterTheContent'));
        add_filter( 'the_excerpt', array($classname, 'wpFilterTheExcerpt'));
        add_filter( 'get_the_excerpt', array($classname, 'wpFilterTheExcerpt'));
        add_filter( 'get_the_archive_title', array($classname, 'wpFilterGetTheArchiveTitle'));

        static::$cpt_handlers[$cpt_key]=$classname;

    }


    /*
    * Attempt to redirect to the base level page for this plugin
    */
    public static function redirectHome(){
        DSCF_DTG_Utilities::redirect(self::getBaseUrl());
    }

    /*
     * Flush Rewrite rules
     * https://codex.wordpress.org/Function_Reference/register_post_type
     */
    public static function flushRewriteRules(){

        // Note:: Need to make sure custom post types are registered before flushing rewrite rules (see DTG Example)

        // Flush the rules
        flush_rewrite_rules();
    }

}
