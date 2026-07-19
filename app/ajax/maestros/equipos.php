<?php
// ============================================================
// GestActivos - AJAX: Tabla + modales de Equipos
// ============================================================
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('maestros');

$db  = Database::getInstance();
$q   = trim($_POST['query'] ?? '');

$sql = "SELECT eq.idequipo, eq.imagen, eq.idmarca_equipo, eq.idmodelo_equipo,
               eq.activo, eq.fecha_compra, eq.costo, eq.factura, eq.vencimiento_garantia,
               eq.estado_equipo, eq.numero_serie, eq.codigo_activo, eq.tipo_equipo, ma.nombreMarca, mo.nombreModelo
        FROM equipo eq
        INNER JOIN marca  ma ON eq.idmarca_equipo  = ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo = mo.idmodelo";

$conditions = [];
$params     = [];
if ($q !== '') {
    $conditions[] = "(ma.nombreMarca LIKE ? OR mo.nombreModelo LIKE ? OR eq.idequipo LIKE ? OR eq.codigo_activo LIKE ? OR eq.numero_serie LIKE ? OR eq.tipo_equipo LIKE ?)";
    $like   = "%$q%";
    $params = [$like, $like, $like, $like, $like, $like];
}
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY eq.idequipo DESC";
$rows    = $db->consulta($sql, $params);
$marcas  = $db->consulta("SELECT * FROM marca  WHERE activo=1 ORDER BY nombreMarca");
$modelos = $db->consulta("SELECT * FROM modelo WHERE activo=1 ORDER BY nombreModelo");

// Para el modal de EDITAR: incluye también inactivos (marcados), para no perder
// la referencia si el equipo ya tenía una marca/modelo que luego se dio de baja.
$marcasTodas  = $db->consulta("SELECT * FROM marca  ORDER BY activo DESC, nombreMarca");
$modelosTodos = $db->consulta("SELECT * FROM modelo ORDER BY activo DESC, nombreModelo");
?>
<?php if ($rows): ?>
<table class="table table-bordered table-striped" id="tablaEquipo">
    <thead style="background-color:#D3E9F1">
        <tr>
            <th>ID</th><th>Código</th><th>Tipo</th><th>Número de serie</th>
            <th>Marca</th><th>Modelo</th><th>Estado del equipo</th>
            <th>Fecha compra</th><th>Costo</th><th>Factura</th><th>Garantía</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <?php
        $activo = (int)$r['activo'];
        $estados = [1 => ['Disponible', 'success'], 2 => ['Asignado', 'primary'], 3 => ['En mantenimiento', 'warning'], 4 => ['Perdido o robado', 'danger'], 5 => ['Dado de baja', 'default']];
        $estadoEquipo = (int)$r['estado_equipo'];
        $estadoInfo = $estados[$estadoEquipo] ?? ['Sin definir', 'default'];
        ?>
        <tr class="<?= $activo === 0 ? 'text-muted' : '' ?>"
            data-idmarca="<?= (int)$r['idmarca_equipo'] ?>" data-idmodelo="<?= (int)$r['idmodelo_equipo'] ?>"
            data-fecha-compra="<?= htmlspecialchars($r['fecha_compra'] ?? '', ENT_QUOTES) ?>"
            data-costo="<?= htmlspecialchars($r['costo'] ?? '', ENT_QUOTES) ?>"
            data-factura="<?= htmlspecialchars($r['factura'] ?? '', ENT_QUOTES) ?>"
            data-garantia="<?= htmlspecialchars($r['vencimiento_garantia'] ?? '', ENT_QUOTES) ?>"
            data-estado-equipo="<?= $estadoEquipo ?>"
            data-numero-serie="<?= htmlspecialchars($r['numero_serie'] ?? '', ENT_QUOTES) ?>"
            data-tipo-equipo="<?= htmlspecialchars($r['tipo_equipo'] ?? 'Otro', ENT_QUOTES) ?>">
            <td><?= $r['idequipo'] ?></td>
            <td><strong><?= htmlspecialchars($r['codigo_activo'] ?? '') ?></strong></td>
            <td><?= htmlspecialchars($r['tipo_equipo'] ?? 'Otro') ?></td>
            <td><?= htmlspecialchars($r['numero_serie'] ?: '—') ?></td>
            <td><?= htmlspecialchars($r['nombreMarca']) ?></td>
            <td><?= htmlspecialchars($r['nombreModelo']) ?></td>
            <td><span class="label label-<?= $estadoInfo[1] ?>"><?= $estadoInfo[0] ?></span><?= $activo === 0 ? ' <span class="label label-default">Inactivo</span>' : '' ?></td>
            <td><?= $r['fecha_compra'] ? date('d/m/Y', strtotime($r['fecha_compra'])) : '—' ?></td>
            <td><?= $r['costo'] !== null ? 'L ' . number_format((float)$r['costo'], 2) : '—' ?></td>
            <td><?= htmlspecialchars($r['factura'] ?: '—') ?></td>
            <td><?= $r['vencimiento_garantia'] ? date('d/m/Y', strtotime($r['vencimiento_garantia'])) : '—' ?></td>
            <td>
                <?php $img = $r['imagen'] ? (BASE_URL . '/' . $r['imagen']) : (BASE_URL . '/public/icons/equipo.png'); ?>
                <a href="#" onclick="return modalImg('<?= htmlspecialchars($img, ENT_QUOTES) ?>')"
                   data-toggle="modal" data-target="#imgModal">
                    <i id="imgIcon" class="fa fa-image"></i>
                </a>
                <?php if ($activo === 1 && $estadoEquipo === 1 && (string)($_SESSION['transacciones'] ?? '0') === '1'): ?>
                <a href="<?= BASE_URL ?>/asignarequipo.php?idequipo=<?= (int)$r['idequipo'] ?>"
                   title="Asignar este equipo a un empleado">
                    <i class="fa fa-user-plus"></i>
                </a>
                <?php endif; ?>
                <?php if ($activo === 1): ?>
                <a href="#" onclick="return editEquipo(event)"
                   data-toggle="modal" data-target="#editModal">
                    <i class="fa fa-edit"></i>
                </a>
                <?php endif; ?>

                <a href="#" onclick="return delEquipo(event)"
   title="<?= $activo === 1 ? 'Dar de baja' : 'Reactivar' ?>"
   data-toggle="modal" data-target="#delModal">
    <i class="fa fa-<?= $activo === 1 ? 'trash' : 'undo' ?>"
       style="color:<?= $activo === 1 ? '#e81414' : '#28a745' ?>"></i>
</a>

            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
$(document).ready(function () {
    $('#tablaEquipo').DataTable({ dom: 'lrtip', order: [[0, 'desc']] });
});

function modalImg(src) {
    $('#equipoFoto').attr('src', src);
}
function editEquipo(e) {
    var tr = $(e.target).closest('tr');
    $('#idequipo').val(tr.find('td').eq(0).text());
    $('#marcaAct').val(tr.data('idmarca'));
    $('#modeloAct').val(tr.data('idmodelo'));
    $('#numero_serieAct').val(tr.attr('data-numero-serie'));
    $('#tipo_equipoAct').val(tr.attr('data-tipo-equipo'));
    $('#fecha_compraAct').val(tr.attr('data-fecha-compra'));
    $('#costoAct').val(tr.attr('data-costo'));
    $('#facturaAct').val(tr.attr('data-factura'));
    $('#vencimiento_garantiaAct').val(tr.attr('data-garantia'));
    $('#estado_equipoAct').val(tr.data('estado-equipo'));
}
function delEquipo(e) {
    var tr = $(e.target).closest('tr');
    $('#idEquipoDel').val(tr.find('td').eq(0).text());
    $('#lblEquipoDel').text(tr.find('td').eq(1).text() + ' - ' + tr.find('td').eq(4).text() + ' ' + tr.find('td').eq(5).text());
}
</script>

<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>

<!-- MODAL IMAGEN -->
<div class="modal fade" id="imgModal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Foto del Equipo</h5>
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
    </div>
    <div class="modal-body text-center">
      <img id="equipoFoto" src="" style="max-width:400px;border:3px solid #ddd;border-radius:4px;padding:5px;">
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
    </div>
  </div></div>
</div>

<!-- MODAL NUEVO -->
<div class="modal fade" id="newModal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/equipos.php" method="post" enctype="multipart/form-data">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Equipo</h5>
        <button class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <div class="catalogo-contextual-encabezado">
            <label for="nuevoEquipoMarca">Marca</label>
            <button type="button" class="btn btn-link btn-xs js-catalogo-toggle" data-target="#altaMarcaEquipo">
              <i class="fa fa-plus"></i> Nueva marca
            </button>
          </div>
          <select name="idmarca" id="nuevoEquipoMarca" class="form-control" data-catalogo-select="marca" required>
            <?php foreach ($marcas as $m): ?>
            <option value="<?= $m['idmarca'] ?>"><?= htmlspecialchars($m['nombreMarca']) ?></option>
            <?php endforeach; ?>
          </select>
          <div id="altaMarcaEquipo" class="catalogo-contextual-panel"
               data-tipo="marca" data-select="#nuevoEquipoMarca"
               data-endpoint="<?= BASE_URL ?>/app/ajax/maestros/catalogos_contextuales.php"
               data-csrf="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES) ?>">
            <div class="input-group">
              <input type="text" class="form-control js-catalogo-nombre" maxlength="50" placeholder="Nombre de la nueva marca" autocomplete="off">
              <span class="input-group-btn">
                <button type="button" class="btn btn-primary js-catalogo-guardar"><i class="fa fa-check"></i> Guardar</button>
                <button type="button" class="btn btn-default js-catalogo-cancelar" title="Cancelar"><i class="fa fa-times"></i></button>
              </span>
            </div>
            <small class="catalogo-contextual-ayuda">Se agregará al catálogo y quedará seleccionada.</small>
            <span class="catalogo-contextual-error" aria-live="polite"></span>
          </div>
        </div>
        <div class="form-group">
          <div class="catalogo-contextual-encabezado">
            <label for="nuevoEquipoModelo">Modelo</label>
            <button type="button" class="btn btn-link btn-xs js-catalogo-toggle" data-target="#altaModeloEquipo">
              <i class="fa fa-plus"></i> Nuevo modelo
            </button>
          </div>
          <select name="idmodelo" id="nuevoEquipoModelo" class="form-control" data-catalogo-select="modelo" required>
            <?php foreach ($modelos as $mo): ?>
            <option value="<?= $mo['idmodelo'] ?>"><?= htmlspecialchars($mo['nombreModelo']) ?></option>
            <?php endforeach; ?>
          </select>
          <div id="altaModeloEquipo" class="catalogo-contextual-panel"
               data-tipo="modelo" data-select="#nuevoEquipoModelo"
               data-endpoint="<?= BASE_URL ?>/app/ajax/maestros/catalogos_contextuales.php"
               data-csrf="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES) ?>">
            <div class="input-group">
              <input type="text" class="form-control js-catalogo-nombre" maxlength="50" placeholder="Nombre del nuevo modelo" autocomplete="off">
              <span class="input-group-btn">
                <button type="button" class="btn btn-primary js-catalogo-guardar"><i class="fa fa-check"></i> Guardar</button>
                <button type="button" class="btn btn-default js-catalogo-cancelar" title="Cancelar"><i class="fa fa-times"></i></button>
              </span>
            </div>
            <small class="catalogo-contextual-ayuda">Se agregará al catálogo y quedará seleccionado.</small>
            <span class="catalogo-contextual-error" aria-live="polite"></span>
          </div>
        </div>
        <div class="form-group"><label>Número de serie</label><input type="text" name="numero_serie" class="form-control" maxlength="100" placeholder="Identificador del fabricante"></div>
        <div class="form-group"><label>Tipo de equipo</label><select name="tipo_equipo" class="form-control" required>
          <option value="Laptop">Laptop</option><option value="Computadora de escritorio">Computadora de escritorio</option>
          <option value="Monitor">Monitor</option><option value="Teléfono">Teléfono</option><option value="Impresora">Impresora</option>
          <option value="Servidor">Servidor</option><option value="Equipo de red">Equipo de red</option><option value="Otro">Otro</option></select></div>
        <div class="form-group"><label>Código de activo</label><input type="text" class="form-control" value="Se generará automáticamente (EQ-0001)" disabled></div>
        <div class="form-group"><label>Fecha de compra</label><input type="date" name="fecha_compra" class="form-control"></div>
        <div class="form-group"><label>Costo (L)</label><input type="number" name="costo" class="form-control" min="0" step="0.01" placeholder="0.00"></div>
        <div class="form-group"><label>Número de factura</label><input type="text" name="factura" class="form-control" maxlength="100"></div>
        <div class="form-group"><label>Vencimiento de garantía</label><input type="date" name="vencimiento_garantia" class="form-control"></div>
        <div class="form-group"><label>Estado inicial</label><input type="text" class="form-control" value="Disponible" disabled><small class="text-muted">Todo equipo nuevo inicia automáticamente como disponible.</small></div>
        <div class="form-group"><label>Foto</label><input type="file" name="archivo" class="form-control" accept="image/*"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <input type="submit" class="btn btn-success" value="Guardar" name="add">
      </div>
    </form>
  </div></div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/equipos.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="idequipo" id="idequipo">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title">Editar Equipo</h5>
        <button class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group"><label>Marca</label>
          <select name="marcaAct" id="marcaAct" class="form-control" data-catalogo-select="marca" required>
            <?php foreach ($marcasTodas as $m): ?>
            <option value="<?= $m['idmarca'] ?>"><?= htmlspecialchars($m['nombreMarca']) ?><?= (int)$m['activo'] === 0 ? ' (inactiva)' : '' ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label>Modelo</label>
          <select name="modeloAct" id="modeloAct" class="form-control" data-catalogo-select="modelo" required>
            <?php foreach ($modelosTodos as $mo): ?>
            <option value="<?= $mo['idmodelo'] ?>"><?= htmlspecialchars($mo['nombreModelo']) ?><?= (int)$mo['activo'] === 0 ? ' (inactivo)' : '' ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label>Número de serie</label><input type="text" name="numero_serieAct" id="numero_serieAct" class="form-control" maxlength="100"></div>
        <div class="form-group"><label>Tipo de equipo</label><select name="tipo_equipoAct" id="tipo_equipoAct" class="form-control" required>
          <option value="Laptop">Laptop</option><option value="Computadora de escritorio">Computadora de escritorio</option>
          <option value="Monitor">Monitor</option><option value="Teléfono">Teléfono</option><option value="Impresora">Impresora</option>
          <option value="Servidor">Servidor</option><option value="Equipo de red">Equipo de red</option><option value="Otro">Otro</option></select></div>
        <div class="form-group"><label>Fecha de compra</label><input type="date" name="fecha_compraAct" id="fecha_compraAct" class="form-control"></div>
        <div class="form-group"><label>Costo (L)</label><input type="number" name="costoAct" id="costoAct" class="form-control" min="0" step="0.01"></div>
        <div class="form-group"><label>Número de factura</label><input type="text" name="facturaAct" id="facturaAct" class="form-control" maxlength="100"></div>
        <div class="form-group"><label>Vencimiento de garantía</label><input type="date" name="vencimiento_garantiaAct" id="vencimiento_garantiaAct" class="form-control"></div>
        <div class="form-group"><label>Estado del equipo</label><select name="estado_equipoAct" id="estado_equipoAct" class="form-control" required>
          <option value="1">Disponible</option><option value="2" disabled>Asignado (automático)</option><option value="3">En mantenimiento</option><option value="4">Perdido o robado</option><option value="5">Dado de baja</option></select></div>
        <div class="form-group"><label>Nueva foto (opcional)</label><input type="file" name="archivoAct" class="form-control" accept="image/*"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <input type="submit" class="btn btn-primary" value="Actualizar" name="edit">
      </div>
    </form>
  </div></div>
</div>

<!-- MODAL ELIMINAR -->
<div class="modal fade" id="delModal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/equipos.php" method="post">
      <input type="hidden" name="idEquipoDel" id="idEquipoDel">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title">Eliminar Equipo</h5>
        <button class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p>¿Seguro que deseas eliminar el equipo <strong><span id="lblEquipoDel"></span></strong>?</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <input type="submit" class="btn btn-danger" value="Eliminar" name="del">
      </div>
    </form>
  </div></div>
</div>
