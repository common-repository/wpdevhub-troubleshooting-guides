<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 5/4/15
 * Time: 10:45 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_StandardSetting{

    public static $settings = array();

    public static $defaults = null;

    public static function initSettings($settingsArray=array()){
        // Don't load it into the settings array - wait to pull them from the DB

        $defaults = array();
        foreach($settingsArray as $settingsRow){
            if(array_key_exists('objectName', $settingsRow) && array_key_exists('value', $settingsRow)){
                $defaults[$settingsRow['objectName']]=$settingsRow['value'];
            }
        }

        self::$defaults = $defaults;

        // To make the plugin faster - do not make the DB calls for settings until actually needed

    }

    public static function saveToDb($settingName, $settingValue){
        $settingName = 'DSCF_DTG_'.$settingName;
        if(is_bool($settingValue)){
            if($settingValue){
                $settingValue = 1;
            }else{
                $settingValue = 0;
            }
        }
        update_option($settingName, $settingValue, false);
    }

    /*
    * A function to retrieve all objects of the calling type,
    * Returns by ID desc by default to eliminate the need for a "Most Recent" function
    */
    public static function getFromDb($settingName, $defaultValue){
        $settingName = 'DSCF_DTG_'.$settingName;
        $optionValue = get_option($settingName, $defaultValue);
        return $optionValue;
    }

    /*
     * Returns a named setting from the static settings object
     */
    public static function getSetting($settingName){

        // Get the setting
        if(!array_key_exists($settingName, self::$settings)){

            $defaultValue = null;
            if(!empty(self::$defaults)){
                if(array_key_exists($settingName, self::$defaults)){
                    $defaultValue = self::$defaults[$settingName];
                }

            }

            // Setting is not in the array yet - check the DB
            $dbValue = self::getFromDb($settingName, $defaultValue);

            // Save it locally
            self::$settings[$settingName] = $dbValue;

        }

        return self::$settings[$settingName];
    }

    // This function is used to insert a single settings key/value pair into the settings object and save it into the options DB
    public static function saveSetting($settingName, $settingValue){
        self::$settings[$settingName] = $settingValue;
        self::saveToDb($settingName, $settingValue);
    }

    public function save(){
        /*
         * if(!empty(self::$settings)){
            foreach(self::$settings as $settingName=>$settingValue){
                self::saveToDb($settingName, $settingValue);
            }
        }
        */
    }

    public static function getSettingsObject($options=array()){
        $settingsObject = new DSCF_DTG_StandardSetting();

        if(!empty($options)){
            foreach($options as $option){
                if(array_key_exists('objectName', $option)){
                    $objectName = $option['objectName'];
                    $defaultValue = $option['value'];
                    $settingsObject->$objectName=self::getSetting($objectName, $defaultValue);
                }
            }
        }

        return $settingsObject;
    }

    public static function saveSettingsObject($settingsObject, $options=array()){
        if(!empty($options)){
            foreach($options as $option){
                if(array_key_exists('objectName', $option)){
                    $objectName = $option['objectName'];
                    self::saveToDb($objectName, $settingsObject->$objectName);
                }
            }
            // Commenting out this message as the Editor Save routine covers us on it.
            //DSCF_DTG_Utilities::addUserMessage('Settings Saved Successfully');
        }
    }

}
