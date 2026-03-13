<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class LanguageController extends Controller
{
     public function switch($language)
    {
        app()->setLocale($language);

        session()->put('locale', $language);

        if ($language === 'ar' || $language === 'fa' || $language === 'ur') {
            session()->put('dir', 'rtl');
        } else {
            session()->put('dir', 'ltr');
        }

        setlocale(LC_TIME, $language);

        Carbon::setLocale($language);

        flash()->success(__('Language changed to').' '.strtoupper($language))->important();

        return redirect()->back();
    }
}
