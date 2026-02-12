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

// Run on load to handle browser persistence
document.addEventListener('DOMContentLoaded', filterTable);

// Toggle filter panel visibility
function toggleFilters() {
    const panel = document.getElementById('filterPanel');
    if (!panel) return;

    const isHidden = panel.classList.contains('hidden');

    if (isHidden) {
        panel.classList.remove('hidden');
        // Store state in localStorage
        localStorage.setItem('filterPanelOpen', 'true');
    } else {
        panel.classList.add('hidden');
        localStorage.setItem('filterPanelOpen', 'false');
    }
}

// Restore filter panel state on page load
document.addEventListener('DOMContentLoaded', function () {
    const panel = document.getElementById('filterPanel');
    if (!panel) return;

    const isOpen = localStorage.getItem('filterPanelOpen') === 'true';

    if (isOpen) {
        panel.classList.remove('hidden');
    }
});

function normalizeReportValue(value) {
    return (value || '').toString().trim().toLowerCase();
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

        return rowValue === filterValue;
    }

    return rowValue.includes(filterValue);
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

            applyReportsFilters();
        });
    }

    applyReportsFilters();
}

document.addEventListener('DOMContentLoaded', initReportsFilters);
