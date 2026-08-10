<?php
defined('MOODLE_INTERNAL') || die();

/**
 * @package    block_moodle_sence
 * @copyright  2026 John Rivera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('BLOCK_MOODLE_SENCE_URL_LOGIN_PROD', 'https://sistemas.sence.cl/rce/Registro/IniciarSesion');
define('BLOCK_MOODLE_SENCE_URL_LOGOUT_PROD', 'https://sistemas.sence.cl/rce/Registro/CerrarSesion');
define('BLOCK_MOODLE_SENCE_URL_LOGIN_TEST', 'https://sistemas.sence.cl/rcetest/Registro/IniciarSesion');
define('BLOCK_MOODLE_SENCE_URL_LOGOUT_TEST', 'https://sistemas.sence.cl/rcetest/Registro/CerrarSesion');

require_once(__DIR__ . '/classes/rut_helper.php');
require_once(__DIR__ . '/classes/session_manager.php');
if (!function_exists('groups_get_all_groups')) {
    global $CFG;
    require_once($CFG->dirroot . '/lib/grouplib.php');
}

use block_moodle_sence\rut_helper;
use block_moodle_sence\session_manager;

/**
 * @return string
 */
function block_moodle_sence_get_rut_otec(): string {
    $rut = get_config('block_moodle_sence', 'rutotec');
    if (empty($rut) && get_config('local_moodle_sicsence', 'credencialesjson')) {
        $json = json_decode((string) get_config('local_moodle_sicsence', 'credencialesjson'), true);
        if (!empty($json[0]['rut'])) {
            $rut = $json[0]['rut'];
        }
    }
    if (empty($rut)) {
        $rut = get_config('block_sence', 'rutotec');
    }
    return rut_helper::format_run($rut ?? '');
}

/**
 * Token OTEC: trim + mayúsculas al enviar (como RTS/SENCE).
 *
 * @return string
 */
function block_moodle_sence_get_token(): string {
    $token = (string) get_config('block_moodle_sence', 'tokenotec');
    if ($token === '' && get_config('local_moodle_sicsence', 'credencialesjson')) {
        $json = json_decode((string) get_config('local_moodle_sicsence', 'credencialesjson'), true);
        if (!empty($json[0]['token'])) {
            $token = $json[0]['token'];
        }
    }
    if ($token === '') {
        $token = (string) get_config('block_sence', 'tokenotec');
    }
    $token = trim($token);
    return $token === '' ? '' : strtoupper($token);
}

/**
 * @return bool
 */
function block_moodle_sence_is_test_mode(): bool {
    return (bool) get_config('block_moodle_sence', 'testmode');
}

/**
 * @return array{login:string,logout:string}
 */
function block_moodle_sence_get_rce_urls(): array {
    if (block_moodle_sence_is_test_mode()) {
        return [
            'login' => BLOCK_MOODLE_SENCE_URL_LOGIN_TEST,
            'logout' => BLOCK_MOODLE_SENCE_URL_LOGOUT_TEST,
        ];
    }
    return [
        'login' => BLOCK_MOODLE_SENCE_URL_LOGIN_PROD,
        'logout' => BLOCK_MOODLE_SENCE_URL_LOGOUT_PROD,
    ];
}

/**
 * @param string $raw config_codigocurso
 * @return array{codsence:string,codigocurso:string,raw:string}
 */
function block_moodle_sence_parse_course_codes(string $raw): array {
    $raw = trim($raw);
    if ($raw === '' || strcasecmp($raw, 'MULTIPLES') === 0) {
        return [
            'codsence' => '',
            'codigocurso' => '',
            'raw' => $raw,
        ];
    }
    $parts = array_map('trim', explode('/', $raw));
    if (count($parts) >= 2) {
        return [
            'codsence' => $parts[0],
            'codigocurso' => $parts[1],
            'raw' => $raw,
        ];
    }
    return [
        'codsence' => strlen($raw) >= 10 ? substr($raw, 0, 10) : $raw,
        'codigocurso' => $raw,
        'raw' => $raw,
    ];
}

/**
 * Resuelve CodSence / CodigoCurso / idacción según config, línea y grupos SENCE-*.
 *
 * @param \stdClass $config
 * @param int $courseid
 * @param int $userid
 * @return array{codsence:string,codigocurso:string,idaccion:string,raw:string,fromgroup:bool}
 */
function block_moodle_sence_resolve_runtime_codes(\stdClass $config, int $courseid, int $userid): array {
    $raw = trim((string) ($config->codigocurso ?? ''));
    $linea = (int) ($config->lineasdecap ?? 3);
    $parsed = block_moodle_sence_parse_course_codes($raw);
    $ismultiples = (strcasecmp($raw, 'MULTIPLES') === 0);

    $best = null;
    $groups = groups_get_all_groups($courseid, $userid, 0, 'g.id,g.name,g.idnumber,g.description');
    foreach ($groups as $group) {
        $actionid = null;
        if (preg_match('/^SENCE-(\d+)/i', (string) $group->name, $m)) {
            $actionid = (int) $m[1];
        } else if (!empty($group->idnumber)) {
            $idnumber = trim((string) $group->idnumber);
            if (preg_match('/^SENCE-?(\d+)$/i', $idnumber, $m)) {
                $actionid = (int) $m[1];
            } else if (ctype_digit($idnumber)) {
                $actionid = (int) $idnumber;
            }
        }
        if ($actionid === null) {
            continue;
        }
        if ($best === null || $best['idaccion'] < $actionid) {
            $best = [
                'idaccion' => $actionid,
                'description' => trim(strip_tags((string) ($group->description ?? ''))),
                'name' => (string) $group->name,
            ];
        }
    }

    $fromgroup = ($best !== null);
    $groupaccion = $fromgroup ? (string) $best['idaccion'] : '';
    $groupdesc = $fromgroup ? $best['description'] : '';

    $codsence = $parsed['codsence'];
    $codigocurso = $parsed['codigocurso'];

    if ($ismultiples) {
        $codsence = $groupdesc;
        $codigocurso = $groupaccion !== '' ? $groupaccion : $groupdesc;
    }

    if ($linea === 1) {
        $codsence = ' ';
        $codigocurso = $groupaccion !== '' ? $groupaccion : $codigocurso;
    } else {
        // Línea 3/6: CodSence = primer segmento (o descripción MULTIPLES); CodigoCurso = idacción grupo o 2º segmento.
        if ($groupaccion !== '') {
            $codigocurso = $groupaccion;
        }
    }

    $idaccion = $groupaccion !== '' ? $groupaccion : $codigocurso;

    return [
        'codsence' => $codsence,
        'codigocurso' => $codigocurso,
        'idaccion' => (string) $idaccion,
        'raw' => $raw,
        'fromgroup' => $fromgroup,
    ];
}

/**
 * @param \stdClass $config
 * @param int $courseid
 * @param int $userid
 * @return bool
 */
function block_moodle_sence_user_is_becado(\stdClass $config, int $courseid, int $userid): bool {
    $raw = trim((string) ($config->grupobecas ?? ''));
    if ($raw === '') {
        return false;
    }
    $becas = array_filter(array_map('trim', explode(',', $raw)));
    if (empty($becas)) {
        return false;
    }
    $groups = groups_get_all_groups($courseid, $userid, 0, 'g.id,g.name');
    $names = array_map(static function ($g) {
        return $g->name;
    }, $groups);
    return count(array_intersect($becas, $names)) > 0;
}

/**
 * Cuenta registros previos en block_sence (asistencia ya informada).
 *
 * @param string $run RUT con o sin guión
 * @param string $codcurso
 * @param string $idaccion
 * @return int
 */
function block_moodle_sence_has_prior_registration(string $run, string $codcurso, string $idaccion): int {
    global $DB;

    if ($codcurso === '' || $idaccion === '') {
        return 0;
    }

    $runbody = rut_helper::run_body($run);
    $runfmt = rut_helper::format_run($run);
    $candidates = array_values(array_unique(array_filter([$runbody, $runfmt])));

    if (empty($candidates)) {
        return 0;
    }

    list($insql, $params) = $DB->get_in_or_equal($candidates, SQL_PARAMS_NAMED, 'run');
    $params['codcurso'] = $codcurso;
    $params['idaccion'] = $idaccion;

    return (int) $DB->count_records_select(
        'block_sence',
        "runalumno $insql AND codcurso = :codcurso AND idaccion = :idaccion",
        $params
    );
}

/**
 * Glosas Anexo 2 (manual RCE). Preferencia por lang string glosa{code}.
 *
 * @param int|string $code
 * @return string
 */
function block_moodle_sence_glosa_message($code): string {
    $code = (int) $code;
    $key = 'glosa' . $code;
    if (get_string_manager()->string_exists($key, 'block_moodle_sence')) {
        return get_string($key, 'block_moodle_sence');
    }

    static $fallback = [
        100 => 'Contraseña incorrecta',
        200 => 'Parámetros vacíos',
        201 => 'Parámetro UrlError sin datos',
        202 => 'Parámetro UrlError con formato incorrecto',
        203 => 'Parámetro UrlRetoma con formato incorrecto',
        204 => 'El Código SENCE tiene formato incorrecto',
        205 => 'El Código Curso tiene formato incorrecto',
        206 => 'Línea de capacitación incorrecta',
        207 => 'Parámetro RunAlumno incorrecto',
        208 => 'RUN Alumno no autorizado para realizar el curso.',
        209 => 'RUT del OTEC está incorrecto',
        210 => 'Inicio de Sesión en SENCE Expirado. Vuelva a intentar.',
        211 => 'Token no corresponde a la empresa',
        212 => 'Token caducado',
        300 => 'Error interno SENCE',
        301 => 'ID de Acción/Folio Sence/SENCENET incorrecto o Línea de Capacitación incorrecta',
        302 => 'Error interno SENCE',
        303 => 'Token no existe o su formato es incorrecto',
        304 => 'Error interno SENCE',
        305 => 'Error interno SENCE',
        306 => 'El Código Curso no corresponde al Código SENCE',
        307 => 'El Código Curso no tiene Modalidad E-Learning',
        308 => 'El Código Curso no corresponde al RUT OTEC.',
        309 => 'Las fechas de ejecución comunicadas para el Código Curso no corresponden a la fecha actual.',
        310 => 'El Código Curso está en estado Terminado o Anulado.',
        311 => 'RUT ingresado en el Login de Clave Única no se corresponde con RUT del usuario en la plataforma',
        312 => 'No se pudo completar la autenticación con Clave Única.',
    ];

    if (isset($fallback[$code])) {
        return $fallback[$code];
    }
    return get_string('senceerror', 'block_moodle_sence', $code);
}

/**
 * Tip operativo por glosa (qué revisar).
 *
 * @param int|string $code
 * @return string
 */
function block_moodle_sence_glosa_tip($code): string {
    $code = (int) $code;
    $key = 'glosa' . $code . '_tip';
    if (get_string_manager()->string_exists($key, 'block_moodle_sence')) {
        return get_string($key, 'block_moodle_sence');
    }
    return '';
}

/**
 * Persiste intento de error SENCE para el reporte del curso.
 *
 * @param int $courseid
 * @param int $userid
 * @param string $run
 * @param array $codes
 * @param int $glosa
 * @param array $post
 * @return int
 */
function block_moodle_sence_log_error(
    int $courseid,
    int $userid,
    string $run,
    array $codes,
    int $glosa,
    array $post = []
): int {
    global $DB;

    if (!$DB->get_manager()->table_exists('block_moodle_sence_log')) {
        return 0;
    }

    $rec = (object) [
        'courseid' => $courseid,
        'userid' => $userid,
        'runalumno' => rut_helper::format_run($run),
        'codsence' => (string) ($codes['codsence'] ?? ($post['CodSence'] ?? '')),
        'codcurso' => (string) ($codes['codigocurso'] ?? ($post['CodigoCurso'] ?? '')),
        'idaccion' => (string) ($codes['idaccion'] ?? ($codes['codigocurso'] ?? '')),
        'glosa' => $glosa,
        'idsesionalumno' => (string) ($post['IdSesionAlumno'] ?? ''),
        'status' => 'error',
        'timecreated' => time(),
        'rawdata' => json_encode($post, JSON_UNESCAPED_UNICODE),
    ];
    return (int) $DB->insert_record('block_moodle_sence_log', $rec);
}

/**
 * Contexto HTML enriquecido para pantallas/correos de error.
 *
 * @param \stdClass $course
 * @param \stdClass $user
 * @param \stdClass $config
 * @param int $glosa
 * @param array $post
 * @return array{codes:array,run:string,courseurl:\moodle_url,groups:string,html:string,text:string}
 */
function block_moodle_sence_format_error_context(
    \stdClass $course,
    \stdClass $user,
    \stdClass $config,
    int $glosa,
    array $post = []
): array {
    $codes = block_moodle_sence_resolve_runtime_codes($config, (int) $course->id, (int) $user->id);
    $run = block_moodle_sence_resolve_user_run($user);
    $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
    $glosatext = block_moodle_sence_glosa_message($glosa);
    $tip = block_moodle_sence_glosa_tip($glosa);
    $tiphtml = $tip !== ''
        ? \html_writer::div(s($tip), 'block-moodle-sence-error-card__tip')
        : '';

    $groups = groups_get_all_groups((int) $course->id, (int) $user->id, 0, 'g.id,g.name');
    $groupnames = implode(', ', array_map(static function ($g) {
        return $g->name;
    }, $groups));

    $a = (object) [
        'code' => $glosa,
        'message' => $glosatext,
        'tip' => $tiphtml,
        'tiptext' => $tip !== '' ? $tip : '—',
        'fullname' => fullname($user),
        'run' => $run,
        'coursename' => format_string($course->fullname),
        'shortname' => $course->shortname,
        'courseurl' => $courseurl->out(false),
        'codsence' => $codes['codsence'] !== '' ? $codes['codsence'] : ($post['CodSence'] ?? '—'),
        'codigocurso' => $codes['codigocurso'] !== '' ? $codes['codigocurso'] : ($post['CodigoCurso'] ?? '—'),
        'idaccion' => $codes['idaccion'] !== '' ? $codes['idaccion'] : '—',
        'otec' => block_moodle_sence_get_rut_otec(),
        'groups' => $groupnames !== '' ? $groupnames : '—',
        'fechahora' => $post['FechaHora'] ?? userdate(time()),
        'zona' => $post['ZonaHoraria'] ?? '',
        'linea' => $post['LineaCapacitacion'] ?? ($config->lineasdecap ?? 3),
        'idsesionalumno' => $post['IdSesionAlumno'] ?? '',
    ];

    $text = get_string('senceerror_detail_text', 'block_moodle_sence', (object) array_merge(
        (array) $a,
        ['tip' => $a->tiptext]
    ));

    return [
        'codes' => $codes,
        'run' => $run,
        'courseurl' => $courseurl,
        'groups' => $groupnames,
        'html' => get_string('senceerror_detail_html', 'block_moodle_sence', $a),
        'text' => $text,
        'a' => $a,
    ];
}

/**
 * RUT alumno desde profile_field_rut (único); fallback idnumber/username.
 *
 * @param \stdClass $user
 * @return string
 */
function block_moodle_sence_resolve_user_run(\stdClass $user): string {
    global $CFG;
    require_once($CFG->dirroot . '/user/profile/lib.php');

    if (empty($user->profile) || !is_array($user->profile)) {
        profile_load_custom_fields($user);
    }

    $raw = '';
    if (!empty($user->profile['rut'])) {
        $raw = trim((string) $user->profile['rut']);
    } else if (!empty($user->profile_field_rut)) {
        $raw = trim((string) $user->profile_field_rut);
    }
    if ($raw === '') {
        $raw = trim((string) ($user->idnumber ?? ''));
    }
    if ($raw === '') {
        $raw = trim((string) ($user->username ?? ''));
    }
    $raw = str_replace('.', '', $raw);
    return rut_helper::format_run($raw);
}

/**
 * URL para editar el campo de perfil RUT.
 *
 * @param int $userid
 * @return \moodle_url
 */
function block_moodle_sence_profile_rut_edit_url(int $userid): \moodle_url {
    return new \moodle_url('/user/edit.php', ['id' => $userid, 'course' => SITEID]);
}

/**
 * Valida formato RUT chileno básico (9–10 chars con guión).
 *
 * @param string $run
 * @return bool
 */
function block_moodle_sence_is_valid_run(string $run): bool {
    $len = strlen($run);
    return $len >= 9 && $len <= 10 && strpos($run, '-') !== false;
}

/**
 * En rcetest: CodSence vacío → -1; CodigoCurso corto (<7) → -1. Conserva espacio de línea 1.
 *
 * @param string $codsence
 * @param string $codigocurso
 * @return array{0:string,1:string}
 */
function block_moodle_sence_apply_testmode_codes(string $codsence, string $codigocurso): array {
    if (!block_moodle_sence_is_test_mode()) {
        return [$codsence, $codigocurso];
    }
    if ($codsence !== ' ' && trim($codsence) === '') {
        $codsence = '-1';
    }
    if (strlen($codigocurso) < 7) {
        $codigocurso = '-1';
    }
    return [$codsence, $codigocurso];
}

/**
 * Envía un correo con copia (CC) al usuario que dispara la acción.
 * Usa get_mailer para CC real; si falla, envía al destinatario y una copia aparte al emisor.
 *
 * @param \stdClass $touser
 * @param \stdClass $fromuser Remitente / quien hace clic
 * @param string $subject
 * @param string $messagetext
 * @param string $messagehtml
 * @return bool
 */
function block_moodle_sence_email_with_cc(
    \stdClass $touser,
    \stdClass $fromuser,
    string $subject,
    string $messagetext,
    string $messagehtml = ''
): bool {
    global $CFG;

    if (empty($touser->email) || empty($fromuser->email)) {
        return false;
    }

    $toname = fullname($touser);
    $fromname = fullname($fromuser);
    $noreply = !empty($CFG->noreplyaddress) ? $CFG->noreplyaddress : $fromuser->email;

    try {
        $mail = get_mailer();
        $mail->Sender = $noreply;
        $mail->From = $noreply;
        $mail->FromName = $fromname;
        $mail->addReplyTo($fromuser->email, $fromname);
        $mail->Subject = substr($subject, 0, 900);
        $mail->addAddress($touser->email, $toname);
        if ((int) $fromuser->id !== (int) $touser->id) {
            $mail->addCC($fromuser->email, $fromname);
        }
        if ($messagehtml !== '') {
            $mail->isHTML(true);
            $mail->Encoding = 'quoted-printable';
            $mail->Body = $messagehtml;
            $mail->AltBody = $messagetext;
        } else {
            $mail->isHTML(false);
            $mail->Body = $messagetext;
        }
        return (bool) $mail->send();
    } catch (\Throwable $e) {
        debugging('block_moodle_sence CC mailer: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    // Fallback: correo al alumno + copia al emisor.
    $ok = email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml);
    if ($ok && (int) $fromuser->id !== (int) $touser->id) {
        $ccsubject = get_string('reminder_cc_subject', 'block_moodle_sence', (object) [
            'subject' => $subject,
            'fullname' => $toname,
        ]);
        $preamble = get_string('reminder_cc_preamble', 'block_moodle_sence', (object) [
            'fullname' => $toname,
            'email' => $touser->email,
        ]);
        email_to_user(
            $fromuser,
            \core_user::get_noreply_user(),
            $ccsubject,
            $preamble . "\n\n" . $messagetext,
            '<p>' . s($preamble) . '</p>' . ($messagehtml !== '' ? $messagehtml : nl2br(s($messagetext)))
        );
    }
    return $ok;
}

/**
 * @param int $courseid
 * @param int $instanceid
 * @param string $type success|error
 * @return \moodle_url
 */
function block_moodle_sence_callback_url(int $courseid, int $instanceid, string $type): \moodle_url {
    $token = session_manager::sign_callback($courseid, $instanceid, $type);
    return new \moodle_url('/blocks/moodle_sence/callback.php', [
        'courseid' => $courseid,
        'instanceid' => $instanceid,
        'type' => $type,
        'token' => $token,
    ]);
}

/**
 * @param \stdClass $config
 * @param int $courseid
 * @param int $instanceid
 * @param \stdClass $user
 * @return array
 */
function block_moodle_sence_build_login_fields(
    \stdClass $config,
    int $courseid,
    int $instanceid,
    \stdClass $user
): array {
    $codes = block_moodle_sence_resolve_runtime_codes($config, $courseid, $user->id);
    $run = block_moodle_sence_resolve_user_run($user);
    $idsesion = session_manager::generate_session_id($user->id, $courseid);
    list($codsence, $codigocurso) = block_moodle_sence_apply_testmode_codes(
        $codes['codsence'],
        $codes['codigocurso']
    );

    $success = block_moodle_sence_callback_url($courseid, $instanceid, 'success');
    $error = block_moodle_sence_callback_url($courseid, $instanceid, 'error');

    return [
        'RutOtec' => block_moodle_sence_get_rut_otec(),
        'Token' => block_moodle_sence_get_token(),
        'CodSence' => $codsence,
        'CodigoCurso' => $codigocurso,
        'LineaCapacitacion' => (int) ($config->lineasdecap ?? 3),
        'RunAlumno' => $run,
        'IdSesionAlumno' => $idsesion,
        'UrlRetoma' => $success->out(false),
        'UrlError' => $error->out(false),
        '_idsesionalumno' => $idsesion,
        '_codcurso' => $codes['codigocurso'],
        '_idaccion' => $codes['idaccion'],
        '_codsence' => $codes['codsence'],
    ];
}

/**
 * @param \stdClass $config
 * @param \stdClass $record
 * @param int $courseid
 * @param int $instanceid
 * @param int $userid
 * @return array
 */
function block_moodle_sence_build_logout_fields(
    \stdClass $config,
    \stdClass $record,
    int $courseid,
    int $instanceid,
    int $userid = 0
): array {
    if ($userid <= 0 && !empty($record->runalumno)) {
        // userid opcional; códigos desde config + grupo del usuario actual si se pasa.
        global $USER;
        $userid = (int) $USER->id;
    }
    $codes = block_moodle_sence_resolve_runtime_codes($config, $courseid, $userid);
    list($codsence, $codigocurso) = block_moodle_sence_apply_testmode_codes(
        $codes['codsence'],
        $codes['codigocurso']
    );

    $success = block_moodle_sence_callback_url($courseid, $instanceid, 'success');
    $error = block_moodle_sence_callback_url($courseid, $instanceid, 'error');

    return [
        'RutOtec' => block_moodle_sence_get_rut_otec(),
        'Token' => block_moodle_sence_get_token(),
        'CodSence' => $codsence,
        'CodigoCurso' => $codigocurso,
        'LineaCapacitacion' => (int) ($config->lineasdecap ?? 3),
        'RunAlumno' => rut_helper::format_run($record->runalumno),
        'IdSesionAlumno' => $record->idsesionalumno,
        'IdSesionSence' => $record->idsesionsence,
        'UrlRetoma' => $success->out(false),
        'UrlError' => $error->out(false),
    ];
}

/**
 * Auto-submit POST form HTML.
 *
 * @param string $action
 * @param array $fields
 * @param string $label
 * @param string $formid
 * @return string
 */
function block_moodle_sence_render_post_form(
    string $action,
    array $fields,
    string $label,
    string $formid = ''
): string {
    $attrs = [
        'method' => 'post',
        'action' => $action,
        'class' => 'block-moodle-sence-rce-form',
    ];
    if ($formid !== '') {
        $attrs['id'] = $formid;
    }
    $out = \html_writer::start_tag('form', $attrs);
    foreach ($fields as $name => $value) {
        if (strpos($name, '_') === 0) {
            continue;
        }
        $out .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
    }
    $out .= \html_writer::tag('button', $label, [
        'type' => 'submit',
        'class' => 'btn block-moodle-sence-btn block-moodle-sence-btn--primary',
    ]);
    $out .= \html_writer::end_tag('form');
    return $out;
}
