<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'SENCE attendance registration';
$string['moodle_sence:addinstance'] = 'Add a SENCE attendance block';
$string['moodle_sence:myaddinstance'] = 'Add a SENCE attendance block to Dashboard';
$string['codigocurso'] = 'Course codes (CodSence/CodigoCurso)';
$string['codigocurso_help'] = 'Format: CodSence/CodigoCurso (e.g. 1234567890/MY-COURSE-01), 10 digits only, or MULTIPLES (CodSence from group description). Action ID usually comes from group name SENCE-XXXXXXX.';
$string['codigocurso_invalid'] = 'Course SENCE code must be 10 digits, CodSence/CodigoCurso, or MULTIPLES.';
$string['grupobecas'] = 'Scholarship groups (comma separated)';
$string['alertafinal'] = 'Final session alert message';
$string['correoalerta'] = 'Error alert emails';
$string['correoalerta_help'] = 'One or more emails separated by comma, semicolon or space. Example: support@otec.cl, coordinator@otec.cl';
$string['correorecordatorio'] = 'Reminder CC emails';
$string['correorecordatorio_help'] = 'One or more emails that receive a visible CC when a SENCE attendance reminder is sent. The user who clicks Remind gets a BCC. Separate with comma, semicolon or space.';
$string['iniciarsesion'] = 'Sign in with ClaveÚnica';
$string['cerrarsesion'] = 'End SENCE session';
$string['cerrarsesion_help'] = 'When you finish the class or study period, you must <strong>end the SENCE session</strong> with ClaveÚnica. Without logout, attendance may remain incomplete in SENCE.';
$string['forzarcierre'] = 'Require login and logout';
$string['sesionmax'] = 'Maximum session duration';
$string['lineasdecap'] = 'Training line';
$string['linea1'] = 'Social programs (1)';
$string['linea3'] = 'Tax franchise (3)';
$string['linea6'] = 'FTP (6)';
$string['codsence'] = 'SENCE code';
$string['settingsheading'] = 'SENCE RCE credentials';
$string['settingsheading_desc'] = 'Token from https://sistemas.sence.cl/rts. May reuse local_moodle_sicsence credentials.';
$string['rutotec'] = 'OTEC RUT';
$string['rutotec_desc'] = 'Format xxxxxxxx-x';
$string['tokenotec'] = 'OTEC token';
$string['tokenotec_desc'] = 'SENCE issuer token';
$string['testmode'] = 'Test environment (rcetest)';
$string['testmode_desc'] = 'Uses rcetest URLs; CodSence/CodigoCurso may be -1 for testing.';
$string['testmode_badge'] = 'Test environment';
$string['loginrequired'] = 'You must be logged in.';
$string['noidnumber'] = 'Your profile must include the RUT custom field (profile_field_rut).';
$string['invalidrun_title'] = 'RUT required';
$string['invalidrun'] = 'RUT missing or invalid. Please <a href="{$a}">enter it in your profile RUT field</a> (unique per user).';
$string['brand_subtitle'] = 'Attendance registration';
$string['logintitle'] = 'Sign in to SENCE';
$string['loginsubtitle'] = 'You will be redirected to ClaveÚnica to register course attendance.';
$string['noconfig'] = 'Configure course code in block settings.';
$string['nogroupaction'] = 'The administrator must set the Action ID in your group name (SENCE-XXXXXXX).';
$string['notoken'] = 'Configure OTEC RUT and token in block settings.';
$string['mustlogin'] = 'You must start a SENCE session before accessing course content.';
$string['gatetitle'] = 'SENCE attendance registration required';
$string['sessionactive'] = 'Active SENCE session';
$string['sessionactive_mustclose'] = 'Remember to end the SENCE session when finished. The red <strong>End SENCE session</strong> button is below in this block.';
$string['session_expired_closing'] = 'Session time expired. Redirecting to end SENCE session with ClaveÚnica…';
$string['timerlabel'] = 'Time remaining';
$string['timerhelp'] = 'When the timer reaches zero, SENCE logout will start automatically (you must confirm with ClaveÚnica).';
$string['claveunicahelp'] = 'Your <strong>ClaveÚnica</strong> will be requested. Get or recover it at the <a href="https://claveunica.gob.cl/" target="_blank" rel="noopener">Citizen Portal</a>.';
$string['callbacktitle'] = 'SENCE callback';
$string['invalidcallback'] = 'Invalid callback link.';
$string['senceerror'] = 'SENCE error (code {$a}).';
$string['senceerrorfull'] = 'Error: {$a->message} ({$a->code}). Contact platform support.';
$string['senceerror_detail_html'] = '<div class="block-moodle-sence-error-card"><h3>SENCE error {$a->code}</h3><p class="block-moodle-sence-error-card__msg"><strong>{$a->message}</strong></p>{$a->tip}<dl class="block-moodle-sence-codes"><dt>Participant</dt><dd>{$a->fullname} — RUT {$a->run}</dd><dt>Moodle course</dt><dd>{$a->coursename} ({$a->shortname})</dd><dt>Course link</dt><dd><a href="{$a->courseurl}">{$a->courseurl}</a></dd><dt>Group(s)</dt><dd>{$a->groups}</dd><dt>CodSence</dt><dd>{$a->codsence}</dd><dt>Course code / Action ID</dt><dd>{$a->codigocurso} / {$a->idaccion}</dd><dt>OTEC</dt><dd>{$a->otec}</dd><dt>Line</dt><dd>{$a->linea}</dd><dt>Date / zone</dt><dd>{$a->fechahora} {$a->zona}</dd><dt>IdSesionAlumno</dt><dd>{$a->idsesionalumno}</dd></dl></div>';
$string['senceerror_detail_text'] = 'SENCE error {$a->code}: {$a->message}

What to check: {$a->tip}

Participant: {$a->fullname}
RUT: {$a->run}
Moodle course: {$a->coursename} ({$a->shortname})
Link: {$a->courseurl}
Group(s): {$a->groups}
CodSence: {$a->codsence}
Course code / Action ID: {$a->codigocurso} / {$a->idaccion}
OTEC: {$a->otec}
Line: {$a->linea}
Date/zone: {$a->fechahora} {$a->zona}
IdSesionAlumno: {$a->idsesionalumno}';
$string['alert_email_subject'] = 'SENCE alert {$a->code} — {$a->shortname}';
$string['alert_email_sence_support'] = 'Report to controlelearning@sence.cl with the error code.';
$string['glosa208_tip'] = 'The RUT is not on the authorised SENCE roster for this action. Check OC/OTIC/LCE and that the learner is in the correct SENCE-XXXX group.';
$string['glosa209_tip'] = 'Check the OTEC RUT configured in the block / plugin settings.';
$string['glosa211_tip'] = 'Renew the OTEC token in SENCE RTS and update it in Moodle.';
$string['glosa212_tip'] = 'The OTEC token expired. Generate a new one in RTS and save it in Moodle.';
$string['glosa306_tip'] = 'CodSence and course code do not match in SENCE. In MULTIPLES mode, the group description must be the real 10-digit CodSence, not the FQ.';
$string['glosa308_tip'] = 'The course code is not linked to the configured OTEC RUT.';
$string['glosa309_tip'] = 'Today is outside the communicated execution dates for this action. Wait for the start date or check LCE/OC dates.';
$string['glosa310_tip'] = 'The action is finished or cancelled in SENCE.';
$string['glosa311_tip'] = 'ClaveÚnica RUT does not match the Moodle profile RUT field.';
$string['report_title'] = 'SENCE attendance report';
$string['report_open'] = 'Open SENCE attendance report';
$string['report_intro'] = 'Participants in <strong>{$a->course}</strong> (<code>{$a->shortname}</code>). Link: <a href="{$a->url}">{$a->url}</a>. OK = attendance recorded; Error = RCE failed; Course without SENCE = entered Moodle course but did not start SENCE session; Never = no access yet.';
$string['report_filter_all'] = 'All';
$string['report_download_csv'] = 'Download CSV';
$string['report_empty'] = 'No participants for this filter.';
$string['report_col_name'] = 'Participant';
$string['report_col_run'] = 'RUT';
$string['report_col_groups'] = 'Groups';
$string['report_col_codes'] = 'Codes';
$string['report_col_status'] = 'Status';
$string['report_col_courseaccess'] = 'Course access';
$string['report_col_sence'] = 'SENCE session';
$string['report_col_detail'] = 'Detail';
$string['report_no_access'] = 'No access';
$string['report_error_code'] = 'Error {$a}';
$string['report_status_ok'] = 'Attendance OK';
$string['report_status_error'] = 'SENCE error';
$string['report_status_course_no_sence'] = 'Course without SENCE';
$string['report_status_never'] = 'No access yet';
$string['report_status_becado'] = 'Scholarship';
$string['report_status_nogroup'] = 'No SENCE group';
$string['report_tip_course_no_sence'] = 'Entered the Moodle course but did not complete the SENCE block login.';
$string['report_tip_never'] = 'No course access and no SENCE attempt yet.';
$string['report_tip_nogroup'] = 'Must be assigned to a SENCE-XXXXXXX group (idnumber = action ID).';
$string['reminder_button'] = 'Send reminder ({$a})';
$string['reminder_button_help'] = 'Emails participants who have not recorded SENCE attendance yet.';
$string['reminder_help'] = 'Use <strong>Remind</strong> on each pending row. The email goes to the participant; <strong>CC</strong> goes to block-configured addresses; you get a <strong>BCC</strong>. Not used for OK attendance or scholarship users.';
$string['reminder_scope_pending'] = 'pending SENCE attendance';
$string['reminder_confirm'] = 'Send a reminder email to <strong>{$a->count}</strong> participant(s) ({$a->scope}) in <strong>{$a->course}</strong>?';
$string['reminder_confirm_user'] = 'Send a reminder to <strong>{$a->fullname}</strong> (&lt;{$a->email}&gt;)?<br>Status: {$a->status}<br>Course: {$a->course}<br><br><strong>CC:</strong> {$a->cc}<br><strong>BCC:</strong> {$a->sender} — {$a->senderemail}';
$string['reminder_user_button'] = 'Remind';
$string['reminder_user_button_help'] = 'Send reminder to this participant (configured CC, BCC to you)';
$string['reminder_user_missing'] = 'Participant not found.';
$string['reminder_user_not_pending'] = 'This participant does not need a reminder (already OK or scholarship).';
$string['reminder_user_sent'] = 'Reminder sent to {$a->fullname} ({$a->email}). CC: {$a->cc}. BCC: {$a->sender}.';
$string['reminder_user_skipped'] = 'Could not send: user has no email or is not eligible.';
$string['reminder_user_failed'] = 'Failed to send reminder to {$a}.';
$string['reminder_preview_more'] = '… and {$a} more';
$string['reminder_result'] = 'Reminders sent: {$a->sent}. Skipped: {$a->skipped}. Failed: {$a->failed}.';
$string['reminder_cc_none'] = '(none configured)';
$string['reminder_cc_subject'] = '[Copy] {$a->subject} — sent to {$a->fullname}';
$string['reminder_cc_preamble'] = 'Copy of the reminder sent to {$a->fullname} <{$a->email}>.';
$string['reminder_bcc_subject'] = '[BCC] {$a->subject} — sent to {$a->fullname}';
$string['managercorreorecordatorio'] = 'Attendance reminders will CC: {$a}';
$string['managercorreorecordatorio_none'] = 'No reminder CC emails configured (sender gets BCC only).';
$string['report_col_action'] = 'Action';
$string['reminder_subject'] = 'Reminder: register your SENCE attendance — {$a->shortname}';
$string['reminder_body_text'] = 'Hello {$a->fullname},

Please register your SENCE attendance in the course:
{$a->coursename} ({$a->shortname})

Open the course and sign in with ClaveÚnica using the SENCE attendance block:
{$a->courseurl}

Current status: {$a->status}
Group(s): {$a->groups}
RUT: {$a->run}

If you already registered attendance, ignore this message.
';
$string['reminder_body_html'] = '<p>Hello <strong>{$a->fullname}</strong>,</p><p>Please register your <strong>SENCE attendance</strong> in the course:</p><p><strong>{$a->coursename}</strong> (<code>{$a->shortname}</code>)</p><p>Open the course and sign in with <strong>ClaveÚnica</strong> using the <em>SENCE attendance</em> block:</p><p><a href="{$a->courseurl}">{$a->courseurl}</a></p><ul><li>Current status: {$a->status}</li><li>Group(s): {$a->groups}</li><li>RUT: {$a->run}</li></ul><p>If you already registered attendance, ignore this message.</p>';
$string['moodle_sence:viewreport'] = 'View course SENCE attendance report';
$string['loginsuccess'] = 'SENCE login recorded successfully. Redirecting to course…';
$string['logoutsuccess'] = 'SENCE logout recorded successfully. Redirecting to course…';
$string['backtocourse'] = 'Back to course';
$string['defaultalert'] = 'Please close your SENCE session within the next 15 minutes';
$string['becado_title'] = 'You may continue';
$string['becado_message'] = 'SENCE registration is not required (scholarship group).';
$string['alreadyregistered_title'] = 'You may continue';
$string['alreadyregistered'] = 'You have already registered your SENCE attendance.';
$string['managertitle'] = 'Manager: SENCE participant integration';
$string['managercode'] = 'Configured SENCE code for {$a->shortname}: {$a->code}';
$string['grouphint'] = 'Assign the action/program ID in participant group names as SENCE-XXXXXXX (group idnumber is also accepted).';
$string['managerbecas'] = 'Users in group: {$a} are not required to integrate with SENCE.';
$string['managerbecas_none'] = 'No scholarship groups configured.';
$string['managercorreo'] = 'Error alert emails will be sent to: {$a}';
$string['managercorreo_none'] = 'No error alert emails will be sent.';
$string['managerforce_on'] = 'Participants will be required to close their SENCE session.';
$string['managerforce_off'] = 'Participants will <strong>not</strong> be required to close their SENCE session.';
$string['glosa100'] = 'Dear participant: <strong>SENCE reports an error with your Clave Única.</strong> Please recover it via the <a href="https://claveunica.gob.cl/" target="_blank" rel="noopener">Citizen Portal</a>.';
$string['glosa200'] = 'Empty parameters';
$string['glosa201'] = 'UrlError parameter has no data';
$string['glosa202'] = 'UrlError parameter has incorrect format';
$string['glosa203'] = 'UrlRetoma parameter has incorrect format';
$string['glosa204'] = 'SENCE code has incorrect format';
$string['glosa205'] = 'Course code has incorrect format';
$string['glosa206'] = 'Incorrect training line';
$string['glosa207'] = 'Incorrect RunAlumno parameter';
$string['glosa208'] = 'Student RUN not authorised for this course.';
$string['glosa209'] = 'OTEC RUT is incorrect';
$string['glosa210'] = 'SENCE session expired. Please try again.';
$string['glosa211'] = 'Token does not match the company';
$string['glosa212'] = 'Token expired';
$string['glosa300'] = 'Internal SENCE error';
$string['glosa301'] = 'Incorrect Action/Folio ID or training line';
$string['glosa302'] = 'Internal SENCE error';
$string['glosa303'] = 'Token missing or incorrect format';
$string['glosa304'] = 'Internal SENCE error';
$string['glosa305'] = 'Internal SENCE error';
$string['glosa306'] = 'Course code does not match SENCE code';
$string['glosa307'] = 'Course code is not e-learning modality';
$string['glosa308'] = 'Course code does not match OTEC RUT.';
$string['glosa309'] = 'Communicated execution dates for the course code do not match today.';
$string['glosa310'] = 'Course code is Finished or Cancelled.';
$string['glosa311'] = 'RUT from Clave Única login does not match the platform user RUT';
$string['glosa312'] = 'Could not complete Clave Única authentication.';
