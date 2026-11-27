<?php $val = fn($k) => old($k, $u[$k] ?? ''); ?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('admin/usuarios')) ?>">Usuarios</a><span aria-hidden="true">/</span><span><?= $u['rol'] === 'admin' ? 'Docente' : 'Estudiante' ?></span></p>
    <h1><?= e($u['nombres'] . ' ' . $u['apellidos']) ?></h1>
    <p class="lead">
      <?= $u['activo'] ? '<span class="badge badge-ok">Activo</span>' : '<span class="badge badge-danger">Desactivado</span>' ?>
      · Registrado el <?= e(fecha_hora($u['created_at'])) ?>
      <?php if (!empty($u['ultimo_ingreso'])): ?> · Último ingreso: <?= e(fecha_hora($u['ultimo_ingreso'])) ?><?php endif; ?>
    </p>
  </div>
  <?php if ($info && $info['pid']): ?><a class="btn" href="<?= e(url('admin/proyecto', ['id' => $info['pid']])) ?>">Ver proyecto</a><?php endif; ?>
</div>

<div class="grid-2">
  <form class="form" method="post" action="<?= e(url('admin/usuario', ['id' => $u['id']])) ?>" novalidate>
    <?= csrf_field() ?>
    <fieldset class="fieldset">
      <div class="legend-row"><legend>Datos personales</legend><?php if ($info && $info['pid']): ?><p>Los cambios quedan en el registro de cambios de su proyecto.</p><?php endif; ?></div>
      <div class="row">
        <div class="field"><label for="nombres">Nombres</label><input class="input" id="nombres" name="nombres" value="<?= e($val('nombres')) ?>"<?= invalid('nombres') ?>><?= field_error('nombres') ?></div>
        <div class="field"><label for="apellidos">Apellidos</label><input class="input" id="apellidos" name="apellidos" value="<?= e($val('apellidos')) ?>"<?= invalid('apellidos') ?>><?= field_error('apellidos') ?></div>
      </div>
      <div class="row-3">
        <div class="field"><label for="cedula">Cédula</label><input class="input mono" id="cedula" name="cedula" inputmode="numeric" value="<?= e($val('cedula')) ?>"<?= invalid('cedula') ?>><?= field_error('cedula') ?></div>
        <div class="field"><label for="email">Correo</label><input class="input" id="email" name="email" type="email" value="<?= e($val('email')) ?>"<?= invalid('email') ?>><?= field_error('email') ?></div>
        <div class="field"><label for="telefono">Teléfono</label><input class="input" id="telefono" name="telefono" type="tel" value="<?= e($val('telefono')) ?>"<?= invalid('telefono') ?>><?= field_error('telefono') ?></div>
      </div>
    </fieldset>
    <div class="form-actions"><button class="btn btn-primary" type="submit">Guardar datos</button></div>
  </form>

  <aside style="display:grid;gap:16px">
    <?php if ($u['rol'] === 'estudiante'): ?>
      <div class="panel panel-pad" style="display:grid;gap:10px">
        <h2 style="font-size:15px">Contraseña</h2>
        <p class="help">Genera una contraseña temporal si la olvidó. Se muestra una sola vez.</p>
        <form method="post" action="<?= e(url('admin/estudiante/clave')) ?>" data-confirm="¿Generar una contraseña temporal para <?= e($u['nombres']) ?>? La actual dejará de funcionar.">
          <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
          <button class="btn" type="submit"><?= icon('key', 16) ?> Restablecer contraseña</button>
        </form>
      </div>

      <div class="panel panel-pad" style="display:grid;gap:10px">
        <h2 style="font-size:15px">Convocatoria</h2>
        <p class="small">Actual: <b><?= $info && $info['conv_nombre'] ? e($info['conv_nombre'] . ' · ' . $info['conv_periodo']) : 'ninguna' ?></b></p>
        <?php if ($avances > 0): ?>
          <p class="help">No se puede cambiar: ya registró <?= $avances ?> avance(s) en el calendario de esta convocatoria.</p>
        <?php else: ?>
          <form method="post" action="<?= e(url('admin/usuario/convocatoria')) ?>" style="display:grid;gap:8px" data-confirm="¿Mover a <?= e($u['nombres']) ?> a la convocatoria elegida? Su proyecto se mueve con él.">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <label class="sr-only" for="conv">Nueva convocatoria</label>
            <select class="select" id="conv" name="conv">
              <?php foreach ($convs as $c): ?><option value="<?= (int)$c['id'] ?>"<?= $info && (int)$info['conv_id'] === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['nombre'] . ' · ' . $c['periodo']) ?></option><?php endforeach; ?>
            </select>
            <button class="btn" type="submit">Cambiar de convocatoria</button>
          </form>
          <p class="help">Útil si se inscribió con el código equivocado.</p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="panel panel-pad" style="display:grid;gap:10px">
        <h2 style="font-size:15px">Contraseña</h2>
        <?php if ($esYo): ?><a class="btn" href="<?= e(url('cuenta/clave')) ?>"><?= icon('key', 16) ?> Cambiar mi contraseña</a>
        <?php else: ?><p class="help">Los docentes cambian su contraseña desde el ícono de la llave.</p><?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!$esYo): ?>
      <div class="panel panel-pad" style="display:grid;gap:10px">
        <h2 style="font-size:15px">Acceso a la plataforma</h2>
        <?php if ($u['activo']): ?>
          <p class="help">Desactivar impide que ingrese. Su proyecto, bitácora y reportes se conservan y puedes reactivarlo cuando quieras.</p>
          <form method="post" action="<?= e(url('admin/usuario/estado')) ?>" data-confirm="¿Desactivar la cuenta de <?= e($u['nombres']) ?>? No podrá ingresar hasta que la reactives.">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="btn btn-danger" type="submit" name="accion" value="desactivar">Desactivar cuenta</button>
          </form>
        <?php else: ?>
          <p class="help">La cuenta está desactivada: no puede ingresar.</p>
          <form method="post" action="<?= e(url('admin/usuario/estado')) ?>">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="btn btn-ok" type="submit" name="accion" value="activar">Reactivar cuenta</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (count($inscripciones) > 1): ?>
      <div>
        <div class="section-head"><h2>Convocatorias anteriores</h2></div>
        <ul class="alert-list"><?php foreach ($inscripciones as $i): ?><li><span><?= e($i['nombre'] . ' · ' . $i['periodo']) ?></span><span class="muted small"><?= e(fecha_corta($i['created_at'])) ?></span></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
  </aside>
</div>
