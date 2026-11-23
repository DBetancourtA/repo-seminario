<div class="page-head">
  <div>
    <p class="eyebrow">Administración</p>
    <h1>Catálogo de tecnologías</h1>
    <p class="lead">Un catálogo cerrado mantiene limpias las estadísticas: «MySQL», «mysql» y «MySql 8» cuentan como una sola.</p>
  </div>
</div>

<?php if ($pend): ?>
<section aria-labelledby="h-pend">
  <div class="section-head"><h2 id="h-pend">Propuestas de estudiantes</h2><span class="meta"><?= count($pend) ?> pendiente<?= count($pend) === 1 ? '' : 's' ?></span></div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th scope="col">Tecnología</th><th scope="col">Categoría</th><th scope="col">Propuesta por</th><th scope="col">Acción</th></tr></thead>
    <tbody>
    <?php foreach ($pend as $t): ?>
      <tr>
        <td><strong style="font-weight:500"><?= e($t['nombre']) ?></strong></td>
        <td><?= e(categoria_label($t['categoria'])) ?></td>
        <td class="small"><?= e(trim(($t['nombres'] ?? '') . ' ' . ($t['apellidos'] ?? ''))) ?> · <?= (int)$t['uso'] ?> proyecto(s)</td>
        <td>
          <div class="actions">
            <form method="post" action="<?= e(url('admin/tecnologias')) ?>">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button class="btn btn-sm btn-ok" name="accion" value="aprobar" type="submit">Aprobar</button>
            </form>
            <form method="post" action="<?= e(url('admin/tecnologias')) ?>" class="actions">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <label class="sr-only" for="dest-<?= (int)$t['id'] ?>">Fusionar con</label>
              <select class="select" id="dest-<?= (int)$t['id'] ?>" name="destino" style="min-height:32px;padding:4px 8px;font-size:13px;width:auto">
                <option value="">Fusionar con…</option>
                <?php foreach ($porCat[$t['categoria']] as $o): ?><option value="<?= (int)$o['id'] ?>"><?= e($o['nombre']) ?></option><?php endforeach; ?>
              </select>
              <button class="btn btn-sm" name="accion" value="fusionar" type="submit">Fusionar</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endif; ?>

<section class="section"<?= $pend ? '' : ' style="margin-top:0"' ?> aria-labelledby="h-add">
  <div class="section-head"><h2 id="h-add">Agregar al catálogo</h2></div>
  <form class="toolbar" method="post" action="<?= e(url('admin/tecnologias')) ?>">
    <?= csrf_field() ?>
    <label class="sr-only" for="nombre">Nombre</label>
    <input class="input" id="nombre" name="nombre" required maxlength="80" placeholder="Nombre de la tecnología">
    <label class="sr-only" for="categoria">Categoría</label>
    <select class="select" id="categoria" name="categoria" required>
      <?php foreach (categorias() as $c): ?><option value="<?= e($c) ?>"><?= e(categoria_label($c)) ?></option><?php endforeach; ?>
    </select>
    <button class="btn" type="submit" name="accion" value="crear"><?= icon('plus', 16) ?> Agregar</button>
  </form>
</section>

<section class="section" aria-labelledby="h-cat">
  <div class="section-head"><h2 id="h-cat">Catálogo</h2><span class="meta">Entre paréntesis, proyectos que la usan</span></div>
  <div class="cat-grid">
    <?php foreach ($porCat as $cat => $list): ?>
      <div>
        <h3><?= e(categoria_label($cat)) ?> <small><?= count($list) ?></small></h3>
        <div class="chips">
          <?php foreach ($list as $t): ?>
            <form method="post" action="<?= e(url('admin/tecnologias')) ?>" style="display:inline-flex" <?= $t['uso'] ? '' : 'data-confirm="¿Eliminar «' . e($t['nombre']) . '» del catálogo?"' ?>>
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <span class="chip" style="display:inline-flex;align-items:center;gap:6px"><?= e($t['nombre']) ?> <span class="mono small muted">(<?= (int)$t['uso'] ?>)</span>
                <?php if (!$t['uso']): ?><button class="linkbtn" style="min-width:20px;min-height:20px;padding:0" type="submit" name="accion" value="eliminar" aria-label="Eliminar <?= e($t['nombre']) ?>"><?= icon('x', 12) ?></button><?php endif; ?>
              </span>
            </form>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
