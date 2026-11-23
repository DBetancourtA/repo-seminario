<?php
declare(strict_types=1);

function home(): void
{
    $u = Auth::user();
    if (!$u) {
        redirect('login');
    }
    redirect($u['rol'] === 'admin' ? 'admin' : 'panel');
}

/** Sirve imágenes subidas comprobando que quien las pide sea el dueño o el docente. */
function archivo(): void
{
    $u = Auth::requireLogin();
    $tipo = input('t');
    $id = input_int('id');

    if ($tipo === 'proyecto') {
        $row = DB::one('SELECT pc.ruta, p.user_id FROM proyecto_capturas pc JOIN proyectos p ON p.id = pc.proyecto_id WHERE pc.id = ?', [$id]);
    } elseif ($tipo === 'avance') {
        $row = DB::one('SELECT ac.ruta, p.user_id FROM avance_capturas ac JOIN avances a ON a.id = ac.avance_id JOIN proyectos p ON p.id = a.proyecto_id WHERE ac.id = ?', [$id]);
    } else {
        abort(404);
    }
    if (!$row || ((int)$row['user_id'] !== (int)$u['id'] && $u['rol'] !== 'admin')) {
        abort(404);
    }
    $path = Upload::path($row['ruta']);
    if (!$path) {
        abort(404);
    }
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
}

/** PDF semanal. Estudiante: su proyecto. Docente: ?proyecto=ID */
function reporte(): void
{
    $u = Auth::requireLogin();
    $semana = input_int('semana');

    if ($u['rol'] === 'admin') {
        $pid = input_int('proyecto');
    } else {
        $conv = Repo::convocatoriaDe((int)$u['id']);
        $p = $conv ? Repo::proyectoDe((int)$u['id'], (int)$conv['id']) : null;
        $pid = $p ? (int)$p['id'] : 0;
    }
    $proyecto = $pid ? Repo::proyecto($pid) : null;
    if (!$proyecto) {
        abort(404, 'No hay un proyecto para generar el reporte.');
    }
    require_once APP . '/Report.php';
    $r = Report::build($proyecto, $semana);
    if (isset($r['error'])) {
        flash('warn', $r['error']);
        $u['rol'] === 'admin' ? redirect('admin/proyecto', ['id' => $pid]) : redirect('semana', ['n' => $semana]);
    }
    $nombre = sprintf('reporte-semana-%02d-%s.pdf', $semana, preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $proyecto['apellidos'] . '-' . $proyecto['nombres'])));
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . strtolower($nombre) . '"');
    header('Cache-Control: private, no-store');
    echo $r['pdf'];
}

/** Página pública: comprueba un código impreso en un reporte PDF. */
function verificar(): void
{
    $codigo = strtoupper(input('c'));
    $rep = null;
    $integro = null;
    if ($codigo !== '') {
        $rep = DB::one(
            'SELECT r.*, p.titulo, p.convocatoria_id, u.nombres, u.apellidos, c.nombre AS conv_nombre
             FROM reportes r JOIN proyectos p ON p.id = r.proyecto_id JOIN users u ON u.id = p.user_id
             JOIN convocatorias c ON c.id = p.convocatoria_id WHERE r.codigo = ?',
            [$codigo]
        );
        if ($rep) {
            require_once APP . '/Report.php';
            $integro = hash_equals($rep['hash'], Report::contentHash((int)$rep['proyecto_id'], (int)$rep['convocatoria_id'], (int)$rep['semana']));
        }
    }
    view('verificar', ['titulo' => 'Verificar reporte', 'codigo' => $codigo, 'rep' => $rep, 'integro' => $integro], 'auth');
}
