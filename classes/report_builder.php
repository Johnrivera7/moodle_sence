<?php
namespace block_moodle_sence;

defined('MOODLE_INTERNAL') || die();

/**
 * Construye el reporte de asistencia SENCE por curso.
 *
 * @package    block_moodle_sence
 * @copyright  2026 John Rivera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_builder {

    /** Estados del reporte. */
    public const STATUS_OK = 'ok';
    public const STATUS_ERROR = 'error';
    public const STATUS_COURSE_NO_SENCE = 'course_no_sence';
    public const STATUS_NEVER = 'never';
    public const STATUS_BECADO = 'becado';
    public const STATUS_NOGROUP = 'nogroup';

    /**
     * Estados que reciben recordatorio (aún no marcaron asistencia OK).
     *
     * @return string[]
     */
    public static function reminder_statuses(): array {
        return [
            self::STATUS_NEVER,
            self::STATUS_COURSE_NO_SENCE,
            self::STATUS_ERROR,
            self::STATUS_NOGROUP,
        ];
    }

    /**
     * Filas destinatarias de recordatorio.
     *
     * @param array $rows
     * @return array
     */
    public static function reminder_recipients(array $rows): array {
        $allowed = array_flip(self::reminder_statuses());
        return array_values(array_filter($rows, static function ($r) use ($allowed) {
            return isset($allowed[$r->status]) && !empty($r->email);
        }));
    }

    /**
     * Envía recordatorio a un único participante, con CC al emisor (quien hace clic).
     *
     * @param \stdClass $course
     * @param \stdClass $row Fila del reporte
     * @param \stdClass $fromuser Quien envía (recibe copia)
     * @return string ok|skipped|failed
     */
    public static function send_reminder_user(\stdClass $course, \stdClass $row, \stdClass $fromuser): string {
        global $CFG;

        require_once($CFG->libdir . '/moodlelib.php');
        require_once($CFG->dirroot . '/blocks/moodle_sence/lib.php');

        $allowed = array_flip(self::reminder_statuses());
        if (!isset($allowed[$row->status]) || empty($row->email)) {
            return 'skipped';
        }

        $user = \core_user::get_user($row->userid, '*', IGNORE_MISSING);
        if (!$user || empty($user->email) || !empty($user->deleted) || !empty($user->suspended)) {
            return 'skipped';
        }

        $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
        $a = (object) [
            'fullname' => $row->fullname,
            'coursename' => format_string($course->fullname),
            'shortname' => $course->shortname,
            'courseurl' => $courseurl->out(false),
            'status' => $row->statuslabel,
            'groups' => $row->groups !== '' ? $row->groups : '—',
            'run' => $row->run !== '' ? $row->run : '—',
            'sender' => fullname($fromuser),
        ];

        $subject = get_string('reminder_subject', 'block_moodle_sence', $a);
        $bodytext = get_string('reminder_body_text', 'block_moodle_sence', $a);
        $bodyhtml = get_string('reminder_body_html', 'block_moodle_sence', $a);

        $ok = \block_moodle_sence_email_with_cc($user, $fromuser, $subject, $bodytext, $bodyhtml);
        return $ok ? 'ok' : 'failed';
    }

    /**
     * @param int $courseid
     * @param \stdClass $config Config del bloque
     * @return array{summary:array,rows:array}
     */
    public static function build(int $courseid, \stdClass $config): array {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->libdir . '/enrollib.php');
        if (!function_exists('groups_get_all_groups')) {
            require_once($CFG->dirroot . '/lib/grouplib.php');
        }

        $context = \context_course::instance($courseid);
        $users = get_enrolled_users($context, '', 0, 'u.id,u.firstname,u.lastname,u.email,u.idnumber,u.username', 'u.lastname,u.firstname');

        $lastaccess = $DB->get_records_menu('user_lastaccess', ['courseid' => $courseid], '', 'userid,timeaccess');

        $errors = [];
        if ($DB->get_manager()->table_exists('block_moodle_sence_log')) {
            $errorrecs = $DB->get_records_sql(
                "SELECT * FROM {block_moodle_sence_log}
                  WHERE courseid = :cid AND status = :st
                  ORDER BY timecreated DESC",
                ['cid' => $courseid, 'st' => 'error']
            );
            foreach ($errorrecs as $er) {
                $uid = (int) $er->userid;
                if (!isset($errors[$uid])) {
                    $errors[$uid] = $er;
                }
            }
        }

        $sencebyrun = self::index_sence_by_run();

        $summary = [
            self::STATUS_OK => 0,
            self::STATUS_ERROR => 0,
            self::STATUS_COURSE_NO_SENCE => 0,
            self::STATUS_NEVER => 0,
            self::STATUS_BECADO => 0,
            self::STATUS_NOGROUP => 0,
            'total' => 0,
        ];

        $rows = [];
        foreach ($users as $user) {
            // Omitir docentes/gestores del listado de participantes SENCE.
            if (has_capability('moodle/course:viewhiddenactivities', $context, $user)) {
                continue;
            }

            profile_load_custom_fields($user);
            $run = \block_moodle_sence_resolve_user_run($user);
            $codes = \block_moodle_sence_resolve_runtime_codes($config, $courseid, (int) $user->id);
            $becado = \block_moodle_sence_user_is_becado($config, $courseid, (int) $user->id);
            $groups = groups_get_all_groups($courseid, (int) $user->id, 0, 'g.id,g.name');
            $groupnames = array_map(static function ($g) {
                return $g->name;
            }, $groups);

            $courseaccessed = !empty($lastaccess[$user->id]);
            $timeaccess = (int) ($lastaccess[$user->id] ?? 0);

            $sence = self::find_sence_for_user($sencebyrun, $run, $codes);
            $hassuccess = ($sence !== null);
            $lasterror = $errors[(int) $user->id] ?? null;

            if ($becado) {
                $status = self::STATUS_BECADO;
            } else if (!$codes['fromgroup']
                && (strcasecmp(trim((string) ($config->codigocurso ?? '')), 'MULTIPLES') === 0
                    || (int) ($config->lineasdecap ?? 3) !== 1)
                && ($codes['idaccion'] === '' || $codes['codigocurso'] === '')) {
                $status = self::STATUS_NOGROUP;
            } else if ($hassuccess) {
                $status = self::STATUS_OK;
            } else if ($lasterror) {
                $status = self::STATUS_ERROR;
            } else if ($courseaccessed) {
                $status = self::STATUS_COURSE_NO_SENCE;
            } else {
                $status = self::STATUS_NEVER;
            }

            $summary[$status]++;
            $summary['total']++;

            $glosa = $lasterror ? (int) $lasterror->glosa : 0;
            $rows[] = (object) [
                'userid' => (int) $user->id,
                'fullname' => fullname($user),
                'email' => $user->email,
                'run' => $run,
                'groups' => implode(', ', $groupnames),
                'codsence' => $codes['codsence'],
                'codigocurso' => $codes['codigocurso'],
                'idaccion' => $codes['idaccion'],
                'status' => $status,
                'statuslabel' => get_string('report_status_' . $status, 'block_moodle_sence'),
                'courseaccessed' => $courseaccessed,
                'timeaccess' => $timeaccess,
                'sencetime' => $sence ? (int) $sence->firstacess : 0,
                'idsesionsence' => $sence->idsesionsence ?? '',
                'errorglosa' => $glosa,
                'errortext' => $glosa ? \block_moodle_sence_glosa_message($glosa) : '',
                'errortip' => $glosa ? \block_moodle_sence_glosa_tip($glosa) : '',
                'errortime' => $lasterror ? (int) $lasterror->timecreated : 0,
                'profileurl' => (new \moodle_url('/user/view.php', [
                    'id' => $user->id,
                    'course' => $courseid,
                ]))->out(false),
            ];
        }

        return ['summary' => $summary, 'rows' => $rows];
    }

    /**
     * Indexa registros block_sence por cuerpo de RUT.
     *
     * @return array<string,array<\stdClass>>
     */
    protected static function index_sence_by_run(): array {
        global $DB;
        $all = $DB->get_records('block_sence', null, 'firstacess DESC');
        $byrun = [];
        foreach ($all as $rec) {
            $body = rut_helper::run_body((string) $rec->runalumno);
            if ($body === '') {
                continue;
            }
            $byrun[$body][] = $rec;
        }
        return $byrun;
    }

    /**
     * @param array $sencebyrun
     * @param string $run
     * @param array $codes
     * @return \stdClass|null
     */
    protected static function find_sence_for_user(array $sencebyrun, string $run, array $codes): ?\stdClass {
        $body = rut_helper::run_body($run);
        if ($body === '' || empty($sencebyrun[$body])) {
            return null;
        }
        $codcurso = (string) ($codes['codigocurso'] ?? '');
        $idaccion = (string) ($codes['idaccion'] ?? '');
        foreach ($sencebyrun[$body] as $rec) {
            if ($codcurso !== '' && (string) $rec->codcurso === $codcurso && !empty($rec->idsesionsence)) {
                return $rec;
            }
            // Historial: sesión cerrada (idsesionsence vacío) pero sí hubo registro.
            if ($codcurso !== '' && (string) $rec->codcurso === $codcurso) {
                return $rec;
            }
            if ($idaccion !== '' && (string) $rec->idaccion === $idaccion) {
                return $rec;
            }
        }
        return null;
    }
}
