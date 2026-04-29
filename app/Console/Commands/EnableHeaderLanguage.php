<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\FrontendSetting\Models\FrontendSetting;

class EnableHeaderLanguage extends Command
{
    protected $signature = 'frontend:enable-language';
    protected $description = 'Enable language dropdown in header and clear config cache';

    public function handle(): int
    {
        $setting = FrontendSetting::where('type', 'header-menu-setting')
            ->where('key', 'header-menu-setting')
            ->first();

        if ($setting && $setting->value) {
            $decoded = $setting->value;
            while (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            if (is_array($decoded)) {
                $decoded['enable_language'] = 1;
                $setting->value = json_encode($decoded);
                $setting->save();
                $this->info('Language dropdown enabled in header settings.');
            }
        } else {
            $this->warn('Header menu setting not found. Language dropdown will show by default.');
        }

        $this->call('config:clear');
        $this->call('cache:clear');
        $this->info('Config and cache cleared.');

        return self::SUCCESS;
    }
}
