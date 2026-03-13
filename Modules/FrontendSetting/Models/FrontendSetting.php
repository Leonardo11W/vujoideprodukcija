<?php
namespace Modules\FrontendSetting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrontendSetting extends Model
{
    use HasFactory;

    protected $table = 'frontend_settings';

    protected $fillable = [
        'type',
        'key',
        'page', // ✅ Required to avoid mass-assignment error
        'status',
        'value',
    ];

    protected $casts = [
        'value' => 'array', // ✅ Decodes JSON on access
    ];

    /**
     * Get decoded setting value by key.
     */
    public static function getValueByKey($key)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) return null;

        $value = $setting->value;

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : $value;
        }

        return $value;
    }

    /**
     * Get selected expert IDs for a given section key and page.
     */
    public static function getSectionExperts($key = 'section_7')
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) return [];

        $value = is_array($setting->value) ? $setting->value : json_decode($setting->value, true);

        if (!isset($value['status']) || (int)$value['status'] !== 1) {
            return [];
        }

        return $value['expert_id'] ?? [];
    }
}
