<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 2/1/17
 * Time: 2:34 PM
 * To change this template use File | Settings | File Templates.
 */

class DSCF_DTG_VirtualPages_Page implements DSCF_DTG_VirtualPages_PageInterface{

    private $url;
    private $title;
    private $content;
    private $template;
    private $wp_post;
    private $callback;
    private $parameters;

    function __construct( $url, $title = 'Untitled', $template = 'page.php' ) {
        $this->url = filter_var( $url, FILTER_SANITIZE_URL );
        $this->setTitle( $title );
        $this->setTemplate( $template);
    }

    function getUrl() {
        return $this->url;
    }

    function getTemplate() {
        return $this->template;
    }

    function getTitle() {
        return $this->title;
    }

    function setTitle( $title ) {
        $this->title = filter_var( $title, FILTER_SANITIZE_STRING );
        return $this;
    }

    function setContent( $content ) {
        $this->content = $content;
        return $this;
    }

    function setTemplate( $template ) {
        $this->template = $template;
        return $this;
    }

    function setCallback($callback) {
        $this->callback = $callback;
        return $this;
    }

    function setParameters($parameters) {
        $this->parameters = $parameters;
        return $this;
    }

    function asWpPost() {
        if ( is_null( $this->wp_post ) ) {

            $postTitle = $this->title;
            $postContent = $this->content ? : '';

            if(!empty($this->callback)){
                // This post uses a callback to get various pieces of content
                $postDetails = call_user_func($this->callback, $this->parameters);

                if(is_array($postDetails)){
                    if(array_key_exists('title', $postDetails) && !empty($postDetails['title'])){
                        $postTitle = $postDetails['title'];
                    }
                    if(array_key_exists('content', $postDetails) && !empty($postDetails['content'])){
                        $postContent = $postDetails['content'];
                    }
                }

            }

            // Add the parameters to the request object - useful if using a custom page template
            if(!empty($this->parameters) && is_array($this->parameters)){
                foreach($this->parameters as $k=>$v){
                    $_REQUEST[$k]=$v;
                }
            }

            $post = array(
                'ID'             => 0,
                'post_title'     => $postTitle,
                'post_name'      => sanitize_title( $postTitle ),
                'post_content'   => $postContent,
                'post_excerpt'   => '',
                'post_parent'    => 0,
                'menu_order'     => 0,
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
                'comment_count'  => 0,
                'post_password'  => '',
                'to_ping'        => '',
                'pinged'         => '',
                'guid'           => home_url( $this->getUrl() ),
                'post_date'      => current_time( 'mysql' ),
                'post_date_gmt'  => current_time( 'mysql', 1 ),
                'post_author'    => is_user_logged_in() ? get_current_user_id() : 0,
                'is_virtual'     => TRUE,
                'filter'         => 'raw'
            );
            $this->wp_post = new \WP_Post( (object) $post );
        }
        return $this->wp_post;
    }

}
