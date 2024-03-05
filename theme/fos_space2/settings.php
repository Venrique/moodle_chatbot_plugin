<?php
/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     theme_fos_space2
 * @copyright   2024 digital Mood  <findme@digital-mood.eu>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('theme_fos_space2_settings', new lang_string('pluginname', 'theme_fos_space2'));

    $name = 'theme_fos_space2/showlogintext';
    $title = get_string('showlogintext', 'theme_fos_space2');
    $description = get_string('showlogintext_desc', 'theme_fos_space2');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $settings->add($setting);

    $name = 'theme_fos_space2/logintext';
    $title = get_string('logintext', 'theme_fos_space2');
    $description = get_string('logintext_desc', 'theme_fos_space2');
    $setting = new admin_setting_configtext($name, $title, $description, PARAM_TEXT);
    $settings->add($setting);


    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // TODO: Define actual plugin settings page and add it to the tree - {@link https://docs.moodle.org/dev/Admin_settings}.
    }
}