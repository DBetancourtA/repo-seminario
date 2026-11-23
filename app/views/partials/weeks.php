<?php
/**
 * @var array $weeks  semana => sesiones
 * @var array $avances sesion_id => avance
 * @var array $conv
 * @var int $actual
 * @var callable $cellLink fn(array $sesion, string $state): ?string
 * @var callable $weekLink fn(int $semana): string
 */
?>
<table class="weeks">
  <caption class="sr-only">Clases por semana y estado de registro</caption>
  <thead>
    <tr><th scope="col">Semana</th><th scope="col">Clases</th><th scope="col">Registro</th><th scope="col"><span class="sr-only">Acciones</span></th></tr>
  </thead>
  <tbody>
  <?php foreach ($weeks as $w => $ss):
      $normales = array_filter($ss, fn($s) => $s['estado'] === 'normal');
      $hechas = array_filter($normales, fn($s) => isset($avances[(int)$s['id']]));
      $first = $ss[0]['fecha']; $last = end($ss)['fecha'];
  ?>
    <tr class="<?= $w === $actual ? 'is-current' : '' ?>">
      <td>
        <span class="wk-num">S<?= sprintf('%02d', $w) ?></span>
        <?php if ($w === 1): ?> <span class="wk-tag">Definición</span><?php endif; ?>
        <?php if ($w === $actual): ?> <span class="wk-tag">· Esta semana</span><?php endif; ?>
        <span class="wk-label"><?= e(fecha_corta($first)) ?> – <?= e(fecha_corta($last)) ?></span>
      </td>
      <td>
        <div class="cells">
        <?php foreach ($ss as $s):
            $a = $avances[(int)$s['id']] ?? null;
            if ($s['estado'] === 'suspendida') { $st = 'off'; $txt = 'Suspendida'; }
            elseif ($a) { $st = $a['tardio'] ? 'late' : 'done'; $txt = $a['tardio'] ? 'Tardío' : 'Hecho'; }
            elseif (!Calendar::isOpen($conv, $s)) { $st = 'future'; $txt = ''; }
            elseif (Calendar::isLate($conv, $s)) { $st = 'missing'; $txt = 'Falta'; }
            else { $st = 'open'; $txt = 'Subir'; }
            $href = $cellLink($s, $st);
            $label = 'Clase ' . $s['numero'] . ', ' . fecha_larga($s['fecha']) . ($txt ? ': ' . $txt : ': aún no inicia');
            $tag = $href ? 'a' : 'span';
        ?>
          <<?= $tag ?> class="cell <?= $st ?>"<?= $href ? ' href="' . e($href) . '"' : '' ?> aria-label="<?= e($label) ?>" title="<?= e($label) ?>">
            <b><?= e(dia_corto($s['fecha'])) ?> <?= (new DateTime($s['fecha']))->format('j') ?></b>
            <span class="st"><?php if ($st === 'done') echo icon('check', 12); elseif ($st === 'late') echo icon('clock', 12); elseif ($st === 'open') echo icon('upload', 12); elseif ($st === 'off') echo icon('pause', 12); ?><?= e($txt) ?></span>
          </<?= $tag ?>>
        <?php endforeach; ?>
        </div>
      </td>
      <td class="nowrap"><span class="mono"><?= count($hechas) ?>/<?= count($normales) ?></span></td>
      <td class="nowrap" style="text-align:right"><a class="btn btn-ghost btn-sm" href="<?= e($weekLink($w)) ?>">Ver semana <?= icon('arrow', 14) ?></a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<div class="legend" aria-hidden="true">
  <span><i class="l-done"></i>Registrada</span>
  <span><i class="l-late"></i>Fuera de plazo</span>
  <span><i class="l-open"></i>Pendiente de subir</span>
  <span><i class="l-missing"></i>No registrada</span>
  <span><i class="l-off"></i>Suspendida</span>
</div>
