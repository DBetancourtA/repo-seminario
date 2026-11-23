<?php
$total = array_sum($porEstado);
$aprobados = ($porEstado['aprobado'] ?? 0) + ($porEstado['tecnologias_definidas'] ?? 0) + ($porEstado['en_desarrollo'] ?? 0) + ($porEstado['finalizado'] ?? 0);
$orden = ['en_revision' => 'accent', 'observado' => 'warn', 'aprobado' => 'ok', 'tecnologias_definidas' => 'ok', 'en_desarrollo' => 'ok', 'finalizado' => '', 'rechazado' => 'danger'];
$maxEstado = max(1, ...array_values($porEstado ?: [0]));
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><span>Tablero</span><span aria-hidden="true">·</span><span><?= e($conv['periodo']) ?></span></p>
    <h1><?= e($conv['nombre']) ?></h1>
    <p class="lead">Semana <?= max(0, $actual) ?> de <?= (int)$conv['semanas'] ?> · código de inscripción <span class="mono" style="color:var(--ink)"><?= e($conv['codigo_inscripcion']) ?></span></p>
  </div>
  <div class="actions">
    <?php if (count($convs) > 1): ?>
    <form method="get" action="<?= e(base_url() . '/index.php') ?>" class="actions">
      <input type="hidden" name="r" value="admin">
      <label class="sr-only" for="conv">Convocatoria</label>
      <select class="select" id="conv" name="conv" onchange="this.form.submit()" style="min-height:40px">
        <?php foreach ($convs as $c): ?><option value="<?= (int)$c['id'] ?>"<?= (int)$c['id'] === (int)$conv['id'] ? ' selected' : '' ?>><?= e($c['nombre'] . ' · ' . $c['periodo']) ?></option><?php endforeach; ?>
      </select>
      <noscript><button class="btn" type="submit">Ver</button></noscript>
    </form>
    <?php endif; ?>
    <a class="btn" href="<?= e(url('admin/exportar', ['conv' => $conv['id']])) ?>"><?= icon('download', 16) ?> CSV</a>
    <a class="btn" href="<?= e(url('admin/convocatoria/ver', ['id' => $conv['id']])) ?>">Calendario</a>
  </div>
</div>

<div class="kpis">
  <div class="kpi"><div class="k">Inscritos</div><div class="v"><?= $inscritos ?></div><div class="d"><?= count($sinProyecto) ?> sin proyecto</div></div>
  <div class="kpi"><div class="k">Proyectos</div><div class="v"><?= $conProyecto ?></div><div class="d"><?= $inscritos ? round($conProyecto * 100 / $inscritos) : 0 ?>% de los inscritos</div></div>
  <div class="kpi"><div class="k">Por revisar</div><div class="v"><a href="<?= e(url('admin/proyectos', ['estado' => 'en_revision'])) ?>" style="text-decoration:none"><?= (int)($porEstado['en_revision'] ?? 0) ?></a></div><div class="d"><?= (int)($porEstado['observado'] ?? 0) ?> con observaciones</div></div>
  <div class="kpi"><div class="k">Aprobados</div><div class="v"><?= $aprobados ?></div><div class="d"><?= $conStack ?> con stack definido</div></div>
</div>

<div class="grid-2" style="margin-top:8px">
  <div>
    <section class="section" aria-labelledby="h-estado">
      <div class="section-head"><h2 id="h-estado">Estado de los proyectos</h2><span class="meta"><?= $total ?> en total</span></div>
      <?php if (!$total): ?>
        <p class="muted">Todavía nadie registra su proyecto.</p>
      <?php else: ?>
      <div class="funnel">
        <?php foreach ($orden as $st => $tone): $n = (int)($porEstado[$st] ?? 0); ?>
          <div class="funnel-row">
            <a href="<?= e(url('admin/proyectos', ['estado' => $st])) ?>" style="color:var(--ink);text-decoration:none"><?= e(estado_label($st)) ?></a>
            <div class="bar <?= $tone ?>" role="img" aria-label="<?= $n ?> proyectos"><span style="width:<?= round($n * 100 / $maxEstado) ?>%"></span></div>
            <span class="mono" style="text-align:right"><?= $n ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

    <section class="section" aria-labelledby="h-tec">
      <div class="section-head"><h2 id="h-tec">Tecnologías elegidas</h2><span class="meta">sobre <?= $conStack ?> proyecto<?= $conStack === 1 ? '' : 's' ?> con stack definido</span></div>
      <?php if (!$tecPorCat): ?>
        <p class="muted">Las tecnologías aparecen aquí cuando los estudiantes con proyecto aprobado las definen.</p>
      <?php else: ?>
      <div class="cat-grid">
        <?php foreach (['lenguaje', 'base_datos', 'backend', 'frontend', 'plataforma', 'infraestructura'] as $cat): if (empty($tecPorCat[$cat])) continue; $list = array_slice($tecPorCat[$cat], 0, 6); ?>
          <div>
            <h3><?= e(categoria_label($cat)) ?> <small><?= count($tecPorCat[$cat]) ?> distintas</small></h3>
            <div class="rank">
              <?php foreach ($list as $t): $pct = $conStack ? round($t['n'] * 100 / $conStack) : 0; ?>
                <div class="rank-row">
                  <span class="name" title="<?= e($t['nombre']) ?>"><?= e($t['nombre']) ?><?= $t['aprobada'] ? '' : ' *' ?></span>
                  <div class="bar <?= $cat === 'lenguaje' ? 'accent' : '' ?>" role="img" aria-label="<?= (int)$t['n'] ?> proyectos, <?= $pct ?>%"><span style="width:<?= $pct ?>%"></span></div>
                  <span class="n"><?= (int)$t['n'] ?> · <?= $pct ?>%</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

    <?php if ($stacks): ?>
    <section class="section" aria-labelledby="h-stack">
      <div class="section-head"><h2 id="h-stack">Combinaciones más usadas</h2><span class="meta">lenguaje + backend + base de datos</span></div>
      <div class="table-wrap"><table class="table">
        <thead><tr><th scope="col">Stack</th><th scope="col" class="num">Proyectos</th></tr></thead>
        <tbody><?php foreach ($stacks as $s): ?><tr><td><?= e($s['combo']) ?></td><td class="num"><?= (int)$s['n'] ?></td></tr><?php endforeach; ?></tbody>
      </table></div>
    </section>
    <?php endif; ?>
  </div>

  <aside class="alerts section" aria-label="Pendientes">
    <div>
      <div class="section-head"><h2>Esperando tu revisión</h2><span class="meta"><?= count($pendientes) ?></span></div>
      <?php if (!$pendientes): ?><p class="muted small">Nada pendiente.</p><?php else: ?>
      <ul class="alert-list">
        <?php foreach (array_slice($pendientes, 0, 8) as $r): ?>
          <li><a href="<?= e(url('admin/proyecto', ['id' => $r['id']])) ?>"><?= e($r['apellidos'] . ', ' . explode(' ', $r['nombres'])[0]) ?></a><span class="muted small nowrap"><?= e(fecha_corta($r['updated_at'])) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <?php if ($solicitudes): ?>
    <div>
      <div class="section-head"><h2>Solicitudes de cambio</h2><span class="meta"><?= count($solicitudes) ?></span></div>
      <ul class="alert-list">
        <?php foreach ($solicitudes as $r): ?>
          <li><a href="<?= e(url('admin/proyecto', ['id' => $r['pid']])) ?>"><?= e($r['apellidos'] . ', ' . explode(' ', $r['nombres'])[0]) ?></a><span class="muted small nowrap"><?= e(Cambios::label($r['campo'])) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <div>
      <div class="section-head"><h2>Sin registro en las 2 últimas clases</h2><span class="meta"><?= count($inactivos) ?></span></div>
      <?php if (!$inactivos): ?><p class="muted small">Todos al día.</p><?php else: ?>
      <ul class="alert-list">
        <?php foreach ($inactivos as $r): ?>
          <li><a href="<?= e(url('admin/proyecto', ['id' => $r['id']])) ?>"><?= e($r['apellidos'] . ', ' . explode(' ', $r['nombres'])[0]) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <div>
      <div class="section-head"><h2>Inscritos sin proyecto</h2><span class="meta"><?= count($sinProyecto) ?></span></div>
      <?php if (!$sinProyecto): ?><p class="muted small">Todos registraron su proyecto.</p><?php else: ?>
      <ul class="alert-list">
        <?php foreach ($sinProyecto as $r): ?>
          <li><span><?= e($r['apellidos'] . ', ' . explode(' ', $r['nombres'])[0]) ?></span><a class="small" href="mailto:<?= e($r['email']) ?>">correo</a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </aside>
</div>

<section class="section" aria-labelledby="h-heat">
  <div class="section-head"><h2 id="h-heat">Cumplimiento de la bitácora</h2><span class="meta">clases registradas / clases ya dictadas, por semana</span></div>
  <?php if (!$proys): ?>
    <p class="muted">Sin proyectos todavía.</p>
  <?php else: ?>
  <div class="table-wrap" style="padding:12px">
    <table class="heat">
      <thead><tr><th scope="col" class="who">Estudiante</th>
        <?php foreach ($weeks as $w => $ss): ?><th scope="col" class="<?= $w === $actual ? 'cur' : '' ?>">S<?= sprintf('%02d', $w) ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
        <?php foreach ($proys as $pr): ?>
          <tr>
            <th scope="row" class="who"><a href="<?= e(url('admin/proyecto', ['id' => $pr['id']])) ?>" style="color:var(--ink)" title="<?= e($pr['titulo']) ?>"><?= e($pr['apellidos'] . ', ' . explode(' ', $pr['nombres'])[0]) ?></a></th>
            <?php foreach ($weeks as $w => $ss):
              $exp = $esperadas[$w] ?? 0; $h = $hechos[(int)$pr['id']][$w]['n'] ?? 0;
              if ($exp === 0) { $c = 'hn'; $txt = $h ?: ''; }
              else { $ratio = $h / $exp; $c = $ratio >= 1 ? 'h3' : ($ratio >= .5 ? 'h2' : ($ratio > 0 ? 'h1' : 'h0')); $txt = $h . '/' . $exp; }
            ?>
              <td class="<?= $c ?>" title="Semana <?= $w ?>: <?= $h ?> de <?= $exp ?>"><a href="<?= e(url('admin/proyecto', ['id' => $pr['id'], 'semana' => $w])) ?>" style="color:inherit;text-decoration:none;display:block"><?= e((string)$txt) ?></a></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
