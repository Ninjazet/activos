<?php
// ============================================================
// GestActivos - AJAX: Tabla + modales de Equipos
// ============================================================
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerir();

$db  = Database::getInstance();
$q   = trim($_POST['query'] ?? '');

$sql = "SELECT eq.idequipo, eq.imagen, eq.idmarca_equipo, eq.idmodelo_equipo,
               eq.activo, ma.nombreMarca, mo.nombreModelo
        FROM equipo eq
        INNER JOIN marca  ma ON eq.idmarca_equipo  = ma.idmarca
        INNER JOIN modelo mo ON eq.idmodelo_equipo = mo.idmodelo";

$conditions = [];
$params     = [];
if ($q !== '') {
    $conditions[] = "(ma.nombreMarca LIKE ? OR mo.nombreModelo LIKE ? OR eq.idequipo LIKE ?)";
    $like   = "%$q%";
    $params = [$like, $like, $like];
}
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

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
            <th>ID</th><th>Marca</th><th>Modelo</th><th>Estado</th>
            <th style="display:none">idmarca</th>
            <th style="display:none">idmodelo</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <?php $activo = (int)$r['activo']; ?>
        <tr class="<?= $activo === 0 ? 'text-muted' : '' ?>">
            <td><?= $r['idequipo'] ?></td>
            <td><?= htmlspecialchars($r['nombreMarca']) ?></td>
            <td><?= htmlspecialchars($r['nombreModelo']) ?></td>
            <td><?= $activo === 1 ? '<span class="label label-success">Activo</span>' : '<span class="label label-default">Inactivo</span>' ?></td>
            <td style="display:none"><?= $r['idmarca_equipo'] ?></td>
            <td style="display:none"><?= $r['idmodelo_equipo'] ?></td>
            <td>
                <?php $img = $r['imagen'] ? (BASE_URL . '/' . $r['imagen']) : (BASE_URL . '/public/icons/equipo.png'); ?>
                <a href="#" onclick="return modalImg('<?= htmlspecialchars($img, ENT_QUOTES) ?>')"
                   data-toggle="modal" data-target="#imgModal">
                    <i id="imgIcon" class="fa fa-image"></i>
                </a>
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
                <a href="#" onclick="return delEquipo(event)"
                   data-toggle="modal" data-target="#delModal">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
$(document).ready(function () {
    $('#tablaEquipo').DataTable({ dom: 'lrtip' });
    $('#tablaEquipo th:nth-child(4), #tablaEquipo td:nth-child(4)').hide();
    $('#tablaEquipo th:nth-child(5), #tablaEquipo td:nth-child(5)').hide();
});

function modalImg(src) {
    $('#equipoFoto').attr('src', src);
}
function editEquipo(e) {
    var tr = $(e.target).closest('tr');
    $('#idequipo').val(tr.find('td').eq(0).text());
    $('#marcaAct').val(tr.find('td').eq(3).text());
    $('#modeloAct').val(tr.find('td').eq(4).text());
}
function delEquipo(e) {
    var tr = $(e.target).closest('tr');
    $('#idEquipoDel').val(tr.find('td').eq(0).text());
    $('#lblEquipoDel').text(tr.find('td').eq(1).text() + ' ' + tr.find('td').eq(2).text());
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
        <div class="form-group"><label>Marca</label>
          <select name="idmarca" class="form-control" required>
            <?php foreach ($marcas as $m): ?>
            <option value="<?= $m['idmarca'] ?>"><?= htmlspecialchars($m['nombreMarca']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label>Modelo</label>
          <select name="idmodelo" class="form-control" required>
            <?php foreach ($modelos as $mo): ?>
            <option value="<?= $mo['idmodelo'] ?>"><?= htmlspecialchars($mo['nombreModelo']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label>Foto</label>
          <input type="file" name="archivo" class="form-control" accept="image/*"></div>
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
          <select name="marcaAct" id="marcaAct" class="form-control" required>
            <?php foreach ($marcasTodas as $m): ?>
            <option value="<?= $m['idmarca'] ?>"><?= htmlspecialchars($m['nombreMarca']) ?><?= (int)$m['activo'] === 0 ? ' (inactiva)' : '' ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label>Modelo</label>
          <select name="modeloAct" id="modeloAct" class="form-control" required>
            <?php foreach ($modelosTodos as $mo): ?>
            <option value="<?= $mo['idmodelo'] ?>"><?= htmlspecialchars($mo['nombreModelo']) ?><?= (int)$mo['activo'] === 0 ? ' (inactivo)' : '' ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label>Nueva foto (opcional)</label>
          <input type="file" name="archivoAct" class="form-control" accept="image/*"></div>
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
