<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FrontendSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('frontend_settings')->delete();
        
        \DB::table('frontend_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'type' => 'landing-page-setting',
                'key' => 'section_1',
                'status' => 1,
                'value' => '"{\\"title\\":\\"Your Prouct, Our Priority - Book Today!\\",\\"description\\":\\"Get the perfect haircut that suits your style and personality with our expert stylists using the latest techniques.\\",\\"enable_search\\":1,\\"section_1\\":1,\\"status\\":1}"',
                'created_at' => NULL,
                'updated_at' => '2025-08-18 06:26:51',
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'type' => 'landing-page-setting',
                'key' => 'section_2',
                'status' => 1,
                'value' => '"{\\"section_2\\":1,\\"status\\":1}"',
                'created_at' => NULL,
                'updated_at' => '2025-08-13 12:36:07',
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'type' => 'landing-page-setting',
                'key' => 'section_3',
                'status' => 1,
                'value' => '{"section_3":1,"branch_id":"Our Popular Branch"}',
                'created_at' => NULL,
                'updated_at' => NULL,
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            3 => 
            array (
                'id' => 4,
                'type' => 'landing-page-setting',
                'key' => 'section_4',
                'status' => 1,
                'value' => '"{\\"section_4\\":1,\\"status\\":1,\\"select_category\\":[\\"1\\",\\"2\\",\\"4\\",\\"5\\",\\"3\\",\\"6\\",\\"21\\",\\"22\\"]}"',
                'created_at' => NULL,
                'updated_at' => '2025-08-18 06:27:15',
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            4 => 
            array (
                'id' => 5,
                'type' => 'landing-page-setting',
                'key' => 'section_5',
                'status' => 1,
                'value' => '{"section_5":1,"package_id":"on"}',
                'created_at' => NULL,
                'updated_at' => NULL,
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            5 => 
            array (
                'id' => 6,
                'type' => 'landing-page-setting',
                'key' => 'section_6',
                'status' => 1,
                'value' => '{"section_6":1,"membership_id":"on"}',
                'created_at' => NULL,
                'updated_at' => NULL,
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            6 => 
            array (
                'id' => 7,
                'type' => 'landing-page-setting',
                'key' => 'section_7',
                'status' => 1,
                'value' => '"{\\"status\\":1,\\"expert_id\\":[\\"13\\",\\"14\\",\\"15\\",\\"16\\",\\"17\\",\\"18\\",\\"20\\",\\"22\\",\\"24\\",\\"26\\",\\"27\\",\\"28\\",\\"29\\",\\"30\\",\\"31\\",\\"32\\",\\"33\\"]}"',
                'created_at' => NULL,
                'updated_at' => '2025-08-21 07:08:02',
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            7 => 
            array (
                'id' => 8,
                'type' => 'landing-page-setting',
                'key' => 'section_8',
                'status' => 1,
                'value' => '"{\\"status\\":1,\\"product_id\\":[\\"2\\",\\"3\\",\\"4\\",\\"5\\",\\"6\\",\\"7\\",\\"9\\",\\"12\\",\\"15\\",\\"16\\",\\"17\\"]}"',
                'created_at' => NULL,
                'updated_at' => '2025-08-14 11:27:44',
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            8 => 
            array (
                'id' => 9,
                'type' => 'landing-page-setting',
                'key' => 'section_9',
                'status' => 1,
                'value' => '"{\\"status\\":1,\\"title_id\\":\\"\\",\\"subtitle_id\\":\\"\\",\\"description_id\\":\\"\\"}"',
                'created_at' => NULL,
                'updated_at' => '2025-08-14 11:28:21',
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            9 => 
            array (
                'id' => 10,
                'type' => 'landing-page-setting',
                'key' => 'section_10',
                'status' => 1,
                'value' => '"{\\"status\\":1,\\"customer_id\\":1}"',
                'created_at' => NULL,
                'updated_at' => '2025-08-18 05:11:50',
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            10 => 
            array (
                'id' => 11,
                'type' => 'landing-page-setting',
                'key' => 'section_11',
                'status' => 1,
                'value' => '{"section_11":1,"title_id":"Daily tips to remember","subtitle_id":"Daily tips to remember","select_blog_id":"Daily tips to remember"}',
                'created_at' => NULL,
                'updated_at' => NULL,
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            11 => 
            array (
                'id' => 12,
                'type' => 'header-menu-setting',
                'key' => 'header-menu-setting',
                'status' => 1,
                'value' => '"{\\"selectbranch\\":true,\\"home\\":true,\\"mybooking\\":true,\\"category\\":true,\\"service\\":true,\\"shop\\":true,\\"header_offer_section\\":true,\\"header_offer_title\\":\\"Limited Offer Sign up and receive 20% bonus discount on checkout\\",\\"status\\":true,\\"enable_search\\":true,\\"enable_language\\":true,\\"enable_darknight_mode\\":true}"',
                'created_at' => NULL,
                'updated_at' => '2025-08-18 05:50:48',
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
            12 => 
            array (
                'id' => 13,
                'type' => 'footer-setting',
                'key' => 'footer-setting',
                'status' => 1,
                'value' => '"{\\"about\\":1,\\"category\\":1,\\"quicklinks\\":1,\\"stayconnected\\":1,\\"select_category\\":[\\"4\\",\\"7\\",\\"12\\",\\"15\\",\\"19\\",\\"22\\",\\"27\\"],\\"social_links\\":{\\"facebook\\":\\"https:\\\\\\/\\\\\\/www.facebook.com\\\\\\/iqonicdesign\\\\\\/\\",\\"youtube\\":\\"https:\\\\\\/\\\\\\/www.youtube.com\\\\\\/iqonicdesign\\",\\"instagram\\":\\"https:\\\\\\/\\\\\\/www.instagram.com\\\\\\/iqonicdesign\\\\\\/?igshid=YmMyMTA2M2Y%3D\\",\\"twitter\\":\\"https:\\\\\\/\\\\\\/www.youtube.com\\\\\\/iqonicdesign\\"}}"',
                'created_at' => NULL,
                'updated_at' => '2025-08-21 06:14:36',
                'deleted_at' => NULL,
                'created_by' => NULL,
                'updated_by' => NULL,
            ),
        ));
        
        
    }
}