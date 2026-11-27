<?php
declare(strict_types=1);

/**
 * Cambios a la ficha de un proyecto ya aprobado.
 *
 * Reglas:
 *  - Antes de la semana de congelamiento, el estudiante puede COMPLETAR campos vacíos
 *    (se aplica al instante) y SOLICITAR cambios en campos llenos (los acepta el docente).
 *    El repositorio es libre.
 *  - Desde la semana de congelamiento, solo el docente edita.
 *  - El docente puede editar siempre. Todo queda registrado con valor anterior y nuevo.
 */
final class Cambios
{
    /** Campos de la ficha del proyecto que puede tocar el estudiante. */
    public const CAMPOS_PROYECTO = [
        'titulo'           => ['label' => 'Título',                 'libre' => false],
        'descripcion'      => ['label' => 'Descripción',            'libre' => false],
        'objetivo_general' => ['label' => 'Objetivo general',       'libre' => false],
        'objetivos'        => ['label' => 'Objetivos específicos',  'libre' => false],
        'repo_url'         => ['label' => 'Repositorio',            'libre' => true],
    ];

    /** Datos del estudiante: solo los edita el docente. */
    public const CAMPOS_ESTUDIANTE = [
        'nombres'   => 'Nombres',
        'apellidos' => 'Apellidos',
        'cedula'    => 'Cédula',
        'email'     => 'Correo',
        'telefono'  => 'Teléfono',
    ];

    public static function label(string $campo): string
    {
        return self::CAMPOS_PROYECTO[$campo]['label'] ?? self::CAMPOS_ESTUDIANTE[$campo] ?? ($campo === 'capturas' ? 'Capturas' : ($campo === 'clave' ? 'Contraseña' : $campo));
    }

    /** ¿El estudiante puede ajustar la ficha ahora mismo? */
    public static function estudiantePuede(array $p, array $conv): bool
    {
        return in_array($p['estado'], ['aprobado', 'tecnologias_definidas', 'en_desarrollo'], true)
            && !Calendar::isFrozen($conv);
    }

    /** El enlace del repositorio se puede actualizar siempre que el proyecto esté aprobado y en curso, aun con la ficha congelada. */
    public static function repoEditable(array $p): bool
    {
        return in_array($p['estado'], ['aprobado', 'tecnologias_definidas', 'en_desarrollo'], true);
    }

    /** Valor actual de un campo como texto (los objetivos, uno por línea). */
    public static function valor(int $pid, string $campo): string
    {
        if ($campo === 'objetivos') {
            return implode("\n", array_column(Repo::objetivos($pid), 'texto'));
        }
        if (isset(self::CAMPOS_ESTUDIANTE[$campo])) {
            return (string) DB::value("SELECT u.`$campo` FROM proyectos p JOIN users u ON u.id = p.user_id WHERE p.id = ?", [$pid]);
        }
        if (isset(self::CAMPOS_PROYECTO[$campo])) {
            return (string) DB::value("SELECT `$campo` FROM proyectos WHERE id = ?", [$pid]);
        }
        throw new InvalidArgumentException("Campo desconocido: $campo");
    }

    public static function normalizar(string $campo, string $v): string
    {
        $v = trim(str_replace("\r\n", "\n", $v));
        if ($campo === 'objetivos') {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $v)), fn($l) => $l !== ''));
            return implode("\n", array_slice($lines, 0, 6));
        }
        if ($campo === 'cedula') {
            return preg_replace('/\D/', '', $v);
        }
        if ($campo === 'email') {
            return mb_strtolower($v);
        }
        return $v;
    }

    /** Valida un valor nuevo. Devuelve mensaje de error o null. */
    public static function validar(string $campo, string $v, int $pid): ?string
    {
        switch ($campo) {
            case 'titulo':
                if (mb_strlen($v) < 8) return 'El título debe tener al menos 8 caracteres.';
                if (mb_strlen($v) > 200) return 'Máximo 200 caracteres.';
                break;
            case 'descripcion':
                if (mb_strlen($v) > 1000) return 'Máximo 1 000 caracteres.';
                break;
            case 'repo_url':
                if (!valid_url($v)) return 'Escribe una URL completa (https://…).';
                break;
            case 'nombres':
            case 'apellidos':
                if (mb_strlen($v) < 2) return 'Obligatorio.';
                break;
            case 'cedula':
                if (!preg_match('/^\d{10}$/', $v)) return 'La cédula debe tener 10 dígitos.';
                if (DB::value('SELECT u.id FROM users u WHERE u.cedula = ? AND u.id <> (SELECT user_id FROM proyectos WHERE id = ?)', [$v, $pid])) return 'Otra cuenta ya usa esa cédula.';
                break;
            case 'email':
                if (!filter_var($v, FILTER_VALIDATE_EMAIL)) return 'Correo no válido.';
                if (DB::value('SELECT u.id FROM users u WHERE u.email = ? AND u.id <> (SELECT user_id FROM proyectos WHERE id = ?)', [$v, $pid])) return 'Otra cuenta ya usa ese correo.';
                break;
            case 'telefono':
                if ($v !== '' && !preg_match('/^[0-9 +()-]{7,20}$/', $v)) return 'Revisa el número.';
                break;
        }
        return null;
    }

    /** Escribe el valor en su tabla. */
    public static function aplicar(int $pid, string $campo, string $v): void
    {
        if ($campo === 'objetivos') {
            DB::run('DELETE FROM proyecto_objetivos WHERE proyecto_id = ?', [$pid]);
            foreach ($v === '' ? [] : explode("\n", $v) as $i => $o) {
                DB::run('INSERT INTO proyecto_objetivos (proyecto_id, orden, texto) VALUES (?,?,?)', [$pid, $i + 1, $o]);
            }
        } elseif (isset(self::CAMPOS_ESTUDIANTE[$campo])) {
            DB::run("UPDATE users SET `$campo` = ? WHERE id = (SELECT user_id FROM proyectos WHERE id = ?)", [$v === '' && $campo === 'telefono' ? null : $v, $pid]);
        } else {
            DB::run("UPDATE proyectos SET `$campo` = ? WHERE id = ?", [$v === '' ? null : $v, $pid]);
        }
    }

    public static function registrar(int $pid, string $campo, ?string $antes, ?string $despues, string $tipo, string $estado, ?int $userId, ?string $motivo = null): int
    {
        return DB::insert(
            'INSERT INTO proyecto_cambios (proyecto_id, campo, valor_anterior, valor_nuevo, tipo, estado, motivo, user_id) VALUES (?,?,?,?,?,?,?,?)',
            [$pid, $campo, $antes, $despues, $tipo, $estado, $motivo, $userId]
        );
    }

    public static function pendientes(int $pid): array
    {
        return DB::all(
            "SELECT c.*, u.nombres, u.apellidos FROM proyecto_cambios c LEFT JOIN users u ON u.id = c.user_id
             WHERE c.proyecto_id = ? AND c.estado = 'pendiente' ORDER BY c.id",
            [$pid]
        );
    }

    public static function pendientePorCampo(int $pid): array
    {
        $out = [];
        foreach (self::pendientes($pid) as $c) {
            $out[$c['campo']] = $c;
        }
        return $out;
    }

    /** Registro completo (excluye solicitudes canceladas por el propio estudiante). */
    public static function registro(int $pid): array
    {
        return DB::all(
            "SELECT c.*, u.nombres, u.apellidos, u.rol, r.nombres AS rev_nombres, r.apellidos AS rev_apellidos
             FROM proyecto_cambios c
             LEFT JOIN users u ON u.id = c.user_id
             LEFT JOIN users r ON r.id = c.revisado_por
             WHERE c.proyecto_id = ? AND c.estado <> 'cancelado' ORDER BY c.id DESC",
            [$pid]
        );
    }

    public static function revisar(int $cambioId, bool $aceptar, int $adminId, ?string $comentario): ?array
    {
        $c = DB::one("SELECT * FROM proyecto_cambios WHERE id = ? AND estado = 'pendiente'", [$cambioId]);
        if (!$c) {
            return null;
        }
        DB::tx(function () use ($c, $aceptar, $adminId, $comentario) {
            if ($aceptar) {
                // El valor anterior real puede haber cambiado (p. ej. si el docente editó entretanto)
                DB::run('UPDATE proyecto_cambios SET valor_anterior = ? WHERE id = ?', [self::valor((int)$c['proyecto_id'], $c['campo']), $c['id']]);
                self::aplicar((int)$c['proyecto_id'], $c['campo'], (string)$c['valor_nuevo']);
            }
            DB::run(
                'UPDATE proyecto_cambios SET estado = ?, revisado_por = ?, comentario_revision = ?, revisado_at = NOW() WHERE id = ?',
                [$aceptar ? 'aceptado' : 'rechazado', $adminId, $comentario ?: null, $c['id']]
            );
        });
        return $c;
    }
}
