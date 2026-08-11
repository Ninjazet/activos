<?php
$cuposDisponibles = (int)$licencia['cupos_disponibles'];
$cuposConsumidos = $licencia['cantidad_total'] === null
    ? (int)$licencia['cupos_usados']
    : max(0, (int)$licencia['cantidad_total'] - $cuposDisponibles);
$estado = LicenciaEstado::estado($licencia, $cuposConsumidos);
$version = trim(($licencia['version'] ?: '') . ' ' . ($licencia['edicion'] ?: ''));
$fecha = static fn($valor): string => $valor ? date('d/m/Y', strtotime($valor)) : '—';
$texto = static fn($valor): string => htmlspecialchars((string)($valor ?: '—'));
$tieneClave = !empty($licencia['clave_cifrada']);
$vencida = !empty($licencia['fecha_vencimiento']) && $licencia['fecha_vencimiento'] < date('Y-m-d');
$puedeAsignar = (int)$licencia['activo'] === 1
    && !$vencida
    && ($licencia['cantidad_total'] === null || $cuposDisponibles > 0)
    && ($empleadosDisponibles || $equiposDisponibles);
?>
<div class="wrapper"><div class="container-fluid">
  <div class="page-header">
    <div class="module-header-copy"><a href="<?= BASE_URL ?>/licencias.php" class="small"><i class="fa fa-arrow-left" aria-hidden="true"></i> Volver a licencias</a><h2><?= htmlspecialchars($licencia['codigo_licencia']) ?></h2><p><?= htmlspecialchars($licencia['fabricante'].' · '.$licencia['software'].($version!==''?' · '.$version:'')) ?></p></div>
    <span class="badge app-badge-<?= LicenciaEstado::badge($estado) ?>"><?= htmlspecialchars($estado) ?></span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Cantidad adquirida</small><h3><?= $licencia['cantidad_total']===null?'Sin límite':(int)$licencia['cantidad_total'] ?></h3></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Cupos disponibles</small><h3><?= $licencia['cantidad_total']===null?'Sin limite':$cuposDisponibles ?></h3><small class="text-muted"><?= (int)$licencia['cupos_usados'] ?> asignado(s) ahora</small></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Instalaciones activas</small><h3><?= (int)$licencia['instalaciones_activas'] ?></h3></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Vencimiento</small><h3 class="h5"><?= $fecha($licencia['fecha_vencimiento']) ?></h3></div></div></div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h3 class="h5">Licenciamiento</h3><dl class="row mb-0">
      <dt class="col-sm-5">Modalidad</dt><dd class="col-sm-7"><?= $texto($licencia['modalidad']) ?></dd>
      <dt class="col-sm-5">Métrica</dt><dd class="col-sm-7"><?= htmlspecialchars(LicenciaEstado::metricas()[$licencia['metrica']] ?? $licencia['metrica']) ?></dd>
      <dt class="col-sm-5">Inicio de vigencia</dt><dd class="col-sm-7"><?= $fecha($licencia['fecha_inicio']) ?></dd>
      <dt class="col-sm-5">Reutilizable</dt><dd class="col-sm-7"><?= (int)$licencia['reutilizable']===1?'Sí':'No' ?></dd>
      <dt class="col-sm-5">Renovación automática</dt><dd class="col-sm-7"><?= (int)$licencia['renovacion_automatica']===1?'Sí':'No' ?></dd>
      <dt class="col-sm-5">Clave de producto</dt><dd class="col-sm-7">
        <div class="input-group input-group-sm" id="claveLicenciaControl"
             data-idlicencia="<?= (int)$licencia['idlicencia'] ?>"
             data-mascara="<?= htmlspecialchars($licencia['clave_mascara'] ?: 'No registrada', ENT_QUOTES) ?>"
             data-csrf="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES) ?>"
             data-endpoint="<?= BASE_URL ?>/app/ajax/transacciones/revelar_clave_licencia.php">
          <input type="text" class="form-control font-monospace" id="claveLicenciaValor"
                 value="<?= htmlspecialchars($licencia['clave_mascara'] ?: 'No registrada', ENT_QUOTES) ?>"
                 readonly autocomplete="off" spellcheck="false" aria-label="Clave de producto">
          <button type="button" class="btn btn-outline-secondary" id="verClaveLicencia"
                  title="Ver clave" aria-label="Ver clave de producto" aria-pressed="false" <?= $tieneClave ? '' : 'disabled' ?>>
            <i class="fa fa-eye" aria-hidden="true"></i>
          </button>
          <button type="button" class="btn btn-outline-secondary" id="copiarClaveLicencia"
                  title="Copiar clave" aria-label="Copiar clave de producto" <?= $tieneClave ? '' : 'disabled' ?>>
            <i class="fa fa-copy" aria-hidden="true"></i>
          </button>
        </div>
        <small class="form-text text-muted" id="estadoClaveLicencia" aria-live="polite">La clave completa solo se consulta al presionar el botón de ver o copiar.</small>
      </dd>
    </dl></div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h3 class="h5">Titular</h3><dl class="row mb-0">
      <dt class="col-sm-4">Nombre</dt><dd class="col-sm-8"><?= $texto($licencia['licenciado_a_nombre']) ?></dd>
      <dt class="col-sm-4">Correo</dt><dd class="col-sm-8"><?= $texto($licencia['licenciado_a_correo']) ?></dd>
      <dt class="col-sm-4">Categoría</dt><dd class="col-sm-8"><?= $texto($licencia['categoria']) ?></dd>
      <dt class="col-sm-4">Estado del registro</dt><dd class="col-sm-8"><?= (int)$licencia['activo']===1?'Activo':'Inactivo' ?></dd>
    </dl></div></div></div>
  </div>

  <div class="card mb-4"><div class="card-body"><h3 class="h5">Compra y contrato</h3><div class="table-responsive"><table class="table table-bordered mb-0"><tbody>
    <tr><th scope="row">Proveedor</th><td><?= $texto($licencia['proveedor']) ?></td><th scope="row">Fecha de compra</th><td><?= $fecha($licencia['fecha_compra']) ?></td></tr>
    <tr><th scope="row">Costo total</th><td><?= $licencia['costo_total']!==null?htmlspecialchars($licencia['moneda']).' '.number_format((float)$licencia['costo_total'],2):'—' ?></td><th scope="row">Factura</th><td><?= $texto($licencia['factura']) ?></td></tr>
    <tr><th scope="row">Orden de compra</th><td><?= $texto($licencia['orden_compra']) ?></td><th scope="row">Contrato</th><td><?= $texto($licencia['numero_contrato']) ?></td></tr>
  </tbody></table></div></div></div>

  <div class="card mb-4"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div><h3 class="h5 mb-1">Asignaciones</h3><p class="text-muted mb-0">Entrega y devolucion de cupos sin borrar el historial.</p></div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#asignarLicenciaModal" <?= $puedeAsignar ? '' : 'disabled' ?>><i class="fa fa-user-plus" aria-hidden="true"></i> Asignar licencia</button>
    </div>
    <?php if (!$puedeAsignar): ?>
      <div class="alert alert-info py-2">
        <?php if ((int)$licencia['activo'] !== 1): ?>La licencia esta inactiva.
        <?php elseif ($vencida): ?>La licencia esta vencida.
        <?php elseif ($licencia['cantidad_total'] !== null && $cuposDisponibles === 0): ?>No quedan cupos disponibles.
        <?php else: ?>No hay destinos activos disponibles para esta metrica.<?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($asignaciones): ?>
      <div class="table-responsive"><table class="table table-bordered align-middle w-100" id="tablaAsignacionesLicencia">
        <thead><tr><th>ID</th><th>Destino</th><th>Cuenta</th><th>Cupo</th><th>Asignada</th><th>Devuelta</th><th>Estado</th><th>Accion</th></tr></thead>
        <tbody><?php foreach ($asignaciones as $asignacion): $destino=$asignacion['empleado'] ?: $asignacion['equipo']; ?>
          <tr>
            <td><?= (int)$asignacion['idasignacion_licencia'] ?></td>
            <td><?= htmlspecialchars((string)$destino) ?></td>
            <td><?= $texto($asignacion['correo_cuenta']) ?></td>
            <td><?= $asignacion['numero_cupo']===null?'Ilimitado':'#'.(int)$asignacion['numero_cupo'] ?></td>
            <td><?= date('d/m/Y H:i',strtotime($asignacion['fecha_asignacion'])) ?></td>
            <td><?= $asignacion['fecha_devolucion']?date('d/m/Y H:i',strtotime($asignacion['fecha_devolucion'])):'—' ?></td>
            <td><?= (int)$asignacion['activa']===1?'<span class="badge app-badge-success">Activa</span>':'<span class="badge app-badge-muted">Devuelta</span>' ?></td>
            <td>
              <?php if ((int)$asignacion['activa']===1): ?>
                <button type="button" class="btn btn-sm btn-light js-devolver-licencia"
                        data-id="<?= (int)$asignacion['idasignacion_licencia'] ?>"
                        data-destino="<?= htmlspecialchars((string)$destino,ENT_QUOTES) ?>"
                        data-bs-toggle="modal" data-bs-target="#devolverLicenciaModal">Devolver</button>
              <?php else: ?><span class="text-muted"><?= $texto($asignacion['motivo_devolucion']) ?></span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table></div>
    <?php else: ?><p class="text-muted mb-0">Esta licencia aun no tiene asignaciones.</p><?php endif; ?>
  </div></div>

  <div class="card mb-4"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div><h3 class="h5 mb-1">Cupos</h3><p class="text-muted mb-0">Disponibilidad actual y trazabilidad de cada cupo.</p></div>
      <?php if ($licencia['cantidad_total']!==null): ?><span class="badge app-badge-primary"><?= $cuposDisponibles ?> disponible(s) · <?= (int)$licencia['cupos_retirados'] ?> retirado(s)</span><?php endif; ?>
    </div>
    <?php if ($licencia['cantidad_total']===null): ?>
      <div class="alert alert-light border mb-0">Esta licencia corporativa no usa cupos numerados. Sus asignaciones se controlan directamente por empleado o equipo.</div>
    <?php elseif ($cupos): ?>
      <div class="table-responsive"><table class="table table-bordered align-middle w-100" id="tablaCuposLicencia">
        <thead><tr><th>Cupo</th><th>Etiqueta</th><th>Clave individual</th><th>Estado</th><th>Asignado a</th></tr></thead>
        <tbody><?php foreach($cupos as $cupo):
          $estadoCupo=(int)$cupo['activo']!==1?'Retirado':($cupo['idasignacion_licencia']!==null?'Asignado':'Disponible');
          $badgeCupo=$estadoCupo==='Disponible'?'success':($estadoCupo==='Asignado'?'primary':'muted');
        ?><tr>
          <td>#<?= (int)$cupo['numero_cupo'] ?></td>
          <td><?= $texto($cupo['etiqueta']) ?></td>
          <td><?= $texto($cupo['clave_mascara']) ?></td>
          <td><span class="badge app-badge-<?= $badgeCupo ?>"><?= $estadoCupo ?></span></td>
          <td><?= $texto($cupo['asignado_a'] ?: $cupo['motivo_retiro']) ?></td>
        </tr><?php endforeach; ?></tbody>
      </table></div>
    <?php else: ?><p class="text-muted mb-0">No hay cupos generados. Aplica la migracion de la parte 3.</p><?php endif; ?>
  </div></div>

  <div class="card"><div class="card-body"><h3 class="h5">Observaciones</h3><p class="mb-0"><?= nl2br($texto($licencia['observaciones'] ?: 'Sin observaciones')) ?></p></div></div>
</div></div>

<div class="modal fade" id="asignarLicenciaModal" tabindex="-1" aria-labelledby="asignarLicenciaTitulo" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form action="<?= BASE_URL ?>/licencia.php?id=<?= (int)$licencia['idlicencia'] ?>" method="post">
    <?= Auth::csrfField() ?><input type="hidden" name="idlicencia" value="<?= (int)$licencia['idlicencia'] ?>">
    <div class="modal-header"><h5 class="modal-title" id="asignarLicenciaTitulo">Asignar <?= htmlspecialchars($licencia['software']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body"><div class="form-grid">
      <div class="form-group form-span-2"><label for="tipoDestinoLicencia">Tipo de destino</label><select name="tipo_destino" id="tipoDestinoLicencia" class="form-select" required>
        <?php if(in_array('empleado',$destinosPermitidos,true)): ?><option value="empleado">Empleado</option><?php endif; ?>
        <?php if(in_array('equipo',$destinosPermitidos,true)): ?><option value="equipo">Equipo</option><?php endif; ?>
      </select></div>
      <div class="form-group form-span-2" id="destinoEmpleadoGrupo"><label for="destinoEmpleadoLicencia">Empleado</label><select id="destinoEmpleadoLicencia" class="form-select">
        <option value="">Selecciona un empleado</option><?php foreach($empleadosDisponibles as $empleado): ?><option value="<?= (int)$empleado['idempleado'] ?>" data-correo="<?= htmlspecialchars($empleado['correo']??'',ENT_QUOTES) ?>"><?= htmlspecialchars($empleado['nombre']) ?></option><?php endforeach; ?>
      </select></div>
      <div class="form-group form-span-2" id="destinoEquipoGrupo"><label for="destinoEquipoLicencia">Equipo</label><select id="destinoEquipoLicencia" class="form-select">
        <option value="">Selecciona un equipo</option><?php foreach($equiposDisponibles as $equipo): ?><option value="<?= (int)$equipo['idequipo'] ?>"><?= htmlspecialchars($equipo['codigo_activo'].' · '.$equipo['tipo_equipo'].' · '.$equipo['nombreMarca'].' '.$equipo['nombreModelo'].' · '.EquipoEstado::nombre((int)$equipo['estado_equipo'])) ?></option><?php endforeach; ?>
      </select></div>
      <div class="form-group form-span-2"><label for="correoCuentaLicencia">Correo o cuenta asociada</label><input type="email" name="correo_cuenta" id="correoCuentaLicencia" class="form-control" maxlength="150" placeholder="usuario@empresa.com"><small class="form-text text-muted">Opcional; permite identificar la cuenta que utiliza la licencia.</small></div>
      <div class="form-group form-span-2"><label for="observacionesAsignacionLicencia">Observaciones</label><textarea name="observaciones" id="observacionesAsignacionLicencia" class="form-control" rows="3" maxlength="1000"></textarea></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="asignar_licencia" value="1">Confirmar asignacion</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="devolverLicenciaModal" tabindex="-1" aria-labelledby="devolverLicenciaTitulo" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
  <form action="<?= BASE_URL ?>/licencia.php?id=<?= (int)$licencia['idlicencia'] ?>" method="post">
    <?= Auth::csrfField() ?><input type="hidden" name="idasignacion_licencia" id="devolverLicenciaId">
    <div class="modal-header"><h5 class="modal-title" id="devolverLicenciaTitulo">Devolver licencia</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body"><p>Se cerrara la asignacion de <strong id="devolverLicenciaDestino"></strong>. El historial se conservara.</p><div class="form-group mb-0"><label for="motivoDevolucionLicencia">Motivo</label><textarea name="motivo_devolucion" id="motivoDevolucionLicencia" class="form-control" rows="3" maxlength="500" required placeholder="Ej.: cambio de puesto, equipo reemplazado o licencia ya no requerida"></textarea></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" name="devolver_licencia" value="1">Devolver licencia</button></div>
  </form>
</div></div></div>

<script>
(function(){
  var tipo=$('#tipoDestinoLicencia');
  var empleado=$('#destinoEmpleadoLicencia');
  var equipo=$('#destinoEquipoLicencia');
  function actualizarDestino(){
    var esEmpleado=tipo.val()==='empleado';
    $('#destinoEmpleadoGrupo').toggle(esEmpleado);
    $('#destinoEquipoGrupo').toggle(!esEmpleado);
    empleado.prop({disabled:!esEmpleado,required:esEmpleado}).attr('name',esEmpleado?'id_destino':null);
    equipo.prop({disabled:esEmpleado,required:!esEmpleado}).attr('name',esEmpleado?null:'id_destino');
  }
  tipo.on('change',actualizarDestino); actualizarDestino();
  empleado.on('change',function(){ var correo=$(this).find(':selected').attr('data-correo'); if(correo){$('#correoCuentaLicencia').val(correo);} });
  $('.js-devolver-licencia').on('click',function(){ $('#devolverLicenciaId').val($(this).attr('data-id')); $('#devolverLicenciaDestino').text($(this).attr('data-destino')); });
  if($.fn.DataTable){
    if($('#tablaAsignacionesLicencia').length){$('#tablaAsignacionesLicencia').DataTable({order:[[0,'desc']],pageLength:10,responsive:true,autoWidth:false});}
    if($('#tablaCuposLicencia').length){$('#tablaCuposLicencia').DataTable({order:[[0,'asc']],pageLength:10,responsive:true,autoWidth:false});}
  }
})();
</script>

<?php if ($tieneClave): ?>
<script>
(function () {
  var control = $('#claveLicenciaControl');
  var campo = $('#claveLicenciaValor');
  var botonVer = $('#verClaveLicencia');
  var botonCopiar = $('#copiarClaveLicencia');
  var estado = $('#estadoClaveLicencia');
  var visible = false;
  var solicitando = false;

  function mensajeError(xhr) {
    var mensaje = xhr && xhr.responseJSON && xhr.responseJSON.mensaje
      ? xhr.responseJSON.mensaje
      : 'No se pudo consultar la clave de producto.';
    toastr.error(mensaje, 'GestActivos');
    estado.text(mensaje);
  }

  function solicitarClave() {
    if (solicitando) { return $.Deferred().reject().promise(); }
    solicitando = true;
    botonVer.add(botonCopiar).prop('disabled', true);
    estado.text('Consultando la clave de forma segura...');
    return $.ajax({
      url: control.attr('data-endpoint'),
      method: 'POST',
      dataType: 'json',
      cache: false,
      data: {
        idlicencia: control.attr('data-idlicencia'),
        csrf_token: control.attr('data-csrf')
      }
    }).then(function (respuesta) {
      if (!respuesta || respuesta.ok !== true || typeof respuesta.clave !== 'string') {
        return $.Deferred().reject({responseJSON:{mensaje:'La respuesta de la clave no es válida.'}}).promise();
      }
      return respuesta.clave;
    }).always(function () {
      solicitando = false;
      botonVer.add(botonCopiar).prop('disabled', false);
    });
  }

  function ocultarClave() {
    visible = false;
    campo.val(control.attr('data-mascara'));
    botonVer.attr({'aria-pressed':'false','aria-label':'Ver clave de producto','title':'Ver clave'})
      .find('i').attr('class','fa fa-eye');
    estado.text('Clave oculta.');
  }

  botonVer.on('click', function () {
    if (visible) { ocultarClave(); return; }
    solicitarClave().done(function (clave) {
      campo.val(clave);
      visible = true;
      botonVer.attr({'aria-pressed':'true','aria-label':'Ocultar clave de producto','title':'Ocultar clave'})
        .find('i').attr('class','fa fa-eye-slash');
      estado.text('Clave visible. Se ocultará al cambiar de pestaña.');
    }).fail(mensajeError);
  });

  function copiarTexto(texto) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(texto);
    }
    return new Promise(function (resolve, reject) {
      var temporal = $('<textarea>').val(texto).attr('readonly', true)
        .css({position:'fixed',opacity:0}).appendTo('body');
      temporal[0].select();
      var copiado = document.execCommand('copy');
      temporal.remove();
      copiado ? resolve() : reject(new Error('No se pudo copiar.'));
    });
  }

  botonCopiar.on('click', function () {
    var obtener = visible ? $.Deferred().resolve(campo.val()).promise() : solicitarClave();
    obtener.done(function (clave) {
      copiarTexto(clave).then(function () {
        toastr.success('Clave copiada al portapapeles.', 'GestActivos');
        estado.text('Clave copiada al portapapeles.');
      }).catch(function () {
        toastr.error('El navegador no permitió copiar la clave.', 'GestActivos');
      });
    }).fail(mensajeError);
  });

  document.addEventListener('visibilitychange', function () {
    if (document.hidden && visible) { ocultarClave(); }
  });
})();
</script>
<?php endif; ?>
