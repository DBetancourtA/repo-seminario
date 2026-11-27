<?php
$correo = (string) cfg('app.correo_docente', '');
$hechos = count(array_filter($entregas, fn($e) => $e['estado'] === 'aprobado'));
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('panel')) ?>">Mi seminario</a><span aria-hidden="true">/</span><span>Entregables</span></p>
    <h1>Entregables del proyecto</h1>
    <p class="lead">No se suben archivos aquí: pega el <b>enlace</b> de cada documento (Google Drive, OneDrive, YouTube…) y compártelo con tu docente.</p>
  </div>
  <?php if ($lista): ?><span class="badge badge-ok"><?= $hechos ?> de <?= count($lista) ?> aprobados</span><?php endif; ?>
</div>

<?php if (!$disponible): ?>
  <div class="panel empty"><strong>Esta sección todavía no está activa</strong>Tu docente la habilitará pronto.</div>
<?php elseif (!$p || !$editable && !$entregas): ?>
  <div class="note note-accent" style="margin-bottom:24px"><strong>Aún no puedes entregar</strong>Los entregables se habilitan cuando tu proyecto está aprobado. Mientras tanto, aquí puedes ver qué tendrás que entregar y cuándo.</div>
<?php endif; ?>

<?php if ($disponible && $correo): ?>
  <details class="share-help">
    <summary>Cómo compartir un archivo con tu docente (<?= e($correo) ?>)</summary>
    <ul>
      <li><b>Google Drive:</b> clic derecho en el archivo → <b>Compartir</b> → escribe <span class="mono"><?= e($correo) ?></span> → permiso <b>Lector</b> → <b>Enviar</b>. Luego <b>Copiar vínculo</b> y pégalo aquí.</li>
      <li><b>OneDrive:</b> <b>Compartir</b> → <b>Personas específicas</b> → <span class="mono"><?= e($correo) ?></span> → <b>Puede ver</b> → <b>Copiar vínculo</b>.</li>
      <li><b>YouTube:</b> sube el video como <b>No listado</b> (no privado) y pega el enlace.</li>
    </ul>
  </details>
<?php endif; ?>

<div class="deliverables">
<?php foreach ($lista as $ent):
  $en = $entregas[(int)$ent['id']] ?? null;
  $st = Entregables::estado($en, $conv, $ent);
  $lim = Entregables::limite($conv, (int)$ent['semana']);
  $puede = $editable && $st !== 'aprobado';
?>
  <article class="deliv deliv-<?= e($st) ?>" id="ent-<?= (int)$ent['id'] ?>">
    <div class="deliv-head">
      <div>
        <h2><?= e($ent['nombre']) ?></h2>
        <p class="small muted">Semana <?= (int)$ent['semana'] ?><?= $lim ? ' · hasta el ' . e(fecha_larga($lim)) : '' ?><?= $ent['descripcion'] ? ' · ' . e($ent['descripcion']) : '' ?></p>
      </div>
      <span class="badge <?= e(Entregables::estadoBadge($st)) ?>"><?= e(Entregables::estadoLabel($st)) ?></span>
    </div>

    <?php if ($en): ?>
      <p class="small">
        <a href="<?= e($en['url']) ?>" target="_blank" rel="noopener"><?= icon('link', 14) ?> Abrir en <?= e(Entregables::servicio($en['url'])) ?></a>
        <span class="muted"> · entregado el <?= e(fecha_hora($en['entregado_at'])) ?><?= $lim && substr($en['entregado_at'], 0, 10) > $lim ? ' (fuera de plazo)' : '' ?></span>
      </p>
      <?php if ($en['comentario']): ?>
        <div class="note <?= $en['estado'] === 'observado' ? 'note-warn' : 'note-accent' ?>"><strong>Docente</strong><?= nl2br(e($en['comentario'])) ?></div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($puede): ?>
      <form class="repo-inline" method="post" action="<?= e(url('entrega')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="entregable_id" value="<?= (int)$ent['id'] ?>">
        <label class="sr-only" for="url-<?= (int)$ent['id'] ?>">Enlace de <?= e($ent['nombre']) ?></label>
        <input class="input mono" id="url-<?= (int)$ent['id'] ?>" name="url" type="url"
               placeholder="<?= $ent['tipo'] === 'video' ? 'https://youtu.be/…' : ($ent['tipo'] === 'url' ? 'https://tu-sistema.com' : 'https://drive.google.com/file/d/…') ?>"
               value="<?= e(old('url_' . $ent['id'], $en['url'] ?? '')) ?>"<?= invalid('url_' . $ent['id']) ?>>
        <button class="btn<?= $en ? '' : ' btn-primary' ?>" type="submit"><?= $en ? 'Actualizar enlace' : 'Entregar' ?></button>
      </form>
      <?= field_error('url_' . $ent['id']) ?>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
</div>
