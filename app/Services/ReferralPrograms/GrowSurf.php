<?php

/**
 * GrowSurf Referral Programs
 *
 * @package     RideOnForDrivers
 * @subpackage  Services\ReferralPrograms
 * @category    GrowSurf
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
*/

namespace App\Services\ReferralPrograms;

use DB;
use GuzzleHttp\Client;

class GrowSurf
{
    /**
     * Set token of GrowSurf api key
     *
     */
    public function __construct()
    {
        $this->api_key = \api_credentials('growsurf_api_key', 'GrowSurf');
    }

    /**
     * Create participant in GrowSurf through API
     *
     * @param object $user_data   Object, contains fields:
     * email (*required), firstName, lastName, referredBy, referralStatus, ipAddress, metadata
     *
     * @return Response GrowSurf response
     */
    /* TODO: Handle request error on this function
    and  return only success with id (or shareUrl) or failed status without other data */
    public function createUser($user_data, $getCampaignId = null) {
        try{
            if (! $getCampaignId){
                $getCampaignId = site_settings('growsurf_campaign_id');
            }

            $user_data = json_encode($user_data);

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "https://api.growsurf.com/v2/campaign/".$getCampaignId."/participant");
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $user_data);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer ".$this->api_key));

            $result = curl_exec($curl);
            $response = json_decode($result);
            curl_close($curl);

            if(isset($response->error)) {
                return array('status' => false,"status_message" => $response->error_description);
            }
            return $response;
        } catch(\Exception $e) { errorLog($e); }
    }

    public function updateUser($user_data, $participant_id, $getCampaignId = null ) {
        try{
            if (! $getCampaignId){
                $getCampaignId = site_settings('growsurf_campaign_id');
            }

            $user_data = json_encode($user_data);

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "https://api.growsurf.com/v2/campaign/".$getCampaignId."/participant/".$participant_id);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $user_data);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer ".$this->api_key));

            $result = curl_exec($curl);
            $response = json_decode($result);
            curl_close($curl);

            if(isset($response->error)) {
                return array('status' => false,"status_message" => $response->error_description);
            }
            return $response;
        } catch(\Exception $e) { errorLog($e); }
    }
}