<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('settings')->delete();

        \DB::table('settings')->insert(array(
            0 =>
            array(
                'id' => 1,
                'name' => 'slot_duration',
                'val' => '00:15',
                'type' => 'text',
                'created_by' => NULL,
                'updated_by' => NULL,
                'deleted_by' => NULL,
                'created_at' => '2025-08-20 10:20:19',
                'updated_at' => '2025-08-20 10:20:19',
                'deleted_at' => NULL,
            ),
            1 =>
            array(
                'id' => 2,
                'name' => 'app_name',
                'val' => 'Frezka',
                'type' => 'general',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            2 =>
            array(
                'id' => 3,
                'name' => 'footer_text',
                'val' => 'Built with ♥ from <a href="https://iqonic.design" target="_blank">IQONIC DESIGN.</a>',
                'type' => 'general',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            3 =>
            array(
                'id' => 4,
                'name' => 'helpline_number',
                'val' => '1234567890',
                'type' => 'general',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            4 =>
            array(
                'id' => 5,
                'name' => 'copyright_text',
                'val' => 'Copyright',
                'type' => 'general',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            5 =>
            array(
                'id' => 6,
                'name' => 'ui_text',
                'val' => 'UI Powered By <a href="https://hopeui.iqonic.design/" target="_blank">HOPE UI</a>',
                'type' => 'general',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            6 =>
            array(
                'id' => 7,
                'name' => 'inquriy_email',
                'val' => 'frezka@admin.com',
                'type' => 'general',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            7 =>
            array(
                'id' => 8,
                'name' => 'site_description',
                'val' => 'Discover effortless beauty bookings with expert stylists, exclusive offers, and a seamless experience — all from one smart platform.',
                'type' => 'general',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            8 =>
            array(
                'id' => 9,
                'name' => 'bussiness_address_line_1',
                'val' => '140 Coxs Rd',
                'type' => 'string',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            9 =>
            array(
                'id' => 10,
                'name' => 'bussiness_address_line_2',
                'val' => 'North Ryde, NSW 2113',
                'type' => 'string',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            10 =>
            array(
                'id' => 11,
                'name' => 'bussiness_address_country',
                'val' => 'Australia',
                'type' => 'string',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            11 =>
            array(
                'id' => 12,
                'name' => 'bussiness_address_state',
                'val' => 'New South Wales',
                'type' => 'string',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            12 =>
            array(
                'id' => 13,
                'name' => 'bussiness_address_city',
                'val' => 'Sydney',
                'type' => 'string',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            13 =>
            array(
                'id' => 14,
                'name' => 'bussiness_address_postal_code',
                'val' => '2230',
                'type' => 'string',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            14 =>
            array(
                'id' => 15,
                'name' => 'bussiness_address_latitude',
                'val' => '-34.051158',
                'type' => 'string',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            15 =>
            array(
                'id' => 16,
                'name' => 'bussiness_address_longitude',
                'val' => '151.154119',
                'type' => 'string',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:14:49',
                'updated_at' => '2025-08-21 07:14:49',
                'deleted_at' => NULL,
            ),
            16 =>
            array(
                'id' => 17,
                'name' => 'is_google_login',
                'val' => '0',
                'type' => 'integaration',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:16:08',
                'updated_at' => '2025-08-21 07:16:08',
                'deleted_at' => NULL,
            ),
            17 =>
            array(
                'id' => 18,
                'name' => 'isForceUpdate',
                'val' => '0',
                'type' => 'integaration',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:16:08',
                'updated_at' => '2025-08-21 07:16:08',
                'deleted_at' => NULL,
            ),
            18 =>
            array(
                'id' => 19,
                'name' => 'is_custom_webhook_notification',
                'val' => '0',
                'type' => 'integaration',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:16:08',
                'updated_at' => '2025-08-21 07:16:08',
                'deleted_at' => NULL,
            ),
            19 =>
            array(
                'id' => 20,
                'name' => 'firebase_notification',
                'val' => '0',
                'type' => 'integaration',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:16:08',
                'updated_at' => '2025-08-21 07:16:08',
                'deleted_at' => NULL,
            ),
            20 =>
            array(
                'id' => 21,
                'name' => 'is_map_key',
                'val' => '0',
                'type' => 'integaration',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:16:08',
                'updated_at' => '2025-08-21 07:16:08',
                'deleted_at' => NULL,
            ),
            21 =>
            array(
                'id' => 22,
                'name' => 'is_application_link',
                'val' => '1',
                'type' => 'integaration',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:16:08',
                'updated_at' => '2025-08-21 07:16:08',
                'deleted_at' => NULL,
            ),
            22 =>
            array(
                'id' => 23,
                'name' => 'firebase_project_id',
                'val' => 'frezka-app',
                'type' => 'firebase_notification',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:16:08',
                'updated_at' => '2025-08-21 07:16:08',
                'deleted_at' => NULL,
            ),
            23 =>
            array(
                'id' => 24,
                'name' => 'customer_app_play_store',
                'val' => 'https://play.google.com/store/apps/details?id=com.frezka.customer&hl=en_IN&pli=1',
                'type' => 'is_application_link',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:16:08',
                'updated_at' => '2025-08-21 07:16:08',
                'deleted_at' => NULL,
            ),
            24 =>
            array(
                'id' => 25,
                'name' => 'customer_app_app_store',
                'val' => 'https://apps.apple.com/us/app/frezka-beauty-salons/id6450424262',
                'type' => 'is_application_link',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => NULL,
                'created_at' => '2025-08-21 07:16:08',
                'updated_at' => '2025-08-21 07:16:08',
                'deleted_at' => NULL,
            ),
        ));
    }
}
