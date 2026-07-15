<?php
// ============================================================
// GestActivos - AJAX: Tabla + modales de Empleados
// ============================================================
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerir();

$db = Database::getInstance();
$q      = trim($_POST['query'] ?? '');
$verTodos = ($_POST['ver_todos'] ?? '0') === '1';

$sql = "SELECT em.idempleado, em.nombre, em.apellidos, em.edad,
               em.telefono, em.direccion, em.imagen, em.activo,
               em.idarea, em.idcargo, em.idsexo,
               ar.descripcionarea, ca.descripcioncargo
        FROM empleados em
        LEFT JOIN areas  ar ON em.idarea  = ar.idarea
        LEFT JOIN cargos ca ON em.idcargo = ca.idcargo";

$conditions = [];
$params     = [];

if (!$verTodos) {
    $conditions[] = "em.activo = 1";
}
if ($q !== '') {
    $conditions[] = "(em.nombre LIKE ? OR em.apellidos LIKE ? OR em.telefono LIKE ? OR ar.descripcionarea LIKE ?)";
    $like   = "%$q%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$rows   = $db->consulta($sql, $params);
$areas  = $db->consulta("SELECT * FROM areas  WHERE activo=1 ORDER BY descripcionarea");
$cargos = $db->consulta("SELECT * FROM cargos WHERE activo=1 ORDER BY descripcioncargo");

// Para el modal de EDITAR: incluye también inactivos (marcados), para no perder
// la referencia si el empleado ya tenía asignada un área/cargo que luego se dio de baja.
$areasTodas  = $db->consulta("SELECT * FROM areas  ORDER BY activo DESC, descripcionarea");
$cargosTodos = $db->consulta("SELECT * FROM cargos ORDER BY activo DESC, descripcioncargo");
?>
<?php if ($rows): ?>

<div class="clearfix" style="margin-bottom:8px;">
    <small class="text-muted">
        <?php if (!$verTodos): ?>
            Solo se muestran empleados activos.
            <a href="#" onclick="ajaxLoad('<?= BASE_URL ?>/app/ajax/maestros/empleados.php'); return false;">Mostrar todos</a>
        <?php else: ?>
            Mostrando todos (activos e inactivos).
        <?php endif; ?>
    </small>
</div>

<table class="table table-bordered table-striped" id="tablaEmp">
    <thead style="background-color:#D3E9F1">
        <tr>
            <th>ID</th><th>Nombre</th><th>Apellidos</th><th>Edad</th>
            <th>Teléfono</th><th>Área</th><th>Cargo</th><th>Estado</th>
            <th style="display:none">idarea</th>
            <th style="display:none">idcargo</th>
            <th style="display:none">idsexo</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <?php
            $activo   = (int)$r['activo'];
            $archivoImagen = basename($r['imagen'] ?? '');
            $imgUrl = ($archivoImagen && file_exists(IMG_EMPLEADOS . $archivoImagen))
                ? BASE_URL . '/public/img/empleados/' . $archivoImagen
                : BASE_URL . '/public/img/empleados/avatar1.png';
        ?>
        <tr class="<?= $activo === 0 ? 'text-muted' : '' ?>">
            <td><?= $r['idempleado'] ?></td>
            <td><?= htmlspecialchars($r['nombre']) ?></td>
            <td><?= htmlspecialchars($r['apellidos']) ?></td>
            <td><?= $r['edad'] ?></td>
            <td><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['descripcionarea'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['descripcioncargo'] ?? '') ?></td>
            <td><?= $activo === 1 ? '<span class="label label-success">Activo</span>' : '<span class="label label-default">Inactivo</span>' ?></td>
            <td style="display:none"><?= $r['idarea'] ?></td>
            <td style="display:none"><?= $r['idcargo'] ?></td>
            <td style="display:none"><?= $r['idsexo'] ?></td>
            <td>
                <a href="#" onclick="return modalImg('<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>')"
                   data-toggle="modal" data-target="#imgModal">
                    <i id="imgIcon" class="fa fa-image"></i>
                </a>
                <?php if ($activo === 1): ?>
                <a href="#" onclick="return editEmp(event)"
                   data-toggle="modal" data-target="#editModal">
                    <i class="fa fa-edit"></i>
                </a>
                <?php endif; ?>
                <a href="#" onclick="return delEmp(event)"
                   title="<?= $activo === 1 ? 'Dar de baja' : 'Reactivar' ?>"
                   data-toggle="modal" data-target="#delModal">
                    <i class="fa fa-<?= $activo === 1 ? 'trash' : 'undo' ?>"
                       style="color:<?= $activo === 1 ? '#e81414' : '#28a745' ?>"></i>
                </a>
                   data-toggle="modal" data-target="#delModal">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
$(document).ready(function(){
    $('#tablaEmp').DataTable({ dom:'lrtip' });
    $('#tablaEmp th:nth-child(8), #tablaEmp td:nth-child(8)').hide();
    $('#tablaEmp th:nth-child(9), #tablaEmp td:nth-child(9)').hide();
    $('#tablaEmp th:nth-child(10),#tablaEmp td:nth-child(10)').hide();
});

function modalImg(src) {
    $('#empFoto').attr('src', src);
}
function editEmp(e) {
    var tr = $(e.target).closest('tr');
    $('#idempleado').val(tr.find('td').eq(0).text());
    $('#nombreAct').val(tr.find('td').eq(1).text());
    $('#apellidosAct').val(tr.find('td').eq(2).text());
    $('#edadAct').val(tr.find('td').eq(3).text());
    $('#telefonoAct').val(tr.find('td').eq(4).text());
    $('#areaAct').val(tr.find('td').eq(7).text());
    $('#cargoAct').val(tr.find('td').eq(8).text());
    $('#sexoAct').val(tr.find('td').eq(9).text());
}
function delEmp(e) {
    var tr = $(e.target).closest('tr');
    $('#idEmpDel').val(tr.find('td').eq(0).text());
    $('#lblEmpDel').text(tr.find('td').eq(1).text() + ' ' + tr.find('td').eq(2).text());
}
</script>

<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>

<!-- MODAL IMAGEN -->
<div class="modal fade" id="imgModal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Foto del Empleado</h5>
      <button class="close" data-dismiss="modal"><span>&times;</span></button>
    </div>
    <div class="modal-body text-center">
      <img id="empFoto" src="" style="max-width:400px;border:3px solid #ddd;border-radius:4px;padding:5px;">
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
    </div>
  </div></div>
</div>

<!-- MODAL NUEVO -->
<div class="modal fade" id="newModal" tabindex="-1" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/empleados.php" method="post" enctype="multipart/form-data">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Empleado</h5>
        <button class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group"><label>Nombre</label>
          <input type="text" name="nombre" class="form-control" required></div>
        <div class="form-group"><label>Apellidos</label>
          <input type="text" name="apellidos" class="form-control" required></div>
        <div class="form-group"><label>Edad</label>
          <input type="number" name="edad" class="form-control" required></div>
        <div class="form-group"><label>Teléfono</label>
          <input type="text" name="telefono" class="form-control"></div>
        <div class="form-group"><label>Dirección</label>
          <input type="text" name="direccion" class="form-control"></div>
        <div class="form-group"><label>Área</label>
          <select name="idarea" class="form-control">
            <?php foreach ($areas as $a): ?>
            <option value="<?= $a['idarea'] ?>"><?= htmlspecialchars($a['descripcionarea']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label>Cargo</label>
          <select name="idcargo" class="form-control">
            <?php foreach ($cargos as $c): ?>
            <option value="<?= $c['idcargo'] ?>"><?= htmlspecialchars($c['descripcioncargo']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label>Sexo</label>
          <select name="idsexo" class="form-control">
            <option value="1">Masculino</option>
            <option value="2">Femenino</option>
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
    <form action="<?= BASE_URL ?>/empleados.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="idempleado" id="idempleado">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title">Editar Empleado</h5>
        <button class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group"><label>Nombre</label>
          <input type="text" name="nombreAct" id="nombreAct" class="form-control" required></div>
        <div class="form-group"><label>Apellidos</label>
          <input type="text" name="apellidosAct" id="apellidosAct" class="form-control" required></div>
        <div class="form-group"><label>Edad</label>
          <input type="number" name="edadAct" id="edadAct" class="form-control" required></div>
        <div class="form-group"><label>Teléfono</label>
          <input type="text" name="telefonoAct" id="telefonoAct" class="form-control"></div>
        <div class="form-group"><label>Área</label>
          <select name="areaAct" id="areaAct" class="form-control">
            <?php foreach ($areasTodas as $a): ?>
            <option value="<?= $a['idarea'] ?>"><?= htmlspecialchars($a['descripcionarea']) ?><?= (int)$a['activo'] === 0 ? ' (inactiva)' : '' ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label>Cargo</label>
          <select name="cargoAct" id="cargoAct" class="form-control">
            <?php foreach ($cargosTodos as $c): ?>
            <option value="<?= $c['idcargo'] ?>"><?= htmlspecialchars($c['descripcioncargo']) ?><?= (int)$c['activo'] === 0 ? ' (inactivo)' : '' ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label>Sexo</label>
          <select name="sexoAct" id="sexoAct" class="form-control">
            <option value="1">Masculino</option>
            <option value="2">Femenino</option>
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
    <form action="<?= BASE_URL ?>/empleados.php" method="post">
      <input type="hidden" name="idEmpleadoDel" id="idEmpDel">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title">Eliminar Empleado</h5>
        <button class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p>¿Seguro que deseas eliminar a <strong><span id="lblEmpDel"></span></strong>?</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <input type="submit" class="btn btn-danger" value="Eliminar" name="del">
      </div>
    </form>
  </div></div>
</div>
