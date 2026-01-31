function filterTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll("#computerTableBody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const searchMeta = row.getAttribute("data-search-terms") || "";
        const combinedText = text + " " + searchMeta;

        row.style.display = combinedText.includes(filter) ? "" : "none";
    });
}
