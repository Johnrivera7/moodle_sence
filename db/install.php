<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

function xmldb_block_moodle_sence_install() {
    xmldb_block_moodle_sence_ensure_meta_table();
    xmldb_block_moodle_sence_ensure_schema();
    xmldb_block_moodle_sence_migrate_legacy_config();
    return true;
}
