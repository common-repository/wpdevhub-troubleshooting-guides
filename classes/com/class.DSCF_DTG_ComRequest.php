<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 1/6/17
 * Time: 11:31 PM
 * To change this template use File | Settings | File Templates.
 */


class DSCF_DTG_ComRequest{

    public $id = null;
    public $postParams = array();
    public $callback = array();
    public $responseParams = array();

    public function __construct($options, $sendNow=false){

        /* SAMPLE STRUCTURE
         $options = array(
            'postParams'=>array(
                'method'=>'get_next_article',
            ),
            'callback'=>array(
                'DimbalClientDashboard','handleNewArticleFromDimbal'
            ),
            'responseParams'=>array(
                'scheduled_timestamp'=>$scheduledTimestamp
            )
         );
         */

        // Setup the ID
        $this->id = DSCF_DTG_ComDispatch::getNextId();

        // Setup the post params
        $this->postParams = DSCF_DTG_Utilities::getFromArray('postParams', $options, array());
        if(empty($this->postParams)){
            DSCF_DTG_Utilities::logError(__CLASS__."::".__FUNCTION__." - Cannot create request without post params");
        }

        // Add the ID to the post params
        $this->postParams['rid']=$this->id;

        // Setup the callback - optional param
        $this->callback = DSCF_DTG_Utilities::getFromArray('callback', $options, null);

        // Setup any response params
        $this->responseParams = DSCF_DTG_Utilities::getFromArray('responseParams', $options, null);

        // Queue up the request
        DSCF_DTG_ComDispatch::queueRequest($this);

        // if we are choosing to send the data now - then do it
        if($sendNow){
            DSCF_DTG_ComDispatch::sendQueue();
        }

        return $this;
    }



}
