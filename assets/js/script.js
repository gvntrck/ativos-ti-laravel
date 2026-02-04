function filterTable() {
    const input = document.getElementById("searchInput");
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
    const isOpen = localStorage.getItem('filterPanelOpen') === 'true';

    if (isOpen) {
        panel.classList.remove('hidden');
    }
});
