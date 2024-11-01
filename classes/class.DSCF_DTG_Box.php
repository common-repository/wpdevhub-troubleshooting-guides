<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 5/22/15
 * Time: 5:12 PM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_Box{

    public $id;                 // An id to use for the element.  If missing a random int will be used
    public $type=1;             // The type id to identify how to be displayed
    public $size=1;             // The size id for this box
    public $title="";           // The title for the box
    public $icon="";            // The icon to use
    public $iconStyle="";       // A style string to use on the icon
    public $content="";         // The content for the box, will be put inside a div that can be styled
    public $contentStyle="";    // A style string to apply to the content
    public $buttons=array();

    const TYPE_STANDARD = 1;    // Bigger icon on the left, text and content on the right
    const TYPE_TRIM = 2;        // Smaller icon and text on top, content underneath

    const SIZE_FULL = 1;
    const SIZE_TWO_THIRDS = 2;
    const SIZE_ONE_HALF = 3;
    const SIZE_ONE_THIRD = 4;
    const SIZE_ONE_FOURTH = 5;

    public function __construct($options=array()){
        foreach($options as $key=>$value){
            $this->$key = $value;
        }

        if(empty($this->id)){
            $this->id = rand(100000,99999);
        }

        return $this;
    }

    public static function renderBoxes($batch){
        $html = '';
        foreach($batch as $item){
            $html .= $item->renderBox();
        }

        return $html;
    }

    public function renderBox(){
        $html = '';

        $size = '100%';
        switch($this->size){
            case self::SIZE_FULL:
                $size = '98%';
                break;
            case self::SIZE_ONE_HALF:
                $size = '47%';
                break;
            case self::SIZE_ONE_THIRD:
                $size = '31%';
                break;
            case self::SIZE_TWO_THIRDS:
                $size = '64%';
                break;
            case self::SIZE_ONE_FOURTH:
                $size = '22%';
                break;
        }

        switch($this->type){
            case self::TYPE_TRIM:
                $html .= '
                <div class="dtg-promo-box-2" style="width:'.$size.'">
                    <div class="dtg-promo-box-2-title-cell">
                    ';
                    if(!empty($this->icon)){
                        $html .= '<div class="dtg-promo-box-2-title-img-cell">
                                <img src="'.$this->icon.'" alt="'.$this->title.'" style="width:40px;'.$this->iconStyle.'" />
                            </div>';
                    }
                    if(!empty($this->title)){
                        $html .= '<div class="dtg-promo-box-2-title-text-cell">
                                <h2>'.$this->title.'</h2>
                            </div>';
                    }
                    $html .= '
                    </div>
                    <div class="dtg-promo-box-2-main-cell">
                        <div style="'.$this->contentStyle.'">'.$this->content.'</div>
                        <br />
                        ';
                        foreach($this->buttons as $button){
                            $html .= DSCF_DTG_Utilities::buildButton($button);
                        }
                        $html .= '
                    </div>
                </div>
                ';
                break;
            case self::TYPE_STANDARD:
                $html .= '
                <div class="dtg-promo-box-1" style="width:'.$size.'">
                    <div style="display:table; width:100%;">
                        <div class="dtg-promo-box-1-img-cell">
                            <img src="'.$this->icon.'" alt="'.$this->title.'" style="'.$this->iconStyle.'" />
                        </div>
                        <div class="dtg-promo-box-1-main-cell">
                            <h2>'.$this->title.'</h2>
                            <div style="'.$this->contentStyle.'">'.$this->content.'</div>
                            <br />
                            ';
                            foreach($this->buttons as $button){
                                $html .= DSCF_DTG_Utilities::buildButton($button);
                            }
                            $html .= '
                        </div>
                    </div>
                </div>
                ';
                break;
        }

        return $html;
    }

}
