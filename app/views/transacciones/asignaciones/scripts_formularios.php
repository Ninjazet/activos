<script>
function modalEdit(evento) {
    var fila = obtenerFilaAsignacion(evento);
    $('#idasignacion').val(fila.find('td').eq(0).text());
    $('#empleadoAct').val(String(fila.data('idempleado')));
    $('#equipoAct').val(String(fila.data('idequipo')));
    $('#editarCondicionEntrega').val(fila.attr('data-condicion-entrega'));
    $('#editarEntregaCargador').prop('checked', fila.data('entrega-cargador') == 1);
    $('#editarEntregaMaletin').prop('checked', fila.data('entrega-maletin') == 1);
    $('#editarEntregaOtros').val(fila.attr('data-entrega-otros'));
    $('#editarObservacionesEntrega').val(fila.attr('data-observaciones-entrega'));
}

function modalFirmarEntrega(evento) {
    var fila = obtenerFilaAsignacion(evento);
    $('#idasignacionFirma').val(fila.find('td').eq(0).text());
    $('#lblEmpleadoFirma').text(fila.find('td').eq(1).text());
    $('#lblEquipoFirma').text(fila.find('td').eq(2).text());
    limpiarFirmaCanvas('canvasFirmaEntrega', 'avisoFirmaEntrega');
}

function modalDevolver(evento) {
    var fila = obtenerFilaAsignacion(evento);
    var accesorios = [];
    if (fila.data('entrega-cargador') == 1) { accesorios.push('Cargador'); }
    if (fila.data('entrega-maletin') == 1) { accesorios.push('Maletín'); }
    if (fila.attr('data-entrega-otros')) { accesorios.push(fila.attr('data-entrega-otros')); }

    $('#idasignacionDevolucion').val(fila.find('td').eq(0).text());
    $('#lblEmpleadoDevolucion').text(fila.find('td').eq(1).text());
    $('#lblEquipoDevolucion').text(fila.find('td').eq(2).text());
    $('#lblChecklistEntrega').text(accesorios.length ? accesorios.join(', ') : 'Sin accesorios');
    $('#condicionDevolucion').val('Bueno');
    $('#devolucionCargador').prop('checked', fila.data('entrega-cargador') == 1);
    $('#devolucionMaletin').prop('checked', fila.data('entrega-maletin') == 1);
    $('#devolucionOtros').val(fila.attr('data-entrega-otros') || '');
    $('#observacionesDevolucion').val('');
    limpiarFirmaCanvas('canvasFirmaDevolucion', 'avisoFirmaDevolucion');
}

function limpiarFirmaCanvas(canvasId, avisoId) {
    var canvas = document.getElementById(canvasId);
    if (!canvas || !canvas._firmaContexto) { return; }
    canvas._firmaContexto.fillStyle = '#ffffff';
    canvas._firmaContexto.fillRect(0, 0, canvas.width, canvas.height);
    canvas._firmaConTrazo = false;
    canvas._firmaCursor = { x: canvas.width / 2, y: canvas.height / 2 };
    $('#' + avisoId).text('');
}

function configurarFirmaCanvas(config) {
    var canvas = document.getElementById(config.canvasId);
    var ctx = canvas.getContext('2d');
    var dibujando = false;
    canvas._firmaContexto = ctx;
    canvas._firmaConTrazo = false;
    canvas.style.touchAction = 'none';
    canvas.style.cursor = 'crosshair';
    limpiarFirmaCanvas(config.canvasId, config.avisoId);

    function posicion(evento) {
        var rect = canvas.getBoundingClientRect();
        return {
            x: (evento.clientX - rect.left) * (canvas.width / rect.width),
            y: (evento.clientY - rect.top) * (canvas.height / rect.height)
        };
    }
    function trazarHasta(punto) {
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1e1e2d';
        ctx.lineTo(punto.x, punto.y);
        ctx.stroke();
        canvas._firmaCursor = punto;
        canvas._firmaConTrazo = true;
    }
    canvas.onpointerdown = function (evento) {
        evento.preventDefault();
        dibujando = true;
        canvas._firmaConTrazo = true;
        canvas.setPointerCapture(evento.pointerId);
        var punto = posicion(evento);
        canvas._firmaCursor = punto;
        ctx.beginPath();
        ctx.moveTo(punto.x, punto.y);
    };
    canvas.onpointermove = function (evento) {
        if (!dibujando) { return; }
        evento.preventDefault();
        var punto = posicion(evento);
        trazarHasta(punto);
    };
    canvas.onpointerup = canvas.onpointercancel = function () { dibujando = false; };
    canvas.onkeydown = function (evento) {
        var movimientos = {
            ArrowLeft: [-1, 0], ArrowRight: [1, 0],
            ArrowUp: [0, -1], ArrowDown: [0, 1]
        };
        if (!movimientos[evento.key]) { return; }
        evento.preventDefault();
        var paso = evento.shiftKey ? 8 : 3;
        var actual = canvas._firmaCursor || { x: canvas.width / 2, y: canvas.height / 2 };
        var siguiente = {
            x: Math.max(0, Math.min(canvas.width, actual.x + movimientos[evento.key][0] * paso)),
            y: Math.max(0, Math.min(canvas.height, actual.y + movimientos[evento.key][1] * paso))
        };
        ctx.beginPath();
        ctx.moveTo(actual.x, actual.y);
        trazarHasta(siguiente);
    };

    $('#' + config.limpiarId).off('click.firma').on('click.firma', function () {
        limpiarFirmaCanvas(config.canvasId, config.avisoId);
    });
    $('#' + config.formId).off('submit.firma').on('submit.firma', function (evento) {
        if (!canvas._firmaConTrazo) {
            evento.preventDefault();
            $('#' + config.avisoId).text('Debe dibujar la firma antes de continuar.');
            return;
        }
        $('#' + config.inputId).val(canvas.toDataURL('image/jpeg', 0.9));
        if (typeof config.alEnviar === 'function') { config.alEnviar(); }
    });
}

$(function () {
    configurarFirmaCanvas({
        canvasId: 'canvasFirmaEntrega', limpiarId: 'btnLimpiarFirmaEntrega',
        formId: 'formFirmaEntrega', inputId: 'firmaEntregaInput', avisoId: 'avisoFirmaEntrega'
    });
    configurarFirmaCanvas({
        canvasId: 'canvasFirmaDevolucion', limpiarId: 'btnLimpiarFirmaDevolucion',
        formId: 'formDevolucion', inputId: 'firmaDevolucionInput', avisoId: 'avisoFirmaDevolucion',
        alEnviar: function () {
            setTimeout(function () {
                var modalDevolucion = document.getElementById('devolucionModal');
                bootstrap.Modal.getOrCreateInstance(modalDevolucion).hide();
                ajaxLoad('<?= BASE_URL ?>/app/ajax/transacciones/asignarequipo.php');
            }, 1400);
        }
    });
    <?php if ($abrirModalNuevo): ?>
    setTimeout(function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('newModal')).show();
    }, 80);
    <?php endif; ?>
});
</script>
