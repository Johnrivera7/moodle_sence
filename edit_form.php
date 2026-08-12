<?php
defined('MOODLE_INTERNAL') || die();

/**
 * @package    block_moodle_sence
 * @copyright  2026 John Rivera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_moodle_sence_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        $lineasdecap = [
            3 => get_string('linea3', 'block_moodle_sence'),
            1 => get_string('linea1', 'block_moodle_sence'),
            6 => get_string('linea6', 'block_moodle_sence'),
        ];
        $mform->addElement('select', 'config_lineasdecap', get_string('lineasdecap', 'block_moodle_sence'), $lineasdecap);
        $mform->setDefault('config_lineasdecap', 3);

        $mform->addElement('text', 'config_codigocurso', get_string('codigocurso', 'block_moodle_sence'), ['size' => 50]);
        $mform->setType('config_codigocurso', PARAM_TEXT);
        $mform->addHelpButton('config_codigocurso', 'codigocurso', 'block_moodle_sence');

        $mform->addElement('text', 'config_grupobecas', get_string('grupobecas', 'block_moodle_sence'), ['size' => 40]);
        $mform->setType('config_grupobecas', PARAM_TEXT);

        $mform->addElement('text', 'config_correoalerta', get_string('correoalerta', 'block_moodle_sence'), ['size' => 60]);
        $mform->setType('config_correoalerta', PARAM_TEXT);
        $mform->addHelpButton('config_correoalerta', 'correoalerta', 'block_moodle_sence');

        $mform->addElement('text', 'config_correorecordatorio', get_string('correorecordatorio', 'block_moodle_sence'), ['size' => 60]);
        $mform->setType('config_correorecordatorio', PARAM_TEXT);
        $mform->addHelpButton('config_correorecordatorio', 'correorecordatorio', 'block_moodle_sence');

        $mform->addElement('advcheckbox', 'config_forzarcierre', get_string('forzarcierre', 'block_moodle_sence'));
        $mform->setDefault('config_forzarcierre', 1);

        $mform->addElement('duration', 'config_sencetimeout', get_string('sesionmax', 'block_moodle_sence'));
        $mform->setDefault('config_sencetimeout', (int) (get_config('block_moodle_sence', 'defaultsencetimeout') ?: 10800));
        $mform->addHelpButton('config_sencetimeout', 'sesionmax', 'block_moodle_sence');

        $mform->addElement('textarea', 'config_alertafinal', get_string('alertafinal', 'block_moodle_sence'), 'wrap="virtual" rows="3" cols="50"');
        $mform->setDefault('config_alertafinal', get_string('defaultalert', 'block_moodle_sence'));
        $mform->setType('config_alertafinal', PARAM_TEXT);
        $mform->addHelpButton('config_alertafinal', 'alertafinal', 'block_moodle_sence');
    }
}
