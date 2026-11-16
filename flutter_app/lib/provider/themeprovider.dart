import 'package:flutter/material.dart';
import '../model/generalsettingmodel.dart' as settings;
import '../utils/sharedpre.dart';

class AppThemeManager {
  AppThemeManager._();
  static final AppThemeManager instance = AppThemeManager._();

  static const String keyPreset = 'theme_preset';
  static const String keyPrimary = 'theme_primary_color';
  static const String keyPrimaryDark = 'theme_primary_dark_color';
  static const String keyAccent = 'theme_accent_color';
  static const String keyBg = 'theme_bg_color';
  static const String keySurface = 'theme_surface_color';
  static const String keyPanelBg = 'theme_panel_bg_color';
  static const String keyTitleText = 'theme_title_text_color';
  static const String keyDescText = 'theme_desc_text_color';
  static const String keyBtnText = 'theme_btn_text_color';

  static const Color defaultPrimary = Color(0xffBAFA34);
  static const Color defaultPrimaryDark = Color(0xff6EBE6B);
  static const Color defaultAccent = Color(0xffEC2B4E);
  static const Color defaultBg = Color(0xFF000000);
  static const Color defaultSurface = Color(0xFF1F2229);
  static const Color defaultPanelBg = Color(0xFF0E0E16);
  static const Color defaultTitleText = Color(0xffffffff);
  static const Color defaultDescText = Color(0xffa9aaac);
  static const Color defaultBtnText = Color(0xff000000);

  Color primaryColor = defaultPrimary;
  Color primaryDarkColor = defaultPrimaryDark;
  Color accentColor = defaultAccent;
  Color bgColor = defaultBg;
  Color surfaceColor = defaultSurface;
  Color panelBgColor = defaultPanelBg;
  Color titleColor = defaultTitleText;
  Color descColor = defaultDescText;
  Color btnTextColor = defaultBtnText;

  bool get isLightTheme =>
      ThemeData.estimateBrightnessForColor(bgColor) == Brightness.light;

  Color get shimmerBaseColor =>
      isLightTheme ? const Color(0xFFE2E8F0) : const Color(0xff454545);

  Color get shimmerBoxColor =>
      isLightTheme ? const Color(0xFFF1F5F9) : const Color(0xff0c0c0d);

  Color get cardLightBg =>
      isLightTheme ? const Color(0xFFE9EEF6) : const Color(0xff171A1B);

  Color get iconMutedColor =>
      isLightTheme ? const Color(0xFF64748B) : const Color(0xff676767);

  static Color parseHexColor(String? raw, Color fallback) {
    if (raw == null) return fallback;
    String hex = raw.trim();
    if (hex.isEmpty) return fallback;
    hex = hex.replaceAll('#', '');
    if (hex.startsWith('0x') || hex.startsWith('0X')) {
      hex = hex.substring(2);
    }
    if (hex.length == 6) {
      hex = 'FF$hex';
    }
    if (hex.length != 8) return fallback;
    final parsed = int.tryParse(hex, radix: 16);
    if (parsed == null) return fallback;
    return Color(parsed);
  }

  Future<void> loadFromSharedPre(SharedPre sharedPre) async {
    final map = <String, String?>{
      keyPrimary: await sharedPre.read(keyPrimary),
      keyPrimaryDark: await sharedPre.read(keyPrimaryDark),
      keyAccent: await sharedPre.read(keyAccent),
      keyBg: await sharedPre.read(keyBg),
      keySurface: await sharedPre.read(keySurface),
      keyPanelBg: await sharedPre.read(keyPanelBg),
      keyTitleText: await sharedPre.read(keyTitleText),
      keyDescText: await sharedPre.read(keyDescText),
      keyBtnText: await sharedPre.read(keyBtnText),
    };
    applyFromMap(map);
  }

  bool applyFromMap(Map<String, String?> map) {
    final nextPrimary = parseHexColor(map[keyPrimary], defaultPrimary);
    final nextPrimaryDark = parseHexColor(
      map[keyPrimaryDark],
      defaultPrimaryDark,
    );
    final nextAccent = parseHexColor(map[keyAccent], defaultAccent);
    final nextBg = parseHexColor(map[keyBg], defaultBg);
    final nextSurface = parseHexColor(map[keySurface], defaultSurface);
    final nextPanelBg = parseHexColor(map[keyPanelBg], defaultPanelBg);
    final nextTitle = parseHexColor(map[keyTitleText], defaultTitleText);
    final nextDesc = parseHexColor(map[keyDescText], defaultDescText);
    final nextBtnText = parseHexColor(map[keyBtnText], defaultBtnText);

    final changed =
        primaryColor != nextPrimary ||
        primaryDarkColor != nextPrimaryDark ||
        accentColor != nextAccent ||
        bgColor != nextBg ||
        surfaceColor != nextSurface ||
        panelBgColor != nextPanelBg ||
        titleColor != nextTitle ||
        descColor != nextDesc ||
        btnTextColor != nextBtnText;

    primaryColor = nextPrimary;
    primaryDarkColor = nextPrimaryDark;
    accentColor = nextAccent;
    bgColor = nextBg;
    surfaceColor = nextSurface;
    panelBgColor = nextPanelBg;
    titleColor = nextTitle;
    descColor = nextDesc;
    btnTextColor = nextBtnText;

    return changed;
  }
}

class ThemeProvider extends ChangeNotifier {
  ThemeProvider._();
  static final ThemeProvider instance = ThemeProvider._();

  int _revision = 0;
  int get revision => _revision;

  void applyFromGeneralSettings(List<settings.Result> items) {
    final map = <String, String?>{};
    for (final item in items) {
      final k = item.key?.toString();
      if (k != null && k.startsWith('theme_')) {
        map[k] = item.value?.toString();
      }
    }
    if (map.isEmpty) return;
    final changed = AppThemeManager.instance.applyFromMap(map);
    if (changed) {
      _revision++;
      notifyListeners();
    }
  }
}
