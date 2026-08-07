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

$post = $_POST;
$glosa = isset($post['GlosaError']) ? (int) $post['GlosaError'] : 0;
$iserror = ($type === 'error') || ($glosa > 0);
$autoredirect = false;

echo $OUTPUT->header();

if ($iserror) {
    if ($glosa === 100) {
        $msg = get_string('glosa100', 'block_moodle_sence');
        echo html_writer::div($msg, 'alert alert-danger block-moodle-sence-callback-error', ['role' => 'alert']);
    } else {
        $glosatext = block_moodle_sence_glosa_message($glosa);
        $msg = get_string('senceerrorfull', 'block_moodle_sence', (object) [
            'code' => $glosa,
            'message' => $glosatext,
        ]);
        echo $OUTPUT->notification($msg, 'error');

        if (!empty($config->correoalerta)) {
            $codes = block_moodle_sence_resolve_runtime_codes($config, $courseid, $USER->id);
            $run = block_moodle_sence_resolve_user_run($USER);
            $subject = 'Alerta Error SENCE';
            $body = "Se ha producido un error interno en la integración con SENCE\n";
            $body .= 'Error: ' . $glosatext . ' (' . $glosa . ")\n";
            $body .= 'Nombre Usuario: ' . fullname($USER) . "\n";
            $body .= 'RUT Usuario: ' . $run . "\n";
            $body .= 'ID Acción: ' . $codes['idaccion'] . "\n";
            $body .= 'Código: ' . $codes['codigocurso'] . "\n";
            $body .= 'OTEC: ' . block_moodle_sence_get_rut_otec() . "\n\n";
            if (in_array($glosa, [300, 304, 305], true)) {
                $body .= "Reportar a controlelearning@sence.cl\n";
                $body .= 'Código Error: ' . $glosa . "\n";
            }
            $body .= "\n" . print_r($post, true);

            $to = new stdClass();
            $to->id = -1;
            $to->email = $config->correoalerta;
            $to->firstname = 'SENCE';
            $to->lastname = 'Alerta';
            $to->maildisplay = true;
            $to->mailformat = 1;
            @email_to_user($to, core_user::get_noreply_user(), $subject, $body, $body);
        }
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
