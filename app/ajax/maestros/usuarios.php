<?php
require_once __DIR__ . "/../../../bootstrap.php";
Auth::requerirPermiso('seguridad');

$db = Database::getInstance();

$sql = "SELECT us.idusuario, us.idempleado, username, us.estado,
               CONCAT(nombre, ' ', apellidos) AS Nombre,
               datosmaestros, transacciones, consultas, reportes, actas, seguridad
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
            <th>Actas</th>
            <th>Seguridad</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resultado as $registro): ?>
        <?php $activo = (int)$registro['estado']; ?>
        <tr class="<?= $activo === 0 ? 'text-muted' : '' ?>" data-idempleado="<?= (int)$registro['idempleado'] ?>">
            <td><?= $registro['idusuario'] ?></td>
            <td><?= htmlspecialchars($registro['username']) ?></td>
            <td><?= htmlspecialchars($registro['Nombre']) ?></td>
            <td><?= $activo === 1 ? '<span class="badge app-badge-success">Activo</span>' : '<span class="badge app-badge-muted">Inactivo</span>' ?></td>
            <?php foreach (['datosmaestros', 'transacciones', 'consultas', 'reportes', 'actas', 'seguridad'] as $permiso): ?>
            <?php $permitido = (int)$registro[$permiso] === 1; ?>
            <td class="permission-cell" data-permission="<?= $permitido ? 1 : 0 ?>" data-order="<?= $permitido ? 1 : 0 ?>">
                <span class="permission-indicator <?= $permitido ? 'is-granted' : 'is-denied' ?>"
                      role="img" aria-label="<?= $permitido ? 'Permitido' : 'Sin permiso' ?>"
                      title="<?= $permitido ? 'Permitido' : 'Sin permiso' ?>">
                    <i class="fa <?= $permitido ? 'fa-check' : 'fa-minus' ?>" aria-hidden="true"></i>
                </span>
            </td>
            <?php endforeach; ?>
            <td class="table-actions">
                <a href='#' title='Editar usuario' aria-label='Editar usuario' onclick='return modalEdit(event);'
                   data-bs-toggle='modal' data-bs-target='#editModal'>
                    <span class="fa fa-edit" aria-hidden="true"></span></a>
                <a href='#' title='<?= $activo === 1 ? 'Desactivar' : 'Reactivar' ?>'
                   aria-label='<?= $activo === 1 ? 'Desactivar usuario' : 'Reactivar usuario' ?>'
                   onclick='return modalDelete(event);'
                   data-bs-toggle='modal' data-bs-target='#deleteModal'>
                    <span class='fa fa-<?= $activo === 1 ? 'trash' : 'undo' ?>'
                          aria-hidden="true"></span></a>
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
});
</script>

<!-- MODAL NUEVO REGISTRO -->

<div class="modal fade" id="newModal" tabindex="-1" role="dialog" aria-labelledby="newUserModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/usuarios.php" method="post" onsubmit="return validaCampos('1');">
                
      <?= Auth::csrfField() ?>
      <div class="modal-header">
                    <h5 class="modal-title" id="newUserModalTitle">Crear usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    
                    <div class="form-group">
                        <label for="usuario">Nombre de usuario</label>
                        <input type="text" name="usuario" id="usuario" value="" class="form-control" autocomplete="username" required>
                        <span class="form-text text-danger"></span>
                    </div> 

                    <div class="form-group">
                        <label for="pass">Contraseña</label>
                        <input type="password" name="pass" id="pass" value="" class="form-control" autocomplete="new-password" required>
                        <span class="form-text text-danger"></span>
                    </div> 

                    <div class="form-group">
                        <label for="empleado">Empleado vinculado</label>
                        <select name="empleado" id="empleado" class="form-select" required>
                            <option value="0">-- Seleccione un empleado --</option>
                            <?php foreach ($empleados_disponibles as $emp): ?>
                            <option value="<?= $emp['idempleado'] ?>"><?= htmlspecialchars($emp['nomApe']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Solo aparecen empleados activos sin cuenta asignada.</small>
                    </div> 

                  
                    <fieldset class="permission-fieldset">
                        <legend>Permisos de acceso</legend>
                        <p class="permission-help">Selecciona solamente los módulos que necesita esta cuenta.</p>
                        <div class="permission-grid">
                            <label class="permission-option" for="maestros"><input type="checkbox" name="maestros" id="maestros"><span><strong>Datos maestros</strong><small>Inventario, personal y catálogos</small></span></label>
                            <label class="permission-option" for="transacciones"><input type="checkbox" name="transacciones" id="transacciones"><span><strong>Transacciones</strong><small>Asignar y devolver; incluye acceso a actas</small></span></label>
                            <label class="permission-option" for="consultas"><input type="checkbox" name="consultas" id="consultas"><span><strong>Consultas</strong><small>Consultar información operativa</small></span></label>
                            <label class="permission-option" for="reportes"><input type="checkbox" name="reportes" id="reportes"><span><strong>Reportes</strong><small>Visualizar y descargar reportes</small></span></label>
                            <label class="permission-option" for="actas"><input type="checkbox" name="actas" id="actas"><span><strong>Actas firmadas</strong><small>Consultar documentos de entrega y devolución</small></span></label>
                            <label class="permission-option" for="seguridad"><input type="checkbox" name="seguridad" id="seguridad"><span><strong>Seguridad</strong><small>Administrar usuarios y bitácora</small></span></label>
                        </div>
                    </fieldset>
                
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" name="add"><i class="fa fa-check" aria-hidden="true"></i> Guardar usuario</button>
                </div>
            </form>                
        </div>
    </div>
</div>


<!-- MODAL EDITAR REGISTRO -->

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/usuarios.php" method="post" onsubmit="return validaCampos('2');">
                
      <?= Auth::csrfField() ?>
      <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalTitle">Editar usuario y permisos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    
                    <input type="hidden" name="idusuario" id="idusuario">

                    <div class="form-group">
                        <label for="usuarioAct">Nombre de usuario</label>
                        <input type="text" name="usuarioAct" id="usuarioAct" class="form-control" autocomplete="username" required>
                    </div>

                    <div class="form-group">
                        <label for="passAct">Nueva contraseña <small class="text-muted">(opcional)</small></label>
                        <input type="password" name="passAct" id="passAct" value="" class="form-control" autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label for="empleadoAct">Empleado vinculado</label>
                        <select name="empleadoAct" id="empleadoAct" class="form-select" disabled>
                            <option value="0">-- Seleccione --</option>
                            <?php foreach ($empleados_todos as $emp): ?>
                            <option value="<?= $emp['idempleado'] ?>"><?= htmlspecialchars($emp['nomApe']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">El empleado vinculado no puede cambiarse desde aquí.</small>
                    </div>
                    
                    <fieldset class="permission-fieldset">
                        <legend>Permisos de acceso</legend>
                        <p class="permission-help">Los cambios se aplicarán cuando el usuario vuelva a iniciar sesión.</p>
                        <div class="permission-grid">
                            <label class="permission-option" for="maestrosAct"><input type="checkbox" name="maestrosAct" id="maestrosAct"><span><strong>Datos maestros</strong><small>Inventario, personal y catálogos</small></span></label>
                            <label class="permission-option" for="transaccionesAct"><input type="checkbox" name="transaccionesAct" id="transaccionesAct"><span><strong>Transacciones</strong><small>Asignar y devolver; incluye acceso a actas</small></span></label>
                            <label class="permission-option" for="consultasAct"><input type="checkbox" name="consultasAct" id="consultasAct"><span><strong>Consultas</strong><small>Consultar información operativa</small></span></label>
                            <label class="permission-option" for="reportesAct"><input type="checkbox" name="reportesAct" id="reportesAct"><span><strong>Reportes</strong><small>Visualizar y descargar reportes</small></span></label>
                            <label class="permission-option" for="actasAct"><input type="checkbox" name="actasAct" id="actasAct"><span><strong>Actas firmadas</strong><small>Consultar documentos de entrega y devolución</small></span></label>
                            <label class="permission-option" for="seguridadAct"><input type="checkbox" name="seguridadAct" id="seguridadAct"><span><strong>Seguridad</strong><small>Administrar usuarios y bitácora</small></span></label>
                        </div>
                    </fieldset>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" name="edit"><i class="fa fa-check" aria-hidden="true"></i> Actualizar usuario</button>
                </div>

            </form>                    
        </div>
    </div>
</div>


<!-- MODAL ELIMINAR REGISTRO -->

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="userStatusModalTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
            <div class="modal-content">
            <form action="<?= BASE_URL ?>/usuarios.php" method="post">
                
      <?= Auth::csrfField() ?>
      <div class="modal-header">
                    <h5 class="modal-title" id="userStatusModalTitle">Cambiar estado del usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idUsuarioDel" name="idUsuarioDel">
                    <p>¿Cambiar el estado del usuario <strong><span id="usuarioDel"></span></strong>?<br>
                    <small class="text-muted">Si está activo se desactivará; si está inactivo se reactivará.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
    $("#empleadoAct").val(String(tr.data('idempleado')));
    $("#passAct").val(''); // nunca pre-llenar la contraseña
    var perms = [4,5,6,7,8,9];
    var ids   = ['maestrosAct','transaccionesAct','consultasAct','reportesAct','actasAct','seguridadAct'];
    perms.forEach(function(col, i){
        var val = parseInt(tr.find('td').eq(col).attr('data-permission'), 10);
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
