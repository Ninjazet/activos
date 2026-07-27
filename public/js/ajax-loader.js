// ============================================================
// GestActivos - Helper AJAX reutilizable
// ============================================================

// Carga el contenido de una URL AJAX dentro del div #datos
function ajaxLoad(url, query, extraData) {
    var data = $.extend({ query: query || '' }, extraData || {});
    var $contenedor = $('#datos');

    $contenedor
        .attr('aria-busy', 'true')
        .html('<div class="ajax-loading" role="status"><span class="fa fa-circle-notch fa-spin" aria-hidden="true"></span><span>Cargando información...</span></div>');

    $.ajax({
        url:      url,
        type:     'POST',
        dataType: 'html',
        data:     data
    })
    .done(function (resp) {
        $contenedor.html(resp);
        prepararContenidoAjax($contenedor);
    })
    .fail(function () {
        $contenedor.html('<div class="app-empty-state app-empty-state-error" role="alert"><span class="fa fa-triangle-exclamation" aria-hidden="true"></span><strong>No se pudieron cargar los datos</strong><span>Actualiza la página o inténtalo nuevamente.</span></div>');
    })
    .always(function () {
        $contenedor.attr('aria-busy', 'false');
    });
}

// Aplica mejoras progresivas sin cambiar el HTML ni la lógica de cada módulo.
function prepararContenidoAjax($contenedor) {
    var tituloPagina = $.trim($('#topbar-title h1').text()) || 'resultados';

    $contenedor.find('table').each(function () {
        var $tabla = $(this);
        $tabla.attr('aria-label', 'Tabla de ' + tituloPagina);
        $tabla.find('thead th').attr('scope', 'col');

        if (!$tabla.closest('.table-responsive').length) {
            $tabla.wrap('<div class="table-responsive app-table-responsive" role="region" tabindex="0" aria-label="Tabla de ' + tituloPagina + '. Desplaza horizontalmente para ver todas las columnas"></div>');
        } else {
            $tabla.closest('.table-responsive').first()
                .addClass('app-table-responsive')
                .attr({
                    role: 'region',
                    tabindex: '0',
                    'aria-label': 'Tabla de ' + tituloPagina + '. Desplaza horizontalmente para ver todas las columnas'
                });
        }
    });

    $contenedor.find('a[title], button[title]').each(function () {
        if (!this.getAttribute('aria-label')) {
            this.setAttribute('aria-label', this.getAttribute('title'));
        }
    });

    $contenedor.find('a[target="_blank"]').attr('rel', 'noopener');
    $contenedor.find('img:not([alt])').attr('alt', '');

    $contenedor.find('.modal').each(function (indice) {
        var $modal = $(this);
        var $titulo = $modal.find('.modal-title').first();
        if ($titulo.length) {
            var tituloId = $titulo.attr('id') || ('modal-title-' + ($modal.attr('id') || indice));
            $titulo.attr('id', tituloId);
            $modal.attr('aria-labelledby', tituloId);
        }
        $modal.attr('aria-hidden', 'true');
        $modal.find('.close').attr('aria-label', 'Cerrar').find('span').attr('aria-hidden', 'true');
    });

    $contenedor.find('.form-group > label:not([for])').each(function (indice) {
        var $label = $(this);
        var $control = $label.siblings('input:not([type="hidden"]), select, textarea').first();
        if (!$control.length) {
            $control = $label.closest('.form-group').find('input:not([type="hidden"]), select, textarea').first();
        }
        if (!$control.length) { return; }
        var controlId = $control.attr('id') || ('form-control-' + indice + '-' + Date.now());
        $control.attr('id', controlId);
        $label.attr('for', controlId);
    });

    $contenedor.find('p.lead').addClass('app-empty-state');

    $contenedor.find('.modal').on('shown.bs.modal', function () {
        var $focusTarget = $(this).find('[autofocus], input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])').filter(':visible').first();
        ($focusTarget.length ? $focusTarget : $(this).find('.close').first()).trigger('focus');
    });
}

$(function () {
    $('#buscar').each(function () {
        if (!this.getAttribute('aria-label')) {
            this.setAttribute('aria-label', this.getAttribute('placeholder') || 'Buscar');
        }
        this.setAttribute('autocomplete', 'off');
    });
    $('#datos').attr('aria-busy', 'false');
});

// Igual que ajaxLoad pero con 300ms de debounce.
// Usar siempre en eventos "keyup" para no enviar una petición
// por cada tecla pulsada (evita sobrecarga innecesaria del servidor).
var _debounceTimer = null;
function ajaxLoadDebounced(url, query) {
    clearTimeout(_debounceTimer);
    _debounceTimer = setTimeout(function () {
        ajaxLoad(url, query);
    }, 300);
}
