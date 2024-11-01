<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 2/1/17
 * Time: 2:34 PM
 * To change this template use File | Settings | File Templates.
 */


interface DSCF_DTG_VirtualPages_PageInterface{

    function getUrl();

    function getTemplate();

    function getTitle();

    function setTitle( $title );

    function setContent( $content );

    function setTemplate( $template );

    /**
     * Get a WP_Post build using virtual Page object
     *
     * @return \WP_Post
     */
    function asWpPost();
}
