<?php
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use block_moodle_sence\report_builder;

global $DB, $PAGE, $OUTPUT, $USER;

$courseid = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);
$filter = optional_param('status', 'all', PARAM_ALPHANUMEXT);
$download = optional_param('download', 0, PARAM_BOOL);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$userid = optional_param('userid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$course = get_course($courseid);
$context = context_course::instance($courseid);
require_login($course);
require_capability('block/moodle_sence:viewreport', $context);

$blockinstance = $DB->get_record('block_instances', [
    'id' => $instanceid,
    'blockname' => 'moodle_sence',
], '*', MUST_EXIST);
$block = block_instance('moodle_sence', $blockinstance);
$config = $block->config ?? new stdClass();

$reporturl = new moodle_url('/blocks/moodle_sence/report.php', [
    'courseid' => $courseid,
    'instanceid' => $instanceid,
]);
$courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);

$data = report_builder::build($courseid, $config);
$allrows = $data['rows'];
$summary = $data['summary'];
$rowsbyid = [];
foreach ($allrows as $r) {
    $rowsbyid[(int) $r->userid] = $r;
}

$rows = $allrows;
if ($filter !== 'all') {
    $rows = array_values(array_filter($rows, static function ($r) use ($filter) {
        return $r->status === $filter;
    }));
}

if ($download) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sence-reporte-curso-' . $courseid . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, [
        'Nombre', 'Email', 'RUT', 'Grupos', 'CodSence', 'CodigoCurso', 'ID Accion',
        'Estado', 'Acceso curso', 'Fecha acceso curso', 'Fecha SENCE', 'IdSesionSence',
        'Error glosa', 'Detalle error',
    ]);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r->fullname,
            $r->email,
            $r->run,
            $r->groups,
            $r->codsence,
            $r->codigocurso,
            $r->idaccion,
            $r->statuslabel,
            $r->courseaccessed ? 'si' : 'no',
            $r->timeaccess ? userdate($r->timeaccess) : '',
            $r->sencetime ? userdate($r->sencetime) : '',
            $r->idsesionsence,
            $r->errorglosa ?: '',
            $r->errortext,
        ]);
    }
    fclose($out);
    exit;
}

$PAGE->set_url($reporturl, $filter !== 'all' ? ['status' => $filter] : []);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('report_title', 'block_moodle_sence'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/blocks/moodle_sence/styles.css');

$backparams = $filter !== 'all' ? ['status' => $filter] : [];
$backurl = new moodle_url($reporturl, $backparams);

// Recordatorio por usuario + CC al emisor.
if ($action === 'remind') {
    require_sesskey();
    if ($userid <= 0 || empty($rowsbyid[$userid])) {
        redirect($backurl, get_string('reminder_user_missing', 'block_moodle_sence'), null,
            \core\output\notification::NOTIFY_ERROR);
    }
    $target = $rowsbyid[$userid];
    $allowed = array_flip(report_builder::reminder_statuses());
    if (!isset($allowed[$target->status])) {
        redirect($backurl, get_string('reminder_user_not_pending', 'block_moodle_sence'), null,
            \core\output\notification::NOTIFY_WARNING);
    }

    if (!$confirm) {
        echo $OUTPUT->header();
        $msg = get_string('reminder_confirm_user', 'block_moodle_sence', (object) [
            'fullname' => $target->fullname,
            'email' => $target->email,
            'status' => $target->statuslabel,
            'course' => format_string($course->fullname),
            'sender' => fullname($USER),
            'senderemail' => $USER->email,
            'cc' => !empty($config->correorecordatorio)
                ? implode(', ', block_moodle_sence_parse_alert_emails((string) $config->correorecordatorio))
                : get_string('reminder_cc_none', 'block_moodle_sence'),
        ]);
        $yesurl = new moodle_url($reporturl, [
            'status' => $filter,
            'action' => 'remind',
            'userid' => $userid,
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]);
        echo $OUTPUT->confirm($msg, $yesurl, $backurl);
        echo $OUTPUT->footer();
        exit;
    }

    $result = report_builder::send_reminder_user($course, $target, $USER, $config);
    if ($result === 'ok') {
        $cclist = block_moodle_sence_parse_alert_emails((string) ($config->correorecordatorio ?? ''));
        redirect($backurl, get_string('reminder_user_sent', 'block_moodle_sence', (object) [
            'fullname' => $target->fullname,
            'email' => $target->email,
            'sender' => fullname($USER),
            'cc' => !empty($cclist) ? implode(', ', $cclist) : get_string('reminder_cc_none', 'block_moodle_sence'),
        ]), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($result === 'skipped') {
        redirect($backurl, get_string('reminder_user_skipped', 'block_moodle_sence'), null,
            \core\output\notification::NOTIFY_WARNING);
    }
    redirect($backurl, get_string('reminder_user_failed', 'block_moodle_sence', $target->fullname), null,
        \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->header();

echo html_writer::start_div('block-moodle-sence-report');
echo html_writer::tag('h2', get_string('report_title', 'block_moodle_sence'));
echo html_writer::tag('p', get_string('report_intro', 'block_moodle_sence', (object) [
    'course' => format_string($course->fullname),
    'shortname' => $course->shortname,
    'url' => $courseurl->out(false),
]));

echo html_writer::start_div('block-moodle-sence-report__summary');
$cards = [
    'ok' => report_builder::STATUS_OK,
    'error' => report_builder::STATUS_ERROR,
    'course_no_sence' => report_builder::STATUS_COURSE_NO_SENCE,
    'never' => report_builder::STATUS_NEVER,
    'becado' => report_builder::STATUS_BECADO,
    'nogroup' => report_builder::STATUS_NOGROUP,
];
foreach ($cards as $css => $key) {
    $url = new moodle_url($reporturl, ['status' => $key]);
    $active = ($filter === $key) ? ' is-active' : '';
    echo html_writer::link($url, html_writer::div(
        html_writer::span((string) ($summary[$key] ?? 0), 'block-moodle-sence-report__count') .
        html_writer::span(get_string('report_status_' . $key, 'block_moodle_sence'), 'block-moodle-sence-report__label'),
        'block-moodle-sence-report__card block-moodle-sence-report__card--' . $css . $active
    ));
}
echo html_writer::end_div();

$filters = [
    'all' => get_string('report_filter_all', 'block_moodle_sence') . ' (' . $summary['total'] . ')',
    report_builder::STATUS_OK => get_string('report_status_ok', 'block_moodle_sence'),
    report_builder::STATUS_ERROR => get_string('report_status_error', 'block_moodle_sence'),
    report_builder::STATUS_COURSE_NO_SENCE => get_string('report_status_course_no_sence', 'block_moodle_sence'),
    report_builder::STATUS_NEVER => get_string('report_status_never', 'block_moodle_sence'),
    report_builder::STATUS_BECADO => get_string('report_status_becado', 'block_moodle_sence'),
    report_builder::STATUS_NOGROUP => get_string('report_status_nogroup', 'block_moodle_sence'),
];
echo html_writer::start_div('block-moodle-sence-report__toolbar');
echo html_writer::start_tag('nav', ['class' => 'block-moodle-sence-report__filters', 'aria-label' => 'Filtros']);
foreach ($filters as $key => $label) {
    $url = ($key === 'all')
        ? $reporturl
        : new moodle_url($reporturl, ['status' => $key]);
    $class = 'block-moodle-sence-report__filter' . ($filter === $key || ($key === 'all' && $filter === 'all') ? ' is-active' : '');
    echo html_writer::link($url, $label, ['class' => $class]);
}
echo html_writer::end_tag('nav');

$csvurl = new moodle_url($reporturl, ['status' => $filter, 'download' => 1]);
echo html_writer::link($csvurl, get_string('report_download_csv', 'block_moodle_sence'), [
    'class' => 'btn btn-secondary',
]);
echo html_writer::link($courseurl, get_string('backtocourse', 'block_moodle_sence'), [
    'class' => 'btn btn-primary',
]);
echo html_writer::end_div();

echo html_writer::tag('p', get_string('reminder_help', 'block_moodle_sence'), [
    'class' => 'block-moodle-sence-report__hint',
]);

$remindable = array_flip(report_builder::reminder_statuses());

$table = new html_table();
$table->attributes['class'] = 'generaltable block-moodle-sence-report__table';
$table->head = [
    get_string('report_col_name', 'block_moodle_sence'),
    get_string('report_col_run', 'block_moodle_sence'),
    get_string('report_col_groups', 'block_moodle_sence'),
    get_string('report_col_codes', 'block_moodle_sence'),
    get_string('report_col_status', 'block_moodle_sence'),
    get_string('report_col_courseaccess', 'block_moodle_sence'),
    get_string('report_col_sence', 'block_moodle_sence'),
    get_string('report_col_detail', 'block_moodle_sence'),
    get_string('report_col_action', 'block_moodle_sence'),
];

foreach ($rows as $r) {
    $codes = html_writer::div('CodSence: ' . s($r->codsence ?: '—')) .
        html_writer::div('Curso/Acción: ' . s($r->codigocurso ?: '—'));
    $access = $r->courseaccessed
        ? userdate($r->timeaccess)
        : get_string('report_no_access', 'block_moodle_sence');
    $sence = $r->sencetime
        ? userdate($r->sencetime) . ($r->idsesionsence ? html_writer::div(s($r->idsesionsence), 'text-muted small') : '')
        : '—';
    $detail = '';
    if ($r->errorglosa) {
        $detail = html_writer::div(
            get_string('report_error_code', 'block_moodle_sence', $r->errorglosa) . ': ' . s($r->errortext),
            'block-moodle-sence-report__err'
        );
        if ($r->errortip !== '') {
            $detail .= html_writer::div(s($r->errortip), 'block-moodle-sence-report__tip');
        }
        if ($r->errortime) {
            $detail .= html_writer::div(userdate($r->errortime), 'small text-muted');
        }
    } else if ($r->status === report_builder::STATUS_COURSE_NO_SENCE) {
        $detail = get_string('report_tip_course_no_sence', 'block_moodle_sence');
    } else if ($r->status === report_builder::STATUS_NEVER) {
        $detail = get_string('report_tip_never', 'block_moodle_sence');
    } else if ($r->status === report_builder::STATUS_NOGROUP) {
        $detail = get_string('report_tip_nogroup', 'block_moodle_sence');
    }

    $statusbadge = html_writer::span(
        $r->statuslabel,
        'block-moodle-sence-report__badge block-moodle-sence-report__badge--' . $r->status
    );

    $actioncell = '—';
    if (isset($remindable[$r->status]) && !empty($r->email)) {
        $remindurl = new moodle_url($reporturl, [
            'status' => $filter,
            'action' => 'remind',
            'userid' => $r->userid,
            'sesskey' => sesskey(),
        ]);
        $actioncell = html_writer::link(
            $remindurl,
            get_string('reminder_user_button', 'block_moodle_sence'),
            [
                'class' => 'btn btn-sm btn-warning',
                'title' => get_string('reminder_user_button_help', 'block_moodle_sence'),
            ]
        );
    }

    $table->data[] = [
        html_writer::link($r->profileurl, s($r->fullname)) . html_writer::div(s($r->email), 'small text-muted'),
        s($r->run),
        s($r->groups ?: '—'),
        $codes,
        $statusbadge,
        $access,
        $sence,
        $detail,
        $actioncell,
    ];
}

if (empty($rows)) {
    echo $OUTPUT->notification(get_string('report_empty', 'block_moodle_sence'), 'info');
} else {
    echo html_writer::table($table);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
