<?php if ($rows): ?>
<p class="responsive-table-note"><i class="fa fa-circle-info" aria-hidden="true"></i> En pantallas pequeñas, pulsa la primera celda para ver las columnas ocultas.</p>
<table class="table table-bordered table-striped nowrap" id="tablaLicencias">
  <thead><tr><th>Código</th><th>Software</th><th>Modalidad</th><th>Métrica</th><th>Cupos</th><th>Vencimiento</th><th>Titular</th><th>Estado</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?>
    <?php
      $activo=(int)$row['activo']===1;
      $cuposConsumidos=$row['cantidad_total']===null
        ? (int)$row['cupos_usados']
        : max(0,(int)$row['cantidad_total']-(int)$row['cupos_disponibles']);
      $estado=LicenciaEstado::estado($row,$cuposConsumidos);
      $version=trim(($row['version'] ?: '').' '.($row['edicion'] ?: ''));
    ?>
    <tr class="<?= $activo ? '' : 'text-muted' ?>"
        data-id="<?= (int)$row['idlicencia'] ?>"
        data-codigo="<?= htmlspecialchars($row['codigo_licencia'],ENT_QUOTES) ?>"
        data-idsoftware="<?= (int)$row['idsoftware'] ?>"
        data-idproveedor="<?= (int)($row['idproveedor'] ?? 0) ?>"
        data-modalidad="<?= htmlspecialchars($row['modalidad'],ENT_QUOTES) ?>"
        data-metrica="<?= htmlspecialchars($row['metrica'],ENT_QUOTES) ?>"
        data-cantidad="<?= htmlspecialchars((string)($row['cantidad_total'] ?? ''),ENT_QUOTES) ?>"
        data-fecha-compra="<?= htmlspecialchars($row['fecha_compra'] ?? '',ENT_QUOTES) ?>"
        data-fecha-inicio="<?= htmlspecialchars($row['fecha_inicio'] ?? '',ENT_QUOTES) ?>"
        data-fecha-vencimiento="<?= htmlspecialchars($row['fecha_vencimiento'] ?? '',ENT_QUOTES) ?>"
        data-renovacion="<?= (int)$row['renovacion_automatica'] ?>"
        data-reutilizable="<?= (int)$row['reutilizable'] ?>"
        data-costo="<?= htmlspecialchars((string)($row['costo_total'] ?? ''),ENT_QUOTES) ?>"
        data-moneda="<?= htmlspecialchars($row['moneda'],ENT_QUOTES) ?>"
        data-factura="<?= htmlspecialchars($row['factura'] ?? '',ENT_QUOTES) ?>"
        data-orden="<?= htmlspecialchars($row['orden_compra'] ?? '',ENT_QUOTES) ?>"
        data-contrato="<?= htmlspecialchars($row['numero_contrato'] ?? '',ENT_QUOTES) ?>"
        data-titular="<?= htmlspecialchars($row['licenciado_a_nombre'] ?? '',ENT_QUOTES) ?>"
        data-correo="<?= htmlspecialchars($row['licenciado_a_correo'] ?? '',ENT_QUOTES) ?>"
        data-observaciones="<?= htmlspecialchars($row['observaciones'] ?? '',ENT_QUOTES) ?>"
        data-clave-mascara="<?= htmlspecialchars($row['clave_mascara'] ?? '',ENT_QUOTES) ?>"
        data-activo="<?= $activo ? 1 : 0 ?>">
      <td><strong><?= htmlspecialchars($row['codigo_licencia']) ?></strong></td>
      <td><?= htmlspecialchars($row['fabricante'].' · '.$row['software']) ?><?php if($version!==''): ?><small class="d-block text-muted"><?= htmlspecialchars($version) ?></small><?php endif; ?></td>
      <td><?= htmlspecialchars($row['modalidad']) ?></td>
      <td><?= htmlspecialchars(LicenciaEstado::metricas()[$row['metrica']] ?? $row['metrica']) ?></td>
      <td><?= $row['cantidad_total']===null ? 'Sin límite' : (int)$row['cupos_disponibles'].' disponibles / '.(int)$row['cantidad_total'] ?></td>
      <td data-order="<?= htmlspecialchars($row['fecha_vencimiento'] ?? '9999-12-31') ?>"><?= $row['fecha_vencimiento'] ? date('d/m/Y',strtotime($row['fecha_vencimiento'])) : 'Sin vencimiento' ?></td>
      <td><?= htmlspecialchars($row['licenciado_a_nombre'] ?: '—') ?><?php if($row['licenciado_a_correo']): ?><small class="d-block text-muted"><?= htmlspecialchars($row['licenciado_a_correo']) ?></small><?php endif; ?></td>
      <td><span class="badge app-badge-<?= LicenciaEstado::badge($estado) ?>"><?= htmlspecialchars($estado) ?></span><?php if(!$activo): ?><small class="d-block text-muted">Registro desactivado</small><?php endif; ?></td>
      <td class="table-actions">
        <a href="<?= BASE_URL ?>/licencia.php?id=<?= (int)$row['idlicencia'] ?>" title="Ver ficha y clave" aria-label="Ver ficha y clave de licencia"><i class="fa fa-eye" aria-hidden="true"></i></a>
        <a href="#" onclick="return editarLicencia(event)" data-bs-toggle="modal" data-bs-target="#editLicenciaModal" title="Editar" aria-label="Editar licencia"><i class="fa fa-edit" aria-hidden="true"></i></a>
        <a href="#" onclick="return estadoLicencia(event)" data-bs-toggle="modal" data-bs-target="#estadoLicenciaModal" title="<?= $activo?'Desactivar':'Reactivar' ?>" aria-label="<?= $activo?'Desactivar licencia':'Reactivar licencia' ?>"><i class="fa fa-<?= $activo?'trash':'undo' ?>" aria-hidden="true"></i></a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>
$(function(){
  $('#tablaLicencias').DataTable({
    dom:'lrtip',order:[[0,'desc']],autoWidth:false,
    responsive:{details:{type:'inline',target:'td:first-child'}},
    columnDefs:[
      {targets:0,className:'dtr-control',responsivePriority:1},
      {targets:1,responsivePriority:1},
      {targets:7,responsivePriority:2},
      {targets:8,responsivePriority:1,orderable:false}
    ]
  });
});
function reglasLicencia(prefijo){
  var modalidad=$('#'+prefijo+'Modalidad').val(), metrica=$('#'+prefijo+'Metrica').val();
  var requiereFechas=modalidad==='<?= addslashes(LicenciaEstado::SUSCRIPCION) ?>'||modalidad==='<?= addslashes(LicenciaEstado::PRUEBA) ?>';
  $('#'+prefijo+'FechaInicio,#'+prefijo+'FechaVencimiento').prop('required',requiereFechas);
  $('#'+prefijo+'Renovacion').prop('disabled',modalidad!=='<?= addslashes(LicenciaEstado::SUSCRIPCION) ?>');
  if(modalidad!=='<?= addslashes(LicenciaEstado::SUSCRIPCION) ?>'){ $('#'+prefijo+'Renovacion').prop('checked',false); }
  $('#'+prefijo+'Cantidad').prop('required',metrica!=='<?= addslashes(LicenciaEstado::CORPORATIVA) ?>');
}
function editarLicencia(evento){
  var tr=$(evento.target).closest('tr'), p='editLicencia';
  $('#'+p+'Id').val(tr.data('id'));
  $('#'+p+'Software').val(String(tr.data('idsoftware')));
  $('#'+p+'Proveedor').val(String(tr.data('idproveedor')||0));
  $('#'+p+'Modalidad').val(tr.attr('data-modalidad'));
  $('#'+p+'Metrica').val(tr.attr('data-metrica'));
  $('#'+p+'Cantidad').val(tr.attr('data-cantidad'));
  $('#'+p+'FechaCompra').val(tr.attr('data-fecha-compra'));
  $('#'+p+'FechaInicio').val(tr.attr('data-fecha-inicio'));
  $('#'+p+'FechaVencimiento').val(tr.attr('data-fecha-vencimiento'));
  $('#'+p+'Renovacion').prop('checked',tr.attr('data-renovacion')==='1');
  $('#'+p+'Reutilizable').val(tr.attr('data-reutilizable'));
  $('#'+p+'Costo').val(tr.attr('data-costo'));
  $('#'+p+'Moneda').val(tr.attr('data-moneda'));
  $('#'+p+'Factura').val(tr.attr('data-factura'));
  $('#'+p+'Orden').val(tr.attr('data-orden'));
  $('#'+p+'Contrato').val(tr.attr('data-contrato'));
  $('#'+p+'Titular').val(tr.attr('data-titular'));
  $('#'+p+'Correo').val(tr.attr('data-correo'));
  $('#'+p+'Observaciones').val(tr.attr('data-observaciones'));
  $('#'+p+'Clave').val(''); $('#'+p+'QuitarClave').prop('checked',false);
  var mascara=tr.attr('data-clave-mascara');
  $('#'+p+'ClaveActual').text(mascara?('Clave guardada: '+mascara):'No hay una clave de producto guardada.');
  $('#'+p+'QuitarContenedor').toggle(!!mascara);
  reglasLicencia(p); return true;
}
function estadoLicencia(evento){
  var tr=$(evento.target).closest('tr'),activo=String(tr.attr('data-activo'))==='1';
  $('#estadoLicenciaId').val(tr.data('id')); $('#estadoLicenciaCodigo').text(tr.attr('data-codigo'));
  $('#estadoLicenciaTitulo').text(activo?'Desactivar licencia':'Reactivar licencia');
  $('#estadoLicenciaAyuda').text(activo?'No podrá desactivarse si tiene cupos o instalaciones activas. El historial no se borrará.':'Volverá a estar disponible para uso y asignación.');
  $('#estadoLicenciaBoton').toggleClass('btn-danger',activo).toggleClass('btn-success',!activo).text(activo?'Desactivar':'Reactivar'); return true;
}
$(document).on('change','[data-license-modalidad],[data-license-metrica]',function(){ reglasLicencia($(this).attr('data-license-prefix')); });
</script>
<?php else: ?><p class="lead"><em>No hay licencias que coincidan con los filtros.</em></p><?php endif; ?>

<?php
$camposLicencia=static function(string $prefijo,array $software,array $proveedores):void{
  $p=htmlspecialchars($prefijo,ENT_QUOTES); $esNuevo=str_starts_with($prefijo,'new'); ?>
  <div class="form-grid">
    <div class="form-group form-span-2"><label for="<?= $p ?>Software">Software</label><select name="idsoftware" id="<?= $p ?>Software" class="form-select" required><option value="">-- Seleccione --</option><?php foreach($software as $item): $detalle=trim(($item['version']?:'').' '.($item['edicion']?:'')); $inactivo=(int)$item['activo']!==1; ?><option value="<?= (int)$item['idsoftware'] ?>" <?= $esNuevo&&$inactivo?'disabled':'' ?>><?= htmlspecialchars($item['fabricante'].' · '.$item['nombre'].($detalle!==''?' · '.$detalle:'').($inactivo?' (Inactivo)':'')) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="<?= $p ?>Modalidad">Modalidad</label><select name="modalidad" id="<?= $p ?>Modalidad" class="form-select" required data-license-modalidad data-license-prefix="<?= $p ?>"><?php foreach(LicenciaEstado::modalidades() as $valor=>$texto): ?><option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($texto) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="<?= $p ?>Metrica">Métrica</label><select name="metrica" id="<?= $p ?>Metrica" class="form-select" required data-license-metrica data-license-prefix="<?= $p ?>"><?php foreach(LicenciaEstado::metricas() as $valor=>$texto): ?><option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($texto) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="<?= $p ?>Cantidad">Cantidad total</label><input type="number" name="cantidad_total" id="<?= $p ?>Cantidad" class="form-control" min="1" max="10000" required><small class="form-text text-muted">En una licencia corporativa puede quedar vacía.</small></div>
    <div class="form-group"><label for="<?= $p ?>Reutilizable">¿Se puede reasignar?</label><select name="reutilizable" id="<?= $p ?>Reutilizable" class="form-select"><option value="1">Sí</option><option value="0">No</option></select></div>
    <div class="form-group"><label for="<?= $p ?>FechaCompra">Fecha de compra</label><input type="date" name="fecha_compra" id="<?= $p ?>FechaCompra" class="form-control"></div>
    <div class="form-group"><label for="<?= $p ?>FechaInicio">Inicio de vigencia</label><input type="date" name="fecha_inicio" id="<?= $p ?>FechaInicio" class="form-control"></div>
    <div class="form-group"><label for="<?= $p ?>FechaVencimiento">Vencimiento</label><input type="date" name="fecha_vencimiento" id="<?= $p ?>FechaVencimiento" class="form-control"></div>
    <div class="form-group d-flex align-items-end"><div class="form-check mb-2"><input type="checkbox" name="renovacion_automatica" value="1" id="<?= $p ?>Renovacion" class="form-check-input"><label for="<?= $p ?>Renovacion" class="form-check-label">Renovación automática</label></div></div>
    <div class="form-group form-span-2"><label for="<?= $p ?>Proveedor">Proveedor</label><select name="idproveedor" id="<?= $p ?>Proveedor" class="form-select"><option value="0">Sin proveedor registrado</option><?php foreach($proveedores as $proveedor): $inactivo=(int)$proveedor['activo']!==1; ?><option value="<?= (int)$proveedor['idproveedor'] ?>" <?= $esNuevo&&$inactivo?'disabled':'' ?>><?= htmlspecialchars($proveedor['nombre'].($inactivo?' (Inactivo)':'')) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="<?= $p ?>Costo">Costo total</label><input type="number" name="costo_total" id="<?= $p ?>Costo" class="form-control" min="0" step="0.01"></div>
    <div class="form-group"><label for="<?= $p ?>Moneda">Moneda</label><select name="moneda" id="<?= $p ?>Moneda" class="form-select"><?php foreach(LicenciaEstado::monedas() as $valor=>$texto): ?><option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($texto) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="<?= $p ?>Factura">Factura</label><input type="text" name="factura" id="<?= $p ?>Factura" class="form-control" maxlength="100"></div>
    <div class="form-group"><label for="<?= $p ?>Orden">Orden de compra</label><input type="text" name="orden_compra" id="<?= $p ?>Orden" class="form-control" maxlength="100"></div>
    <div class="form-group form-span-2"><label for="<?= $p ?>Contrato">Número de contrato</label><input type="text" name="numero_contrato" id="<?= $p ?>Contrato" class="form-control" maxlength="100"></div>
    <div class="form-group"><label for="<?= $p ?>Titular">Licenciado a nombre de</label><input type="text" name="licenciado_a_nombre" id="<?= $p ?>Titular" class="form-control" maxlength="150"></div>
    <div class="form-group"><label for="<?= $p ?>Correo">Correo licenciado</label><input type="email" name="licenciado_a_correo" id="<?= $p ?>Correo" class="form-control" maxlength="150"></div>
    <div class="form-group form-span-2"><label for="<?= $p ?>Clave">Clave de producto <?= str_starts_with($prefijo,'edit')?'<small class="text-muted">(dejar vacía para conservar)</small>':'' ?></label><input type="password" name="clave_producto" id="<?= $p ?>Clave" class="form-control" maxlength="1000" autocomplete="new-password"><small class="form-text text-muted">Se cifra antes de guardarse y nunca se muestra completa en las tablas.</small></div>
    <?php if(str_starts_with($prefijo,'edit')): ?><div class="form-group form-span-2"><p id="<?= $p ?>ClaveActual" class="mb-2 text-muted"></p><div class="form-check" id="<?= $p ?>QuitarContenedor"><input type="checkbox" name="quitar_clave" value="1" id="<?= $p ?>QuitarClave" class="form-check-input"><label for="<?= $p ?>QuitarClave" class="form-check-label">Eliminar la clave guardada</label></div></div><?php endif; ?>
    <div class="form-group form-span-2"><label for="<?= $p ?>Observaciones">Observaciones</label><textarea name="observaciones" id="<?= $p ?>Observaciones" class="form-control" rows="3" maxlength="1000"></textarea></div>
  </div>
<?php }; ?>

<div class="modal fade" id="newLicenciaModal" tabindex="-1" aria-labelledby="newLicenciaTitulo" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content"><form action="<?= BASE_URL ?>/licencias.php" method="post"><?= Auth::csrfField() ?>
  <div class="modal-header"><h5 class="modal-title" id="newLicenciaTitulo">Registrar licencia</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><?php $camposLicencia('newLicencia',$software,$proveedores); ?></div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success" name="add" value="1">Guardar licencia</button></div>
</form></div></div></div>

<div class="modal fade" id="editLicenciaModal" tabindex="-1" aria-labelledby="editLicenciaTitulo" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content"><form action="<?= BASE_URL ?>/licencias.php" method="post"><?= Auth::csrfField() ?><input type="hidden" name="idlicencia" id="editLicenciaId">
  <div class="modal-header"><h5 class="modal-title" id="editLicenciaTitulo">Editar licencia</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><?php $camposLicencia('editLicencia',$software,$proveedores); ?></div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="edit" value="1">Actualizar licencia</button></div>
</form></div></div></div>

<div class="modal fade" id="estadoLicenciaModal" tabindex="-1" aria-labelledby="estadoLicenciaTitulo" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><form action="<?= BASE_URL ?>/licencias.php" method="post"><?= Auth::csrfField() ?><input type="hidden" name="idlicencia" id="estadoLicenciaId">
  <div class="modal-header"><h5 class="modal-title" id="estadoLicenciaTitulo">Cambiar estado</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><p><strong id="estadoLicenciaCodigo"></strong></p><p class="text-muted" id="estadoLicenciaAyuda"></p></div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn" id="estadoLicenciaBoton" name="del" value="1">Confirmar</button></div>
</form></div></div></div>
<script>$(function(){ reglasLicencia('newLicencia'); });</script>
