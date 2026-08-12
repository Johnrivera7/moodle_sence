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
        $table->add_field('timeend', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('closealertsent', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
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
        ['timeend', XMLDB_TYPE_INTEGER, '10', 'firstacess'],
        ['closealertsent', XMLDB_TYPE_INTEGER, '10', 'timeend'],
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
 * Log de errores RCE para el reporte por curso.
 */
function xmldb_block_moodle_sence_ensure_log_table(): void {
    global $DB;
    $dbman = $DB->get_manager();
    $table = new xmldb_table('block_moodle_sence_log');
    if ($dbman->table_exists($table)) {
        return;
    }

    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('runalumno', XMLDB_TYPE_CHAR, '20', null, null, null, null);
    $table->add_field('codsence', XMLDB_TYPE_CHAR, '50', null, null, null, null);
    $table->add_field('codcurso', XMLDB_TYPE_CHAR, '100', null, null, null, null);
    $table->add_field('idaccion', XMLDB_TYPE_CHAR, '100', null, null, null, null);
    $table->add_field('glosa', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('idsesionalumno', XMLDB_TYPE_CHAR, '100', null, null, null, null);
    $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'error');
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('rawdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_index('courseid_status', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'status']);
    $table->add_index('userid_courseid', XMLDB_INDEX_NOTUNIQUE, ['userid', 'courseid']);
    $dbman->create_table($table);
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

/**
 * Crea (o asegura) el campo de perfil shortname=rut → profile_field_rut, único por usuario.
 */
function xmldb_block_moodle_sence_ensure_profile_field_rut(): void {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/user/profile/lib.php');

    $existing = $DB->get_record('user_info_field', ['shortname' => 'rut']);
    if ($existing) {
        $changed = false;
        if (empty($existing->forceunique)) {
            $existing->forceunique = 1;
            $changed = true;
        }
        if ((string) $existing->visible !== (string) PROFILE_VISIBLE_ALL) {
            $existing->visible = PROFILE_VISIBLE_ALL;
            $changed = true;
        }
        if ($changed) {
            $DB->update_record('user_info_field', $existing);
        }
        return;
    }

    $categoryid = (int) $DB->get_field_sql('SELECT MIN(id) FROM {user_info_category}');
    if ($categoryid <= 0) {
        $categoryid = (int) $DB->insert_record('user_info_category', (object) [
            'name' => 'SENCE',
            'sortorder' => 1,
        ]);
    }

    $sortorder = (int) $DB->get_field_sql(
        'SELECT COALESCE(MAX(sortorder), 0) + 1 FROM {user_info_field} WHERE categoryid = ?',
        [$categoryid]
    );

    $DB->insert_record('user_info_field', (object) [
        'shortname' => 'rut',
        'name' => 'RUT',
        'datatype' => 'text',
        'description' => 'RUT chileno del alumno para integración SENCE (formato 12345678-9).',
        'descriptionformat' => FORMAT_HTML,
        'categoryid' => $categoryid,
        'sortorder' => $sortorder,
        'required' => 0,
        'locked' => 0,
        'visible' => PROFILE_VISIBLE_ALL,
        'forceunique' => 1,
        'signup' => 0,
        'defaultdata' => '',
        'defaultdataformat' => FORMAT_HTML,
        'param1' => '30',
        'param2' => '12',
        'param3' => '0',
        'param4' => '',
        'param5' => '',
    ]);
}
