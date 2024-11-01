<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 3/18/15
 * Time: 4:44 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_StandardEditor{

    // Object Types
    const OT_BOOLEAN=1;
    const OT_STRING=2;
    const OT_NUMERIC=3;
    const OT_ARRAY=4;
    const OT_DATE=5;
    const OT_SKIP=6;
    const OT_ARRAY_ARRAY=7;
    const OT_OBJECT=8;

    // Editor Types
    const ET_TEXT=1;
    const ET_TEXT_READONLY=2;
    const ET_TEXT_ADDITIONAL=3;
    const ET_MENU=4;
    const ET_MENU_STATUS=5;
    const ET_MENU_MULTIPLE=6;	//Doesn't work :: use ET_CHECKBOX_GROUP instead
    const ET_MENU_BOOLEAN=7;
    const ET_DATE=8;
    const ET_CHECKBOX=9;
    const ET_CHECKBOX_GROUP=10;
    const ET_RADIO=11;
    const ET_TEXTAREA=12;
    const ET_SUBMIT=13;
    const ET_HIDDEN=14;
    const ET_PASSWORD=15;
    const ET_LINK=16;
    const ET_HTML=17;
    const ET_MENU_KEY=18;
    const ET_BUTTON=19;
    const ET_TEXT_ARRAY_ARRAY=20;
    const ET_ZONE_ITEM_PICKER = 21;
    const ET_ITEM_ZONE_PICKER = 22;
    const ET_DPM_ANSWER_CHOICE_PICKER=23;
    const ET_TEXTAREA_HTML=24;
    const ET_DATE_TIME = 25;        // In order to enable Date Time it has to be included
        //wp_enqueue_script( WPDEVHUB_CONST_DTG_SLUG.'jquery-time-picker' ,  WPDEVHUB_CONST_DTG_URL.'/js/jquery-ui-timepicker-addon.min.js',  array('jquery' ));
    const ET_SKIP = 26;
    const ET_MEDIA_LIBRARY=27;
    const ET_MEDIA_LIBRARY_MULTIPLE=28;
    const ET_DQQ_ANSWER_CHOICE_PICKER=29;
    //const ET_BUTTON_SAVE_AND_FORWARD_ADMIN=30;        // Can't get this to work -- leave it commented out for now
    const ET_DTT_ITEMS = 31;    // DTT Item List Utility
    const ET_SLUG_EDITOR = 32;
    const ET_TEXT_ARRAY_READONLY = 33;
    const ET_TEXT_MENU_ADDITIONAL = 34;
    const ET_MENU_USERID_USERNAME = 35;

    // Default menus options
    public static $defaultsMenuTrueFalse = array(1=>'True',0=>'False');
    public static $defaultsMenuActive = array('Active','Inactive');
    public static $defaultsOperators = array('>','>=','=','=<','<');

    public static function buildPageTemplate($class, $editorParams=array(), $forceEditor=false){

        // Make sure the class exists
        if(!class_exists($class)){
            return $class." - class does not exist.";
        }

        $html = '';

        //DSCF_DTG_Utilities::logMessage("Request Vars: ".print_r($_REQUEST, true));

        // See if the editor was passed
        $editor = DSCF_DTG_Utilities::getRequestVarIfExists('formEditor');

        // See if a new was passed ofr an id
        $id = DSCF_DTG_Utilities::getRequestVarIfExists('id');

        if(empty($editor) && empty($id)){
            // Both Editor Submit and Editor Display are empty - we do not belong in here
            return '';
        }

        // Setup the object
        $object = false;
        if($id=='new'){
            $object = false; // Do not create a new object yet
        }elseif(empty($id)){
            $object = new $class();     // Will catch either 0 or ''
        }else{
            $object = $class::get($id);
        }

        // Update or Insert the Choices as appropriate
        if(empty($editorParams)){
            $options = $class::editorBuildOptions($object);
        }else{
            $options = $class::editorBuildOptions($object, $editorParams);
        }

        if($object && isset($_REQUEST['formEditor'])){
            // Save the changes but do not build the editor
            $object = DSCF_DTG_StandardEditor::saveEditorChanges($object,$options,$_REQUEST);

            if($forceEditor){
                // Build the editor again (such as in Free Zones)
                $object = $class::get($id);
                if(empty($editorParams)){
                    $options = $class::editorBuildOptions($object);
                }else{
                    $options = $class::editorBuildOptions($object, $editorParams);
                }
                $html .= DSCF_DTG_StandardEditor::buildEditor($options, '#');
            }else{
                unset($_REQUEST['id']);
            }
        }else{
            // Build the editor because the form was not shown
            $html .= DSCF_DTG_StandardEditor::buildEditor($options, '#');
        }

        return $html;
    }

    public static function buildCreateNewButtonOptions($page, $text="Create New"){
        $buttonOptions = array(
            'params'=>array('page'=>$page,'id'=>0),
            'text'=>$text
        );
        return $buttonOptions;
    }

    public static function buildEditor($options, $target, $id="", $forceDemoOnly=false){
        /*
          $options = array(
              0=array(
                  'title'='Name',
                  'objectType'=CommonTools::OT_STRING,
                  'formType'=CommonTools::FT_TEXT,
                  'value'='Default Name',
                  'help'='Please fill out the name of the object'
                  ),
              1=array(
                  'title'='Status',
                  'objectType'=CommonTools::OT_BOOLEAN,
                  'formType'=CommonTools::FT_MENU,
                  'formOptions'=CommonTools::$defaultsMenuActive
                  'value'='active',
                  'help'='Select whether this object is active or inactive'
                  ),
              );
          */

        $showBottomSaveButton = true;
        if(array_key_exists('showBottomSaveButton', $options)){
            $showBottomSaveButton = $options['showBottomSaveButton'];
            unset($options['showBottomSaveButton']);
        }

        $skipFormElement = false;
        if(array_key_exists('skipFormElement', $options)){
            $skipFormElement = $options['skipFormElement'];
            unset($options['skipFormElement']);
        }

        $html ='';
        $html .= '<div class="contentSectionWrapper">';

        // Option to skip the form element (for forms inside a form)
        if(empty($skipFormElement)){
            $html .= '<form action="'.$target.'" method="post" id="'.$id.'" enctype="multipart/form-data">';
        }

        $html .= '<input type="hidden" name="formEditor" value="1" />';

        // See if we are using Tabs or not
        $tabRows = array();
        foreach($options as $optionRow){
            // Generally it should be the first row... but still
            if(is_array($optionRow) && array_key_exists('rowType', $optionRow) && $optionRow['rowType']=='tabHeader'){
                $tabRows[] = $optionRow;
            }
        }

        if(empty($tabRows)){
            $html .= '<table class="dtg-content-editor">';
            foreach($options as $optionRow){
                if($forceDemoOnly){
                    $html .= self::buildEditorRowForDemo($optionRow);
                }else{
                    $html .= self::buildEditorRow($optionRow);
                }
            }
            $html .= '</table>';

        }else{

            $html .= '<ul class="nav nav-tabs" role="tablist">';
            foreach($options as $optionRow){
                if(array_key_exists('rowType', $optionRow) && $optionRow['rowType']=='tabHeader'){
                    $html .= '<li role="presentation"><a href="#editorTabs-'.$optionRow['tabId'].'" aria-controls="editorTabs-'.$optionRow['tabId'].'" role="tab" data-toggle="tab">'.$optionRow['title'].'</a></li>';
                }
            }
            $html .= '</ul>';

            $html .= '<div class="tab-content">';
            $firstDiv = true;
            foreach($options as $optionRow){
                if(array_key_exists('rowType', $optionRow) && $optionRow['rowType']=='tabHeader'){
                    if(!$firstDiv){
                        $html .= '</table>';
                        $html .= '</div>';
                    }
                    $active="";
                    if($firstDiv){
                        $active = " active";
                    }
                    $html .= '<div role="tabpanel" class="tab-pane'.$active.'" id="editorTabs-'.$optionRow['tabId'].'">';
                    $html .= '<table class="styledTable1 dtg-content-editor">';
                    $firstDiv = false;
                }else{
                    $html .= self::buildEditorRow($optionRow);
                }
            }
            $html .= '</table>';
            $html .= '</div>';  // Close the last table block
            $html .= '</div>';  // Close the tab-content block

        }


        if($showBottomSaveButton){
            $html .= '<div style="text-align:center; padding:5px;"><input type="submit" class="button" name="submit2" value="Save Changes" /></div>';
        }

        // Option to skip the form element (for forms inside a form)
        if(empty($skipFormElement)){
            $html .= '</form>';
        }

        $html .= '</div>';
        return $html;

    }

    public static function buildEditorRow($options=array()){
        $html = '';
        $defaults = array(
            'formType'=>'',
            'objectName'=>'',
            'value'=>'',
            'formOptions'=>array(),
            'maxlength'=>'',
            'rows'=>'',
            'cols'=>'',
            'size'=>'',
            'help'=>''
        );
        $options = array_merge($defaults,$options);
        if($options['formType'] == self::ET_HIDDEN){
            $html .= '<input type="hidden" name="'.$options['objectName'].'" value="'.$options['value'].'" />';
        }elseif(array_key_exists('rowType', $options) && $options['rowType']=='SectionHeader'){
            $html .= '<tr>';
            $html .= '<th colspan="2">'.$options['title'].'</th>';
            $html .= '</tr>';
        }else{
            $html .= '<tr>';
            $html .= '<td>';
            $html .= '<div class="dtg-content-editor-title">'.$options['title'].'</div>';
            $html .= '<div class="dtg-content-editor-help">'.$options['help'].'</div>';
            $html .= '</td>';
            $html .= '<td>';
            $html .= self::buildEditorCell($options);
            $html .= '</td>';
            $html .= '</tr>';
        }
        return $html;
    }

    public static function buildEditorRowForDemo($options){
        $html = '';
        if(array_key_exists('rowType', $options) && $options['rowType']=='SectionHeader'){
            $html .= '<tr>';
            $html .= '<th colspan="2">'.$options['title'].'</th>';
            $html .= '</tr>';
        }else{
            $html .= '<tr>';
            $html .= '<td>';
            $html .= '<div class="dtg-content-editor-title">'.$options['title'].'</div>';
            $html .= '</td>';
            $html .= '<td>';
            $html .= '<div class="dtg-content-editor-help">'.$options['help'].'</div>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        return $html;

    }

    public static function buildEditorCell($options){
        $html = '';
        switch ($options['formType']){
            case self::ET_TEXT:
                $html .= '<input type="text" id="'.$options['objectName'].'" name="'.$options['objectName'].'" value="'.$options['value'].'" size="'.$options['size'].'" /> <label for="'.$options['objectName'].'"></label>';
                //DSCF_DTG_Utilities::logMessage("Common Editor TEXT: Value:".print_r($options['value'],true));
                break;
            case self::ET_TEXT_READONLY:
                $html .= $options['value'];
                $html .= '<input type="hidden" id="'.$options['objectName'].'" name="'.$options['objectName'].'" value="'.$options['value'].'" /> <label for="'.$options['objectName'].'"></label>';
                break;
            case self::ET_TEXT_ARRAY_READONLY:
                foreach($options['value'] as $key=>$value){
                    $html .= '<div>'.$value;
                    $html .= '<input type="hidden" id="'.$options['objectName'].$key.'" name="'.$options['objectName'].$key.'" value="'.$value.'" /> <label for="'.$options['objectName'].$key.'"></label>';
                    $html .= '</div>';
                }
                break;
            case self::ET_SLUG_EDITOR:
                $html .= '
					<input type="hidden" id="'.$options['objectName'].'" name="'.$options['objectName'].'" value="'.$options['value'].'" />
					<input id="slug_'.$options['slugClassName'].'_preview" name="slug_'.$options['slugClassName'].'_preview" type="text" size="50" value="'.$options['value'].'" /><div id="slug_'.$options['slugClassName'].'_previewText" style="display:inline-block;"></div>
					<br /><a href="javascript:DSCF_DTG_Admin.createSlug(\''.$options['objectName'].'\',\''.$options['sourceName'].'\',\''.$options['slugClassName'].'\');">create from title</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:DSCF_DTG_Admin.validateSlug(\''.$options['objectName'].'\',\''.$options['slugClassName'].'\');">validate slug</a>';
                break;
            case self::ET_TEXT_ADDITIONAL:
                $size=50;
                if(array_key_exists('size',$options) && !empty($options['size'])){
                    $size = $options['size'];
                }
                if(DSCF_DTG_Utilities::validateArray($options['value'])){
                    foreach($options['value'] as $key=>$value){
                        $html .= '<div id="'.$options['objectName'].$key.'_wrapper"><input type="text" id="'.$options['objectName'].$key.'" name="'.$options['objectName'].$key.'" value="'.$value.'" size="'.$size.'"  /><button type=button onclick="DSCF_DTG_Admin.deleteOptionRow(\''.$options['objectName'].$key.'\');">delete</button></div>';
                    }
                }
                $html .= '<div id="addMoreRows'.$options['objectName'].'"><a href="javascript:DSCF_DTG_Admin.addBlankRow(\'moreRows'.$options['objectName'].'\',\'addMoreRows'.$options['objectName'].'\');">add row</a></div>';
                break;
            case self::ET_TEXT_MENU_ADDITIONAL:
                $size=50;
                if(array_key_exists('size',$options) && !empty($options['size'])){
                    $size = $options['size'];
                }
                $selectMenu=array();
                if(array_key_exists('selectMenu',$options) && !empty($options['selectMenu'])){
                    $selectMenu = $options['selectMenu'];
                }
                if(DSCF_DTG_Utilities::validateArray($options['value'])){
                    foreach($options['value'] as $key=>$value){
                        $html .= '<div id="'.$options['objectName'].$key.'_wrapper"><input type="text" id="'.$options['objectName'].$key.'" name="'.$options['objectName'].$key.'" value="'.$value.'" size="'.$size.'"  />';
                        $html .= '<select id="'.$options['objectName'].$key.'_select">';
                            foreach($selectMenu as $k=>$v){
                                $html .= '<option value='.$k.'>'.$v.'</option>';
                            }
                        $html .= '</select>';
                        $html .= '<button type=button onclick="DSCF_DTG_Admin.deleteOptionRow(\''.$options['objectName'].$key.'\');">delete</button></div>';
                    }
                }
                $html .= '<div id="addMoreRows'.$options['objectName'].'"><a href="javascript:DSCF_DTG_Admin.addBlankRow(\'moreRows'.$options['objectName'].'\',\'addMoreRows'.$options['objectName'].'\');">add row</a></div>';
                break;
            case self::ET_TEXT_ARRAY_ARRAY:
                $first = true;
                $html .= '<table>';
                $extraRowNeeded = true;
                $item = $options['defaultArray'];
                foreach($options['value'] as $key=>$value){
                    $html .= '<tr>';
                    $item = $value;

                    foreach($value as $k=>$v){
                        $html .= '<td>'.$k.': <input type="text" name="'.$options['objectName'].$key.$k.'" value="'.$v.'" size="'.$options['size'].'" /></td>';
                        if($extraRowNeeded){

                        }
                    }
                    $html .= '</tr>';

                }
                //4 Extra Blank Rows by Default
                for($i=0;$i<4;$i++){
                    $html .= '<tr>';
                    foreach($item as $k=>$v){
                        $html .= '<td><input type="text" name="moreRows'.$options['objectName'].'_blank_'.$k.'_'.$i.'" size="'.$options['size'].'" /></td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</table>';
                break;
            case self::ET_MENU:
                $html .= '<select id="'.$options['objectName'].'" name="'.$options['objectName'].'"> <label for="'.$options['objectName'].'"></label>';
                $html .= '<option value="">--</option>';
                foreach($options['formOptions'] as $key=>$option){
                    $selected = '';
                    if($option==$options['value']){ $selected=' selected="selected"'; }
                    $html .= '<option value="'.$option.'"'.$selected.'>'.$option.'</option>';
                }
                $html .= '</select>';
                break;
            case self::ET_MENU_KEY:
                $html .= '<select id="'.$options['objectName'].'" name="'.$options['objectName'].'"> <label for="'.$options['objectName'].'"></label>';
                $html .= '<option value="">--</option>';
                foreach($options['formOptions'] as $key=>$option){
                    $selected = '';
                    if($key==$options['value']){ $selected=' selected="selected"'; }
                    $html .= '<option value="'.$key.'"'.$selected.'>'.$option.'</option>';
                }
                $html .= '</select>';
                break;
            case self::ET_MENU_STATUS:
                $html .= '<select id="'.$options['objectName'].'" name="'.$options['objectName'].'"> <label for="'.$options['objectName'].'"></label>';
                $html .= '<option value="">--</option>';
                foreach(DSCF_DTG_StandardObjectRecord::getAllStatusMarks() as $key=>$value){
                    $selected = '';
                    if($key==$options['value']){ $selected=' selected="selected"'; }
                    $html .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
                }
                $html .= '</select>';
                break;
            case self::ET_MENU_MULTIPLE:
                $html .= '<select id="'.$options['objectName'].'" name="'.$options['objectName'].'" multiple="multiple" size="10"> <label for="'.$options['objectName'].'"></label>';
                $html .= '<option value="">--</option>';
                foreach($options['formOptions'] as $key=>$value){
                    $selected = '';
                    if($value==$options['value']){ $selected=' selected="selected"'; }
                    $html .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
                }
                $html .= '</select>';
                break;
            case self::ET_MENU_BOOLEAN:
                $html .= '<select id="'.$options['objectName'].'" name="'.$options['objectName'].'"> <label for="'.$options['objectName'].'"></label>';
                $html .= '<option value="">--</option>';
                foreach(self::$defaultsMenuTrueFalse as $key=>$value){
                    $selected = '';
                    if($key==$options['value']){ $selected=' selected="selected"'; }
                    $html .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
                }
                $html .= '</select>';
                break;
            case self::ET_MENU_USERID_USERNAME:
                $userArray = DSCF_DTG_Utilities::getUserLoginsAsMenuArray();
                $html .= '<select id="'.$options['objectName'].'" name="'.$options['objectName'].'"> <label for="'.$options['objectName'].'"></label>';
                $html .= '<option value="">--</option>';
                foreach($userArray as $key=>$option){
                    $selected = '';
                    if($key==$options['value']){ $selected=' selected="selected"'; }
                    $html .= '<option value="'.$key.'"'.$selected.'>'.$option.'</option>';
                }
                $html .= '</select>';
                break;
            case self::ET_DATE:
                $html .= '<input type="text" id="'.$options['objectName'].'" class="DSCF_DTG_jquery-date-picker" name="'.$options['objectName'].'" value="'.DSCF_DTG_StandardObjectRecord::formatJQueryDate($options['value']).'" />';
                $html .= ' '.self::renderAjaxInsertTodaysDate($options['objectName']).' <label for="'.$options['objectName'].'"></label>';
                break;
            case self::ET_DATE_TIME:
                $html .= '<input type="text" id="'.$options['objectName'].'" class="DSCF_DTG_jquery-datetime-picker" name="'.$options['objectName'].'" value="'.DSCF_DTG_StandardObjectRecord::formatJQueryDateTime($options['value']).'" />';
                $html .= ' '.self::renderAjaxInsertTodaysDate($options['objectName']).' <label for="'.$options['objectName'].'"></label>';
                break;
            case self::ET_CHECKBOX:
                $first = true;
                $checked = '';
                if($options['value']==true){ $checked=' checked="checked"'; }
                $html .= '<input type="checkbox" id="'.$options['objectName'].'" name="'.$options['objectName'].'"'.$checked.'> <label for="'.$options['objectName'].'"></label>';
                break;
            case self::ET_ZONE_ITEM_PICKER:
            case self::ET_ITEM_ZONE_PICKER:
            case self::ET_CHECKBOX_GROUP:
                $first = true;
                $html .= '<div class="contentScrollCheckboxes">';
                foreach($options['formOptions'] as $key=>$value){
                    $checked = '';
                    if(is_array($options['value']) && in_array($key, $options['value'])){ $checked=' checked="checked"'; }
                    if(!$first){ $html.='<br />'; }
                    $html .= '<input type="checkbox" name="'.$options['objectName'].$key.'" value="'.$key.'"'.$checked.'>'.$value;
                    $first = false;
                }
                $html .= '</div>';

                if(($options['formType'] == self::ET_ITEM_ZONE_PICKER) && (empty($options['formOptions']))){
                    $html .= '<div>Create zones using the Zone Manager.</div>';
                }

                break;
            case self::ET_DPM_ANSWER_CHOICE_PICKER:
                // Specific to DPM
                foreach($options['formOptions'] as $key=>$value){
                    $checked = '';
                    if($value->status == DSCF_DTG_StandardObjectRecord::STATUS_ACTIVE){ $checked=' checked="checked"'; }
                    $html .= '<div id="'.$options['objectName'].$key.'_div"><input type="checkbox" name="'.$options['objectName'].$key.'_chk"'.$checked.'> <input type="text" name="'.$options['objectName'].$key.'_txt" value="'.$value->text.'" /> <input type="button" name="'.$options['objectName'].$key.'_dlt" value="Delete" onClick="javascript:dimbalPoll.confirmChoiceDelete(\''.$options['objectName'].$key.'\')" /></div>';
                }
                $html .= '<div id="addMoreRows'.$options['objectName'].'"><a href="javascript:dimbalPoll.addBlankRow(\''.$options['objectName'].'\', \'addMoreRows'.$options['objectName'].'\');">add choice</a></div>';
                break;
            case self::ET_DQQ_ANSWER_CHOICE_PICKER:
                // Specific to DQQ
                $size=50;
                $key=0;
                if(array_key_exists('size',$options) && !empty($options['size'])){
                    $size = $options['size'];
                }
                foreach($options['value'] as $key=>$value){
                    $checked = '';
                    if($key == $options['selectedAnswer']){ $checked=' checked="checked"'; }
                    $html .= '<div id="'.$options['objectName'].$key.'_wrapper"><input type="radio" id="'.$options['objectName'].$key.'_rdo" name="'.$options['objectName'].'_rdo"'.$checked.' value="'.$key.'"><input type="text" name="'.$options['objectName'].$key.'_txt" value="'.$value.'" size="'.$size.'" /> <input type="button" name="'.$options['objectName'].$key.'_dlt" value="Delete" onClick="javascript:DSCF_DTG_Quiz.deleteOptionRow(\''.$options['objectName'].$key.'\')" /></div>';
                }
                $html .= '<div id="addMoreQuizRows'.$options['objectName'].'"><a href="javascript:DSCF_DTG_Quiz.setCounter('.$key.');DSCF_DTG_Quiz.addBlankRow(\''.$options['objectName'].'\', \'addMoreQuizRows'.$options['objectName'].'\');">add answer choice</a></div>';
                break;
            case self::ET_DTT_ITEMS:
                // Specific to DTT
                wp_enqueue_media();
                $key=0;
                if(array_key_exists('size',$options) && !empty($options['size'])){
                    $size = $options['size'];
                }
                $html .= '<ul class="DSCF_DTG_jquery-ui-sortable">';
                foreach($options['value'] as $key=>$value){
                    // The "$value" should be an Object representing an item

                    // Vlaidate instance type
                    if(is_a($value, 'DSCF_DTG_TopTen_Item')){

                        $rowId = $options['objectName'].'_dtt_row_'.$key;
                        $rowKey = $options['objectName'].'_dtt_item_'.$key;

                        $html .= '<li id="'.$rowKey.'_wrapper" class="dtg-pbox5">';

                        // Hidden Element to identify this row
                        $html .= '<input type="hidden" name="'.$rowId.'" value="'.$key.'" />';

                        // Thumbnail
                        $saved_attachment_url = "";
                        $saved_media_id = 0;
                        if(!empty($value->mediaId)){
                            $saved_attachment_url = wp_get_attachment_thumb_url($value->mediaId);
                            $saved_media_id = $value->mediaId;
                        }
                        $html .= '<div>';
                        $html .= '<button style="float:right" onClick="javascript:DSCF_DTG_Admin.deleteGenericWrapperRow(\''.$rowKey.'\')">Delete Item</button>';
                        $html .= 'Title: <input type="text" name="'.$rowKey.'_title" value="'.$value->title.'" size="50" />';
                        $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Primary Image: ';
                        $html .= '<img id="'.$rowKey.'_mediaId_preview" src="'.$saved_attachment_url.'" style="width:50px; height:50px; padding:10px; vertical-align:middle;" />';
                        $html .= '<button class="button" onclick="DSCF_DTG_Admin.openMediaLibrary(\''.$rowKey.'_mediaId\','.$saved_media_id.')">Open Media Library</button>';
                        $html .= '<input type="hidden" name="'.$rowKey.'_mediaId" id="'.$rowKey.'_mediaId" value="'.$saved_media_id.'">';
                        $html .= '</div>';

                        /*
                        ob_start();
                        wp_editor($value->contents, $rowKey.'_contents', array('textarea_rows'=>5));
                        $html .= '<div>'.ob_get_clean().'</div>';
                        */

                        $html .= '<div><textarea name="'.$rowKey.'_contents" style="width:100%; height:150px;">'.$value->contents.'</textarea></div>';

                        $html .= '</li>';

                        $key++;
                    }

                }

                $html .= '</ul>';
                $html .= '<input type="hidden" id="'.$options['objectName'].'_row_counter" value="'.$key.'" />';
                $html .= '<div id="addMoreDttItemRows'.$options['objectName'].'"><a href="javascript:DSCF_DTG_Admin.addDttListRow(\''.$options['objectName'].'\', \'addMoreDttItemRows'.$options['objectName'].'\');">add list item</a></div>';
                break;
            case self::ET_RADIO:
                $first = true;
                foreach($options['formOptions'] as $option){
                    if(!$first){ $html.='<br />'; }
                    $checked = '';
                    if($option==$options['value']){ $checked=' checked="checked"'; }
                    $html .= '<input type="radio" name="'.$options['objectName'].'" value="'.$option.'"'.$checked.'>'.$option;
                    $first = false;
                }
                break;
            case self::ET_TEXTAREA:
                $maxlength = '';
                if(array_key_exists('maxlength', $options)){
                    $maxlength = ' maxlength="'.$options['maxlength'].'"';
                }
                $cols = '';
                if(array_key_exists('cols', $options) && !empty($options['cols'])){
                    $cols = ' cols="'.$options['cols'].'"';
                }
                $rows = '';
                if(array_key_exists('rows', $options) && !empty($options['rows'])){
                    $rows = ' rows="'.$options['rows'].'"';
                }
                $html .= '<textarea id="'.$options['objectName'].'" name="'.$options['objectName'].'" class="dtg-textarea" '.$rows.''.$cols.''.$maxlength.'>'.$options['value'].'</textarea> <label for="'.$options['objectName'].'"></label>';
                break;
            case self::ET_TEXTAREA_HTML:
                //$html .= '<div style="display:table; width:500px; text-align:left;"><div style="display:table-cell; width:500px;"><textarea name="'.$options['objectName'].'" class="tinyMCE">'.$options['value'].'</textarea></div></div>';
                ob_start();
                wp_editor($options['value'], $options['objectName']);
                $html .= ob_get_clean();
                break;
            case self::ET_SUBMIT:
                $html .= '<input type="submit" id="'.$options['objectName'].'" name="'.$options['objectName'].'" value="'.$options['title'].'" /> <label for="'.$options['objectName'].'"></label>';
                break;
            case self::ET_PASSWORD:
                $html .= '<input type="password" id="'.$options['objectName'].'" name="'.$options['objectName'].'" /> <label for="'.$options['objectName'].'"></label>';
                break;
            case self::ET_LINK:
                $html .= '<a href="'.$options['value'].'">'.$options['title'].'</a>';
                break;
            case self::ET_HTML:
                $html .= $options['value'];
                break;
            case self::ET_MEDIA_LIBRARY:
                wp_enqueue_media();
                $saved_attachment_url = "";
                $saved_value = 0;
                if(!empty($options['value'])){
                    $saved_attachment_url = wp_get_attachment_thumb_url($options['value']);
                    $saved_value = $options['value'];
                }
                if(array_key_exists('skip_preview', $options) && !empty($options['skip_preview'])){
                    // Do not include the image preview
                    $html .= '<img id="'.$options['objectName'].'_preview" style="display:none;" />'; // including a hidden image so the JS doesn't fail on us
                }else{
                    $html .= '
                    <div class="'.$options['objectName'].'_preview_wrapper">
                        <img id="'.$options['objectName'].'_preview" src="'.$saved_attachment_url.'" width="100" height="100" style="max-height: 100px; width: 100px;" />
                    </div>
                    ';
                }
                if(array_key_exists('skip_filename', $options) && !empty($options['skip_filename'])){
                    // Do not include the filename data
                    $html .= '<div id="'.$options['objectName'].'_filename" style="display:none;" />'; // including a hidden image so the JS doesn't fail on us
                }else{
                    $html .= '<div>Media ID: <span id="'.$options['objectName'].'_filename">'.$saved_value.'</span></div>';
                }
	            $html .= '
	            <input id="'.$options['objectName'].'_button" type="button" class="dtg-button button" value="Open Media Library" onclick="DSCF_DTG_Admin.openMediaLibrary(\''.$options['objectName'].'\','.$saved_value.')" />
	            <input type="hidden" name="'.$options['objectName'].'" id="'.$options['objectName'].'" value="'.$saved_value.'">
                ';
                break;
            case self::ET_MEDIA_LIBRARY_MULTIPLE:
                wp_enqueue_media();
                $saved_value_string = array();

                $html .= '<ul id="'.$options['objectName'].'_preview" class="DSCF_DTG_jquery-ui-sortable">';
                if(!empty($options['value']) && is_array($options['value'])){
                    // This is an array of Media ID's
                    $saved_value_string = implode(",",$options['value']);
                    foreach($options['value'] as $mediaId){
                        $attachment_url = wp_get_attachment_thumb_url($mediaId);
                        $wrapperId = $options['objectName'].'_wrapper_'.$mediaId;
                        $html .= '<li id="'.$wrapperId.'" class="dtg-ml-thumb-wrapper">';
                        $html .= '<div class="dtg-align-right"><img src="'.WPDEVHUB_CONST_DTG_URL_IMAGES.'/cancel.png" style="width:14px;" onclick="jQuery(\'#'.$wrapperId.'\').remove();" /></div>';
                        $html .= '<img src="'.$attachment_url.'" width="100" height="100" />';
                        $html .= '<input type="hidden" name="'.$options['objectName'].'_mediaId_'.$mediaId.'" id="'.$options['objectName'].'_mediaId_'.$mediaId.'" value="'.$mediaId.'" />';
                        $html .= '</li>';
                    }
                }
                $html .= '</ul>';

                $html .= '
	            <input id="'.$options['objectName'].'_button" type="button" class="dtg-button button" value="Open Media Library" onclick="DSCF_DTG_Admin.openMediaLibraryMultiple(\''.$options['objectName'].'\')" />
	            ';
                break;
            /*
            case self::ET_BUTTON_SAVE_AND_FORWARD_ADMIN:
                $page = '';
                $params = array();
                if(array_key_exists('params', $options)){
                    $params = $options['params'];
                }
                if(array_key_exists('page', $params)){
                    $page = $params['page'];
                    unset($params['page']);
                }
                if(!empty($page)){
                    $slug = DSCF_DTG_Utilities::getPageUrl($page, $params);
                    $html .= '<input type="hidden" name="forward_url_admin" value="'.$slug.'" />';
                    $html .= '<input type="submit" class="dtg-button" name="submit_save_and_forward" value="Save Changes" />';
                }else{
                    DSCF_DTG_Utilities::logMessage('variable "page" not set in params for ET_BUTTON_SAVE_AND_FORWARD_ADMIN');
                }

                break;
            */
        }
        return $html;
    }

    public static function saveArrayChanges($originalArray, $options, $requestVars){
        $changes = array();
        try{
            foreach($options as $option){
                $value = "";
                $processValue = false;
                switch ($option['objectType']){
                    case self::OT_DATE:
                        $enteredDate = $requestVars[$option['objectName']];
                        if(strlen(trim($enteredDate))>1){
                            $value = DSCF_DTG_StandardObjectRecord::formatIncomingDateString($requestVars[$option['objectName']]);
                        }else{
                            $value = "";
                        }
                        $processValue = true;
                        break;
                    case self::OT_NUMERIC:
                        $value = $requestVars[$option['objectName']];
                        if(!is_numeric($value)){
                            $value=0;
                        }
                        $processValue = true;
                        break;
                    case self::OT_STRING:
                        $value = $requestVars[$option['objectName']];
                        $value = str_replace("\\", "", $value);
                        $value = trim($value);
                        $processValue = true;
                        break;
                    case self::OT_BOOLEAN:
                        //DSCF_DTG_Utilities::logMessage("Inside the Editor with Object Type String");
                        if(array_key_exists($option['objectName'], $requestVars)){
                            $value = $requestVars[$option['objectName']];
                            if($value=='checked' || $value=='selected' || $value=='on'){
                                $value = true;
                            }else{
                                $value = false;
                            }
                        }else{
                            //Then it means it was a checkmark and was not selected...  will not be included in responseVars
                            $value = false;
                        }
                        $processValue = true;
                        break;
                    case self::OT_SKIP:
                        //Skip checking this variable :: useful for displaying entities only outside of the form
                        break;
                }//Switch Block
                if($processValue){
                    $changes[$option['objectName']]=$value;
                }
            }//For Loop
        }catch(Exception $e){

        }
        return $changes;

    }

    public static function saveEditorChanges($object, $options, $requestVars){

        DSCF_DTG_Utilities::logMessage("Inside saveEditorChanges");
        DSCF_DTG_Utilities::logMessage("Request Vars: ".print_r($requestVars, true));

        /*
          $options = array(
              0=array(
                  'title'='Name',
                  'objectType'=CommonTools::OT_STRING,
                  'objectName'='name',
                  'formType'=CommonTools::FT_TEXT,
                  'value'='Default Name',
                  'help'='Please fill out the name of the object'
                  ),
              1=array(
                  'title'='Status',
                  'objectType'=CommonTools::OT_BOOLEAN,
                  'formType'=CommonTools::FT_MENU,
                  'formOptions'=CommonTools::$defaultsMenuActive
                  'value'='active',
                  'help'='Select whether this object is active or inactive'
                  ),
              );
          */
        try{
            foreach($options as $option){
                $value = false;
                $processValue = false;
                if($option['title']=='ID'){
                    //Skip the ID Field
                }elseif(array_key_exists('rowType', $option) && $option['rowType']=='SectionHeader'){
                    // Skip the Section Headers
                }elseif(array_key_exists('rowType', $option) && $option['rowType']=='tabHeader'){
                    // Skip the Tab Headers
                }elseif(array_key_exists('skipIfEmpty', $option) && $option['skipIfEmpty']==true && $requestVars[$option['objectName']]==""){
                    //Skip the field if blank and marked to skip
                    $object->$option['objectName']="";
                }elseif(!array_key_exists('objectType', $option) || empty($option['objectType'])){
                    // Object type is empty -- we should not be here
                    DSCF_DTG_Utilities::logMessage("ObjectType Not Found in Editor: ".print_r($option, true));
                }else{
                    switch ($option['objectType']){
                        case self::OT_DATE:
                            $enteredDate = DSCF_DTG_Utilities::getFromArray($option['objectName'], $requestVars, 0);
                            if(strlen(trim($enteredDate))>1 || $option['objectName']=="createdDate"){
                                $value = DSCF_DTG_StandardObjectRecord::formatIncomingDateString($requestVars[$option['objectName']]);
                            }else{
                                $value = "";
                            }
                            break;
                        case self::OT_NUMERIC:
                            switch ($option['formType']){
                                case self::ET_MENU_STATUS:
                                    $value = DSCF_DTG_Utilities::getFromArray($option['objectName'], $requestVars, 0);
                                    if(!is_numeric($value) || empty($value)){
                                        $value=DSCF_DTG_StandardObjectRecord::STATUS_ACTIVE;
                                    }
                                    $processValue = true;
                                    break;
                                default:
                                    $value = DSCF_DTG_Utilities::getFromArray($option['objectName'], $requestVars, 0);
                                    if(!is_numeric($value)){
                                        $value=0;
                                    }
                                    $processValue = true;
                                    break;
                            }
                            break;
                        case self::OT_ARRAY_ARRAY:
                            switch ($option['formType']){
                                case self::ET_TEXT_ARRAY_ARRAY:
                                    $items = array();
                                    $newItem = $option['defaultArray'];
                                    if(is_array($option['value'])){
                                        foreach($option['value'] as $key=>$value){
                                            $newItem = $option['value'][$key];
                                            foreach($value as $k=>$v){
                                                if(array_key_exists($option['objectName'].$key.$k,$requestVars)){
                                                    $val = $requestVars[$option['objectName'].$key.$k];
                                                    $val = str_replace("\\", "", $val);
                                                    $newItem[$k]=$val;
                                                    $items[$key] = $newItem;
                                                }
                                            }

                                        }
                                    }
                                    for($i=0;$i<=50;$i++){
                                        $newlyMadeItem = array();
                                        foreach($newItem as $k=>$v){
                                            $fieldString = 'moreRows'.$option['objectName'].'_blank_'.$k.'_'.$i;
                                            //DSCF_DTG_Utilities::logMessage("Field String :: ".$fieldString);
                                            if(array_key_exists($fieldString,$requestVars)){
                                                $val = $requestVars[$fieldString];
                                                if(strlen($val)>=1){
                                                    $val = str_replace("\\", "", $val);
                                                    $newlyMadeItem[$k]=$val;
                                                    //DSCF_DTG_Utilities::logMessage("Newly Made Item: ".print_r($newlyMadeItem, true));
                                                }
                                            }
                                        }
                                        if(count($newlyMadeItem)>0){
                                            $items[] = $newlyMadeItem;
                                        }
                                    }
                                    $value = $items;
                                    break;
                            }
                            break;
                        case self::OT_ARRAY:
                            switch ($option['formType']){
                                case self::ET_CHECKBOX_GROUP:
                                    $items = array();
                                    foreach($option['formOptions'] as $key=>$value){
                                        $rvKeyname = $option['objectName'].$key;
                                        if(array_key_exists($rvKeyname, $requestVars) && $key==$requestVars[$rvKeyname]){
                                            $items[] = $key;
                                        }
                                    }
                                    $value = $items;
                                    break;
                                case self::ET_ZONE_ITEM_PICKER:
                                    $items = array();
                                    foreach($option['formOptions'] as $key=>$value){
                                        $rvKeyname = $option['objectName'].$key;
                                        error_log("ZONE_ITEM_PICKER: Key[$key] Value[$value] rvKeyname[$rvKeyname]");
                                        if(array_key_exists($rvKeyname, $requestVars) && $key==$requestVars[$rvKeyname]){
                                            $items[] = $key;
                                        }
                                    }
                                    $object->removeAllItemIds();
                                    $object->addItemIds($items);
                                    $value = $items;
                                    break;
                                case self::ET_ITEM_ZONE_PICKER:
                                    $zones = array();
                                    $allZones = array();
                                    foreach($option['formOptions'] as $key=>$value){
                                        $allZones[] = $key;
                                        $rvKeyname = $option['objectName'].$key;
                                        if(array_key_exists($rvKeyname, $requestVars) && $key==$requestVars[$rvKeyname]){
                                            $zones[] = $key;
                                        }
                                    }
                                    DSCF_DTG_Zone::removeZonesForItem($object->id, $allZones);
                                    DSCF_DTG_Zone::addZonesForItem($object->id, $zones);
                                    $value = false;		//Skip the save below
                                    break;
                                case self::ET_TEXT_ADDITIONAL:
                                    $items = array();
                                    if(is_array($option['value'])){
                                        foreach($option['value'] as $key=>$value){
                                            if($val = $requestVars[$option['objectName'].$key]){
                                                $val = str_replace("\\", "", $val);
                                                if(!empty($val)){
                                                    $items[] = $val;
                                                }
                                            }
                                        }
                                    }
                                    for($i=0;$i<=50;$i++){
                                        $fieldString = 'moreRows'.$option['objectName'].'_blankTxt_'.$i;
                                        //DSCF_DTG_Utilities::logMessage("Field String :: ".$fieldString);
                                        if(array_key_exists($fieldString,$requestVars)){
                                            $val = $requestVars[$fieldString];
                                            $val = str_replace("\\", "", $val);
                                            if(strlen(trim($val))>0){
                                                $items[] = $val;
                                            }
                                        }
                                    }
                                    $value = $items;
                                    //DSCF_DTG_Utilities::logMessage("ITEMS: ".print_r($items));
                                    break;
                                case self::ET_DPM_ANSWER_CHOICE_PICKER:
                                    if(is_array($option['formOptions'])){
                                        foreach($option['formOptions'] as $key=>$value){
                                            $fieldString = $option['objectName'].$key.'_txt';
                                            if(array_key_exists($fieldString, $requestVars)){
                                                $chkString = $option['objectName'].$key.'_chk';
                                                $status = DSCF_DTG_StandardObjectRecord::STATUS_INACTIVE;
                                                if(array_key_exists($chkString,$requestVars)){
                                                    $value = $requestVars[$chkString];
                                                    if($value == 'checked' || $value=='on'){
                                                        $status = DSCF_DTG_StandardObjectRecord::STATUS_ACTIVE;
                                                    }elseif($value == 'deleted'){
                                                        $status = "DELETE";
                                                    }
                                                }
                                                $val = $requestVars[$fieldString];
                                                $val = str_replace("\\", "", $val);
                                                $object->saveAnswerChoice($key, $val, $status);
                                            }
                                        }
                                    }
                                    for($i=0;$i<=50;$i++){
                                        $fieldString = $option['objectName'].'_blankTxt_'.$i;
                                        //DSCF_DTG_Utilities::logMessage("Field String :: ".$fieldString);
                                        if(array_key_exists($fieldString,$requestVars)){
                                            $chkString = $option['objectName'].'_blankChk_'.$i;
                                            $status = DSCF_DTG_StandardObjectRecord::STATUS_INACTIVE;
                                            if(array_key_exists($chkString,$requestVars)){
                                                $value = $requestVars[$chkString];
                                                if($value == 'checked' || $value=='on'){
                                                    $status = DSCF_DTG_StandardObjectRecord::STATUS_ACTIVE;
                                                }
                                            }
                                            $val = $requestVars[$fieldString];
                                            $val = str_replace("\\", "", $val);
                                            if(strlen(trim($val))>0){
                                                $object->saveAnswerChoice(0, $val, $status);
                                            }
                                        }
                                    }
                                    $value = false;
                                    //DSCF_DTG_Utilities::logMessage("ITEMS: ".print_r($items));
                                    break;
                                case self::ET_DQQ_ANSWER_CHOICE_PICKER:
                                    // Save the selected Answer Choice
                                    if(array_key_exists($option['objectName'].'_rdo', $requestVars)){
                                        $object->selectedAnswer = $requestVars[$option['objectName'].'_rdo'];
                                    }
                                    // Now save all the answers
                                    $items = array();
                                    if(is_array($option['value'])){
                                        foreach($option['value'] as $key=>$value){
                                            $keyname = $option['objectName'].$key.'_txt';
                                            if(array_key_exists($keyname, $requestVars)){
                                                 $val = $requestVars[$keyname];
                                                $val = str_replace("\\", "", $val);
                                                if(!empty($val)){
                                                    $items[$key] = $val;
                                                }
                                            }
                                        }
                                    }
                                    for($i=0;$i<=50;$i++){
                                        $fieldString = $option['objectName'].'_blankTxt_'.$i;
                                        //DSCF_DTG_Utilities::logMessage("Field String :: ".$fieldString);
                                        if(array_key_exists($fieldString,$requestVars)){
                                            $val = $requestVars[$fieldString];
                                            $val = str_replace("\\", "", $val);
                                            if(strlen(trim($val))>0){
                                                $items[$i] = $val;
                                            }
                                        }
                                    }
                                    $value = $items;
                                    //DSCF_DTG_Utilities::logMessage("ITEMS: ".print_r($items));
                                    break;
                                case self::ET_MEDIA_LIBRARY_MULTIPLE:
                                    $items = array();
                                    foreach($requestVars as $name=>$value){
                                        $string = $option['objectName']."_mediaId_";
                                        if(strpos($name, $string) !== false){
                                            $testValue = str_replace($string, "", $name);
                                            if($testValue == $value){
                                                // Parse Media ID should be same ad the ID in the value field
                                                $items[]=$value;
                                            }
                                        }
                                    }
                                    $value = $items;
                                    break;

                                case self::ET_DTT_ITEMS:

                                    $items = array();
                                    foreach($requestVars as $name=>$value){
                                        $string = $option['objectName']."_dtt_row_";
                                        if(strpos($name, $string) !== false){
                                            $rowKey = $option['objectName']."_dtt_item_".$value;

                                            //DSCF_DTG_Utilities::logMessage("KEY FOUND: Key[$name] Value[$value]");

                                            // Get the values
                                            $title = DSCF_DTG_Utilities::getFromArray($rowKey.'_title', $requestVars);
                                            $mediaId = DSCF_DTG_Utilities::getFromArray($rowKey.'_mediaId', $requestVars);
                                            $contents = DSCF_DTG_Utilities::getFromArray($rowKey.'_contents', $requestVars);

                                            $list = new DSCF_DTG_TopTen_Item();
                                            $list->title = $title;
                                            $list->mediaId = $mediaId;
                                            $list->contents = $contents;

                                            $items[]=$list;

                                            //DSCF_DTG_Utilities::logMessage("Finished with an Item: ".print_r($list, true));

                                        }
                                    }
                                    $value = $items;

                                    break;


                            }
                            break;
                        case self::OT_STRING:
                            switch ($option['formType']){
                                case self::ET_PASSWORD:
                                    $password = DSCF_DTG_Utilities::getFromArray($option['objectName'], $requestVars, "");
                                    if(strlen(trim($password))>2){
                                        $object->updatePassword($password);
                                        $value = false;
                                    }
                                    break;
                                default:
                                    $value = DSCF_DTG_Utilities::getFromArray($option['objectName'], $requestVars, "");
                                    $value = str_replace("\\", "", $value);
                                    $value = trim($value);
                                    $processValue = true;
                                    //DSCF_DTG_Utilities::logMessage("Common Save Editor TEXT: Value:".print_r($value,true));
                                    break;
                            }
                            break;
                        case self::OT_BOOLEAN:
                            //DSCF_DTG_Utilities::logMessage("Inside the Editor with Object Type String");
                            if(array_key_exists($option['objectName'], $requestVars)){
                                $value = $requestVars[$option['objectName']];
                                if($value=='checked' || $value=='selected' || $value=='on' || $value=='1'){
                                    DSCF_DTG_Utilities::logMessage("Settings Save: Array Key Exists Value is On");
                                    $value = true;
                                }else{
                                    DSCF_DTG_Utilities::logMessage("Settings Save: Array Key Exists Value is OFF");
                                    $value = false;
                                    $processValue = true;
                                }
                            }else{
                                //Then it means it was a checkmark and was not selected...  will not be included in responseVars
                                DSCF_DTG_Utilities::logMessage("Settings Save: Array Key Does Not Exist");
                                $value = false;
                                $processValue = true;
                            }
                            break;
                        case self::OT_SKIP:
                            //Skip checking this variable :: useful for displaying entities only outside of the form
                            break;
                        default:
                            $value = $requestVars[$option['objectName']];
                            break;
                    }
                    if($value || $processValue){
                        $objectName = $option['objectName'];
                        $object->$objectName = $value;
                    }
                }

            }

            $object->save();
            DSCF_DTG_Utilities::addUserMessage('Save Successful');


        }catch(Exception $e){
            error_log("Exception Caught: ".$e->getMessage());
        }


        return $object;

    }

    public static function renderAjaxInsertTodaysDate($formIds){
        if(!is_array($formIds)){
            $formIds = array($formIds);
        }
        $html = '<button onclick="';
        foreach($formIds as $formId){
            $html .= 'jQuery(\'#'.$formId.'\').val(\''.DSCF_DTG_StandardObjectRecord::formatJQueryDate(time()).'\');return false;';
        }
        $html .= '">insert today</button>';
        return $html;
    }

}
