function filterTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll("#computerTableBody tr");

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
}
