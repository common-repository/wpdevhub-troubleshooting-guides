<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 3/13/15
 * Time: 12:33 PM
 *
 * Base class for the Common Data Objects stored within this Plugin
 */
class DSCF_DTG_StandardObjectRecord{

    public $id;                             // The ID of the Object
    public $blogId=0;                       // The blog ID this object is assigned to
    public $userId=0;                       // The user id of the user that created the object
    public $slug='';
    public $status=self::STATUS_ACTIVE;     // Status flag whether the object is active or inactive
    public $lastModified = 0;               // The date the object was last saved
    public $startDate=0;                    // The start date for the object to be considered public
    public $endDate=0;                      // The end date for the object to be considered public
    public $enforceStartEndDates=false;     // Whether or not to enforce the start and end dates
    public $createdDate=0;                  // The date the object was created on
    public $hitCount=0;                     // How many hits the object has received
    public $lastHitDate=0;                  // The date of the last hit for this object
    public $isSampleData=false;             // Whether or not this was fake data setup for display purposes

    // Child classes should overwrite this constant
    const TABLE_NAME="";

    // Status markers
    const STATUS_ACTIVE=1;
    const STATUS_INACTIVE=2;

    // Delete Salt - Helps to protect against unintended delete requests
    const DELETE_SALT='SAFIASHIUHFLK#(#O#P!@123nklnlj123e';

    const RATING_HML_LOW = 1;
    const RATING_HML_MED = 2;
    const RATING_HML_HIGH = 3;

    const HASH_KEY_LENGTH=5;        // length of 5 has approx 33 Million Combinations

    /*
     * Some common setup routines across all objects
     */
    public function create(){
        $this->createdDate = time();
        $this->status = self::STATUS_ACTIVE;
        $this->setLastModified();
        $this->blogId = get_current_blog_id();
        $this->userId = get_current_user_id();
    }

    /*
     * Pack the contents of the Object into a single field for DB Storage
     */
    public static function pack($object){
        return base64_encode(serialize($object));
    }

    /*
     * Unpack the contents of the Object from the Database
     */
    public static function unpack($object){
        return unserialize(base64_decode($object));
    }

    /*
     * Function to validate and unpack a single object
     */
    public static function unpackObject($packedObject){

        $goodObject = false;
        if(is_array($packedObject) && array_key_exists('data',$packedObject)){
            // Good Object - unpack
            $goodObject = self::unpack($packedObject['data']);
        }
        return $goodObject;
    }

    /*
     * Function to validate and unpack a group of objects in bulk
     */
    public static function unpackObjects($packedObjects){
        $goodObjects = array();
        foreach($packedObjects as $packedObject){
            if(is_array($packedObject) && array_key_exists('data',$packedObject)){
                // Good Object - unpack
                $goodObjects[] = self::unpack($packedObject['data']);
            }
        }
        return $goodObjects;
    }

    /*
     * Returns a single object from the calling Database Table
     */
    public static function get($id){

        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Query the Data
        $sql = $wpdb->prepare(
            "
            SELECT * FROM $tableName
            WHERE id=%d
            AND blogId=%d
            ",
            $id,
            get_current_blog_id()
        );

        // Get the results
        return self::getBySql($sql);
    }

    /*
     * A function to retrieve all objects of the calling type,
     * Returns by ID desc by default to eliminate the need for a "Most Recent" function
     */
    public static function getAll($start=0, $limit=5000){

        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Query the Data
        $sql = $wpdb->prepare(
            "
            SELECT * FROM $tableName
            WHERE blogId=%d
            ORDER BY id DESC
            LIMIT %d,%d
            ",
            get_current_blog_id(),
            $start,
            $limit
        );

        return self::getMultipleBySql($sql);
    }

    public static function getAllActive($start=0, $limit=5000){
        $allObjects = static::getAll($start, $limit);

        $activeObjects = array();

        foreach($allObjects as $object){
            if($object->status == static::STATUS_ACTIVE){
                $activeObjects[] = $object;
            }
        }

        return $activeObjects;
    }

    public static function getAllActiveAsArray($start=0, $limit=5000, $fieldValue='title',$fieldKey='id'){
        $objects = static::getAllActive();
        $array = self::getBasicArrayFromObjects($objects, $fieldValue, $fieldKey);
        return $array;
    }

    /*
     * A function to retrieve all objects of the calling type,
     * Returns by ID desc by default to eliminate the need for a "Most Recent" function
     */
    public static function getRandom($quantity=0, $exclusionIds=array()){

        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Query the Data
        $sql = $wpdb->prepare(
            "
            SELECT * FROM $tableName
            WHERE blogId=%d
            AND id NOT IN (%s)
            ORDER BY RAND()
            LIMIT 0,%d
            ",
            get_current_blog_id(),
            implode(",",$exclusionIds),
            $quantity
        );

        return self::getMultipleBySql($sql);
    }

    public static function getBySlug($slug){

        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Query the Data
        $sql = $wpdb->prepare(
            "
            SELECT * FROM $tableName
            WHERE blogId=%d
            AND slug=%s
            ",
            get_current_blog_id(),
            $slug
        );

        return self::getBySql($sql);
    }

    /*
    * Returns a single object from the calling Database Table
    */
    public static function getByHashKey($hashKey){

        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Query the Data
        $sql = $wpdb->prepare(
            "
            SELECT * FROM $tableName
            WHERE blogId=%d
            AND hashKey=%s
            ",
            get_current_blog_id(),
            $hashKey
        );

        // Get the results
        return self::getBySql($sql);
    }

    /*
     * Get total count of records
     */
    public static function getCount(){

        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Query the Data
        $sql = "SELECT count(*) as count FROM $tableName WHERE blogId=".get_current_blog_id().";";

        return self::getCountBySql($sql);

    }

    /*
     * Generic GetBy function to make child class adoptions lighter
     */
    public static function getBySql($sql=''){

        // Get the results
        $packedObject = self::executeRowQuery($sql, ARRAY_A);

        // Test the result is valid and unpack if so
        $goodObject = self::unpackObject($packedObject);

        // Return the results
        return $goodObject;
    }

    /*
     * Generic GetAllBy function to make child class adoptions lighter
     */
    public static function getMultipleBySql($sql){

        // Get the results
        $packedObjects = self::executeQuery($sql, ARRAY_A);

        // Test the results are valid and unpack if so
        $goodObjects = self::unpackObjects($packedObjects);

        // Return the results
        return $goodObjects;
    }

    /*
     * Generic GetCount function to make child class adoptions lighter
     */
    public static function getCountBySql($sql){

        // Get the results
        $return = self::executeRowQuery($sql, ARRAY_A);

        // get the count
        $count = 0;
        if(array_key_exists('count', $return)){
            $count = $return['count'];
        }

        return $count;
    }

    /*
     * Delete a single object by ID reference -- currently only supporting deleting one by one
     */
    public static function deleteById($id){

        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Get the results
        $result = $wpdb->delete( $tableName, array( 'ID' => $id ), array( '%d' ) );

        // Return the result
        return $result;
    }

    /*
     * A generic Query wrapper to get a single result from the DB
     */
    public static function executeRowQuery( $sql, $outputType = ARRAY_A, $offset=0 ){
        global $wpdb;
        return $wpdb->get_row( $sql, $outputType, $offset );
    }

    /*
     * A generic query wrapper to execute a query that returns results
     */
    public static function executeQuery( $sql, $outputType = ARRAY_A ){
        global $wpdb;
        return $wpdb->get_results( $sql, $outputType );
    }

    /*
     * A generic query wrapper to execute a query that returns results
     */
    public static function executeQueryAndUnpackObjects( $sql, $outputType = ARRAY_A ){
        global $wpdb;
        $packedObjects = $wpdb->get_results( $sql, $outputType );
        return self::unpackObjects($packedObjects);
    }

    /*
    * A generic query wrapper to execute a query, returns a numeric value indicating number of rows effected
    */
    public static function executeGenericQuery( $sql){
        global $wpdb;
        return $wpdb->query( $sql );
    }

    /*
     * Child classes should implement their own SAVE routines
     */
    public function save(){
        // Nothing to do in the parent
    }

    /*
     * Return the table name -- Child classes override this method
     */
    public static function getTableName(){
        global $wpdb;
        $name = $wpdb->base_prefix . WPDEVHUB_CONST_DTG_DB_PREFIX . '_' . static::TABLE_NAME;
        $name = str_replace("-","_",$name);
        return $name;
    }

    /*
     * Validates that a slug is at least set -- valuable in save routines
     */
    public function validateSlug($string){
        if(empty($this->slug) && !empty($string)){
            $this->updateSlug($string);
        }
    }

    /*
     * Determines if we need to update the objects slug based on the passed in string
     */
    public function updateSlug($string){
        $newSlug = DSCF_DTG_Utilities::buildSlug($string);
        if($this->slug != $newSlug){
            $this->slug = static::createNewSlug($newSlug);
        }
    }

    /*
     * Created a new slug for this object -- validates it against existing slugs
     */
    public static function createNewSlug($string){
        $newSlug = DSCF_DTG_Utilities::buildSlug($string);
        $slugAvailable = static::isSlugAvailable($newSlug);
        $counter = 0;
        while($slugAvailable == false){
            $counter++;
            $newSlug = DSCF_DTG_Utilities::buildSlug($string." ".$counter);
            $slugAvailable = static::isSlugAvailable($newSlug);
        }
        return $newSlug;
    }

    /*
     * Checks to see if a given slug is available or not
     */
    public static function isSlugAvailable($slug){
        if(static::getBySlug($slug)){
            return false; //Slug is in use and not available
        }
        return true;
    }

    public function validateHashKey(){
        if(empty($this->hashKey)){
            $this->buildHashKey();
        }
    }

    /*
     * Builds the HashKey as needed
     */
    public function buildHashKey($maxAttempts = 25){
        $goodHash = false;
        $attempts = 0;
        while($goodHash == false){
            $hashKey = DSCF_DTG_Utilities::generateSalt('', static::HASH_KEY_LENGTH, false);
            $testLink = self::getByHashKey($hashKey);
            if(empty($testLink)){
                $goodHash = true;
                $this->hashKey = $hashKey;
            }
            $attempts++;
            if($attempts > $maxAttempts){
                DSCF_DTG_Utilities::logError(__CLASS__."::".__FUNCTION__." :: Could not create new Hash Key, attempts timed out.");
                return false;
            }
        }
        return true;
    }

    /*
     * Saves the current timestamp as the last modified timestamp
     */
    public function setLastModified(){
        $this->lastModified = time();
    }

    /*
     * Increase the internal hit counter for this object
     */
    public function increaseHitCount($hitNumber=1){
        $this->hitCount=$this->hitCount+$hitNumber;
        $this->lastHitDate = time();
    }

    /*
     * Deletes all objects that are marked with the sample data flag
     */
    public static function deleteSampleData(){
        $objects = static::getAll();
        foreach($objects as $object){
            if($object->isSampleData){
                static::deleteById($object->id);
            }
        }
    }

    public static function buildShortcodeHelper($params){

        if(empty($params)){
            return '';
        }

        $paramString = '';
        foreach($params as $key=>$value){
            $paramString .= ' '.$key.'="'.$value.'"';
        }

        $html = '['.WPDEVHUB_CONST_DTG_SLUG.$paramString.']';
        return $html;
    }

    public static function buildShortcodeHelperWithInputTextField($params){
        $shortcode = self::buildShortcodeHelper($params);
        $html = '<input type="text" value="'.$shortcode.'" />';
        return $html;
    }

    /*
     * Returns the current Status flags as an array for Editors and such
     */
    public static function getAllStatusMarks(){
        $statusCollection = array();
        $statusCollection[self::STATUS_ACTIVE]=self::getFormattedStatusString(self::STATUS_ACTIVE);
        $statusCollection[self::STATUS_INACTIVE]=self::getFormattedStatusString(self::STATUS_INACTIVE);
        return $statusCollection;
    }

    /*
     * Returns a human readable version of the Status Flag
     */
    public static function getFormattedStatusString($status){
        $statusCollection = array(
            self::STATUS_ACTIVE=>'Active',
            self::STATUS_INACTIVE=>'Inactive',
        );
        return $statusCollection[$status];
    }

    /*
     * Returns an image representing the status
     */
    public static function getFormattedStatusImage($status, $width='32px'){
        $statusCollection = array(
            self::STATUS_ACTIVE=>'<img src="'.WPDEVHUB_CONST_DTG_URL_IMAGES.'/accept.png" style="width:'.$width.';" title="Active" />',
            self::STATUS_INACTIVE=>'<img src="'.WPDEVHUB_CONST_DTG_URL_IMAGES.'/cancel.png" style="width:'.$width.';" title="Inactive" />',
        );
        if(array_key_exists($status, $statusCollection)){
            return $statusCollection[$status];
        }
        return '';
    }

    /*
    * Returns the current Status flags as an array for Editors and such
    */
    public static function getAllHighMedLowMarks(){
        $statusCollection = array();
        $statusCollection[self::RATING_HML_LOW]=self::getFormattedHighMedLowString(self::RATING_HML_LOW);
        $statusCollection[self::RATING_HML_MED]=self::getFormattedHighMedLowString(self::RATING_HML_MED);
        $statusCollection[self::RATING_HML_HIGH]=self::getFormattedHighMedLowString(self::RATING_HML_HIGH);
        return $statusCollection;
    }

    /*
     * Returns a human readable version of the Status Flag
     */
    public static function getFormattedHighMedLowString($status){
        $statusCollection = array(
            self::RATING_HML_LOW=>'Low',
            self::RATING_HML_MED=>'Medium',
            self::RATING_HML_HIGH=>'High',
        );
        return $statusCollection[$status];
    }

    public function isActive(){
        if($this->status == self::STATUS_ACTIVE){
            return true;
        }
        return false;
    }

    /*
     * Gets a date variable according to the default format
     */
    public function getFormattedDate($variable){
        return self::formatDate($this->$variable);
    }

    /*
     * Format a timestamp or string according to the JQuery default Date format
     */
    public static function formatJQueryDate($date){
        //return self::formatDate($date, "m/j/Y");
        return self::formatDate($date, "F j, Y");
    }

    /*
     * Format a timestamp or string according to the JQuery default Date/Time format
     */
    public static function formatJQueryDateTime($date){
        //return self::formatDate($date, "m/j/Y H:i");
        return self::formatDate($date, "F j, Y H:i");
    }

    /*
     * Format a timestamp or string into a given passed format
     */
    public static function formatDate($date, $format="M j, Y"){
        if($date==''){
            return '';
        }elseif(is_numeric($date)){
            return date($format,$date);
        }else{
            $date = strtotime($date);
            return date($format,$date);
        }
    }

    /*
     * Format a timestamp or string into a given passed format (shortcut function)
     */
    public static function formatDateTime($date, $format="M j, Y g:ia"){
        return self::formatDate($date, $format);
    }

    /*
     * Takes a UTC timestamp and returns it as a formatted Time String according to the Site Settings
     */
    public static function formatSiteDate($timestamp, $format='M j, Y g:ia'){
        $timestamp = self::formatTimestampForSite($timestamp);
        return date_i18n($format, $timestamp, true);
    }

    /*
     * Takes a UTC timestamp and changes it to a timestamp that offset according to the Site Settings
     */
    public static function formatTimestampForSite($timestamp){
        $timezone_str = get_option('timezone_string');
        //DSCF_DTG_Utilities::logMessage("Timezone setting: ".$timezone_str);
        if(empty($timezone_str)){
            $gmt_offset = get_option('gmt_offset');
            //DSCF_DTG_Utilities::logMessage("GMT Offset setting: ".$gmt_offset);
            if(empty($gmt_offset)){
                $gmt_offset = 0;
                //DSCF_DTG_Utilities::logMessage("RESETTING GMT OFFSET".$gmt_offset);
            }
            $timezone_str = DSCF_DTG_Utilities::gmtOffsetToTimezoneName($gmt_offset);
            //DSCF_DTG_Utilities::logMessage("Timezone String From GMT Offset: ".$timezone_str);
        }

        if(empty($timezone_str)){
            //DSCF_DTG_Utilities::logMessage("Timezone String Was Empty: ".$timezone_str);
            $timezone_str = 'UTC';
        }

        $timezone = new \DateTimeZone($timezone_str);


        //DSCF_DTG_Utilities::logMessage("Final Timezone setting: ".$timezone_str);

        // The date in the local timezone.
        $date = new \DateTime(null, $timezone);
        $date->setTimestamp($timestamp);
        $date_str = $date->format('Y-m-d H:i:s');

        // Pretend the local date is UTC to get the timestamp
        // to pass to date_i18n().
        $utc_timezone = new \DateTimeZone('UTC');
        $utc_date = new \DateTime($date_str, $utc_timezone);
        $timestamp = $utc_date->getTimestamp();

        return $timestamp;
    }

    /*
     * Basically does the opposite of formatTimestampForSite and converts a Site timestamp back to a GMT timestamp
     */
    public static function formatSiteTimestampToGmtTimestamp($timestamp){
        $altTimestamp = self::formatTimestampForSite($timestamp);
        $delta = $timestamp-$altTimestamp;
        return $timestamp+$delta;
    }

    /*
     * Format an income date string from various formats into a unix timestamp
     */
    public static function formatIncomingDateString($date="now"){
        //Formats the date for save into the object
        if(empty($date)){
            $date=time();
        }else{
            $date=strtotime($date);
        }
        return $date;
    }

    /*
     * Returns a unix timestamp formatted for the Start of Day for a given unix timestamp
     */
    public static function standardizeForStartOfDay($date){
        return mktime(0, 0, 0, date("m", $date), date("d", $date), date("Y", $date));
    }

    /*
     * Returns a unix timestamp formatted for the End of Day for a given unix timestamp
     */
    public static function standardizeForEndOfDay($date){
        return mktime(23, 59, 59, date("m", $date), date("d", $date), date("Y", $date));
    }

    /*
     * Validates start and end dates for objects that support it
     */
    public function validateStartEndDates(){
        $time = time();

        if(empty($this->enforceStartEndDates)){
            // No need to enforce dates
            return true;
        }

        if(!empty($this->startDate) && ($this->startDate > $time)){
            // Start date is not empty and is in the future.  Object Dates are invalid
            //error_log("Start Date is Invalid: ".$this->startDate);
            return false;
        }

        if(!empty($this->endDate) && ($this->endDate < $time)){
            // End date is not empty and is in the past.  Object Dates are invalid
            //error_log("End Date is Invalid: ".$this->endDate);
            return false;
        }

        //error_log("Dates are fine: StartDate[".$this->startDate."] EndDate[".$this->endDate."] Time[".$time."]");

        // If we got here then no other checks - so the dates must be valid
        return true;
    }

    /*
     * Get a single object with a matching ID from a batch of objects
     */
    public static function getByIdFromObjects($objects, $id){
        foreach($objects as $object){
            if($object->id == $id){
                return $object;
            }
        }
        return null;
    }

    /*
     * Returns a simple Key=>Value pair array for a batch of objects
     */
    public static function getBasicArrayFromObjects($objects,$fieldValue='text',$fieldKey='id'){
        $items = array();
        foreach($objects as $object){
            $items[$object->$fieldKey]=$object->$fieldValue;
        }
        return $items;
    }

    /*
     * Filter function.  Used to save time on the editor pages
     */
    public static function filterObjectsByFieldValue($objects, $fieldKey, $fieldValue){
        $newObjectArray = array();
        foreach($objects as $arrayKey=>$object){
            //DSCF_DTG_Utilities::logMessage("Inside filterObjectsByFieldValue Set1[".$object->$fieldKey."] Set2[$fieldValue]");
            if($object->$fieldKey == $fieldValue){
                $newObjectArray[$arrayKey]=$object;
            }
        }
        return $newObjectArray;
    }


    /*
     * Editor and Manager functions -- in most cases they will be overriden
     */
    public static function editorBuildOptions($object){
        return array();
    }

    public static function managerBuildOptions($objects){
        return array();
    }

    public static function filterEditorOptions($options, $approvedObjectNames){

        $approvedKeys = array_flip($approvedObjectNames);

        $newOptions = array();
        foreach($options as $optionRow){
            if(array_key_exists('objectName',$optionRow) && array_key_exists($optionRow['objectName'], $approvedKeys)){
                $newOptions[]=$optionRow;
            }
        }

        return $newOptions;
    }

    public function getDeleteObjectHash(){
        $hash = md5($this->id.__CLASS__.self::DELETE_SALT);
        return $hash;
    }

    public function getObjectHashId(){
        return spl_object_hash($this);
    }

    public static function getObjectByClassAndId($className, $id){
        $object = call_user_func(array($className, 'get'), $id);
        return $object;
    }

    public static function checkForDelete($options=array()){
        $defaults = array(
            'class'=>get_called_class(),
            'requestVars'=>$_REQUEST
        );
        $options = array_merge($defaults, $options);
        $return=self::checkForDeleteConfirmation($options);
        if(!$return){
            $return = self::checkForDeleteRequest($options);
        }
        return $return;
    }

    public static function checkForDeleteRequest($options){
        //DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__.__FUNCTION__);
        $requestVars = $options['requestVars'];
        $return = false;
        //DSCF_DTG_Utilities::logMessage("Delete(".$requestVars['delete'].") ID(".$requestVars['id'].")");
        if(isset($requestVars['delete'])){
            if(isset($requestVars['id'])){
                $objectToDelete = self::getObjectByClassAndId($options['class'],$requestVars['id']);
                if($objectToDelete){
                    $return = $objectToDelete->prepareDeleteConfirmation($options['class']);
                }else{
                    // Bad Delete request -- go to the redirect url
                }
            }else{
                // Bad Delete request -- go to the redirect url
            }
        }
        return $return;
    }

    public static function checkForDeleteConfirmation($options){
        //DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__.__FUNCTION__);
        $requestVars = $options['requestVars'];
        $return = false;
        if(isset($requestVars['deleteHash'])){
            if(isset($requestVars['id'])){
                $objectToDelete = self::getObjectByClassAndId($options['class'],$requestVars['id']);
                if($objectToDelete){
                    return $objectToDelete->processDeleteRequest($requestVars);
                }
            }
        }
        return $return;
    }

    public function prepareDeleteConfirmation($type){
        //DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__.__FUNCTION__);
        // A delete request was made and an ID was set... show the confirmation box for this delete object
        $hash = $this->getDeleteObjectHash();
        $msg = '';
        $msg .= '
			<div id="confirmationDeleteDialog" class="dtg-pbox3" style="">
				<img src="'.WPDEVHUB_CONST_DTG_URL_IMAGES.'/error.png" style="float:left; width:50px; padding-right:25px;" />

					<p>Please confirm that you would like to delete the following entry.  This action cannot be undone.</p>
					<div>Type: '.$type.' (ID: '.$this->id.')</div>
					<div>Created Date: '.$this->formatDate($this->createdDate).'</div>
					<br />
					<form method="post" action="#">
						<input type="hidden" name="deleteHash" value="'.$hash.'" />
						<input type="hidden" name="id" value="'.$this->id.'" />
						<input type="submit" class="dtg-button" name="submit" value="Confirm Delete" />
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" onclick="javascript:$(\'#confirmationDeleteDialog\').fadeOut();">Cancel</a>
					</form>
			</div>
		';
        return $msg;
    }

    public function processDeleteRequest($requestVars){
        //DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__.__FUNCTION__);
        if(!isset($requestVars['deleteHash'])){
            //DSCF_DTG_Utilities::logError('Delete Attempt made without a Delete Hash');
            return '';
        }

        $hash = $this->getDeleteObjectHash();
        if($requestVars['deleteHash']!=$hash){
            //DSCF_DTG_Utilities::logError('Delete Attempt made with invalid Delete Hash Passed('.$requestVars['deleteHash'].') Generated('.$hash.')');
            return '';
        }

        //If it got this far then we are good to go... go ahead and do the delete
        try{
            $this->deleteById($this->id);
            $this->additionalDeleteSteps();
            unset($_REQUEST['id']); // Unset this var so we can go back to the manager
            //UserMessages::add('Object successfully deleted.', UserMessages::L_SUCCESS, true);
        }catch(Exception $e){
            //DSCF_DTG_Utilities::logError("Error Caught Processing Delete command - were we acting upon the Standard Object? Message: ".$e->getMessage());
        }

        return '';

    }

    public function additionalDeleteSteps(){

    }

    public static function getSelectOptionsFromObjects($objects, $fieldValue='name', $fieldKey='id'){
        $items = array();
        foreach($objects as $object){
            $items[]="<option value='".$object->$fieldKey."'>".$object->$fieldValue."</option>";
        }
        return $items;
    }

    public function getUserObject($field="userId"){
        if(empty($this->$field)){
            return "";
        }
        return get_user_by('id', $this->$field);
    }

    public function getUserName(){
        $userObject = $this->getUserObject();
        $userName = DSCF_DTG_Utilities::getFromObject('user_nicename', $userObject);
        return $userName;
    }

    public function unsetVars($vars){
        foreach($vars as $var){
            // Do not check for the property otherwise null values will not be removed
            unset($this->$var);
        }
    }

}
