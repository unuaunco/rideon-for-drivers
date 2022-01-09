<?php

/**
 * Message Controller
 *
 * @package     RideOnForDrivers
 * @subpackage  Controller
 * @category    Message
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function sendAutoResponse(Request $request)
    {
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders['secret_key']) && $requestHeaders['secret_key'] == 'rUUP0LvrTl') {
            $app_name = $request->post('app');
            $sender = $request->post('sender');
            $message = strtolower($request->post('message'));
            if ($app_name == "WhatsApp") {
                switch ($message) {
                    case 'i want to starting doing deliveries':
                        $reply = ['reply' => 'Great, you can complete an application on this page - www.joinrideon.com/drive'];
                        break;
                    case 'i have issues with the driver\'s app':
                        $reply = ['reply' => "If you applied in the last 48 hrs, our team may not have confirmed you application yet. Please check your email if you are required to provide any additional verifying documents. If you have already been approved, please reset your password from the app and try again. If that fails, please email rodo@rideon.group and we will check your details in our system.\n\nIs there any other issue with the app that we can try and resolve for you?\n\nThank you. One of our team will respond shortly."];
                        break;
                    case 'i have issues with payments':
                        $reply = ['reply' => "Can you please outline what the deliveries were that are problematic.\n\nThank you. We will review this and revert with confirmation. Don’t worry we will solve it."];
                        break;
                    case 'other matter':
                        $reply = ['reply' => "Please let us know.\n\nThank you. One of our team will respond shortly"];
                        break;
                    default:
                        $reply = ['reply' => 'Hi ' . $sender . ', How can we help you today?'];
                        break;
                }
                echo json_encode($reply);
            }
        }
        return;

    }
}
