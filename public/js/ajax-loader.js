// ============================================================
// GestActivos - Helper AJAX reutilizable
// ============================================================

// Carga el contenido de una URL AJAX dentro del div #datos
function ajaxLoad(url, query) {
    $.ajax({
        url:      url,
        type:     'POST',
        dataType: 'html',
        data:     { query: query || '' }
    })
    .done(function (resp) { $('#datos').html(resp); })
    .fail(function ()     { $('#datos').html('<p class="text-danger">Error al cargar datos.</p>'); });
}

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
