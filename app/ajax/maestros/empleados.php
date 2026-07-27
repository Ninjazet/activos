<?php
// ============================================================
// GestActivos - AJAX: Tabla + modales de Empleados
// ============================================================
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('maestros');

$db = Database::getInstance();
$q      = trim($_POST['query'] ?? '');
$verTodos = ($_POST['ver_todos'] ?? '0') === '1';

$sql = "SELECT em.idempleado, em.nombre, em.apellidos, em.edad,
               em.telefono, em.correo, em.direccion, em.imagen, em.activo,
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
    $conditions[] = "(em.nombre LIKE ? OR em.apellidos LIKE ? OR em.telefono LIKE ? OR em.correo LIKE ? OR ar.descripcionarea LIKE ?)";
    $like   = "%$q%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY em.idempleado DESC";
$rows   = $db->consulta($sql, $params);
$areas  = $db->consulta("SELECT * FROM areas  WHERE activo=1 ORDER BY descripcionarea");
$cargos = $db->consulta("SELECT * FROM cargos WHERE activo=1 ORDER BY descripcioncargo");

// Para el modal de EDITAR: incluye también inactivos (marcados), para no perder
// la referencia si el empleado ya tenía asignada un área/cargo que luego se dio de baja.
$areasTodas  = $db->consulta("SELECT * FROM areas  ORDER BY activo DESC, descripcionarea");
$cargosTodos = $db->consulta("SELECT * FROM cargos ORDER BY activo DESC, descripcioncargo");
?>
<?php if ($rows): ?>

<div class="table-context-bar">
    <span>
        <?php if (!$verTodos): ?>
            Solo se muestran empleados activos.
        <?php else: ?>
            Mostrando todos (activos e inactivos).
        <?php endif; ?>
    </span>
    <?php if (!$verTodos): ?>
        <a href="#" onclick="ajaxLoad('<?= BASE_URL ?>/app/ajax/maestros/empleados.php', '', { ver_todos: 1 }); return false;">
            Mostrar todos
        </a>
    <?php endif; ?>
</div>

<p class="responsive-table-note">
    <i class="fa fa-circle-info" aria-hidden="true"></i>
    Pulsa el indicador de la primera celda para consultar las columnas ocultas en pantallas pequeñas.
</p>
<table class="table table-bordered table-striped nowrap" id="tablaEmp">
    <thead style="background-color:#D3E9F1">
        <tr>
            <th data-priority="6">ID</th><th data-priority="1">Nombre</th><th data-priority="2">Apellidos</th><th data-priority="8">Edad</th>
            <th data-priority="5">Teléfono</th><th data-priority="7">Correo</th><th data-priority="4">Área</th><th data-priority="5">Cargo</th><th data-priority="2">Estado</th>
            <th data-priority="1">Acciones</th>
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
        <tr class="<?= $activo === 0 ? 'text-muted' : '' ?>"
            data-idarea="<?= (int)$r['idarea'] ?>" data-idcargo="<?= (int)$r['idcargo'] ?>" data-idsexo="<?= (int)$r['idsexo'] ?>"
            data-direccion="<?= htmlspecialchars($r['direccion'] ?? '', ENT_QUOTES) ?>" data-activo="<?= $activo ?>">
            <td><?= $r['idempleado'] ?></td>
            <td><?= htmlspecialchars($r['nombre']) ?></td>
            <td><?= htmlspecialchars($r['apellidos']) ?></td>
            <td><?= $r['edad'] ?></td>
            <td><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['correo'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['descripcionarea'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['descripcioncargo'] ?? '') ?></td>
            <td><?= $activo === 1 ? '<span class="badge app-badge-success">Activo</span>' : '<span class="badge app-badge-muted">Inactivo</span>' ?></td>
            <td class="table-actions">
                <a href="#" onclick="return modalImg('<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>')"
                   data-bs-toggle="modal" data-bs-target="#imgModal" title="Ver foto del empleado" aria-label="Ver foto del empleado">
                    <i class="fa fa-image img-icon" aria-hidden="true"></i>
                </a>
                <?php if ($activo === 1 && (string)($_SESSION['transacciones'] ?? '0') === '1'): ?>
                <a href="<?= BASE_URL ?>/asignarequipo.php?idempleado=<?= (int)$r['idempleado'] ?>"
                   title="Asignar equipo a este empleado" aria-label="Asignar equipo a este empleado">
                    <i class="fa fa-laptop-file" aria-hidden="true"></i>
                </a>
                <?php endif; ?>
                <?php if ($activo === 1): ?>
                <a href="#" onclick="return editEmp(event)"
                   data-bs-toggle="modal" data-bs-target="#editModal" title="Editar empleado" aria-label="Editar empleado">
                    <i class="fa fa-edit" aria-hidden="true"></i>
                </a>
                <?php endif; ?>
                
<a href="#" onclick="return delEmp(event)"
   title="<?= $activo === 1 ? 'Dar de baja' : 'Reactivar' ?>"
   aria-label="<?= $activo === 1 ? 'Dar de baja al empleado' : 'Reactivar al empleado' ?>"
   data-bs-toggle="modal" data-bs-target="#delModal">
    <i class="fa fa-<?= $activo === 1 ? 'trash' : 'undo' ?>" aria-hidden="true"></i>
</a>

            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
$(document).ready(function(){
    $('#tablaEmp').DataTable({
        dom: 'lrtip',
        order: [[0, 'desc']],
        autoWidth: false,
        responsive: {
            details: {
                type: 'inline',
                target: 'td:first-child'
            }
        },
        columnDefs: [
            { targets: 0, className: 'dtr-control', responsivePriority: 6 },
            { targets: 1, responsivePriority: 1 },
            { targets: 2, responsivePriority: 2 },
            { targets: 8, responsivePriority: 2 },
            { targets: 9, responsivePriority: 1, orderable: false }
        ]
    });
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
    $('#correoAct').val(tr.find('td').eq(5).text());
    $('#direccionAct').val(tr.attr('data-direccion'));
    $('#areaAct').val(tr.data('idarea'));
    $('#cargoAct').val(tr.data('idcargo'));
    $('#sexoAct').val(tr.data('idsexo'));
}
function delEmp(e) {
    var tr = $(e.target).closest('tr');
    var activo = String(tr.attr('data-activo')) === '1';
    $('#idEmpDel').val(tr.find('td').eq(0).text());
    $('#lblEmpDel').text(tr.find('td').eq(1).text() + ' ' + tr.find('td').eq(2).text());
    $('#tituloEstadoEmp').text(activo ? 'Dar de baja al empleado' : 'Reactivar empleado');
    $('#textoEstadoEmp').text(activo
        ? 'El empleado quedará inactivo. Esta acción no elimina su historial.'
        : 'El empleado volverá a estar disponible para las operaciones permitidas.');
    $('#btnEstadoEmp')
        .toggleClass('btn-danger', activo)
        .toggleClass('btn-success', !activo)
        .text(activo ? 'Dar de baja' : 'Reactivar');
}
</script>

<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>

<!-- MODAL IMAGEN -->
<div class="modal fade" id="imgModal" tabindex="-1" role="dialog" aria-labelledby="tituloFotoEmp">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="tituloFotoEmp">Foto del empleado</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>
    <div class="modal-body text-center">
      <img id="empFoto" class="app-image-view" src="" alt="Vista ampliada del empleado">
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
    </div>
  </div></div>
</div>

<!-- MODAL NUEVO -->
<div class="modal fade" id="newModal" tabindex="-1" role="dialog" aria-labelledby="tituloNuevoEmp">
  <div class="modal-dialog modal-lg app-modal-wide"><div class="modal-content">
    <form action="<?= BASE_URL ?>/empleados.php" method="post" enctype="multipart/form-data">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="tituloNuevoEmp">Nuevo empleado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="app-form-sections">
        <section class="form-section" aria-labelledby="nuevoEmpPersonal">
          <div class="form-section-header">
            <h6 class="form-section-title" id="nuevoEmpPersonal">Datos personales</h6>
            <small class="form-section-help">Información básica para identificar al empleado.</small>
          </div>
          <div class="form-grid">
        <div class="form-group"><label for="nombreNuevo">Nombre</label>
          <input type="text" name="nombre" id="nombreNuevo" class="form-control" required></div>
        <div class="form-group"><label for="apellidosNuevo">Apellidos</label>
          <input type="text" name="apellidos" id="apellidosNuevo" class="form-control" required></div>
        <div class="form-group"><label for="edadNuevo">Edad</label>
          <input type="number" name="edad" id="edadNuevo" class="form-control" min="15" max="100" required></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="nuevoEmpContacto">
          <div class="form-section-header">
            <h6 class="form-section-title" id="nuevoEmpContacto">Contacto</h6>
          </div>
          <div class="form-grid">
        <div class="form-group"><label for="telefonoNuevo">Teléfono</label>
          <input type="text" name="telefono" id="telefonoNuevo" class="form-control"></div>
        <div class="form-group"><label for="correoNuevo">Correo</label>
          <input type="email" name="correo" id="correoNuevo" class="form-control" maxlength="150" placeholder="nombre@empresa.com"></div>
        <div class="form-group form-span-2"><label for="direccionNuevo">Dirección</label>
          <input type="text" name="direccion" id="direccionNuevo" class="form-control"></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="nuevoEmpLaboral">
          <div class="form-section-header">
            <h6 class="form-section-title" id="nuevoEmpLaboral">Datos laborales</h6>
            <small class="form-section-help">Selecciona el área y cargo; puedes crear opciones sin salir del formulario.</small>
          </div>
          <div class="form-grid">
        <div class="form-group">
          <div class="catalogo-contextual-encabezado">
            <label for="nuevoEmpleadoArea">Área</label>
            <button type="button" class="btn btn-link btn-sm js-catalogo-toggle" data-catalogo-target="#altaAreaEmpleado">
              <i class="fa fa-plus"></i> Nueva área
            </button>
          </div>
          <select name="idarea" id="nuevoEmpleadoArea" class="form-select" data-catalogo-select="area" required>
            <?php foreach ($areas as $a): ?>
            <option value="<?= $a['idarea'] ?>"><?= htmlspecialchars($a['descripcionarea']) ?></option>
            <?php endforeach; ?>
          </select>
          <div id="altaAreaEmpleado" class="catalogo-contextual-panel"
               data-tipo="area" data-select="#nuevoEmpleadoArea"
               data-endpoint="<?= BASE_URL ?>/app/ajax/maestros/catalogos_contextuales.php"
               data-csrf="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES) ?>">
            <div class="input-group">
              <input type="text" class="form-control js-catalogo-nombre" maxlength="100" placeholder="Nombre de la nueva área" autocomplete="off">
              <button type="button" class="btn btn-primary js-catalogo-guardar"><i class="fa fa-check"></i> Guardar</button>
              <button type="button" class="btn btn-secondary js-catalogo-cancelar" title="Cancelar"><i class="fa fa-times"></i></button>
            </div>
            <small class="catalogo-contextual-ayuda">Se agregará al catálogo y quedará seleccionada.</small>
            <span class="catalogo-contextual-error" aria-live="polite"></span>
          </div>
        </div>
        <div class="form-group">
          <div class="catalogo-contextual-encabezado">
            <label for="nuevoEmpleadoCargo">Cargo</label>
            <button type="button" class="btn btn-link btn-sm js-catalogo-toggle" data-catalogo-target="#altaCargoEmpleado">
              <i class="fa fa-plus"></i> Nuevo cargo
            </button>
          </div>
          <select name="idcargo" id="nuevoEmpleadoCargo" class="form-select" data-catalogo-select="cargo" required>
            <?php foreach ($cargos as $c): ?>
            <option value="<?= $c['idcargo'] ?>"><?= htmlspecialchars($c['descripcioncargo']) ?></option>
            <?php endforeach; ?>
          </select>
          <div id="altaCargoEmpleado" class="catalogo-contextual-panel"
               data-tipo="cargo" data-select="#nuevoEmpleadoCargo"
               data-endpoint="<?= BASE_URL ?>/app/ajax/maestros/catalogos_contextuales.php"
               data-csrf="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES) ?>">
            <div class="input-group">
              <input type="text" class="form-control js-catalogo-nombre" maxlength="100" placeholder="Nombre del nuevo cargo" autocomplete="off">
              <button type="button" class="btn btn-primary js-catalogo-guardar"><i class="fa fa-check"></i> Guardar</button>
              <button type="button" class="btn btn-secondary js-catalogo-cancelar" title="Cancelar"><i class="fa fa-times"></i></button>
            </div>
            <small class="catalogo-contextual-ayuda">Se agregará al catálogo y quedará seleccionado.</small>
            <span class="catalogo-contextual-error" aria-live="polite"></span>
          </div>
        </div>
        <div class="form-group"><label for="sexoNuevo">Sexo</label>
          <select name="idsexo" id="sexoNuevo" class="form-select">
            <option value="1">Masculino</option>
            <option value="2">Femenino</option>
          </select></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="nuevoEmpImagen">
          <div class="form-section-header">
            <h6 class="form-section-title" id="nuevoEmpImagen">Imagen</h6>
            <small class="form-section-help">La carga conserva el comportamiento actual del sistema.</small>
          </div>
          <div class="form-grid">
        <div class="form-group form-span-2"><label for="fotoNuevoEmp">Foto</label>
          <input type="file" name="archivo" id="fotoNuevoEmp" class="form-control" accept="image/*"></div>
          </div>
        </section>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success" name="add" value="1">
          <i class="fa fa-save" aria-hidden="true"></i> Guardar
        </button>
      </div>
    </form>
  </div></div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="tituloEditarEmp">
  <div class="modal-dialog modal-lg app-modal-wide"><div class="modal-content">
    <form action="<?= BASE_URL ?>/empleados.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="idempleado" id="idempleado">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="tituloEditarEmp">Editar empleado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="app-form-sections">
        <section class="form-section" aria-labelledby="editarEmpPersonal">
          <div class="form-section-header">
            <h6 class="form-section-title" id="editarEmpPersonal">Datos personales</h6>
          </div>
          <div class="form-grid">
        <div class="form-group"><label for="nombreAct">Nombre</label>
          <input type="text" name="nombreAct" id="nombreAct" class="form-control" required></div>
        <div class="form-group"><label for="apellidosAct">Apellidos</label>
          <input type="text" name="apellidosAct" id="apellidosAct" class="form-control" required></div>
        <div class="form-group"><label for="edadAct">Edad</label>
          <input type="number" name="edadAct" id="edadAct" class="form-control" min="15" max="100" required></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="editarEmpContacto">
          <div class="form-section-header">
            <h6 class="form-section-title" id="editarEmpContacto">Contacto</h6>
          </div>
          <div class="form-grid">
        <div class="form-group"><label for="telefonoAct">Teléfono</label>
          <input type="text" name="telefonoAct" id="telefonoAct" class="form-control"></div>
        <div class="form-group"><label for="correoAct">Correo</label>
          <input type="email" name="correoAct" id="correoAct" class="form-control" maxlength="150" placeholder="nombre@empresa.com"></div>
        <div class="form-group form-span-2"><label for="direccionAct">Dirección</label>
          <input type="text" name="direccionAct" id="direccionAct" class="form-control"></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="editarEmpLaboral">
          <div class="form-section-header">
            <h6 class="form-section-title" id="editarEmpLaboral">Datos laborales</h6>
          </div>
          <div class="form-grid">
        <div class="form-group"><label for="areaAct">Área</label>
          <select name="areaAct" id="areaAct" class="form-select" data-catalogo-select="area">
            <?php foreach ($areasTodas as $a): ?>
            <option value="<?= $a['idarea'] ?>"><?= htmlspecialchars($a['descripcionarea']) ?><?= (int)$a['activo'] === 0 ? ' (inactiva)' : '' ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label for="cargoAct">Cargo</label>
          <select name="cargoAct" id="cargoAct" class="form-select" data-catalogo-select="cargo">
            <?php foreach ($cargosTodos as $c): ?>
            <option value="<?= $c['idcargo'] ?>"><?= htmlspecialchars($c['descripcioncargo']) ?><?= (int)$c['activo'] === 0 ? ' (inactivo)' : '' ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label for="sexoAct">Sexo</label>
          <select name="sexoAct" id="sexoAct" class="form-select">
            <option value="1">Masculino</option>
            <option value="2">Femenino</option>
          </select></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="editarEmpImagen">
          <div class="form-section-header">
            <h6 class="form-section-title" id="editarEmpImagen">Imagen</h6>
            <small class="form-section-help">Déjalo vacío para conservar la foto actual.</small>
          </div>
          <div class="form-grid">
        <div class="form-group form-span-2"><label for="fotoActEmp">Nueva foto (opcional)</label>
          <input type="file" name="archivoAct" id="fotoActEmp" class="form-control" accept="image/*"></div>
          </div>
        </section>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" name="edit" value="1">
          <i class="fa fa-save" aria-hidden="true"></i> Actualizar
        </button>
      </div>
    </form>
  </div></div>
</div>

<!-- MODAL ELIMINAR -->
<div class="modal fade" id="delModal" tabindex="-1" role="dialog" aria-labelledby="tituloEstadoEmp">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/empleados.php" method="post">
      <input type="hidden" name="idEmpleadoDel" id="idEmpDel">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="tituloEstadoEmp">Cambiar estado del empleado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p><strong><span id="lblEmpDel"></span></strong></p>
        <p id="textoEstadoEmp">Confirma el cambio de estado del empleado.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger" id="btnEstadoEmp" name="del" value="1">Confirmar</button>
      </div>
    </form>
  </div></div>
</div>
