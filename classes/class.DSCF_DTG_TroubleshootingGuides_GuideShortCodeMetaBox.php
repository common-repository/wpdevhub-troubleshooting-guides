<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 3/21/17
 * Time: 3:46 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_TroubleshootingGuides_GuideShortCodeMetaBox extends DSCF_DTG_StandardMetaBox{

    const KEYNAME = "dtg_shortcode";
    const TITLE = "Guide Shortcode";
    const SCREEN = DSCF_DTG_TroubleshootingGuides_Guide::KEYNAME;

    const CONTEXT = "side";       // Either "normal", "advanced" or "side"
    const PRIORITY = "default";          // Either "default", "core", "high" or "low"

    public static function renderCustom( $post ){

        // Display the shortcode

        $html = '';

        $shortcode = DSCF_DTG_TroubleshootingGuides_Guide::getInsertGuideShortcode($post->ID);

        $html = '<input type="text" style="width:100%; padding:5px;" value=\''.$shortcode.'\' onclick="this.select();" />';

        echo $html;

    }

    public static function saveCustom( $post_id ){

        // Nothing to do here

    }

}
