<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'block_moodle_sence\task\notify_open_sessions',
        'blocking' => 0,
        'minute' => '*/15',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
