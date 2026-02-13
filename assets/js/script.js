/**
 * Compressao de imagem client-side antes do upload.
 * Ajuste os valores abaixo conforme necessidade de qualidade vs tamanho.
 */
var CCS_IMAGE_COMPRESS = {
    enabled: true,
    maxWidth: 1920,
    maxHeight: 1920,
    quality: 0.80,
    mimeType: 'image/jpeg',
};

function ccsCompressImage(file) {
    return new Promise(function (resolve) {
        if (!CCS_IMAGE_COMPRESS.enabled || !file || !/^image\//.test(file.type)) {
            resolve(file);
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            var img = new Image();
            img.onload = function () {
                var maxW = CCS_IMAGE_COMPRESS.maxWidth;
                var maxH = CCS_IMAGE_COMPRESS.maxHeight;
                var w = img.width;
                var h = img.height;

                if (w > maxW || h > maxH) {
                    var ratio = Math.min(maxW / w, maxH / h);
                    w = Math.round(w * ratio);
                    h = Math.round(h * ratio);
                }

                var canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, w, h);

                canvas.toBlob(function (blob) {
                    if (!blob || blob.size >= file.size) {
                        resolve(file);
                        return;
                    }
                    var compressed = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                        type: CCS_IMAGE_COMPRESS.mimeType,
                        lastModified: file.lastModified,
                    });
                    resolve(compressed);
                }, CCS_IMAGE_COMPRESS.mimeType, CCS_IMAGE_COMPRESS.quality);
            };
            img.onerror = function () { resolve(file); };
            img.src = e.target.result;
        };
        reader.onerror = function () { resolve(file); };
        reader.readAsDataURL(file);
    });
}

function ccsCompressImages(fileList) {
    var files = Array.from(fileList || []);
    return Promise.all(files.map(function (f) { return ccsCompressImage(f); }));
}

function filterTable() {
    const input = document.getElementById("searchInput");
    if (!input) return; // Exit if search input doesn't exist
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll("#computerTableBody tr:not(.no-results-row)");
    const noResultsRow = document.querySelector(".no-results-row");

    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const searchMeta = row.getAttribute("data-search-terms") || "";
        const combinedText = text + " " + searchMeta;

        const isVisible = combinedText.includes(filter);
        row.style.display = isVisible ? "" : "none";

        if (isVisible) visibleCount++;
    });

    const countElement = document.getElementById("visibleCount");
    if (countElement) {
        countElement.textContent = visibleCount;
    }

    // Handle "No results" visual feedback if you want (optional, but good UX)
    // If we have a dedicated no-results row from PHP, it might show initially if list is empty.
    // If list is NOT empty but filtered to 0, we might want to show a message, but for now just updating count is enough.
}

function getCurrentModule() {
    if (typeof window.ccsCurrentModule === 'string' && window.ccsCurrentModule.trim() !== '') {
        return window.ccsCurrentModule.trim().toLowerCase();
    }

    try {
        const params = new URLSearchParams(window.location.search);
        const module = (params.get('module') || '').trim().toLowerCase();
        if (module) return module;
    } catch (error) {
        // Ignore parsing issues and fallback below.
    }

    return 'computers';
}

// Run on load to handle browser persistence
document.addEventListener('DOMContentLoaded', filterTable);

function getFilterPanelStateKey() {
    return 'filterPanelOpen_' + getCurrentModule();
}

// Toggle filter panel visibility
function toggleFilters() {
    const panel = document.getElementById('filterPanel');
    if (!panel) return;

    const isHidden = panel.classList.contains('hidden');

    if (isHidden) {
        panel.classList.remove('hidden');
        // Store state in localStorage
        localStorage.setItem(getFilterPanelStateKey(), 'true');
    } else {
        panel.classList.add('hidden');
        localStorage.setItem(getFilterPanelStateKey(), 'false');
    }
}

// Restore filter panel state on page load
document.addEventListener('DOMContentLoaded', function () {
    const panel = document.getElementById('filterPanel');
    if (!panel) return;

    const isOpen = localStorage.getItem(getFilterPanelStateKey()) === 'true';

    if (isOpen) {
        panel.classList.remove('hidden');
    }
});

function normalizeReportValue(value) {
    return (value || '').toString().trim().toLowerCase();
}

function getReportsFiltersStateKey() {
    const context = ((window.ccsReportContext || 'list') + '').toLowerCase();
    const module = getCurrentModule();
    return context === 'reports'
        ? 'ccs_reports_filters_' + module + '_reports'
        : 'ccs_reports_filters_' + module + '_list';
}

function saveReportsFiltersState() {
    const tableBody = document.getElementById('reportsTableBody');
    if (!tableBody) return;

    const filterControls = document.querySelectorAll('[data-report-filter]');
    const globalInput = document.getElementById('reportGlobalSearch');
    const filters = {};

    filterControls.forEach((control) => {
        const column = control.getAttribute('data-report-filter');
        if (!column) return;
        filters[column] = control.value || '';
    });

    const state = {
        global: globalInput ? (globalInput.value || '') : '',
        filters: filters,
    };

    try {
        sessionStorage.setItem(getReportsFiltersStateKey(), JSON.stringify(state));
    } catch (error) {
        // Ignore storage quota/privacy mode errors.
    }
}

function restoreReportsFiltersState(filterControls, globalInput) {
    let rawState = null;
    try {
        rawState = sessionStorage.getItem(getReportsFiltersStateKey());
    } catch (error) {
        rawState = null;
    }

    if (!rawState) return;

    let state = null;
    try {
        state = JSON.parse(rawState);
    } catch (error) {
        state = null;
    }

    if (!state || typeof state !== 'object') return;

    if (globalInput && typeof state.global === 'string') {
        globalInput.value = state.global;
    }

    const savedFilters = state.filters && typeof state.filters === 'object' ? state.filters : null;
    if (!savedFilters) return;

    filterControls.forEach((control) => {
        const column = control.getAttribute('data-report-filter');
        if (!column) return;

        if (Object.prototype.hasOwnProperty.call(savedFilters, column)) {
            control.value = savedFilters[column] || '';
        }
    });
}

function rowMatchesReportFilter(row, control) {
    const column = control.getAttribute('data-report-filter');
    if (!column) return true;

    const filterType = control.getAttribute('data-report-filter-type') || 'text';
    const filterValue = normalizeReportValue(control.value);

    if (!filterValue) return true;

    const rowValue = normalizeReportValue(row.getAttribute('data-col-' + column));

    if (filterType === 'date') {
        return rowValue.startsWith(filterValue);
    }

    if (filterType === 'select') {
        if (filterValue === '__empty__') {
            return rowValue === '';
        }

        if (filterValue === '__not_empty__') {
            return rowValue !== '';
        }

        return rowValue === filterValue;
    }

    return rowValue.includes(filterValue);
}

function updateReportsFilterHighlights(filterControls) {
    const controls = filterControls || document.querySelectorAll('[data-report-filter]');

    controls.forEach((control) => {
        const column = control.getAttribute('data-report-filter');
        if (!column) return;

        const isActive = normalizeReportValue(control.value) !== '';
        const headerCell = document.querySelector('[data-report-header-cell="' + column + '"]');
        const filterCell = document.querySelector('[data-report-filter-cell="' + column + '"]');

        if (headerCell) {
            headerCell.classList.toggle('bg-indigo-100', isActive);
            headerCell.classList.toggle('text-indigo-700', isActive);
            headerCell.classList.toggle('text-slate-600', !isActive);
        }

        if (filterCell) {
            filterCell.classList.toggle('bg-indigo-50', isActive);
        }
    });
}

function clearReportsFilterHighlights() {
    document.querySelectorAll('[data-report-header-cell]').forEach((headerCell) => {
        headerCell.classList.remove('bg-indigo-100', 'text-indigo-700');
        headerCell.classList.add('text-slate-600');
    });

    document.querySelectorAll('[data-report-filter-cell]').forEach((filterCell) => {
        filterCell.classList.remove('bg-indigo-50');
    });
}

function applyReportsFilters() {
    const tableBody = document.getElementById('reportsTableBody');
    if (!tableBody) return;

    const rows = tableBody.querySelectorAll('.report-row');
    const globalInput = document.getElementById('reportGlobalSearch');
    const globalQuery = normalizeReportValue(globalInput ? globalInput.value : '');
    const filterControls = document.querySelectorAll('[data-report-filter]');
    const noResultsRow = document.getElementById('reportsNoResults');
    const visibleCountElement = document.getElementById('reportVisibleCount');

    let visibleCount = 0;

    rows.forEach((row) => {
        const rowSearchValue = normalizeReportValue(row.getAttribute('data-report-search'));
        const matchesGlobal = !globalQuery || rowSearchValue.includes(globalQuery);

        if (!matchesGlobal) {
            row.style.display = 'none';
            return;
        }

        for (const control of filterControls) {
            if (!rowMatchesReportFilter(row, control)) {
                row.style.display = 'none';
                return;
            }
        }

        row.style.display = '';
        visibleCount++;
    });

    if (visibleCountElement) {
        visibleCountElement.textContent = visibleCount;
    }

    if (noResultsRow) {
        if (visibleCount === 0) {
            noResultsRow.classList.remove('hidden');
        } else {
            noResultsRow.classList.add('hidden');
        }
    }

    updateReportsFilterHighlights(filterControls);
    saveReportsFiltersState();
}

function initReportsFilters() {
    const tableBody = document.getElementById('reportsTableBody');
    if (!tableBody) return;

    const filterControls = document.querySelectorAll('[data-report-filter]');
    const globalInput = document.getElementById('reportGlobalSearch');
    const clearButton = document.getElementById('clearReportFilters');

    filterControls.forEach((control) => {
        const eventName = control.tagName === 'SELECT' ? 'change' : 'input';
        control.addEventListener(eventName, applyReportsFilters);
    });

    if (globalInput) {
        globalInput.addEventListener('input', applyReportsFilters);
    }

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            if (globalInput) {
                globalInput.value = '';
            }

            filterControls.forEach((control) => {
                control.value = '';
            });

            clearReportsFilterHighlights();
            applyReportsFilters();
        });
    }

    restoreReportsFiltersState(filterControls, globalInput);
    applyReportsFilters();

    var preloadStyle = document.getElementById('ccsPreloadHide');
    if (preloadStyle) preloadStyle.remove();
}

document.addEventListener('DOMContentLoaded', initReportsFilters);

function initReportsPhotoLightbox() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-report-photo-url]');
        if (!trigger) return;

        event.preventDefault();

        const photoUrl = (trigger.getAttribute('data-report-photo-url') || '').trim();
        const photosAttr = (trigger.getAttribute('data-report-photos') || '').trim();

        let photos = [];

        if (photosAttr) {
            try {
                const parsed = JSON.parse(photosAttr);
                if (Array.isArray(parsed)) {
                    photos = parsed
                        .map((item) => (item || '').toString().trim())
                        .filter((item) => item.length > 0);
                }
            } catch (error) {
                photos = [];
            }
        }

        if (photos.length === 0 && photoUrl) {
            photos = [photoUrl];
        }

        if (photos.length === 0) return;

        let startIndex = parseInt(trigger.getAttribute('data-report-photo-index') || '0', 10);
        if (Number.isNaN(startIndex) || startIndex < 0 || startIndex >= photos.length) {
            startIndex = 0;
        }

        if (typeof openLightbox === 'function') {
            openLightbox(photos, startIndex);
            return;
        }

        window.open(photos[startIndex], '_blank', 'noopener,noreferrer');
    });
}

document.addEventListener('DOMContentLoaded', initReportsPhotoLightbox);

function cloneReportTablePrefs(obj) {
    return JSON.parse(JSON.stringify(obj || {}));
}

function getDefaultReportTablePrefs(config) {
    const visibility = {};
    (config.columns || []).forEach((column) => {
        visibility[column] = true;
    });

    return {
        columns_order: [...(config.columns || [])],
        columns_visibility: visibility,
        density: 'normal',
        zebra: false,
    };
}

function sanitizeReportTablePrefs(config, incomingPrefs) {
    const defaults = getDefaultReportTablePrefs(config);
    const prefs = incomingPrefs && typeof incomingPrefs === 'object' ? incomingPrefs : {};
    const columns = config.columns || [];

    const incomingOrder = Array.isArray(prefs.columns_order) ? prefs.columns_order : [];
    const ordered = [];
    incomingOrder.forEach((column) => {
        if (columns.includes(column) && !ordered.includes(column)) {
            ordered.push(column);
        }
    });
    columns.forEach((column) => {
        if (!ordered.includes(column)) {
            ordered.push(column);
        }
    });
    defaults.columns_order = ordered;

    const incomingVisibility = prefs.columns_visibility && typeof prefs.columns_visibility === 'object'
        ? prefs.columns_visibility
        : {};
    columns.forEach((column) => {
        if (Object.prototype.hasOwnProperty.call(incomingVisibility, column)) {
            defaults.columns_visibility[column] = !!incomingVisibility[column];
        }
    });

    if (prefs.density === 'compact' || prefs.density === 'normal') {
        defaults.density = prefs.density;
    }

    defaults.zebra = !!prefs.zebra;
    return defaults;
}

function reorderReportRowByAttr(row, attrName, orderedColumns) {
    if (!row) return;
    const map = {};
    row.querySelectorAll('[' + attrName + ']').forEach((cell) => {
        map[cell.getAttribute(attrName)] = cell;
    });

    orderedColumns.forEach((column) => {
        if (map[column]) {
            row.appendChild(map[column]);
        }
    });
}

function applyReportTablePrefs(config, prefs) {
    const table = document.getElementById('reportsTable');
    if (!table) return;

    const colgroup = table.querySelector('colgroup');
    const headerRow = table.querySelector('thead tr:first-child');
    const filterRow = table.querySelector('thead tr:nth-child(2)');
    const bodyRows = document.querySelectorAll('#reportsTableBody tr.report-row');

    if (colgroup) {
        reorderReportRowByAttr(colgroup, 'data-report-col', prefs.columns_order);
    }
    reorderReportRowByAttr(headerRow, 'data-report-header-cell', prefs.columns_order);
    reorderReportRowByAttr(filterRow, 'data-report-filter-cell', prefs.columns_order);
    bodyRows.forEach((row) => reorderReportRowByAttr(row, 'data-report-cell', prefs.columns_order));

    let visibleColumnsCount = 0;
    let filtersChanged = false;

    (config.columns || []).forEach((column) => {
        const isVisible = prefs.columns_visibility[column] !== false;
        if (isVisible) visibleColumnsCount++;

        const headerCell = table.querySelector('[data-report-header-cell="' + column + '"]');
        const filterCell = table.querySelector('[data-report-filter-cell="' + column + '"]');
        const col = table.querySelector('col[data-report-col="' + column + '"]');
        const bodyCells = table.querySelectorAll('[data-report-cell="' + column + '"]');
        const control = table.querySelector('[data-report-filter="' + column + '"]');

        const displayValue = isVisible ? '' : 'none';

        if (headerCell) headerCell.style.display = displayValue;
        if (filterCell) filterCell.style.display = displayValue;
        if (col) col.style.display = displayValue;
        bodyCells.forEach((cell) => {
            cell.style.display = displayValue;
        });

        if (!isVisible && control && control.value !== '') {
            control.value = '';
            filtersChanged = true;
        }
    });

    const noResults = document.querySelector('#reportsNoResults td');
    if (noResults) {
        noResults.setAttribute('colspan', String(Math.max(visibleColumnsCount, 1)));
    }

    table.classList.toggle('report-density-compact', prefs.density === 'compact');
    table.classList.toggle('report-zebra-enabled', !!prefs.zebra);

    if (filtersChanged) {
        applyReportsFilters();
    } else {
        updateReportsFilterHighlights();
    }
}

function buildReportTableColumnsList(config, prefs, listElement) {
    if (!listElement) return;
    listElement.innerHTML = '';

    prefs.columns_order.forEach((column) => {
        const label = (config.labels && config.labels[column]) ? config.labels[column] : column;
        const isChecked = prefs.columns_visibility[column] !== false;

        const row = document.createElement('div');
        row.className = 'px-3 py-2 flex items-center justify-between gap-3 bg-white';
        row.setAttribute('data-report-pref-column', column);

        row.innerHTML = `
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 min-w-0">
                <input type="checkbox" class="h-4 w-4 text-indigo-600 border-slate-300 rounded" data-report-pref-visible="${column}" ${isChecked ? 'checked' : ''}>
                <span class="truncate">${label}</span>
            </label>
            <div class="flex items-center gap-1">
                <button type="button" class="px-2 py-1 text-xs border border-slate-300 rounded text-slate-600 hover:bg-slate-50" data-report-pref-move="up" data-report-pref-column="${column}">Subir</button>
                <button type="button" class="px-2 py-1 text-xs border border-slate-300 rounded text-slate-600 hover:bg-slate-50" data-report-pref-move="down" data-report-pref-column="${column}">Descer</button>
            </div>
        `;

        listElement.appendChild(row);
    });
}

function moveReportPrefColumn(prefs, column, direction) {
    const order = prefs.columns_order;
    const index = order.indexOf(column);
    if (index < 0) return;

    const targetIndex = direction === 'up' ? index - 1 : index + 1;
    if (targetIndex < 0 || targetIndex >= order.length) return;

    const swapped = order[targetIndex];
    order[targetIndex] = order[index];
    order[index] = swapped;
}

async function persistReportTablePrefs(config, prefs) {
    const formData = new FormData();
    formData.append('ccs_action', 'save_table_preferences');
    formData.append('_wpnonce', config.nonce || '');
    formData.append('preferences_json', JSON.stringify(prefs));
    formData.append('module', config.module || getCurrentModule());
    formData.append('ajax', '1');

    const response = await fetch(config.save_url || '?', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
        throw new Error('Resposta invalida ao salvar preferencias.');
    }

    const data = await response.json();
    if (!data.success) {
        const msg = (data && data.data && data.data.message) ? data.data.message : 'Erro ao salvar preferencias.';
        throw new Error(msg);
    }
}

function initReportTablePreferences() {
    const config = window.ccsTablePreferencesConfig;
    const table = document.getElementById('reportsTable');
    if (!config || !table || !Array.isArray(config.columns)) return;

    const modal = document.getElementById('reportTableSettingsModal');
    const openBtn = document.getElementById('reportEditTableBtn');
    const saveBtn = document.getElementById('reportTableSaveBtn');
    const resetBtn = document.getElementById('reportTableResetBtn');
    const closeEls = modal ? modal.querySelectorAll('[data-report-modal-close]') : [];
    const listEl = document.getElementById('reportTableColumnsList');
    const densitySelect = document.getElementById('reportDensitySetting');
    const zebraCheckbox = document.getElementById('reportZebraSetting');

    if (!modal || !openBtn || !saveBtn || !resetBtn || !listEl || !densitySelect || !zebraCheckbox) {
        return;
    }

    let activePrefs = sanitizeReportTablePrefs(config, config.preferences || {});
    let draftPrefs = cloneReportTablePrefs(activePrefs);

    const renderModal = () => {
        densitySelect.value = draftPrefs.density || 'normal';
        zebraCheckbox.checked = !!draftPrefs.zebra;
        buildReportTableColumnsList(config, draftPrefs, listEl);
    };

    const openModal = () => {
        draftPrefs = cloneReportTablePrefs(activePrefs);
        renderModal();
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    openBtn.addEventListener('click', openModal);
    closeEls.forEach((el) => el.addEventListener('click', closeModal));

    listEl.addEventListener('click', (event) => {
        const moveButton = event.target.closest('[data-report-pref-move]');
        if (!moveButton) return;

        const column = moveButton.getAttribute('data-report-pref-column');
        const direction = moveButton.getAttribute('data-report-pref-move');
        moveReportPrefColumn(draftPrefs, column, direction);
        renderModal();
    });

    listEl.addEventListener('change', (event) => {
        const checkbox = event.target.closest('[data-report-pref-visible]');
        if (!checkbox) return;

        const column = checkbox.getAttribute('data-report-pref-visible');
        draftPrefs.columns_visibility[column] = !!checkbox.checked;
    });

    densitySelect.addEventListener('change', () => {
        draftPrefs.density = densitySelect.value === 'compact' ? 'compact' : 'normal';
    });

    zebraCheckbox.addEventListener('change', () => {
        draftPrefs.zebra = !!zebraCheckbox.checked;
    });

    resetBtn.addEventListener('click', () => {
        draftPrefs = getDefaultReportTablePrefs(config);
        renderModal();
    });

    saveBtn.addEventListener('click', async () => {
        const visibleCount = Object.values(draftPrefs.columns_visibility || {}).filter(Boolean).length;
        if (visibleCount === 0 && draftPrefs.columns_order.length > 0) {
            draftPrefs.columns_visibility[draftPrefs.columns_order[0]] = true;
        }

        activePrefs = sanitizeReportTablePrefs(config, draftPrefs);
        applyReportTablePrefs(config, activePrefs);
        closeModal();

        try {
            await persistReportTablePrefs(config, activePrefs);
            if (typeof showToast === 'function') {
                showToast('Preferencias da tabela salvas.', 'success');
            }
        } catch (error) {
            if (typeof showToast === 'function') {
                showToast(error.message || 'Erro ao salvar preferencias.', 'error');
            } else {
                alert(error.message || 'Erro ao salvar preferencias.');
            }
        }
    });

    applyReportTablePrefs(config, activePrefs);
}

document.addEventListener('DOMContentLoaded', initReportTablePreferences);
