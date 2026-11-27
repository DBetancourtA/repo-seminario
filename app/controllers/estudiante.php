<?php
declare(strict_types=1);

/** Devuelve [usuario, convocatoria, proyecto|null]; redirige si falta la inscripción. */
function contexto_estudiante(): array
{
    $u = Auth::requireRole('estudiante');
    $conv = Repo::convocatoriaDe((int)$u['id']);
    if (!$conv) {
        redirect('unirse');
    }
    $p = Repo::proyectoDe((int)$u['id'], (int)$conv['id']);
    if ($p) {
        $p = ProjectState::sync($p);
    }
    return [$u, $conv, $p];
}

function unirse(): void
{
    $u = Auth::requireRole('estudiante');
    if (!is_post()) {
        view('estudiante/unirse', ['titulo' => 'Unirse a una convocatoria', 'conv' => Repo::convocatoriaDe((int)$u['id'])]);
        return;
    }
    $codigo = strtoupper(input('codigo'));
    $conv = DB::one("SELECT * FROM convocatorias WHERE codigo_inscripcion = ? AND estado IN ('abierta','en_curso')", [$codigo]);
    if (!$conv) {
        set_errors(['codigo' => 'El código no corresponde a una convocatoria abierta.']);
        keep_old(['codigo' => $codigo]);
        redirect('unirse');
    }
    DB::run('INSERT IGNORE INTO inscripciones (convocatoria_id, user_id) VALUES (?,?)', [$conv['id'], $u['id']]);
    DB::run('UPDATE inscripciones SET created_at = NOW() WHERE convocatoria_id = ? AND user_id = ?', [$conv['id'], $u['id']]);
    flash('ok', 'Te uniste a «' . $conv['nombre'] . '».');
    redirect('panel');
}

function panel(): void
{
    [$u, $conv, $p] = contexto_estudiante();
    $weeks = Calendar::weeks((int)$conv['id']);
    $avances = $p ? Repo::avances((int)$p['id']) : [];
    view('estudiante/panel', [
        'titulo'   => 'Mi trabajo de titulación',
        'u'        => $u,
        'conv'     => $conv,
        'p'        => $p,
        'weeks'    => $weeks,
        'avances'  => $avances,
        'actual'   => Calendar::currentWeek((int)$conv['id']),
        'tecs'     => $p ? Repo::tecnologias((int)$p['id']) : [],
    ]);
}

function proyecto(): void
{
    [$u, $conv, $p] = contexto_estudiante();
    $nuevo = $p && $p['estado'] === 'rechazado' && input('nuevo') === '1';
    $editable = !$p || $nuevo || ProjectState::editableByStudent($p['estado']);

    if (!is_post()) {
        if (!$editable) {
            view('estudiante/proyecto_ver', [
                'titulo'    => 'Mi proyecto',
                'u'         => $u,
                'conv'      => $conv,
                'p'         => array_merge(Repo::proyecto((int)$p['id']), $p),
                'objetivos' => Repo::objetivos((int)$p['id']),
                'capturas'  => Repo::capturas((int)$p['id']),
                'tecs'      => Repo::tecnologias((int)$p['id']),
                'historial' => Repo::historial((int)$p['id']),
                'cambios'   => Cambios::registro((int)$p['id']),
                'puedeAjustar' => Cambios::estudiantePuede($p, $conv),
                'congela'   => Calendar::freezeDate($conv),
            ]);
            return;
        }
        $base = $nuevo ? null : $p;
        view('estudiante/proyecto_form', [
            'titulo'    => $base ? 'Editar proyecto' : 'Registrar proyecto',
            'u'         => $u,
            'conv'      => $conv,
            'p'         => $base,
            'nuevo'     => $nuevo,
            'objetivos' => $base ? array_column(Repo::objetivos((int)$base['id']), 'texto') : [],
            'capturas'  => $base ? Repo::capturas((int)$base['id']) : [],
            'historial' => $base ? Repo::historial((int)$base['id']) : [],
        ]);
        return;
    }

    if (!$editable) {
        abort(403, 'El proyecto ya fue aprobado y no se puede editar.');
    }
    $target = $nuevo ? null : $p;

    // Datos personales (obligatorios)
    $d = [
        'nombres'          => input('nombres'),
        'apellidos'        => input('apellidos'),
        'telefono'         => input('telefono'),
        'titulo'           => input('titulo'),
        'descripcion'      => input('descripcion'),
        'objetivo_general' => input('objetivo_general'),
        'repo_url'         => input('repo_url'),
    ];
    $objetivos = array_values(array_filter(array_map('trim', (array)($_POST['objetivos'] ?? [])), fn($o) => $o !== ''));
    $err = [];
    if (mb_strlen($d['nombres']) < 2)   $err['nombres'] = 'Obligatorio.';
    if (mb_strlen($d['apellidos']) < 2) $err['apellidos'] = 'Obligatorio.';
    if ($d['telefono'] !== '' && !preg_match('/^[0-9 +()-]{7,20}$/', $d['telefono'])) $err['telefono'] = 'Revisa el número.';
    if (mb_strlen($d['titulo']) < 8)    $err['titulo'] = 'Escribe el título completo del proyecto (mínimo 8 caracteres).';
    if (mb_strlen($d['titulo']) > 200)  $err['titulo'] = 'Máximo 200 caracteres.';
    if (mb_strlen($d['descripcion']) > 1000) $err['descripcion'] = 'Máximo 1 000 caracteres.';
    if (count($objetivos) > 6)          $err['objetivos'] = 'Máximo 6 objetivos específicos.';
    if (!valid_url($d['repo_url']))     $err['repo_url'] = 'Escribe una URL completa (https://…).';

    $files = Upload::files('capturas');
    $existentes = $target ? count(Repo::capturas((int)$target['id'])) : 0;
    if ($existentes + count($files) > 4) {
        $err['capturas'] = 'Puedes tener hasta 4 capturas del proyecto.';
    }
    foreach ($files as $f) {
        if ($m = Upload::validate($f)) {
            $err['capturas'] = $m;
            break;
        }
    }

    if ($err) {
        set_errors($err);
        keep_old($d + ['objetivos' => $objetivos]);
        redirect('proyecto', $nuevo ? ['nuevo' => 1] : []);
    }

    $paths = array_map([Upload::class, 'store'], $files);

    DB::tx(function () use ($u, $conv, $target, $d, $objetivos, $paths) {
        DB::run('UPDATE users SET nombres = ?, apellidos = ?, telefono = ? WHERE id = ?', [$d['nombres'], $d['apellidos'], $d['telefono'] ?: null, $u['id']]);
        if ($target) {
            $pid = (int)$target['id'];
            DB::run(
                'UPDATE proyectos SET titulo = ?, descripcion = ?, objetivo_general = ?, repo_url = ? WHERE id = ?',
                [$d['titulo'], $d['descripcion'] ?: null, $d['objetivo_general'] ?: null, $d['repo_url'] ?: null, $pid]
            );
        } else {
            $pid = DB::insert(
                'INSERT INTO proyectos (convocatoria_id, user_id, titulo, descripcion, objetivo_general, repo_url) VALUES (?,?,?,?,?,?)',
                [$conv['id'], $u['id'], $d['titulo'], $d['descripcion'] ?: null, $d['objetivo_general'] ?: null, $d['repo_url'] ?: null]
            );
            DB::run('INSERT INTO proyecto_estado_log (proyecto_id, desde, hacia, user_id) VALUES (?,?,?,?)', [$pid, null, 'registrado', $u['id']]);
        }
        DB::run('DELETE FROM proyecto_objetivos WHERE proyecto_id = ?', [$pid]);
        foreach ($objetivos as $i => $o) {
            DB::run('INSERT INTO proyecto_objetivos (proyecto_id, orden, texto) VALUES (?,?,?)', [$pid, $i + 1, $o]);
        }
        foreach ($paths as $path) {
            DB::run('INSERT INTO proyecto_capturas (proyecto_id, ruta) VALUES (?,?)', [$pid, $path]);
        }
        $p = DB::one('SELECT * FROM proyectos WHERE id = ?', [$pid]);
        if ($p['estado'] === 'registrado') {
            ProjectState::transition($p, 'en_revision', 'sistema', null, 'Enviado a revisión del docente.');
        } elseif ($p['estado'] === 'observado') {
            ProjectState::transition($p, 'en_revision', 'estudiante', (int)$u['id'], 'Correcciones enviadas por el estudiante.');
        }
    });

    flash('ok', $target ? 'Cambios guardados. Tu proyecto está en revisión.' : 'Proyecto enviado. Tu docente lo revisará y verás el resultado en el flujo de arriba.');
    redirect('panel');
}

function capturaBorrar(): void
{
    if (!is_post()) {
        abort(405);
    }
    [$u, $conv, $p] = contexto_estudiante();
    $c = DB::one('SELECT pc.* FROM proyecto_capturas pc JOIN proyectos p ON p.id = pc.proyecto_id WHERE pc.id = ? AND p.user_id = ?', [input_int('id'), $u['id']]);
    $enRevision = $p && ProjectState::editableByStudent($p['estado']);
    $enAjustes = $p && Cambios::estudiantePuede($p, $conv);
    if (!$c || !$p || (int)$c['proyecto_id'] !== (int)$p['id'] || (!$enRevision && !$enAjustes)) {
        abort(403);
    }
    DB::run('DELETE FROM proyecto_capturas WHERE id = ?', [$c['id']]);
    Upload::delete($c['ruta']);
    if ($enAjustes) {
        Cambios::registrar((int)$p['id'], 'capturas', null, 'Eliminó una captura', 'completado', 'aplicado', (int)$u['id']);
    }
    flash('ok', 'Captura eliminada.');
    redirect($enAjustes ? 'proyecto/ajustes' : 'proyecto');
}

/** Ajustes a la ficha después de aprobada (hasta la semana de congelamiento). */
function ajustes(): void
{
    [$u, $conv, $p] = contexto_estudiante();
    if (!$p || !Cambios::estudiantePuede($p, $conv)) {
        flash('info', Calendar::isFrozen($conv)
            ? 'Desde la semana ' . (int)$conv['semana_congelamiento'] . ' la ficha del proyecto solo la modifica el docente. Pídele el cambio en clase.'
            : 'La ficha se ajusta desde aquí cuando el proyecto ya está aprobado.');
        redirect('proyecto');
    }
    $pid = (int)$p['id'];
    $pend = Cambios::pendientePorCampo($pid);

    if (!is_post()) {
        $valores = [];
        foreach (array_keys(Cambios::CAMPOS_PROYECTO) as $campo) {
            $valores[$campo] = Cambios::valor($pid, $campo);
        }
        view('estudiante/ajustes', [
            'titulo'   => 'Ajustar ficha del proyecto',
            'conv'     => $conv,
            'p'        => $p,
            'valores'  => $valores,
            'pend'     => $pend,
            'capturas' => Repo::capturas($pid),
            'congela'  => Calendar::freezeDate($conv),
        ]);
        return;
    }

    $err = [];
    $aplicar = [];   // completados / libres
    $solicitar = []; // requieren al docente
    foreach (Cambios::CAMPOS_PROYECTO as $campo => $def) {
        if (!array_key_exists($campo, $_POST) || isset($pend[$campo])) {
            continue;
        }
        $actual = Cambios::valor($pid, $campo);
        $nuevo = Cambios::normalizar($campo, (string)$_POST[$campo]);
        if ($nuevo === $actual) {
            continue;
        }
        if ($m = Cambios::validar($campo, $nuevo, $pid)) {
            $err[$campo] = $m;
            continue;
        }
        if ($def['libre'] || $actual === '') {
            $aplicar[$campo] = [$actual, $nuevo];
        } else {
            $motivo = trim((string)($_POST['motivo_' . $campo] ?? ''));
            if (mb_strlen($motivo) < 10) {
                $err['motivo_' . $campo] = 'Explica por qué necesitas el cambio (mínimo 10 caracteres).';
                continue;
            }
            $solicitar[$campo] = [$actual, $nuevo, $motivo];
        }
    }

    $files = Upload::files('capturas');
    if (count(Repo::capturas($pid)) + count($files) > 4) {
        $err['capturas'] = 'Puedes tener hasta 4 capturas del proyecto.';
    }
    foreach ($files as $f) {
        if ($m = Upload::validate($f)) {
            $err['capturas'] = $m;
            break;
        }
    }

    if ($err) {
        set_errors($err);
        keep_old($_POST);
        redirect('proyecto/ajustes');
    }
    if (!$aplicar && !$solicitar && !$files) {
        flash('info', 'No hiciste cambios.');
        redirect('proyecto/ajustes');
    }

    $paths = array_map([Upload::class, 'store'], $files);
    DB::tx(function () use ($pid, $u, $aplicar, $solicitar, $paths) {
        foreach ($aplicar as $campo => [$antes, $despues]) {
            Cambios::aplicar($pid, $campo, $despues);
            Cambios::registrar($pid, $campo, $antes, $despues, 'completado', 'aplicado', (int)$u['id']);
        }
        foreach ($solicitar as $campo => [$antes, $despues, $motivo]) {
            Cambios::registrar($pid, $campo, $antes, $despues, 'solicitud', 'pendiente', (int)$u['id'], $motivo);
        }
        foreach ($paths as $path) {
            DB::run('INSERT INTO proyecto_capturas (proyecto_id, ruta) VALUES (?,?)', [$pid, $path]);
        }
        if ($paths) {
            Cambios::registrar($pid, 'capturas', null, 'Agregó ' . count($paths) . ' captura(s)', 'completado', 'aplicado', (int)$u['id']);
        }
    });

    $msg = [];
    if ($aplicar || $paths) $msg[] = 'Se guardó lo que completaste';
    if ($solicitar) $msg[] = count($solicitar) . ' solicitud(es) de cambio enviada(s) al docente';
    flash('ok', implode('. ', $msg) . '.');
    redirect('proyecto');
}

/** Actualiza solo el enlace del repositorio (permitido aun con la ficha congelada). */
function repositorio(): void
{
    if (!is_post()) {
        abort(405);
    }
    [$u, $conv, $p] = contexto_estudiante();
    if (!$p || !Cambios::repoEditable($p)) {
        abort(403, 'El repositorio se actualiza cuando el proyecto está aprobado y en curso.');
    }
    $pid = (int)$p['id'];
    $actual = Cambios::valor($pid, 'repo_url');
    $nuevo = Cambios::normalizar('repo_url', (string)input('repo_url'));
    if ($nuevo === $actual) {
        flash('info', 'El enlace no cambió.');
        redirect('proyecto');
    }
    if ($m = Cambios::validar('repo_url', $nuevo, $pid)) {
        set_errors(['repo_url' => $m]);
        keep_old(['repo_url' => $nuevo]);
        redirect('proyecto');
    }
    DB::tx(function () use ($pid, $actual, $nuevo, $u) {
        Cambios::aplicar($pid, 'repo_url', $nuevo);
        Cambios::registrar($pid, 'repo_url', $actual, $nuevo, 'completado', 'aplicado', (int)$u['id']);
    });
    flash('ok', $nuevo === '' ? 'Enlace del repositorio eliminado.' : 'Enlace del repositorio actualizado.');
    redirect('proyecto');
}

function cambioCancelar(): void
{
    if (!is_post()) {
        abort(405);
    }
    [$u, $conv, $p] = contexto_estudiante();
    $c = DB::one("SELECT * FROM proyecto_cambios WHERE id = ? AND estado = 'pendiente' AND user_id = ?", [input_int('id'), $u['id']]);
    if (!$c || !$p || (int)$c['proyecto_id'] !== (int)$p['id']) {
        abort(404);
    }
    DB::run("UPDATE proyecto_cambios SET estado = 'cancelado' WHERE id = ?", [$c['id']]);
    flash('ok', 'Solicitud retirada.');
    redirect('proyecto/ajustes');
}

function tecnologias(): void
{
    [$u, $conv, $p] = contexto_estudiante();
    if (!$p || !ProjectState::techEditable($p['estado'])) {
        flash('info', 'Podrás definir las tecnologías cuando tu docente apruebe el proyecto.');
        redirect('panel');
    }

    if (is_post()) {
        $ids = array_map('intval', (array)($_POST['tec'] ?? []));
        $nueva = trim((string)input('nueva_nombre'));
        $nuevaCat = input('nueva_categoria');
        $err = [];

        if ($nueva !== '') {
            if (!in_array($nuevaCat, categorias(), true) || mb_strlen($nueva) > 80) {
                $err['nueva'] = 'Elige la categoría de la tecnología que propones.';
            } else {
                $exist = DB::one('SELECT id FROM tecnologias WHERE categoria = ? AND LOWER(nombre) = LOWER(?)', [$nuevaCat, $nueva]);
                $ids[] = $exist ? (int)$exist['id'] : DB::insert('INSERT INTO tecnologias (nombre, categoria, aprobada, propuesta_por) VALUES (?,?,0,?)', [$nueva, $nuevaCat, $u['id']]);
            }
        }
        // Solo ids válidos: aprobadas o propuestas por el mismo estudiante
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $validos = DB::all("SELECT id, categoria FROM tecnologias WHERE id IN ($in) AND (aprobada = 1 OR propuesta_por = ?)", [...$ids, $u['id']]);
        } else {
            $validos = [];
        }
        $cats = array_column($validos, 'categoria');
        if (!in_array('lenguaje', $cats, true))   $err['lenguaje'] = 'Elige al menos un lenguaje de programación.';
        if (!in_array('base_datos', $cats, true)) $err['base_datos'] = 'Elige la base de datos (o «Sin base de datos»).';
        if (!in_array('plataforma', $cats, true)) $err['plataforma'] = 'Indica el tipo de plataforma.';

        if ($err) {
            set_errors($err);
            keep_old(['tec' => array_column($validos, 'id')]);
            redirect('tecnologias');
        }
        DB::tx(function () use ($p, $validos, $u) {
            Repo::guardarTecnologias((int)$p['id'], array_column($validos, 'id'));
            if ($p['estado'] === 'aprobado') {
                ProjectState::transition($p, 'tecnologias_definidas', 'estudiante', (int)$u['id'], 'Stack tecnológico definido.');
            }
        });
        ProjectState::sync(DB::one('SELECT * FROM proyectos WHERE id = ?', [$p['id']]));
        flash('ok', 'Tecnologías guardadas.');
        redirect('panel');
    }

    $catalogo = DB::all('SELECT * FROM tecnologias WHERE aprobada = 1 OR propuesta_por = ? ORDER BY nombre', [$u['id']]);
    $porCat = array_fill_keys(categorias(), []);
    foreach ($catalogo as $t) {
        $porCat[$t['categoria']][] = $t;
    }
    $sel = old('tec', array_column(Repo::tecnologias((int)$p['id']), 'id'));
    view('estudiante/tecnologias', [
        'titulo'  => 'Tecnologías del proyecto',
        'p'       => $p,
        'conv'    => $conv,
        'porCat'  => $porCat,
        'sel'     => array_map('intval', $sel),
    ]);
}

function avance(): void
{
    [$u, $conv, $p] = contexto_estudiante();
    $s = DB::one('SELECT * FROM sesiones WHERE id = ? AND convocatoria_id = ?', [input_int('sesion'), $conv['id']]);
    if (!$s) {
        abort(404, 'Esa clase no existe en tu convocatoria.');
    }
    if (!$p) {
        flash('info', 'Primero registra tu proyecto.');
        redirect('proyecto');
    }
    if (!ProjectState::canLogProgress($p['estado'])) {
        flash('warn', 'No puedes registrar avances con el proyecto en estado «' . estado_label($p['estado']) . '».');
        redirect('panel');
    }
    if ($s['estado'] === 'suspendida') {
        flash('info', 'Esa clase fue suspendida; no requiere registro.');
        redirect('semana', ['n' => $s['semana']]);
    }
    if (!Calendar::isOpen($conv, $s)) {
        flash('info', 'Podrás registrar esta clase desde el ' . fecha_larga($s['fecha']) . ' a las ' . substr($conv['hora_inicio'], 0, 5) . '.');
        redirect('semana', ['n' => $s['semana']]);
    }
    $a = DB::one('SELECT * FROM avances WHERE proyecto_id = ? AND sesion_id = ?', [$p['id'], $s['id']]);
    $caps = $a ? DB::all('SELECT * FROM avance_capturas WHERE avance_id = ? ORDER BY id', [$a['id']]) : [];

    if (!is_post()) {
        view('estudiante/avance', [
            'titulo'   => 'Registrar avance',
            'conv'     => $conv,
            'p'        => $p,
            's'        => $s,
            'a'        => $a,
            'caps'     => $caps,
            'deadline' => Calendar::sessionDeadline($conv, $s),
            'tarde'    => Calendar::isLate($conv, $s),
            'comentarios' => $a ? DB::all('SELECT c.*, u.nombres, u.apellidos FROM avance_comentarios c JOIN users u ON u.id=c.user_id WHERE avance_id = ? ORDER BY c.id', [$a['id']]) : [],
        ]);
        return;
    }

    $d = [
        'actividad' => input('actividad'),
        'tipo'      => input('tipo'),
        'enlace'    => input('enlace'),
    ];
    $quitar = array_map('intval', (array)($_POST['quitar'] ?? []));
    $capsQuedan = array_filter($caps, fn($c) => !in_array((int)$c['id'], $quitar, true));
    $files = Upload::files('capturas');
    $min = (int) cfg('app.min_actividad', 80);
    $err = [];
    if (mb_strlen($d['actividad']) < $min) {
        $err['actividad'] = "Describe lo que hiciste con más detalle (mínimo {$min} caracteres; llevas " . mb_strlen($d['actividad']) . ').';
    }
    if (!in_array($d['tipo'], ['codigo', 'interfaz', 'base_datos', 'documentacion', 'otro'], true)) {
        $err['tipo'] = 'Elige el tipo de evidencia.';
    }
    if (!valid_url($d['enlace'])) {
        $err['enlace'] = 'Escribe una URL completa (https://…).';
    }
    $total = count($capsQuedan) + count($files);
    if ($total < 1) $err['capturas'] = 'Sube al menos una captura del código o del proyecto.';
    if ($total > 3) $err['capturas'] = 'Máximo 3 capturas por clase.';
    foreach ($files as $f) {
        if ($m = Upload::validate($f)) {
            $err['capturas'] = $m;
            break;
        }
    }
    if ($err) {
        set_errors($err);
        keep_old($d);
        redirect('avance', ['sesion' => $s['id']]);
    }

    $paths = array_map([Upload::class, 'store'], $files);
    $tardio = Calendar::isLate($conv, $s) ? 1 : 0;

    $borrar = DB::tx(function () use ($a, $p, $s, $d, $paths, $caps, $quitar, $tardio) {
        if ($a) {
            $aid = (int)$a['id'];
            DB::run('UPDATE avances SET actividad = ?, tipo = ?, enlace = ? WHERE id = ?', [$d['actividad'], $d['tipo'], $d['enlace'] ?: null, $aid]);
        } else {
            $aid = DB::insert('INSERT INTO avances (proyecto_id, sesion_id, actividad, tipo, enlace, tardio) VALUES (?,?,?,?,?,?)', [$p['id'], $s['id'], $d['actividad'], $d['tipo'], $d['enlace'] ?: null, $tardio]);
        }
        $borrar = [];
        foreach ($caps as $c) {
            if (in_array((int)$c['id'], $quitar, true)) {
                DB::run('DELETE FROM avance_capturas WHERE id = ?', [$c['id']]);
                $borrar[] = $c['ruta'];
            }
        }
        foreach ($paths as $path) {
            DB::run('INSERT INTO avance_capturas (avance_id, ruta) VALUES (?,?)', [$aid, $path]);
        }
        return $borrar;
    });
    array_map([Upload::class, 'delete'], $borrar);

    flash('ok', 'Avance de la clase ' . $s['numero'] . ' guardado' . ($tardio && !$a ? ' (registrado fuera de plazo).' : '.'));
    redirect('semana', ['n' => $s['semana']]);
}

function semana(): void
{
    [$u, $conv, $p] = contexto_estudiante();
    $n = max(1, input_int('n', 1));
    $sesiones = DB::all('SELECT * FROM sesiones WHERE convocatoria_id = ? AND semana = ? ORDER BY numero', [$conv['id'], $n]);
    if (!$sesiones) {
        abort(404, 'Esa semana no existe.');
    }
    require_once APP . '/Report.php';
    $avances = $p ? Repo::avances((int)$p['id']) : [];
    view('estudiante/semana', [
        'titulo'   => 'Semana ' . $n,
        'conv'     => $conv,
        'p'        => $p,
        'n'        => $n,
        'sesiones' => $sesiones,
        'avances'  => $avances,
        'disp'     => Report::availability($sesiones, $avances),
    ]);
}

/* =====================  ENTREGABLES  ===================== */

function entregables(): void
{
    [$u, $conv, $p] = contexto_estudiante();
    view('estudiante/entregables', [
        'titulo'     => 'Entregables',
        'conv'       => $conv,
        'p'          => $p,
        'disponible' => Entregables::disponible(),
        'lista'      => Entregables::deConvocatoria($conv),
        'entregas'   => $p ? Entregables::entregasDe((int)$p['id']) : [],
        'editable'   => $p && Entregables::editablePorEstudiante($p),
    ]);
}

function entregar(): void
{
    if (!is_post()) {
        abort(405);
    }
    [$u, $conv, $p] = contexto_estudiante();
    if (!Entregables::disponible()) {
        abort(404);
    }
    if (!$p || !Entregables::editablePorEstudiante($p)) {
        flash('info', 'Podrás entregar cuando tu proyecto esté aprobado.');
        redirect('entregables');
    }
    $ent = DB::one('SELECT * FROM entregables WHERE id = ? AND convocatoria_id = ?', [input_int('entregable_id'), $conv['id']]);
    if (!$ent) {
        abort(404);
    }
    $actual = DB::one('SELECT * FROM entregas WHERE proyecto_id = ? AND entregable_id = ?', [$p['id'], $ent['id']]);
    if ($actual && $actual['estado'] === 'aprobado') {
        flash('info', '«' . $ent['nombre'] . '» ya fue aprobado; si necesitas cambiarlo, pídeselo al docente.');
        redirect('entregables');
    }
    $url = trim((string)input('url'));
    if ($m = Entregables::validarUrl($url, $ent['tipo'])) {
        set_errors(['url_' . $ent['id'] => $m]);
        keep_old(['url_' . $ent['id'] => $url]);
        header('Location: ' . url('entregables') . '#ent-' . $ent['id']);
        exit;
    }
    if ($actual) {
        DB::run("UPDATE entregas SET url = ?, estado = 'entregado', entregado_at = NOW() WHERE id = ?", [$url, $actual['id']]);
    } else {
        DB::run('INSERT INTO entregas (proyecto_id, entregable_id, url) VALUES (?,?,?)', [$p['id'], $ent['id'], $url]);
    }
    flash('ok', '«' . $ent['nombre'] . '» entregado. Recuerda compartirlo con tu docente.');
    header('Location: ' . url('entregables') . '#ent-' . $ent['id']);
    exit;
}
