<?php

namespace Modules\Page\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Page\Models\Page;

class PageDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Page::updateOrCreate(
            [
                'slug' => 'privacy-policy',
            ],
            [
                'status' => 1,
                'name' => 'Privacy Policy',
                'description' => 'Privacy Policy

Your privacy is important to us. This Privacy Policy outlines how we collect, use, disclose, and safeguard your information when you use our application and services. By accessing or using the app, you agree to the terms described in this policy.

We may collect various types of information to enhance your experience and ensure the proper functioning of our services. This includes personal information such as your full name, email address, phone number, gender (optional), and profile photo (optional). Additionally, we may collect booking-related details such as appointment date and time, selected services, and payment method and status. To improve app performance and ensure security, we also gather device and usage information, including your IP address, device type and operating system version, app usage statistics, and your location (only if you grant permission).

The information we collect is used to manage your appointments and profile, send confirmation and reminder notifications, provide promotional updates, process payments, enhance the overall app experience, ensure security, and comply with applicable legal obligations. We do not sell or rent your personal information. However, we may share your data with trusted service providers who assist us with hosting, payments, analytics, and customer support. We may also share relevant booking details with branch staff, such as beauticians, for managing your appointments. In cases where disclosure is legally required, we may provide your information to law enforcement or regulatory authorities.

To protect your information, we implement appropriate technical and organizational security measures. While we strive to ensure your data is safe, please note that no digital platform can guarantee complete security. We encourage you to keep your login credentials confidential and secure.

As a user, you have certain rights regarding your personal data. These include the ability to access, update, or delete your profile, withdraw your consent (which may affect your ability to use certain services), and request a copy of your data in a portable format.

Our services are not intended for children under the age of 13, and we do not knowingly collect personal data from minors. If we become aware that a child has provided us with personal information, we will take steps to delete such data.

We may update this Privacy Policy periodically to reflect changes in our practices or applicable laws. When we do, the updated version will be posted within the app or on our website, along with the revised "Effective Date."',
            
            'sequence' => 6,
        ]
        );

        Page::updateOrCreate([
            'slug' => 'terms-and-conditions',
        ], [
            'status' => 1,
            'name' => 'Terms & Conditions',
            'description' => '<p>By downloading, installing, or using our application, you acknowledge that you have read, understood, and agreed to be bound by these Terms and Conditions. If you do not agree to these terms, please do not access or use the app or its services.

Our app provides a digital platform that allows users to book salon and spa services, manage appointments, and make payments. We function solely as a facilitator, connecting users with service providers, and do not directly offer the services listed on the platform.

To use certain features, such as booking appointments, users must register and maintain an account. You agree to provide accurate and up-to-date information and are responsible for keeping your login credentials secure. Any activities conducted through your account will be considered your responsibility.

Appointments can be scheduled through the app with confirmation of selected time slots. Payments can be made using the in-app wallet or other supported methods. Any cancellations or rescheduling must adhere to the specific salon or spa’s policy, which will be visible during the booking process.

As a user, you agree not to misuse the app in any way, including participating in fraudulent or illegal activities, posting offensive or misleading content, or attempting to hack or disrupt the service. Any such violations may result in the suspension or permanent termination of your account.

While we facilitate connections between users and service providers, we are not responsible for the quality, timing, or outcome of services rendered by salons or spas. Any dissatisfaction, damage, or delays are the sole responsibility of the service providers.

Cancellation and refund policies vary by provider. Users must carefully review and agree to these terms before confirming their booking. Refunds, if applicable, are handled according to the provider’s individual policy.

All content within the app, including but not limited to text, images, branding, and software, is either owned by us or licensed to us. You may not copy, reproduce, modify, or distribute any content from the app without our prior written consent.

We are not liable for any issues or damages resulting from service provider performance, delays, or the use—or inability to use—the app. This includes indirect, incidental, or consequential damages. Your use of the app is entirely at your own risk.

We reserve the right to modify or update these Terms and Conditions at any time. Any changes will be communicated through the app or on our website. Continued use of the app following any updates constitutes your acceptance of the revised terms..</p>',
            'sequence' => 4,
]);

        Page::updateOrCreate([
            'slug' => 'about-us',
        ], [
            'status' => 1,
            'name' => 'About Us',
            'description' => '<p>At Frezka, we believe self-care should be simple, accessible, and enjoyable. That’s why we’ve created a powerful Flutter-based solution that brings together customers, salon professionals, and spa businesses into one beautifully designed platform. Whether youre booking a quick haircut, a relaxing massage, or a complete makeover, Frezka ensures a smooth journey from scheduling to service.Our mission is to digitally transform the beauty and wellness industry by offering modern tools for salons to grow, and effortless booking for users who value their time and convenience.</p>',
            'sequence' => 1,
        ]);


        Page::updateOrCreate([
            'slug' => 'help',
        ], [
            'status' => 1,
            'name' => 'Help & Support',
            'description' => '<p>We’re here to assist you with any questions, concerns, or issues you may encounter while using our app or services. If you need help with bookings, payments, account settings, or technical difficulties, please don’t hesitate to reach out to our support team. You can contact us directly through the app’s support section, send an email to frezka-inquries@gmail.com , or visit our website for additional resources and FAQs. We strive to respond to all queries promptly and ensure you have a smooth and satisfying experience..</p>',
            'sequence' => 2,
        ]);

        Page::updateOrCreate([
            'slug' => 'refund',
        ], [
            'status' => 1,
            'name' => 'Refund Policy',
            'description' => '<p>Your refund policy content goes here.</p>',
            'sequence' => 3,
        ]);

        Page::updateOrCreate([
            'slug' => 'data-deletion-request',
        ], [
            'status' => 1,
            'name' => 'Data Deletion Request',
            'description' => '<p>To ensure the safety and privacy of your data, we offer a streamlined process for requesting its removal from our servers. To initiate the deletion process, kindly send an email from your registered email address to our dedicated email inbox at hello@iqonic.design. Upon receiving your request, our team will thoroughly examine the provided details and proceed with necessary actions in adherence to our privacy policies and legal obligations.</p>',
            'sequence' => 5,
        ]);

        $pages = [
            

            // [
            //     'name' => 'Term & Condition',
            //     'sequence' => 2,
            //     'description' => 'Term & Condition',
            // ],
        ];
        if (env('IS_DUMMY_DATA')) {
            foreach ($pages as $key => $pages_data) {
                $pages = Page::create($pages_data);
            }
        }
    }
}
