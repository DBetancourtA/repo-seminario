<?php
declare(strict_types=1);

function login(): void
{
    if (Auth::user()) {
        redirect('');
    }
    if (!is_post()) {
        view('auth/login', ['titulo' => 'Ingresar'], 'auth');
        return;
    }
    $email = mb_strtolower(input('email'));
    $pass  = (string)($_POST['password'] ?? '');

    if (Auth::tooManyAttempts($email)) {
        set_errors(['email' => 'Demasiados intentos. Espera 15 minutos e inténtalo de nuevo.']);
        keep_old(['email' => $email]);
        redirect('login');
    }
    $u = DB::one('SELECT * FROM users WHERE email = ?', [$email]);
    if ($u && !$u['activo'] && password_verify($pass, $u['password_hash'])) {
        set_errors(['email' => 'Tu cuenta está desactivada. Habla con tu docente.']);
        keep_old(['email' => $email]);
        redirect('login');
    }
    if (!$u || !$u['activo'] || !password_verify($pass, $u['password_hash'])) {
        Auth::registerFailure($email);
        set_errors(['email' => 'El correo o la contraseña no coinciden.']);
        keep_old(['email' => $email]);
        redirect('login');
    }
    Auth::clearFailures($email);
    Auth::login($u);
    if (DB::hasColumn('users', 'ultimo_ingreso')) {
        DB::run('UPDATE users SET ultimo_ingreso = NOW() WHERE id = ?', [$u['id']]);
    }
    redirect('');
}

function registro(): void
{
    if (Auth::user()) {
        redirect('');
    }
    if (!is_post()) {
        view('auth/registro', ['titulo' => 'Crear cuenta', 'codigo' => strtoupper(input('codigo'))], 'auth');
        return;
    }

    $d = [
        'codigo'    => strtoupper(input('codigo')),
        'nombres'   => input('nombres'),
        'apellidos' => input('apellidos'),
        'cedula'    => preg_replace('/\D/', '', input('cedula')),
        'email'     => mb_strtolower(input('email')),
        'telefono'  => input('telefono'),
    ];
    $pass  = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');
    $err = [];

    $conv = DB::one("SELECT * FROM convocatorias WHERE codigo_inscripcion = ? AND estado IN ('abierta','en_curso')", [$d['codigo']]);
    if (!$conv) {
        $err['codigo'] = 'El código no corresponde a una convocatoria abierta. Pídeselo a tu docente.';
    }
    if (mb_strlen($d['nombres']) < 2)   $err['nombres'] = 'Escribe tus nombres.';
    if (mb_strlen($d['apellidos']) < 2) $err['apellidos'] = 'Escribe tus apellidos.';
    if (!preg_match('/^\d{10}$/', $d['cedula'])) {
        $err['cedula'] = 'La cédula debe tener 10 dígitos.';
    } elseif (DB::value('SELECT id FROM users WHERE cedula = ?', [$d['cedula']])) {
        $err['cedula'] = 'Ya existe una cuenta con esta cédula. Ingresa con tu correo.';
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $err['email'] = 'Escribe un correo válido.';
    } elseif (DB::value('SELECT id FROM users WHERE email = ?', [$d['email']])) {
        $err['email'] = 'Este correo ya está registrado.';
    }
    if ($d['telefono'] !== '' && !preg_match('/^[0-9 +()-]{7,20}$/', $d['telefono'])) {
        $err['telefono'] = 'Revisa el número de teléfono.';
    }
    if (strlen($pass) < 8)  $err['password'] = 'Mínimo 8 caracteres.';
    if ($pass !== $pass2)   $err['password2'] = 'Las contraseñas no coinciden.';

    if ($err) {
        set_errors($err);
        keep_old($d);
        redirect('registro');
    }

    $uid = DB::tx(function () use ($d, $pass, $conv) {
        $uid = DB::insert(
            'INSERT INTO users (rol, nombres, apellidos, cedula, email, telefono, password_hash) VALUES ("estudiante",?,?,?,?,?,?)',
            [$d['nombres'], $d['apellidos'], $d['cedula'], $d['email'], $d['telefono'] ?: null, password_hash($pass, PASSWORD_DEFAULT)]
        );
        DB::run('INSERT INTO inscripciones (convocatoria_id, user_id) VALUES (?,?)', [$conv['id'], $uid]);
        return $uid;
    });

    Auth::login(DB::one('SELECT * FROM users WHERE id = ?', [$uid]));
    flash('ok', 'Cuenta creada. Ahora registra tu proyecto de titulación.');
    redirect('proyecto');
}

function salir(): void
{
    if (is_post()) {
        Auth::logout();
    }
    redirect('login');
}

/** Crea la cuenta del docente. Solo funciona si no existe ningún admin. */
function instalar(): void
{
    $hayAdmin = false;
    try {
        $hayAdmin = (bool) DB::value("SELECT id FROM users WHERE rol = 'admin' LIMIT 1");
    } catch (PDOException $e) {
        abort(500, 'No se pudo leer la tabla de usuarios. Importa primero database/schema.sql en tu base de datos.');
    }
    if ($hayAdmin || !cfg('app.instalador_activo')) {
        abort(404, 'La instalación ya se realizó.');
    }
    if (!is_post()) {
        view('auth/instalar', ['titulo' => 'Configuración inicial'], 'auth');
        return;
    }
    $d = [
        'nombres'   => input('nombres'),
        'apellidos' => input('apellidos'),
        'cedula'    => preg_replace('/\D/', '', input('cedula')),
        'email'     => mb_strtolower(input('email')),
    ];
    $pass = (string)($_POST['password'] ?? '');
    $err = [];
    if (mb_strlen($d['nombres']) < 2)   $err['nombres'] = 'Obligatorio.';
    if (mb_strlen($d['apellidos']) < 2) $err['apellidos'] = 'Obligatorio.';
    if (strlen($d['cedula']) < 6)       $err['cedula'] = 'Obligatorio.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Correo no válido.';
    if (strlen($pass) < 10)             $err['password'] = 'Usa al menos 10 caracteres para la cuenta del docente.';
    if ($err) {
        set_errors($err);
        keep_old($d);
        redirect('instalar');
    }
    $id = DB::insert(
        'INSERT INTO users (rol, nombres, apellidos, cedula, email, password_hash) VALUES ("admin",?,?,?,?,?)',
        [$d['nombres'], $d['apellidos'], $d['cedula'], $d['email'], password_hash($pass, PASSWORD_DEFAULT)]
    );
    Auth::login(DB::one('SELECT * FROM users WHERE id = ?', [$id]));
    flash('ok', 'Cuenta de docente creada. Por seguridad, pon "instalador_activo" en false en config.php. Ahora crea tu primera convocatoria.');
    redirect('admin/convocatoria');
}


/** Cualquier usuario cambia su propia contraseña. */
function cambiarClave(): void
{
    $u = Auth::requireLogin();
    if (!is_post()) {
        view('auth/cambiar_clave', ['titulo' => 'Cambiar contraseña', 'u' => $u]);
        return;
    }
    $actual = (string)($_POST['actual'] ?? '');
    $nueva  = (string)($_POST['nueva'] ?? '');
    $nueva2 = (string)($_POST['nueva2'] ?? '');
    $min = $u['rol'] === 'admin' ? 10 : 8;
    $err = [];
    if (!password_verify($actual, $u['password_hash'])) {
        $err['actual'] = 'La contraseña actual no es correcta.';
    }
    if (strlen($nueva) < $min) {
        $err['nueva'] = "Mínimo {$min} caracteres.";
    } elseif (password_verify($nueva, $u['password_hash'])) {
        $err['nueva'] = 'La nueva contraseña debe ser distinta de la actual.';
    }
    if ($nueva !== $nueva2) {
        $err['nueva2'] = 'Las contraseñas no coinciden.';
    }
    if ($err) {
        set_errors($err);
        redirect('cuenta/clave');
    }
    DB::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($nueva, PASSWORD_DEFAULT), $u['id']]);
    session_regenerate_id(true);
    flash('ok', 'Contraseña actualizada.');
    redirect('');
}
