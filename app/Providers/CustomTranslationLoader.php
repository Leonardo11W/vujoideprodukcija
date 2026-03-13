<?php

namespace App\Providers;

use Illuminate\Translation\FileLoader;
use Modules\Language\Models\Language;
use Illuminate\Support\Facades\Log;

class CustomTranslationLoader extends FileLoader
{
    public function load($locale, $group, $namespace = null)
    {
        try {
            $fileTranslations = parent::load($locale, $group, $namespace);

            // Ensure fileTranslations is an array
            if (!is_array($fileTranslations)) {
                Log::warning("File translations for locale: {$locale}, group: {$group} is not an array", [
                    'fileTranslations' => $fileTranslations,
                    'type' => gettype($fileTranslations)
                ]);
                $fileTranslations = [];
            }

            try {
                $languageQuery = Language::getAllLang();
                
                // Check if the query returned a valid collection
                if (!$languageQuery || !method_exists($languageQuery, 'where')) {
                    Log::warning("Language::getAllLang() did not return a valid collection", [
                        'result' => $languageQuery,
                        'type' => gettype($languageQuery)
                    ]);
                    $databaseTranslations = [];
                } else {
                    $databaseTranslations = $languageQuery->where('language', $locale)
                        ->where('file', $group)
                        ->pluck('value', 'key')
                        ->toArray();
                        
                    // Ensure databaseTranslations is an array
                    if (!is_array($databaseTranslations)) {
                        Log::warning("Database translations for locale: {$locale}, group: {$group} is not an array", [
                            'databaseTranslations' => $databaseTranslations,
                            'type' => gettype($databaseTranslations)
                        ]);
                        $databaseTranslations = [];
                    }
                }
            } catch (\Throwable $th) {
                Log::error("Error loading database translations for locale: {$locale}, group: {$group}", [
                    'error' => $th->getMessage(),
                    'trace' => $th->getTraceAsString()
                ]);
                $databaseTranslations = [];
            }

            return array_merge($fileTranslations, $databaseTranslations);
            
        } catch (\Throwable $th) {
            Log::error("Error in CustomTranslationLoader::load for locale: {$locale}, group: {$group}", [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            
            // Return empty array as fallback
            return [];
        }
    }
}
