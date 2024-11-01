<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 3/21/17
 * Time: 3:36 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_StandardCustomPostType{

    const KEYNAME = 'custom-post-type-keyname';

    const TITLE = 'Post';

    const REMOVE_WPAUTOP = false;

    // Constants for the Post Query List
    const POSTLIST_NUMBER_POSTS = 100;
    const POSTLIST_OFFSET = 0;
    const POSTLIST_POST_STATUS = 'publish';
    const POSTLIST_POSTS_PER_PAGE = 3;
    const POSTLIST_ORDER_BY = 'date';
    const POSTLIST_ORDER = 'DESC';

    public function __construct() {

    }

    public static function get($postId){
        return get_post($postId);
    }

    public static function disableWpComments($classname){
        add_action( 'widget_comments_args', array( $classname , 'disableCommentsWpActionWidgetCommentsArgs' ) );
        add_filter( 'comment_feed_where', array( $classname , 'disableCommentsWpFilterCommentFeedWhere' ) );
    }

    public static function disableCommentsWpActionWidgetCommentsArgs($args){
        foreach($args as $key=>$arg){
            if($arg == static::KEYNAME){
                unset($args[$key]);
            }
        }
        return $args;
    }

    public static function disableCommentsWpFilterCommentFeedWhere($where){
        return $where . " AND wp_posts.post_type NOT IN ( '".static::KEYNAME."' )";
    }

    /*
     * Clients extend this function to register the unique taxonomies for this CPT
     */
    public static function registerTaxonomies(){

    }

    /*
     * Needed only to appease the built in editor - not actually used to save anything as that happens via the MB Save routines
     */
    public static function save(){

    }

    public static function get_post_meta($post_id){
        return get_post_meta( $post_id, static::KEYNAME, true );
    }

    public static function update_post_meta($post_id, $object){
        update_post_meta( $post_id, static::KEYNAME, $object );
    }

    /*
     * Child classes extend this function to display the custom post type in a public fashion
     */
    public static function get_display($content){
        DSCF_DTG_Utilities::logMessage("get_display.  Wrong place to be - in the parent");
        return $content;
    }

    public static function getAllPostObjects(){
        return get_posts(array(
            'numberposts'=>-1,
            'post_type'=>static::KEYNAME,
        ));
    }

    public static function getAllPostObjectsAsMenuArray(){
        $posts = self::getAllPostObjects();
        $array = DSCF_DTG_StandardObjectRecord::getBasicArrayFromObjects($posts, 'post_title', 'ID');
        return $array;
    }

    public static function getBaseUrl(){

    }

    public function getUrl(){
        return get_post_permalink($this->ID);
    }

    public static function wpFilterTheContent($content){

        // Get the post to determine post type
        $post_type = get_post_type();

        if($post_type == static::KEYNAME){

            // First see if we need to disable WPAUTOP
            if(static::REMOVE_WPAUTOP){
                remove_filter( 'the_content', 'wpautop' );
                remove_filter( 'the_content', 'wp_strip_all_tags' );
            }

            $content = static::get_display($content);
        }

        return $content;

    }

    public static function wpFilterTheExcerpt($excerpt){

        // Get the post to determine post type
        $post_type = get_post_type();

        if($post_type == static::KEYNAME){

            // First see if we need to disable WPAUTOP
            if(static::REMOVE_WPAUTOP){
                remove_filter( 'the_excerpt', 'wpautop' );
                remove_filter( 'the_excerpt', 'wp_strip_all_tags' );
            }


        }

        return $excerpt;

    }

    /*
    * Filter hook to be used with the filter "get_the_archive_title" to filter out "Archives: " from the category title set
    */
    public static function wpFilterGetTheArchiveTitle($title){

        $post_type = get_post_type();

        if($post_type == static::KEYNAME){

            if ( is_category() ) {
                $title = single_cat_title( '', false );
            } elseif ( is_tag() ) {
                $title = single_tag_title( '', false );
            } elseif ( is_post_type_archive() ) {
                $title = post_type_archive_title( '', false );
            } elseif ( is_tax() ) {
                $title = single_term_title( '', false );
            }

        }

        return $title;
    }

    /*
     * replaces the WordPress the_content function - just doesn't echo the outputs
     * Make sure setup post data is done before hand
     */
    public static function theContent(){
        $content = get_the_content();
        $content = apply_filters( 'the_content', $content );
        $content = str_replace( ']]>', ']]&gt;', $content );
        return $content;
    }

    /*
     * Function to get the content for a post ID while in the middle of a loop.
     * Usually used for shortcodes to get access to random posts content
     */
    public static function getContentForPostId($postId){

        // Need to use the global post object
        global $post;
        $originalPost = $post;
        $html = '';

        if(!empty($postId)){

            // Get the post for the passed ID
            $post = get_post($postId);

            // Setup the post data for the newly fetched post
            setup_postdata($post);

            // Get the content for this post
            $html = self::theContent();

            // Set the original Post back to the global post object
            $post = $originalPost;

            // Be sure the run the setup function so as to not mess it up
            setup_postdata($post);
        }

        return $html;

    }

    /*
    * Function to get the content for a post object while in the middle of a loop.
    * Usually used for looping a list of objects outside of the normal post loop.
    */
    public static function getContentForPost($passedPost){

        // Need to use the global post object
        global $post;
        $originalPost = $post;
        $html = '';

        if(!empty($passedPost)){

            // change the global variable
            $post = $passedPost;

            // Setup the post data for the newly fetched post
            setup_postdata($post);

            // Get the content for this post
            $html = self::theContent();

            // Set the original Post back to the global post object
            $post = $originalPost;

            // Be sure the run the setup function so as to not mess it up
            setup_postdata($post);
        }

        return $html;

    }

    /*
     * General function to display a batch of posts from a list of objects
     * Usually done outside of the loop
     */
    public static function getDisplayForBatch($postObjects){
        $html = '';
        foreach($postObjects as $postObject){
            $html .= self::getContentForPost($postObject);
        }
        return $html;
    }


    /*
     * General function to perform a basic Virtual Page request for a Custom Post Type
     * Attempt is to be flexible and enforce standards
     */
    public static function getVirtualPagesPostList($params){

        global $post;

        // Validate the passed params array
        if(!is_array($params)){
            $params = array();
        }

        // Setup the base query params
        $query_args = array(
            'post_type'  => static::KEYNAME,
            'numberposts'=> static::POSTLIST_NUMBER_POSTS,
            'offset'=> static::POSTLIST_OFFSET,
            'post_status' => static::POSTLIST_POST_STATUS,
            'posts_per_page' => static::POSTLIST_POSTS_PER_PAGE,
            'orderby' => static::POSTLIST_ORDER_BY,
            'order' => static::POSTLIST_ORDER
        );

        // See if a page number was passed
        $currentPage = 1;
        if(array_key_exists('page',$params)){
            $currentPage = $params['page'];
            $currentPage = max(1, $currentPage);
        }


        // Setup the default title
        // Could probably replace this with a string name from the register taxonomy call?
        $title = static::TITLE;

        // See if a taxonomy was passed
        $termType = null;
        $slug = "";
        if(array_key_exists('category_slug', $params)){
            $slug = $params['category_slug'];
            $termType = static::KEYNAME_CATEGORY;
        }elseif(array_key_exists('tag_slug', $params)){
            $slug = $params['tag_slug'];
            $termType = static::KEYNAME_TAG;
        }

        // If the slug is not empty - add a tax_query to the query for posts
        if(!empty($slug)){
            $term = get_term_by('slug', $slug, $termType);
            if(!empty($term)){
                $query_args['tax_query']=array(array(
                    'taxonomy' => $termType,
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ));
                $title .= " : ".$term->name;
            }
        }

        // Execute the query
        $posts = get_posts($query_args);

        // Now loop through and get the display for each item
        $html = '';
        foreach($posts as $post){
            setup_postdata($post);
            $html .= '<h2>'.get_the_title().'</h2>';
            $html .= static::theContent();
        }

        /*
        // Show the page Navigation
        $paginationVars = array(
            'base' => get_post_type_archive_link(static::KEYNAME).'%_%',        // Need to change this if is a Taxonomy
            'format' => 'page/%#%',
            'current' => $currentPage,
            'total' => 10,
            'show_all' => true
        );
        DSCF_DTG_Utilities::logMessage("PAGINATION VARS: ".print_r($paginationVars, true));
        $pagination = get_the_posts_pagination($paginationVars);
        $html .= $pagination;
        DSCF_DTG_Utilities::logMessage("PAGINATION: ".$pagination);
        */

        $details = array(
            'title'=>$title,
            'content'=>$html
        );

        return $details;

    }

    public static function getTheTermsDisplay($options=array()){

        $defaults = array(
            'post_id'=>0,
            'taxonomy'=>'',
            'separator'=>' | ',
            'title'=>'',
            'title_separator'=>': '
        );
        $options = array_merge($defaults, $options);

        $html = '';
        $taxonomies = get_the_terms($options['post_id'], $options['taxonomy']);
        if(!empty($taxonomies)){
            $taxonomy_list = array();
            foreach($taxonomies as $taxonomy){
                $taxonomy_list[] = '<a href="'.get_term_link($taxonomy).'">'.$taxonomy->name.'</a>';
            }
            if(!empty($taxonomy_list)){
                if(!empty($options['title'])){
                    $html .= $options['title'].$options['title_separator'];
                }
                $html .= implode($taxonomy_list, $options['separator']);
            }
        }

        return $html;

    }

    /*
    * This short code handler will display all of the available lists
    */
    public static function shortcodeHandlerDisplayGenericPostType($atts, $id_field){

        global $post;

        $html = '';

        // Get the shortcode attributes
        $data = shortcode_atts( array(
            $id_field => 0,
        ), $atts );

        // Pull the custom post type id out
        $post_id = DSCF_DTG_Utilities::getFromArray($id_field, $data, 0);

        // Get and display the post
        if(!empty($post_id)){
            $post = get_post($post_id);
            setup_postdata($post);
            $html = self::theContent();
        }

        return $html;

    }

    public static function displayPostListAsExcerpts($posts){

        if(empty($posts)){
            return '';
        }

        // Will need this later
        global $post;
        $orignalPost = $post;

        $html = '';

        foreach($posts as $post){
            if(!empty($post)){

                setup_postdata($post);

                $html .= '<article id="">';

                $html .= '<h2><a href="'.get_permalink($post).'">'.$post->post_title.'</a></h2>';

                $html .= get_the_excerpt($post);

                $html .= '</article>';

            }

        }

        // Setup the original post
        $post = $orignalPost;
        setup_postdata($post);

        return $html;

    }

    public static function displayPostListAsLinks($posts=array(), $title=''){
        $linkString = "";
        $first=true;
        $html='';
        if(!empty($posts)){
            foreach($posts as $post){
                if(!empty($post)){
                    if(!empty($first)){
                        $first=false;
                    }else{
                        $linkString .= ', ';
                    }
                    $linkString .= '<a href="'.get_permalink($post).'">'.$post->post_title.'</a>';
                }
            }
            if(!empty($linkString)){
                $html .= '<p>';
                if(!empty($title)){
                    $html .= $title;
                }
                $html .= $linkString;
                $html .= '</p>';
            }
        }
        return $html;
    }

    public static function helperBuildTitleArrayForParentId($parentPostObject, $postsByParent){

        $parentPostObjectId = 0;
        if(!empty($parentPostObject)){
            $parentPostObjectId = $parentPostObject->ID;
        }

        // Get the list of Child Identifications
        $childPosts = DSCF_DTG_Utilities::getFromArray($parentPostObjectId, $postsByParent);
        //DSCF_DTG_Utilities::logMessage("helperbuildIdentificationArrayForParentId Child Identifications Found for ParentID[$parentPostObjectId]: ".print_r($childPosts, true));

        // Remove the Children from this array so that it shrinks in time
        unset($postsByParent[$parentPostObjectId]);

        // It could be empty -- that's totally cool...
        $finalizedChildren = array();
        if(!empty($childPosts)){

            // Alphabetize the list of child locations
            //DSCF_DTG_Utilities::logMessage("helperbuildIdentificationArrayForParentId BEFORE SORT Array: ".print_r($childPosts, true));
            $childPostsBySlug = array();
            foreach($childPosts as $childPost){
                $childPostsBySlug[$childPost->post_name]=$childPost;
            }
            ksort($childPostsBySlug);

            //DSCF_DTG_Utilities::logMessage("helperbuildIdentificationArrayForParentId AFTER SORT Array: ".print_r($childPostsBySlug, true));
            // Loop through each child location and do a recurssive loop as need to get their respective children
            foreach($childPostsBySlug as $childPost){
                $finalizedChildren[$childPost->ID] = self::helperBuildTitleArrayForParentId($childPost, $postsByParent);
            }

        }

        $returnArray = array(
            'location'=>$parentPostObject,
            'children'=>$finalizedChildren
        );

        //DSCF_DTG_Utilities::logMessage("helperbuildIdentificationArrayForParentId Return Array: ".print_r($returnArray, true));

        return $returnArray;

    }

    public static function helperSortPostsByHierarchy($posts){

        // Build the array of locations sorted by parent id
        $postsByParent = array();
        foreach($posts as $post){
            $parentId = $post->post_parent;
            if(!array_key_exists($parentId, $postsByParent)){
                $postsByParent[$parentId]=array();
            }
            $postsByParent[$parentId][]=$post;
        }

        //DSCF_DTG_Utilities::logMessage("Unsorted Identifications Only By Parent ID: ".print_r($postsByParent, true));

        // Sent it off to the recursive function to organize by Parent ID
        $finalArray = self::helperBuildTitleArrayForParentId(null, $postsByParent);

        //DSCF_DTG_Utilities::logMessage("Sorted Identifications By Parent ID: ".print_r($finalArray, true));

        return $finalArray;

    }

    public static function getPostsWithHierarchyForMenu($sortedPosts, $menuArray=array(), $existingPrefix=""){

        //DSCF_DTG_Utilities::logMessage("Sorted Identifications Passed in: ".print_r($sortedPosts, true));
        foreach($sortedPosts as $postArray){

            $post = DSCF_DTG_Utilities::getFromArray('location', $postArray, null);
            $children = DSCF_DTG_Utilities::getFromArray('children', $postArray, array());

            if(!empty($post)){
                $menuArray[$post->ID] = $existingPrefix.$post->post_title;
            }

            if(!empty($children)){
                $menuArray = self::getPostsWithHierarchyForMenu($children, $menuArray, $existingPrefix."-->  ");
            }

        }

        //DSCF_DTG_Utilities::logMessage("Menu Array Being Return: ".print_r($menuArray, true));
        return $menuArray;

    }


}
