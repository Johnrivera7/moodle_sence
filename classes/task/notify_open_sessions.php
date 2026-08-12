<?php
namespace block_moodle_sence\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Avisa por correo sesiones SENCE abiertas próximas a expirar o ya vencidas.
 *
 * @package    block_moodle_sence
 * @copyright  2026 John Rivera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_open_sessions extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_notify_open_sessions', 'block_moodle_sence');
    }

    public function execute(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/blocks/moodle_sence/lib.php');

        if (!get_config('block_moodle_sence', 'alertacierrecron')) {
            return;
        }

        $warnseconds = (int) get_config('block_moodle_sence', 'alertacierreminutos');
        if ($warnseconds <= 0) {
            $warnseconds = 900;
        }

        $opens = $DB->get_records_select(
            'block_sence',
            "idsesionsence IS NOT NULL AND idsesionsence <> ''",
            null,
            'firstacess ASC'
        );
        if (empty($opens)) {
            return;
        }

        foreach ($opens as $rec) {
            $parsed = \block_moodle_sence_parse_session_alumno((string) $rec->idsesionalumno);
            if (!$parsed) {
                continue;
            }
            $courseid = $parsed['courseid'];
            $userid = $parsed['userid'];
            $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
            $user = \core_user::get_user($userid, '*', IGNORE_MISSING);
            if (!$course || !$user || empty($user->email) || !empty($user->deleted)) {
                continue;
            }

            $blockcfg = \block_moodle_sence_get_course_block_config($courseid);
            $timeout = \block_moodle_sence_resolve_session_timeout($blockcfg);
            $remaining = \block_moodle_sence\session_manager::seconds_remaining($rec, $timeout);
            $already = (int) ($rec->closealertsent ?? 0);

            // Solo avisar cuando esté en ventana de alerta o vencida.
            if ($remaining > $warnseconds) {
                continue;
            }
            // Evitar reenvíos más frecuentes que cada 12 horas.
            if ($already > 0 && (time() - $already) < 12 * HOURSECS) {
                continue;
            }

            $courseurl = new \moodle_url('/course/view.php', ['id' => $courseid]);
            $a = (object) [
                'fullname' => fullname($user),
                'coursename' => format_string($course->fullname),
                'shortname' => $course->shortname,
                'courseurl' => $courseurl->out(false),
                'remaining' => format_time(max(0, $remaining)),
                'expired' => $remaining <= 0 ? 1 : 0,
                'run' => \block_moodle_sence\rut_helper::format_run((string) $rec->runalumno),
                'idsesionsence' => $rec->idsesionsence,
            ];

            if ($remaining <= 0) {
                $subject = get_string('closealert_expired_subject', 'block_moodle_sence', $a);
                $text = get_string('closealert_expired_text', 'block_moodle_sence', $a);
                $html = get_string('closealert_expired_html', 'block_moodle_sence', $a);
            } else {
                $subject = get_string('closealert_warn_subject', 'block_moodle_sence', $a);
                $text = get_string('closealert_warn_text', 'block_moodle_sence', $a);
                $html = get_string('closealert_warn_html', 'block_moodle_sence', $a);
            }

            email_to_user($user, \core_user::get_noreply_user(), $subject, $text, $html);

            $alertto = '';
            if ($blockcfg && !empty($blockcfg->correoalerta)) {
                $alertto = (string) $blockcfg->correoalerta;
            }
            if ($alertto !== '') {
                \block_moodle_sence_send_alert_emails(
                    $alertto,
                    '[Staff] ' . $subject,
                    $text
                );
            }

            if ($DB->get_manager()->field_exists('block_sence', 'closealertsent')) {
                $DB->set_field('block_sence', 'closealertsent', time(), ['id' => $rec->id]);
            }

            mtrace('SENCE close alert sent to ' . $user->email . ' course ' . $courseid .
                ' remaining=' . $remaining);
        }
    }
}
