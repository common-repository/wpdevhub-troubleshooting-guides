<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 2/1/17
 * Time: 2:34 PM
 * To change this template use File | Settings | File Templates.
 */


interface DSCF_DTG_VirtualPages_TemplateLoaderInterface{

    /**
     * Setup loader for a page objects
     *
     * @param DSCF_DTG_VirtualPages_PageInterface $page matched virtual page
     */
    public function init(DSCF_DTG_VirtualPages_PageInterface $page);

    /**
     * Trigger core and custom hooks to filter templates,
     * then load the found template.
     */
    public function load();
}
