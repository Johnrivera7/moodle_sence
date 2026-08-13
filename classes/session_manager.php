<?php
namespace block_moodle_sence;

defined('MOODLE_INTERNAL') || die();

class session_manager {

    /**
     * @param int $userid
     * @param int $courseid
     * @return string
     */
    public static function generate_session_id(int $userid, int $courseid): string {
        return 'MOODLE-' . $courseid . '-' . $userid . '-' . time();
    }

    /**
     * @param int $courseid
     * @param int $instanceid
     * @param string $type
     * @return string
     */
    public static function sign_callback(int $courseid, int $instanceid, string $type): string {
        return hash_hmac('sha256', $courseid . ':' . $instanceid . ':' . $type, self::secret());
    }

    /**
     * @param int $courseid
     * @param int $instanceid
     * @param string $type
     * @param string $token
     * @return bool
     */
    public static function verify_callback(int $courseid, int $instanceid, string $type, string $token): bool {
        return hash_equals(self::sign_callback($courseid, $instanceid, $type), $token);
    }

    /**
     * @return string
     */
    protected static function secret(): string {
        global $CFG;
        return $CFG->wwwroot . '|' . $CFG->passwordsaltmain;
    }

    /**
     * Sesión abierta: tiene IdSesionSence y aún no tiene timeend.
     *
     * @param \stdClass $rec
     * @return bool
     */
    public static function is_open_record(\stdClass $rec): bool {
        if (empty($rec->idsesionsence)) {
            return false;
        }
        $ended = isset($rec->timeend) ? (int) $rec->timeend : 0;
        return $ended <= 0;
    }

    /**
     * Busca sesión abierta por RUT sin guión y, si no hay, con guión.
     *
     * @param string $runformatted
     * @param string $codcurso
     * @return \stdClass|null
     */
    public static function get_open_session(string $runformatted, string $codcurso): ?\stdClass {
        global $DB;

        $runbody = rut_helper::run_body($runformatted);
        $runwith = rut_helper::format_run($runformatted);
        $candidates = array_values(array_unique(array_filter([$runbody, $runwith])));

        foreach ($candidates as $run) {
            $records = $DB->get_records('block_sence', [
                'runalumno' => $run,
                'codcurso' => $codcurso,
            ], 'firstacess DESC', '*', 0, 10);
            foreach ($records as $rec) {
                if (self::is_open_record($rec)) {
                    return $rec;
                }
            }
        }
        return null;
    }

    /**
     * Sesión abierta por IdSesionAlumno (el que SENCE devuelve en el POST de error/cierre).
     *
     * @param string $idsesionalumno
     * @param string $runformatted
     * @return \stdClass|null
     */
    public static function get_open_session_by_alumno_id(string $idsesionalumno, string $runformatted = ''): ?\stdClass {
        global $DB;

        $idsesionalumno = trim($idsesionalumno);
        if ($idsesionalumno === '') {
            return null;
        }

        $records = $DB->get_records('block_sence', [
            'idsesionalumno' => $idsesionalumno,
        ], 'firstacess DESC', '*', 0, 10);

        $runbody = $runformatted !== '' ? rut_helper::run_body($runformatted) : '';
        $runwith = $runformatted !== '' ? rut_helper::format_run($runformatted) : '';

        foreach ($records as $rec) {
            if (!self::is_open_record($rec)) {
                continue;
            }
            if ($runformatted !== '') {
                $stored = (string) ($rec->runalumno ?? '');
                if ($stored !== $runbody && $stored !== $runwith) {
                    continue;
                }
            }
            return $rec;
        }
        return null;
    }

    /**
     * Guarda login: runalumno sin guión (lookup acepta ambos formatos).
     *
     * @param \stdClass $data
     * @return int
     */
    public static function save_login(\stdClass $data): int {
        global $DB;
        $rec = new \stdClass();
        $rec->runalumno = rut_helper::run_body($data->runalumno);
        $rec->codcurso = $data->codcurso;
        $rec->idaccion = $data->idaccion ?? $data->codcurso;
        $rec->idsesionalumno = $data->idsesionalumno;
        $rec->idsesionsence = $data->idsesionsence;
        $rec->firstacess = time();
        if ($DB->get_manager()->field_exists('block_sence', 'codsence') && isset($data->codsence)) {
            $rec->codsence = (string) $data->codsence;
        }
        if ($DB->get_manager()->field_exists('block_sence', 'timeend')) {
            $rec->timeend = 0;
        }
        return (int) $DB->insert_record('block_sence', $rec);
    }

    /**
     * Cierra sesión conservando IdSesionSence para historial y registra timeend.
     *
     * @param int $recordid
     */
    public static function close_session(int $recordid): void {
        global $DB;
        $rec = $DB->get_record('block_sence', ['id' => $recordid], '*', IGNORE_MISSING);
        if (!$rec) {
            return;
        }
        $update = (object) ['id' => $recordid];
        if ($DB->get_manager()->field_exists('block_sence', 'timeend')) {
            $update->timeend = time();
            // Conserva idsesionsence para el historial; la apertura se detecta con timeend=0.
        } else {
            // Compatibilidad instalaciones sin timeend.
            $update->idsesionsence = '';
        }
        $DB->update_record('block_sence', $update);
    }

    /**
     * Cierre de sesión exitoso (POST retorno sin IdSesionSence o con cierre).
     *
     * @param array $post
     * @param \stdClass $user
     */
    public static function close_session_by_post(array $post, \stdClass $user): void {
        $idsesion = $post['IdSesionAlumno'] ?? '';
        $runraw = $post['RunAlumno'] ?? '';
        if ($runraw === '') {
            $runraw = \block_moodle_sence_resolve_user_run($user);
        }
        $runbody = rut_helper::run_body(rut_helper::format_run($runraw));
        $runwith = rut_helper::format_run($runraw);

        if ($idsesion !== '') {
            global $DB;
            foreach (array_unique([$runbody, $runwith]) as $run) {
                if ($rec = $DB->get_record('block_sence', ['idsesionalumno' => $idsesion, 'runalumno' => $run])) {
                    if (self::is_open_record($rec) || empty($rec->timeend)) {
                        self::close_session((int) $rec->id);
                    }
                    return;
                }
            }
        }

        $codcurso = $post['CodigoCurso'] ?? '';
        if ($codcurso !== '') {
            $open = self::get_open_session(rut_helper::format_run($runraw), $codcurso);
            if ($open) {
                self::close_session((int) $open->id);
            }
        }
    }

    /**
     * @param \stdClass $record
     * @param int $maxseconds
     * @return int seconds remaining
     */
    public static function seconds_remaining(\stdClass $record, int $maxseconds): int {
        if (empty($record->firstacess) || !self::is_open_record($record)) {
            return 0;
        }
        $end = (int) $record->firstacess + $maxseconds;
        return max(0, $end - time());
    }
}
