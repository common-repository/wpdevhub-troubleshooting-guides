<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 2/1/17
 * Time: 2:34 PM
 * To change this template use File | Settings | File Templates.
 */



class DSCF_DTG_VirtualPages_TemplateLoader implements DSCF_DTG_VirtualPages_TemplateLoaderInterface{

    public function init(DSCF_DTG_VirtualPages_PageInterface $page){
        $this->templates = wp_parse_args(
            array( 'page.php', 'index.php' ), (array) $page->getTemplate()
        );
    }

    public function load() {
        do_action( 'template_redirect' );

        //DSCF_DTG_Utilities::logMessage("All Templates: ".print_r($this->templates, true));

        $template = '';

        foreach ( $this->templates as $template_name ) {

            //DSCF_DTG_Utilities::logMessage("template_name to look for: ".print_r($template_name, true));

            if ( !$template_name )
                    continue;
            if ( file_exists(STYLESHEETPATH . '/' . $template_name)) {
                $template = STYLESHEETPATH . '/' . $template_name;
                break;
            } elseif ( file_exists(TEMPLATEPATH . '/' . $template_name) ) {
                $template = TEMPLATEPATH . '/' . $template_name;
                break;
            } elseif ( file_exists( ABSPATH . WPINC . '/theme-compat/' . $template_name ) ) {
                $template = ABSPATH . WPINC . '/theme-compat/' . $template_name;
                break;
            } elseif ( file_exists( WPDEVHUB_CONST_DTG_DIR . '/' . $template_name ) ) {
                $template = WPDEVHUB_CONST_DTG_DIR . '/' . $template_name;
                break;
            }

        }

        //$template = locate_template( array_filter( $this->templates ) );

        //DSCF_DTG_Utilities::logMessage("Template: ".print_r($template, true));

        $filtered = apply_filters( 'template_include',
            apply_filters( 'virtual_page_template', $template )
        );
        if ( empty( $filtered ) || file_exists( $filtered ) ) {
            $template = $filtered;
        }
        if ( ! empty( $template ) && file_exists( $template ) ) {
            require_once $template;
        }
    }

}
