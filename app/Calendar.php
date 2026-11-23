<?php
declare(strict_types=1);

final class Calendar
{
    /**
     * Genera las sesiones de una convocatoria.
     * La semana 1 empieza el lunes de la semana de fecha_inicio.
     */
    public static function generate(array $conv): array
    {
        $dias = array_values(array_filter(array_map('intval', explode(',', $conv['dias_clase'])), fn($d) => $d >= 1 && $d <= 7));
        sort($dias);
        $inicio = new DateTimeImmutable($conv['fecha_inicio']);
        $lunes  = $inicio->modify('-' . ((int)$inicio->format('N') - 1) . ' days');
        $out = [];
        for ($w = 1; $w <= (int)$conv['semanas']; $w++) {
            $n = 0;
            foreach ($dias as $d) {
                $f = $lunes->modify('+' . (($w - 1) * 7 + ($d - 1)) . ' days');
                if ($f < $inicio) {
                    continue;
                }
                $out[] = ['semana' => $w, 'numero' => ++$n, 'fecha' => $f->format('Y-m-d')];
            }
        }
        return $out;
    }

    public static function rebuild(int $convId): void
    {
        $conv = DB::one('SELECT * FROM convocatorias WHERE id = ?', [$convId]);
        DB::run('DELETE FROM sesiones WHERE convocatoria_id = ?', [$convId]);
        foreach (self::generate($conv) as $s) {
            DB::run('INSERT INTO sesiones (convocatoria_id, semana, numero, fecha) VALUES (?,?,?,?)', [$convId, $s['semana'], $s['numero'], $s['fecha']]);
        }
    }

    /** Sesiones agrupadas por semana. */
    public static function weeks(int $convId): array
    {
        $rows = DB::all('SELECT * FROM sesiones WHERE convocatoria_id = ? ORDER BY semana, numero', [$convId]);
        $weeks = [];
        foreach ($rows as $r) {
            $weeks[(int)$r['semana']][] = $r;
        }
        return $weeks;
    }

    /** Semana en curso según la fecha de hoy (0 si aún no empieza). */
    public static function currentWeek(int $convId): int
    {
        $rows = DB::all('SELECT semana, MIN(fecha) AS f FROM sesiones WHERE convocatoria_id = ? GROUP BY semana ORDER BY semana', [$convId]);
        $hoy = date('Y-m-d');
        $current = 0;
        foreach ($rows as $r) {
            $d = new DateTimeImmutable($r['f']);
            $lunes = $d->modify('-' . ((int)$d->format('N') - 1) . ' days')->format('Y-m-d');
            if ($hoy >= $lunes) {
                $current = (int)$r['semana'];
            }
        }
        return $current;
    }

    public static function sessionStart(array $conv, array $sesion): DateTimeImmutable
    {
        return new DateTimeImmutable($sesion['fecha'] . ' ' . $conv['hora_inicio']);
    }

    public static function sessionDeadline(array $conv, array $sesion): DateTimeImmutable
    {
        return (new DateTimeImmutable($sesion['fecha'] . ' ' . $conv['hora_fin']))->modify('+' . (int)$conv['ventana_horas'] . ' hours');
    }

    /** ¿Ya se puede registrar el avance de esta sesión? (desde el inicio de la clase) */
    public static function isOpen(array $conv, array $sesion): bool
    {
        return $sesion['estado'] === 'normal' && new DateTimeImmutable() >= self::sessionStart($conv, $sesion);
    }

    public static function isLate(array $conv, array $sesion): bool
    {
        return new DateTimeImmutable() > self::sessionDeadline($conv, $sesion);
    }

    public static function weekEnded(array $sesionesSemana): bool
    {
        $last = end($sesionesSemana);
        return $last && date('Y-m-d') > $last['fecha'];
    }

    /** Fecha desde la que la ficha queda congelada para el estudiante (inicio de la semana de congelamiento). */
    public static function freezeDate(array $conv): ?string
    {
        $sem = (int)($conv['semana_congelamiento'] ?? 3);
        if ($sem < 1) {
            return null;
        }
        $f = DB::value('SELECT MIN(fecha) FROM sesiones WHERE convocatoria_id = ? AND semana = ?', [$conv['id'], $sem]);
        if (!$f) {
            return null;
        }
        $d = new DateTimeImmutable($f);
        return $d->modify('-' . ((int)$d->format('N') - 1) . ' days')->format('Y-m-d');
    }

    public static function isFrozen(array $conv): bool
    {
        $f = self::freezeDate($conv);
        return $f !== null && date('Y-m-d') >= $f;
    }

    public static function diasLabel(string $csv): string
    {
        $n = ['', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];
        return implode(', ', array_map(fn($d) => $n[(int)$d] ?? '', explode(',', $csv)));
    }
}
