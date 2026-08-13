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
        $maxseconds = block_moodle_sence_resolve_session_timeout($config);
        $forcer = !empty($config->forzarcierre);
        $prior = block_moodle_sence_has_prior_registration($run, $codes['codigocurso'], $codes['idaccion']);

        // Pasado el tope global (p. ej. 3 h): no cerrar en SENCE; iniciar sesión nueva.
        if ($session && session_manager::is_open_record($session)
            && block_moodle_sence_is_session_stale($session)) {
            session_manager::close_session((int) $session->id);
            $session = null;
        }

        $out = html_writer::start_div('block-moodle-sence');

        if (block_moodle_sence_is_test_mode()) {
            $out .= html_writer::span(get_string('testmode_badge', 'block_moodle_sence'), 'block-moodle-sence-badge block-moodle-sence-badge--test');
        }

        // forzarcierre=0 y ya registró: permitir curso sin gate.
        if (!$forcer && $prior > 0 && !($session && session_manager::is_open_record($session))) {
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

        if ($session && session_manager::is_open_record($session)) {
            $remaining = session_manager::seconds_remaining($session, $maxseconds);
            $expired = ($remaining <= 0);
            $alertmsg = !empty($config->alertafinal)
                ? $config->alertafinal
                : get_string('defaultalert', 'block_moodle_sence');

            $panelclass = 'block-moodle-sence-panel block-moodle-sence-panel--session';
            if ($expired) {
                $panelclass .= ' block-moodle-sence-panel--gate block-moodle-sence-panel--logout-gate';
            }

            $out .= html_writer::start_div($panelclass, [
                'id' => 'block-moodle-sence-session-panel',
                'data-expired' => $expired ? '1' : '0',
            ]);
            $out .= html_writer::div(
                html_writer::span('SENCE', 'block-moodle-sence-brand__mark') .
                html_writer::span(get_string('brand_subtitle', 'block_moodle_sence'), 'block-moodle-sence-brand__sub'),
                'block-moodle-sence-brand'
            );

            if ($expired) {
                $out .= html_writer::div(
                    html_writer::tag('p', get_string('session_expired_title', 'block_moodle_sence'), ['class' => 'block-moodle-sence__title']) .
                    html_writer::tag('p', get_string('session_expired_clickclose', 'block_moodle_sence'), ['class' => 'block-moodle-sence__text']),
                    'block-moodle-sence-card block-moodle-sence-card--danger',
                    ['role' => 'alert']
                );
            } else {
                $out .= html_writer::div(
                    html_writer::tag('p', get_string('sessionactive', 'block_moodle_sence'), ['class' => 'block-moodle-sence__title']) .
                    html_writer::tag('p', get_string('sessionactive_mustclose', 'block_moodle_sence'), ['class' => 'block-moodle-sence__text']),
                    'block-moodle-sence-card block-moodle-sence-card--success'
                );
                $out .= html_writer::tag('p', get_string('timerhelp', 'block_moodle_sence'), ['class' => 'block-moodle-sence__hint']);
            }

            $out .= html_writer::div(
                html_writer::tag('span', get_string('timerlabel', 'block_moodle_sence'), ['class' => 'block-moodle-sence-timer__label']) .
                html_writer::tag('strong', '00:00:00', ['id' => 'block-moodle-sence-countdown', 'class' => 'block-moodle-sence-timer__value']),
                'block-moodle-sence-timer' . ($expired ? ' block-moodle-sence-timer--warn' : ''),
                ['id' => 'block-moodle-sence-timer']
            );

            $logoutfields = block_moodle_sence_build_logout_fields(
                $config,
                $session,
                $COURSE->id,
                (int) $this->instance->id,
                (int) $USER->id
            );
            $out .= html_writer::tag('button', get_string('cerrarsesion', 'block_moodle_sence'), [
                'type' => 'submit',
                'form' => 'block-moodle-sence-logout-form',
                'class' => 'btn block-moodle-sence-btn block-moodle-sence-btn--primary',
            ]);
            $out .= html_writer::tag(
                'p',
                get_string('cerrarsesion_help', 'block_moodle_sence'),
                ['class' => 'block-moodle-sence__help']
            );
            $out .= html_writer::end_div();

            // Barra fija bajo el navbar: cronómetro + POST de cierre (se mueve a body).
            $out .= html_writer::start_div('block-moodle-sence-sticky', [
                'id' => 'block-moodle-sence-sticky',
                'role' => 'region',
                'aria-label' => get_string('pluginname', 'block_moodle_sence'),
                'aria-live' => 'polite',
                'data-remaining' => $remaining,
                'data-alert' => s($alertmsg),
                'data-alertat' => 900,
                'data-warnat' => 1800,
                'data-expiremsg' => get_string('session_expired_closing', 'block_moodle_sence'),
            ]);
            $out .= html_writer::div(
                html_writer::span('SENCE', 'block-moodle-sence-sticky__brand') .
                html_writer::span(get_string('timerlabel', 'block_moodle_sence'), 'block-moodle-sence-sticky__label') .
                html_writer::tag('strong', '00:00:00', [
                    'id' => 'block-moodle-sence-sticky-countdown',
                    'class' => 'block-moodle-sence-sticky__time',
                ]) .
                html_writer::span('', 'block-moodle-sence-sticky__hint'),
                'block-moodle-sence-sticky__left'
            );
            $out .= block_moodle_sence_render_post_form(
                $urls['logout'],
                $logoutfields,
                get_string('cerrarsesion', 'block_moodle_sence'),
                'block-moodle-sence-logout-form',
                'block-moodle-sence-rce-form block-moodle-sence-sticky__form',
                'btn block-moodle-sence-btn block-moodle-sence-sticky__btn'
            );
            $out .= html_writer::end_div();

            $out .= html_writer::script($this->timer_js());
            $out .= html_writer::script($this->logout_intercept_js());
            $out .= html_writer::script($this->session_keepalive_js());
            if ($expired) {
                $out .= html_writer::script($this->gate_js('.block-moodle-sence-panel--logout-gate'));
            }
        } else {
            if (!block_moodle_sence_is_valid_run($run)) {
                $editurl = block_moodle_sence_profile_rut_edit_url((int) $USER->id);
                $out .= html_writer::div(
                    html_writer::tag('p', get_string('invalidrun_title', 'block_moodle_sence'), ['class' => 'block-moodle-sence__title']) .
                    html_writer::tag('p', get_string('invalidrun', 'block_moodle_sence', $editurl->out(false)), ['class' => 'block-moodle-sence__text']),
                    'block-moodle-sence-card block-moodle-sence-card--danger',
                    ['role' => 'alert']
                );
            } else if ((int) ($config->lineasdecap ?? 3) !== 1
                && (strcasecmp(trim($config->codigocurso), 'MULTIPLES') !== 0)
                && strlen($codes['codsence']) < 5) {
                $out .= html_writer::div(
                    get_string('noconfig', 'block_moodle_sence'),
                    'block-moodle-sence-card block-moodle-sence-card--danger',
                    ['role' => 'alert']
                );
            } else if ((int) ($config->lineasdecap ?? 3) !== 1 && !$codes['fromgroup']
                && ($codes['codigocurso'] === '' || $codes['idaccion'] === '')) {
                $out .= html_writer::div(
                    get_string('nogroupaction', 'block_moodle_sence'),
                    'block-moodle-sence-card block-moodle-sence-card--danger',
                    ['role' => 'alert']
                );
            } else {
                $panelclass = $forcer
                    ? 'block-moodle-sence-panel block-moodle-sence-panel--gate'
                    : 'block-moodle-sence-panel block-moodle-sence-panel--login';

                $out .= html_writer::start_div($panelclass);
                $out .= html_writer::div(
                    html_writer::span('SENCE', 'block-moodle-sence-brand__mark') .
                    html_writer::span(get_string('brand_subtitle', 'block_moodle_sence'), 'block-moodle-sence-brand__sub'),
                    'block-moodle-sence-brand'
                );

                if ($forcer) {
                    $out .= html_writer::div(
                        html_writer::tag('p', get_string('gatetitle', 'block_moodle_sence'), ['class' => 'block-moodle-sence__title']) .
                        html_writer::tag('p', get_string('mustlogin', 'block_moodle_sence'), ['class' => 'block-moodle-sence__text']),
                        'block-moodle-sence-card block-moodle-sence-card--warning'
                    );
                    $out .= html_writer::script($this->gate_js());
                } else {
                    $out .= html_writer::tag('p', get_string('logintitle', 'block_moodle_sence'), ['class' => 'block-moodle-sence__title']);
                    $out .= html_writer::tag('p', get_string('loginsubtitle', 'block_moodle_sence'), ['class' => 'block-moodle-sence__text']);
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
                $out .= html_writer::end_div();
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
        $emails = block_moodle_sence_parse_alert_emails($correo);
        if (!empty($emails)) {
            $out .= html_writer::tag('p', get_string('managercorreo', 'block_moodle_sence', implode(', ', $emails)));
        } else {
            $out .= html_writer::tag('p', get_string('managercorreo_none', 'block_moodle_sence'));
        }

        $correorecordatorio = trim((string) ($config->correorecordatorio ?? ''));
        $emailsreminder = block_moodle_sence_parse_alert_emails($correorecordatorio);
        if (!empty($emailsreminder)) {
            $out .= html_writer::tag(
                'p',
                get_string('managercorreorecordatorio', 'block_moodle_sence', implode(', ', $emailsreminder))
            );
        } else {
            $out .= html_writer::tag('p', get_string('managercorreorecordatorio_none', 'block_moodle_sence'));
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

        $context = context_course::instance((int) $course->id);
        if (has_capability('block/moodle_sence:viewreport', $context) && !empty($this->instance->id)) {
            $reporturl = new moodle_url('/blocks/moodle_sence/report.php', [
                'courseid' => $course->id,
                'instanceid' => $this->instance->id,
            ]);
            $out .= html_writer::div(
                html_writer::link(
                    $reporturl,
                    get_string('report_open', 'block_moodle_sence'),
                    ['class' => 'btn btn-primary block-moodle-sence-btn']
                ),
                'block-moodle-sence-manager-actions mt-3'
            );
        }

        $out .= html_writer::end_div();
        return $out;
    }

    /**
     * Gate: cubre #region-main con el panel SENCE (login o cierre).
     *
     * @param string $sourceselector
     * @return string
     */
    protected function gate_js(string $sourceselector = '.block-moodle-sence-panel--gate'): string {
        $selector = json_encode($sourceselector);
        return <<<JS
(function () {
  var sourceSelector = {$selector};
  function run() {
    document.body.classList.add('block-moodle-sence-gated');
    var main = document.getElementById('region-main');
    var block = document.querySelector('.block_moodle_sence');
    if (!main || !block) {
      return;
    }
    if (document.getElementById('block-moodle-sence-gate-root')) {
      return;
    }
    var source = block.querySelector(sourceSelector)
      || block.querySelector('.block-moodle-sence-panel--gate')
      || block.querySelector('.block-moodle-sence-panel--logout-gate')
      || block.querySelector('.card-text, .content, [data-region="content"]')
      || block;
    var gate = document.createElement('div');
    gate.id = 'block-moodle-sence-gate-root';
    gate.className = 'block-moodle-sence-gate';
    gate.setAttribute('role', 'dialog');
    gate.setAttribute('aria-modal', 'true');
    gate.setAttribute('aria-label', 'SENCE');
    var shell = document.createElement('div');
    shell.className = 'block-moodle-sence-gate__shell';
    var moveNode = String(sourceSelector).indexOf('logout') !== -1;
    if (moveNode) {
      shell.appendChild(source);
    } else {
      shell.appendChild(source.cloneNode(true));
    }
    gate.appendChild(shell);
    main.innerHTML = '';
    main.appendChild(gate);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
  window.blockMoodleSenceShowGate = run;
})();
JS;
    }

    /**
     * Mueve la barra de cierre al body y la pega bajo el navbar de Moodle.
     *
     * @return string
     */
    protected function session_keepalive_js(): string {
        return <<<'JS'
(function () {
  var bar = document.getElementById('block-moodle-sence-sticky');
  if (!bar) {
    return;
  }
  function navBottom() {
    var nav = document.querySelector('.navbar.fixed-top, header.navbar.fixed-top, #page-header .navbar.fixed-top');
    if (!nav) {
      return 0;
    }
    var r = nav.getBoundingClientRect();
    return Math.max(0, Math.round(r.bottom));
  }
  function place() {
    var top = navBottom();
    bar.style.top = top + 'px';
    var h = bar.offsetHeight || 0;
    document.documentElement.style.setProperty('--bms-sticky-top', top + 'px');
    document.documentElement.style.setProperty('--bms-sticky-h', h + 'px');
  }
  function mount() {
    if (!bar.dataset.bmsMounted) {
      document.body.appendChild(bar);
      bar.dataset.bmsMounted = '1';
    }
    document.body.classList.add('block-moodle-sence-session-open');
    place();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
  } else {
    mount();
  }
  window.addEventListener('resize', place);
  window.addEventListener('scroll', place, { passive: true });
})();
JS;
    }

    /**
     * Cronómetro: alerta a 900s; al llegar a 0 bloquea pantalla y envía cierre SENCE.
     *
     * @return string
     */
    protected function timer_js(): string {
        return <<<'JS'
(function () {
  var sticky = document.getElementById('block-moodle-sence-sticky');
  var box = sticky || document.getElementById('block-moodle-sence-timer');
  var stickyEl = document.getElementById('block-moodle-sence-sticky-countdown');
  var el = document.getElementById('block-moodle-sence-countdown');
  if (!box) {
    return;
  }
  var left = parseInt(box.getAttribute('data-remaining'), 10) || 0;
  var alertAt = parseInt(box.getAttribute('data-alertat'), 10) || 900;
  var warnAt = parseInt(box.getAttribute('data-warnat'), 10) || 1800;
  var msg = box.getAttribute('data-alert') || '';
  var expireMsg = box.getAttribute('data-expiremsg') || msg;
  var startedExpired = left <= 0;
  var alerted = false;
  var closing = false;

  var pad = function (n) {
    return (n < 10 ? '0' : '') + n;
  };
  var fmt = function (u) {
    var h = Math.floor(u / 3600);
    var m = Math.floor((u % 3600) / 60);
    var s = Math.floor(u % 60);
    return pad(h) + ':' + pad(m) + ':' + pad(s);
  };
  var showBanner = function (text) {
    if (sticky) {
      sticky.classList.add('block-moodle-sence-sticky--alert');
      var hint = sticky.querySelector('.block-moodle-sence-sticky__hint');
      if (hint) {
        hint.textContent = text || msg || '';
      }
      return;
    }
    var banner = document.getElementById('alerta-cierre-sesion-sence');
    if (!banner) {
      banner = document.createElement('div');
      banner.id = 'alerta-cierre-sesion-sence';
      banner.className = 'block-moodle-sence-timeout-banner';
      document.body.appendChild(banner);
    }
    banner.innerHTML = '<h3>' + (text || msg) + '</h3>';
  };
  var showExpireGate = function () {
    document.body.classList.add('block-moodle-sence-gated', 'block-moodle-sence-session-expired');
    var panel = document.getElementById('block-moodle-sence-session-panel');
    if (panel) {
      panel.classList.add('block-moodle-sence-panel--gate', 'block-moodle-sence-panel--logout-gate');
    }
    if (typeof window.blockMoodleSenceShowGate === 'function') {
      window.blockMoodleSenceShowGate();
    } else {
      var main = document.getElementById('region-main');
      var block = document.querySelector('.block_moodle_sence');
      if (!main || !block || document.getElementById('block-moodle-sence-gate-root')) {
        return;
      }
      var source = block.querySelector('.block-moodle-sence-panel--logout-gate')
        || block.querySelector('#block-moodle-sence-session-panel')
        || block;
      var gate = document.createElement('div');
      gate.id = 'block-moodle-sence-gate-root';
      gate.className = 'block-moodle-sence-gate';
      gate.setAttribute('role', 'dialog');
      gate.setAttribute('aria-modal', 'true');
      var shell = document.createElement('div');
      shell.className = 'block-moodle-sence-gate__shell';
      shell.appendChild(source);
      gate.appendChild(shell);
      main.innerHTML = '';
      main.appendChild(gate);
    }
  };
  var submitLogout = function () {
    if (closing) {
      return;
    }
    closing = true;
    showBanner(expireMsg);
    showExpireGate();
    var form = document.getElementById('block-moodle-sence-logout-form');
    if (form) {
      window.setTimeout(function () {
        try { form.submit(); } catch (e) {}
      }, 400);
    }
  };
  var tick = function () {
    var text = left > 0 ? fmt(left) : '00:00:00';
    if (el) {
      el.textContent = text;
    }
    if (stickyEl) {
      stickyEl.textContent = text;
    }
    if (left < warnAt) {
      box.classList.add('block-moodle-sence-timer--warn');
      document.body.classList.add('block-moodle-sence-session-warn');
    } else {
      box.classList.remove('block-moodle-sence-timer--warn');
    }
    if (!alerted && left > 0 && left <= alertAt) {
      alerted = true;
      showBanner(msg);
      if (msg) {
        try { window.alert(msg); } catch (e) {}
      }
    }
    if (left <= 0) {
      // Si ya venía vencida (p. ej. sesión de ayer), no auto-POST a SENCE:
      // esa sesión suele devolver 301. El alumno cierra con el botón.
      if (startedExpired) {
        showExpireGate();
        showBanner(expireMsg);
      } else {
        submitLogout();
      }
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
