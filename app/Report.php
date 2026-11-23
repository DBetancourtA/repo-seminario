<?php
declare(strict_types=1);

require_once ROOT . '/lib/fpdf/fpdf.php';

final class ReportPdf extends FPDF
{
    public string $codigo = '';
    public string $headerLeft = '';
    public string $headerRight = '';
    public string $verifyUrl = '';

    public function Header(): void
    {
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(95, 93, 87);
        $this->Cell(120, 5, t($this->headerLeft), 0, 0, 'L');
        $this->Cell(0, 5, t($this->headerRight), 0, 1, 'R');
        $this->SetDrawColor(222, 219, 210);
        $this->Line(18, $this->GetY() + 1, 192, $this->GetY() + 1);
        $this->Ln(6);
    }

    public function Footer(): void
    {
        $this->SetY(-16);
        $this->SetDrawColor(222, 219, 210);
        $this->Line(18, $this->GetY(), 192, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Courier', '', 7.5);
        $this->SetTextColor(95, 93, 87);
        $this->Cell(140, 4, t('Verificación ' . $this->codigo . '  ·  ' . $this->verifyUrl), 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8);
        $this->Cell(0, 4, t('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }
}

function t(string $s): string
{
    $r = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
    return $r === false ? utf8_decode_safe($s) : $r;
}

function utf8_decode_safe(string $s): string
{
    return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
}

final class Report
{
    /** Huella del contenido de la semana: si el estudiante edita algo, cambia. */
    public static function contentHash(int $pid, int $convId, int $semana): string
    {
        $rows = DB::all(
            'SELECT a.id, a.sesion_id, a.actividad, a.tipo, a.enlace, a.tardio,
                    (SELECT GROUP_CONCAT(ruta ORDER BY id) FROM avance_capturas WHERE avance_id = a.id) AS caps
             FROM avances a JOIN sesiones s ON s.id = a.sesion_id
             WHERE a.proyecto_id = ? AND s.convocatoria_id = ? AND s.semana = ? ORDER BY s.numero',
            [$pid, $convId, $semana]
        );
        return hash('sha256', json_encode($rows, JSON_UNESCAPED_UNICODE));
    }

    /** Comprueba si la semana ya permite generar reporte. */
    public static function availability(array $sesiones, array $avances): array
    {
        $normales = array_filter($sesiones, fn($s) => $s['estado'] === 'normal');
        $hechas = array_filter($normales, fn($s) => isset($avances[(int)$s['id']]));
        $ended = Calendar::weekEnded($sesiones);
        $ok = count($hechas) > 0 && ($ended || count($hechas) === count($normales));
        return ['ok' => $ok, 'hechas' => count($hechas), 'total' => count($normales), 'terminada' => $ended];
    }

    public static function build(array $p, int $semana): array
    {
        $conv = DB::one('SELECT * FROM convocatorias WHERE id = ?', [$p['convocatoria_id']]);
        $sesiones = DB::all('SELECT * FROM sesiones WHERE convocatoria_id = ? AND semana = ? ORDER BY numero', [$conv['id'], $semana]);
        if (!$sesiones) {
            return ['error' => 'Esa semana no existe en la convocatoria.'];
        }
        $avances = Repo::avances((int)$p['id']);
        $av = self::availability($sesiones, $avances);
        if (!$av['ok']) {
            return ['error' => "El reporte de la semana {$semana} se habilita cuando registres todas las clases o cuando termine la semana ({$av['hechas']} de {$av['total']} registradas)."];
        }

        // Código de verificación: se reutiliza si el contenido no cambió
        $hash = self::contentHash((int)$p['id'], (int)$conv['id'], $semana);
        $rep = DB::one('SELECT * FROM reportes WHERE proyecto_id = ? AND semana = ? AND hash = ? ORDER BY id DESC LIMIT 1', [$p['id'], $semana, $hash]);
        if (!$rep) {
            do {
                $codigo = random_code(10);
            } while (DB::value('SELECT id FROM reportes WHERE codigo = ?', [$codigo]));
            DB::run('INSERT INTO reportes (proyecto_id, semana, codigo, hash) VALUES (?,?,?,?)', [$p['id'], $semana, $codigo, $hash]);
            $rep = ['codigo' => $codigo, 'created_at' => date('Y-m-d H:i:s')];
        }

        $primera = $sesiones[0]['fecha'];
        $ultima = end($sesiones)['fecha'];

        $pdf = new ReportPdf('P', 'mm', 'A4');
        $pdf->codigo = $rep['codigo'];
        $pdf->headerLeft = cfg('app.institucion') . ' · ' . $conv['nombre'];
        $pdf->headerRight = 'Semana ' . $semana . ' de ' . $conv['semanas'];
        $pdf->verifyUrl = url('verificar', ['c' => $rep['codigo']]);
        $pdf->SetTitle(t('Reporte semana ' . $semana . ' - ' . $p['apellidos']));
        $pdf->SetAuthor(t($p['nombres'] . ' ' . $p['apellidos']));
        $pdf->SetMargins(18, 14, 18);
        $pdf->SetAutoPageBreak(true, 22);
        $pdf->AliasNbPages();
        $pdf->AddPage();

        // Título
        $pdf->SetTextColor(28, 27, 25);
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(0, 9, t('Reporte semanal de actividades'), 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(95, 93, 87);
        $pdf->Cell(0, 5, t($conv['carrera'] . ' · ' . $conv['periodo']), 0, 1);
        $pdf->Ln(5);

        // Ficha
        $ficha = [
            ['Estudiante', $p['nombres'] . ' ' . $p['apellidos']],
            ['Cédula', $p['cedula']],
            ['Correo', $p['email']],
            ['Proyecto', $p['titulo']],
            ['Semana', $semana . ' · del ' . fecha_larga($primera) . ' al ' . fecha_larga($ultima)],
            ['Generado', fecha_hora(date('Y-m-d H:i:s'))],
        ];
        foreach ($ficha as [$k, $v]) {
            $pdf->SetFont('Helvetica', '', 8.5);
            $pdf->SetTextColor(95, 93, 87);
            $pdf->Cell(28, 6, t(mb_strtoupper($k)), 0, 0);
            $pdf->SetFont('Helvetica', $k === 'Proyecto' ? 'B' : '', 10);
            $pdf->SetTextColor(28, 27, 25);
            $pdf->MultiCell(0, 6, t($v), 0, 'L');
        }
        $pdf->Ln(4);

        // Resumen
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 7, t('Resumen de la semana'), 0, 1);
        $w = [12, 44, 32, 30, 0];
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(95, 93, 87);
        $pdf->SetDrawColor(222, 219, 210);
        $heads = ['Clase', 'Fecha', 'Evidencia', 'Registro', 'Capturas'];
        foreach ($heads as $i => $h) {
            $pdf->Cell($w[$i] ?: 0, 7, t(mb_strtoupper($h)), 'B', $i === 4 ? 1 : 0);
        }
        $pdf->SetTextColor(28, 27, 25);
        $pdf->SetFont('Helvetica', '', 9.5);
        foreach ($sesiones as $s) {
            $a = $avances[(int)$s['id']] ?? null;
            if ($s['estado'] === 'suspendida') {
                $estado = 'Clase suspendida';
            } elseif (!$a) {
                $estado = 'Sin registro';
            } else {
                $estado = $a['tardio'] ? 'Tardío' : 'A tiempo';
            }
            $pdf->Cell($w[0], 7, (string)$s['numero'], 'B');
            $pdf->Cell($w[1], 7, t(fecha_larga($s['fecha'])), 'B');
            $pdf->Cell($w[2], 7, t($a ? tipo_label($a['tipo']) : '—'), 'B');
            $pdf->Cell($w[3], 7, t($estado), 'B');
            $pdf->Cell(0, 7, $a ? (string)count($a['capturas']) : '0', 'B', 1);
        }
        $pdf->Ln(6);

        // Detalle por clase
        foreach ($sesiones as $s) {
            $a = $avances[(int)$s['id']] ?? null;
            if (!$a) {
                continue;
            }
            if ($pdf->GetY() > 230) {
                $pdf->AddPage();
            }
            $pdf->SetFillColor(31, 78, 95);
            $pdf->Rect(18, $pdf->GetY() + 1.2, 1.2, 5, 'F');
            $pdf->SetX(22);
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetTextColor(28, 27, 25);
            $pdf->Cell(0, 7, t('Clase ' . $s['numero'] . ' · ' . fecha_larga($s['fecha']) . ($s['tema'] ? ' · ' . $s['tema'] : '')), 0, 1);
            $pdf->SetFont('Helvetica', '', 8.5);
            $pdf->SetTextColor(95, 93, 87);
            $pdf->Cell(0, 5, t(tipo_label($a['tipo']) . ' · registrado el ' . fecha_hora($a['created_at']) . ($a['tardio'] ? ' (tardío)' : '')), 0, 1);
            $pdf->Ln(1);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(28, 27, 25);
            $pdf->MultiCell(0, 5.4, t($a['actividad']));
            if ($a['enlace']) {
                $pdf->SetFont('Courier', '', 8.5);
                $pdf->SetTextColor(31, 78, 95);
                $pdf->Cell(0, 6, t($a['enlace']), 0, 1, 'L', false, $a['enlace']);
            }
            $pdf->Ln(2);
            foreach ($a['capturas'] as $c) {
                $path = Upload::path($c['ruta']);
                if (!$path) {
                    continue;
                }
                [$iw, $ih] = getimagesize($path);
                $maxW = 174;
                $maxH = 105;
                $scale = min($maxW / $iw, $maxH / $ih);
                $wmm = $iw * $scale;
                $hmm = $ih * $scale;
                if ($pdf->GetY() + $hmm > 275) {
                    $pdf->AddPage();
                }
                $y = $pdf->GetY();
                $pdf->SetDrawColor(222, 219, 210);
                $pdf->Rect(18, $y, $wmm, $hmm);
                $pdf->Image($path, 18, $y, $wmm, $hmm, 'JPG');
                $pdf->SetY($y + $hmm + 4);
            }
            $pdf->Ln(4);
        }

        return ['pdf' => $pdf->Output('S'), 'codigo' => $rep['codigo']];
    }
}
