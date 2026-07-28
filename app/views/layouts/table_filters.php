<?php
// Componente compartido para filtros AJAX. La página define los campos y opciones.
if (!function_exists('renderTableFilters')) {
    function renderTableFilters(array $config): void
    {
        $escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $searchLabel = $config['search_label'] ?? 'Buscar';
        $searchPlaceholder = $config['search_placeholder'] ?? 'Buscar...';
        $tableId = $config['table_id'] ?? '';
        $filters = is_array($config['filters'] ?? null) ? $config['filters'] : [];
        ?>
        <section class="app-filter-panel" data-table-filters aria-label="Filtros de la tabla">
            <div class="app-filter-heading">
                <div>
                    <strong><i class="fa fa-filter" aria-hidden="true"></i> Filtros</strong>
                    <span class="app-filter-count" data-filter-active-count aria-live="polite">Sin filtros activos</span>
                </div>
                <button type="button" class="btn btn-light btn-sm js-clear-table-filters" disabled>
                    <i class="fa fa-rotate-left" aria-hidden="true"></i> Limpiar
                </button>
            </div>
            <div class="app-filter-grid">
                <div class="app-filter-field app-filter-search">
                    <label for="buscar"><?= $escape($searchLabel) ?></label>
                    <div class="input-group">
                        <span class="input-group-text" aria-hidden="true"><i class="fa fa-search"></i></span>
                        <input type="search" id="buscar" class="form-control" data-table-search
                               placeholder="<?= $escape($searchPlaceholder) ?>" autocomplete="off"
                               <?= $tableId !== '' ? 'aria-controls="' . $escape($tableId) . '"' : '' ?>>
                    </div>
                </div>
                <?php foreach ($filters as $index => $filter): ?>
                    <?php
                    $name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($filter['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $id = 'filtro_' . $name;
                    $label = $filter['label'] ?? ucfirst(str_replace('_', ' ', $name));
                    $type = ($filter['type'] ?? 'select') === 'date' ? 'date' : 'select';
                    $value = (string)($filter['value'] ?? '');
                    ?>
                    <div class="app-filter-field">
                        <label for="<?= $escape($id) ?>"><?= $escape($label) ?></label>
                        <?php if ($type === 'date'): ?>
                            <input type="date" id="<?= $escape($id) ?>" name="<?= $escape($name) ?>"
                                   class="form-control" data-table-filter value="<?= $escape($value) ?>">
                        <?php else: ?>
                            <select id="<?= $escape($id) ?>" name="<?= $escape($name) ?>"
                                    class="form-select" data-table-filter>
                                <option value=""><?= $escape($filter['empty_label'] ?? 'Todos') ?></option>
                                <?php foreach (($filter['options'] ?? []) as $optionValue => $optionLabel): ?>
                                    <option value="<?= $escape($optionValue) ?>" <?= (string)$optionValue === $value ? 'selected' : '' ?>>
                                        <?= $escape($optionLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
