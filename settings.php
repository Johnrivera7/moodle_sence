<?php
defined('MOODLE_INTERNAL') || die();

/**
 * @package    block_moodle_sence
 * @copyright  2026 John Rivera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'block_moodle_sence/heading',
        get_string('settingsheading', 'block_moodle_sence'),
        get_string('settingsheading_desc', 'block_moodle_sence')
    ));

    $settings->add(new admin_setting_configtext(
        'block_moodle_sence/rutotec',
        get_string('rutotec', 'block_moodle_sence'),
        get_string('rutotec_desc', 'block_moodle_sence'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'block_moodle_sence/tokenotec',
        get_string('tokenotec', 'block_moodle_sence'),
        get_string('tokenotec_desc', 'block_moodle_sence'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_moodle_sence/testmode',
        get_string('testmode', 'block_moodle_sence'),
        get_string('testmode_desc', 'block_moodle_sence'),
        0
    ));

    $settings->add(new admin_setting_heading(
        'block_moodle_sence/heading_session',
        get_string('settingsheading_session', 'block_moodle_sence'),
        get_string('settingsheading_session_desc', 'block_moodle_sence')
    ));

    $settings->add(new admin_setting_configduration(
        'block_moodle_sence/defaultsencetimeout',
        get_string('defaultsencetimeout', 'block_moodle_sence'),
        get_string('defaultsencetimeout_desc', 'block_moodle_sence'),
        10800
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_moodle_sence/alertacierrecron',
        get_string('alertacierrecron', 'block_moodle_sence'),
        get_string('alertacierrecron_desc', 'block_moodle_sence'),
        1
    ));

    $settings->add(new admin_setting_configduration(
        'block_moodle_sence/alertacierreminutos',
        get_string('alertacierreminutos', 'block_moodle_sence'),
        get_string('alertacierreminutos_desc', 'block_moodle_sence'),
        900
    ));
}
