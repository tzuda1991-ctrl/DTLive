<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\General_Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class ThemeSettingController extends Controller
{
    public static function defaults(): array
    {
        return [
            'theme_preset' => 'zulive_lime',
            'theme_primary_color' => '#BAFA34',
            'theme_primary_dark_color' => '#6EBE6B',
            'theme_accent_color' => '#EC2B4E',
            'theme_bg_color' => '#000000',
            'theme_surface_color' => '#1F2229',
            'theme_panel_bg_color' => '#0E0E16',
            'theme_title_text_color' => '#FFFFFF',
            'theme_desc_text_color' => '#A9AAAC',
            'theme_btn_text_color' => '#000000',
        ];
    }

    public static function presets(): array
    {
        return [
            'zulive_lime' => [
                'name' => 'ZuLive Neon Lime',
                'tagline' => 'Signature Dark + Electric Lime',
                'theme_primary_color' => '#BAFA34',
                'theme_primary_dark_color' => '#6EBE6B',
                'theme_accent_color' => '#EC2B4E',
                'theme_bg_color' => '#000000',
                'theme_surface_color' => '#1F2229',
                'theme_panel_bg_color' => '#0E0E16',
                'theme_title_text_color' => '#FFFFFF',
                'theme_desc_text_color' => '#A9AAAC',
                'theme_btn_text_color' => '#000000',
            ],
            'cinema_crimson' => [
                'name' => 'Cinema Crimson',
                'tagline' => 'Bold Theater Red + Obsidian',
                'theme_primary_color' => '#E50914',
                'theme_primary_dark_color' => '#B20710',
                'theme_accent_color' => '#FF6B35',
                'theme_bg_color' => '#0A0A0C',
                'theme_surface_color' => '#18181D',
                'theme_panel_bg_color' => '#121216',
                'theme_title_text_color' => '#FFFFFF',
                'theme_desc_text_color' => '#B3B3B8',
                'theme_btn_text_color' => '#FFFFFF',
            ],
            'ocean_sapphire' => [
                'name' => 'Ocean Sapphire',
                'tagline' => 'Deep Midnight + Stream Blue',
                'theme_primary_color' => '#1D9BF0',
                'theme_primary_dark_color' => '#0066CC',
                'theme_accent_color' => '#00D4AA',
                'theme_bg_color' => '#060B16',
                'theme_surface_color' => '#111C2E',
                'theme_panel_bg_color' => '#0B1322',
                'theme_title_text_color' => '#F8FAFC',
                'theme_desc_text_color' => '#94A3B8',
                'theme_btn_text_color' => '#FFFFFF',
            ],
            'royal_gold' => [
                'name' => 'Royal Gold',
                'tagline' => 'Luxury Gold + Espresso Dark',
                'theme_primary_color' => '#F5B81C',
                'theme_primary_dark_color' => '#D97706',
                'theme_accent_color' => '#EF4444',
                'theme_bg_color' => '#0B0907',
                'theme_surface_color' => '#1C1814',
                'theme_panel_bg_color' => '#14110E',
                'theme_title_text_color' => '#FFFDF7',
                'theme_desc_text_color' => '#B8AFA3',
                'theme_btn_text_color' => '#000000',
            ],
            'electric_violet' => [
                'name' => 'Electric Violet',
                'tagline' => 'Vibrant Purple + Indigo Neon',
                'theme_primary_color' => '#9333EA',
                'theme_primary_dark_color' => '#6366F1',
                'theme_accent_color' => '#EC4899',
                'theme_bg_color' => '#090712',
                'theme_surface_color' => '#161226',
                'theme_panel_bg_color' => '#100D1D',
                'theme_title_text_color' => '#F8F7FF',
                'theme_desc_text_color' => '#A7A0C2',
                'theme_btn_text_color' => '#FFFFFF',
            ],
            'emerald_cyber' => [
                'name' => 'Emerald Cyber',
                'tagline' => 'Mint Emerald + Deep Jade',
                'theme_primary_color' => '#10B981',
                'theme_primary_dark_color' => '#059669',
                'theme_accent_color' => '#F59E0B',
                'theme_bg_color' => '#040D0A',
                'theme_surface_color' => '#0F211B',
                'theme_panel_bg_color' => '#091712',
                'theme_title_text_color' => '#ECFDF5',
                'theme_desc_text_color' => '#86A89B',
                'theme_btn_text_color' => '#000000',
            ],
            'sunset_amber' => [
                'name' => 'Sunset Amber',
                'tagline' => 'Fiery Orange + Warm Charcoal',
                'theme_primary_color' => '#FF5E1A',
                'theme_primary_dark_color' => '#FF8C00',
                'theme_accent_color' => '#E11D48',
                'theme_bg_color' => '#0D0806',
                'theme_surface_color' => '#1F1512',
                'theme_panel_bg_color' => '#160F0D',
                'theme_title_text_color' => '#FFF7F5',
                'theme_desc_text_color' => '#BDA69F',
                'theme_btn_text_color' => '#FFFFFF',
            ],
            'clean_light' => [
                'name' => 'Clean Studio Light',
                'tagline' => 'Daylight White + Royal Blue',
                'theme_primary_color' => '#2563EB',
                'theme_primary_dark_color' => '#1D4ED8',
                'theme_accent_color' => '#E11D48',
                'theme_bg_color' => '#F4F6FA',
                'theme_surface_color' => '#FFFFFF',
                'theme_panel_bg_color' => '#E9EEF6',
                'theme_title_text_color' => '#111827',
                'theme_desc_text_color' => '#6B7280',
                'theme_btn_text_color' => '#FFFFFF',
            ],
        ];
    }

    public static function ensureDefaults(): void
    {
        foreach (self::defaults() as $key => $defaultValue) {
            $existing = General_Setting::where('key', $key)->first();
            if (!$existing) {
                General_Setting::create([
                    'key' => $key,
                    'value' => $defaultValue,
                ]);
            }
        }
    }

    public function index()
    {
        try {
            self::ensureDefaults();
            $settings = Setting_Data();
            $defaults = self::defaults();

            $current = [];
            foreach ($defaults as $key => $val) {
                $current[$key] = !empty($settings[$key]) ? $settings[$key] : $val;
            }

            return view('admin.theme_setting.index', [
                'current' => $current,
                'presets' => self::presets(),
                'defaults' => $defaults,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 400, 'errors' => $e->getMessage()]);
        }
    }

    public function save(Request $request)
    {
        try {
            $hexRule = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
            $validator = Validator::make($request->all(), [
                'theme_preset' => 'nullable|string|max:60',
                'theme_primary_color' => $hexRule,
                'theme_primary_dark_color' => $hexRule,
                'theme_accent_color' => $hexRule,
                'theme_bg_color' => $hexRule,
                'theme_surface_color' => $hexRule,
                'theme_panel_bg_color' => $hexRule,
                'theme_title_text_color' => $hexRule,
                'theme_desc_text_color' => $hexRule,
                'theme_btn_text_color' => $hexRule,
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 400, 'errors' => $validator->errors()->all()]);
            }

            $keys = array_keys(self::defaults());
            foreach ($keys as $key) {
                $val = trim((string) $request->input($key, self::defaults()[$key]));
                if ($key !== 'theme_preset') {
                    $val = strtoupper($val);
                }
                General_Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $val]
                );
            }

            // Keep HTML CMS page colors aligned with active theme
            General_Setting::updateOrCreate(
                ['key' => 'page_background_color'],
                ['value' => strtoupper(trim((string) $request->input('theme_bg_color')))]
            );
            General_Setting::updateOrCreate(
                ['key' => 'page_title_color'],
                ['value' => strtoupper(trim((string) $request->input('theme_title_text_color')))]
            );

            return response()->json([
                'status' => 200,
                'success' => 'App theme colors saved! Restart or reopen the mobile app to see the live theme.',
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 400, 'errors' => $e->getMessage()]);
        }
    }
}
