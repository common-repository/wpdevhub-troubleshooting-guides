<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 3/31/15
 * Time: 10:01 AM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_Zone extends DSCF_DTG_StandardObjectRecord{

    public $text;
    public $items=array();
    public $additionalHtml = '';
    public $typeId = 0;
    public $notes = '';             // Generic Notes about the Zone
    public $showTitle=false;

    const TYPE_DPM = 1;
    const TYPE_DFM = 2;
    const TYPE_DBM = 3;
    const TYPE_DTM = 4;
    const TYPE_DTT = 5;
    const TYPE_DTA = 6;
    const TYPE_DCM_TOP_TEN = 7;
    const TYPE_DCM_DRIP_LISTS = 8;
    const TYPE_DCM_FACTOIDS = 9;
    const TYPE_DDL = 10;
    const TYPE_DAS = 11;

    const TABLE_NAME = "zone";

    public function __construct(){

        global $wpdb;
        $table_name = self::getTableName();

        //Pass it to the parent to setup some common items
        $this->create();

        //Save in the DB
        try{
            $wpdb->insert(
                $table_name,
                array(
                    'blogId' => $this->blogId,
                    'typeId' => $this->typeId,
                    'data' => self::pack($this)
                ),
                array(
                    '%d',
                    '%d',
                    '%s'
                )
            );
        }catch(Exception $e){
            //DSCF_DTG_Utilities::logMessage('Error creating Poll: '.$e->getMessage());
            return false;
        }

        //Get the ID of the inserted row and save it back to the object
        try{
            $this->id = $wpdb->insert_id;
            $this->save();
        }catch(Exception $e){
            //DSCF_DTG_Utilities::logMessage('Error creating Poll: '.$e->getMessage());
            return false;
        }

        //Return the object
        return $this;
    }

    public function save(){
        global $wpdb;
        $table_name = self::getTableName();
        $this->setLastModified();
        try{
            $wpdb->update(
                $table_name,
                array(
                    'typeId' => $this->typeId,
                    'data' => self::pack($this)
                ),
                array( 'ID' => $this->id ),
                array(
                    '%d',
                    '%s'
                ),
                array( '%d' )
            );
        }catch(Exception $e){
            //DSCF_DTG_Utilities::logMessage('Error saving Object: '.$e->getMessage());
            return false;
        }
        return true;
    }

    // Controlled by Parent Class
    //public static function get($id){}

    // Controlled by Parent Class
    //public static function getAll($start=0,$limit=500){}

    // Controlled by Parent Class
    //public static function deleteById($id){}

    /*
     * A function to retrieve all objects based upon the Type of Zone being passed,
     * Returns by ID desc by default to eliminate the need for a "Most Recent" function
     */
    public static function getAllByTypeId($typeId, $start=0, $limit=500){

        // Setup the variables
        global $wpdb;
        $tableName = self::getTableName();

        // Query the Data
        $sql = $wpdb->prepare(
            "
            SELECT * FROM $tableName
            WHERE typeId = %d
             AND blogId=%d
            ORDER BY id DESC
            LIMIT %d,%d
            ",
            $typeId,
            get_current_blog_id(),
            $start,
            $limit
        );

        // Get the results
        $packedObjects = self::executeQuery($sql, ARRAY_A);

        // Test the results are valid and unpack if so
        $goodObjects = self::unpackObjects($packedObjects);

        // Return the results
        return $goodObjects;

    }

    /*
     * Deletes all Zone Objects by a certain Type ID
     */
    public static function deleteAllByTypeId($typeId){

        // Setup the variables
        global $wpdb;
        $tableName = self::getTableName();

        $result = $wpdb->delete( $tableName, array( 'typeId' => $typeId ), array( '%d' ) );

        // Return the result
        return $result;

    }

    /*
     * Add a bunch of new items for a given Zone
     */
    public function addItemIds($itemIds){
        if(!is_array($itemIds)){
            $itemIds = array($itemIds);
        }
        foreach($itemIds as $itemId){
            $this->items[] = $itemId;
            $link = new DSCF_DTG_ZoneItem($itemId, $this->id);
        }
        $this->save();
    }

    public function getItemIds(){
        $records = DSCF_DTG_ZoneItem::getAllForZoneId($this->id);
        $itemIds = array();
        foreach($records as $record){
            $itemIds[] = $record['itemId'];
        }

        error_log("RECORDS: ".print_r($records, true));
        error_log("ITEMIDS: ".print_r($itemIds, true));

        return $itemIds;
    }

    public function removeItemIds($itemIds){
        foreach($itemIds as $itemId){
            DSCF_DTG_ZoneItem::deleteSingleRelationship($itemId, $this->id);
        }
    }

    public function removeAllItemIds(){
        DSCF_DTG_ZoneItem::deleteAllForZoneId($this->id);
    }

    public static function getZonesForItem($itemId){
        $zones = array();
        $records = DSCF_DTG_ZoneItem::getAllForItemId($itemId);
        foreach($records as $record){
            if(array_key_exists('zoneId', $record)){
                $zones[] = $record['zoneId'];
            }
        }
        return $zones;
    }

    public static function getZoneObjectsForItem($itemId){
        $zoneIds = DSCF_DTG_ZoneItem::getAllForItemId($itemId);
        $objects = array();
        foreach($zoneIds as $zoneId){
            $zone = self::get($zoneId);
            if(!empty($zone)){
                $objects[]=$zone;
            }
        }
        return $objects;
    }

    public static function addZonesForItem($itemId, $zoneIds){
        foreach($zoneIds as $zoneId){
            $link = new DSCF_DTG_ZoneItem($itemId, $zoneId);
        }
    }

    public static function removeZonesForItem($itemId, $zoneIds){
        foreach($zoneIds as $zoneId){
            DSCF_DTG_ZoneItem::deleteSingleRelationship($itemId, $zoneId);
        }
    }

    public static function removeAllZonesForItem($itemId){
        DSCF_DTG_ZoneItem::deleteAllForItemId($itemId);
    }


    public static function editorBuildOptions($object){
        $zoneTypeId = self::getTypeIdByAppCode(WPDEVHUB_CONST_DTG_APP_CODE);
        $options=array();
        $options[]=array(
            'rowType'=>'SectionHeader',
            'title'=>'Basic '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.' Properties',
        );
        $options[]=array(
            'title'=>'ID',
            'objectType'=>DSCF_DTG_StandardEditor::OT_NUMERIC,
            'objectName'=>'id',
            'formType'=>DSCF_DTG_StandardEditor::ET_TEXT_READONLY,
            'value'=>($object)?$object->id:'',
            'help'=>''
        );
        $typeIdToUse = ($object)?$object->typeId:$zoneTypeId;
        $options[]=array(
            'title'=>WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.' Type (HIDDEN)',
            'objectType'=>DSCF_DTG_StandardEditor::OT_NUMERIC,
            'objectName'=>'typeId',
            'formType'=>DSCF_DTG_StandardEditor::ET_HIDDEN,
            'value'=>$typeIdToUse,
            'help'=>'The Type of '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.'.  Once created '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.'s cannot change type.'
        );
        /*
        $options[]=array(
            'title'=>WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.' Type',
            'objectType'=>DSCF_DTG_StandardEditor::OT_SKIP,
            'objectName'=>'skip',
            'formType'=>DSCF_DTG_StandardEditor::ET_HTML,
            'value'=>DSCF_DTG_Zone::getFormattedTypeString($typeIdToUse),
            'help'=>'The Type of '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.'.  Once created '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.'s cannot change type.'
        );
        */
        $options[]=array(
            'title'=>'Title',
            'objectType'=>DSCF_DTG_StandardEditor::OT_STRING,
            'objectName'=>'text',
            'formType'=>DSCF_DTG_StandardEditor::ET_TEXT,
            'value'=>($object)?$object->text:'',
            'help'=>'The Name of the '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME,
            'size'=>100,
        );

        $options[]=array(
            'title'=>'Show Title',
            'objectType'=>DSCF_DTG_StandardEditor::OT_BOOLEAN,
            'objectName'=>'showTitle',
            'formType'=>DSCF_DTG_StandardEditor::ET_CHECKBOX,
            'value'=>($object)?$object->showTitle:DSCF_DTG_StandardSetting::getSetting('dpm_default_show_legend'),
            'help'=>'If Checked, will display the '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.' title with the '.WPDEVHUB_CONST_DTG_ZONE_ITEM_NAME.' (if item type supports this feature)',
        );

        $options[]=array(
            'title'=>'Status',
            'objectType'=>DSCF_DTG_StandardEditor::OT_NUMERIC,
            'objectName'=>'status',
            'formType'=>DSCF_DTG_StandardEditor::ET_MENU_STATUS,
            'formOptions'=>DSCF_DTG_StandardObjectRecord::getAllStatusMarks(),
            'value'=>($object)?$object->status:DSCF_DTG_StandardObjectRecord::STATUS_ACTIVE,
            'help'=>'Select whether this '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.' is active or inactive'
        );
        $options[]=array(
            'rowType'=>'SectionHeader',
            'title'=>'Item Selection',
        );

        // The Zone Item Picker
        $items = array();
        $itemsArray = array();
        switch($zoneTypeId){
            case DSCF_DTG_Zone::TYPE_DPM:
                $items = DSCF_DTG_StandardPollQuestion::getAll(0,1000);
                $itemsArray = DSCF_DTG_StandardObjectRecord::getBasicArrayFromObjects($items);
                break;
            case DSCF_DTG_Zone::TYPE_DTM:
                $items = DSCF_DTG_StandardTipItem::getAll(0,1000);
                $itemsArray = DSCF_DTG_StandardObjectRecord::getBasicArrayFromObjects($items);
                break;
            case DSCF_DTG_Zone::TYPE_DTA:
                $items = DSCF_DTG_StandardTemplateItem::getAll(0,1000);
                $itemsArray = DSCF_DTG_StandardObjectRecord::getBasicArrayFromObjects($items);
                break;
            case DSCF_DTG_Zone::TYPE_DCM_DRIP_LISTS:
            case DSCF_DTG_Zone::TYPE_DCM_TOP_TEN:
            case DSCF_DTG_Zone::TYPE_DCM_FACTOIDS:
                $items = DSCF_DTG_StandardContentItem::getAll(0,1000);
                $itemsArray = DSCF_DTG_StandardObjectRecord::getBasicArrayFromObjects($items);
                break;
            case DSCF_DTG_Zone::TYPE_DBM:
                $items = DSCF_DTG_Banners_Banner::getAll(0,1000);
                $itemsArray = DSCF_DTG_StandardObjectRecord::getBasicArrayFromObjects($items, 'title');
                break;
        }


        // Zone Items
        $options[]=array(
            'title'=>WPDEVHUB_CONST_DTG_ZONE_ITEM_NAME.'s',
            'objectType'=>DSCF_DTG_StandardEditor::OT_ARRAY,
            'objectName'=>'items',
            'formOptions'=>$itemsArray,
            'formType'=>DSCF_DTG_StandardEditor::ET_ZONE_ITEM_PICKER,
            'value'=>($object)?$object->getItemIds():'',
            'help'=>'Select the '.WPDEVHUB_CONST_DTG_ZONE_ITEM_NAME.'s that should be included in this '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME
        );

        $options[]=array(
            'rowType'=>'SectionHeader',
            'title'=>'Additional Information',
        );
        $options[]=array(
            'title'=>'Created Date',
            'objectType'=>DSCF_DTG_StandardEditor::OT_SKIP,
            'objectName'=>'createdDate',
            'formType'=>DSCF_DTG_StandardEditor::ET_TEXT_READONLY,
            'value'=>($object)?DSCF_DTG_StandardObjectRecord::formatDate($object->createdDate):'',
            'help'=>'The date the '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.' was created on'
        );
        $options[]=array(
            'title'=>'Last Hit Date',
            'objectType'=>DSCF_DTG_StandardEditor::OT_DATE,
            'objectName'=>'lastHitDate',
            'formType'=>DSCF_DTG_StandardEditor::ET_TEXT_READONLY,
            'value'=>($object)?DSCF_DTG_StandardObjectRecord::formatDate($object->lastHitDate,"M j, Y, g:i a"):'',
            'help'=>'The date the '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.' was last accessed.'
        );
        $options[]=array(
            'title'=>'Current Hit Count',
            'objectType'=>DSCF_DTG_StandardEditor::OT_NUMERIC,
            'objectName'=>'hitCount',
            'formType'=>DSCF_DTG_StandardEditor::ET_TEXT,
            'value'=>($object)?$object->hitCount:'',
            'help'=>'The current number of hits/displays the '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.' has received',
            'size'=>10,
        );
        $options[]=array(
            'title'=>'Additional HTML',
            'objectType'=>DSCF_DTG_StandardEditor::OT_STRING,
            'objectName'=>'additionalHtml',
            'formType'=>DSCF_DTG_StandardEditor::ET_TEXTAREA,
            'size'=>100,
            'value'=>($object)?$object->additionalHtml:'',
            'help'=>'You can optionally include any custom html underneath the Item. (if '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.' type supports this feature)'
        );
        return $options;
    }

    public static function managerBuildOptions($objects){
        $rows = array();
        foreach($objects as $object){
            $row = array();
            $row[] = array(
                'title'=>'ID',
                'content'=>$object->id,
            );
            $row[] = array(
                'title'=>'Status',
                'content'=>$object->getFormattedStatusImage($object->status, '24px'),
            );
            $row[] = array(
                'title'=>'Edit',
                'url'=>DSCF_DTG_Utilities::getPageUrl(WPDEVHUB_CONST_DTG_PAGE_ZONES, array('id'=>$object->id, 'typeId'=>$object->typeId)),
                'image'=>WPDEVHUB_CONST_DTG_URL_IMAGES.'/page_edit.png',
                'image_tooltip'=>'Edit '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME,
            );
            $row[] = array(
                'title'=>'Text',
                'content'=>$object->text,
            );

            // Zone Items
            $row[] = array(
                'title'=>WPDEVHUB_CONST_DTG_ZONE_ITEM_NAME.'s',
                'content'=>count($object->items).' ',
            );
            /*
            $row[] = array(
                'title'=>'Report',
                'url'=>'reports.php?pollId='.$object->id,
                'image'=>WPDEVHUB_CONST_DTG_URL_IMAGES.'/document_layout.png',
                'image_tooltip'=>'Build Report',
            );
            */
            $typesWithPreview = array(
                self::TYPE_DPM,
                self::TYPE_DTM
            );

            /*
            if(in_array($object->typeId, $typesWithPreview)){
                $row[] = array(
                    'title'=>'Preview',
                    'url'=>$object->getPreviewUrl(),
                    'image'=>WPDEVHUB_CONST_DTG_URL_IMAGES.'/magnifier.png',
                    'image_tooltip'=>'Preview Zone',
                );
            }
            */
            $row[] = array(
                'title'=>'Created Date',
                'content'=>DSCF_DTG_StandardObjectRecord::formatSiteDate($object->createdDate),
            );
            $row[] = array(
                'title'=>'Shortcode',
                'content'=>"<input size='20' onclick='this.focus();this.select();' type='text' value='".$object->getShortcode()."' />",
            );
            $row[] = array(
                'title'=>'Hits',
                'content'=>max(0, $object->hitCount).' ',
            );
            $row[] = array(
                'title'=>'Delete',
                'url'=>DSCF_DTG_Utilities::getPageUrl(WPDEVHUB_CONST_DTG_PAGE_ZONES, array('delete'=>1,'id'=>$object->id, 'typeId'=>$object->typeId)),
                'image'=>WPDEVHUB_CONST_DTG_URL_IMAGES.'/delete.png',
                'image_tooltip'=>'Delete '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME,
            );
            $rows[]=$row;
        }
        return $rows;
    }

    /*
    * Returns the current TYPE flags as an array for Editors and such
    */
    public static function getAllTypeMarks(){
        $collection = array();
        $collection[self::TYPE_DPM]=self::getFormattedStatusString(self::TYPE_DPM);
        $collection[self::TYPE_DTM]=self::getFormattedStatusString(self::TYPE_DTM);
        $collection[self::TYPE_DTT]=self::getFormattedStatusString(self::TYPE_DTT);
        return $collection;
    }

    /*
     * Returns a human readable version of the TYPE Flag
     */
    public static function getFormattedTypeString($id){
        return WPDEVHUB_CONST_DTG_ZONE_ITEM_NAME.'s';
    }

    /*
    * Returns a human readable version of the TYPE Flag
    */
    public static function getTypeIdByAppCode($appCode){
        $collection = array(
            'dbm'=>self::TYPE_DBM,
            'dpm'=>self::TYPE_DPM,
            'dtm'=>self::TYPE_DTM,
            'dtt'=>self::TYPE_DTT,
        );
        if(array_key_exists($appCode, $collection)){
            return $collection[$appCode];
        }
        return '';
    }

    public function getPreviewUrl(){
        return DSCF_DTG_Utilities::getPageUrl(WPDEVHUB_CONST_DTG_PAGE_PREVIEW, array('ac'=>'3','zoneId'=>$this->id));
    }

    public function getDisplayCode(){
        $html = '';
        switch($this->typeId){
            case self::TYPE_DPM:
                $html .= '<div class="WPDEVHUB_CONST_DTG_SLUG-WidgetWrapper" dpm_zone="'.$this->id.'">';
                //$html .= '<a href="https://www.wpdevhub.com">Loading the Dimbal Poll Manager</a>';
                $html .= '</div>';
                break;
            case self::TYPE_DTM:
                $html .= '<div class="dtmWidgetWrapper" dtm_zone="'.$this->id.'">';
                //$html .= '<a href="https://www.wpdevhub.com">Loading the Dimbal Tips Manager</a>';
                $html .= '</div>';
                break;
        }
        return $html;
    }

    public function getShortcode(){

        // Type specific functions
        switch($this->typeId){
            case self::TYPE_DBM:
                return DSCF_DTG_Banners_Main::getShortcodeDisplayZone($this->id);
        }

        // Default handling
        return self::buildShortcodeHelper(array('zone_id'=>$this->id));
    }

    public static function getZoneManagerPageContent(){

        $zoneTypeId = self::getTypeIdByAppCode(WPDEVHUB_CONST_DTG_APP_CODE);

        $html = "";

        // Build the Header
        $html .= DSCF_DTG_Utilities::buildHeader(array(
            'title'=>WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.' Manager',
            'icon'=>WPDEVHUB_CONST_DTG_URL_IMAGES.'/bricks.png',
            'description'=>'Use this manager to build and maintain custom '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME.'s for your '.WPDEVHUB_CONST_DTG_ZONE_ITEM_NAME.'s.',
            'buttons'=>array(
                0=>array('text'=>'Create New '.WPDEVHUB_CONST_DTG_ZONE_GROUP_NAME,'params'=>array('page'=>DSCF_DTG_Utilities::buildPageSlug(WPDEVHUB_CONST_DTG_PAGE_ZONES), 'id'=>'new')),
                1=>array('text'=>'View All','params'=>array('page'=>DSCF_DTG_Utilities::buildPageSlug(WPDEVHUB_CONST_DTG_PAGE_ZONES))),
            )
        ));

        // Check for a delete request
        $html .= DSCF_DTG_Zone::checkForDelete(array());

        ///////////////////////  Editor DISPLAY  ///////////////////////////
        $html .= DSCF_DTG_StandardEditor::buildPageTemplate(DSCF_DTG_Utilities::buildAppClassName('DSCF_DTG_Zone'));

        // If the ID field was removed or is not present that means we want the Manager
        $id = DSCF_DTG_Utilities::getRequestVarIfExists('id');
        if(empty($id)){
            ///////////////////////  MANAGER DISPLAY  ///////////////////////////
            $rows = DSCF_DTG_Zone::managerBuildOptions(DSCF_DTG_Zone::getAllByTypeId($zoneTypeId));
            $html .= DSCF_DTG_StandardManager::buildManagerTable($rows);
        }

        // Close the wrapper
        $html .= DSCF_DTG_Utilities::buildFooter();

        return $html;

    }

}
