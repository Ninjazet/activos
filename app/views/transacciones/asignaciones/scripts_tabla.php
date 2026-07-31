<script>
var tablaAsignacionesActivas = null;

function obtenerFilaAsignacion(evento) {
    var fila = $(evento.target).closest('tr');
    if (fila.hasClass('child') || fila.hasClass('assignment-mobile-child')) {
        fila = fila.prevAll('tr').not('.child, .assignment-mobile-child').first();
    }
    return fila;
}

function crearAccionesAsignacionMovil(fila) {
    var acciones = $(fila).find('.assignment-actions').first().clone();
    var botones = $('<div class="assignment-mobile-actions-buttons"></div>');

    acciones.find('a, button').each(function () {
        var control = $(this);
        var etiqueta = control.attr('aria-label') || control.attr('title') || 'Acción';
        control.append($('<span class="assignment-mobile-action-label"></span>').text(etiqueta));
        botones.append(control);
    });

    return $('<div class="assignment-mobile-actions-panel"></div>')
        .append('<strong class="assignment-mobile-actions-title">Opciones de la asignación</strong>')
        .append(botones);
}

$(function () {
    var consultaMovil = window.matchMedia('(max-width: 767px)');
    tablaAsignacionesActivas = $('#datosE').DataTable({
        dom: 'lrtip',
        order: [[0, 'desc']],
        columnDefs: [
            { targets: 7, orderable: false }
        ]
    });

    function aplicarModoAsignacionMovil() {
        var filas = $('#datosE tbody tr').not('.child, .assignment-mobile-child');

        if (consultaMovil.matches) {
            filas.attr({
                tabindex: '0',
                'aria-describedby': 'assignmentMobileHelp'
            }).each(function () {
                $(this).attr(
                    'aria-expanded',
                    tablaAsignacionesActivas.row(this).child.isShown() ? 'true' : 'false'
                );
            });
            return;
        }

        tablaAsignacionesActivas.rows().every(function () {
            if (this.child.isShown()) {
                this.child.hide();
            }
            $(this.node()).removeClass('assignment-mobile-open');
        });
        filas.removeAttr('tabindex aria-expanded aria-describedby');
    }

    function alternarOpcionesMoviles(fila) {
        if (!consultaMovil.matches) { return; }

        var registro = tablaAsignacionesActivas.row(fila);
        if (registro.child.isShown()) {
            registro.child.hide();
            $(fila).removeClass('assignment-mobile-open').attr('aria-expanded', 'false');
            return;
        }

        tablaAsignacionesActivas.rows().every(function () {
            if (this.child.isShown()) {
                this.child.hide();
                $(this.node()).removeClass('assignment-mobile-open').attr('aria-expanded', 'false');
            }
        });

        registro.child(crearAccionesAsignacionMovil(fila), 'assignment-mobile-child').show();
        $(fila).addClass('assignment-mobile-open').attr('aria-expanded', 'true');
    }

    $('#datosE tbody')
        .off('.assignmentMobile')
        .on('click.assignmentMobile', 'tr:not(.child):not(.assignment-mobile-child)', function (evento) {
            if ($(evento.target).closest('a, button, input, select, textarea, label').length) { return; }
            alternarOpcionesMoviles(this);
        })
        .on('keydown.assignmentMobile', 'tr:not(.child):not(.assignment-mobile-child)', function (evento) {
            if (evento.key !== 'Enter' && evento.key !== ' ') { return; }
            evento.preventDefault();
            alternarOpcionesMoviles(this);
        });

    tablaAsignacionesActivas.on('draw.dt.assignmentMobile', aplicarModoAsignacionMovil);
    $(window)
        .off('resize.assignmentMobile')
        .on('resize.assignmentMobile', function () {
            aplicarModoAsignacionMovil();
            tablaAsignacionesActivas.columns.adjust();
        });

    aplicarModoAsignacionMovil();
    tablaAsignacionesActivas.columns.adjust();
});
</script>
