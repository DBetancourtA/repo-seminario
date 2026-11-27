<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
define('STORAGE', ROOT . '/storage');

$configFile = ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Falta config/config.php. Copia config/config.example.php como config.php y ajusta los datos de la base.');
}
$GLOBALS['config'] = require $configFile;

date_default_timezone_set(cfg('app.zona_horaria', 'America/Guayaquil'));
mb_internal_encoding('UTF-8');

if (cfg('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', STORAGE . '/logs/php-error.log');
}

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $secure,
    'samesite' => 'Lax',
]);
session_name('seminario_sid');
session_start();

require APP . '/helpers.php';
require APP . '/DB.php';
require APP . '/Auth.php';
require APP . '/ProjectState.php';
require APP . '/Calendar.php';
require APP . '/Upload.php';
require APP . '/Repo.php';
require APP . '/Cambios.php';
require APP . '/Entregables.php';

function cfg(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}
