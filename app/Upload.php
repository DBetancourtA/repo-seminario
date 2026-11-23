<?php
declare(strict_types=1);

/**
 * Subida segura de imágenes: valida el tipo real, vuelve a codificar con GD
 * (elimina cualquier contenido incrustado), reduce a 1600 px y guarda como JPEG.
 */
final class Upload
{
    private const MAX_SIDE = 1600;
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** Normaliza $_FILES['campo'] (simple o múltiple) a una lista. */
    public static function files(string $field): array
    {
        if (empty($_FILES[$field])) {
            return [];
        }
        $f = $_FILES[$field];
        if (!is_array($f['name'])) {
            return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
        }
        $out = [];
        foreach ($f['name'] as $i => $name) {
            if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
        return $out;
    }

    /** Valida sin guardar. Devuelve mensaje de error o null. */
    public static function validate(array $file): ?string
    {
        $maxMb = (int) cfg('app.max_mb', 5);
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            return "«{$file['name']}» supera el tamaño permitido por el servidor.";
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return "No se pudo subir «{$file['name']}».";
        }
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return "«{$file['name']}» pesa más de {$maxMb} MB.";
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED, true) || @getimagesize($file['tmp_name']) === false) {
            return "«{$file['name']}» no es una imagen válida (usa PNG, JPG o WebP).";
        }
        return null;
    }

    /** Guarda la imagen y devuelve la ruta relativa dentro de storage/uploads. */
    public static function store(array $file): string
    {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
            'image/gif'  => @imagecreatefromgif($file['tmp_name']),
            default      => false,
        };
        if (!$src) {
            throw new RuntimeException('Imagen ilegible.');
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, self::MAX_SIDE / max($w, $h));
        $nw = max(1, (int)round($w * $scale));
        $nh = max(1, (int)round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $rel = date('Y/m') . '/' . bin2hex(random_bytes(12)) . '.jpg';
        $abs = STORAGE . '/uploads/' . $rel;
        if (!is_dir(dirname($abs))) {
            mkdir(dirname($abs), 0775, true);
        }
        imagejpeg($dst, $abs, 86);
        imagedestroy($src);
        imagedestroy($dst);
        return $rel;
    }

    public static function delete(string $rel): void
    {
        $abs = STORAGE . '/uploads/' . $rel;
        if (is_file($abs) && str_starts_with(realpath($abs), realpath(STORAGE . '/uploads'))) {
            @unlink($abs);
        }
    }

    public static function path(string $rel): ?string
    {
        $abs = realpath(STORAGE . '/uploads/' . $rel);
        $base = realpath(STORAGE . '/uploads');
        return ($abs && $base && str_starts_with($abs, $base) && is_file($abs)) ? $abs : null;
    }
}
