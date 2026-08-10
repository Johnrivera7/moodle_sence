<?php
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use block_moodle_sence\session_manager;

global $DB, $USER, $OUTPUT, $PAGE;

require_login();

$courseid = required_param('courseid', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);
$type = required_param('type', PARAM_ALPHA);
$token = required_param('token', PARAM_ALPHANUMEXT);

$course = get_course($courseid);
require_course_login($course);

if (!session_manager::verify_callback($courseid, $instanceid, $type, $token)) {
    throw new moodle_exception('invalidcallback', 'block_moodle_sence');
}

$blockinstance = $DB->get_record('block_instances', ['id' => $instanceid, 'blockname' => 'moodle_sence'], '*', MUST_EXIST);
$block = block_instance('moodle_sence', $blockinstance);
$config = $block->config ?? new stdClass();

$courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);

$PAGE->set_url(new moodle_url('/blocks/moodle_sence/callback.php', [
    'courseid' => $courseid,
    'instanceid' => $instanceid,
    'type' => $type,
]));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title(get_string('callbacktitle', 'block_moodle_sence'));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css('/blocks/moodle_sence/styles.css');

$post = $_POST;
$glosa = isset($post['GlosaError']) ? (int) $post['GlosaError'] : 0;
$iserror = ($type === 'error') || ($glosa > 0);
$autoredirect = false;

echo $OUTPUT->header();

if ($iserror) {
    $ctx = block_moodle_sence_format_error_context($course, $USER, $config, $glosa, $post);
    block_moodle_sence_log_error(
        $courseid,
        (int) $USER->id,
        $ctx['run'],
        $ctx['codes'],
        $glosa,
        $post
    );

    if ($glosa === 100) {
        $msg = get_string('glosa100', 'block_moodle_sence');
        echo html_writer::div($msg, 'alert alert-danger block-moodle-sence-callback-error', ['role' => 'alert']);
    } else {
        echo html_writer::div($ctx['html'], 'block-moodle-sence-callback-error', ['role' => 'alert']);
    }

    if (!empty($config->correoalerta)) {
        $subject = get_string('alert_email_subject', 'block_moodle_sence', (object) [
            'code' => $glosa,
            'shortname' => $course->shortname,
        ]);
        $body = $ctx['text'];
        if (in_array($glosa, [300, 304, 305], true)) {
            $body .= "\n\n" . get_string('alert_email_sence_support', 'block_moodle_sence');
        }
        $body .= "\n\n--- POST SENCE ---\n" . print_r($post, true);

        block_moodle_sence_send_alert_emails((string) $config->correoalerta, $subject, $body);
    }
} else {
    $idsesionsence = trim($post['IdSesionSence'] ?? '');
    if ($type === 'success' && $idsesionsence !== '') {
        $codes = block_moodle_sence_resolve_runtime_codes($config, $courseid, $USER->id);
        $data = (object) [
            'runalumno' => $post['RunAlumno'] ?? block_moodle_sence_resolve_user_run($USER),
            'codcurso' => $codes['codigocurso'],
            'idaccion' => $codes['idaccion'],
            'idsesionalumno' => $post['IdSesionAlumno'] ?? '',
            'idsesionsence' => $idsesionsence,
        ];
        session_manager::save_login($data);
        echo $OUTPUT->notification(get_string('loginsuccess', 'block_moodle_sence'), 'success');
        $autoredirect = true;
    } else if ($type === 'success') {
        session_manager::close_session_by_post($post, $USER);
        echo $OUTPUT->notification(get_string('logoutsuccess', 'block_moodle_sence'), 'success');
        $autoredirect = true;
    }
}

echo html_writer::div(
    html_writer::link(
        $courseurl,
        get_string('backtocourse', 'block_moodle_sence'),
        ['class' => 'btn btn-primary block-moodle-sence-btn']
    ),
    'mt-3 block-moodle-sence-callback-actions'
);

if ($autoredirect) {
    $urljs = $courseurl->out(false);
    echo html_writer::empty_tag('meta', ['http-equiv' => 'refresh', 'content' => '2;url=' . $urljs]);
    echo html_writer::script('setTimeout(function(){ window.location.href = ' . json_encode($urljs) . '; }, 2000);');
}

echo $OUTPUT->footer();
