<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

function xmldb_block_moodle_sence_upgrade($oldversion) {
    if ($oldversion < 2026052800) {
        xmldb_block_moodle_sence_ensure_meta_table();
        xmldb_block_moodle_sence_ensure_schema();
        xmldb_block_moodle_sence_migrate_legacy_config();
        upgrade_block_savepoint(true, 2026052800, 'moodle_sence');
    }

    if ($oldversion < 2026080710) {
        xmldb_block_moodle_sence_ensure_profile_field_rut();
        upgrade_block_savepoint(true, 2026080710, 'moodle_sence');
    }

    if ($oldversion < 2026081010) {
        xmldb_block_moodle_sence_ensure_log_table();
        upgrade_block_savepoint(true, 2026081010, 'moodle_sence');
    }

    if ($oldversion < 2026081215) {
        xmldb_block_moodle_sence_ensure_schema();
        upgrade_block_savepoint(true, 2026081215, 'moodle_sence');
    }

    if ($oldversion < 2026081220) {
        // Campo timeend para historial por día (inicio/cierre/pendiente).
        xmldb_block_moodle_sence_ensure_schema();
        upgrade_block_savepoint(true, 2026081220, 'moodle_sence');
    }

    if ($oldversion < 2026081300) {
        // CodSence en el registro + IdSesion* a 255 (manual RCE largo 149).
        xmldb_block_moodle_sence_ensure_schema();
        upgrade_block_savepoint(true, 2026081300, 'moodle_sence');
    }

    if ($oldversion < 2026081301) {
        $current = get_config('block_moodle_sence', 'defaultsencetimeout');
        if ($current === false || $current === '' || (int) $current === 10800) {
            set_config('defaultsencetimeout', 7200, 'block_moodle_sence');
        }
        if (get_config('block_moodle_sence', 'sessionstaleafter') === false) {
            set_config('sessionstaleafter', 10800, 'block_moodle_sence');
        }
        upgrade_block_savepoint(true, 2026081301, 'moodle_sence');
    }

    return true;
}
