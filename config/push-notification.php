<?php

return array(

    'Rider'     => array(
        'environment' =>'development',
        'certificate' =>app_path().'/RideOnRiderDev.pem',
        'passPhrase'  =>'password',
        'service'     =>'apns'
    ),
    'Driver'     => array(
        'environment' =>'development',
        'certificate' => app_path().'/RideOnDriverDev.pem',
        'passPhrase'  =>'password',
        'service'     =>'apns'
    )

);



?>