<?php
declare(strict_types=1);

/**
 * Máquina de estados del trabajo de titulación.
 * Ningún estado se cambia fuera de esta clase.
 */
final class ProjectState
{
    /** Etapas visibles en la barra de flujo. */
    public const STAGES = [
        1 => 'Registro',
        2 => 'Revisión',
        3 => 'Aprobado',
        4 => 'Tecnologías',
        5 => 'Desarrollo',
        6 => 'Finalizado',
    ];

    /** desde => [hacia => rol que puede hacerlo] */
    private const TRANSITIONS = [
        'registrado'            => ['en_revision' => 'sistema'],
        'en_revision'           => ['aprobado' => 'admin', 'observado' => 'admin', 'rechazado' => 'admin'],
        'observado'             => ['en_revision' => 'estudiante', 'rechazado' => 'admin', 'aprobado' => 'admin'],
        'aprobado'              => ['tecnologias_definidas' => 'estudiante', 'observado' => 'admin'],
        'tecnologias_definidas' => ['en_desarrollo' => 'sistema', 'finalizado' => 'admin'],
        'en_desarrollo'         => ['finalizado' => 'admin'],
        'finalizado'            => ['en_desarrollo' => 'admin'],
        'rechazado'             => [],
    ];

    public static function can(string $from, string $to, string $actor): bool
    {
        $rol = self::TRANSITIONS[$from][$to] ?? null;
        return $rol !== null && ($rol === $actor || $actor === 'sistema');
    }

    public static function transition(array $proyecto, string $to, string $actor, ?int $userId, ?string $comentario = null): void
    {
        $from = $proyecto['estado'];
        if (!self::can($from, $to, $actor)) {
            throw new RuntimeException("Transición no permitida: {$from} → {$to}");
        }
        $obs = in_array($to, ['observado', 'rechazado'], true) ? $comentario : null;
        DB::run('UPDATE proyectos SET estado = ?, observacion = ? WHERE id = ?', [$to, $obs, $proyecto['id']]);
        DB::run(
            'INSERT INTO proyecto_estado_log (proyecto_id, desde, hacia, user_id, comentario) VALUES (?,?,?,?,?)',
            [$proyecto['id'], $from, $to, $userId, $comentario]
        );
    }

    /** Transiciones automáticas que dependen del calendario. */
    public static function sync(array $proyecto): array
    {
        if ($proyecto['estado'] === 'tecnologias_definidas') {
            $inicioSemana2 = DB::value(
                'SELECT MIN(fecha) FROM sesiones WHERE convocatoria_id = ? AND semana = 2',
                [$proyecto['convocatoria_id']]
            );
            if ($inicioSemana2 && date('Y-m-d') >= $inicioSemana2) {
                self::transition($proyecto, 'en_desarrollo', 'sistema', null, 'Inicio de la fase de desarrollo (semana 2).');
                $proyecto['estado'] = 'en_desarrollo';
            }
        }
        return $proyecto;
    }

    /** Devuelve [etapa_actual, tono] para pintar la barra. */
    public static function stageOf(string $estado): array
    {
        return match ($estado) {
            'registrado'            => [1, 'current'],
            'en_revision'           => [2, 'current'],
            'observado'             => [2, 'warn'],
            'rechazado'             => [2, 'danger'],
            'aprobado'              => [4, 'current'],
            'tecnologias_definidas' => [5, 'current'],
            'en_desarrollo'         => [5, 'current'],
            'finalizado'            => [6, 'done'],
            default                 => [1, 'current'],
        };
    }

    public static function editableByStudent(string $estado): bool
    {
        return in_array($estado, ['registrado', 'en_revision', 'observado'], true);
    }

    public static function techEditable(string $estado): bool
    {
        return in_array($estado, ['aprobado', 'tecnologias_definidas', 'en_desarrollo'], true);
    }

    public static function canLogProgress(string $estado): bool
    {
        return $estado !== 'rechazado' && $estado !== 'finalizado';
    }
}
