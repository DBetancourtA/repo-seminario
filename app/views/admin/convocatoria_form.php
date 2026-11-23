<?php
$dias = old('dias', $c ? array_map('intval', explode(',', $c['dias_clase'])) : [1, 2, 3, 4]);
$v = fn($k, $def = '') => old($k, $c[$k] ?? $def);
$nombresDias = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
?>
<div class="page-head">
  <div>
    <p class="eyebrow"><a href="<?= e(url('admin/convocatorias')) ?>">Convocatorias</a><span aria-hidden="true">/</span><span><?= $c ? 'Editar' : 'Nueva' ?></span></p>
    <h1><?= $c ? e($c['nombre']) : 'Nueva convocatoria' ?></h1>
    <p class="lead">Al guardar se genera el calendario de clases. Después puedes mover fechas o marcar clases suspendidas una por una.</p>
  </div>
</div>

<?php if ($bloqueado): ?>
  <div class="note note-warn" style="margin-bottom:24px"><strong>El calendario está bloqueado</strong>Ya hay avances registrados, así que la fecha de inicio, las semanas y los días no se pueden cambiar. Ajusta clases individuales desde la vista de la convocatoria.</div>
<?php endif; ?>

<form class="form" method="post" action="<?= e(url('admin/convocatoria', $c ? ['id' => $c['id']] : [])) ?>" novalidate style="max-width:760px">
  <?= csrf_field() ?>
  <fieldset class="fieldset">
    <div class="legend-row"><legend>Datos generales</legend></div>
    <div class="field">
      <label for="nombre">Nombre</label>
      <input class="input" id="nombre" name="nombre" required placeholder="Seminario de Titulación · Paralelo A" value="<?= e($v('nombre')) ?>"<?= invalid('nombre') ?>>
      <?= field_error('nombre') ?>
    </div>
    <div class="row">
      <div class="field">
        <label for="periodo">Periodo académico</label>
        <input class="input" id="periodo" name="periodo" required placeholder="2026-2" value="<?= e($v('periodo')) ?>"<?= invalid('periodo') ?>>
        <?= field_error('periodo') ?>
      </div>
      <div class="field">
        <label for="estado">Estado</label>
        <select class="select" id="estado" name="estado">
          <?php foreach (['abierta' => 'Inscripciones abiertas', 'en_curso' => 'En curso (sigue aceptando inscripciones)', 'borrador' => 'Borrador (no acepta inscripciones)', 'cerrada' => 'Cerrada'] as $k => $l): ?>
            <option value="<?= $k ?>"<?= $v('estado', 'abierta') === $k ? ' selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label for="carrera">Carrera</label>
      <input class="input" id="carrera" name="carrera" required value="<?= e($v('carrera', cfg('app.carrera'))) ?>"<?= invalid('carrera') ?>>
      <?= field_error('carrera') ?>
    </div>
  </fieldset>

  <fieldset class="fieldset">
    <div class="legend-row"><legend>Calendario</legend><p>La semana 1 es la de definición del proyecto.</p></div>
    <div class="row">
      <div class="field">
        <label for="fecha_inicio">Fecha de inicio</label>
        <input class="input" id="fecha_inicio" name="fecha_inicio" type="date" required value="<?= e($v('fecha_inicio')) ?>"<?= $bloqueado ? ' readonly' : '' ?><?= invalid('fecha_inicio') ?>>
        <?= field_error('fecha_inicio') ?>
      </div>
      <div class="field">
        <label for="semanas">Número de semanas</label>
        <input class="input" id="semanas" name="semanas" type="number" min="1" max="30" required value="<?= e($v('semanas', 12)) ?>"<?= $bloqueado ? ' readonly' : '' ?><?= invalid('semanas') ?>>
        <?= field_error('semanas') ?>
      </div>
    </div>
    <div class="field">
      <span class="label" id="dias-label">Días de clase</span>
      <div class="days" role="group" aria-labelledby="dias-label">
        <?php foreach ($nombresDias as $n => $l): ?>
          <label><input type="checkbox" name="dias[]" value="<?= $n ?>"<?= in_array($n, $dias, true) ? ' checked' : '' ?><?= $bloqueado ? ' onclick="return false"' : '' ?>> <?= $l ?></label>
        <?php endforeach; ?>
      </div>
      <?= field_error('dias') ?>
    </div>
    <div class="row-3">
      <div class="field">
        <label for="hora_inicio">Hora de inicio</label>
        <input class="input" id="hora_inicio" name="hora_inicio" type="time" required value="<?= e(substr((string)$v('hora_inicio', '18:00'), 0, 5)) ?>"<?= invalid('hora') ?>>
      </div>
      <div class="field">
        <label for="hora_fin">Hora de fin</label>
        <input class="input" id="hora_fin" name="hora_fin" type="time" required value="<?= e(substr((string)$v('hora_fin', '20:00'), 0, 5)) ?>"<?= invalid('hora') ?>>
      </div>
      <div class="field">
        <label for="ventana_horas">Plazo para registrar (h)</label>
        <input class="input" id="ventana_horas" name="ventana_horas" type="number" min="0" max="720" value="<?= e($v('ventana_horas', 48)) ?>" aria-describedby="vent-help"<?= invalid('ventana_horas') ?>>
      </div>
    </div>
    <?= field_error('hora') ?>
    <p class="help" id="vent-help">Horas después del fin de la clase. Lo que se registre después queda marcado como «tardío», no se bloquea.</p>
    <div class="field" style="max-width:320px">
      <label for="semana_congelamiento">Congelar la ficha desde la semana</label>
      <input class="input" id="semana_congelamiento" name="semana_congelamiento" type="number" min="0" max="30" value="<?= e($v('semana_congelamiento', 3)) ?>" aria-describedby="cong-help"<?= invalid('semana_congelamiento') ?>>
      <?= field_error('semana_congelamiento') ?>
    </div>
    <p class="help" id="cong-help">Antes de esa semana, con el proyecto aprobado, el estudiante puede completar campos vacíos y pedir cambios. Desde esa semana solo tú editas la ficha. 0 = nunca se congela.</p>
  </fieldset>

  <div class="form-actions">
    <button class="btn btn-primary" type="submit"><?= $c ? 'Guardar cambios' : 'Crear convocatoria' ?></button>
    <a class="btn btn-ghost" href="<?= e(url($c ? 'admin/convocatoria/ver' : 'admin/convocatorias', $c ? ['id' => $c['id']] : [])) ?>">Cancelar</a>
  </div>
</form>
