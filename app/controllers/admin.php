<?php
declare(strict_types=1);

function conv_seleccionada(): ?array
{
    $id = input_int('conv');
    if ($id) {
        $c = DB::one('SELECT * FROM convocatorias WHERE id = ?', [$id]);
        if ($c) {
            $_SESSION['conv_admin'] = (int)$c['id'];
            return $c;
        }
    }
    if (!empty($_SESSION['conv_admin'])) {
        $c = DB::one('SELECT * FROM convocatorias WHERE id = ?', [$_SESSION['conv_admin']]);
        if ($c) return $c;
    }
    return DB::one("SELECT * FROM convocatorias ORDER BY FIELD(estado,'en_curso','abierta','borrador','cerrada'), fecha_inicio DESC LIMIT 1");
}

function tablero(): void
{
    Auth::requireRole('admin');
    $conv = conv_seleccionada();
    if (!$conv) {
        flash('info', 'Crea tu primera convocatoria para empezar.');
        redirect('admin/convocatoria');
    }
    $cid = (int)$conv['id'];

    $porEstado = array_column(DB::all('SELECT estado, COUNT(*) n FROM proyectos WHERE convocatoria_id = ? GROUP BY estado', [$cid]), 'n', 'estado');
    $inscritos = (int) DB::value('SELECT COUNT(*) FROM inscripciones WHERE convocatoria_id = ?', [$cid]);
    $conProyecto = (int) DB::value("SELECT COUNT(DISTINCT user_id) FROM proyectos WHERE convocatoria_id = ? AND estado <> 'rechazado'", [$cid]);

    $tecRows = DB::all(
        "SELECT t.categoria, t.nombre, t.aprobada, COUNT(*) n FROM proyecto_tecnologias pt
         JOIN tecnologias t ON t.id = pt.tecnologia_id JOIN proyectos p ON p.id = pt.proyecto_id
         WHERE p.convocatoria_id = ? AND p.estado <> 'rechazado' GROUP BY t.id ORDER BY n DESC, t.nombre",
        [$cid]
    );
    $tecPorCat = [];
    foreach ($tecRows as $r) {
        $tecPorCat[$r['categoria']][] = $r;
    }
    $conStack = (int) DB::value(
        "SELECT COUNT(DISTINCT pt.proyecto_id) FROM proyecto_tecnologias pt JOIN proyectos p ON p.id = pt.proyecto_id
         WHERE p.convocatoria_id = ? AND p.estado <> 'rechazado'", [$cid]
    );

    // Combinaciones de stack (lenguaje + base de datos)
    $stacks = DB::all(
        "SELECT combo, COUNT(*) n FROM (
            SELECT p.id, CONCAT_WS(' + ',
              (SELECT GROUP_CONCAT(t.nombre ORDER BY t.nombre SEPARATOR ' / ') FROM proyecto_tecnologias x JOIN tecnologias t ON t.id=x.tecnologia_id WHERE x.proyecto_id=p.id AND t.categoria='lenguaje'),
              (SELECT GROUP_CONCAT(t.nombre ORDER BY t.nombre SEPARATOR ' / ') FROM proyecto_tecnologias x JOIN tecnologias t ON t.id=x.tecnologia_id WHERE x.proyecto_id=p.id AND t.categoria='backend'),
              (SELECT GROUP_CONCAT(t.nombre ORDER BY t.nombre SEPARATOR ' / ') FROM proyecto_tecnologias x JOIN tecnologias t ON t.id=x.tecnologia_id WHERE x.proyecto_id=p.id AND t.categoria='base_datos')
            ) combo
            FROM proyectos p WHERE p.convocatoria_id = ? AND p.estado <> 'rechazado'
         ) s WHERE combo <> '' GROUP BY combo ORDER BY n DESC LIMIT 8",
        [$cid]
    );

    // Cumplimiento de bitácora: estudiante × semana
    $weeks = Calendar::weeks($cid);
    $hoy = date('Y-m-d');
    $proys = DB::all(
        "SELECT p.id, p.titulo, p.estado, u.nombres, u.apellidos FROM proyectos p JOIN users u ON u.id = p.user_id
         WHERE p.convocatoria_id = ? AND p.estado <> 'rechazado' ORDER BY u.apellidos, u.nombres",
        [$cid]
    );
    $hechos = [];
    foreach (DB::all("SELECT a.proyecto_id, s.semana, COUNT(*) n, SUM(a.tardio) t FROM avances a JOIN sesiones s ON s.id = a.sesion_id WHERE s.convocatoria_id = ? GROUP BY a.proyecto_id, s.semana", [$cid]) as $r) {
        $hechos[(int)$r['proyecto_id']][(int)$r['semana']] = ['n' => (int)$r['n'], 't' => (int)$r['t']];
    }
    $esperadas = [];
    foreach ($weeks as $w => $ss) {
        $esperadas[$w] = count(array_filter($ss, fn($s) => Calendar::isOpen($conv, $s)));
    }

    // Alertas
    $pendientes = DB::all(
        "SELECT p.id, p.titulo, p.updated_at, u.nombres, u.apellidos FROM proyectos p JOIN users u ON u.id = p.user_id
         WHERE p.convocatoria_id = ? AND p.estado = 'en_revision' ORDER BY p.updated_at",
        [$cid]
    );
    $solicitudes = DB::all(
        "SELECT pc.id, pc.campo, pc.created_at, p.id pid, u.nombres, u.apellidos FROM proyecto_cambios pc
         JOIN proyectos p ON p.id = pc.proyecto_id JOIN users u ON u.id = p.user_id
         WHERE p.convocatoria_id = ? AND pc.estado = 'pendiente' ORDER BY pc.created_at",
        [$cid]
    );
    $ultimas = DB::all("SELECT id FROM sesiones WHERE convocatoria_id = ? AND estado = 'normal' AND fecha < CURDATE() ORDER BY fecha DESC LIMIT 2", [$cid]);
    $inactivos = [];
    if (count($ultimas) === 2) {
        $ids = array_column($ultimas, 'id');
        $inactivos = DB::all(
            "SELECT p.id, p.titulo, u.nombres, u.apellidos FROM proyectos p JOIN users u ON u.id = p.user_id
             WHERE p.convocatoria_id = ? AND p.estado NOT IN ('rechazado','finalizado')
               AND NOT EXISTS (SELECT 1 FROM avances a WHERE a.proyecto_id = p.id AND a.sesion_id IN (?,?))
             ORDER BY u.apellidos",
            [$cid, $ids[0], $ids[1]]
        );
    }
    $sinProyecto = DB::all(
        "SELECT u.id, u.nombres, u.apellidos, u.email FROM inscripciones i JOIN users u ON u.id = i.user_id
         WHERE i.convocatoria_id = ? AND NOT EXISTS (SELECT 1 FROM proyectos p WHERE p.user_id = u.id AND p.convocatoria_id = i.convocatoria_id AND p.estado <> 'rechazado')
         ORDER BY u.apellidos",
        [$cid]
    );

    view('admin/tablero', [
        'titulo'      => 'Tablero',
        'conv'        => $conv,
        'convs'       => DB::all('SELECT id, nombre, periodo FROM convocatorias ORDER BY fecha_inicio DESC'),
        'porEstado'   => $porEstado,
        'inscritos'   => $inscritos,
        'conProyecto' => $conProyecto,
        'conStack'    => $conStack,
        'tecPorCat'   => $tecPorCat,
        'stacks'      => $stacks,
        'weeks'       => $weeks,
        'actual'      => Calendar::currentWeek($cid),
        'proys'       => $proys,
        'hechos'      => $hechos,
        'esperadas'   => $esperadas,
        'pendientes'  => $pendientes,
        'solicitudes' => $solicitudes,
        'inactivos'   => $inactivos,
        'sinProyecto' => $sinProyecto,
    ]);
}

function convocatorias(): void
{
    Auth::requireRole('admin');
    $rows = DB::all(
        "SELECT c.*,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.convocatoria_id = c.id) inscritos,
            (SELECT COUNT(*) FROM proyectos p WHERE p.convocatoria_id = c.id AND p.estado <> 'rechazado') proyectos,
            (SELECT COUNT(*) FROM proyectos p WHERE p.convocatoria_id = c.id AND p.estado = 'en_revision') pendientes
         FROM convocatorias c ORDER BY c.fecha_inicio DESC"
    );
    view('admin/convocatorias', ['titulo' => 'Convocatorias', 'rows' => $rows]);
}

function convocatoriaForm(): void
{
    Auth::requireRole('admin');
    $id = input_int('id');
    $c = $id ? DB::one('SELECT * FROM convocatorias WHERE id = ?', [$id]) : null;
    if ($id && !$c) {
        abort(404);
    }
    $bloqueado = $c && DB::value('SELECT COUNT(*) FROM avances a JOIN sesiones s ON s.id = a.sesion_id WHERE s.convocatoria_id = ?', [$c['id']]) > 0;

    if (!is_post()) {
        view('admin/convocatoria_form', [
            'titulo'    => $c ? 'Editar convocatoria' : 'Nueva convocatoria',
            'c'         => $c,
            'bloqueado' => $bloqueado,
        ]);
        return;
    }

    $d = [
        'nombre'        => input('nombre'),
        'periodo'       => input('periodo'),
        'carrera'       => input('carrera'),
        'fecha_inicio'  => input('fecha_inicio'),
        'semanas'       => input_int('semanas', 12),
        'dias_clase'    => implode(',', array_map('intval', (array)($_POST['dias'] ?? []))),
        'hora_inicio'   => input('hora_inicio'),
        'hora_fin'      => input('hora_fin'),
        'ventana_horas' => input_int('ventana_horas', 48),
        'semana_congelamiento' => input_int('semana_congelamiento', 3),
        'estado'        => input('estado'),
    ];
    $err = [];
    if (mb_strlen($d['nombre']) < 3)  $err['nombre'] = 'Obligatorio.';
    if ($d['periodo'] === '')         $err['periodo'] = 'Obligatorio.';
    if ($d['carrera'] === '')         $err['carrera'] = 'Obligatorio.';
    if (!DateTime::createFromFormat('Y-m-d', $d['fecha_inicio'])) $err['fecha_inicio'] = 'Fecha no válida.';
    if ($d['semanas'] < 1 || $d['semanas'] > 30) $err['semanas'] = 'Entre 1 y 30 semanas.';
    if ($d['dias_clase'] === '')      $err['dias'] = 'Marca al menos un día de clase.';
    if (!preg_match('/^\d{2}:\d{2}$/', $d['hora_inicio']) || !preg_match('/^\d{2}:\d{2}$/', $d['hora_fin']) || $d['hora_fin'] <= $d['hora_inicio']) {
        $err['hora'] = 'Revisa el horario.';
    }
    if ($d['ventana_horas'] < 0 || $d['ventana_horas'] > 720) $err['ventana_horas'] = 'Entre 0 y 720 horas.';
    if ($d['semana_congelamiento'] < 0 || $d['semana_congelamiento'] > $d['semanas']) $err['semana_congelamiento'] = 'Entre 0 (nunca) y el número de semanas.';
    if (!in_array($d['estado'], ['borrador', 'abierta', 'en_curso', 'cerrada'], true)) $d['estado'] = 'abierta';

    if ($err) {
        set_errors($err);
        keep_old($d + ['dias' => array_map('intval', (array)($_POST['dias'] ?? []))]);
        redirect('admin/convocatoria', $c ? ['id' => $c['id']] : []);
    }

    $cid = DB::tx(function () use ($c, $d, $bloqueado) {
        if ($c) {
            if ($bloqueado) {
                DB::run(
                    'UPDATE convocatorias SET nombre=?, periodo=?, carrera=?, hora_inicio=?, hora_fin=?, ventana_horas=?, semana_congelamiento=?, estado=? WHERE id=?',
                    [$d['nombre'], $d['periodo'], $d['carrera'], $d['hora_inicio'], $d['hora_fin'], $d['ventana_horas'], $d['semana_congelamiento'], $d['estado'], $c['id']]
                );
                return (int)$c['id'];
            }
            $recalc = $c['fecha_inicio'] !== $d['fecha_inicio'] || (int)$c['semanas'] !== $d['semanas'] || $c['dias_clase'] !== $d['dias_clase'];
            DB::run(
                'UPDATE convocatorias SET nombre=?, periodo=?, carrera=?, fecha_inicio=?, semanas=?, dias_clase=?, hora_inicio=?, hora_fin=?, ventana_horas=?, semana_congelamiento=?, estado=? WHERE id=?',
                [$d['nombre'], $d['periodo'], $d['carrera'], $d['fecha_inicio'], $d['semanas'], $d['dias_clase'], $d['hora_inicio'], $d['hora_fin'], $d['ventana_horas'], $d['semana_congelamiento'], $d['estado'], $c['id']]
            );
            if ($recalc) {
                Calendar::rebuild((int)$c['id']);
            }
            return (int)$c['id'];
        }
        do {
            $codigo = random_code(6);
        } while (DB::value('SELECT id FROM convocatorias WHERE codigo_inscripcion = ?', [$codigo]));
        $cid = DB::insert(
            'INSERT INTO convocatorias (nombre, periodo, carrera, codigo_inscripcion, fecha_inicio, semanas, dias_clase, hora_inicio, hora_fin, ventana_horas, semana_congelamiento, estado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$d['nombre'], $d['periodo'], $d['carrera'], $codigo, $d['fecha_inicio'], $d['semanas'], $d['dias_clase'], $d['hora_inicio'], $d['hora_fin'], $d['ventana_horas'], $d['semana_congelamiento'], $d['estado']]
        );
        Calendar::rebuild($cid);
        return $cid;
    });
    $_SESSION['conv_admin'] = $cid;
    flash('ok', $c ? 'Convocatoria actualizada.' : 'Convocatoria creada. Comparte el código de inscripción con tus estudiantes.');
    redirect('admin/convocatoria/ver', ['id' => $cid]);
}

function convocatoriaVer(): void
{
    Auth::requireRole('admin');
    $c = DB::one('SELECT * FROM convocatorias WHERE id = ?', [input_int('id')]);
    if (!$c) {
        abort(404);
    }
    $_SESSION['conv_admin'] = (int)$c['id'];
    $estudiantes = DB::all(
        "SELECT u.id uid, u.nombres, u.apellidos, u.cedula, u.email, i.created_at inscrito,
                p.id pid, p.titulo, p.estado
         FROM inscripciones i JOIN users u ON u.id = i.user_id
         LEFT JOIN proyectos p ON p.id = (SELECT id FROM proyectos WHERE user_id = u.id AND convocatoria_id = i.convocatoria_id ORDER BY (estado='rechazado'), id DESC LIMIT 1)
         WHERE i.convocatoria_id = ? ORDER BY u.apellidos, u.nombres",
        [$c['id']]
    );
    view('admin/convocatoria_ver', [
        'titulo'      => $c['nombre'],
        'c'           => $c,
        'weeks'       => Calendar::weeks((int)$c['id']),
        'estudiantes' => $estudiantes,
        'actual'      => Calendar::currentWeek((int)$c['id']),
    ]);
}

function sesionGuardar(): void
{
    Auth::requireRole('admin');
    if (!is_post()) {
        abort(405);
    }
    $s = DB::one('SELECT * FROM sesiones WHERE id = ?', [input_int('id')]);
    if (!$s) {
        abort(404);
    }
    $fecha = input('fecha');
    $tema = mb_substr(input('tema'), 0, 150);
    $estado = input('estado') === 'suspendida' ? 'suspendida' : 'normal';
    if (!DateTime::createFromFormat('Y-m-d', $fecha)) {
        flash('warn', 'Fecha no válida.');
    } else {
        DB::run('UPDATE sesiones SET fecha = ?, tema = ?, estado = ? WHERE id = ?', [$fecha, $tema ?: null, $estado, $s['id']]);
        flash('ok', "Semana {$s['semana']}, clase {$s['numero']} actualizada.");
    }
    header('Location: ' . url('admin/convocatoria/ver', ['id' => $s['convocatoria_id']]) . '#semana-' . $s['semana']);
    exit;
}

function proyectos(): void
{
    Auth::requireRole('admin');
    $conv = conv_seleccionada();
    $estado = input('estado');
    $q = input('q');
    $sql = "SELECT p.*, u.nombres, u.apellidos, u.cedula,
              (SELECT COUNT(*) FROM avances a WHERE a.proyecto_id = p.id) avances,
              (SELECT COUNT(*) FROM proyecto_cambios pc WHERE pc.proyecto_id = p.id AND pc.estado = 'pendiente') cambios_pend
            FROM proyectos p JOIN users u ON u.id = p.user_id WHERE p.convocatoria_id = ?";
    $params = [$conv['id'] ?? 0];
    if ($estado === 'cambios') {
        $sql .= " AND EXISTS (SELECT 1 FROM proyecto_cambios pc WHERE pc.proyecto_id = p.id AND pc.estado = 'pendiente')";
    } elseif ($estado !== '') {
        $sql .= ' AND p.estado = ?';
        $params[] = $estado;
    }
    if ($q !== '') {
        $sql .= ' AND (p.titulo LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ? OR u.cedula LIKE ?)';
        array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
    }
    $sql .= " ORDER BY FIELD(p.estado,'en_revision','observado','aprobado','tecnologias_definidas','en_desarrollo','registrado','finalizado','rechazado'), p.updated_at DESC";
    $rows = DB::all($sql, $params);
    $conteo = $conv ? array_column(DB::all('SELECT estado, COUNT(*) n FROM proyectos WHERE convocatoria_id = ? GROUP BY estado', [$conv['id']]), 'n', 'estado') : [];
    $conteoCambios = $conv ? (int) DB::value("SELECT COUNT(DISTINCT pc.proyecto_id) FROM proyecto_cambios pc JOIN proyectos p ON p.id = pc.proyecto_id WHERE p.convocatoria_id = ? AND pc.estado = 'pendiente'", [$conv['id']]) : 0;
    view('admin/proyectos', [
        'titulo' => 'Proyectos',
        'conv'   => $conv,
        'convs'  => DB::all('SELECT id, nombre, periodo FROM convocatorias ORDER BY fecha_inicio DESC'),
        'rows'   => $rows,
        'estado' => $estado,
        'q'      => $q,
        'conteo' => $conteo,
        'conteoCambios' => $conteoCambios,
    ]);
}

function proyectoVer(): void
{
    Auth::requireRole('admin');
    $p = Repo::proyecto(input_int('id'));
    if (!$p) {
        abort(404);
    }
    $p = array_merge($p, ProjectState::sync($p));
    $conv = DB::one('SELECT * FROM convocatorias WHERE id = ?', [$p['convocatoria_id']]);
    require_once APP . '/Report.php';
    $weeks = Calendar::weeks((int)$conv['id']);
    $avances = Repo::avances((int)$p['id']);
    $disp = [];
    foreach ($weeks as $w => $ss) {
        $disp[$w] = Report::availability($ss, $avances);
    }
    view('admin/proyecto', [
        'titulo'    => $p['titulo'],
        'p'         => $p,
        'conv'      => $conv,
        'objetivos' => Repo::objetivos((int)$p['id']),
        'capturas'  => Repo::capturas((int)$p['id']),
        'tecs'      => Repo::tecnologias((int)$p['id']),
        'historial' => Repo::historial((int)$p['id']),
        'weeks'     => $weeks,
        'avances'   => $avances,
        'disp'      => $disp,
        'semanaSel' => input_int('semana', max(1, Calendar::currentWeek((int)$conv['id']))),
        'pendientes'=> Cambios::pendientes((int)$p['id']),
        'cambios'   => Cambios::registro((int)$p['id']),
    ]);
}

function proyectoEstado(): void
{
    $u = Auth::requireRole('admin');
    if (!is_post()) {
        abort(405);
    }
    $p = DB::one('SELECT * FROM proyectos WHERE id = ?', [input_int('id')]);
    if (!$p) {
        abort(404);
    }
    $map = ['aprobar' => 'aprobado', 'observar' => 'observado', 'rechazar' => 'rechazado', 'finalizar' => 'finalizado', 'reabrir' => 'en_desarrollo'];
    $to = $map[input('accion')] ?? null;
    $comentario = input('comentario');
    if (!$to) {
        abort(400);
    }
    if (in_array($to, ['observado', 'rechazado'], true) && mb_strlen($comentario) < 10) {
        set_errors(['comentario' => 'Explica al estudiante qué debe corregir (mínimo 10 caracteres).']);
        keep_old(['comentario' => $comentario]);
        redirect('admin/proyecto', ['id' => $p['id']]);
    }
    if (!ProjectState::can($p['estado'], $to, 'admin')) {
        flash('warn', 'Ese cambio no es posible desde «' . estado_label($p['estado']) . '».');
        redirect('admin/proyecto', ['id' => $p['id']]);
    }
    ProjectState::transition($p, $to, 'admin', (int)$u['id'], $comentario ?: null);
    flash('ok', 'Proyecto ahora: ' . estado_label($to) . '.');
    $siguiente = DB::value("SELECT id FROM proyectos WHERE convocatoria_id = ? AND estado = 'en_revision' AND id <> ? ORDER BY updated_at LIMIT 1", [$p['convocatoria_id'], $p['id']]);
    if ($siguiente && in_array($to, ['aprobado', 'observado', 'rechazado'], true)) {
        flash('info', 'Hay más proyectos esperando revisión.');
    }
    redirect('admin/proyecto', ['id' => $p['id']]);
}

function comentar(): void
{
    $u = Auth::requireRole('admin');
    if (!is_post()) {
        abort(405);
    }
    $a = DB::one('SELECT a.*, s.semana FROM avances a JOIN sesiones s ON s.id = a.sesion_id WHERE a.id = ?', [input_int('avance_id')]);
    $texto = input('texto');
    if (!$a) {
        abort(404);
    }
    if ($texto !== '') {
        DB::run('INSERT INTO avance_comentarios (avance_id, user_id, texto) VALUES (?,?,?)', [$a['id'], $u['id'], mb_substr($texto, 0, 2000)]);
        flash('ok', 'Comentario agregado.');
    }
    header('Location: ' . url('admin/proyecto', ['id' => $a['proyecto_id'], 'semana' => $a['semana']]) . '#avance-' . $a['id']);
    exit;
}

function tecnologias(): void
{
    Auth::requireRole('admin');
    if (is_post()) {
        $accion = input('accion');
        $id = input_int('id');
        if ($accion === 'aprobar') {
            DB::run('UPDATE tecnologias SET aprobada = 1 WHERE id = ?', [$id]);
            flash('ok', 'Tecnología aprobada y agregada al catálogo.');
        } elseif ($accion === 'fusionar') {
            $destino = input_int('destino');
            if ($destino && $destino !== $id) {
                DB::tx(function () use ($id, $destino) {
                    DB::run('INSERT IGNORE INTO proyecto_tecnologias (proyecto_id, tecnologia_id) SELECT proyecto_id, ? FROM proyecto_tecnologias WHERE tecnologia_id = ?', [$destino, $id]);
                    DB::run('DELETE FROM tecnologias WHERE id = ?', [$id]);
                });
                flash('ok', 'Tecnología fusionada; los proyectos que la usaban ahora apuntan a la elegida.');
            }
        } elseif ($accion === 'eliminar') {
            $uso = (int) DB::value('SELECT COUNT(*) FROM proyecto_tecnologias WHERE tecnologia_id = ?', [$id]);
            if ($uso > 0) {
                flash('warn', 'No se puede eliminar: la usan ' . $uso . ' proyecto(s). Fusiónala con otra.');
            } else {
                DB::run('DELETE FROM tecnologias WHERE id = ?', [$id]);
                flash('ok', 'Tecnología eliminada.');
            }
        } elseif ($accion === 'crear') {
            $nombre = input('nombre');
            $cat = input('categoria');
            if ($nombre !== '' && in_array($cat, categorias(), true)) {
                DB::run('INSERT IGNORE INTO tecnologias (nombre, categoria, aprobada) VALUES (?,?,1)', [mb_substr($nombre, 0, 80), $cat]);
                flash('ok', 'Tecnología agregada.');
            }
        }
        redirect('admin/tecnologias');
    }
    $rows = DB::all(
        'SELECT t.*, u.nombres, u.apellidos, (SELECT COUNT(*) FROM proyecto_tecnologias pt WHERE pt.tecnologia_id = t.id) uso
         FROM tecnologias t LEFT JOIN users u ON u.id = t.propuesta_por ORDER BY t.nombre'
    );
    $pend = array_values(array_filter($rows, fn($r) => !$r['aprobada']));
    $porCat = array_fill_keys(categorias(), []);
    foreach ($rows as $r) {
        if ($r['aprobada']) {
            $porCat[$r['categoria']][] = $r;
        }
    }
    view('admin/tecnologias', ['titulo' => 'Catálogo de tecnologías', 'pend' => $pend, 'porCat' => $porCat]);
}

function exportar(): void
{
    Auth::requireRole('admin');
    $conv = conv_seleccionada();
    if (!$conv) {
        abort(404);
    }
    $rows = DB::all(
        "SELECT p.id, u.apellidos, u.nombres, u.cedula, u.email, u.telefono, p.titulo, p.estado, p.repo_url,
                (SELECT COUNT(*) FROM avances a WHERE a.proyecto_id = p.id) avances,
                (SELECT COUNT(*) FROM avances a WHERE a.proyecto_id = p.id AND a.tardio = 1) tardios
         FROM proyectos p JOIN users u ON u.id = p.user_id WHERE p.convocatoria_id = ? ORDER BY u.apellidos",
        [$conv['id']]
    );
    $tecs = [];
    foreach (DB::all('SELECT pt.proyecto_id, t.categoria, t.nombre FROM proyecto_tecnologias pt JOIN tecnologias t ON t.id = pt.tecnologia_id JOIN proyectos p ON p.id = pt.proyecto_id WHERE p.convocatoria_id = ?', [$conv['id']]) as $t) {
        $tecs[$t['proyecto_id']][$t['categoria']][] = $t['nombre'];
    }
    $fname = 'proyectos-' . preg_replace('/[^a-z0-9]+/i', '-', $conv['periodo']) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . strtolower($fname) . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
    $head = ['Apellidos', 'Nombres', 'Cédula', 'Correo', 'Teléfono', 'Proyecto', 'Estado', 'Repositorio', 'Avances', 'Tardíos'];
    foreach (categorias() as $c) {
        $head[] = categoria_label($c);
    }
    fputcsv($out, $head, ';');
    foreach ($rows as $r) {
        $line = [$r['apellidos'], $r['nombres'], $r['cedula'], $r['email'], $r['telefono'], $r['titulo'], estado_label($r['estado']), $r['repo_url'], $r['avances'], $r['tardios']];
        foreach (categorias() as $c) {
            $line[] = implode(', ', $tecs[$r['id']][$c] ?? []);
        }
        fputcsv($out, $line, ';');
    }
    fclose($out);
}


/** El docente edita cualquier campo de la ficha o los datos del estudiante. Todo queda registrado. */
function proyectoEditar(): void
{
    $u = Auth::requireRole('admin');
    $p = Repo::proyecto(input_int('id'));
    if (!$p) {
        abort(404);
    }
    $pid = (int)$p['id'];
    $campos = array_merge(array_keys(Cambios::CAMPOS_ESTUDIANTE), array_keys(Cambios::CAMPOS_PROYECTO));

    if (!is_post()) {
        $valores = [];
        foreach ($campos as $c) {
            $valores[$c] = Cambios::valor($pid, $c);
        }
        view('admin/proyecto_editar', [
            'titulo'   => 'Editar ficha',
            'p'        => $p,
            'valores'  => $valores,
            'capturas' => Repo::capturas($pid),
            'pend'     => Cambios::pendientePorCampo($pid),
        ]);
        return;
    }

    $nota = trim((string)input('nota'));
    $err = [];
    $cambios = [];
    foreach ($campos as $c) {
        if (!array_key_exists($c, $_POST)) {
            continue;
        }
        $actual = Cambios::valor($pid, $c);
        $nuevo = Cambios::normalizar($c, (string)$_POST[$c]);
        if ($nuevo === $actual) {
            continue;
        }
        if ($m = Cambios::validar($c, $nuevo, $pid)) {
            $err[$c] = $m;
            continue;
        }
        $cambios[$c] = [$actual, $nuevo];
    }
    if ($err) {
        set_errors($err);
        keep_old($_POST);
        redirect('admin/proyecto/editar', ['id' => $pid]);
    }
    if (!$cambios) {
        flash('info', 'No hubo cambios.');
        redirect('admin/proyecto', ['id' => $pid]);
    }
    DB::tx(function () use ($pid, $cambios, $u, $nota) {
        foreach ($cambios as $c => [$antes, $despues]) {
            Cambios::aplicar($pid, $c, $despues);
            Cambios::registrar($pid, $c, $antes, $despues, 'docente', 'aplicado', (int)$u['id'], $nota ?: null);
        }
    });
    flash('ok', count($cambios) . ' campo(s) actualizado(s). Quedó registrado en el historial de cambios.');
    redirect('admin/proyecto', ['id' => $pid]);
}

function cambioRevisar(): void
{
    $u = Auth::requireRole('admin');
    if (!is_post()) {
        abort(405);
    }
    $aceptar = input('accion') === 'aceptar';
    $comentario = trim((string)input('comentario'));
    $c = Cambios::revisar(input_int('id'), $aceptar, (int)$u['id'], $comentario);
    if (!$c) {
        flash('warn', 'Esa solicitud ya fue revisada o retirada.');
        back();
    }
    flash('ok', 'Solicitud de cambio en «' . Cambios::label($c['campo']) . '» ' . ($aceptar ? 'aceptada y aplicada.' : 'rechazada.'));
    redirect('admin/proyecto', ['id' => $c['proyecto_id']]);
}

function capturaBorrarAdmin(): void
{
    $u = Auth::requireRole('admin');
    if (!is_post()) {
        abort(405);
    }
    $c = DB::one('SELECT * FROM proyecto_capturas WHERE id = ?', [input_int('id')]);
    if (!$c) {
        abort(404);
    }
    DB::run('DELETE FROM proyecto_capturas WHERE id = ?', [$c['id']]);
    Upload::delete($c['ruta']);
    Cambios::registrar((int)$c['proyecto_id'], 'capturas', null, 'El docente eliminó una captura', 'docente', 'aplicado', (int)$u['id']);
    flash('ok', 'Captura eliminada.');
    redirect('admin/proyecto/editar', ['id' => $c['proyecto_id']]);
}
