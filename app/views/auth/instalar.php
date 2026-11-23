<?php $wide = true; ?>
<p class="eyebrow">Configuración inicial</p>
<h2>Cuenta del docente</h2>
<p class="lead">Esta cuenta administra convocatorias y aprueba proyectos. Esta pantalla deja de estar disponible después de crearla.</p>
<form class="form" method="post" action="<?= e(url('instalar')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="row">
    <div class="field">
      <label for="nombres">Nombres</label>
      <input class="input" id="nombres" name="nombres" required value="<?= e(old('nombres')) ?>"<?= invalid('nombres') ?>>
      <?= field_error('nombres') ?>
    </div>
    <div class="field">
      <label for="apellidos">Apellidos</label>
      <input class="input" id="apellidos" name="apellidos" required value="<?= e(old('apellidos')) ?>"<?= invalid('apellidos') ?>>
      <?= field_error('apellidos') ?>
    </div>
  </div>
  <div class="row">
    <div class="field">
      <label for="cedula">Cédula</label>
      <input class="input mono" id="cedula" name="cedula" required inputmode="numeric" value="<?= e(old('cedula')) ?>"<?= invalid('cedula') ?>>
      <?= field_error('cedula') ?>
    </div>
    <div class="field">
      <label for="email">Correo</label>
      <input class="input" id="email" name="email" type="email" required value="<?= e(old('email')) ?>"<?= invalid('email') ?>>
      <?= field_error('email') ?>
    </div>
  </div>
  <div class="field">
    <label for="password">Contraseña</label>
    <input class="input" id="password" name="password" type="password" required minlength="10" autocomplete="new-password"<?= invalid('password') ?>>
    <?= field_error('password') ?>
  </div>
  <button class="btn btn-primary" type="submit">Crear cuenta de docente</button>
</form>
