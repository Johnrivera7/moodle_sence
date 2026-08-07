<?php
namespace block_moodle_sence;

defined('MOODLE_INTERNAL') || die();

class rut_helper {

    public static function format_run(string $raw): string {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (strpos($raw, '-') !== false) {
            return $raw;
        }
        $clean = preg_replace('/[^0-9kK]/', '', $raw);
        if (strlen($clean) < 2) {
            return $raw;
        }
        $dv = strtolower(substr($clean, -1));
        $num = substr($clean, 0, -1);
        return $num . '-' . $dv;
    }

    public static function run_body(string $formatted): string {
        return str_replace('-', '', $formatted);
    }
}
