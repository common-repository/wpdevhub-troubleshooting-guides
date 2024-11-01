<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 2/1/17
 * Time: 2:34 PM
 * To change this template use File | Settings | File Templates.
 */



class DSCF_DTG_VirtualPages_Controller implements DSCF_DTG_VirtualPages_ControllerInterface{

    private $pages;
    private $loader;
    private $matched;

    static public $s_controller=null;

    const PATH_SEPERATOR = "%%";

    function __construct(DSCF_DTG_VirtualPages_TemplateLoaderInterface $loader){
        $this->pages = new \SplObjectStorage;
        $this->loader = $loader;
        self::$s_controller = $this;
    }

    public static function getController(){
        return self::$s_controller;
    }

    function init() {
        do_action( WPDEVHUB_CONST_DTG_SLUG.'_virtual_pages', $this );
    }

    function addPage(DSCF_DTG_VirtualPages_PageInterface $page){
        $this->pages->attach( $page );
        return $page;
    }

    function dispatch( $bool, \WP $wp ) {

        if ($this->checkRequest() && $this->matched instanceof DSCF_DTG_VirtualPages_Page) {

            $this->loader->init( $this->matched );
            $wp->virtual_page = $this->matched;
            do_action( 'parse_request', $wp );
            $this->setupQuery();
            do_action( 'wp', $wp );
            $this->loader->load();
            $this->handleExit();
        }
        return $bool;
    }

    private function checkRequest() {
        $this->pages->rewind();
        $path = trim( $this->getPathInfo(), '/' );

        while( $this->pages->valid() ) {
            $currentPageUrl = trim( $this->pages->current()->getUrl(), '/' );

            //DSCF_DTG_Utilities::logMessage("checkRequest: Path[$path] SetupUrl[".$currentPageUrl."]");

            if(strpos($currentPageUrl, self::PATH_SEPERATOR) !== FALSE){
                // This means that this check is using variables
                $pathParts = explode("/", $path);
                $currentParts = explode("/", $currentPageUrl);
                $variables = array();

                //DSCF_DTG_Utilities::logMessage("Inside the Path Seperator");
                //DSCF_DTG_Utilities::logMessage("Paths: ".print_r($pathParts, true));
                //DSCF_DTG_Utilities::logMessage("Current: ".print_r($currentParts, true));

                // Make sure the arrays are of the same length
                if(count($pathParts) != count($currentParts)){
                    // Arrays are not the same length - continue to next test
                    $this->pages->next();
                    continue;
                }elseif(empty($pathParts) || empty($currentParts)){
                    // One of the arrays is empty - move to the next test
                    $this->pages->next();
                    continue;
                }

                foreach($currentParts as $key=>$currentPart){

                    // Make sure both arrays pieces exist
                    if(!array_key_exists($key, $pathParts)){
                        // No key - bad match - break out of the for each loop
                        $this->pages->next();
                        continue 2;
                    }
                    $pathPart = $pathParts[$key];

                    //DSCF_DTG_Utilities::logMessage("Path Part test: CurrentPart[$currentPart] PathPart[$pathPart]");

                    // Now test if this is a variable or a string match
                    if(strpos($currentPart, self::PATH_SEPERATOR) !== FALSE){
                        // save this piece as a variable
                        $variableName = str_replace(self::PATH_SEPERATOR, "", $currentPart);
                        $variables[$variableName] = $pathPart;
                        //DSCF_DTG_Utilities::logMessage("Path Part test: Saved as a Variable");
                    }elseif($currentPart === $pathPart){
                        // String match - so far so good
                        //DSCF_DTG_Utilities::logMessage("Path Part test: String Match");
                    }else{
                        // No string match and not variable -- bad match - break out of the foreach loop
                        //DSCF_DTG_Utilities::logMessage("Path Part test: No String Match and No Variable - break out.");
                        $this->pages->next();
                        continue 2;
                    }
                }

                // If I got to this point then I found a valid match for each part
                $this->matched = $this->pages->current();
                $this->matched = $this->matched->setParameters($variables);

                //DSCF_DTG_Utilities::logMessage("A match was found inside the path seperators: ".print_r($this, true));

                return TRUE;

            }else{
                // use standard string comps
                if ( $currentPageUrl === $path ) {
                    $this->matched = $this->pages->current();
                    return TRUE;
                }
            }


            $this->pages->next();
        }
    }

    private function getPathInfo() {
        $home_path = parse_url( home_url(), PHP_URL_PATH );
        return preg_replace( "#^/?{$home_path}/#", '/', esc_url( add_query_arg(array()) ) );
    }

    private function setupQuery() {
        global $wp_query;
        $wp_query->init();
        $wp_query->is_page       = TRUE;
        $wp_query->is_singular   = TRUE;
        $wp_query->is_home       = FALSE;
        $wp_query->found_posts   = 1;
        $wp_query->post_count    = 1;
        $wp_query->max_num_pages = 1;
        $posts = (array) apply_filters(
            'the_posts', array( $this->matched->asWpPost() ), $wp_query
        );
        $post = $posts[0];
        $wp_query->posts          = $posts;
        $wp_query->post           = $post;
        $wp_query->queried_object = $post;
        $GLOBALS['post']          = $post;
        $wp_query->virtual_page   = $post instanceof \WP_Post && isset( $post->is_virtual )
            ? $this->matched
            : NULL;
    }

    public function handleExit() {
        exit();
    }

}
