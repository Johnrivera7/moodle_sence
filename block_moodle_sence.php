<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

use block_moodle_sence\session_manager;

/**
 * Bloque Registro Asistencia SENCE (MIT, manual v1.1.6).
 *
 * @package    block_moodle_sence
 * @copyright  2026 John Rivera
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_moodle_sence extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_moodle_sence');
    }

    /**
     * Habilita la página de ajustes globales (settings.php).
     */
    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        return [
            'all' => false,
            'site' => false,
            'site-index' => false,
            'course-view' => true,
            'mod' => true,
            'mod-quiz' => false,
        ];
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function get_content() {
        global $USER, $COURSE, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($USER->id) || isguestuser($USER)) {
            $this->content->text = $OUTPUT->notification(get_string('loginrequired', 'block_moodle_sence'), 'info');
            return $this->content;
        }

        $config = $this->config ?? new stdClass();
        $context = context_course::instance($COURSE->id);

        // Panel gestor (docentes / managers).
        if (has_capability('moodle/course:viewhiddenactivities', $context)) {
            $this->content->text = $this->render_manager_panel($config, $COURSE, $OUTPUT);
            $PAGE->requires->css('/blocks/moodle_sence/styles.css');
            return $this->content;
        }

        if (empty($config->codigocurso)) {
            $this->content->text = $OUTPUT->notification(get_string('noconfig', 'block_moodle_sence'), 'warning');
            return $this->content;
        }

        if (block_moodle_sence_get_token() === '' || block_moodle_sence_get_rut_otec() === '') {
            $this->content->text = $OUTPUT->notification(get_string('notoken', 'block_moodle_sence'), 'error');
            return $this->content;
        }

        // Becarios: mensaje amigable, sin formularios RCE.
        if (block_moodle_sence_user_is_becado($config, $COURSE->id, $USER->id)) {
            $out = html_writer::start_div('block-moodle-sence block-moodle-sence--becado');
            $out .= html_writer::div(
                html_writer::tag('p', get_string('becado_title', 'block_moodle_sence'), ['class' => 'block-moodle-sence__title']) .
                html_writer::tag('p', get_string('becado_message', 'block_moodle_sence'), ['class' => 'block-moodle-sence__text']),
                'block-moodle-sence-card block-moodle-sence-card--info'
            );
            $out .= html_writer::end_div();
            $this->content->text = $out;
            $PAGE->requires->css('/blocks/moodle_sence/styles.css');
            return $this->content;
        }

        $run = block_moodle_sence_resolve_user_run($USER);
        $codes = block_moodle_sence_resolve_runtime_codes($config, $COURSE->id, $USER->id);
        $session = session_manager::get_open_session($run, $codes['codigocurso']);
        $urls = block_moodle_sence_get_rce_urls();
        $maxseconds = (int) ($config->sencetimeout ?? 10800);
        $forcer = !empty($config->forzarcierre);
        $prior = block_moodle_sence_has_prior_registration($run, $codes['codigocurso'], $codes['idaccion']);

        $out = html_writer::start_div('block-moodle-sence');

        if (block_moodle_sence_is_test_mode()) {
            $out .= html_writer::span(get_string('testmode_badge', 'block_moodle_sence'), 'block-moodle-sence-badge block-moodle-sence-badge--test');
        }

        // forzarcierre=0 y ya registró: permitir curso sin gate.
        if (!$forcer && $prior > 0 && !($session && !empty($session->idsesionsence))) {
            $out .= html_writer::div(
                html_writer::tag('p', get_string('alreadyregistered_title', 'block_moodle_sence'), ['class' => 'block-moodle-sence__title']) .
                html_writer::tag('p', get_string('alreadyregistered', 'block_moodle_sence'), ['class' => 'block-moodle-sence__text']),
                'block-moodle-sence-card block-moodle-sence-card--success'
            );
            $out .= html_writer::end_div();
            $this->content->text = $out;
            $PAGE->requires->css('/blocks/moodle_sence/styles.css');
            return $this->content;
        }

        if ($session && !empty($session->idsesionsence)) {
            $remaining = session_manager::seconds_remaining($session, $maxseconds);
            $alertmsg = !empty($config->alertafinal)
                ? $config->alertafinal
                : get_string('defaultalert', 'block_moodle_sence');

            $out .= html_writer::div(
                get_string('sessionactive', 'block_moodle_sence'),
                'block-moodle-sence-card block-moodle-sence-card--success'
            );
            $out .= html_writer::tag('p', get_string('timerhelp', 'block_moodle_sence'), ['class' => 'block-moodle-sence__hint']);

            $out .= html_writer::div(
                html_writer::tag('span', get_string('timerlabel', 'block_moodle_sence'), ['class' => 'block-moodle-sence-timer__label']) .
                html_writer::tag('strong', '00:00:00', ['id' => 'block-moodle-sence-countdown', 'class' => 'block-moodle-sence-timer__value']),
                'block-moodle-sence-timer',
                [
                    'id' => 'block-moodle-sence-timer',
                    'data-remaining' => $remaining,
                    'data-alert' => s($alertmsg),
                    'data-alertat' => 900,
                    'data-warnat' => 1800,
                ]
            );

            $out .= html_writer::script($this->timer_js());

            $logoutfields = block_moodle_sence_build_logout_fields(
                $config,
                $session,
                $COURSE->id,
                (int) $this->instance->id,
                (int) $USER->id
            );
            $out .= block_moodle_sence_render_post_form(
                $urls['logout'],
                $logoutfields,
                get_string('cerrarsesion', 'block_moodle_sence'),
                'block-moodle-sence-logout-form'
            );
            $out .= html_writer::script($this->logout_intercept_js());
        } else {
            if (!block_moodle_sence_is_valid_run($run)) {
                $editurl = new moodle_url('/user/edit.php', ['id' => $USER->id]);
                $out .= html_writer::div(
                    get_string('invalidrun', 'block_moodle_sence', $editurl->out(false)),
                    'alert alert-danger block-moodle-sence-card block-moodle-sence-card--danger',
                    ['role' => 'alert']
                );
            } else if ((int) ($config->lineasdecap ?? 3) !== 1
                && (strcasecmp(trim($config->codigocurso), 'MULTIPLES') !== 0)
                && strlen($codes['codsence']) < 5) {
                $out .= html_writer::div(
                    get_string('noconfig', 'block_moodle_sence'),
                    'alert alert-danger',
                    ['role' => 'alert']
                );
            } else if ((int) ($config->lineasdecap ?? 3) !== 1 && !$codes['fromgroup']
                && ($codes['codigocurso'] === '' || $codes['idaccion'] === '')) {
                $out .= html_writer::div(
                    get_string('nogroupaction', 'block_moodle_sence'),
                    'alert alert-danger',
                    ['role' => 'alert']
                );
            } else {
                if ($forcer) {
                    $out .= html_writer::div(
                        html_writer::tag('p', get_string('gatetitle', 'block_moodle_sence'), ['class' => 'block-moodle-sence__title']) .
                        html_writer::tag('p', get_string('mustlogin', 'block_moodle_sence'), ['class' => 'block-moodle-sence__text']),
                        'block-moodle-sence-card block-moodle-sence-card--warning'
                    );
                    $out .= html_writer::script($this->gate_js());
                }

                $loginfields = block_moodle_sence_build_login_fields(
                    $config,
                    $COURSE->id,
                    (int) $this->instance->id,
                    $USER
                );
                $out .= block_moodle_sence_render_post_form(
                    $urls['login'],
                    $loginfields,
                    get_string('iniciarsesion', 'block_moodle_sence')
                );
                $out .= html_writer::tag(
                    'p',
                    get_string('claveunicahelp', 'block_moodle_sence'),
                    ['class' => 'block-moodle-sence__help']
                );
            }
        }

        $out .= html_writer::end_div();
        $this->content->text = $out;
        $PAGE->requires->css('/blocks/moodle_sence/styles.css');

        return $this->content;
    }

    /**
     * @param \stdClass $config
     * @param \stdClass $course
     * @param \core_renderer $output
     * @return string
     */
    protected function render_manager_panel(\stdClass $config, \stdClass $course, $output): string {
        $out = html_writer::start_div('block-moodle-sence block-moodle-sence--manager');
        $out .= html_writer::tag('h5', get_string('managertitle', 'block_moodle_sence'), ['class' => 'block-moodle-sence__title']);

        if (block_moodle_sence_is_test_mode()) {
            $out .= html_writer::span(get_string('testmode_badge', 'block_moodle_sence'), 'block-moodle-sence-badge block-moodle-sence-badge--test');
        }

        $cod = trim((string) ($config->codigocurso ?? ''));
        $linea = (int) ($config->lineasdecap ?? 3);

        if ($cod === '') {
            $out .= $output->notification(get_string('noconfig', 'block_moodle_sence'), 'error');
        } else if (strlen($cod) !== 10 && strcasecmp($cod, 'MULTIPLES') !== 0 && $linea === 3 && strpos($cod, '/') === false) {
            $out .= $output->notification(get_string('codigocurso_invalid', 'block_moodle_sence'), 'error');
        } else {
            $out .= html_writer::tag('p', get_string('managercode', 'block_moodle_sence', (object) [
                'shortname' => $course->shortname,
                'code' => $cod,
            ]));
        }

        $out .= html_writer::tag('p', get_string('grouphint', 'block_moodle_sence'));

        $becas = trim((string) ($config->grupobecas ?? ''));
        if ($becas !== '') {
            $out .= html_writer::tag('p', get_string('managerbecas', 'block_moodle_sence', $becas));
        } else {
            $out .= html_writer::tag('p', get_string('managerbecas_none', 'block_moodle_sence'));
        }

        $correo = trim((string) ($config->correoalerta ?? ''));
        if ($correo !== '') {
            $out .= html_writer::tag('p', get_string('managercorreo', 'block_moodle_sence', $correo));
        } else {
            $out .= html_writer::tag('p', get_string('managercorreo_none', 'block_moodle_sence'));
        }

        if (!empty($config->forzarcierre)) {
            $out .= html_writer::tag('p', get_string('managerforce_on', 'block_moodle_sence'));
        } else {
            $out .= html_writer::tag('p', get_string('managerforce_off', 'block_moodle_sence'));
        }

        $out .= html_writer::tag('dl',
            html_writer::tag('dt', get_string('lineasdecap', 'block_moodle_sence')) .
            html_writer::tag('dd', (string) $linea) .
            html_writer::tag('dt', get_string('rutotec', 'block_moodle_sence')) .
            html_writer::tag('dd', s(block_moodle_sence_get_rut_otec())),
            ['class' => 'block-moodle-sence-codes']
        );

        $out .= html_writer::end_div();
        return $out;
    }

    /**
     * Gate: muestra el bloque cubriendo #region-main hasta registrar asistencia.
     *
     * @return string
     */
    protected function gate_js(): string {
        return <<<'JS'
(function () {
  function run() {
    document.body.classList.add('block-moodle-sence-gated');
    var main = document.getElementById('region-main');
    var block = document.querySelector('.block_moodle_sence');
    if (!main || !block) {
      return;
    }
    var source = block.querySelector('.card-text, .content, [data-region="content"]') || block;
    var gate = document.createElement('div');
    gate.className = 'block-moodle-sence-gate';
    gate.setAttribute('role', 'dialog');
    gate.setAttribute('aria-label', 'SENCE');
    var heading = document.createElement('div');
    heading.className = 'block-moodle-sence-gate__banner';
    heading.textContent = source.querySelector('.block-moodle-sence__title')
      ? source.querySelector('.block-moodle-sence__title').textContent
      : 'SENCE';
    gate.appendChild(heading);
    gate.appendChild(source.cloneNode(true));
    main.innerHTML = '';
    main.appendChild(gate);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
JS;
    }

    /**
     * Cronómetro: alerta a 900s; color a 1800s restantes.
     *
     * @return string
     */
    protected function timer_js(): string {
        return <<<'JS'
(function () {
  var box = document.getElementById('block-moodle-sence-timer');
  var el = document.getElementById('block-moodle-sence-countdown');
  if (!box || !el) {
    return;
  }
  var left = parseInt(box.getAttribute('data-remaining'), 10) || 0;
  var alertAt = parseInt(box.getAttribute('data-alertat'), 10) || 900;
  var warnAt = parseInt(box.getAttribute('data-warnat'), 10) || 1800;
  var msg = box.getAttribute('data-alert') || '';
  var alerted = false;

  var pad = function (n) {
    return (n < 10 ? '0' : '') + n;
  };
  var fmt = function (u) {
    var h = Math.floor(u / 3600);
    var m = Math.floor((u % 3600) / 60);
    var s = Math.floor(u % 60);
    return pad(h) + ':' + pad(m) + ':' + pad(s);
  };
  var showBanner = function () {
    if (document.getElementById('alerta-cierre-sesion-sence')) {
      return;
    }
    var banner = document.createElement('div');
    banner.id = 'alerta-cierre-sesion-sence';
    banner.className = 'block-moodle-sence-timeout-banner';
    banner.innerHTML = '<h3>' + msg + '</h3>';
    document.body.appendChild(banner);
  };
  var tick = function () {
    el.textContent = left > 0 ? fmt(left) : '00:00:00';
    if (left < warnAt) {
      box.classList.add('block-moodle-sence-timer--warn');
    } else {
      box.classList.remove('block-moodle-sence-timer--warn');
    }
    if (!alerted && left > 0 && left <= alertAt) {
      alerted = true;
      showBanner();
      if (msg) {
        try { window.alert(msg); } catch (e) {}
      }
    }
    if (left <= 0) {
      return;
    }
    left -= 1;
    window.setTimeout(tick, 1000);
  };
  tick();
})();
JS;
    }

    /**
     * Intercepta enlaces de logout de Moodle para cerrar sesión SENCE primero.
     *
     * @return string
     */
    protected function logout_intercept_js(): string {
        return <<<'JS'
(function () {
  function bind() {
    var form = document.getElementById('block-moodle-sence-logout-form');
    if (!form) {
      return;
    }
    document.addEventListener('click', function (e) {
      var a = e.target.closest ? e.target.closest('a[href*="logout"]') : null;
      if (!a) {
        return;
      }
      e.preventDefault();
      form.submit();
    }, true);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
JS;
    }
}
