<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Registro Asistencia SENCE';
$string['moodle_sence:addinstance'] = 'Añadir un bloque Registro Asistencia SENCE';
$string['moodle_sence:myaddinstance'] = 'Añadir un bloque Registro Asistencia SENCE al Área personal';
$string['codigocurso'] = 'Códigos curso (CodSence/CodigoCurso)';
$string['codigocurso_help'] = 'Formato: CodSence/CodigoCurso (ej. 1234567890/MI-CURSO-01), solo 10 dígitos, o MULTIPLES (CodSence desde descripción del grupo). El ID de acción suele venir del grupo SENCE-XXXXXXX.';
$string['codigocurso_invalid'] = 'El Código SENCE del curso debe tener 10 dígitos, formato CodSence/CodigoCurso, o MULTIPLES.';
$string['grupobecas'] = 'Grupos becarios (separados por coma)';
$string['alertafinal'] = 'Mensaje alerta final de sesión';
$string['correoalerta'] = 'Correo alertas de error';
$string['iniciarsesion'] = 'Iniciar sesión con ClaveÚnica';
$string['cerrarsesion'] = 'Cerrar sesión SENCE';
$string['forzarcierre'] = 'Exigir inicio y cierre de sesión';
$string['sesionmax'] = 'Duración máxima de sesión';
$string['lineasdecap'] = 'Línea de capacitación';
$string['linea1'] = 'Programas Sociales o Becas (1)';
$string['linea3'] = 'Franquicia Tributaria (3)';
$string['linea6'] = 'Formación para el trabajo FTP (6)';
$string['codsence'] = 'Código SENCE';
$string['settingsheading'] = 'Credenciales RCE SENCE';
$string['settingsheading_desc'] = 'Token vigente desde https://sistemas.sence.cl/rts. También puede usar credenciales de local_moodle_sicsence si están configuradas.';
$string['rutotec'] = 'RUT OTEC';
$string['rutotec_desc'] = 'Formato xxxxxxxx-x';
$string['tokenotec'] = 'Token OTEC';
$string['tokenotec_desc'] = 'Token UUID del emisor SENCE';
$string['testmode'] = 'Ambiente de prueba (rcetest)';
$string['testmode_desc'] = 'Usa URLs rcetest; CodSence/CodigoCurso pueden ser -1 si no tiene códigos vigentes.';
$string['testmode_badge'] = 'Ambiente de prueba';
$string['loginrequired'] = 'Debe iniciar sesión en Moodle.';
$string['noidnumber'] = 'Su usuario debe tener RUT en el campo de perfil RUT (profile_field_rut).';
$string['invalidrun_title'] = 'Falta su RUT';
$string['invalidrun'] = 'RUT no configurado o incorrecto. Por favor, <a href="{$a}">ingréselo en el campo RUT de su perfil</a> (único por usuario).';
$string['brand_subtitle'] = 'Registro de asistencia';
$string['logintitle'] = 'Inicie sesión en SENCE';
$string['loginsubtitle'] = 'Será redirigido a ClaveÚnica para registrar su asistencia en el curso.';
$string['noconfig'] = 'Configure el código de curso en los ajustes del bloque.';
$string['nogroupaction'] = 'El administrador debe configurar el ID de Acción en el nombre de su grupo (SENCE-XXXXXXX).';
$string['notoken'] = 'Configure RUT OTEC y Token en Administración → Plugins → Bloques → Moodle SENCE.';
$string['mustlogin'] = 'Debe iniciar sesión SENCE antes de acceder al contenido del curso.';
$string['gatetitle'] = 'Registro de asistencia SENCE requerido';
$string['sessionactive'] = 'Sesión SENCE activa';
$string['timerlabel'] = 'Tiempo restante';
$string['timerhelp'] = 'Recuerde cerrar su sesión antes de que el cronómetro llegue a cero.';
$string['claveunicahelp'] = 'Será solicitada su <strong>ClaveÚnica</strong>. Para obtenerla o recuperarla ingrese al <a href="https://claveunica.gob.cl/" target="_blank" rel="noopener">Portal Ciudadano</a>.';
$string['callbacktitle'] = 'Retorno SENCE';
$string['invalidcallback'] = 'Enlace de retorno no válido.';
$string['senceerror'] = 'Error SENCE (código {$a}). Revise Anexo 2 del manual técnico.';
$string['senceerrorfull'] = 'Error: {$a->message} ({$a->code}). Informe a soporte de plataforma.';
$string['senceerror_detail_html'] = '<div class="block-moodle-sence-error-card"><h3>Error SENCE {$a->code}</h3><p class="block-moodle-sence-error-card__msg"><strong>{$a->message}</strong></p>{$a->tip}<dl class="block-moodle-sence-codes"><dt>Participante</dt><dd>{$a->fullname} — RUT {$a->run}</dd><dt>Curso Moodle</dt><dd>{$a->coursename} ({$a->shortname})</dd><dt>Enlace del curso</dt><dd><a href="{$a->courseurl}">{$a->courseurl}</a></dd><dt>Grupo(s)</dt><dd>{$a->groups}</dd><dt>CodSence</dt><dd>{$a->codsence}</dd><dt>Código curso / ID acción</dt><dd>{$a->codigocurso} / {$a->idaccion}</dd><dt>OTEC</dt><dd>{$a->otec}</dd><dt>Línea</dt><dd>{$a->linea}</dd><dt>Fecha / zona</dt><dd>{$a->fechahora} {$a->zona}</dd><dt>IdSesionAlumno</dt><dd>{$a->idsesionalumno}</dd></dl></div>';
$string['senceerror_detail_text'] = 'Error SENCE {$a->code}: {$a->message}

Qué revisar: {$a->tip}

Participante: {$a->fullname}
RUT: {$a->run}
Curso Moodle: {$a->coursename} ({$a->shortname})
Enlace: {$a->courseurl}
Grupo(s): {$a->groups}
CodSence: {$a->codsence}
Código curso / ID acción: {$a->codigocurso} / {$a->idaccion}
OTEC: {$a->otec}
Línea: {$a->linea}
Fecha/zona: {$a->fechahora} {$a->zona}
IdSesionAlumno: {$a->idsesionalumno}';
$string['alert_email_subject'] = 'Alerta SENCE {$a->code} — {$a->shortname}';
$string['alert_email_sence_support'] = 'Reportar a controlelearning@sence.cl indicando el código de error.';
$string['glosa208_tip'] = 'El RUT no está en la nómina autorizada de esa acción en SENCE. Verifique OC/OTIC/LCE y que el alumno esté en el grupo SENCE-XXXX correcto (no en otra sociedad o ID de acción).';
$string['glosa209_tip'] = 'Revise el RUT OTEC configurado en el bloque / ajustes del plugin.';
$string['glosa211_tip'] = 'Renueve el token OTEC en RTS SENCE y actualícelo en Moodle.';
$string['glosa212_tip'] = 'El token OTEC caducó. Genere uno nuevo en RTS y guárdelo en Moodle.';
$string['glosa306_tip'] = 'CodSence y Código Curso no coinciden en SENCE. En modo MULTIPLES, la descripción del grupo debe ser el CodSence real (10 dígitos), no el FQ.';
$string['glosa308_tip'] = 'El Código Curso no está asociado al RUT OTEC configurado.';
$string['glosa309_tip'] = 'Hoy está fuera de las fechas de ejecución comunicadas a SENCE para esa acción. Espere al día de inicio o revise fechas en LCE/OC.';
$string['glosa310_tip'] = 'La acción está terminada o anulada en SENCE.';
$string['glosa311_tip'] = 'El RUT de ClaveÚnica no coincide con el RUT del perfil Moodle (profile_field_rut).';
$string['report_title'] = 'Reporte asistencia SENCE';
$string['report_open'] = 'Ver reporte de asistencia SENCE';
$string['report_intro'] = 'Participantes del curso <strong>{$a->course}</strong> (<code>{$a->shortname}</code>). Enlace: <a href="{$a->url}">{$a->url}</a>. Estados: OK = marcó asistencia; Error = falló RCE; En curso sin SENCE = entró al curso Moodle pero no registró sesión SENCE; Sin ingreso = no ha entrado.';
$string['report_filter_all'] = 'Todos';
$string['report_download_csv'] = 'Descargar CSV';
$string['report_empty'] = 'No hay participantes en este filtro.';
$string['report_col_name'] = 'Participante';
$string['report_col_run'] = 'RUT';
$string['report_col_groups'] = 'Grupos';
$string['report_col_codes'] = 'Códigos';
$string['report_col_status'] = 'Estado';
$string['report_col_courseaccess'] = 'Acceso curso';
$string['report_col_sence'] = 'Sesión SENCE';
$string['report_col_detail'] = 'Detalle';
$string['report_no_access'] = 'Sin acceso';
$string['report_error_code'] = 'Error {$a}';
$string['report_status_ok'] = 'Asistencia OK';
$string['report_status_error'] = 'Con error SENCE';
$string['report_status_course_no_sence'] = 'En curso sin SENCE';
$string['report_status_never'] = 'Sin ingreso';
$string['report_status_becado'] = 'Becario';
$string['report_status_nogroup'] = 'Sin grupo SENCE';
$string['report_tip_course_no_sence'] = 'Entró al curso Moodle pero no completó el inicio de sesión SENCE del bloque.';
$string['report_tip_never'] = 'Aún no registra acceso al curso ni intento SENCE.';
$string['report_tip_nogroup'] = 'Debe asignarse a un grupo SENCE-XXXXXXX (idnumber = ID acción).';
$string['reminder_button'] = 'Enviar recordatorio ({$a})';
$string['reminder_button_help'] = 'Envía un correo a quienes aún no marcaron asistencia SENCE.';
$string['reminder_help'] = 'Use <strong>Recordar</strong> en cada fila pendiente. El correo va al participante y usted recibe <strong>copia (CC)</strong>. No aplica a asistencia OK ni becarios.';
$string['reminder_scope_pending'] = 'pendientes de asistencia SENCE';
$string['reminder_confirm'] = '¿Enviar correo de recordatorio a <strong>{$a->count}</strong> participante(s) ({$a->scope}) del curso <strong>{$a->course}</strong>?';
$string['reminder_confirm_user'] = '¿Enviar recordatorio a <strong>{$a->fullname}</strong> (&lt;{$a->email}&gt;)?<br>Estado: {$a->status}<br>Curso: {$a->course}<br><br>Usted (<strong>{$a->sender}</strong> — {$a->senderemail}) recibirá una <strong>copia (CC)</strong> del mismo correo.';
$string['reminder_user_button'] = 'Recordar';
$string['reminder_user_button_help'] = 'Enviar recordatorio a este participante (con copia a usted)';
$string['reminder_user_missing'] = 'No se encontró el participante indicado.';
$string['reminder_user_not_pending'] = 'Este participante no requiere recordatorio (ya marcó asistencia o es becario).';
$string['reminder_user_sent'] = 'Recordatorio enviado a {$a->fullname} ({$a->email}). Copia enviada a {$a->sender}.';
$string['reminder_user_skipped'] = 'No se pudo enviar: usuario sin correo o no elegible.';
$string['reminder_user_failed'] = 'Falló el envío del recordatorio a {$a}.';
$string['reminder_preview_more'] = '… y {$a} más';
$string['reminder_result'] = 'Recordatorios enviados: {$a->sent}. Omitidos: {$a->skipped}. Fallidos: {$a->failed}.';
$string['reminder_cc_subject'] = '[Copia] {$a->subject} — enviado a {$a->fullname}';
$string['reminder_cc_preamble'] = 'Copia del recordatorio enviado a {$a->fullname} <{$a->email}>.';
$string['report_col_action'] = 'Acción';
$string['reminder_subject'] = 'Recordatorio: registre su asistencia SENCE — {$a->shortname}';
$string['reminder_body_text'] = 'Hola {$a->fullname},

Le recordamos que debe registrar su asistencia SENCE en el curso:
{$a->coursename} ({$a->shortname})

Ingrese al curso e inicie sesión con ClaveÚnica en el bloque Registro Asistencia SENCE:
{$a->courseurl}

Estado actual: {$a->status}
Grupo(s): {$a->groups}
RUT: {$a->run}

Si ya marcó asistencia, ignore este mensaje.
';
$string['reminder_body_html'] = '<p>Hola <strong>{$a->fullname}</strong>,</p><p>Le recordamos que debe registrar su <strong>asistencia SENCE</strong> en el curso:</p><p><strong>{$a->coursename}</strong> (<code>{$a->shortname}</code>)</p><p>Ingrese al curso e inicie sesión con <strong>ClaveÚnica</strong> en el bloque <em>Registro Asistencia SENCE</em>:</p><p><a href="{$a->courseurl}">{$a->courseurl}</a></p><ul><li>Estado actual: {$a->status}</li><li>Grupo(s): {$a->groups}</li><li>RUT: {$a->run}</li></ul><p>Si ya marcó asistencia, ignore este mensaje.</p>';
$string['moodle_sence:viewreport'] = 'Ver reporte de asistencia SENCE del curso';
$string['loginsuccess'] = 'Inicio de sesión SENCE registrado correctamente. Redirigiendo al curso…';
$string['logoutsuccess'] = 'Cierre de sesión SENCE registrado correctamente. Redirigiendo al curso…';
$string['backtocourse'] = 'Volver al curso';
$string['defaultalert'] = 'Debes cerrar tu sesión en los próximos 15 minutos';
$string['becado_title'] = '¡Adelante!';
$string['becado_message'] = 'No requiere informar a SENCE (grupo becario).';
$string['alreadyregistered_title'] = '¡Adelante!';
$string['alreadyregistered'] = 'Ya has registrado tu formación en SENCE.';
$string['managertitle'] = 'Gestor: integración SENCE de participantes';
$string['managercode'] = 'El Código SENCE configurado para {$a->shortname} es: {$a->code}';
$string['grouphint'] = 'Asigne el ID de acción o código del programa en el nombre del grupo de participantes, así: SENCE-XXXXXXX (también puede usar el idnumber del grupo).';
$string['managerbecas'] = 'Los usuarios en el grupo: {$a}, no serán requeridos de integrar SENCE.';
$string['managerbecas_none'] = 'No existen grupos de becarios configurados.';
$string['managercorreo'] = 'Se enviarán correos de alerta por errores a: {$a}';
$string['managercorreo_none'] = 'No se enviarán correos de alerta en caso de errores.';
$string['managerforce_on'] = 'Se pedirá el cierre de sesión al participante.';
$string['managerforce_off'] = '<strong>No</strong> se pedirá el cierre de sesión al participante.';
$string['glosa100'] = 'Estimad@ Participante: <strong>SENCE informa error en su Clave Única.</strong> Por favor recupere su ClaveÚnica a través del <a href="https://claveunica.gob.cl/" target="_blank" rel="noopener">Portal Ciudadano</a>.';
$string['glosa200'] = 'Parámetros vacíos';
$string['glosa201'] = 'Parámetro UrlError sin datos';
$string['glosa202'] = 'Parámetro UrlError con formato incorrecto';
$string['glosa203'] = 'Parámetro UrlRetoma con formato incorrecto';
$string['glosa204'] = 'El Código SENCE tiene formato incorrecto';
$string['glosa205'] = 'El Código Curso tiene formato incorrecto';
$string['glosa206'] = 'Línea de capacitación incorrecta';
$string['glosa207'] = 'Parámetro RunAlumno incorrecto';
$string['glosa208'] = 'RUN Alumno no autorizado para realizar el curso.';
$string['glosa209'] = 'RUT del OTEC está incorrecto';
$string['glosa210'] = 'Inicio de Sesión en SENCE Expirado. Vuelva a intentar.';
$string['glosa211'] = 'Token no corresponde a la empresa';
$string['glosa212'] = 'Token caducado';
$string['glosa300'] = 'Error interno SENCE';
$string['glosa301'] = 'ID de Acción/Folio Sence/SENCENET incorrecto o Línea de Capacitación incorrecta';
$string['glosa302'] = 'Error interno SENCE';
$string['glosa303'] = 'Token no existe o su formato es incorrecto';
$string['glosa304'] = 'Error interno SENCE';
$string['glosa305'] = 'Error interno SENCE';
$string['glosa306'] = 'El Código Curso no corresponde al Código SENCE';
$string['glosa307'] = 'El Código Curso no tiene Modalidad E-Learning';
$string['glosa308'] = 'El Código Curso no corresponde al RUT OTEC.';
$string['glosa309'] = 'Las fechas de ejecución comunicadas para el Código Curso no corresponden a la fecha actual.';
$string['glosa310'] = 'El Código Curso está en estado Terminado o Anulado.';
$string['glosa311'] = 'RUT ingresado en el Login de Clave Única no se corresponde con RUT del usuario en la plataforma';
$string['glosa312'] = 'No se pudo completar la autenticación con Clave Única.';
