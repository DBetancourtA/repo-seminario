<?php
declare(strict_types=1);

/* ---------- Salida y rutas ---------- */

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    $url = (string) cfg('app.url', '');
    if ($url !== '') {
        return rtrim($url, '/');
    }
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

/** Construye una URL interna: url('proyecto/editar', ['id' => 3]) */
function url(string $route = '', array $params = []): string
{
    $q = $route !== '' ? ['r' => $route] + $params : $params;
    return base_url() . '/index.php' . ($q ? '?' . http_build_query($q) : '');
}

function asset(string $path): string
{
    $file = ROOT . '/public/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 1;
    return base_url() . '/assets/' . $path . '?v=' . $v;
}

function redirect(string $route = '', array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

function back(): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '' && str_starts_with($ref, base_url())) {
        header('Location: ' . $ref);
        exit;
    }
    redirect('');
}

function abort(int $code, string $msg = ''): never
{
    http_response_code($code);
    $titulos = [403 => 'Sin permiso', 404 => 'No encontrado', 419 => 'La sesión expiró', 500 => 'Error'];
    view('error', ['codigo' => $code, 'titulo' => $titulos[$code] ?? 'Error', 'mensaje' => $msg]);
    exit;
}

/* ---------- Vistas ---------- */

function view(string $name, array $data = [], string $layout = 'app'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP . '/views/' . $name . '.php';
    $content = ob_get_clean();
    if ($layout === '') {
        echo $content;
        return;
    }
    require APP . '/views/layouts/' . $layout . '.php';
}

function partial(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP . '/views/partials/' . $name . '.php';
}

/* ---------- Petición ---------- */

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function input_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? $_GET[$key] ?? null;
    return is_numeric($v) ? (int)$v : $default;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        abort(419, 'El formulario caducó. Vuelve a cargar la página e inténtalo otra vez.');
    }
}

/* ---------- Mensajes flash y valores anteriores ---------- */

function flash(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function keep_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function old(string $key, $default = '')
{
    return $_SESSION['_old_now'][$key] ?? $default;
}

function errors(): array
{
    return $_SESSION['_errors_now'] ?? [];
}

function set_errors(array $errors): void
{
    $_SESSION['_errors'] = $errors;
}

function field_error(string $key): string
{
    $err = errors()[$key] ?? null;
    return $err ? '<p class="field-error" id="err-' . e($key) . '">' . e($err) . '</p>' : '';
}

function invalid(string $key): string
{
    return isset(errors()[$key]) ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
}

/** Mueve old/errors de la petición anterior a la actual (se llama una vez por petición). */
function rotate_request_state(): void
{
    $_SESSION['_old_now'] = $_SESSION['_old'] ?? [];
    $_SESSION['_errors_now'] = $_SESSION['_errors'] ?? [];
    unset($_SESSION['_old'], $_SESSION['_errors']);
}

/* ---------- Fechas en español (sin depender de intl) ---------- */

function fecha_larga(string $date): string
{
    $d = new DateTime($date);
    $dias  = ['', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $dias[(int)$d->format('N')] . ' ' . $d->format('j') . ' de ' . $meses[(int)$d->format('n')];
}

function fecha_corta(string $date): string
{
    $d = new DateTime($date);
    $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return $d->format('j') . ' ' . $meses[(int)$d->format('n')];
}

function dia_corto(string $date): string
{
    return ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'][(int)(new DateTime($date))->format('N')];
}

function fecha_hora(string $dt): string
{
    $d = new DateTime($dt);
    return fecha_corta($dt) . ' ' . $d->format('Y') . ', ' . $d->format('H:i');
}

/* ---------- Etiquetas ---------- */

function estado_label(string $estado): string
{
    return [
        'registrado'            => 'Registrado',
        'en_revision'           => 'En revisión',
        'observado'             => 'Con observaciones',
        'aprobado'              => 'Aprobado',
        'rechazado'             => 'Rechazado',
        'tecnologias_definidas' => 'Tecnologías definidas',
        'en_desarrollo'         => 'En desarrollo',
        'finalizado'            => 'Finalizado',
    ][$estado] ?? $estado;
}

function estado_tono(string $estado): string
{
    return match ($estado) {
        'observado'  => 'warn',
        'rechazado'  => 'danger',
        'aprobado', 'tecnologias_definidas', 'en_desarrollo', 'finalizado' => 'ok',
        default      => 'neutral',
    };
}

function categoria_label(string $c): string
{
    return [
        'lenguaje'        => 'Lenguajes',
        'backend'         => 'Backend',
        'frontend'        => 'Frontend / UI',
        'base_datos'      => 'Base de datos',
        'plataforma'      => 'Plataforma',
        'infraestructura' => 'Despliegue',
        'herramienta'     => 'Herramientas',
    ][$c] ?? $c;
}

function categorias(): array
{
    return ['lenguaje', 'backend', 'frontend', 'base_datos', 'plataforma', 'infraestructura', 'herramienta'];
}

function tipo_label(string $t): string
{
    return [
        'codigo'        => 'Código',
        'interfaz'      => 'Interfaz',
        'base_datos'    => 'Base de datos',
        'documentacion' => 'Documentación',
        'otro'          => 'Otro',
    ][$t] ?? $t;
}

/* ---------- Iconos SVG (trazos estilo Lucide) ---------- */

function icon(string $name, int $size = 18): string
{
    $paths = [
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
        'alert'    => '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        'x'        => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'upload'   => '<path d="M12 15V3"/><path d="m7 8 5-5 5 5"/><path d="M5 15v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/>',
        'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'plus'     => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'arrow'    => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'back'     => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
        'image'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4-4L5 21"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'pause'    => '<path d="M8 5v14"/><path d="M16 5v14"/>',
        'link'     => '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>',
        'menu'     => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
        'sun'      => '<g><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.3 17.7-1.4 1.4"/><path d="m19.1 4.9-1.4 1.4"/></g>',
        'moon'     => '<g><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></g>',
        'file'     => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/>',
    ];
    $p = $paths[$name] ?? '';
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
}

function initials(string $nombres, string $apellidos): string
{
    return mb_strtoupper(mb_substr($nombres, 0, 1) . mb_substr($apellidos, 0, 1));
}

function random_code(int $len = 8): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < $len; $i++) {
        $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $s;
}

function valid_url(string $u): bool
{
    return $u === '' || (filter_var($u, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $u));
}
