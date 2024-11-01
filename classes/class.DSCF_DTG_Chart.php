<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 3/24/17
 * Time: 7:18 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_Chart{

    /*
     * <div id="'+barChart.elemId+'" class="dtg-charts-wrapper-half"></div>
     *
     *
var pieChart = new dimbalChartObject();
pieChart.type = 1;
pieChart.elemId = wrapperId+'_dist_pie_chart';
pieChart.data = googleDataArray;
pieChart.options = {title:'Response Distribution by Answer Choice'};
jQuery("#"+wrapperId).append('<div id="'+pieChart.elemId+'" class="dtg-charts-wrapper-half"></div>');
DSCF_DTG_Charts.chartsToDo.push(pieChart);
    */

    public $type=1;
    public $elemId='';
    public $data=array();       // Array is fine - will be converted to JSON object during data display
    public $options=array();    // Array is fine - will be converted to JSON object during data display

    const TYPE_PIE = 1;
    const TYPE_BAR = 2;
    const TYPE_TABLE = 3;
    const TYPE_ANNOTATION = 4;
    const TYPE_COLUMN = 5;
    const TYPE_LINE = 6;

    public function buildChartDisplayAndLoader(){
        $html = '';
        $html .= $this->displayChart();
        $html .= self::displayChartLoader();
        return $html;
    }

    public function buildChartDisplay($includeScriptTags=true){
        $html = '';

        // Create the basic clean object
        $item = new stdClass();
        $item->type = $this->type;
        $item->elemId = $this->elemId;
        $item->data = $this->data;
        $item->options = $this->options;

        // Create the HTML code
        if($includeScriptTags){
            $html .= '<script>';
        }
        $html .= '
jQuery(document).ready(function($) {
    DSCF_DTG_Charts.chartsToDo.push('. json_encode($item)  .');

});
        ';
        if($includeScriptTags){
            $html .= '</script>';
        }

        return $html;
    }

    public static function buildChartLoader($includeScriptTags=true){
        $html = '';

        // Create the HTML code
        if($includeScriptTags){
            $html .= '<script>';
        }
        $html .= '
jQuery(document).ready(function($) {
    DSCF_DTG_Charts.drawGoogleCharts();

});
        ';
        if($includeScriptTags){
            $html .= '</script>';
        }

        return $html;
    }

}
