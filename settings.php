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
}
