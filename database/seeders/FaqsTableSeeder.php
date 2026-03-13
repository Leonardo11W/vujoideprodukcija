<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FaqsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('faqs')->delete();

        \DB::table('faqs')->insert(array (
            0 =>
            array (
                'id' => 1,
                'question' => 'What is the frezka ?',
                'answer' => 'Frezka operates on a subscription-based model, where salon owners can sign up, select a plan, and access a comprehensive dashboard to manage their services, staff, and customers efficiently.',
                'status' => 1,
                'created_at' => '2025-02-27 13:02:33',
                'updated_at' => '2025-02-27 13:02:33',
            ),
            1 =>
            array (
                'id' => 2,
                'question' => 'How does frezka Work?',
                'answer' => 'Frezka operates on a subscription-based model, where salon owners can sign up, select a plan, and access a comprehensive dashboard to manage their services, staff, and customers efficiently.',
                'status' => 1,
                'created_at' => '2025-02-27 13:03:04',
                'updated_at' => '2025-02-27 13:03:04',
            ),
            2 =>
            array (
                'id' => 3,
                'question' => 'What are the available subscription plans?',
                'answer' => 'Frezka offers multiple pricing tiers based on features and business size. You can also get discounts on annual plans.',
                'status' => 1,
                'created_at' => '2025-02-27 13:03:21',
                'updated_at' => '2025-02-27 13:03:21',
            ),
            3 =>
            array (
                'id' => 4,
                'question' => 'Is there a free trial available?',
                'answer' => 'Yes, Frezka offers a free trial so you can explore its features before subscribing.',
                'status' => 1,
                'created_at' => '2025-02-27 13:03:41',
                'updated_at' => '2025-02-27 13:03:41',
            ),
            4 =>
            array (
                'id' => 5,
                'question' => 'How secure is my data on Frezka?',
            'answer' => 'Frezka follows industry-standard encryption, role-based access control (RBAC) to ensure data security and privacy.',
                'status' => 1,
                'created_at' => '2025-02-27 13:03:57',
                'updated_at' => '2025-02-27 13:03:57',
            ),
            5 =>
            array (
                'id' => 6,
                'question' => 'Can I customize websites through Frezka?',
                'answer' => 'Absolutely! Frezka provides dynamic website settings where salons can customize branding, services, pricing, and appointment booking options.',
                'status' => 1,
                'created_at' => '2025-02-27 13:04:37',
                'updated_at' => '2025-02-27 13:04:37',
            ),
            // 6 =>
            // array (
            //     'id' => 7,
            //     'question' => 'Can I upgrade my subscription?',
            //     'answer' => 'Yes! You can upgrade or cancel your plan anytime from the admin panel.',
            //     'status' => 1,
            //     'created_at' => '2025-02-27 13:06:23',
            //     'updated_at' => '2025-02-27 13:06:23',
            // ),
        ));


    }
}
