<?php

use Illuminate\Database\Seeder;

class JoinUsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('join_us')->delete();

        DB::table('join_us')->insert([
                ['name' => 'facebook', 'value' => 'https://www.facebook.com/Rideonfordrivers.Technologies/'],
                ['name' => 'google_plus', 'value' => ''],
                ['name' => 'twitter', 'value' => 'https://twitter.com/RideonfordriversTech'],
                ['name' => 'linkedin', 'value' => 'https://www.linkedin.com/company/13184720'],
                ['name' => 'pinterest', 'value' => 'https://in.pinterest.com/RideonfordriversTech/'],
                ['name' => 'youtube', 'value' => 'https://www.youtube.com/channel/UC2EWcEd5dpvGmBh-H4TQ0wg'],
                ['name' => 'instagram', 'value' => 'https://www.instagram.com/Rideonfordriverstech'],
                ['name' => 'app_store_rider', 'value' => 'https://itunes.apple.com/in/app/RideOn-on-demand-service/id1253818335?mt=8'],
                ['name' => 'app_store_driver', 'value' => 'https://itunes.apple.com/in/app/RideOn-driver-on-demand-service/id1253819680?mt=8'],
                ['name' => 'play_store_rider', 'value' => 'https://play.google.com/store/apps/details?id=com.Rideonfordrivers.RideOn&hl=en'],
                ['name' => 'play_store_driver', 'value' => 'https://play.google.com/store/apps/details?id=com.Rideonfordrivers.RideOndriver&hl=en'],
            ]);
    }
}
