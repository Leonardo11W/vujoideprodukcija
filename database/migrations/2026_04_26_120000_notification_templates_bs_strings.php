<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bosanski tekstovi za push/database obavještenja (notification_subject + notification_template_detail).
 */
return new class extends Migration
{
    /**
     * @var array<string, array{notification_subject: string, notification_template_detail: string}>
     */
    private array $templatesBs = [
        'cancel_booking' => [
            'notification_subject' => 'Rezervacija otkazana',
            'notification_template_detail' => '<p>Vaša rezervacija #[[ id ]] je otkazana. Za dodatne informacije obratite se podršci.</p>',
        ],
        'new_booking' => [
            'notification_subject' => 'Nova rezervacija',
            'notification_template_detail' => '<p>Nova rezervacija: [[ user_name ]] — usluge: [[ service_name ]].</p>',
        ],
        'check_in_booking' => [
            'notification_subject' => 'Prijava na termin',
            'notification_template_detail' => '<p>Poštovani/a [[ user_name ]], potvrđena je prijava na termin #[[ id ]] ([[ booking_date ]] [[ booking_time ]]).</p>',
        ],
        'checkout_booking' => [
            'notification_subject' => 'Podsjetnik za odjavu',
            'notification_template_detail' => '<p>Hvala vam na posjeti. Molimo odjavite se do [[ check_out_time ]].</p>',
        ],
        'complete_booking' => [
            'notification_subject' => 'Termin završen',
            'notification_template_detail' => '<p>Vaš termin #[[ id ]] je uspješno završen. Hvala vam!</p>',
        ],
        'quick_booking' => [
            'notification_subject' => 'Brza rezervacija',
            'notification_template_detail' => '<p>Poštovani/a [[ user_name ]], vaša rezervacija je zabilježena za [[ booking_date ]] u [[ booking_time ]].</p>',
        ],
        'wallet_top_up' => [
            'notification_subject' => 'Novčanik dopunjen',
            'notification_template_detail' => '<p>Na novčanik je dodano [[ credit_debit_amount ]].</p>',
        ],
        'order_placed' => [
            'notification_subject' => 'Narudžba primljena',
            'notification_template_detail' => '<p>Vaša narudžba je uspješno poslana.</p>',
        ],
        'order_proccessing' => [
            'notification_subject' => 'Narudžba u obradi',
            'notification_template_detail' => '<p>Vaša narudžba se obrađuje.</p>',
        ],
        'order_delivered' => [
            'notification_subject' => 'Narudžba isporučena',
            'notification_template_detail' => '<p>Vaša narudžba je isporučena.</p>',
        ],
        'order_cancelled' => [
            'notification_subject' => 'Narudžba otkazana',
            'notification_template_detail' => '<p>Vaša narudžba je otkazana.</p>',
        ],
        'package_expiry' => [
            'notification_subject' => 'Paket ističe',
            'notification_template_detail' => '<p>Podsjetnik: paket [[ package_name ]] ističe [[ package_expiry_date ]].</p>',
        ],
        'change_password' => [
            'notification_subject' => 'Lozinka promijenjena',
            'notification_template_detail' => '<p>Vaša lozinka je nedavno promijenjena. Ako niste vi, kontaktirajte podršku.</p>',
        ],
        'forget_email_password' => [
            'notification_subject' => 'Reset lozinke',
            'notification_template_detail' => '<p>Zatražili ste reset lozinke. Slijedite upute na [[ link ]].</p>',
        ],
    ];

    public function up(): void
    {
        foreach ($this->templatesBs as $type => $columns) {
            $templateId = DB::table('notification_templates')
                ->where('type', $type)
                ->whereNull('deleted_at')
                ->value('id');

            if (! $templateId) {
                continue;
            }

            DB::table('notification_template_content_mapping')
                ->where('template_id', $templateId)
                ->whereNull('deleted_at')
                ->update([
                    'notification_subject' => $columns['notification_subject'],
                    'notification_template_detail' => $columns['notification_template_detail'],
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Tekstovi predložaka su namjerno ne-vraćeni (jednosmjerna lokalizacija).
    }
};
