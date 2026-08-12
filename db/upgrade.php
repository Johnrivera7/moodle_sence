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

    return true;
}
