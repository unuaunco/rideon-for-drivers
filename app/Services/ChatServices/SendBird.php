<?php

/**
 * Stripe Payout Service
 *
 * @package     RideOnForDrivers
 * @subpackage  Services\ChatServices
 * @category    SendBird
 * @author      RideOn team
 * @version     2.2
 * @link        https://joinrideon.com
 */

namespace App\Services\ChatServices;

use App\Models\Country;

class SendBird
{
    /**
     * Intialize SendBird with Secret key and parameters
     *
     */
    public function __construct()
    {
        //$this->request_helper = resolve('App\Http\Helper\RequestHelper');
        // ab42dc5795df3d35c76540cdeff56723406ad9d4
        $this->master_api_token = \api_credentials('master_api_token', 'SendBird');
        // 9ED0109B-5FEC-44E0-8D15-24D9CB64F78D
        $this->application_id = \api_credentials('application_id', 'SendBird');

        $this->base_url = "https://api-" . $this->application_id . ".sendbird.com/";
        $this->api_version = "v3";
    }

    /**
     * Send request to SendBird API
     *
     * @param string $api_endpoint  Api endpoint
     * @param array $parameters    Input request parameters values
     * @param string $request_type
     * @return array Array of data
     */
    protected function sendRequest($api_endpoint, $request_type, $parameters)
    {

        $ch = curl_init();

        $full_request_url = $this->base_url . $this->api_version . "/" . $api_endpoint;
        
        if ($request_type == "GET"){
            if($parameters){
                $full_request_url = $full_request_url . "?" . http_build_query($parameters);
            }
        }
        else{
            curl_setopt($ch, CURLOPT_FAILONERROR, true);                                                                    
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
        }

        // Init API URL to send data
        curl_setopt($ch, CURLOPT_URL, $full_request_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // SET Header
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json, charset=utf8',
            'Api-Token: ' . $this->master_api_token
        ));

        // Execute curl and assign returned data
        $response  = curl_exec($ch);
        $response_result = json_decode($response);

        // Close curl
        curl_close($ch);

        return $response_result;
    }


    // Group channel functions group

    /**
     * Get list of groups
     *
     * @return array Array of channels data
     */
    public function getChannelsList($data)
    {
        return $this->sendRequest("group_channels", "GET", $data);
    }

    /**
     * Get group data
     *
     * @return array Array of channels data
     */
    public function getChannelData($channel_url)
    {
        return $this->sendRequest("group_channels/" . $channel_url, "GET", false);
    }

    /**
     * Create group
     * 
     * @param string $name  Channel name
     * @param array $data Array of data to create channel
     * @return array Array of channel data
     */
    public function createChannel($name, $data)
    {
        $parameters = array_merge(array("name" => $name), $data);
        return $this->sendRequest("group_channels", "POST", $parameters);
    }

    /**
     * Invite user to the group
     * 
     * @param string $channel_url Channel url
     * @param array $user_ids Array user ids
     * @return array Array of channel data
     */
    public function inviteUserToChannel($user_ids, $channel_url)
    {
        $parameters = array("user_ids" => $user_ids);
        return $this->sendRequest("group_channels/" . $channel_url . "/invite", "POST", $parameters);
    }
}
