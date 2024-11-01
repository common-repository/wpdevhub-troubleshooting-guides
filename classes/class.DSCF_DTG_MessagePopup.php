<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 2/23/17
 * Time: 8:15 PM
 * To change this template use File | Settings | File Templates.
 */


class DSCF_DTG_MessagePopup{

    public $id;
    public $title;
    public $content;
    public $hidden;
    public $style='';
    public $html;

    public function __construct($options=array(), $styles=array()){
        $defaultOptions=array(
            'hidden'=>true,	//hidden by default
            'title'=>'',
            'content'=>'Anything including <span="font-weight:bold;">HTML</span> is allowed in here',
        );
        $defaultStyles=array(
            'width'=>'800',
            'height'=>'auto',
        );
        $o = array_merge($defaultOptions,$options);
        $s = array_merge($defaultStyles,$styles);
        $styleString = self::getHtmlStyleString($s);

        $this->id = rand(0,1000000);

        $this->title = $o['title'];
        $this->content = $o['content'];
        $this->hidden = $o['hidden'];
        $this->style = $styleString;

        return $this;

    }

    public function getDisplayCode(){
        $elemId = $this->getElementId();
        $html = '';
        $dimbalPopupHidden = '';
        if($this->hidden){
            $dimbalPopupHidden = ' dtg-popup-hidden';
        }

        $html .= '<div id="'.$elemId.'Grey" class="dtg-popup-grey'.$dimbalPopupHidden.'" onclick="jQuery(\'#'.$elemId.'Wrapper\').toggle();jQuery(\'#'.$elemId.'Grey\').toggle();">';
        $html .= '</div>';

        $html .= '<div id="'.$elemId.'Wrapper" class="dtg-popup-wrapper'.$dimbalPopupHidden.'">';
        $html .= '<div id="'.$elemId.'Container" class="dtg-popup-container" style="'.$this->style.'">';
        $html .= '<div id="'.$elemId.'Close" class="dtg-popup-close" onclick="jQuery(\'#'.$elemId.'Wrapper\').toggle();jQuery(\'#'.$elemId.'Grey\').toggle();">';
        $html .= '<img src="'.WPDEVHUB_CONST_DTG_URL_IMAGES .'/cancel.png" />';
        $html .= '</div>';
        if(!empty($this->title)){
            $html .= '<div class="dtg-popup-title">';
            $html .= $this->title;
            $html .= '</div>';
        }
        if(!empty($this->content)){
            $html .= '<div class="dtg-popup-content">';
            $html .= $this->content;
            $html .= '<br style="clear:both;">';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function getJavascriptOpenCode(){
        $elemId = $this->getElementId();
        return 'jQuery(\'#'.$elemId.'Wrapper\').toggle();jQuery(\'#'.$elemId.'Grey\').toggle();';
    }

    public function getElementId(){
        return "dimbalPopup_".$this->id;
    }

    public static function getHtmlStyleString($options){
        $html = '';
        foreach($options as $key=>$value){
            $html .= ' '.$key.':'.$value.';';
        }
        return $html;
    }


}
