<div class="page-head">
  <div>
    <p class="eyebrow">Inscripción</p>
    <h1>Unirse a una convocatoria</h1>
    <p class="lead"><?= $conv ? 'Actualmente estás en «' . e($conv['nombre']) . '». Si tu docente te asignó otra, escribe su código.' : 'Escribe el código que te dio tu docente.' ?></p>
  </div>
</div>
<form class="form panel panel-pad" style="max-width:480px" method="post" action="<?= e(url('unirse')) ?>" novalidate>
  <?= csrf_field() ?>
  <div class="field">
    <label for="codigo">Código de convocatoria</label>
    <input class="input code-input" id="codigo" name="codigo" required maxlength="12" autocomplete="off" value="<?= e(old('codigo')) ?>"<?= invalid('codigo') ?>>
    <?= field_error('codigo') ?>
  </div>
  <div class="actions">
    <button class="btn btn-primary" type="submit">Unirme</button>
    <?php if ($conv): ?><a class="btn btn-ghost" href="<?= e(url('panel')) ?>">Cancelar</a><?php endif; ?>
  </div>
</form>
