<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 3/21/17
 * Time: 3:36 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_StandardMetaBox{

    const KEYNAME = "some_meta_box_name";
    const TITLE = "Some Meta Box Headline";
    const SCREEN = null;
    const CONTEXT = "normal";       // Either "normal", "advanced" or "side"
    const PRIORITY = "high";          // Either "default", "core", "high" or "low"
    const CONTENT_HOOK = false;

    public static function getClassName(){
        $object = new static();
        $classname = get_class($object);
        //DSCF_DTG_Utilities::logMessage("Inside [".__CLASS__."]::[".__FUNCTION__."] Classname[".$classname."]");
        return $classname;
    }

    public static function registerActions(){
        //DSCF_DTG_Utilities::logMessage("Inside [".__CLASS__."]::[".__FUNCTION__."] Screen[".static::SCREEN."]");
        add_action( 'add_meta_boxes_'.strtolower(static::SCREEN), array( static::getClassName(), 'AddMetaBox' ) );
        add_action( 'save_post',      array( static::getClassName(), 'saveBase' ) );

        if(static::CONTENT_HOOK){
            // Register the Content Hook
            add_filter( 'the_content', array( static::getClassName(), 'filterContent' ) );
        }
    }

    /**
     * Adds the meta box container.
     */
    public static function AddMetaBox() {
        //DSCF_DTG_Utilities::logMessage("Add Meta Box: ".static::KEYNAME);
        add_meta_box(
            static::KEYNAME,
            __( static::TITLE, 'textdomain' ),
            array( static::getClassName(), 'renderBase' ),
            static::SCREEN,
            static::CONTEXT,
            static::PRIORITY
        );
    }

    public static function getPostMeta($postId){
        return get_post_meta( $postId, static::KEYNAME, true );
    }

    public static function savePostMeta($postId, $value){
        update_post_meta( $postId, static::KEYNAME, $value );
    }


    public static function getEditorObjectClassName(){
        return static::getClassName().'Object';
    }


    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public static function renderBase( $post ) {

        // Add an nonce field so we can check for it later.
        wp_nonce_field( static::KEYNAME, static::KEYNAME.'_nonce' );

        static::renderFromEditorOptions($post->ID);

        static::renderCustom($post);


    }

    /*
    * If the object class exists then build the common editor to make editing fast and easy
    */
    public static function renderFromEditorOptions( $postId ){

        $objectClassname = static::getEditorObjectClassName();

        if(class_exists($objectClassname)){

            $object = null;
            if(!empty($postId)){
                $object = static::getPostMeta( $postId );
            }

            $options = $objectClassname::editorBuildOptions($object);
            $options['showBottomSaveButton']=false;

            //DSCF_DTG_Utilities::logMessage("Inside mb_render: Options: ".print_r($options, true));

            $html = DSCF_DTG_StandardEditor::buildEditor($options, '#');

            echo $html;

        }else{
            // Do not record any messages as the class may not exist
        }

    }

    /*
    * Child classes extend this function to render the Meta Box on the Custom Post Type Editor
    */
    public static function renderCustom($post){}


    /**
     * The base routines that make up the entire save process.
     *  First execute save_base
     *  Second it checks for Editor based save routines
     *  Third it checks for any Child base save() overrides
     *
     * @param int $post_id The ID of the post being saved.
     */
    public static function saveBase( $post_id ) {

        /*
        * We need to verify this came from the our screen and with proper authorization,
        * because save_post can be triggered at other times.
        */

        // Check if our nonce is set.
        if ( ! isset( $_POST[static::KEYNAME.'_nonce'] ) ) {
            return $post_id;
        }

        $nonce = $_POST[static::KEYNAME.'_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, static::KEYNAME ) ) {
            return $post_id;
        }

        /*
        * If this is an autosave, our form has not been submitted,
        * so we don't want to do anything.
        */
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check the user's permissions.
        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }

        // First check if this object had a base
        static::saveFromEditorOptions($post_id);

        static::saveCustom($post_id);

    }

    /*
     * If editor objects are defined then do the save automatically so child classes don't have to do it
     */
    public static function saveFromEditorOptions( $postId ){

        $objectClassname = static::getEditorObjectClassName();

        if(class_exists($objectClassname)){
            $object = new $objectClassname();

            $options = $objectClassname::editorBuildOptions($object);

            $object = DSCF_DTG_StandardEditor::saveEditorChanges($object, $options, $_POST);

            //DSCF_DTG_Utilities::logMessage("mb_save.  Request Object: ".print_r($_REQUEST, true));
            //DSCF_DTG_Utilities::logMessage("mb_save.  MB Object: ".print_r($object, true));

            // Save the object into the meta data
            static::savePostMeta( $postId, $object );
        }else{
            // Do not record any messages as the class may not exist
        }

    }

    /*
     * Child classes extend this function to save the editor fields on the Custom Post Type Editor
     */
    public static function saveCustom($post_id){}


    /*
     * Child classes extend this function to change or manipulate the post content as a result of this Meta Box
     */
    public static function filterContent($content){}

}
