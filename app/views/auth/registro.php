<?php $wide = true; ?>
<h2>Crear cuenta</h2>
<p class="lead">Tu docente te dará el código de la convocatoria a la que perteneces.</p>
<form class="form" method="post" action="<?= e(url('registro')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="field">
    <label for="codigo">Código de convocatoria <span class="req" aria-hidden="true">*</span></label>
    <input class="input code-input" id="codigo" name="codigo" required maxlength="12" autocomplete="off" spellcheck="false" value="<?= e(old('codigo', $codigo)) ?>"<?= invalid('codigo') ?>>
    <?= field_error('codigo') ?>
  </div>
  <div class="row">
    <div class="field">
      <label for="nombres">Nombres <span class="req" aria-hidden="true">*</span></label>
      <input class="input" id="nombres" name="nombres" required autocomplete="given-name" value="<?= e(old('nombres')) ?>"<?= invalid('nombres') ?>>
      <?= field_error('nombres') ?>
    </div>
    <div class="field">
      <label for="apellidos">Apellidos <span class="req" aria-hidden="true">*</span></label>
      <input class="input" id="apellidos" name="apellidos" required autocomplete="family-name" value="<?= e(old('apellidos')) ?>"<?= invalid('apellidos') ?>>
      <?= field_error('apellidos') ?>
    </div>
  </div>
  <div class="row">
    <div class="field">
      <label for="cedula">Cédula <span class="req" aria-hidden="true">*</span></label>
      <input class="input mono" id="cedula" name="cedula" required inputmode="numeric" maxlength="10" value="<?= e(old('cedula')) ?>"<?= invalid('cedula') ?>>
      <?= field_error('cedula') ?>
    </div>
    <div class="field">
      <label for="telefono">Teléfono <span class="opt">(opcional)</span></label>
      <input class="input" id="telefono" name="telefono" type="tel" autocomplete="tel" value="<?= e(old('telefono')) ?>"<?= invalid('telefono') ?>>
      <?= field_error('telefono') ?>
    </div>
  </div>
  <div class="field">
    <label for="email">Correo <span class="req" aria-hidden="true">*</span></label>
    <input class="input" id="email" name="email" type="email" required autocomplete="email" value="<?= e(old('email')) ?>"<?= invalid('email') ?>>
    <?= field_error('email') ?>
  </div>
  <div class="row">
    <div class="field">
      <label for="password">Contraseña <span class="req" aria-hidden="true">*</span></label>
      <input class="input" id="password" name="password" type="password" required minlength="8" autocomplete="new-password" aria-describedby="pass-help"<?= invalid('password') ?>>
      <p class="help" id="pass-help">Mínimo 8 caracteres.</p>
      <?= field_error('password') ?>
    </div>
    <div class="field">
      <label for="password2">Repite la contraseña <span class="req" aria-hidden="true">*</span></label>
      <input class="input" id="password2" name="password2" type="password" required autocomplete="new-password"<?= invalid('password2') ?>>
      <?= field_error('password2') ?>
    </div>
  </div>
  <button class="btn btn-primary" type="submit">Crear cuenta</button>
</form>
<p class="auth-switch">¿Ya tienes cuenta? <a href="<?= e(url('login')) ?>">Ingresa</a>.</p>
