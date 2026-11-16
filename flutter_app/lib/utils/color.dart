import 'package:flutter/material.dart';
import '../provider/themeprovider.dart';

Color get colorPrimary => AppThemeManager.instance.primaryColor;
Color get colorPrimaryDark => AppThemeManager.instance.primaryDarkColor;
Color get colorAccent => AppThemeManager.instance.accentColor;

Color get appBgColor => AppThemeManager.instance.bgColor;
Color get secondaryBgColor => AppThemeManager.instance.surfaceColor; //Visible color in appBgColor
Color get authPanelBgColor => AppThemeManager.instance.panelBgColor; // Auth page right panel background
const complimentryColor = Color(0xff792bec);

Color get titleTextColor => AppThemeManager.instance.titleColor;
Color get descTextColor => AppThemeManager.instance.descColor;
Color get onPrimaryBtnTextColor => AppThemeManager.instance.btnTextColor;
Color get defaultIconColor => AppThemeManager.instance.iconMutedColor;

const edtShadowColor = Color(0xff707070);
Color get secProgressColor => AppThemeManager.instance.descColor.withValues(alpha: 0.5);

const transparent = Color.fromRGBO(0, 0, 0, 0);
const white = Color(0xffffffff);
const black = Color(0xff000000);
Color get lightBlack => AppThemeManager.instance.cardLightBg;
const gray = Color(0xff777D73);
const grayDark = Color(0xff454545);

Color get shimmerColor => AppThemeManager.instance.shimmerBaseColor;
Color get shimmerItemColor => AppThemeManager.instance.shimmerBoxColor;

const successBG = Color(0xff007c60);
const warningBG = Color(0xffFFA500);
const infoBG = Color(0xff0096FF);
const failureBG = Color(0xffde323e);

const redColor = Color(0xffd6181b);
const greenColor = Color(0xff008000);
