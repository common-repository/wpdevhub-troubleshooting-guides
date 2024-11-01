<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 3/12/15
 * Time: 11:26 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_ZoneManager{

    // General Settings and Constants

    // Database Install Routine
    public static function installDatabase(){
        global $wpdb;

        // Get the DB CharSet
        $charset_collate = $wpdb->get_charset_collate();

        // Setup the Poll Question table
        $zone_table_name = DSCF_DTG_Zone::getTableName();
        $zone_item_table_name = DSCF_DTG_ZoneItem::getTableName();
        $sql = "
            CREATE TABLE $zone_table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                blogId BIGINT(20),
                typeId int(11) NOT NULL,
                data mediumblob,
                UNIQUE KEY id (id)
            ) $charset_collate;
            CREATE TABLE $zone_item_table_name (
                id int(11) unsigned NOT NULL AUTO_INCREMENT,
                blogId BIGINT(20),
                itemId int(11) NOT NULL,
                zoneId int(11) NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;
            ";

        // Run the SQL
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public static function shortcodeHandlerZone($atts){
        $html = "";
        $zone_id = 0;
        extract( shortcode_atts( array(
            'zone_id' => 0
        ), $atts ) );

        $zone = DSCF_DTG_Zone::get($zone_id);
        if(!empty($zone)){
            $html = $zone->getDisplayCode();
        }

        return $html;
    }

    public static function validateFreeZone($typeId){
        global $_POST;
        $zones = DSCF_DTG_Zone::getAllByTypeId($typeId);
        $zoneId = null;
        foreach($zones as $zone){
            if(!empty($zoneId)){
                DSCF_DTG_Zone::deleteById($zone->id);
            }else{
                $zoneId = $zone->id;
            }
        }

        if(empty($zoneId)){
            $zone = new DSCF_DTG_Zone();
            $zone->typeId = $typeId;
            $zone->text = "Default Zone";
            $zone->save();
            $zoneId = $zone->id;
        }

        if(empty($_REQUEST['id'])){
            $_REQUEST['id']=$zoneId;
        }

        return $zoneId;
    }

}
