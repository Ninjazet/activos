// ============================================================
// GestActivos - Helper AJAX reutilizable
// ============================================================

var _ajaxLoadRequest = null;

// Carga el contenido de una URL AJAX dentro del div #datos
function ajaxLoad(url, query, extraData) {
    var data = $.extend({ query: query || '' }, extraData || {});
    var $contenedor = $('#datos');

    if (_ajaxLoadRequest && _ajaxLoadRequest.readyState !== 4) {
        _ajaxLoadRequest.abort();
    }

    $contenedor
        .attr('aria-busy', 'true')
        .html('<div class="ajax-loading" role="status"><span class="fa fa-circle-notch fa-spin" aria-hidden="true"></span><span>Cargando información...</span></div>');

    var request = $.ajax({
        url:      url,
        type:     'POST',
        dataType: 'html',
        data:     data
    })
    .done(function (resp) {
        $contenedor.html(resp);
        prepararContenidoAjax($contenedor);
    })
    .fail(function (xhr, status) {
        if (status === 'abort') { return; }
        $contenedor.html('<div class="app-empty-state app-empty-state-error" role="alert"><span class="fa fa-triangle-exclamation" aria-hidden="true"></span><strong>No se pudieron cargar los datos</strong><span>Actualiza la página o inténtalo nuevamente.</span></div>');
    })
    .always(function () {
        if (_ajaxLoadRequest === request) {
            _ajaxLoadRequest = null;
            $contenedor.attr('aria-busy', 'false');
        }
    });
    _ajaxLoadRequest = request;
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
        $modal.find('.btn-close').attr('aria-label', 'Cerrar');
    });

    $contenedor.find('.form-group > label:not([for]), .mb-3 > label:not([for])').each(function (indice) {
        var $label = $(this);
        var $control = $label.siblings('input:not([type="hidden"]), select, textarea').first();
        if (!$control.length) {
            $control = $label.closest('.form-group, .mb-3').find('input:not([type="hidden"]), select, textarea').first();
        }
        if (!$control.length) { return; }
        var controlId = $control.attr('id') || ('form-control-' + indice + '-' + Date.now());
        $control.attr('id', controlId);
        $label.attr('for', controlId);
    });

    $contenedor.find('p.lead').addClass('app-empty-state');

    $contenedor.find('.modal').each(function () {
        this.addEventListener('shown.bs.modal', function () {
            var $modal = $(this);
            var $focusTarget = $modal
                .find('[autofocus], input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])')
                .filter(':visible')
                .first();
            ($focusTarget.length ? $focusTarget : $modal.find('.btn-close').first()).trigger('focus');
        });
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
function ajaxLoadDebounced(url, query, extraData) {
    clearTimeout(_debounceTimer);
    _debounceTimer = setTimeout(function () {
        ajaxLoad(url, query, extraData);
    }, 300);
}

// Lee los filtros visibles sin acoplar el helper a un módulo específico.
function obtenerFiltrosTabla() {
    var data = {};
    $('[data-table-filters]').first().find('[data-table-filter]').each(function () {
        var name = this.name;
        if (name) {
            data[name] = $(this).val() || '';
        }
    });
    return data;
}

function obtenerBusquedaTabla() {
    return $.trim($('[data-table-filters]').first().find('[data-table-search]').val() || '');
}

function resolverDatosExtra(extraData) {
    var resolved = typeof extraData === 'function' ? extraData() : extraData;
    return $.extend({}, resolved || {}, obtenerFiltrosTabla());
}

function actualizarEstadoFiltros() {
    var $panel = $('[data-table-filters]').first();
    if (!$panel.length) { return; }

    var active = obtenerBusquedaTabla() !== '' ? 1 : 0;
    $panel.find('[data-table-filter]').each(function () {
        if (String($(this).val() || '') !== '') { active++; }
    });

    var label = active === 0 ? 'Sin filtros activos'
        : active === 1 ? '1 filtro activo'
        : active + ' filtros activos';
    $panel.find('[data-filter-active-count]').text(label);
    $panel.find('.js-clear-table-filters').prop('disabled', active === 0);
}

// Inicializa carga, búsqueda, cambios y limpieza para una tabla AJAX.
function initAjaxTableFilters(url, extraData) {
    $(function () {
        var $panel = $('[data-table-filters]').first();
        if (!$panel.length) {
            ajaxLoad(url, '', typeof extraData === 'function' ? extraData() : extraData);
            return;
        }

        function loadNow() {
            clearTimeout(_debounceTimer);
            actualizarEstadoFiltros();
            ajaxLoad(url, obtenerBusquedaTabla(), resolverDatosExtra(extraData));
        }

        $panel.off('.appTableFilters');
        $panel.on('input.appTableFilters', '[data-table-search]', function () {
            actualizarEstadoFiltros();
            ajaxLoadDebounced(url, obtenerBusquedaTabla(), resolverDatosExtra(extraData));
        });
        $panel.on('change.appTableFilters', '[data-table-filter]', loadNow);
        $panel.on('click.appTableFilters', '.js-clear-table-filters', function () {
            $panel.find('[data-table-search], [data-table-filter]').val('');
            loadNow();
            $panel.find('[data-table-search]').trigger('focus');
        });

        loadNow();
    });
}

// Construye la misma consulta para descargas y vistas previas de reportes.
function tableFilterQueryString(extraData) {
    return $.param($.extend(
        { buscar: obtenerBusquedaTabla() },
        obtenerFiltrosTabla(),
        extraData || {}
    ));
}
