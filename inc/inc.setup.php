<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 5/19/15
 * Time: 9:23 PM
 * To change this template use File | Settings | File Templates.
 */

if ( ! defined( 'ABSPATH' ) ) exit();	// sanity check

// Setup the Add Define Routine
if(!function_exists("dimbal_add_define")){
    function dimbal_add_define($key, $val) {
        if (!defined($key)) {
            define($key, $val);
            return true;
        }
        return false;
    }
}

// Define the Constants to control which classes are loaded
dimbal_add_define('WPDEVHUB_CONST_DTG_CLASSES_ZONES', false);
dimbal_add_define('WPDEVHUB_CONST_DTG_CLASSES_VIRTUAL_PAGES', false);
dimbal_add_define('WPDEVHUB_CONST_DTG_CLASSES_BROWSER', false);
dimbal_add_define('WPDEVHUB_CONST_DTG_CLASSES_DIMBALCOM', false);
dimbal_add_define('WPDEVHUB_CONST_DTG_CLASSES_CHART', false);

/********** INCLUDES **********/
$classes = array();

// Core Files
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardCustomPostType.php';
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardEditor.php';
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardGroupRecord.php';
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardLinkRecord.php';
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardMain.php';
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardManager.php';
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardMetaBox.php';
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardMetaBoxAndDbObject.php';
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardMetaBoxObject.php';
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardObjectRecord.php';
$classes[] = dirname(__FILE__).'/../classes/core/class.DSCF_DTG_StandardSetting.php';


// Utilities
$classes[] = dirname(__FILE__).'/../classes/class.DSCF_DTG_Utilities.php';
$classes[] = dirname(__FILE__).'/../classes/class.DSCF_DTG_Box.php';
$classes[] = dirname(__FILE__).'/../classes/class.DSCF_DTG_MessagePopup.php';

// Dimbal Com
if(WPDEVHUB_CONST_DTG_CLASSES_DIMBALCOM){
    $classes[] = dirname(__FILE__).'/../classes/com/class.DSCF_DTG_ComRequest.php';
    $classes[] = dirname(__FILE__).'/../classes/com/class.DSCF_DTG_ComDispatch.php';
}

// Zone Manager
if(WPDEVHUB_CONST_DTG_CLASSES_ZONES){
    $classes[] = dirname(__FILE__).'/../classes/zone/class.DSCF_DTG_ZoneManager.php';
    $classes[] = dirname(__FILE__).'/../classes/zone/class.DSCF_DTG_Zone.php';
    $classes[] = dirname(__FILE__).'/../classes/zone/class.DSCF_DTG_ZoneItem.php';
}

// Virtual Pages
if(WPDEVHUB_CONST_DTG_CLASSES_VIRTUAL_PAGES){
    $classes[] = dirname(__FILE__).'/../classes/vp/interface.DSCF_DTG_VirtualPages_PageInterface.php';
    $classes[] = dirname(__FILE__).'/../classes/vp/interface.DSCF_DTG_VirtualPages_ControllerInterface.php';
    $classes[] = dirname(__FILE__).'/../classes/vp/interface.DSCF_DTG_VirtualPages_TemplateLoaderInterface.php';
    $classes[] = dirname(__FILE__).'/../classes/vp/class.DSCF_DTG_VirtualPages_Page.php';
    $classes[] = dirname(__FILE__).'/../classes/vp/class.DSCF_DTG_VirtualPages_Controller.php';
    $classes[] = dirname(__FILE__).'/../classes/vp/class.DSCF_DTG_VirtualPages_TemplateLoader.php';
}

// Browser Helper
if(WPDEVHUB_CONST_DTG_CLASSES_BROWSER){
    $classes[] = dirname(__FILE__).'/../classes/class.DSCF_DTG_Browser.php';
}

// Chart Helper
if(WPDEVHUB_CONST_DTG_CLASSES_CHART){
    $classes[] = dirname(__FILE__).'/../classes/class.DSCF_DTG_Chart.php';
}

foreach($classes as $classpath){
    include($classpath);
}

// Setup Virtual Page Hook Controller
if(WPDEVHUB_CONST_DTG_CLASSES_VIRTUAL_PAGES){
    $virtualPageController = new DSCF_DTG_VirtualPages_Controller( new DSCF_DTG_VirtualPages_TemplateLoader() );
    add_action( 'init', array( $virtualPageController, 'init' ) );
    add_filter( 'do_parse_request', array( $virtualPageController, 'dispatch' ), PHP_INT_MAX, 2 );
    add_action( 'loop_end', function( \WP_Query $query ) {
        if ( isset( $query->virtual_page ) && ! empty( $query->virtual_page ) ) {
            $query->virtual_page = NULL;
        }
    } );
    add_filter( 'the_permalink', function( $plink ) {
        global $post, $wp_query;

        if (
            $wp_query->is_page
            && isset( $wp_query->virtual_page )
            && ($wp_query->virtual_page instanceof DSCF_DTG_VirtualPages_Page)
            && isset( $post->is_virtual )
            && $post->is_virtual
        ) {
            $plink = home_url( $wp_query->virtual_page->getUrl() );
        }
        return $plink;
    } );
}



// Base Constants that should be overridden
dimbal_add_define('WPDEVHUB_CONST_DTG_APP_CODE', 'undefined');
dimbal_add_define('WPDEVHUB_CONST_DTG_SLUG', 'wpdevhub-'.WPDEVHUB_CONST_DTG_APP_CODE);
dimbal_add_define('WPDEVHUB_CONST_DTG_FOLDER', 'wpdevhub-'.WPDEVHUB_CONST_DTG_APP_CODE);
dimbal_add_define('WPDEVHUB_CONST_DTG_DB_PREFIX', 'wpdevhub-'.WPDEVHUB_CONST_DTG_APP_CODE);
dimbal_add_define('WPDEVHUB_CONST_DTG_SETTINGS_PREFIX', WPDEVHUB_CONST_DTG_SLUG.'-');
dimbal_add_define('WPDEVHUB_CONST_DTG_URL', plugins_url() . "/" . WPDEVHUB_CONST_DTG_FOLDER);
dimbal_add_define('WPDEVHUB_CONST_DTG_DIR', WP_PLUGIN_DIR . '/' . WPDEVHUB_CONST_DTG_FOLDER);
dimbal_add_define('WPDEVHUB_CONST_DTG_URL_IMAGES', WPDEVHUB_CONST_DTG_URL . '/images');
dimbal_add_define('WPDEVHUB_CONST_DTG_PLUGIN_FILE', WPDEVHUB_CONST_DTG_DIR . '/index.php');
dimbal_add_define('WPDEVHUB_CONST_DTG_USE_UPDATER',false);      // Use the WordPress Updater by Default
dimbal_add_define('WPDEVHUB_CONST_DTG_PROMO_DCD',true);       // Safety switch to turn off promo'ing.
dimbal_add_define('WPDEVHUB_CONST_DTG_URL_SUBSCRIPTIONS', 'https://www.wpdevhub.com/subscriptions/');

// Pages
dimbal_add_define('WPDEVHUB_CONST_DTG_PAGE_HOME', 'home');
dimbal_add_define('WPDEVHUB_CONST_DTG_PAGE_ZONES', 'zones');
dimbal_add_define('WPDEVHUB_CONST_DTG_PAGE_SETTINGS', 'settings');
dimbal_add_define('WPDEVHUB_CONST_DTG_PAGE_REPORTS', 'reports');
dimbal_add_define('WPDEVHUB_CONST_DTG_PAGE_PREVIEW', 'preview');
dimbal_add_define('WPDEVHUB_CONST_DTG_PAGE_SUPPORT', 'support');


// Zones
dimbal_add_define('WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME', 'Zone');
dimbal_add_define('WPDEVHUB_CONST_DTG_ZONE_ITEM_NAME', 'Item');

// Environment Specific Loading
include dirname(__FILE__).'/inc.env.php';
include dirname(__FILE__).'/inc.ver.php';

