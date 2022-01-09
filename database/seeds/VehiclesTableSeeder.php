<?php

use Illuminate\Database\Seeder;

class VehiclesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('car_type')->insert(['id'=>'1','car_name' =>'RideOnGo','description' =>'RideOnGo','status' =>'Active','vehicle_image'=> 'RideOngo.png','active_image' =>'RideOngo.png']);
        DB::table('car_type')->insert(['id'=>'2','car_name' =>'RideOnX','description' =>'RideOnX','status' =>'Active','vehicle_image'=> 'RideOnx.png','active_image' =>'RideOnx.png']);
        DB::table('car_type')->insert(['id'=>'3','car_name' =>'RideOnXL','description' =>'RideOnXL','status' =>'Active','vehicle_image'=> 'RideOnxl.png','active_image' =>'RideOnxl.png']);
    }   
}