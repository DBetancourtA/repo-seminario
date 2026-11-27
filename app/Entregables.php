<?php
declare(strict_types=1);

/**
 * Entregables por enlace: el estudiante pega un enlace (Drive, OneDrive, YouTube, GitHub…)
 * y el docente lo revisa. La plataforma no almacena estos archivos.
 */
final class Entregables
{
    /** Lista por defecto para cada convocatoria nueva. */
    public const DEFAULTS = [
        ['Demo en vivo', 'URL del sistema publicado y funcionando, con un usuario de prueba.', 7, 'url'],
        ['Manual técnico', 'PDF: arquitectura, base de datos, instalación y despliegue.', 10, 'documento'],
        ['Manual de usuario', 'PDF: cómo usar el sistema, pantalla por pantalla.', 10, 'documento'],
        ['Documento final de titulación', 'Documento completo según el formato del instituto.', 11, 'documento'],
        ['Video demostrativo', 'De 3 a 5 minutos mostrando el sistema funcionando.', 11, 'video'],
        ['Presentación para la sustentación', 'Diapositivas de la defensa.', 12, 'documento'],
    ];

    public const TIPOS = [
        'documento' => 'Documento (Drive, OneDrive, GitHub, Canva)',
        'video'     => 'Video (YouTube, Drive, Vimeo, Loom)',
        'url'       => 'Sitio web (cualquier enlace)',
    ];

    private const HOSTS = [
        'documento' => ['drive.google.com', 'docs.google.com', 'onedrive.live.com', '1drv.ms', 'sharepoint.com', 'github.com', 'canva.com', 'dropbox.com'],
        'video'     => ['youtube.com', 'youtu.be', 'drive.google.com', 'vimeo.com', 'loom.com'],
    ];

    public static function disponible(): bool
    {
        return DB::hasTable('entregables') && DB::hasTable('entregas');
    }

    /** Crea la lista por defecto si la convocatoria aún no tiene entregables. */
    public static function asegurar(array $conv): void
    {
        if (!self::disponible()) {
            return;
        }
        if ((int) DB::value('SELECT COUNT(*) FROM entregables WHERE convocatoria_id = ?', [$conv['id']]) > 0) {
            return;
        }
        $max = (int)$conv['semanas'];
        foreach (self::DEFAULTS as $i => [$nombre, $desc, $semana, $tipo]) {
            DB::run(
                'INSERT INTO entregables (convocatoria_id, nombre, descripcion, semana, tipo, orden) VALUES (?,?,?,?,?,?)',
                [$conv['id'], $nombre, $desc, min($semana, $max), $tipo, $i + 1]
            );
        }
    }

    public static function deConvocatoria(array $conv): array
    {
        if (!self::disponible()) {
            return [];
        }
        self::asegurar($conv);
        return DB::all('SELECT * FROM entregables WHERE convocatoria_id = ? ORDER BY semana, orden, id', [$conv['id']]);
    }

    /** Entregas de un proyecto, indexadas por entregable_id. */
    public static function entregasDe(int $pid): array
    {
        if (!self::disponible()) {
            return [];
        }
        $out = [];
        foreach (DB::all('SELECT * FROM entregas WHERE proyecto_id = ?', [$pid]) as $e) {
            $out[(int)$e['entregable_id']] = $e;
        }
        return $out;
    }

    /** Fecha límite: el último día de clase de esa semana. */
    public static function limite(array $conv, int $semana): ?string
    {
        static $cache = [];
        $k = $conv['id'] . '-' . $semana;
        if (!array_key_exists($k, $cache)) {
            $cache[$k] = DB::value('SELECT MAX(fecha) FROM sesiones WHERE convocatoria_id = ? AND semana = ?', [$conv['id'], $semana]);
        }
        return $cache[$k];
    }

    /** pendiente | vencido | entregado | observado | aprobado */
    public static function estado(?array $entrega, array $conv, array $ent): string
    {
        if ($entrega) {
            return $entrega['estado'];
        }
        $lim = self::limite($conv, (int)$ent['semana']);
        return ($lim && date('Y-m-d') > $lim) ? 'vencido' : 'pendiente';
    }

    public static function estadoLabel(string $e): string
    {
        return ['pendiente' => 'Pendiente', 'vencido' => 'Vencido', 'entregado' => 'Por revisar', 'observado' => 'Observado', 'aprobado' => 'Aprobado'][$e] ?? $e;
    }

    public static function estadoBadge(string $e): string
    {
        return ['pendiente' => '', 'vencido' => 'badge-danger', 'entregado' => 'badge-accent', 'observado' => 'badge-warn', 'aprobado' => 'badge-ok'][$e] ?? '';
    }

    /** Valida el enlace según el tipo de entregable. Devuelve mensaje de error o null. */
    public static function validarUrl(string $url, string $tipo): ?string
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            return 'Pega un enlace completo (que empiece con https://).';
        }
        if (mb_strlen($url) > 500) {
            return 'El enlace es demasiado largo.';
        }
        if ($tipo === 'url') {
            return null;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        foreach (self::HOSTS[$tipo] as $ok) {
            if ($host === $ok || str_ends_with($host, '.' . $ok)) {
                return null;
            }
        }
        return $tipo === 'video'
            ? 'Usa un enlace de YouTube, Google Drive, Vimeo o Loom.'
            : 'Usa un enlace de Google Drive, OneDrive, GitHub o Canva.';
    }

    /** URL para incrustar una vista previa, o null si el servicio no lo permite. */
    public static function embed(string $url): ?string
    {
        if (preg_match('#drive\.google\.com/file/d/([\w-]+)#', $url, $m)) {
            return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
        }
        if (preg_match('#drive\.google\.com/(?:open|uc)\?(?:.*&)?id=([\w-]+)#', $url, $m)) {
            return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
        }
        if (preg_match('#docs\.google\.com/(document|presentation|spreadsheets)/d/([\w-]+)#', $url, $m)) {
            return 'https://docs.google.com/' . $m[1] . '/d/' . $m[2] . '/preview';
        }
        if (preg_match('#(?:youtube\.com/watch\?(?:.*&)?v=|youtu\.be/|youtube\.com/shorts/)([\w-]{6,})#', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1];
        }
        if (preg_match('#vimeo\.com/(\d+)#', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        if (preg_match('#loom\.com/share/([\w]+)#', $url, $m)) {
            return 'https://www.loom.com/embed/' . $m[1];
        }
        return null;
    }

    public static function servicio(string $url): string
    {
        $h = strtolower((string) parse_url($url, PHP_URL_HOST));
        return match (true) {
            str_contains($h, 'drive.google') || str_contains($h, 'docs.google') => 'Google Drive',
            str_contains($h, 'onedrive') || str_contains($h, '1drv') || str_contains($h, 'sharepoint') => 'OneDrive',
            str_contains($h, 'youtu') => 'YouTube',
            str_contains($h, 'github') => 'GitHub',
            str_contains($h, 'canva') => 'Canva',
            str_contains($h, 'vimeo') => 'Vimeo',
            str_contains($h, 'loom') => 'Loom',
            default => $h,
        };
    }

    public static function editablePorEstudiante(array $p): bool
    {
        return in_array($p['estado'], ['aprobado', 'tecnologias_definidas', 'en_desarrollo'], true);
    }
}
