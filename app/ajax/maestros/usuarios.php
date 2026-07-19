<?php
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerirPermiso('seguridad');

$db = Database::getInstance();

$sql = "SELECT us.idusuario, username, us.estado,
               CONCAT(nombre, ' ', apellidos) AS Nombre,
               datosmaestros, transacciones, consultas, reportes, seguridad
        FROM usuarios us
        INNER JOIN empleados em ON us.idempleado = em.idempleado
        INNER JOIN permisos  pe ON us.idusuario  = pe.idusuario";
$params = [];

$q = trim($_POST['query'] ?? '');
if ($q !== '') {
    $sql   .= " WHERE username LIKE ? OR CONCAT(nombre, ' ', apellidos) LIKE ? OR us.idusuario LIKE ?";
    $params = ["%$q%", "%$q%", "%$q%"];
}

$sql .= " ORDER BY us.idusuario DESC";
$resultado = $db->consulta($sql, $params);

// Cargar solo empleados activos que no tengan cuenta de usuario asignada
// más los que ya tienen cuenta (para el select de edición)
$empleados_disponibles = $db->consulta(
    "SELECT em.idempleado, CONCAT(nombre,' ',apellidos) AS nomApe
     FROM empleados em
     LEFT JOIN usuarios us ON em.idempleado = us.idempleado
     WHERE em.activo=1 AND us.idusuario IS NULL
     ORDER BY nomApe"
);
$empleados_todos = $db->consulta(
    "SELECT idempleado, CONCAT(nombre,' ',apellidos) AS nomApe
     FROM empleados WHERE activo=1 ORDER BY nomApe"
);
?>

<?php if (count($resultado) > 0): ?>

<table class="table table-bordered table-striped" id='datosE'>
    <thead style="background-color:#D3E9F1">
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Empleado</th>
            <th>Estado</th>
            <th>Maestros</th>
            <th>Transacciones</th>
            <th>Consultas</th>
            <th>Reportes</th>
            <th>Seguridad</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resultado as $registro): ?>
        <?php $activo = (int)$registro['estado']; ?>
        <tr class="<?= $activo === 0 ? 'text-muted' : '' ?>">
            <td><?= $registro['idusuario'] ?></td>
            <td><?= htmlspecialchars($registro['username']) ?></td>
            <td><?= htmlspecialchars($registro['Nombre']) ?></td>
            <td><?= $activo === 1 ? '<span class="label label-success">Activo</span>' : '<span class="label label-default">Inactivo</span>' ?></td>
            <td><?= $registro['datosmaestros'] ?></td>
            <td><?= $registro['transacciones'] ?></td>
            <td><?= $registro['consultas'] ?></td>
            <td><?= $registro['reportes'] ?></td>
            <td><?= $registro['seguridad'] ?></td>
            <td>
                <a href='#' title='Editar' onclick='return modalEdit(event);'
                   data-toggle='modal' data-target='#editModal'>
                    <span class="fa fa-edit"></span></a>
                <a href='#' title='<?= $activo === 1 ? 'Desactivar' : 'Reactivar' ?>'
                   onclick='return modalDelete(event);'
                   data-toggle='modal' data-target='#deleteModal'>
                    <span class='fa fa-<?= $activo === 1 ? 'trash' : 'undo' ?>'
                          style="color:<?= $activo === 1 ? '#e81414' : '#28a745' ?>"></span></a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>
<p class='lead'><em>No hay registros</em></p>
<?php endif; ?>

<script>
$(document).ready(function(){
    $("#datosE").DataTable({ dom: 'lrtip', order: [[0, 'desc']] });
    var hide = [4,5,6,7,8];
    hide.forEach(function(i){ $("#datosE th:nth-child("+i+"), #datosE td:nth-child("+i+")").hide(); });
});
</script>

<!-- MODAL NUEVO REGISTRO -->

<div class="modal fade" id="newModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/usuarios.php" method="post" onsubmit="return validaCampos('1');">
                
      <?= Auth::csrfField() ?>
      <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Agregando Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    
                    <div class="form-group">
                        <label>Ingrese nombre de usuario:</label>
                        <input type="text" name="usuario" id="usuario" value="" class="form-control">
                        <span class="help-block"></span>
                    </div> 

                    <div class="form-group">
                        <label>Ingrese password:</label>
                        <input type="password" name="pass" id="pass" value="" class="form-control">
                        <span class="help-block"></span>
                    </div> 

                    <div class="form-group">
                        <label>Seleccione empleado:</label>
                        <select name="empleado" id="empleado" class="form-control" required>
                            <option value="0">-- Seleccione un empleado --</option>
                            <?php foreach ($empleados_disponibles as $emp): ?>
                            <option value="<?= $emp['idempleado'] ?>"><?= htmlspecialchars($emp['nomApe']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Solo aparecen empleados activos sin cuenta asignada.</small>
                    </div> 

                  
                    <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="maestros" id="maestros"/>
                    <label class="custom-control-label" for="maestros">Datos Maestros</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="transacciones" id="transacciones"/>
                    <label class="custom-control-label" for="transacciones">Transacciones</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="consultas" id="consultas"/>
                    <label class="custom-control-label" for="consultas">Consultas</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="reportes" id="reportes"/>
                    <label class="custom-control-label" for="reportes">Reportes</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="seguridad" id="seguridad"/>
                    <label class="custom-control-label" for="seguridad">Seguridad</label>
                    </div>
                
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <input type="submit" class="btn btn-success" value="Guardar" name="add"/>
                </div>
            </form>                
        </div>
    </div>
</div>


<!-- MODAL EDITAR REGISTRO -->

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/usuarios.php" method="post" onsubmit="return validaCampos('2');">
                
      <?= Auth::csrfField() ?>
      <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Actualizando Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    
                    <label>Id Usuario:</label>
                    <input type="hidden" name="idusuario" id="idusuario">

                    <div class="form-group">
                        <label>Nombre de usuario:</label>
                        <input type="text" name="usuarioAct" id="usuarioAct" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Nueva contraseña: <small class="text-muted">(dejar en blanco para no cambiarla)</small></label>
                        <input type="password" name="passAct" id="passAct" value="" class="form-control" autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label>Empleado:</label>
                        <select name="empleadoAct" id="empleadoAct" class="form-control" disabled>
                            <option value="0">-- Seleccione --</option>
                            <?php foreach ($empleados_todos as $emp): ?>
                            <option value="<?= $emp['idempleado'] ?>"><?= htmlspecialchars($emp['nomApe']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">El empleado vinculado no puede cambiarse desde aquí.</small>
                    </div>
                    
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="maestrosAct" id="maestrosAct"/>
                        <label class="custom-control-label" for="maestros">Datos Maestros</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="transaccionesAct" id="transaccionesAct"/>
                        <label class="custom-control-label" for="transacciones">Transacciones</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="consultasAct" id="consultasAct"/>
                        <label class="custom-control-label" for="consultas">Consultas</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="reportesAct" id="reportesAct"/>
                        <label class="custom-control-label" for="reportes">Reportes</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="seguridadAct" id="seguridadAct"/>
                        <label class="custom-control-label" for="seguridad">Seguridad</label>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <input type="submit" class="btn btn-primary" value="Actualizar" name="edit"/>
                </div>

            </form>                    
        </div>
    </div>
</div>


<!-- MODAL ELIMINAR REGISTRO -->

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
            <div class="modal-content">
            <form action="<?= BASE_URL ?>/usuarios.php" method="post">
                
      <?= Auth::csrfField() ?>
      <div class="modal-header">
                    <h5 class="modal-title">Cambiar estado de Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idUsuarioDel" name="idUsuarioDel">
                    <p>¿Cambiar el estado del usuario <strong><span id="usuarioDel"></span></strong>?<br>
                    <small class="text-muted">Si está activo se desactivará; si está inactivo se reactivará.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <input type="submit" class="btn btn-warning" value="Confirmar" name="del"/>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Columnas: 0=ID, 1=Usuario, 2=Empleado, 3=Estado, 4=Maestros...9=Seguridad
function modalEdit(evento) {
    var tr = $(evento.target).closest('tr');
    $("#idusuario").val(tr.find('td').eq(0).text());
    $("#usuarioAct").val(tr.find('td').eq(1).text());
    $("#passAct").val(''); // nunca pre-llenar la contraseña
    var perms = [4,5,6,7,8];
    var ids   = ['maestrosAct','transaccionesAct','consultasAct','reportesAct','seguridadAct'];
    perms.forEach(function(col, i){
        var val = parseInt(tr.find('td').eq(col).text());
        $('#'+ids[i]).prop('checked', val === 1);
    });
}

function modalDelete(evento) {
    var tr = $(evento.target).closest('tr');
    $("#idUsuarioDel").val(tr.find('td').eq(0).text());
    $("#usuarioDel").text(tr.find('td').eq(1).text());
}


    function validaCampos(indice) {
        if (indice == 1) { // Nuevo usuario
            if ($.trim($("#usuario").val()) === '') {
                toastr.error("Ingresa un nombre de usuario.", "Aviso");
                return false;
            }
            if ($.trim($("#pass").val()) === '') {
                toastr.error("Ingresa una contraseña.", "Aviso");
                return false;
            }
            if ($("#empleado").val() == 0) {
                toastr.error("Selecciona un empleado.", "Aviso");
                return false;
            }
        }
        if (indice == 2) { // Editar usuario
            if ($.trim($("#usuarioAct").val()) === '') {
                toastr.error("El nombre de usuario no puede quedar vacío.", "Aviso");
                return false;
            }
            // La contraseña es opcional al editar: si viene vacía, no se cambia
        }
        return true;
    }
</script>
