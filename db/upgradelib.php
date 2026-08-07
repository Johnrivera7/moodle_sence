<?php
defined('MOODLE_INTERNAL') || die();

/**
 * @package    block_moodle_sence
 * @copyright  2026 John Rivera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Crea o actualiza mdl_block_sence sin duplicar si ya existe.
 */
function xmldb_block_moodle_sence_ensure_schema(): void {
    global $DB;

    $dbman = $DB->get_manager();
    $table = new xmldb_table('block_sence');

    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('runalumno', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('codcurso', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('idaccion', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('idsesionalumno', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('idsesionsence', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('firstacess', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table);
        return;
    }

    $fields = [
        ['codcurso', XMLDB_TYPE_CHAR, '100', 'runalumno'],
        ['idaccion', XMLDB_TYPE_CHAR, '100', 'codcurso'],
        ['idsesionalumno', XMLDB_TYPE_CHAR, '100', 'idaccion'],
        ['idsesionsence', XMLDB_TYPE_CHAR, '100', 'idsesionalumno'],
        ['firstacess', XMLDB_TYPE_INTEGER, '10', 'idsesionsence'],
    ];
    foreach ($fields as $f) {
        $field = new xmldb_field($f[0], $f[1], $f[2], null, null, null, null, $f[3]);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }
}

/**
 * Tabla auxiliar del plugin (install.xml).
 */
function xmldb_block_moodle_sence_ensure_meta_table(): void {
    global $DB;
    $dbman = $DB->get_manager();
    $table = new xmldb_table('block_moodle_sence_meta');
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table);
    }
}

/**
 * Migra ajustes globales desde el component block_sence si existen en config_plugins.
 */
function xmldb_block_moodle_sence_migrate_legacy_config(): void {
    global $DB;

    $legacy = $DB->get_records('config_plugins', ['plugin' => 'block_sence']);
    foreach ($legacy as $row) {
        if ($DB->record_exists('config_plugins', ['plugin' => 'block_moodle_sence', 'name' => $row->name])) {
            continue;
        }
        set_config($row->name, $row->value, 'block_moodle_sence');
    }
}
