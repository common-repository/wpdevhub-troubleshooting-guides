<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 3/12/15
 * Time: 11:26 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_TroubleshootingGuides_Main extends DSCF_DTG_StandardMain {

    static $classname = "DSCF_DTG_TroubleshootingGuides_Main";

    const CURRENT_VERSION = 1;

    const PAGE_HOME = "home";
    const PAGE_SETTINGS = "settings";

    const SC_ID_HOMEPAGE = 1;
    const SC_ID_DISPLAY_GUIDE = 2;
    const SC_ID_DISPLAY_CATEGORY = 3;

    const SETTINGS_SLUG_BASE = "vp_slug_base";
    const SLUG_BASE = "troubleshooting";

    const SETTINGS_PLUGIN_ENABLED = "plugin_enabled";
    const SETTINGS_OPEN_BY_DEFAULT = "open_by_default";

    /*
    * WordPress Action Hook for init :: Generally the first action available to the Plugins
    */
    public static function wpActionInit(){
        parent::wpActionInit();

        // Add CSS and JS Resources
        //self::addResourceToEnqueue($name, $url, $type);
        self::addResourceToEnqueue(WPDEVHUB_CONST_DTG_SLUG.'-public-css', WPDEVHUB_CONST_DTG_URL.'/css/wpdevhub-dtg.css', self::ERT_PUBLIC_CSS);

        // Add AJAX Handlers
        //self::addAjaxMapping($name, $callable, $type);

        // Add CRON Hooks
        //self::addCronHandler($name, $callable, $schedule);

        // Add Widgets
        //self::addWidget($classname);

        // Add Custom post types
        self::addCustomPostType( DSCF_DTG_TroubleshootingGuides_Guide::KEYNAME , 'DSCF_DTG_TroubleshootingGuides_Guide' );

        // Add MetaBox
        self::addMetaBox( 'DSCF_DTG_TroubleshootingGuides_StepMetaBox' );
        self::addMetaBox( 'DSCF_DTG_TroubleshootingGuides_GuideShortCodeMetaBox' );

        // Add ShortCode
        self::addShortcode(self::SC_ID_DISPLAY_GUIDE, array('DSCF_DTG_TroubleshootingGuides_Guide','shortcodeHandlerDisplayGuide'));

        // Random Filters, etc
        //add_filter('redirect_post_location', array('DSCF_DTG_Quizzes_Quiz','wpFilterRedirectPostLocation'));

        // Migrate the old CPT Names and Keys
        //DSCF_DTG_Utilities::migratePostTypesIfNeeded("DSCF_DTG_quide", DSCF_DTG_TroubleshootingGuides_Guide::KEYNAME);
        //DSCF_DTG_Utilities::migrateTaxonomyTypesIfNeeded("DSCF_DTG_quide_category", DSCF_DTG_TroubleshootingGuides_Guide::KEYNAME_CATEGORY);
        //DSCF_DTG_Utilities::migrateTaxonomyTypesIfNeeded("DSCF_DTG_quide_tag", DSCF_DTG_TroubleshootingGuides_Guide::KEYNAME_TAG);
        //DSCF_DTG_Utilities::migrateMetaBoxDataIfNeeded("steps", DSCF_DTG_TroubleshootingGuides_StepMetaBox::KEYNAME);

    }

    public static function wpActionActivate(){

        // Need to register the Taxonomies BEFORE calling the Flush Rewrite Rules Routine
        DSCF_DTG_TroubleshootingGuides_Guide::registerTaxonomies();

        parent::wpActionActivate();
    }

    public static function wpActionAdminMenu(){
        //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        //add_menu_page( 'Dimbal Troubleshooting Guides', WPDEVHUB_CONST_DTG_PLUGIN_TITLE, 'manage_options', DSCF_DTG_Utilities::buildPageSlug(self::PAGE_HOME), array('DSCF_DTG_Utilities','renderPage') );

        $dscf = strtolower(DSCF_DTG_TroubleshootingGuides_Guide::KEYNAME);

        //add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
        add_submenu_page( 'edit.php?post_type='.$dscf, 'Settings', 'Settings', 'manage_options', DSCF_DTG_Utilities::buildPageSlug(self::PAGE_SETTINGS), array('DSCF_DTG_Utilities','renderPage'));

        //add_submenu_page( 'fake-slug-does-not-exist', 'Preview', 'Preview', 'manage_options', DSCF_DTG_Utilities::buildPageSlug(self::PAGE_PREVIEW), array('DSCF_DTG_Utilities','renderPage'));
    }

    public static function buildSettingsEditorOptions($object=null){

        $options[]=array(
            'rowType'=>'SectionHeader',
            'title'=>'Global Framework Settings',
        );

        $options[]=array(
            'title'=>'Plugin Enabled',
            'objectType'=>DSCF_DTG_StandardEditor::OT_BOOLEAN,
            'objectName'=>'plugin_enabled',
            'formType'=>DSCF_DTG_StandardEditor::ET_CHECKBOX,
            'value'=>(isset($object->plugin_enabled))?$object->plugin_enabled:true,
            'help'=>'True to enable the Plugin, False to disable it without uninstalling it.  If False, will prevent the display of all user facing guides, etc...  Use this feature to disable the plugin globally without having to uninstall it.'
        );
        $keyname = self::SETTINGS_SLUG_BASE;
        $options[]=array(
            'title'=>'Virtual Path',
            'objectType'=>DSCF_DTG_StandardEditor::OT_STRING,
            'objectName'=>$keyname,
            'formType'=>DSCF_DTG_StandardEditor::ET_TEXT,
            'size'=>50,
            'value'=>(isset($object->$keyname))?$object->$keyname:self::SLUG_BASE,
            'help'=>'The virtual path off of '.site_url().' to house the troubleshooting guides.  Default Value: "'.self::SLUG_BASE.'"'
        );
        $options[]=array(
            'rowType'=>'SectionHeader',
            'title'=>'Guide Settings',
        );
        $keyname = self::SETTINGS_OPEN_BY_DEFAULT;
        $options[]=array(
            'title'=>'Open Steps by Default',
            'objectType'=>DSCF_DTG_StandardEditor::OT_BOOLEAN,
            'objectName'=>$keyname,
            'formType'=>DSCF_DTG_StandardEditor::ET_CHECKBOX,
            'value'=>(isset($object->$keyname))?$object->$keyname:false,
            'help'=>'If checked, each step will be expanded on page display.  Default setting is to have them hidden until clicked'
        );
        return $options;

    }

    public static function buildSampleData(){

        $categoryCount=5;
        $guideCountLow=6;
        $guideCountHigh=10;
        $commentCountLow=0;
        $commentCountHigh=5;
        $userObject = wp_get_current_user();
        $categoryObjects = array();

        $postData = array(
            'post_author'   => $userObject->ID,
            'post_title'    => DSCF_DTG_Utilities::getRandomLoremIpsumTitle(),
            'post_content'  => 'content',
            'post_status'   => 'publish',           // Choose: publish, preview, future, draft, etc.
            'post_type'     => 'DSCF_DTG_guide',
            'meta_input'    => array(
                'is_sampled_data'   => true
            )

        );

        $time = current_time('mysql');
        $commentData = array(
            'comment_post_ID' => 0,
            'comment_author' => 'admin',
            'comment_author_email' => 'admin@admin.com',
            'comment_author_url' => 'http://',
            'comment_content' => 'content here',
            'comment_type' => '',
            'comment_parent' => 0,
            'user_id' => 1,
            'comment_author_IP' => '127.0.0.1',
            'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
            'comment_date' => $time,
            'comment_approved' => 1,
        );

        for($cat=0; $cat<$categoryCount; $cat++){

            $termString = DSCF_DTG_Utilities::getRandomLoremIpsumTitle();
            $categoryResponse = term_exists($termString, 'DSCF_DTG_category');
            if(empty($categoryResponse)){
                $categoryResponse = wp_insert_term(DSCF_DTG_Utilities::getRandomLoremIpsumTitle(), 'DSCF_DTG_category');
            }

            if(!empty($categoryResponse) && is_array($categoryResponse)){

                $guideCount = rand($guideCountLow, $guideCountHigh);
                for($i=0; $i<$guideCount; $i++){

                    $lineCount = rand(2, 5);
                    $postData['post_title'] = DSCF_DTG_Utilities::getRandomLoremIpsumTitle();
                    $postData['post_content'] = DSCF_DTG_Utilities::getRandomLoremIpsumParagraphs($lineCount);
                    $pid = wp_insert_post($postData);

                    // Set the category
                    wp_set_post_terms( $pid, $categoryResponse['term_id'], 'DSCF_DTG_category', true );

                    // Create comments
                    $commentCount = rand($commentCountLow, $commentCountHigh);
                    for($c=0; $c<$commentCount; $c++){
                        $lineCount = rand(1, 3);
                        $commentData['comment_post_ID'] = $pid;
                        $commentData['comment_content'] = DSCF_DTG_Utilities::getRandomLoremIpsumParagraphs($lineCount);
                        wp_insert_comment($commentData);
                    }

                }
            }else{
                DSCF_DTG_Utilities::logMessage("Error Creating Category: ".print_r($categoryResponse, true));
            }

        }

    }

    public static function getBaseUrl(){
        $url = self::getSlugBase();
        $url = trim($url, '/');
        $url = '/'.$url.'/';
        $url = site_url() . $url;
        return $url;

    }

    public static function getSlugBase(){
        $slug = DSCF_DTG_StandardSetting::getSetting(self::SETTINGS_SLUG_BASE);
        if(empty($slug)){
            $slug = self::SLUG_BASE;
        }
        return $slug;
    }

    public static function isPluginEnabled(){
        $isEnabled = DSCF_DTG_StandardSetting::getSetting(self::SETTINGS_PLUGIN_ENABLED);
        if(empty($isEnabled)){
            return false;
        }
        return true;
    }



}
