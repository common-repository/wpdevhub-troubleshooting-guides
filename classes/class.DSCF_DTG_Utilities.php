<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 3/9/15
 * Time: 11:14 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_Utilities{

    const USER_MESSAGES_KEY = "WPDEVHUB_CONST_DTG_USER_MESSAGES_COOKIE";

    const BASIC_ALIGN_LEFT = 1;
    const BASIC_ALIGN_CENTER = 2;
    const BASIC_ALIGN_RIGHT = 3;

    /**
     * The version string for the Database
     *
     * @static
     * @return string
     */
    public static function buildDatabaseVersionString(){
        return WPDEVHUB_CONST_DTG_SLUG . '-db-version';
    }

    /**
     * Routine to check the Database version and upgrade the schema as needed.
     *
     * @static
     * @param $currentVersion
     * @param $installCallback
     */
    public static function checkForUpgrade($currentVersion, $installCallback){
        global $wpdb;
        $installedVersion = DSCF_DTG_Utilities::getInstalledVersionNumber();
        if ( $currentVersion != $installedVersion) {
            // Upgrade the Database
            call_user_func($installCallback);

            // Update the version number in the Options Database
            update_option( self::buildDatabaseVersionString(), $currentVersion );
        }
    }

    /**
     * Gets the Installed Version of the Dimbal Plugin and formats it for numeric values, etc
     *
     * @static
     * @return int
     */
    public static function getInstalledVersionNumber(){
        $installed_ver = get_option( self::buildDatabaseVersionString() );
        if(empty($installed_ver)){
            $installed_ver = 0;
        }
        return $installed_ver;
    }

    /**
     * Checks the user defined settings to see if the Framework was disabled
     *
     * @static
     * @return null
     */
    public static function isPluginEnabled(){
        return DSCF_DTG_StandardSetting::getSetting('plugin_enabled');
    }

    /**
     * This is just a helper function... this doesn't actually set whether a plugin is truly PRO or FREE
     *
     * @static
     * @return bool
     */
    public static function isPluginPro(){
        if(strtolower(WPDEVHUB_CONST_DTG_LEVEL) == 'pro'){
            return true;
        }
        return false;
    }

    /**
     * @static
     * @return string
     */
    public static function getBasePluginFile(){
        // Can replace index.php with a configurable constant if we ever need to
        return WPDEVHUB_CONST_DTG_SLUG."/index.php";
    }

    /**
     * Called via the register function.  Use the $plugin_page variable to get the page slug.  The page must exist within the pages folder named according to the designated slug
     *
     * @static
     *
     */
    public static function renderPage(){
        global $plugin_page;
        $plugin_page = str_replace("-free", "", $plugin_page);
        $plugin_page = str_replace("-pro", "", $plugin_page);
        include WP_PLUGIN_DIR.'/'.WPDEVHUB_CONST_DTG_FOLDER.'/pages/'.$plugin_page.'.php';
    }

    /**
     * Builds a full localized slug from a page name
     *
     * @static
     * @param $page
     * @return string
     */
    public static function buildPageSlug($page){
        return WPDEVHUB_CONST_DTG_SLUG.'-'.$page;
    }

    /**
     * Builds a page url for use in admin links and so forth.  Appended via a query string parameter.
     *
     * @static
     * @param $page
     * @param array $params
     * @return string
     */
    public static function getPageUrl($page, $params=array()){
        $url = '?page=' . self::buildPageSlug($page);
        if(!empty($params)){
            $url = add_query_arg($params, $url);
        }
        return $url;
    }

    /**
     * @static
     * @param $url
     */
    public static function redirect($url){
        wp_redirect($url);
        exit();
    }

    /**
     * @static
     * @param $handlers
     * @param $callback
     */
    public static function ajaxRegisterPublicHandlers($handlers, $callback){
        DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__."::".__FUNCTION__);
        foreach($handlers as $action=>$handler){
            add_action( 'wp_ajax_nopriv_'.$action, $callback );     // Non logged in users
        }
    }

    /**
     * @static
     * @param $handlers
     * @param $callback
     */
    public static function ajaxRegisterAdminHandlers($handlers, $callback){
        DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__."::".__FUNCTION__);
        foreach($handlers as $action=>$handler){
            add_action( 'wp_ajax_'.$action, $callback );            // Logged in users
        }
    }

    /**
     * @static
     * @param $handlers
     */
    public static function ajaxProcessHandler($handlers){
        DSCF_DTG_Utilities::logMessage("Inside ".__CLASS__."::".__FUNCTION__);

        // Setup the response Object -- ALWAYS JSON
        header( "Content-Type: application/json" );

        DSCF_DTG_Utilities::logMessage("inside ajaxProcessHandler");

        // Route the call as appropriate
        $response = '';
        $action = DSCF_DTG_Utilities::getRequestVarIfExists('action');
        if(array_key_exists($action, $handlers)){
            $call = $handlers[$action];
            $response = call_user_func($call);
        }

        echo json_encode($response);

        // Always exit after processing the AJAX
        exit;
    }

    /**
     * @static
     *
     */
    public static function enqueueJqueryUi(){
        // For simplicity we are linking to Google's hosted jQuery UI CSS.
        wp_register_style( 'jquery-ui', 'http://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css' );
        wp_enqueue_style( 'jquery-ui' );
    }

    /**
     * Takes in a class name such as "Dimbal" and returns an app formatted name such as "Dimbal_DPM"
     *
     * DEPRECATED
     *
     * @static
     * @param $className
     * @return mixed
     */
    public static function buildAppClassName($className){
        return $className;
        //$newClassName = $className . '_' . strtoupper(WPDEVHUB_CONST_DTG_APP_CODE) . '_' . strtoupper(WPDEVHUB_CONST_DTG_PLUGIN_LEVEL);
        //return $newClassName;
    }

    /**
     * @static
     * @param $options
     * @return string
     */
    public static function buildButton($options){
        $defaults = array(
            'url'=>'?',
            'params'=>array(),
            'method'=>'post',
            'text'=>'New Button',
            'target'=>''
        );
        $options = array_merge($defaults, $options);

        $params = '';
        foreach($options['params'] as $k=>$v){
            if(!empty($params)){
                $params .= "&";
            }
            $params .= $k . "=" . $v;
        }
        $url = $options['url'].$params;

        $html = '
            <div style="display:inline-block; padding:2px;">
                <a href="'.$url.'" target="'.$options['target'].'"><div class="button">'.$options['text'].'</div></a>
            </div>
        ';

        return $html;
    }

    /**
     * @static
     * @param array $options
     * @return string
     */
    public static function buildHeader($options=array()){

        $defaults = array(
            'title'=>'Dimbal Software',
            'icon'=>null,
            'description'=>null,
            'buttons'=>array(),
        );
        $options = array_merge($defaults, $options);

        $html = '';

        $html .= '<div id="dtg-wrapper" class="dtg-wrapper">';   // Wrapper for all our pages

            $html .= '<div style="display:table; width:inherit;">';
                $html .= '<div style="display:table-cell; vertical-align:middle; width:57px;"><img src="'.$options['icon'].'" alt="'.$options['title'].'" style="vertical-align:middle; margin: 5px 20px 5px 5px;" /></div>';
                $html .= '<div style="display:table-cell; vertical-align:middle;"><h1 style="vertical-align:middle;">'.$options['title'].'</a></div>';
                $html .= '<div style="display:table-cell; vertical-align:middle; text-align:right;">';
                    $html .= '<p style="font-style:italic;">'.$options['description'].'</p>';
                    $html .= '<div>';
                    foreach($options['buttons'] as $buttonOptions){
                        $html .= self::buildButton($buttonOptions);
                    }
                    $html .= '</div>';
                $html .= '</div>';
            $html .= '</div>';

            $html .= '<br />';

        return $html;
    }

    /**
     * @static
     * @return string
     */
    public static function buildFooter(){

        $html = '';

        $html .= self::displayAllUserMessages();    // User Messages display - if any

        $html .= '</div>'; // Close the common wrapper

        return $html;
    }

    /**
     * @static
     * @param $objectClassname
     */
    public static function buildStandardObjectPage($objectClassname){

        // Check for a delete request
        echo call_user_func(array($objectClassname, 'checkForDelete'), array('class'=>$objectClassname));

        ///////////////////////  Editor DISPLAY  ///////////////////////////
        echo DSCF_DTG_StandardEditor::buildPageTemplate($objectClassname);

        // If the ID field was removed or is not present that means we want the Manager
        $id = DSCF_DTG_Utilities::getRequestVarIfExists('id');
        if(empty($id)){
            ///////////////////////  MANAGER DISPLAY  ///////////////////////////
            $objects = call_user_func(array($objectClassname,'getAll'));

            $rows = call_user_func(array($objectClassname,'managerBuildOptions'), $objects);
            echo DSCF_DTG_StandardManager::buildManagerTable($rows);
        }

        // Close the wrapper
        echo DSCF_DTG_Utilities::buildFooter();

    }

    /**
     * Makes an email address string in a specific format for use in email headers
     *
     * @static
     * @param string $name
     * @param string $email
     * @return bool|string
     */
    public static function makeEmailAddressStringForHeaders($name="", $email=""){

        if(empty($email)){
            return false;
        }

        if(!is_email($email)){
            return false;
        }

        if(empty($name)){
            return $email;
        }

        return $name." <".$email.">";
    }


    /**
     * Gets the user data for a given user id and returns a designated field value
     *
     * @static
     * @param $user_id
     * @param string $field_name
     * @param string $field_value       // A default of sorts
     * @return string
     */
    public static function getUserDataField($user_id, $field_name="user_login", $field_value=""){
        $user = get_userdata($user_id);
        if(!empty($user) && isset($user->$field_name)){
            $field_value = $user->$field_name;
        }
        return $field_value;
    }

    /**
     * Checks if the variable exists - does not change the value
     *
     * @static
     * @param $fieldName
     * @return null
     */
    public static function getRequestVarIfExists($fieldName){
        if(array_key_exists($fieldName, $_REQUEST)){
            return $_REQUEST[$fieldName];
        }
        return null;
    }

    /**
     * @static
     * @param $msg
     */
    public static function logMessage($msg){
        error_log("[".WPDEVHUB_CONST_DTG_APP_CODE."] ".$msg);
    }

    /**
     * @static
     * @param $msg
     */
    public static function logError($msg){
        error_log("[".WPDEVHUB_CONST_DTG_APP_CODE."] ".$msg);
    }

    /**
     * @static
     * @return array
     */
    public static function getUserMessages(){
        $messages = array();
        if(array_key_exists(self::USER_MESSAGES_KEY, $_SESSION)){
            $messages = $_SESSION[self::USER_MESSAGES_KEY];
        }
        if(empty($messages) || !is_array($messages)){
            $messages = array();
        }
        return $messages;
    }

    /**
     * @static
     * @param $msg
     */
    public static function addUserMessage($msg){
        self::startSession();   // make sure the session has started
        $messages = self::getUserMessages();
        $messages[] = $msg;
        $_SESSION[self::USER_MESSAGES_KEY] = $messages;
    }

    /**
     * Will add a user message only if the messages are empty - useful for catching errors, etc
     *
     * @static
     * @param $msg
     */
    public static function AddUserMessageIfMessagesAreEmpty($msg){
        self::startSession();   // make sure the session has started
        $messages = self::getUserMessages();
        if(empty($messages)){
            $messages[] = $msg;
            $_SESSION[self::USER_MESSAGES_KEY] = $messages;
        }
    }

    /**
     * @static
     * @return string
     */
    public static function displayAllUserMessages(){
        $messages = self::getUserMessages();

        $html = '';
        if(!empty($messages)){
            $html .= '<div id="dtg-user-messages-tmp" style="display:none;">';
            foreach($messages as $message){
                $rand = rand(100000,999999);
                $html .= '<div class="dtg-user-message" id="dimbal_user_message_'.$rand.'" onclick="dimbalUserMessages.remove('.$rand.')">'.$message.'</div>';
            }
            $html .= '</div>';
        }

        unset($_SESSION[self::USER_MESSAGES_KEY]);

        return $html;

    }

    /**
     * Start the PHP Session Object if it has not yet started
     *
     * @static
     *
     */
    public static function startSession(){
        if(!session_id()) {
            session_start();
        }
    }


    /**
     * @static
     * @param $offset
     * @return bool
     */
    public static function gmtOffsetToTimezoneName($offset)
    {
        $offset *= 3600; // convert hour offset to seconds
        $abbrarray = timezone_abbreviations_list();
        foreach ($abbrarray as $abbr)
        {
            foreach ($abbr as $city)
            {
                if ($city['offset'] == $offset)
                {
                    return $city['timezone_id'];
                }
            }
        }

        return FALSE;
    }

    /**
     * Takes in a UTC based timestamp and converts it for display according to the WordPress sites local settings
     *
     * @static
     * @param $timestamp
     * @param string $format
     * @return mixed
     */
    public static function getLocalDateStringFromTimestamp($timestamp, $format='F d, Y H:i'){
        $timezone_str = get_option('timezone_string') ?: 'UTC';
        $timezone = new \DateTimeZone($timezone_str);

        // The date in the local timezone.
        $date = new \DateTime(null, $timezone);
        $date->setTimestamp($timestamp);
        $date_str = $date->format('Y-m-d H:i:s');

        // Pretend the local date is UTC to get the timestamp
        // to pass to date_i18n().
        $utc_timezone = new \DateTimeZone('UTC');
        $utc_date = new \DateTime($date_str, $utc_timezone);
        $timestamp = $utc_date->getTimestamp();

        return date_i18n($format, $timestamp, true);
    }


    /**
     * Convert a hexadecimal color code to its RGB equivalent
     *
     * @param string $hexStr (hexadecimal color value)
     * @param boolean $returnAsString (if set true, returns the value separated by the separator character. Otherwise returns associative array)
     * @param string $seperator (to separate RGB values. Applicable only if second parameter is true.)
     * @return array or string (depending on second parameter. Returns False if invalid hex color value)
     */
    public static function hex2rgb($hexStr, $returnAsString = false, $seperator = ',') {
        $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
        $rgbArray = array();
        if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
            $colorVal = hexdec($hexStr);
            $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
            $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArray['blue'] = 0xFF & $colorVal;
        } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
            $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
        } else {
            return false; //Invalid hex color code
        }
        return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
    }

    /**
     * Converts an RGB Color code to it's Hexadecimal equivalent
     *
     * @static
     * @param $rgb string the RGB color code
     *
     * @return string the hexadecimal color code equivalent
     */
    public static function rgb2hex($rgb) {
        $hex = "#";
        $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

        return $hex; // returns the hex value including the number sign (#)
    }


    /**
     * Takes in a string based color name such as blue and converts it to the hexadecimal equivalent or false if not found
     *
     * @static
     * @param $colorName The string representing a color
     * @param bool $includeHashTag Whether or not to include a hashtag in the output
     *
     * @return bool|string The hexadecimal color string equivalent
     */
    public static function colorNameToHex($colorName, $includeHashTag=true)
    {
        // standard 147 HTML color names
        $colors  =  array(
            'aliceblue'=>'F0F8FF',
            'antiquewhite'=>'FAEBD7',
            'aqua'=>'00FFFF',
            'aquamarine'=>'7FFFD4',
            'azure'=>'F0FFFF',
            'beige'=>'F5F5DC',
            'bisque'=>'FFE4C4',
            'black'=>'000000',
            'blanchedalmond '=>'FFEBCD',
            'blue'=>'0000FF',
            'blueviolet'=>'8A2BE2',
            'brown'=>'A52A2A',
            'burlywood'=>'DEB887',
            'cadetblue'=>'5F9EA0',
            'chartreuse'=>'7FFF00',
            'chocolate'=>'D2691E',
            'coral'=>'FF7F50',
            'cornflowerblue'=>'6495ED',
            'cornsilk'=>'FFF8DC',
            'crimson'=>'DC143C',
            'cyan'=>'00FFFF',
            'darkblue'=>'00008B',
            'darkcyan'=>'008B8B',
            'darkgoldenrod'=>'B8860B',
            'darkgray'=>'A9A9A9',
            'darkgreen'=>'006400',
            'darkgrey'=>'A9A9A9',
            'darkkhaki'=>'BDB76B',
            'darkmagenta'=>'8B008B',
            'darkolivegreen'=>'556B2F',
            'darkorange'=>'FF8C00',
            'darkorchid'=>'9932CC',
            'darkred'=>'8B0000',
            'darksalmon'=>'E9967A',
            'darkseagreen'=>'8FBC8F',
            'darkslateblue'=>'483D8B',
            'darkslategray'=>'2F4F4F',
            'darkslategrey'=>'2F4F4F',
            'darkturquoise'=>'00CED1',
            'darkviolet'=>'9400D3',
            'deeppink'=>'FF1493',
            'deepskyblue'=>'00BFFF',
            'dimgray'=>'696969',
            'dimgrey'=>'696969',
            'dodgerblue'=>'1E90FF',
            'firebrick'=>'B22222',
            'floralwhite'=>'FFFAF0',
            'forestgreen'=>'228B22',
            'fuchsia'=>'FF00FF',
            'gainsboro'=>'DCDCDC',
            'ghostwhite'=>'F8F8FF',
            'gold'=>'FFD700',
            'goldenrod'=>'DAA520',
            'gray'=>'808080',
            'green'=>'008000',
            'greenyellow'=>'ADFF2F',
            'grey'=>'808080',
            'honeydew'=>'F0FFF0',
            'hotpink'=>'FF69B4',
            'indianred'=>'CD5C5C',
            'indigo'=>'4B0082',
            'ivory'=>'FFFFF0',
            'khaki'=>'F0E68C',
            'lavender'=>'E6E6FA',
            'lavenderblush'=>'FFF0F5',
            'lawngreen'=>'7CFC00',
            'lemonchiffon'=>'FFFACD',
            'lightblue'=>'ADD8E6',
            'lightcoral'=>'F08080',
            'lightcyan'=>'E0FFFF',
            'lightgoldenrodyellow'=>'FAFAD2',
            'lightgray'=>'D3D3D3',
            'lightgreen'=>'90EE90',
            'lightgrey'=>'D3D3D3',
            'lightpink'=>'FFB6C1',
            'lightsalmon'=>'FFA07A',
            'lightseagreen'=>'20B2AA',
            'lightskyblue'=>'87CEFA',
            'lightslategray'=>'778899',
            'lightslategrey'=>'778899',
            'lightsteelblue'=>'B0C4DE',
            'lightyellow'=>'FFFFE0',
            'lime'=>'00FF00',
            'limegreen'=>'32CD32',
            'linen'=>'FAF0E6',
            'magenta'=>'FF00FF',
            'maroon'=>'800000',
            'mediumaquamarine'=>'66CDAA',
            'mediumblue'=>'0000CD',
            'mediumorchid'=>'BA55D3',
            'mediumpurple'=>'9370D0',
            'mediumseagreen'=>'3CB371',
            'mediumslateblue'=>'7B68EE',
            'mediumspringgreen'=>'00FA9A',
            'mediumturquoise'=>'48D1CC',
            'mediumvioletred'=>'C71585',
            'midnightblue'=>'191970',
            'mintcream'=>'F5FFFA',
            'mistyrose'=>'FFE4E1',
            'moccasin'=>'FFE4B5',
            'navajowhite'=>'FFDEAD',
            'navy'=>'000080',
            'oldlace'=>'FDF5E6',
            'olive'=>'808000',
            'olivedrab'=>'6B8E23',
            'orange'=>'FFA500',
            'orangered'=>'FF4500',
            'orchid'=>'DA70D6',
            'palegoldenrod'=>'EEE8AA',
            'palegreen'=>'98FB98',
            'paleturquoise'=>'AFEEEE',
            'palevioletred'=>'DB7093',
            'papayawhip'=>'FFEFD5',
            'peachpuff'=>'FFDAB9',
            'peru'=>'CD853F',
            'pink'=>'FFC0CB',
            'plum'=>'DDA0DD',
            'powderblue'=>'B0E0E6',
            'purple'=>'800080',
            'red'=>'FF0000',
            'rosybrown'=>'BC8F8F',
            'royalblue'=>'4169E1',
            'saddlebrown'=>'8B4513',
            'salmon'=>'FA8072',
            'sandybrown'=>'F4A460',
            'seagreen'=>'2E8B57',
            'seashell'=>'FFF5EE',
            'sienna'=>'A0522D',
            'silver'=>'C0C0C0',
            'skyblue'=>'87CEEB',
            'slateblue'=>'6A5ACD',
            'slategray'=>'708090',
            'slategrey'=>'708090',
            'snow'=>'FFFAFA',
            'springgreen'=>'00FF7F',
            'steelblue'=>'4682B4',
            'tan'=>'D2B48C',
            'teal'=>'008080',
            'thistle'=>'D8BFD8',
            'tomato'=>'FF6347',
            'turquoise'=>'40E0D0',
            'violet'=>'EE82EE',
            'wheat'=>'F5DEB3',
            'white'=>'FFFFFF',
            'whitesmoke'=>'F5F5F5',
            'yellow'=>'FFFF00',
            'yellowgreen'=>'9ACD32');

        // Default value
        $color = false;

        // Convert to lower case
        $colorName = strtolower($colorName);

        // Check for the presence of the
        if (array_key_exists($colorName, $colors)){
            $color = $colors[$colorName];

            // Whether or not to include the hashtag
            if($includeHashTag){
                $color = '#'.$color;
            }
        }

        // return the result
        return $color;
    }

    /**
     * @static
     * @param $color
     * @return string
     */
    public static function colorHexInverse($color){
        $color = str_replace('#', '', $color);
        if (strlen($color) != 6){ return '000000'; }
        $rgb = '';
        for ($x=0;$x<3;$x++){
            $c = 255 - hexdec(substr($color,(2*$x),2));
            $c = ($c < 0) ? 0 : dechex($c);
            $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
        }
        return self::rgb2hex($rgb);
    }

    /**
     * Updates the text color array in the object.  Accepts a hex value or named string
     *
     * @static
     * @param $color
     * @return array|bool|string
     */
    public static function convertMixedColorValueToRgb($color){

        // See if we have a named color
        $newValue = DSCF_DTG_Utilities::colorNameToHex($color, false);
        if(!empty($newValue)){
            // A Hex value was returned
            $color = $newValue;
        }

        // Now let's convert the Hex to RGB
        $newValue = DSCF_DTG_Utilities::hex2RGB($color);
        if(empty($newValue)){
            // Conversion failed - didn't pass a valid color value or name
            //error_log("Color Conversion Failed: OLD($color) NEW($newValue)");
            return false;
        }

        //error_log("NEW COLOR VALUE: OLD($color) NEW($newValue)");

        return $newValue;

    }

    /**
     * Gets a named variable from a object.
     * Done in a safe manner to test presence of variable and validate object is an object.
     *
     * @static
     * @param string $key
     * @param null $object
     * @param null $default
     * @return null
     */
    public static function getFromObject($key='', $object=null, $default=null){
        if(!is_object($object)){
            return null;
        }
        if(isset($object->$key)){
            return $object->$key;
        }
        return $default;
    }

    /**
     * Safety method for retrieving an item from an array
     * Tests that the passed variable is an array and that the desired kley exists.
     * Will return the default if the key is invalid or does not exist
     *
     * @static
     * @param string $key
     * @param array $array
     * @param null $default
     * @return null
     */
    public static function getFromArray($key='', $array=array(), $default=null){
        if(!is_array($array)){
            return null;
        }
        if(array_key_exists($key, $array)){
            return $array[$key];
        }
        return $default;
    }

    /**
     * @static
     * @param string $key
     * @param array $array
     * @return array|null
     */
    public static function removeFromArray($key='', $array=array()){
        if(!is_array($array)){
            return null;
        }
        if(array_key_exists($key, $array)){
            unset($array[$key]);
        }
        return $array;
    }

    /**
     * @static
     * @param $arrayToTest
     * @param bool $emptyOk
     * @return bool
     */
    public static function validateArray($arrayToTest, $emptyOk=false){
        if(!is_array($arrayToTest)){
            return false;
        }
        if(!$emptyOk && empty($arrayToTest)){
            // Empty is not ok and yet the array is empty
            return false;
        }
        return true;
    }

    /**
     * @static
     * @param $array
     * @return bool
     */
    public static function getRandomArrayEntry($array){
        if(is_array($array) && !empty($array)){
            return $array[array_rand($array)];
        }
        return false;
    }

    /**
     * Function to ensure that a passed variable is an array
     *
     * @static
     * @param $arrayToTest
     * @return array
     */
    public static function ensureIsArray($arrayToTest){
        if(empty($arrayToTest) || !is_array($arrayToTest)){
            $arrayToTest = array();
        }
        return $arrayToTest;
    }

    /**
     * @static
     * @param $booleanToTest
     * @return bool
     */
    public static function ensureIsBoolean($booleanToTest){
        //DSCF_DTG_Utilities::logMessage("Bool To test [$booleanToTest]");
        if(empty($booleanToTest)){
            //DSCF_DTG_Utilities::logMessage("Returning FALSE - 1");
            return false;
        }elseif($booleanToTest === 0){
            //DSCF_DTG_Utilities::logMessage("Returning FALSE - 2");
            return false;
        }elseif($booleanToTest === "0"){
            //DSCF_DTG_Utilities::logMessage("Returning FALSE - 3");
            return false;
        }elseif(strtolower($booleanToTest) === "false"){
            //DSCF_DTG_Utilities::logMessage("Returning FALSE - 4");
            return false;
        }
        //DSCF_DTG_Utilities::logMessage("Returning TRUE");
        return true;
    }

    public static function getUserLoginsAsMenuArray($args=array()){
        $args['fields']=array('ID','user_login'); //Override this one value to trim the response
        $userObjects = get_users($args);
        $userNames = array();
        foreach($userObjects as $userObject){
            $userNames[$userObject->ID]=$userObject->user_login;
        }
        return $userNames;
    }

    /**
     * Compares whether or not a cooldown has expired
     *
     * @static
     * @param $checkTime - the original time being checked
     * @param $cooldown - the value (in seconds) that should be added
     * @param null $compareTime - the time to be compared against to see if the CheckTime and Cooldown combined are less then it.
     * @return bool
     */
    public static function hasCooldownElapsed($checkTime, $cooldown, $compareTime=null){

        if(empty($checkTime)){
            $checkTime = 0;
        }

        if(empty($compareTime)){
            $compareTime = time();
        }

        if(($checkTime + $cooldown) < $compareTime){
            return true;
        }
        return false;
    }


    /**
     * @static
     * @param $url
     * @param string $filename
     * @return string
     */
    public static function downloadTempImageFromUrl($url, $filename=""){

        // If empty, pass in the url and get the file name of the url being used (explode and use last part)
        if(empty($filename)){
            $urlParts = explode("/", $url);
            foreach($urlParts as $urlPart){
                $filename = $urlPart;
            }
        }

        // Create a file to use to download the image into
        $upload_dir = wp_upload_dir();
        $upload_file = $upload_dir['path'] . '/' . $filename;
        $counter = 0;
        $maxAttempts = 10;

        // Validate the destination file is unique
        while(is_file($upload_file)){
            $counter++;
            if($counter > $maxAttempts){
                // Break and exit as we could not find a good file name
                DSCF_DTG_Utilities::logError(__CLASS__."::".__FUNCTION__." - Could not create a unique filename - exiting routine. Filename[$filename]");
                return;
            }
            $upload_file = $upload_dir['path'] . '/' . $filename;
        }

        // Now save the image into the destination file
        $contents= file_get_contents($url);
        $save_file = fopen($upload_file, 'w');
        fwrite($save_file, $contents);
        fclose($save_file);

        // Return the full saved image
        return $upload_file;
    }

    /**
     * @static
     * @param $tmpFile
     * @param string $title
     * @param int $parentPostId
     * @return mixed
     */
    public static function importWordpressAttachement($tmpFile, $title="", $parentPostId=0){
        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype( basename( $tmpFile ), null );

        $wp_upload_dir = wp_upload_dir();

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'guid'           => $wp_upload_dir['url'] . '/' . basename( $tmpFile ),
            'post_mime_type' => $filetype['type'],
            'post_title'     => $title,
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $tmpFile, $parentPostId );

        // Commenting this out in favor of some other plugins that did the extra steps to get the full path
        $attach_data = wp_generate_attachment_metadata( $attach_id, $tmpFile );

        //$image_new = get_post( $attach_id );
        //$full_path = get_attached_file( $image_new->id );
        //$attach_data = wp_generate_attachment_metadata( $attach_id, $full_path );

        wp_update_attachment_metadata( $attach_id,  $attach_data );

        return $attach_id;

    }

    public static function getThumbnailHtmlForMediaId($mediaId, $classes="", $styles=array()){
        $imageMeta = wp_get_attachment_image_src($mediaId, 'thumbnail');
        if ( $imageMeta ) {
            list($src, $width, $height) = $imageMeta;
        }
        $styleString = DSCF_DTG_Utilities::buildStyleStringFromArray($styles);
        if(!empty($src)){
            return '<img src="'.$src.'" class="'.$classes.'" style="'.$styleString.'" />';
        }
        return '';
    }

    public static function buildStyleStringFromArray($styles){
        $string = "";
        foreach($styles as $key=>$value){
            $string .= $key.": ".$value.";";
        }
        return $string;
    }

    /**
     * @static
     * @param $string
     * @param $len
     * @param bool $breakOnSpaces
     * @param string $extraChars
     * @return string
     */
    public static function getTrimmedString($string, $len, $breakOnSpaces=true, $extraChars="..."){
        if(strlen($string) <= $len){
            // Nothing needed - return it
            return $string;
        }

        $newString = "";
        if($breakOnSpaces){
            $parts = explode(" ", $string);
            foreach($parts as $part){
                $testString = $newString." ".$part;
                $testString = trim($testString);
                if(strlen($testString) > $len){
                    // We have reached out max
                    break;
                }
                $newString = $testString;
            }
        }else{
            $newString = substr($string, 0, $len);
        }

        // Check to see if we need to add extra characters
        if($extraChars){
            $newString = trim($newString).$extraChars;
        }

        return $newString;

    }

    /**
     * @static
     * @param $string
     * @param $stop_words
     * @param int $max_count
     * @return array
     */
    public static function extractCommonWords($string, $stop_words, $max_count = 5) {
        $string = preg_replace('/ss+/i', '', $string);
        $string = trim($string); // trim the string
        $string = preg_replace('/[^a-zA-Z -]/', '', $string); // only take alphabet characters, but keep the spaces and dashes too…
        $string = strtolower($string); // make it lowercase

        preg_match_all('/\b.*?\b/i', $string, $match_words);
        $match_words = $match_words[0];

        foreach ( $match_words as $key => $item ) {
            if ( $item == '' || in_array(strtolower($item), $stop_words) || strlen($item) <= 3 ) {
                unset($match_words[$key]);
            }
        }

        $word_count = str_word_count( implode(" ", $match_words) , 1);
        $frequency = array_count_values($word_count);
        arsort($frequency);
        //DSCF_DTG_Utilities::logMessage("FREQUENCY ARRAY SORTED: ".print_r($frequency,true));

        //Now just provide the keys... although this is now sorted
        $frequency = array_keys($frequency);

        //DSCF_DTG_Utilities::logMessage("FREQUENCY ARRAY KEYS: ".print_r($frequency,true));

        //arsort($word_count_arr);
        $keywords = array_slice($frequency, 0, $max_count);
        return $keywords;
    }

    /**
     * @static
     * @return array
     */
    public static function getStopWordsArray(){
        return $stopwords = array("a", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also","although","always","am","among", "amongst", "amoungst", "amount",  "an", "and", "another", "any","anyhow","anyone","anything","anyway", "anywhere", "are", "around", "as",  "at", "back","be","became", "because","become","becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom","but", "by", "call", "can", "cannot", "cant", "co", "con", "could", "couldnt", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven","else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least", "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own","part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereupon", "these", "they", "thickv", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "very", "via", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself", "yourselves", "the");
    }

    /**
     * @static
     * @return array
     */
    public static function getStopWords(){
        return file(dirname(__FILE__).'/../inc/stop_words.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * @static
     * @return array
     */
    public static function getAllStopWords(){
        $stopWordsArrayHardcoded = self::getStopWordsArray();
        $stopWordsArrayFile = self::getStopWords();
        $stopWords = array_merge($stopWordsArrayHardcoded, $stopWordsArrayFile);
        return $stopWords;
    }

    /**
     * @static
     * @return mixed
     */
    public static function getRandomLoremIpsumTitle(){

        $lines = array(
            0=>"Quisque lectus lorem, sollicitudin sed.",
            1=>"Sed arcu nibh, lacinia vitae tellus vitae risus.",
            2=>"Nulla accumsan quam vitae diam porttitor.",
            3=>"Suspendisse eu euismod tortor.",
            4=>"Curabitur sed diam turpis.",
            5=>"Sed sit amet vestibulum mi.",
            6=>"Donec consectetur felis dui, eu eleifend et.",
        );

        return $lines[array_rand($lines)];

    }

    /**
     * @static
     * @param $count
     * @param bool $includeParagraphs
     * @return string
     */
    public static function getRandomLoremIpsumParagraphs($count, $includeParagraphs=true){

        $paragraphs = array(
            0=>"Quisque lectus lorem, sollicitudin id faucibus sed, gravida sit amet justo. Sed arcu nibh, lacinia vitae tellus vitae, tristique eleifend risus. Nulla accumsan quam vitae diam porttitor, at laoreet nisi ultricies. Suspendisse eu euismod tortor. Curabitur sed diam turpis. Sed sit amet vestibulum mi. Donec consectetur felis dui, eu ultrices ante eleifend et.",
            1=>"Cras et pretium arcu. Donec a scelerisque ligula. Ut hendrerit metus id elit congue pretium. Vivamus pharetra blandit odio. Maecenas vitae mollis velit, et suscipit est. Maecenas sed semper erat. Mauris at tortor vestibulum arcu tincidunt dignissim. Pellentesque ornare metus sed iaculis finibus. In vulputate tristique sagittis. Nullam suscipit nibh ornare sapien blandit, quis luctus ligula euismod.",
            2=>"Suspendisse faucibus faucibus diam. Suspendisse ac felis enim. Proin vel ultrices est. Maecenas ultrices, urna et suscipit malesuada, risus nisl lacinia nisi, dapibus porta leo dolor non magna. Sed volutpat sodales convallis. Proin suscipit dictum tortor, sit amet vestibulum arcu congue sed. Curabitur mollis quis urna eget pretium. Suspendisse condimentum sapien nisi, a consectetur nisi sagittis at. Sed tempus ante iaculis sodales dignissim. Quisque ac odio vitae libero tristique auctor.",
            3=>"Duis auctor condimentum lobortis. Nulla consequat nulla mi, sed iaculis lorem maximus nec. Fusce laoreet et metus laoreet iaculis. Nam enim leo, volutpat ac lorem vel, ultricies venenatis urna. Donec laoreet, metus vitae scelerisque ultrices, metus nulla suscipit tortor, a scelerisque diam elit sit amet tellus. Praesent interdum malesuada ligula, eget convallis felis varius non. Morbi euismod aliquet tellus. Etiam semper mauris sit amet diam consequat, a condimentum mauris sodales. Morbi egestas lorem tincidunt cursus finibus. Sed ut sagittis nisl. Nam luctus tristique nulla, vel cursus turpis luctus quis. Praesent tempor massa turpis, vel convallis risus iaculis vel. Phasellus id augue ut mi pharetra malesuada.",
            4=>"In vitae ligula sit amet felis tempus tempor id at metus. Praesent mattis suscipit tempor. Nunc interdum varius dui in malesuada. Nam velit dui, commodo et metus et, congue feugiat nulla. Cras lobortis volutpat augue eget efficitur. Sed lorem orci, egestas vel orci et, mattis elementum elit. Etiam tortor purus, sodales in leo nec, lobortis dignissim sem. Quisque non porttitor nulla. Phasellus ante quam, dapibus sed odio vel, auctor sollicitudin leo. Sed porttitor, tellus molestie sollicitudin mattis, mi tortor aliquet diam, et imperdiet mi elit a dui. Morbi maximus ex ipsum, in gravida sem semper sit amet. Nullam molestie, sapien non molestie congue, elit velit laoreet ipsum, quis consequat neque tellus vestibulum leo. Etiam id augue velit. Etiam euismod elit sagittis velit feugiat, accumsan ornare lorem condimentum. Nam blandit, purus non tempus vestibulum, massa sem tempus purus, sed imperdiet metus libero a tellus.",
            5=>"Nullam pulvinar sem at purus elementum faucibus. Nunc venenatis felis sed augue fringilla tincidunt. Curabitur dapibus accumsan neque, ac convallis est semper sed. Duis lacinia interdum quam, eget accumsan dui dignissim nec. Etiam imperdiet molestie rhoncus. Nulla eget velit mauris. Maecenas feugiat efficitur ex, in iaculis erat laoreet in. Etiam id cursus purus. Donec fringilla maximus turpis, vel condimentum turpis vehicula vel. Curabitur dignissim eros vitae elit hendrerit, eu consectetur dui placerat. Maecenas bibendum in neque in sagittis. Sed fringilla massa leo, in volutpat urna pulvinar non. Etiam eget maximus tortor. Morbi vel urna lacus.",
            6=>"Integer magna enim, fringilla vel placerat sit amet, dictum eget quam. Ut mattis eleifend ante, eget euismod ante faucibus ut. Donec sit amet odio vitae eros facilisis tempor. Integer mauris nulla, eleifend sit amet aliquam ut, egestas sagittis magna. Sed sollicitudin, arcu eu maximus porttitor, nunc velit posuere mi, sed eleifend tortor ipsum id est. Nullam placerat elit vitae libero euismod, non dictum mauris fringilla. Vivamus varius ultricies leo tempor gravida. Pellentesque et dolor eu velit faucibus bibendum.",
            7=>"Integer nisi quam, viverra vitae urna vitae, molestie euismod est. Pellentesque quis justo dapibus, fermentum nulla hendrerit, venenatis neque. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris ut nisl vitae justo tristique fermentum. Nam nisl turpis, aliquet vitae pretium id, rhoncus ut libero. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce sed tincidunt nisi. Fusce semper dapibus orci nec ornare. Etiam malesuada tristique mi vel tempor. Sed sollicitudin sagittis nulla ac hendrerit.",
            8=>"Integer mi massa, congue sed porta sed, tempus sit amet ante. Nunc odio nunc, vestibulum ac eros vitae, tristique dapibus justo. Proin in tristique leo. Proin sit amet turpis mauris. Etiam maximus metus pellentesque ultricies tempor. Phasellus sagittis justo in feugiat iaculis. Proin mattis ultricies convallis. Donec tincidunt orci vitae metus auctor, in mattis justo posuere.",
            9=>"Nam porttitor dolor quis tellus malesuada euismod. Integer tincidunt ipsum sed velit volutpat semper. Cras maximus magna nisl, quis sagittis velit scelerisque ut. Vestibulum aliquet nunc felis, at bibendum erat pellentesque in. In eu tortor at felis dignissim fermentum sit amet id justo. Suspendisse vel dignissim purus. Maecenas cursus, metus a vestibulum ullamcorper, neque magna lacinia lacus, et mollis felis velit sed mauris.",
        );

        $html='';
        for($i=0; $i<$count; $i++){

            $line = $paragraphs[array_rand($paragraphs)];

            if($includeParagraphs){
                $html .= '<p>'.$line.'</p>';
            }else{
                $html .= $line .'<br />';
            }
        }

        return $html;

    }

    /**
     * @static
     * @param $str
     * @param array $replace
     * @param string $delimiter
     * @return mixed|string
     */
    public static function buildSlug($str, $replace=array("'"), $delimiter='-'){
        setlocale(LC_ALL, 'en_US.UTF8');
        $clean = $str;

        // Force it to lower case
        $clean = strtolower($str);

        if( !empty($replace) ) {
            $clean = str_replace((array)$replace, ' ', $clean);
        }

        //Now remove Stop Words
        $stopGapWords = self::getStopWordsArray();
        foreach($stopGapWords as $word){
            $clean = str_replace(" ".$word." ", " ", $clean);
        }

        $strLen = strlen($clean);
        $clean = str_replace("  "," ",$clean);
        while($strLen != strlen($clean)){
            $clean = str_replace("  "," ",$clean);
            $strLen = strlen($clean);
        }

        $clean = preg_replace("/&#?[a-z0-9]+;/i","",$clean);		// Remove any encoded elements
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $clean);
        $clean = preg_replace("/[^a-z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }

    /**
     * Generates a salt based on optional input
     *
     * @param string $key			Optional, a salt for the salt
     * @param int $length			Optional, max length of for the salt
     * @param bool $caseSensitive	Optional, adds lower case letters into the salt
     *
     * @return string A salt
     */
    public static function generateSalt($key = '', $length = 20, $caseSensitive = true) {
        // Purposely left off quotation marks so it doesn't mess with SQL
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ' . '23456789';     //Remove I and 1 as well as O and 0 as they look too similar
        if ($caseSensitive) {
            $chars .= 'abcdefghjklmnpqrstuvwxyz';
        }

        $salt = $key;
        $max = (strlen($chars) - 1);

        // ZLIVE-220 - use urandom instead of mt_rand
        $num = file_get_contents('/dev/urandom', 0, null, -1, $length);
        for ($i = 0; $i < $length; $i++) {
            $salt .= $chars[(ord($num[$i]) % ($max + 1))];
        }

        return $salt;
    }


    /**
     * @static
     * @return array
     */
    public static function ajaxValidateSlugHandler(){

        // Get fields from the client
        $classname = DSCF_DTG_Utilities::getRequestVarIfExists("class_name");
        $slugText = DSCF_DTG_Utilities::getRequestVarIfExists("slug_text");
        $elemId = DSCF_DTG_Utilities::getRequestVarIfExists("elem_id");
        $objectId = DSCF_DTG_Utilities::getRequestVarIfExists("object_id");

        // Some other variables for this routine
        $slug = "";
        $createNewSlug = true;

        // Validate that we are not just messing around with the existing slug
        if(!empty($objectId)){
            $object = call_user_func(array($classname, 'get'), $objectId);
            if(!empty($object)){
                $slugTest = DSCF_DTG_Utilities::buildSlug($slugText);
                if($slugTest == $object->slug){
                    // The current slug is the same one that is being tested - do not test a new one
                    $slug = $object->slug;
                    $createNewSlug = false;     // We do not need to create a new slug
                }
            }
        }

        // Create a new one from the text being passed
        if($createNewSlug){
            $slug = call_user_func(array($classname, 'createNewSlug'), $slugText);
        }

        // Prepare the response object
        $response = array(
            'elemId'=>$elemId,
            'class_name'=>$classname,
            'slug'=>$slug
        );

        return $response;
    }

    /**
     * @static
     * @return array
     */
    public static function getDaysOfWeek(){
        return array(
            0=>'Sunday',
            1=>'Monday',
            2=>'Tuesday',
            3=>'Wednesday',
            4=>'Thursday',
            5=>'Friday',
            6=>'Saturday',
        );
    }

    /**
     * @static
     * @return array
     */
    public static function getTimeArray(){
        $times = array();

        for($i=0; $i<1440; $i+=15){
            $times[$i] = self::translateMinutesToTimestamp($i);
        }

        return $times;
    }

    /**
     * @static
     * @param $mins
     * @param bool $militaryTime
     * @return string
     */
    public static function translateMinutesToTimestamp($mins, $militaryTime=false){

        $hours = 0;
        if(!empty($mins)){
            $hours = floor($mins/60);
        }

        $minsLeft = $mins - ($hours*60);

        if($militaryTime){
            $result = str_pad($hours, 2, "0", STR_PAD_LEFT).":".str_pad($minsLeft, 2, "0", STR_PAD_LEFT);
        }else{
            $ampm = "AM";
            if($hours >= 12){
                $hours -= 12;
                $ampm = "PM";
            }
            if($hours==0){
                $hours=12;
            }
            $result = str_pad($hours, 2, "0", STR_PAD_LEFT).":".str_pad($minsLeft, 2, "0", STR_PAD_LEFT).$ampm;
        }

        return $result;
    }

    /**
     * @static
     * @return array
     */
    public static function getCategoriesAsArray(){
        $allCategories = get_categories(array('taxonomy'=>'category'));
        $defaultCategories = array();
        foreach($allCategories as $category){
            $defaultCategories[$category->term_id]=$category->name;
        }
        return $defaultCategories;
    }

    /**
     * @static
     * @return array
     */
    public static function getTagsAsArray(){
        $allTags = get_tags(array('orderby'=>'name'));
        $defaultTags = array();
        foreach($allTags as $tag){
            $defaultTags[$tag->term_id]=$tag->name;
        }
        return $defaultTags;
    }

    /**
     * Returns the current Alignments Values as an array for Editors and such
     *
     * @static
     * @return array
     */
    public static function getAllBasicAlignmentMarks(){
        $collection = array();
        $collection[self::BASIC_ALIGN_LEFT]='Left';
        $collection[self::BASIC_ALIGN_CENTER]='Center';
        $collection[self::BASIC_ALIGN_RIGHT]='Right';
        return $collection;
    }

    /**
     * Returns a human readable version of the Alignment Flag
     *
     * @static
     * @param $id
     * @return null
     */
    public static function getFormattedBasicAlignmentString($id){
        $collection = self::getAllBasicAlignmentMarks();
        return DSCF_DTG_Utilities::getFromArray($id, $collection, '');
    }

    /**
     * @static
     * @param $post_id
     * @param int $length
     * @return string
     */
    public static function getExcerptByPostId($post_id, $length=35){
        $the_post = get_post($post_id); //Gets post ID
        return self::getExcerptByPostObject($the_post, $length);
    }

    /**
     * @static
     * @param $the_post
     * @param int $length
     * @return string
     */
    public static function getExcerptByPostObject($the_post, $length=35){

        // If the excerpt exists on the post object - then return that
        $the_excerpt = $the_post->post_excerpt;
        if(empty($the_excerpt)){
            // Excerpt didn't exist - try to get it from the contents
            $the_excerpt = $the_post->post_content;
        }

        $excerpt_length = 35; //Sets excerpt length by word count
        $the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
        $words = explode(' ', $the_excerpt, $excerpt_length + 1);

        if(count($words) > $excerpt_length){
            array_pop($words);
            array_push($words, '…');
            $the_excerpt = implode(' ', $words);
        }

        $the_excerpt = '<p>' . $the_excerpt . '</p>';

        return $the_excerpt;
    }

    /**
     * @static
     * @param $oldType
     * @param $newType
     */
    public static function migrateTableNameIfNeeded($oldTableName, $newTableName){

        global $wpdb;
        $migrationKey = "dtg_tbn_migr_f_".$oldTableName."_t_".$newTableName;

        $value = DSCF_DTG_StandardSetting::getSetting($migrationKey);
        if(!empty($value)){
            // Migration has been done - move on
            return;   // Commenting out while testing
        }

        // It might not be "wp_posts" - I need to prefix...

        $sql = "RENAME TABLE `".$wpdb->prefix.$oldTableName."` TO `".$wpdb->prefix.$newTableName."`;";
        $wpdb->query($sql);

        DSCF_DTG_StandardSetting::saveSetting($migrationKey, true);

    }

    /**
     * @static
     * @param $oldType
     * @param $newType
     */
    public static function migratePostTypesIfNeeded($oldType, $newType){

        global $wpdb;
        $migrationKey = "dtg_cpt_migr_f_".$oldType."_t_".$newType;

        $value = DSCF_DTG_StandardSetting::getSetting($migrationKey);
        if(!empty($value)){
            // Migration has been done - move on
            //return false;   // Commenting out while testing
        }

        // It might not be "wp_posts" - I need to prefix...

        $sql = "UPDATE `".$wpdb->prefix."posts` SET `post_type` = '".$newType."' WHERE `post_type` = '".$oldType."';";
        $wpdb->query($sql);

        DSCF_DTG_StandardSetting::saveSetting($migrationKey, true);

    }

    /**
     * @static
     * @param $oldType
     * @param $newType
     */
    public static function migrateTaxonomyTypesIfNeeded($oldType, $newType){
        global $wpdb;
        $migrationKey = "dtg_tax_migr_f_".$newType."_t_".$oldType;

        $value = DSCF_DTG_StandardSetting::getSetting($migrationKey);
        if(!empty($value)){
            // Migration has been done - move on
            //return false;   // Commenting out while testing
        }

        $sql = "UPDATE `".$wpdb->prefix."term_taxonomy` SET `taxonomy` = '".$newType."' WHERE `taxonomy` = '".$oldType."';";
        DSCF_DTG_Utilities::logMessage($sql);
        $wpdb->query($sql);

        DSCF_DTG_StandardSetting::saveSetting($migrationKey, true);
    }

    /**
     * @static
     * @param $oldType
     * @param $newType
     */
    public static function migrateMetaBoxDataIfNeeded($oldType, $newType){
        global $wpdb;
        $migrationKey = "dtg_cptm_migr_f_".$newType."_t_".$oldType;

        $value = DSCF_DTG_StandardSetting::getSetting($migrationKey);
        if(!empty($value)){
            // Migration has been done - move on
            //return false;   // Commenting out while testing
        }

        $sql = "UPDATE `".$wpdb->prefix."postmeta` SET `meta_key` = '".$newType."' WHERE `meta_key` = '".$oldType."';";
        DSCF_DTG_Utilities::logMessage($sql);
        $wpdb->query($sql);

        DSCF_DTG_StandardSetting::saveSetting($migrationKey, true);
    }

    /**
     * @static
     * @param $inputSeconds
     * @param bool $includeSeconds
     * @param bool $includeMinutes
     * @param bool $includeHours
     * @param bool $includeDays
     * @return string
     */
    public static function secondsToTime($inputSeconds, $includeSeconds=true, $includeMinutes=true, $includeHours=true, $includeDays=true) {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;

        // Extract days
        $days = floor($inputSeconds / $secondsInADay);

        // Extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        // Extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // Extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        // Format and return
        $timeParts = [];
        $sections = [
            'day' => (int)$days,
            'hour' => (int)$hours,
            'minute' => (int)$minutes,
            'second' => (int)$seconds,
        ];

        if(empty($includeSeconds)){ unset($sections['second']); }
        if(empty($includeMinutes)){ unset($sections['minute']); }
        if(empty($includeHours)){ unset($sections['hour']); }
        if(empty($includeDays)){ unset($sections['day']); }

        foreach ($sections as $name => $value){
            if ($value > 0){
                $timeParts[] = $value. ' '.$name.($value == 1 ? '' : 's');
            }
        }

        return implode(', ', $timeParts);
    }

    public static function getLettersArray(){
        $letters = array(
            'a'=>array(),
            'b'=>array(),
            'c'=>array(),
            'd'=>array(),
            'e'=>array(),
            'f'=>array(),
            'g'=>array(),
            'h'=>array(),
            'i'=>array(),
            'j'=>array(),
            'k'=>array(),
            'l'=>array(),
            'm'=>array(),
            'n'=>array(),
            'o'=>array(),
            'p'=>array(),
            'q'=>array(),
            'r'=>array(),
            's'=>array(),
            't'=>array(),
            'u'=>array(),
            'v'=>array(),
            'w'=>array(),
            'x'=>array(),
            'y'=>array(),
            'z'=>array(),
        );
        return $letters;
    }

    /*
     * Echos a Login Form to the user
     */
    public static function echoLoginForm($redirect=""){

        $args = array();

        if(!empty($redirect)){
            $args['redirect']=$redirect;
        }

        wp_login_form($args);

    }

    public static function isUserAdmin(){
        $current_user = wp_get_current_user();
        if ( in_array( 'administrator', (array) $current_user->roles ) ) {
            return true;
        }
        return false;
    }

}
