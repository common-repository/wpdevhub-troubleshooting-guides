<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 2/1/17
 * Time: 2:34 PM
 * To change this template use File | Settings | File Templates.
 */


interface DSCF_DTG_VirtualPages_ControllerInterface{

    /**
     * Init the controller, fires the hook that allows consumer to add pages
     */
    function init();

    /**
     * Register a page object in the controller
     *
     * @param  DSCF_DTG_VirtualPages_Page $page
     * @return DSCF_DTG_VirtualPages_Page
     */
    function addPage(DSCF_DTG_VirtualPages_PageInterface $page);

    /**
     * Run on 'do_parse_request' and if the request is for one of the registered pages
     * setup global variables, fire core hooks, requires page template and exit.
     *
     * @param boolean $bool The boolean flag value passed by 'do_parse_request'
     * @param \WP $wp       The global wp object passed by 'do_parse_request'
     */
    function dispatch( $bool, \WP $wp );
}
