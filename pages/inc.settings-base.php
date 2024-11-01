<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 3/18/15
 * Time: 8:47 PM
 * To change this template use File | Settings | File Templates.
 */

echo DSCF_DTG_Utilities::buildHeader(array(
    'title'=>'Software Settings',
    'icon'=>WPDEVHUB_CONST_DTG_URL_IMAGES.'/cog.png',
    'description'=>'Change default behaviour and more in this settings panel.',
));

// See if the editor was passed
$editor = DSCF_DTG_Utilities::getRequestVarIfExists('formEditor');

// Update or Insert the Choices as appropriate
$options = call_user_func(array($settingsClassname, 'buildSettingsEditorOptions'));

// Get the Settings Object
$object = DSCF_DTG_StandardSetting::getSettingsObject($options);

if($object && $editor){

    // Save the changes from the editor into the object
    $object = DSCF_DTG_StandardEditor::saveEditorChanges($object,$options,$_REQUEST);

    DSCF_DTG_Utilities::logMessage("Dimbal Settings Object after Changes: ".print_r($object, true));

    // Now set the cache object back
    DSCF_DTG_StandardSetting::saveSettingsObject($object, $options);

}

// Now rebuild the options with the new saved data
$options = call_user_func(array($settingsClassname, 'buildSettingsEditorOptions'), $object);

// Build the editor in almost all circumstances
echo DSCF_DTG_StandardEditor::buildEditor($options, '#');

// Close the wrapper
echo DSCF_DTG_Utilities::buildFooter();
