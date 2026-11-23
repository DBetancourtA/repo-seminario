<h2>Ingresar</h2>
<p class="lead">Usa el correo con el que te registraste.</p>
<form class="form" method="post" action="<?= e(url('login')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="field">
    <label for="email">Correo</label>
    <input class="input" id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>"<?= invalid('email') ?>>
    <?= field_error('email') ?>
  </div>
  <div class="field">
    <label for="password">Contraseña</label>
    <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
  </div>
  <button class="btn btn-primary" type="submit">Ingresar</button>
</form>
<p class="auth-switch">¿Primera vez? <a href="<?= e(url('registro')) ?>">Crea tu cuenta con el código de tu convocatoria</a>.</p>
