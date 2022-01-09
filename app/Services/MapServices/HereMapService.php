<?php

/**
 * Stripe Payout Service
 *
 * @package     RideOnForDrivers
 * @subpackage  Services\Payouts
 * @category    Stripe
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
*/

namespace App\Services\MapServices;

use App\Models\Country;

class HereMapService
{
	/**
     * Intialize Stripe with Secret key
     *
     */	
    public function __construct()
    {
        //$this->request_helper = resolve('App\Http\Helper\RequestHelper');
        $this->rest_api_key = \api_credentials('rest_api_key', 'Here');
        $this->js_api_key = \api_credentials('javascript_api_key', 'Here');

        $this->base_url = "https://router.hereapi.com/";
        $this->api_version = "v8";
    }

    /**
     * Send request to HERE API
     *
     * @param string $api_endpoint  Api endpoint
     * @param array $parameters    Input request parameters values
     * @return array Array of data
     */
    protected function sendRequest($api_endpoint, $parameters)
    {

        $ch = curl_init();

        // Init API URL to send data
        $full_request_url = $this->base_url . $this->api_version . "/" . $api_endpoint . "?" . http_build_query($parameters) . "&apiKey=" . $this->rest_api_key;
        curl_setopt($ch, CURLOPT_URL, $full_request_url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute curl and assign returned data
        $response  = curl_exec($ch);
        $response_result = json_decode($response);
        // Close curl
        curl_close($ch);

        return $response_result;
    }

    /**
     * Get estimate time to arrival from HERE API
     *
     * @param object $request   Object with origin and destination coordinates
     * @return integer Minutes to arrival
     */
    public function getETA($request){
        
        $parameters = array(
            "transportMode" => "car",
            "origin"        => $request->origin_latitude . "," . $request->origin_longitude,
            "destination"   => $request->destination_latitude . "," . $request->destination_longitude,
            "return"        =>"summary",
        );

        $result = $this->sendRequest("routes", $parameters);
        
        $estimate_time = $result->routes[0]->sections[0]->summary->duration;

        return (int)round($estimate_time / 60);
    }

    /**
     * Get distance besween to points from HERE API
     *
     * @param object $request   Object with origin and destination coordinates
     * @return float Distance in Meters
     */
    public function getDistance($request){

        $parameters = array(
            "transportMode" => "car",
            "origin"        => $request->origin_latitude . "," . $request->origin_longitude,
            "destination"   => $request->destination_latitude . "," . $request->destination_longitude,
            "return"        =>"summary",
        );

        $result = $this->sendRequest("routes", $parameters);

        return $result->routes[0]->sections[0]->summary->length;
    }

    /**
     * Get polyline of route from HERE API
     *
     * @param object $request   Object with origin and destination coordinates
     * @return float Distance in Meters
     */
    public function getPolyline($request){

        $parameters = array(
            "transportMode" => "car",
            "origin"        => $request->origin_latitude . "," . $request->origin_longitude,
            "destination"   => $request->destination_latitude . "," . $request->destination_longitude,
            "return"        =>"summary",
        );

        $result = $this->sendRequest("routes", $parameters);

        return $result->routes[0]->sections[0]->summary->length;
    }

}