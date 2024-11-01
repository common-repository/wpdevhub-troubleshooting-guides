<?php
/*
 * Plugin Name:   WPDevHub Troubleshooting Guides
 * Version:       2.9.1
 * Plugin URI:    https://www.wpdevhub.com/wordpress-plugins/troubleshooting-guides/
 * Description:   Create feature rich troubleshooting guides for your website visitors to follow.
 * Author:        WPDevHub
 * Author URI:    https://www.wpdevhub.com/
 */

define('WPDEVHUB_CONST_DTG_PLUGIN_TITLE', 'WPDevHub Troubleshooting Guides');
define('WPDEVHUB_CONST_DTG_APP_CODE', 'dtg');
define('WPDEVHUB_CONST_DTG_FOLDER', 'wpdevhub-troubleshooting-guides');

// Standard Setup Steps
include dirname(__FILE__).'/inc/inc.setup.php';

// Class Includes
include dirname(__FILE__).'/classes/class.DSCF_DTG_TroubleshootingGuides_Main.php';
include dirname(__FILE__).'/classes/class.DSCF_DTG_TroubleshootingGuides_Guide.php';
include dirname(__FILE__).'/classes/class.DSCF_DTG_TroubleshootingGuides_GuideShortCodeMetaBox.php';
include dirname(__FILE__).'/classes/class.DSCF_DTG_TroubleshootingGuides_Step.php';
include dirname(__FILE__).'/classes/class.DSCF_DTG_TroubleshootingGuides_StepMetaBox.php';

// Actions
add_action( 'init', array('DSCF_DTG_TroubleshootingGuides_Main', 'wpActionInit'), 0 );

// Activations Hooks
register_activation_hook(__FILE__, array('DSCF_DTG_TroubleshootingGuides_Main', 'wpActionActivate'));
