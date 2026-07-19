(function ($) {
    'use strict';

    function notificar(tipo, mensaje) {
        if (window.toastr && typeof window.toastr[tipo] === 'function') {
            window.toastr[tipo](mensaje, 'GestActivos');
            return;
        }
        if (tipo === 'error') {
            window.alert(mensaje);
        }
    }

    function ordenarOpciones($select) {
        var valorActual = $select.val();
        var opciones = $select.find('option').get();
        opciones.sort(function (a, b) {
            return a.text.localeCompare(b.text, 'es', { sensitivity: 'base' });
        });
        $select.empty().append(opciones).val(valorActual);
    }

    function actualizarSelectores(tipo, id, nombre, selectorObjetivo) {
        var valor = String(id);
        $('select[data-catalogo-select="' + tipo + '"]').each(function () {
            var $select = $(this);
            var $opcion = $select.find('option').filter(function () {
                return String(this.value) === valor;
            }).first();

            if ($opcion.length) {
                $opcion.text(nombre).prop('disabled', false);
            } else {
                $select.append(new Option(nombre, valor, false, false));
            }
            ordenarOpciones($select);
        });

        var $objetivo = $(selectorObjetivo);
        if ($objetivo.length) {
            $objetivo.val(valor).trigger('change');
        }
    }

    function guardarCatalogo($panel) {
        if ($panel.data('guardando')) {
            return;
        }

        var $input = $panel.find('.js-catalogo-nombre');
        var $boton = $panel.find('.js-catalogo-guardar');
        var $error = $panel.find('.catalogo-contextual-error');
        var nombre = $.trim($input.val());

        $error.text('');
        if (!nombre) {
            $error.text('Escribe un nombre antes de guardar.');
            $input.focus();
            return;
        }

        $panel.data('guardando', true);
        $boton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando');

        $.ajax({
            url: $panel.data('endpoint'),
            method: 'POST',
            dataType: 'json',
            data: {
                tipo: $panel.data('tipo'),
                nombre: nombre,
                csrf_token: $panel.data('csrf')
            }
        }).done(function (respuesta) {
            if (!respuesta || !respuesta.success) {
                $error.text((respuesta && respuesta.message) || 'No se pudo guardar el registro.');
                return;
            }

            actualizarSelectores(
                respuesta.tipo,
                respuesta.id,
                respuesta.nombre,
                $panel.data('select')
            );
            $input.val('');
            $panel.slideUp(140);
            notificar('success', respuesta.message);
        }).fail(function (xhr) {
            var respuesta = xhr.responseJSON || {};
            var mensaje = respuesta.message || 'No se pudo conectar con el servidor.';
            $error.text(mensaje);
            notificar('error', mensaje);
        }).always(function () {
            $panel.data('guardando', false);
            $boton.prop('disabled', false).html('<i class="fa fa-check"></i> Guardar');
        });
    }

    $(document).on('click', '.js-catalogo-toggle', function () {
        var $panel = $($(this).data('target'));
        $('.catalogo-contextual-panel:visible').not($panel).slideUp(120);
        $panel.slideToggle(140, function () {
            if ($panel.is(':visible')) {
                $panel.find('.js-catalogo-nombre').focus();
            }
        });
    });

    $(document).on('click', '.js-catalogo-cancelar', function () {
        var $panel = $(this).closest('.catalogo-contextual-panel');
        $panel.find('.js-catalogo-nombre').val('');
        $panel.find('.catalogo-contextual-error').text('');
        $panel.slideUp(120);
    });

    $(document).on('click', '.js-catalogo-guardar', function () {
        guardarCatalogo($(this).closest('.catalogo-contextual-panel'));
    });

    $(document).on('keydown', '.js-catalogo-nombre', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            guardarCatalogo($(this).closest('.catalogo-contextual-panel'));
        }
        if (event.key === 'Escape') {
            $(this).closest('.catalogo-contextual-panel').slideUp(120);
        }
    });
})(jQuery);
