<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Blog; // make sure this path is correct
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;

class BlogsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        // Optionally truncate the table before seeding
        Blog::truncate();

        $blogs = [
    [
        'auther_id'   => 1,
        'title'       => 'Must-Have Features in a Modern Salon Booking System',
        'status'      => 1,
        'image'       => 'blog_img3919561751543988modern.avif',
        'description' => '<p><strong>Your salon deserves a smarter booking system. Here\'s what to look for:</strong></p>
<p style="padding-left: 40px;">
🗓️ <strong>Real-time appointment scheduling</strong> for staff & customers<br>
🔔 <strong>Automated reminders</strong> to reduce no-shows<br>
📱 <strong>Mobile-friendly design</strong> to book anytime, anywhere<br>
💳 <strong>Integrated payment support</strong> for convenience<br>
📊 <strong>Analytics & reporting</strong> to track performance<br>
</p>
<p><strong>With Frezka, enjoy all these features in one easy platform!</strong></p>',
        'total_view'  => 0,
        'created_at'  => '2025-02-27 11:05:00',
        'updated_at'  => '2025-02-27 11:05:00',
    ],
    [
        'auther_id'   => 3,
        'title'       => 'Why Every Salon Needs a Digital Presence in 2025',
        'status'      => 1,
        'image'       => 'blog_img5577981751438641saloon.avif',
        'description' => '<p><strong>Having an online presence is no longer optional. Here\'s why it matters:</strong></p>
<p style="padding-left: 40px;">
🌐 <strong>Reach more customers</strong> through Google & social media<br>
🧑‍💻 <strong>24/7 booking access</strong> via your website or app<br>
⭐ <strong>Showcase reviews</strong> to build trust & credibility<br>
📸 <strong>Display your portfolio</strong> with a professional look<br>
💬 <strong>Stay connected</strong> with automated messages & updates<br>
</p>
<p><strong>Frezka helps you build a powerful digital presence in minutes!</strong></p>',
        'total_view'  => 0,
        'created_at'  => '2025-02-27 11:05:00',
        'updated_at'  => '2025-02-27 11:05:00',
    ],
    
    [
        'auther_id'   => 5,
        'title'       => 'How Multi-Branch Salons Can Simplify Operations with Frezka',
        'status'      => 1,
        'image'       => 'blog_img5636781751438546multibranch.avif',
        'description' => '<p><strong>Manage all your salon branches from one centralized system without the chaos of spreadsheets or manual records.</strong></p>
<p style="padding-left: 40px;">
🌐 <strong>Central Admin Control</strong> – Manage staff, settings, and services across all branches.<br>
📍 <strong>Branch-specific Reporting</strong> – Track each location’s performance separately.<br>
👥 <strong>Role-Based Access</strong> – Give branch managers the right permissions.<br>
🔄 <strong>Service Syncing</strong> – Keep consistent offerings across locations.<br>
📅 <strong>Cross-branch Booking Support</strong> – Let clients book from any nearby outlet<br>
</p>
<p><strong>Frezka brings structure to multi-location salon chains effortlessly.</strong></p>',
        'total_view'  => 0,
        'created_at'  => '2025-02-27 11:10:00',
        'updated_at'  => '2025-02-27 11:10:00',
    ],
    [
        'auther_id'   => 7,
        'title'       => 'From Manual to Magical: Automating Your Salon with Frezka',
        'status'      => 1,
        'image'       => 'blog_img1237311751438508digital.avif',
        'description' => '<p><strong>If you\'re still juggling pen and paper, here’s how automation can save you hours and scale your business effortlessly.</strong></p>
<p style="padding-left: 40px;">
⚙️ <strong>Automated Appointments & Reminders</strong><br>
🧾 <strong>Instant Billing & Invoicing</strong><br>
👤 <strong>CRM for Client Notes & History</strong><br>
🔄 <strong>Recurring Package Management</strong><br>
🧑‍🤝‍🧑 <strong>Staff Duty & Payroll Automation</strong><br>
</p>
<p><strong>Let Frezka do the heavy lifting while you focus on styling!</strong></p>',
        'total_view'  => 0,
        'created_at'  => '2025-02-27 11:10:00',
        'updated_at'  => '2025-02-27 11:10:00',
    ],
    [
        'auther_id'   => 4,
        'title'       => 'More Than Just Bookings: The Hidden Power of Frezka\'s Dashboard & Reports',
        'status'      => 1,
        'image'       => 'blog_img2280721751438590dashboard.avif',
        'description' => '<p><strong>Your salon generates a wealth of data – are you using it to your advantage?</strong></p>
<p><strong>Explore how Frezka\'s intuitive dashboards and comprehensive reports transform raw data into actionable insights:</strong></p>
<p style="padding-left: 40px;">
🚀 <strong>Spot Trends Early</strong> – Identify popular services, peak times, and client behavior patterns.<br>
🏆 <strong>Evaluate Staff Performance</strong> – Understand individual and team strengths to foster growth.<br>
💡 <strong>Optimize Marketing Spend</strong> – See which campaigns drive the most revenue.<br>
🧠 <strong>Make Informed Decisions</strong> – Move beyond guesswork with data-backed strategies for pricing, staffing, and promotions.<br>
</p>
<p><strong>Frezka’s reports turn data into growth strategies.</strong></p>',
        'total_view'  => 0,
        'created_at'  => '2025-02-27 11:10:00',
        'updated_at'  => '2025-02-27 11:10:00',
    ],
    [
        'auther_id'   => 5,
        'title'       => 'Why Reviews Matter: Build Salon Trust Online',
        'status'      => 1,
        'image'       => 'medium-shot-young-people-with-reviews.jpg',
        'description' => '<p><strong>Positive reviews attract more clients. Here’s how to leverage them:</strong></p>
<p style="padding-left: 40px;">
🌟 <strong>Ask happy customers to leave feedback</strong><br>
📩 <strong>Follow up with review requests via email/SMS</strong><br>
🗣️ <strong>Respond to all reviews—good or bad</strong><br>
🖼️ <strong>Highlight top reviews on your homepage</strong><br>
💡 <strong>Use Frezka to automate review collection</strong><br>
</p>
<p><strong>Build trust and grow your salon organically!</strong></p>',
        'total_view'  => 0,
        'created_at'  => '2025-02-27 11:05:00',
        'updated_at'  => '2025-02-27 11:05:00',
    ],


        ];
        $dummyImages = [
            'blog_img3919561751543988modern.avif',
            'blog_img5577981751438641saloon.avif',
            'blog_img5636781751438546multibranch.avif',
            'blog_img1237311751438508digital.avif',
            'blog_img2280721751438590dashboard.avif',
            'medium-shot-young-people-with-reviews.jpg',
        ];
        // Set destination folder for blog images
        $destinationFolder = public_path('blog/images/');
        if (!File::exists($destinationFolder)) {
            File::makeDirectory($destinationFolder, 0777, true);
        }
        if (env('IS_DUMMY_DATA')) {
            foreach ($blogs as $blogData) {

                // Define the source path where your dummy images are stored
                $sourceImage = public_path('blog/images/' . $blogData['image']);
                if (File::exists($sourceImage)) {
                    // Generate a unique image name similar to your controller
                    $img_name = 'blog_img' . rand(100000, 999999) . time() . '.' . pathinfo($sourceImage, PATHINFO_EXTENSION);
                    $destinationPath = $destinationFolder . $img_name;
                    File::copy($sourceImage, $destinationPath);
                    // Store relative path in the blog data
                    $blogData['image'] = 'blog/images/' . $img_name;
                } else {
                    $blogData['image'] = null;
                }
                $blog = Blog::create($blogData);
                $blog->image = $blogData['image'] ?? null;
                $blog->save();
            }
        }
    }
}
