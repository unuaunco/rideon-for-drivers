<?php

use Illuminate\Database\Seeder;

class MerchantsIntegrationTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('merchants_integration_types')->delete();

		DB::table('merchants_integration_types')->insert([
            ['id' => '1', 'name' => 'Gloria Food', 'description' => 'Integration with Gloria Food'],
            ['id' => '2', 'name' => 'Square Up', 'description' => 'Integration with Square Up'],
            ['id' => '3', 'name' => 'Shopify', 'description' => 'Integration with Shopify'],
            ['id' => '4', 'name' => 'CloudWaitress', 'description' => 'Integration with CloudWaitress'],
            ['id' => '5', 'name' => 'Yelo', 'description' => 'Integration with Yelo'], 
		]);
    }
}
