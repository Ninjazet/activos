<?php
// ============================================================
// GestActivos - AJAX: Tabla + modales de Equipos
// ============================================================
require_once __DIR__ . '/../../../bootstrap.php';
Auth::requerirPermiso('maestros');

$db  = Database::getInstance();
$q = TableFilter::text('query');
$estadoEquipoFiltro = TableFilter::enum('estado_equipo', ['1', '2', '3', '4', '5']);
$tipoEquipoFiltro = TableFilter::text('tipo_equipo', 50);
$marcaFiltro = TableFilter::positiveInt('idmarca');
$modeloFiltro = TableFilter::positiveInt('idmodelo');
$activoFiltro = TableFilter::enum('activo', ['0', '1']);
$garantiaFiltro = TableFilter::enum('garantia', ['vigente', 'vence_30', 'vencida', 'sin_fecha']);

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
if ($estadoEquipoFiltro !== '') {
    $conditions[] = 'eq.estado_equipo = ?';
    $params[] = (int)$estadoEquipoFiltro;
}
if ($tipoEquipoFiltro !== '') {
    $conditions[] = 'eq.tipo_equipo = ?';
    $params[] = $tipoEquipoFiltro;
}
if ($marcaFiltro > 0) {
    $conditions[] = 'eq.idmarca_equipo = ?';
    $params[] = $marcaFiltro;
}
if ($modeloFiltro > 0) {
    $conditions[] = 'eq.idmodelo_equipo = ?';
    $params[] = $modeloFiltro;
}
if ($activoFiltro !== '') {
    $conditions[] = 'eq.activo = ?';
    $params[] = (int)$activoFiltro;
}
if ($garantiaFiltro === 'vigente') {
    $conditions[] = 'eq.vencimiento_garantia >= CURDATE()';
} elseif ($garantiaFiltro === 'vence_30') {
    $conditions[] = 'eq.vencimiento_garantia BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
} elseif ($garantiaFiltro === 'vencida') {
    $conditions[] = 'eq.vencimiento_garantia < CURDATE()';
} elseif ($garantiaFiltro === 'sin_fecha') {
    $conditions[] = 'eq.vencimiento_garantia IS NULL';
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
<p class="responsive-table-note">
    <i class="fa fa-circle-info" aria-hidden="true"></i>
    Cuando una columna se oculte por falta de espacio, pulsa el indicador de la primera celda para ver el detalle completo.
</p>
<table class="table table-bordered table-striped nowrap" id="tablaEquipo">
    <thead style="background-color:#D3E9F1">
        <tr>
            <th data-priority="6">ID</th><th data-priority="1">Código</th>
            <th data-priority="4">Tipo</th><th data-priority="5">Número de serie</th>
            <th data-priority="5">Marca</th><th data-priority="3">Modelo</th>
            <th data-priority="2">Estado del equipo</th>
            <th data-priority="8">Fecha compra</th><th data-priority="7">Costo</th>
            <th data-priority="9">Factura</th><th data-priority="10">Garantía</th>
            <th data-priority="1">Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <?php
        $activo = (int)$r['activo'];
        $estados = [1 => ['Disponible', 'success'], 2 => ['Asignado', 'primary'], 3 => ['En mantenimiento', 'warning'], 4 => ['Perdido o robado', 'danger'], 5 => ['Dado de baja', 'muted']];
        $estadoEquipo = (int)$r['estado_equipo'];
        $estadoInfo = $estados[$estadoEquipo] ?? ['Sin definir', 'muted'];
        ?>
        <tr class="<?= $activo === 0 ? 'text-muted' : '' ?>"
            data-idmarca="<?= (int)$r['idmarca_equipo'] ?>" data-idmodelo="<?= (int)$r['idmodelo_equipo'] ?>"
            data-fecha-compra="<?= htmlspecialchars($r['fecha_compra'] ?? '', ENT_QUOTES) ?>"
            data-costo="<?= htmlspecialchars($r['costo'] ?? '', ENT_QUOTES) ?>"
            data-factura="<?= htmlspecialchars($r['factura'] ?? '', ENT_QUOTES) ?>"
            data-garantia="<?= htmlspecialchars($r['vencimiento_garantia'] ?? '', ENT_QUOTES) ?>"
            data-estado-equipo="<?= $estadoEquipo ?>"
            data-numero-serie="<?= htmlspecialchars($r['numero_serie'] ?? '', ENT_QUOTES) ?>"
            data-tipo-equipo="<?= htmlspecialchars($r['tipo_equipo'] ?? 'Otro', ENT_QUOTES) ?>"
            data-activo="<?= $activo ?>">
            <td><?= $r['idequipo'] ?></td>
            <td><strong><?= htmlspecialchars($r['codigo_activo'] ?? '') ?></strong></td>
            <td><?= htmlspecialchars($r['tipo_equipo'] ?? 'Otro') ?></td>
            <td><?= htmlspecialchars($r['numero_serie'] ?: '—') ?></td>
            <td><?= htmlspecialchars($r['nombreMarca']) ?></td>
            <td><?= htmlspecialchars($r['nombreModelo']) ?></td>
            <td><span class="badge app-badge-<?= $estadoInfo[1] ?>"><?= $estadoInfo[0] ?></span><?= $activo === 0 ? ' <span class="badge app-badge-muted">Inactivo</span>' : '' ?></td>
            <td><?= $r['fecha_compra'] ? date('d/m/Y', strtotime($r['fecha_compra'])) : '—' ?></td>
            <td><?= $r['costo'] !== null ? 'L ' . number_format((float)$r['costo'], 2) : '—' ?></td>
            <td><?= htmlspecialchars($r['factura'] ?: '—') ?></td>
            <td><?= $r['vencimiento_garantia'] ? date('d/m/Y', strtotime($r['vencimiento_garantia'])) : '—' ?></td>
            <td class="table-actions">
                <?php $img = $r['imagen'] ? (BASE_URL . '/' . $r['imagen']) : (BASE_URL . '/public/icons/equipo.png'); ?>
                <a href="#" onclick="return modalImg('<?= htmlspecialchars($img, ENT_QUOTES) ?>')"
                   data-bs-toggle="modal" data-bs-target="#imgModal" title="Ver imagen del equipo" aria-label="Ver imagen del equipo">
                    <i class="fa fa-image img-icon" aria-hidden="true"></i>
                </a>
                <?php if ($activo === 1 && $estadoEquipo === 1 && (string)($_SESSION['transacciones'] ?? '0') === '1'): ?>
                <a href="<?= BASE_URL ?>/asignarequipo.php?idequipo=<?= (int)$r['idequipo'] ?>"
                   title="Asignar este equipo a un empleado" aria-label="Asignar este equipo a un empleado">
                    <i class="fa fa-user-plus" aria-hidden="true"></i>
                </a>
                <?php endif; ?>
                <?php if ($activo === 1): ?>
                <a href="#" onclick="return editEquipo(event)"
                   data-bs-toggle="modal" data-bs-target="#editModal" title="Editar equipo" aria-label="Editar equipo">
                    <i class="fa fa-edit" aria-hidden="true"></i>
                </a>
                <?php endif; ?>

<a href="#" onclick="return delEquipo(event)"
   title="<?= $activo === 1 ? 'Dar de baja' : 'Reactivar' ?>"
   aria-label="<?= $activo === 1 ? 'Dar de baja el equipo' : 'Reactivar el equipo' ?>"
   data-bs-toggle="modal" data-bs-target="#delModal">
    <i class="fa fa-<?= $activo === 1 ? 'trash' : 'undo' ?>" aria-hidden="true"></i>
</a>

            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
$(document).ready(function () {
    $('#tablaEquipo').DataTable({
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
            { targets: 6, responsivePriority: 2 },
            { targets: 11, responsivePriority: 1, orderable: false }
        ]
    });
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
    var activo = String(tr.attr('data-activo')) === '1';
    $('#idEquipoDel').val(tr.find('td').eq(0).text());
    $('#lblEquipoDel').text(tr.find('td').eq(1).text() + ' - ' + tr.find('td').eq(4).text() + ' ' + tr.find('td').eq(5).text());
    $('#tituloEstadoEquipo').text(activo ? 'Dar de baja el equipo' : 'Reactivar equipo');
    $('#textoEstadoEquipo').text(activo
        ? 'El equipo quedará inactivo y su historial se conservará.'
        : 'El equipo volverá a estar activo con estado Disponible.');
    $('#btnEstadoEquipo')
        .toggleClass('btn-danger', activo)
        .toggleClass('btn-success', !activo)
        .text(activo ? 'Dar de baja' : 'Reactivar');
}
</script>

<?php else: ?>
<p class="lead"><em>No hay registros.</em></p>
<?php endif; ?>

<!-- MODAL IMAGEN -->
<div class="modal fade" id="imgModal" tabindex="-1" role="dialog" aria-labelledby="tituloFotoEquipo">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="tituloFotoEquipo">Foto del equipo</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>
    <div class="modal-body text-center">
      <img id="equipoFoto" class="app-image-view" src="" alt="Vista ampliada del equipo">
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
    </div>
  </div></div>
</div>

<!-- MODAL NUEVO -->
<div class="modal fade" id="newModal" tabindex="-1" role="dialog" aria-labelledby="tituloNuevoEquipo">
  <div class="modal-dialog modal-lg app-modal-wide"><div class="modal-content">
    <form action="<?= BASE_URL ?>/equipos.php" method="post" enctype="multipart/form-data">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="tituloNuevoEquipo">Nuevo equipo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="app-form-sections">
        <section class="form-section" aria-labelledby="nuevoEquipoIdentificacion">
          <div class="form-section-header">
            <h6 class="form-section-title" id="nuevoEquipoIdentificacion">Identificación</h6>
            <small class="form-section-help">Datos que permiten reconocer y etiquetar el activo.</small>
          </div>
          <div class="form-grid">
        <div class="form-group">
          <div class="catalogo-contextual-encabezado">
            <label for="nuevoEquipoMarca">Marca</label>
            <button type="button" class="btn btn-link btn-sm js-catalogo-toggle" data-catalogo-target="#altaMarcaEquipo">
              <i class="fa fa-plus"></i> Nueva marca
            </button>
          </div>
          <select name="idmarca" id="nuevoEquipoMarca" class="form-select" data-catalogo-select="marca" required>
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
              <button type="button" class="btn btn-primary js-catalogo-guardar"><i class="fa fa-check"></i> Guardar</button>
              <button type="button" class="btn btn-secondary js-catalogo-cancelar" title="Cancelar"><i class="fa fa-times"></i></button>
            </div>
            <small class="catalogo-contextual-ayuda">Se agregará al catálogo y quedará seleccionada.</small>
            <span class="catalogo-contextual-error" aria-live="polite"></span>
          </div>
        </div>
        <div class="form-group">
          <div class="catalogo-contextual-encabezado">
            <label for="nuevoEquipoModelo">Modelo</label>
            <button type="button" class="btn btn-link btn-sm js-catalogo-toggle" data-catalogo-target="#altaModeloEquipo">
              <i class="fa fa-plus"></i> Nuevo modelo
            </button>
          </div>
          <select name="idmodelo" id="nuevoEquipoModelo" class="form-select" data-catalogo-select="modelo" required>
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
              <button type="button" class="btn btn-primary js-catalogo-guardar"><i class="fa fa-check"></i> Guardar</button>
              <button type="button" class="btn btn-secondary js-catalogo-cancelar" title="Cancelar"><i class="fa fa-times"></i></button>
            </div>
            <small class="catalogo-contextual-ayuda">Se agregará al catálogo y quedará seleccionado.</small>
            <span class="catalogo-contextual-error" aria-live="polite"></span>
          </div>
        </div>
        <div class="form-group"><label for="serieNuevoEquipo">Número de serie</label><input type="text" name="numero_serie" id="serieNuevoEquipo" class="form-control" maxlength="100" placeholder="Identificador del fabricante"></div>
        <div class="form-group"><label for="tipoNuevoEquipo">Tipo de equipo</label><select name="tipo_equipo" id="tipoNuevoEquipo" class="form-select" required>
          <option value="Laptop">Laptop</option><option value="Computadora de escritorio">Computadora de escritorio</option>
          <option value="Monitor">Monitor</option><option value="Teléfono">Teléfono</option><option value="Impresora">Impresora</option>
          <option value="Servidor">Servidor</option><option value="Equipo de red">Equipo de red</option><option value="Otro">Otro</option></select></div>
        <div class="form-group form-span-2"><label for="codigoNuevoEquipo">Código de activo</label><input type="text" id="codigoNuevoEquipo" class="form-control" value="Se generará automáticamente (EQ-0001)" disabled></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="nuevoEquipoCompra">
          <div class="form-section-header">
            <h6 class="form-section-title" id="nuevoEquipoCompra">Compra y garantía</h6>
          </div>
          <div class="form-grid">
        <div class="form-group"><label for="fechaCompraNuevo">Fecha de compra</label><input type="date" name="fecha_compra" id="fechaCompraNuevo" class="form-control"></div>
        <div class="form-group"><label for="costoNuevo">Costo (L)</label><input type="number" name="costo" id="costoNuevo" class="form-control" min="0" step="0.01" placeholder="0.00"></div>
        <div class="form-group"><label for="facturaNueva">Número de factura</label><input type="text" name="factura" id="facturaNueva" class="form-control" maxlength="100"></div>
        <div class="form-group"><label for="garantiaNueva">Vencimiento de garantía</label><input type="date" name="vencimiento_garantia" id="garantiaNueva" class="form-control"></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="nuevoEquipoEstado">
          <div class="form-section-header">
            <h6 class="form-section-title" id="nuevoEquipoEstado">Estado operativo</h6>
          </div>
          <div class="form-grid">
        <div class="form-group form-span-2"><label for="estadoNuevoEquipo">Estado inicial</label><input type="text" id="estadoNuevoEquipo" class="form-control" value="Disponible" disabled><small class="text-muted">Todo equipo nuevo inicia automáticamente como disponible.</small></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="nuevoEquipoImagen">
          <div class="form-section-header">
            <h6 class="form-section-title" id="nuevoEquipoImagen">Imagen</h6>
            <small class="form-section-help">La carga conserva el comportamiento actual del sistema.</small>
          </div>
          <div class="form-grid">
        <div class="form-group form-span-2"><label for="fotoNuevoEquipo">Foto</label><input type="file" name="archivo" id="fotoNuevoEquipo" class="form-control" accept="image/*"></div>
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
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="tituloEditarEquipo">
  <div class="modal-dialog modal-lg app-modal-wide"><div class="modal-content">
    <form action="<?= BASE_URL ?>/equipos.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="idequipo" id="idequipo">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="tituloEditarEquipo">Editar equipo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="app-form-sections">
        <section class="form-section" aria-labelledby="editarEquipoIdentificacion">
          <div class="form-section-header">
            <h6 class="form-section-title" id="editarEquipoIdentificacion">Identificación</h6>
          </div>
          <div class="form-grid">
        <div class="form-group"><label for="marcaAct">Marca</label>
          <select name="marcaAct" id="marcaAct" class="form-select" data-catalogo-select="marca" required>
            <?php foreach ($marcasTodas as $m): ?>
            <option value="<?= $m['idmarca'] ?>"><?= htmlspecialchars($m['nombreMarca']) ?><?= (int)$m['activo'] === 0 ? ' (inactiva)' : '' ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label for="modeloAct">Modelo</label>
          <select name="modeloAct" id="modeloAct" class="form-select" data-catalogo-select="modelo" required>
            <?php foreach ($modelosTodos as $mo): ?>
            <option value="<?= $mo['idmodelo'] ?>"><?= htmlspecialchars($mo['nombreModelo']) ?><?= (int)$mo['activo'] === 0 ? ' (inactivo)' : '' ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="form-group"><label for="numero_serieAct">Número de serie</label><input type="text" name="numero_serieAct" id="numero_serieAct" class="form-control" maxlength="100"></div>
        <div class="form-group"><label for="tipo_equipoAct">Tipo de equipo</label><select name="tipo_equipoAct" id="tipo_equipoAct" class="form-select" required>
          <option value="Laptop">Laptop</option><option value="Computadora de escritorio">Computadora de escritorio</option>
          <option value="Monitor">Monitor</option><option value="Teléfono">Teléfono</option><option value="Impresora">Impresora</option>
          <option value="Servidor">Servidor</option><option value="Equipo de red">Equipo de red</option><option value="Otro">Otro</option></select></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="editarEquipoCompra">
          <div class="form-section-header">
            <h6 class="form-section-title" id="editarEquipoCompra">Compra y garantía</h6>
          </div>
          <div class="form-grid">
        <div class="form-group"><label for="fecha_compraAct">Fecha de compra</label><input type="date" name="fecha_compraAct" id="fecha_compraAct" class="form-control"></div>
        <div class="form-group"><label for="costoAct">Costo (L)</label><input type="number" name="costoAct" id="costoAct" class="form-control" min="0" step="0.01"></div>
        <div class="form-group"><label for="facturaAct">Número de factura</label><input type="text" name="facturaAct" id="facturaAct" class="form-control" maxlength="100"></div>
        <div class="form-group"><label for="vencimiento_garantiaAct">Vencimiento de garantía</label><input type="date" name="vencimiento_garantiaAct" id="vencimiento_garantiaAct" class="form-control"></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="editarEquipoEstado">
          <div class="form-section-header">
            <h6 class="form-section-title" id="editarEquipoEstado">Estado operativo</h6>
            <small class="form-section-help">El estado Asignado se controla desde las transacciones.</small>
          </div>
          <div class="form-grid">
        <div class="form-group form-span-2"><label for="estado_equipoAct">Estado del equipo</label><select name="estado_equipoAct" id="estado_equipoAct" class="form-select" required>
          <option value="1">Disponible</option><option value="2" disabled>Asignado (automático)</option><option value="3">En mantenimiento</option><option value="4">Perdido o robado</option><option value="5">Dado de baja</option></select></div>
          </div>
        </section>
        <section class="form-section" aria-labelledby="editarEquipoImagen">
          <div class="form-section-header">
            <h6 class="form-section-title" id="editarEquipoImagen">Imagen</h6>
            <small class="form-section-help">Déjalo vacío para conservar la imagen actual.</small>
          </div>
          <div class="form-grid">
        <div class="form-group form-span-2"><label for="fotoActEquipo">Nueva foto (opcional)</label><input type="file" name="archivoAct" id="fotoActEquipo" class="form-control" accept="image/*"></div>
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
<div class="modal fade" id="delModal" tabindex="-1" role="dialog" aria-labelledby="tituloEstadoEquipo">
  <div class="modal-dialog"><div class="modal-content">
    <form action="<?= BASE_URL ?>/equipos.php" method="post">
      <input type="hidden" name="idEquipoDel" id="idEquipoDel">
      
      <?= Auth::csrfField() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="tituloEstadoEquipo">Cambiar estado del equipo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p><strong><span id="lblEquipoDel"></span></strong></p>
        <p id="textoEstadoEquipo">Confirma el cambio de estado del equipo.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger" id="btnEstadoEquipo" name="del" value="1">Confirmar</button>
      </div>
    </form>
  </div></div>
</div>
