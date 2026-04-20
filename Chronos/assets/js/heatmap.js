document.addEventListener('DOMContentLoaded', () => {
    if(document.getElementById('heatmapGrid')) renderHeatmapDOM();
});

async function renderHeatmapDOM() {
    const grid = document.getElementById('heatmapGrid');
    if (!grid) return;
    
    grid.innerHTML = ''; // prevent duplicates

    // Produce 52 weeks * 7 days empty grid
    const totalDays = 52 * 7;
    const now = new Date();
    // Normalise to 1 year ago exactly
    const startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - totalDays);

    let heatmapData = [];
    try {
        const res = await API.getHeatmap(now.getFullYear());
        heatmapData = res.data; // [{date: '2026-04-10', minutes: 25}, ...]
    } catch(err) {}

    // Convert to map for quick lookup
    const dataMap = {};
    heatmapData.forEach(d => { dataMap[d.date] = parseInt(d.minutes); });

    for (let i = 0; i < totalDays; i++) {
        const day = new Date(startDate);
        day.setDate(startDate.getDate() + i);
        
        // YYYY-MM-DD local format conceptually
        const dateStr = day.getFullYear() + "-" + String(day.getMonth() + 1).padStart(2,'0') + "-" + String(day.getDate()).padStart(2,'0');

        const cell = document.createElement('div');
        cell.className = 'heatmap-cell';
        cell.title = dateStr;

        if (dataMap[dateStr]) {
            const min = dataMap[dateStr];
            cell.title = `${min} mins on ${dateStr}`;
            if (min > 120) cell.classList.add('heatmap-lvl-4');
            else if (min > 60) cell.classList.add('heatmap-lvl-3');
            else if (min > 30) cell.classList.add('heatmap-lvl-2');
            else cell.classList.add('heatmap-lvl-1');
        }

        grid.appendChild(cell);
    }
}
