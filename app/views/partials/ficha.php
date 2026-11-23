<?php
$porCat = [];
foreach ($tecs as $t) { $porCat[$t['categoria']][] = $t; }
?>
<dl class="dl">
  <dt>Estudiante</dt>
  <dd><?= e($p['nombres'] . ' ' . $p['apellidos']) ?> <span class="muted small mono">· <?= e($p['cedula']) ?></span><br>
    <a class="small" href="mailto:<?= e($p['email']) ?>"><?= e($p['email']) ?></a><?= $p['telefono'] ? ' <span class="muted small">· ' . e($p['telefono']) . '</span>' : '' ?></dd>
  <dt>Descripción</dt>
  <dd><?= $p['descripcion'] ? nl2br(e($p['descripcion'])) : '<span class="muted">No registró descripción.</span>' ?></dd>
  <dt>Objetivo general</dt>
  <dd><?= $p['objetivo_general'] ? nl2br(e($p['objetivo_general'])) : '<span class="muted">No registró objetivo general.</span>' ?></dd>
  <dt>Objetivos específicos</dt>
  <dd>
    <?php if ($objetivos): ?>
      <ol class="objectives"><?php foreach ($objetivos as $o): ?><li><?= e($o['texto']) ?></li><?php endforeach; ?></ol>
    <?php else: ?><span class="muted">Ninguno.</span><?php endif; ?>
  </dd>
  <dt>Tecnologías</dt>
  <dd>
    <?php if ($tecs): ?>
      <div class="tech-groups">
        <?php foreach ($porCat as $cat => $list): ?>
          <div class="tech-group"><span><?= e(categoria_label($cat)) ?></span><div class="chips"><?php foreach ($list as $t): ?><span class="chip<?= $t['aprobada'] ? '' : ' pending' ?>" <?= $t['aprobada'] ? '' : 'title="Propuesta, pendiente de aprobar en el catálogo"' ?>><?= e($t['nombre']) ?></span><?php endforeach; ?></div></div>
        <?php endforeach; ?>
      </div>
    <?php else: ?><span class="muted">Aún no definidas.</span><?php endif; ?>
  </dd>
  <dt>Repositorio</dt>
  <dd><?= $p['repo_url'] ? '<a class="mono small" href="' . e($p['repo_url']) . '" target="_blank" rel="noopener">' . e($p['repo_url']) . '</a>' : '<span class="muted">No indicado.</span>' ?></dd>
  <dt>Capturas</dt>
  <dd>
    <?php if ($capturas): ?>
      <div class="shots">
        <?php foreach ($capturas as $c): $src = url('archivo', ['t' => 'proyecto', 'id' => $c['id']]); ?>
          <div class="shot"><a href="<?= e($src) ?>" target="_blank" rel="noopener"><img src="<?= e($src) ?>" alt="Captura del proyecto" loading="lazy"></a></div>
        <?php endforeach; ?>
      </div>
    <?php else: ?><span class="muted">Sin capturas.</span><?php endif; ?>
  </dd>
</dl>
