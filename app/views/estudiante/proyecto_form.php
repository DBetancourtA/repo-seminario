<?php
$u = Auth::user();
$objs = old('objetivos', $objetivos);
if (!$objs) { $objs = ['']; }
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('panel')) ?>">Mi seminario</a><span aria-hidden="true">/</span><span><?= $p ? 'Editar proyecto' : 'Registro' ?></span></p>
    <h1><?= $p ? 'Editar proyecto' : ($nuevo ? 'Nueva propuesta de proyecto' : 'Registrar proyecto de titulación') ?></h1>
    <p class="lead">Solo el título y tus datos son obligatorios. Lo demás ayuda a tu docente a aprobar la propuesta más rápido.</p>
  </div>
</div>

<?php if ($p && $p['estado'] === 'observado'): ?>
  <div class="note note-warn" style="margin-bottom:24px"><strong>Observaciones del docente</strong><span style="white-space:pre-line"><?= e($p['observacion']) ?></span></div>
<?php endif; ?>

<div class="grid-2">
<form class="form" method="post" action="<?= e(url('proyecto', $nuevo ? ['nuevo' => 1] : [])) ?>" enctype="multipart/form-data" novalidate data-busy="Enviando…">
  <?= csrf_field() ?>

  <fieldset class="fieldset">
    <div class="legend-row"><legend>Tus datos</legend><p>Aparecerán en tus reportes semanales.</p></div>
    <div class="row">
      <div class="field">
        <label for="nombres">Nombres <span class="req" aria-hidden="true">*</span></label>
        <input class="input" id="nombres" name="nombres" required value="<?= e(old('nombres', $u['nombres'])) ?>"<?= invalid('nombres') ?>>
        <?= field_error('nombres') ?>
      </div>
      <div class="field">
        <label for="apellidos">Apellidos <span class="req" aria-hidden="true">*</span></label>
        <input class="input" id="apellidos" name="apellidos" required value="<?= e(old('apellidos', $u['apellidos'])) ?>"<?= invalid('apellidos') ?>>
        <?= field_error('apellidos') ?>
      </div>
    </div>
    <div class="row-3">
      <div class="field">
        <label for="cedula">Cédula</label>
        <input class="input mono" id="cedula" value="<?= e($u['cedula']) ?>" readonly aria-describedby="ro-help">
      </div>
      <div class="field">
        <label for="email">Correo</label>
        <input class="input" id="email" value="<?= e($u['email']) ?>" readonly aria-describedby="ro-help">
      </div>
      <div class="field">
        <label for="telefono">Teléfono <span class="opt">(opcional)</span></label>
        <input class="input" id="telefono" name="telefono" type="tel" value="<?= e(old('telefono', $u['telefono'])) ?>"<?= invalid('telefono') ?>>
        <?= field_error('telefono') ?>
      </div>
    </div>
    <p class="help" id="ro-help">La cédula y el correo solo los puede cambiar tu docente.</p>
  </fieldset>

  <fieldset class="fieldset">
    <div class="legend-row"><legend>Proyecto</legend><p>Describe qué vas a construir y para quién.</p></div>
    <div class="field">
      <label for="titulo">Título del proyecto <span class="req" aria-hidden="true">*</span></label>
      <input class="input" id="titulo" name="titulo" required maxlength="200" value="<?= e(old('titulo', $p['titulo'] ?? '')) ?>"<?= invalid('titulo') ?>>
      <?= field_error('titulo') ?>
    </div>
    <div class="field">
      <label for="descripcion">Descripción breve <span class="opt">(opcional)</span></label>
      <textarea class="textarea" id="descripcion" name="descripcion" maxlength="1000" data-count data-max="1000"<?= invalid('descripcion') ?>><?= e(old('descripcion', $p['descripcion'] ?? '')) ?></textarea>
      <div class="counter" id="descripcion-count" aria-live="polite"></div>
      <?= field_error('descripcion') ?>
    </div>
    <div class="field">
      <label for="objetivo_general">Objetivo general <span class="opt">(opcional)</span></label>
      <textarea class="textarea" id="objetivo_general" name="objetivo_general" rows="3" style="min-height:84px"><?= e(old('objetivo_general', $p['objetivo_general'] ?? '')) ?></textarea>
    </div>
    <div class="field">
      <span class="label" id="obj-label">Objetivos específicos <span class="opt">(opcional, hasta 6)</span></span>
      <div class="objective-list" id="objetivos" role="group" aria-labelledby="obj-label">
        <?php foreach ($objs as $i => $o): ?>
          <div class="objective-item">
            <span class="n"><?= $i + 1 ?>.</span>
            <textarea class="textarea" name="objetivos[]" rows="2" style="min-height:42px" aria-label="Objetivo específico <?= $i + 1 ?>"><?= e($o) ?></textarea>
            <button class="linkbtn" type="button" data-remove aria-label="Quitar objetivo"><?= icon('x') ?></button>
          </div>
        <?php endforeach; ?>
      </div>
      <div><button class="btn btn-ghost btn-sm" type="button" id="obj-add"><?= icon('plus', 14) ?> Agregar objetivo</button></div>
      <?= field_error('objetivos') ?>
      <template id="obj-tpl">
        <div class="objective-item">
          <span class="n"></span>
          <textarea class="textarea" name="objetivos[]" rows="2" style="min-height:42px"></textarea>
          <button class="linkbtn" type="button" data-remove aria-label="Quitar objetivo"><?= icon('x') ?></button>
        </div>
      </template>
    </div>
    <div class="field">
      <label for="repo_url">Repositorio Git <span class="opt">(opcional)</span></label>
      <input class="input mono" id="repo_url" name="repo_url" type="url" placeholder="https://github.com/usuario/proyecto" value="<?= e(old('repo_url', $p['repo_url'] ?? '')) ?>"<?= invalid('repo_url') ?>>
      <?= field_error('repo_url') ?>
      <?php partial('repo_hint', ['for' => 'repo_url']); ?>
    </div>
  </fieldset>

  <fieldset class="fieldset">
    <div class="legend-row"><legend>Capturas <span class="opt" style="font-weight:400">(opcional)</span></legend><p>Si ya tienes algo hecho — el login, una pantalla, un prototipo — súbelo. Hasta 4 imágenes.</p></div>
    <?php if ($capturas): ?>
      <div class="shots">
        <?php foreach ($capturas as $c): ?>
          <div class="shot">
            <a href="<?= e(url('archivo', ['t' => 'proyecto', 'id' => $c['id']])) ?>" target="_blank" rel="noopener"><img src="<?= e(url('archivo', ['t' => 'proyecto', 'id' => $c['id']])) ?>" alt="Captura del proyecto" loading="lazy"></a>
            <button class="linkbtn shot-del" type="submit" form="del-<?= (int)$c['id'] ?>" aria-label="Eliminar captura"><?= icon('x', 16) ?></button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (count($capturas) < 4): ?>
    <div class="drop" data-previews="prev-proy" data-max="<?= 4 - count($capturas) ?>">
      <input type="file" name="capturas[]" accept="image/png,image/jpeg,image/webp" multiple aria-label="Subir capturas del proyecto"<?= invalid('capturas') ?>>
      <?= icon('image', 22) ?>
      <strong>Arrastra imágenes o haz clic para elegir</strong>
      <span>PNG, JPG o WebP · máx. <?= (int)cfg('app.max_mb', 5) ?> MB c/u</span>
    </div>
    <p class="field-error" id="prev-proy-msg" aria-live="polite"></p>
    <div class="previews" id="prev-proy"></div>
    <?php endif; ?>
    <?= field_error('capturas') ?>
  </fieldset>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit"><?= $p ? ($p['estado'] === 'observado' ? 'Reenviar a revisión' : 'Guardar cambios') : 'Enviar a revisión' ?></button>
    <a class="btn btn-ghost" href="<?= e(url('panel')) ?>">Cancelar</a>
  </div>
</form>

<aside>
  <div class="panel panel-pad">
    <h2 style="font-size:15px;margin-bottom:12px">Qué revisa el docente</h2>
    <ul style="margin:0;padding-left:18px;display:grid;gap:8px;font-size:14px;color:var(--ink-2)">
      <li>Que el proyecto resuelva un problema real y concreto.</li>
      <li>Que tenga lógica de programación propia, no solo una plantilla.</li>
      <li>Que el alcance quepa en <?= (int)$conv['semanas'] - 1 ?> semanas de desarrollo.</li>
      <li>Que los objetivos se puedan comprobar al final.</li>
    </ul>
  </div>
  <?php if ($historial): ?>
  <div class="section" style="margin-top:24px">
    <div class="section-head"><h2>Historial</h2></div>
    <?php partial('historial', ['historial' => $historial]); ?>
  </div>
  <?php endif; ?>
</aside>
</div>

<?php foreach ($capturas as $c): ?>
  <form id="del-<?= (int)$c['id'] ?>" method="post" action="<?= e(url('proyecto/captura/borrar')) ?>" data-confirm="¿Eliminar esta captura?" hidden>
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
  </form>
<?php endforeach; ?>
