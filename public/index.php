<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
rotate_request_state();

$routes = [
    ''                      => ['comun', 'home'],
    'login'                 => ['auth', 'login'],
    'registro'              => ['auth', 'registro'],
    'salir'                 => ['auth', 'salir'],
    'cuenta/clave'          => ['auth', 'cambiarClave'],
    'instalar'              => ['auth', 'instalar'],
    'verificar'             => ['comun', 'verificar'],
    'archivo'               => ['comun', 'archivo'],
    'reporte'               => ['comun', 'reporte'],

    'unirse'                => ['estudiante', 'unirse'],
    'panel'                 => ['estudiante', 'panel'],
    'proyecto'              => ['estudiante', 'proyecto'],
    'proyecto/captura/borrar' => ['estudiante', 'capturaBorrar'],
    'proyecto/ajustes'      => ['estudiante', 'ajustes'],
    'proyecto/repositorio'  => ['estudiante', 'repositorio'],
    'proyecto/cambio/cancelar' => ['estudiante', 'cambioCancelar'],
    'tecnologias'           => ['estudiante', 'tecnologias'],
    'avance'                => ['estudiante', 'avance'],
    'semana'                => ['estudiante', 'semana'],
    'entregables'           => ['estudiante', 'entregables'],
    'entrega'               => ['estudiante', 'entregar'],

    'admin'                 => ['admin', 'tablero'],
    'admin/convocatorias'   => ['admin', 'convocatorias'],
    'admin/convocatoria'    => ['admin', 'convocatoriaForm'],
    'admin/convocatoria/ver'=> ['admin', 'convocatoriaVer'],
    'admin/sesion'          => ['admin', 'sesionGuardar'],
    'admin/proyectos'       => ['admin', 'proyectos'],
    'admin/proyecto'        => ['admin', 'proyectoVer'],
    'admin/proyecto/estado' => ['admin', 'proyectoEstado'],
    'admin/comentar'        => ['admin', 'comentar'],
    'admin/tecnologias'     => ['admin', 'tecnologias'],
    'admin/exportar'        => ['admin', 'exportar'],
    'admin/proyecto/editar' => ['admin', 'proyectoEditar'],
    'admin/cambio'          => ['admin', 'cambioRevisar'],
    'admin/captura/borrar'  => ['admin', 'capturaBorrarAdmin'],
    'admin/estudiante/clave'=> ['admin', 'restablecerClave'],
    'admin/usuarios'        => ['admin', 'usuarios'],
    'admin/entrega'         => ['admin', 'entregaRevisar'],
    'admin/entregables'     => ['admin', 'entregablesConfig'],
    'admin/usuario'         => ['admin', 'usuarioEditar'],
    'admin/usuario/estado'  => ['admin', 'usuarioEstado'],
    'admin/usuario/convocatoria' => ['admin', 'usuarioConvocatoria'],
    'admin/usuarios/exportar' => ['admin', 'usuariosExportar'],
];

$route = trim((string)($_GET['r'] ?? ''), '/');
if (!isset($routes[$route])) {
    abort(404, 'La página que buscas no existe.');
}

[$file, $fn] = $routes[$route];
require APP . '/controllers/' . $file . '.php';

try {
    if (is_post()) {
        csrf_check();
    }
    $fn();
} catch (PDOException $e) {
    error_log((string)$e);
    abort(500, cfg('app.debug') ? $e->getMessage() : 'Ocurrió un problema con la base de datos. Inténtalo de nuevo.');
}
