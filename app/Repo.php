<?php
declare(strict_types=1);

/** Consultas compartidas entre el panel del estudiante y el del docente. */
final class Repo
{
    /** Convocatoria más reciente en la que está inscrito el estudiante. */
    public static function convocatoriaDe(int $userId): ?array
    {
        return DB::one(
            'SELECT c.* FROM inscripciones i JOIN convocatorias c ON c.id = i.convocatoria_id
             WHERE i.user_id = ? ORDER BY i.created_at DESC, i.id DESC LIMIT 1',
            [$userId]
        );
    }

    /** Proyecto vigente del estudiante en una convocatoria (el último no rechazado; si no, el último). */
    public static function proyectoDe(int $userId, int $convId): ?array
    {
        return DB::one(
            "SELECT * FROM proyectos WHERE user_id = ? AND convocatoria_id = ?
             ORDER BY (estado = 'rechazado') ASC, id DESC LIMIT 1",
            [$userId, $convId]
        );
    }

    public static function proyecto(int $id): ?array
    {
        return DB::one(
            'SELECT p.*, u.nombres, u.apellidos, u.cedula, u.email, u.telefono,
                    c.nombre AS conv_nombre, c.periodo AS conv_periodo, c.carrera AS conv_carrera
             FROM proyectos p
             JOIN users u ON u.id = p.user_id
             JOIN convocatorias c ON c.id = p.convocatoria_id
             WHERE p.id = ?',
            [$id]
        );
    }

    public static function objetivos(int $pid): array
    {
        return DB::all('SELECT * FROM proyecto_objetivos WHERE proyecto_id = ? ORDER BY orden', [$pid]);
    }

    public static function capturas(int $pid): array
    {
        return DB::all('SELECT * FROM proyecto_capturas WHERE proyecto_id = ? ORDER BY id', [$pid]);
    }

    public static function tecnologias(int $pid): array
    {
        return DB::all(
            'SELECT t.* FROM proyecto_tecnologias pt JOIN tecnologias t ON t.id = pt.tecnologia_id
             WHERE pt.proyecto_id = ? ORDER BY FIELD(t.categoria,"lenguaje","backend","frontend","base_datos","plataforma","infraestructura","herramienta"), t.nombre',
            [$pid]
        );
    }

    public static function historial(int $pid): array
    {
        return DB::all(
            'SELECT l.*, u.nombres, u.apellidos, u.rol FROM proyecto_estado_log l
             LEFT JOIN users u ON u.id = l.user_id WHERE l.proyecto_id = ? ORDER BY l.id DESC',
            [$pid]
        );
    }

    /** Avances del proyecto indexados por sesion_id, con capturas y comentarios. */
    public static function avances(int $pid): array
    {
        $rows = DB::all('SELECT * FROM avances WHERE proyecto_id = ?', [$pid]);
        if (!$rows) {
            return [];
        }
        $ids = array_column($rows, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $caps = DB::all("SELECT * FROM avance_capturas WHERE avance_id IN ($in) ORDER BY id", $ids);
        $coms = DB::all(
            "SELECT c.*, u.nombres, u.apellidos FROM avance_comentarios c JOIN users u ON u.id = c.user_id
             WHERE c.avance_id IN ($in) ORDER BY c.id",
            $ids
        );
        $out = [];
        foreach ($rows as $r) {
            $r['capturas'] = [];
            $r['comentarios'] = [];
            $out[(int)$r['sesion_id']] = $r;
        }
        $bySesion = array_column($rows, 'sesion_id', 'id');
        foreach ($caps as $c) {
            $out[(int)$bySesion[$c['avance_id']]]['capturas'][] = $c;
        }
        foreach ($coms as $c) {
            $out[(int)$bySesion[$c['avance_id']]]['comentarios'][] = $c;
        }
        return $out;
    }

    public static function guardarTecnologias(int $pid, array $ids): void
    {
        DB::run('DELETE FROM proyecto_tecnologias WHERE proyecto_id = ?', [$pid]);
        foreach (array_unique(array_map('intval', $ids)) as $tid) {
            if ($tid > 0) {
                DB::run('INSERT IGNORE INTO proyecto_tecnologias (proyecto_id, tecnologia_id) VALUES (?,?)', [$pid, $tid]);
            }
        }
    }
}
