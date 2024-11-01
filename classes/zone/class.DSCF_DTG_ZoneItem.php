<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 3/31/15
 * Time: 10:01 AM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_ZoneItem extends DSCF_DTG_StandardLinkRecord{

    const TABLE_NAME = 'zone-item';
    const COLUMN_A = 'itemId';
    const COLUMN_B = 'zoneId';

    /*
	  *	Create a new link for a given Item ID and an Zone ID
	  */
    public function __construct($itemId, $zoneId){
        return parent::__construct($itemId, $zoneId);
    }

    /*
       *	Delete a single Item ID and Zone ID relationship
       */
    public static function deleteRelationship($itemId, $zoneId){
        self::deleteSingleRelationship($itemId, $zoneId);
    }

    /*
    *	Delete all links to a given Item ID
    */
    public static function deleteAllForItemId($id){
        self::deleteAllForColumn(self::COLUMN_A, $id);
    }

    /*
       *	Delete all links to a given Zone ID
       */
    public static function deleteAllForZoneId($id){
        self::deleteAllForColumn(self::COLUMN_B, $id);
    }

    /*
       *	Get all Zone Ids linked to the given Item ID
       */
    public static function getAllForItemId($id){
        return self::getAllByForColumn(self::COLUMN_A, $id);
    }

    /*
       *	Get all Item Ids linked to the given Zone ID
       */
    public static function getAllForZoneId($id){
        return self::getAllByForColumn(self::COLUMN_B, $id);
    }

    /*
       *	Get a count of all Zone Ids linked to the given Item ID
       */
    public static function getCountForItemId($id){
        return self::getCountForColumn(self::COLUMN_A, $id);
    }

    /*
       *	Get a count of all Item Ids linked to the given Zone ID
       */
    public static function getCountForZoneId($id){
        return self::getCountForColumn(self::COLUMN_B, $id);
    }

}
