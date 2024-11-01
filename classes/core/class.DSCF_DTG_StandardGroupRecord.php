<?php

class DSCF_DTG_StandardGroupRecord{

    public $id;
    public $title='';
    public $status=self::STATUS_ACTIVE;

    public static $isSetup = false;

    public static $objects=array();

    const STATUS_ACTIVE = 1;		// Active
    const STATUS_INACTIVE = 2;		// Inactive - may be temporary

    const BASE_FOLDER = 'folder/';
    const TITLE = 'Group Object';

    public function __construct($options=array()){

        $defaults = array(
            'id'=>0,
            'title'=>'',
            'status'=>static::STATUS_ACTIVE,		// Keep this as the default so children define as little as possible
        );

        $options = array_merge($defaults, $options);

        foreach($options as $name=>$value){
            $this->$name = $value;
        }

        // Add it to the static list
        static::$objects[$this->id] = $this;

        // Return it
        return $this;

    }

    /*
     * Updates an Object in the Objects list
     *
     */
    public function save(){
        static::$objects[$this->id] = $this;
    }

    public static function organizeObjectsById($objects=array()){
        $tmpArray = $objects;
        $objects = array();
        foreach($tmpArray as $object){
            $objects[$object->id]=$object;
        }
        return $objects;
    }

    public static function get($id){
        if(array_key_exists($id, static::$objects)){
            return static::$objects[$id];
        }
        return false;
    }

    public static function getAll(){
        return static::$objects;
    }

    public static function getAllActive(){
        $items = array();
        foreach(static::$objects as $object){
            if($object->status == static::STATUS_ACTIVE){
                $items[] = $object;
            }
        }
        return $items;
    }

    public function isActive(){
        if($this->status == static::STATUS_ACTIVE){
            return true;
        }
        return false;
    }

    public static function getMenuList($activeOnly = true){
        $menu = array();
        if($activeOnly){
            $items = static::getAllActive();
        }else{
            $items = static::getAll();
        }
        foreach($items as $object){
            $menu[$object->id]=$object->title;
        }
        return $menu;
    }

    public static function getAllStatusMarks(){
        $statusCollection = array();
        $statusCollection[self::STATUS_ACTIVE]=self::getFormattedStatusString(self::STATUS_ACTIVE);
        $statusCollection[self::STATUS_INACTIVE]=self::getFormattedStatusString(self::STATUS_INACTIVE);
        return $statusCollection;
    }

    public static function getFormattedStatusString($status){
        $statusCollection = array(
            self::STATUS_ACTIVE=>'Active',
            self::STATUS_INACTIVE=>'Inactive',
        );
        return $statusCollection[$status];
    }

    public static function getFormattedStatusImage($status, $size="24"){
        $statusCollection = array(
            self::STATUS_ACTIVE=>'<img src="'.WPDEVHUB_CONST_DTG_URL_IMAGES.'accept.png" style="width:'.$size.'px;" title="Active" />',
            self::STATUS_INACTIVE=>'<img src="'.WPDEVHUB_CONST_DTG_URL_IMAGES.'cancel.png" style="width:'.$size.'px;" title="Inactive" />',
        );
        return $statusCollection[$status];
    }

    public static function getObjectByFieldValue($field, $value){
        foreach(static::$objects as $object){
            if(isset($object->$field)){
                if($object->$field == $value){
                    return $object;
                }
            }
        }
        return false;
    }

    public static function getAllObjectsByFieldValue($field, $value){
        $items = array();
        foreach(static::$objects as $object){
            if(isset($object->$field)){
                if($object->$field == $value){
                    $items[] = $object;
                    DSCF_DTG_Utilities::logMessage(__CLASS__."::".__FUNCTION__." Object Field Found : field($field) value($value) objectValue(".$object->$field.")");
                }else{
                    DSCF_DTG_Utilities::logMessage(__CLASS__."::".__FUNCTION__." Object Field Does Not Equal : field($field) value($value) objectValue(".$object->$field.")");
                }
            }else{
                DSCF_DTG_Utilities::logMessage(__CLASS__."::".__FUNCTION__." Object Field Not Set : field($field) value($value)");
            }

        }
        return $items;
    }

    public static function getAllValuesForObjects($field, $passedObjects=false){
        $values = array();
        $objects = static::$objects;
        if(!empty($passedObjects)){
            $objects = $passedObjects;
        }
        foreach($objects as $object){
            if(isset($object->$field)){
                $values[$object->id]=$object->$field;
            }
        }
        return $values;
    }

    public function getFieldValue($field){
        if(isset($this->$field)){
            return $this->$field;
        }
        return null;
    }

    public static function getFieldValueByObjectId($field,$id){
        if(array_key_exists($id, static::$objects) && isset(static::$objects[$id]->$field)){
            return static::$objects[$id]->$field;
        }
        return null;
    }

    public static function getTitleById($id){
        return static::getFieldValueByObjectId('title',$id);
    }

    public function getTitle(){
        return static::getTitleById($this->id);
    }

    public static function getStatusById($id){
        return static::getFieldValueByObjectId('status',$id);
    }

    public static function alphabetizeObjects($objects, $field="title"){

        $tempArray = array();
        foreach($objects as $object){
            $tempArray[$object->$field]=$object;
        }

        ksort($tempArray);

        return $tempArray;

    }

}
