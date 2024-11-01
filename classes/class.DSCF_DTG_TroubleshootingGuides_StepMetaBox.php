<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 3/21/17
 * Time: 3:46 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_TroubleshootingGuides_StepMetaBox extends DSCF_DTG_StandardMetaBox{

    const KEYNAME = "dtg_steps";
    const TITLE = "Troubleshooting Steps";
    const SCREEN = DSCF_DTG_TroubleshootingGuides_Guide::KEYNAME;

    const PRIORITY = "high";

    public static function renderCustom( $post ){

        // Use get_post_meta to retrieve an existing value from the database.
        $steps = self::getPostMeta( $post->ID );
        $steps = DSCF_DTG_Utilities::ensureIsArray($steps);

        $html = '';

        // Display the form, using the current value.
        wp_enqueue_media();
        $key=0;

        $html .= '<ul class="DSCF_DTG_jquery-ui-sortable">';
        if(!empty($steps)){
            foreach($steps as $value){
                // The "$value" should be an Object representing an item

                // Validate instance type
                if(is_a($value, 'DSCF_DTG_TroubleshootingGuides_Step')){

                    $rowId = 'dtg_row_'.$key;
                    $rowKey = 'dtg_step_'.$key;

                    $html .= '<li id="'.$rowKey.'_wrapper" class="dtg-pbox5">';

                    // Hidden Element to identify this row
                    $html .= '<input type="hidden" name="'.$rowId.'" value="'.$key.'" />';

                    $html .= '<p>';
                    $html .= '<button style="float:right" onClick="javascript:DSCF_DTG_Admin.deleteGenericWrapperRow(\''.$rowKey.'\')">Delete Item</button>';
                    $html .= 'Title: <input type="text" name="'.$rowKey.'_title" value="'.$value->title.'" size="50" />';
                    $html .= '</p>';

                    ob_start();
                    wp_editor($value->contents, $rowKey.'_contents', array('textarea_rows'=>5));
                    $html .= '<div>'.ob_get_clean().'</div>';

                    $html .= '</li>';

                    $key++;
                }

            }
        }

        $html .= '</ul>';
        $html .= '<input type="hidden" id="dtg_row_counter" value='.$key.' />';
        $html .= '<div id="addMoreDtgStepRows"><a href="javascript:DSCF_DTG_Admin.addDtgStepRow();">add list item</a></div>';

        echo $html;

    }

    public static function saveCustom( $post_id ){

        // Sanitize the user input.
        //$mydata = sanitize_text_field( $_POST['myplugin_new_field'] );

        // Update the meta field.
        //update_post_meta( $post_id, 'DSCF_DTG_steps', $mydata );

        $items = array();
        foreach($_POST as $name=>$value){
            $string = "dtg_row_";
            if(strpos($name, $string) !== false){
                $rowKey = "dtg_step_".$value;

                //DSCF_DTG_Utilities::logMessage("KEY FOUND: Key[$name] Value[$value]");

                // Get the values
                $title = DSCF_DTG_Utilities::getFromArray($rowKey.'_title', $_POST);
                $contents = DSCF_DTG_Utilities::getFromArray($rowKey.'_contents', $_POST);

                if(!empty($title) && !empty($contents)){
                    $step = new DSCF_DTG_TroubleshootingGuides_Step();
                    $step->title = $title;
                    $step->contents = $contents;

                    $items[]=$step;

                    //DSCF_DTG_Utilities::logMessage("Finished with a Step: ".print_r($step, true));
                }



            }
        }

        self::savePostMeta( $post_id, $items );

    }

}
