<?php
/**
 * Created by JetBrains PhpStorm.
 * User: admin
 * Date: 1/6/17
 * Time: 11:31 PM
 * To change this template use File | Settings | File Templates.
 */


class DSCF_DTG_ComDispatch{

    public static $id = 2;
    public static $messageQueue = array();

    public function __destruct(){
        //self::sendQueue();
    }

    public static function getEndpoint(){
        return WPDEVHUB_CONST_DTG_WDH_SERVICES_BASE_URL."/client/cp/";
    }

    public static function queueRequest($dimbalRequest){
        self::$messageQueue[$dimbalRequest->id] = $dimbalRequest;
    }

    public static function getNextId(){
        self::$id++;
        return "r_".self::$id;
    }

    public static function resetQueue(){
        self::$messageQueue = array();
    }

    // Sends off the entire pending message queue
    public static function sendQueue(){


        if(empty(self::$messageQueue)){
            // No pending messages to send.
            return;
        }

        $batch = array();
        foreach(self::$messageQueue as $dimbalComRequest){
            $batch[] = $dimbalComRequest->postParams;
        }

        // Start setting up the post params
        $postBody = array('batch'=>$batch);

        // Setup the
        $requestParams = array(
            'method'=>'POST',
            'timeout'=>20,
            'body'=>$postBody
        );

        DSCF_DTG_Utilities::logMessage("About to call wp_safe_remote_post with the following values URL[".self::getEndpoint()."] PARAMS: ".print_r($requestParams, true));

        $response = wp_safe_remote_post(self::getEndpoint(), $requestParams);

        //DSCF_DTG_Utilities::logMessage("Response From Dimbal Request: ".print_r($response, true));

        $dataBody = DSCF_DTG_Utilities::getFromArray('body', $response);

        //DSCF_DTG_Utilities::logMessage("Response BODY From Dimbal Request: ".print_r($data, true));

        $dataBody = json_decode($dataBody, true);

        DSCF_DTG_Utilities::logMessage("Response BODY From Dimbal Request AFTER JSON DECODING: ".print_r($dataBody, true));

        // Copy the request queue to a temp variable
        $requestData = self::$messageQueue;

        // Reset the message queue
        self::resetQueue();

        // The response may be empty depending on what was requested
        if(is_array($dataBody) && !empty($dataBody)){

            // Loop through each response provided and process them
            foreach($dataBody as $responseItem){

                DSCF_DTG_Utilities::logMessage("Inside ResponseItem Loop: ResponseItem:" .print_r($responseItem, true));

                // Make sure the response object had a valid ID
                $requestId = DSCF_DTG_Utilities::getFromArray("rid", $responseItem);
                if(!empty($requestId)){

                    // Get the request object out of the pending queue data out
                    $requestObject = DSCF_DTG_Utilities::getFromArray($requestId, $requestData);
                    if(!empty($requestObject) && !empty($requestObject->callback)){

                        // Merge any of the response params set on the request object into the response object
                        if(!empty($requestObject->responseParams)){
                            $responseItem = array_merge($responseItem, $requestObject->responseParams);
                        }

                        // Now actually call the callback function
                        call_user_func_array($requestObject->callback, array($responseItem));

                    }

                }

            }

        }





    }

}
