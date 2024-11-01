<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 3/3/17
 * Time: 4:56 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_TroubleshootingGuides_Guide extends DSCF_DTG_StandardCustomPostType {

    const KEYNAME = "dtg_quide";
    const KEYNAME_CATEGORY = "dtg_guide_category";
    const KEYNAME_TAG = "dtg_guide_tag";


    public function __construct(){

        parent::__construct();

    }

    public static function registerTaxonomies(){

        $troubleshootingSlugBase = DSCF_DTG_TroubleshootingGuides_Main::getSlugBase();

        // Register the Custom Support Forum Category Structure
        register_taxonomy(self::KEYNAME_CATEGORY, null,
            array(
                'hierarchical'      => true, // make it hierarchical (like categories)
                'labels'            => array(
                    'name'              => _x('Guide Categories', 'taxonomy general name'),
                    'singular_name'     => _x('Guide Categories', 'taxonomy singular name'),
                    'search_items'      => __('Search Guide Categories'),
                    'all_items'         => __('All Guide Categories'),
                    'parent_item'       => __('Parent Guide Category'),
                    'parent_item_colon' => __('Parent Guide Category:'),
                    'edit_item'         => __('Edit Guide Category'),
                    'update_item'       => __('Update Guide Category'),
                    'add_new_item'      => __('Add New Guide Category'),
                    'new_item_name'     => __('New Guide Category Name'),
                    'menu_name'         => __('Categories'),
                ),
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => ['slug' => $troubleshootingSlugBase.'/category'],
            )
        );

        // Register the Custom Support Forum Category Structure
        register_taxonomy(self::KEYNAME_TAG, null,
            array(
                'hierarchical'      => false, // make it non hierarchical (like regular tags)
                'labels'            => array(
                    'name'              => _x('Guide Tags', 'taxonomy general name'),
                    'singular_name'     => _x('Guide Tags', 'taxonomy singular name'),
                    'search_items'      => __('Search Guide Tags'),
                    'all_items'         => __('All Guide Tags'),
                    'parent_item'       => __('Parent Guide Tag'),
                    'parent_item_colon' => __('Parent Guide Tag:'),
                    'edit_item'         => __('Edit Guide Tag'),
                    'update_item'       => __('Update Guide Tag'),
                    'add_new_item'      => __('Add New Guide Tag'),
                    'new_item_name'     => __('New Guide Tag Name'),
                    'menu_name'         => __('Tags'),
                ),
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => ['slug' => $troubleshootingSlugBase.'/tag'],
            )
        );

        // Register the Custom Troubleshootingleshooting Post Type
        register_post_type( self::KEYNAME,
            array(
                'labels'                => array(
                    'name'                  => __( 'Troubleshooting Guides' ),
                    'singular_name'         => __( 'Troubleshooting Guide' ),
                    'menu_name'             => __( 'Troubleshooting Guides' ),
                    'all_items'             => __( 'All Guides' ),
                    'view_item'             => __( 'View Troubleshooting Guide' ),
                    'view_items'            => __( 'View Troubleshooting Guides' ),
                    'archives'              => __( 'Archive Troubleshooting Guides' ),
                    'add_new_item'          => __( 'Add New Troubleshooting Guide' ),
                    'edit_item'             => __( 'Edit Troubleshooting Guide' ),
                    'update_item'           => __( 'Update Troubleshooting Guide' ),
                    'search_items'          => __( 'Search Troubleshooting Guides' ),
                ),
                'description'           => __( 'WPDevHub Troubleshooting Guides' ),
                'public'                => true,
                'has_archive'           => true,
                'rewrite'               => array('slug' => $troubleshootingSlugBase.'/guide'),
                'hierarchical'          => false,
                'supports'              => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions' ),
                'show_ui'               => true,
                'show_in_menu'          => true,
                'show_in_nav_menus'     => true,
                'show_in_admin_bar'     => true,
                'can_export'            => true,
                'exclude_from_search'   => false,
                'publicly_queryable'    => true,
                'capability_type'       => 'post',
                'taxonomies'            => array( self::KEYNAME_CATEGORY , self::KEYNAME_TAG ),
            )
        );

        // Associate the Taxonomies with the Object Types
        register_taxonomy_for_object_type( self::KEYNAME_TAG , self::KEYNAME );
        register_taxonomy_for_object_type( self::KEYNAME_CATEGORY , self::KEYNAME );
    }

    public static function get_display($content){

        $postId = get_the_ID();
        $is_single = is_single();

        DSCF_DTG_TroubleshootingGuides_Main::wpActionEnqueueScripts();

        $html = '';

        // Whether or not to display the Navigation Links and other Meta Fields
        if($is_single){

            $html .= $content;

            $openByDefault = DSCF_DTG_StandardSetting::getSetting(DSCF_DTG_TroubleshootingGuides_Main::SETTINGS_OPEN_BY_DEFAULT);
            $displayStyle="";
            if($openByDefault){
                $displayStyle="display:block;";
            }

            try{
                $steps = DSCF_DTG_TroubleshootingGuides_StepMetaBox::getPostMeta($postId);
                $steps = DSCF_DTG_Utilities::ensureIsArray($steps);
                if(!empty($steps)){
                    $html .= '<ul class="dtg_step_list">';
                    foreach($steps as $key=>$object){
                        if(is_a($object, 'DSCF_DTG_TroubleshootingGuides_Step')){
                            $contentId = 'dtg_step_content_'.$key;
                            $html .= '<li class="dtg_step_item" onclick="jQuery(\'#'.$contentId.'\').toggle();">';
                            $html .= '<div class="dtg_step_title">>> '.$object->title.'</div>';
                            $html .= '<div class="dtg_step_content" id="'.$contentId.'" style="'.$displayStyle.'">'.$object->contents.'</div>';
                            $html .= '</li>';
                        }
                    }
                    $html .= '</ul>';
                }
            }catch(Exception $e){
                error_log("Exception Caught displaying Troubleshooting Guide Steps: ".$e->getMessage());
            }

        }else{

            // Not a single Item - more like a list display
            $html .= $content;

        }

        return $html;
    }

    public static function getInsertGuideShortcode($postId){
        return DSCF_DTG_StandardObjectRecord::buildShortcodeHelper(array('sc_id'=>DSCF_DTG_TroubleshootingGuides_Main::SC_ID_DISPLAY_GUIDE, 'guide_id'=>$postId));
    }

    /*
    * This short code handler will display all of the available lists
    */
    public static function shortcodeHandlerDisplayGuide($atts){

        global $post;

        // Check to see if the Plugin is Active
        if(!DSCF_DTG_TroubleshootingGuides_Main::isPluginEnabled()){
            DSCF_DTG_Utilities::logMessage("Inside ShortCode Handler: Plugin is not Enabled");
            return "";
        }

        DSCF_DTG_TroubleshootingGuides_Main::enqueuePublicResources();

        $html = '';
        $guide_id = 0;
        extract( shortcode_atts( array(
            'guide_id' => 0,
        ), $atts ) );


        if(!empty($guide_id)){

            $post = get_post($guide_id);
            setup_postdata($post);
            $html = self::theContent();

        }

        return $html;

    }

}
