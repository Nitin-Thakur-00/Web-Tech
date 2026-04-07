const display = document.getElementById('display');
const secondaryDisplay = document.getElementById('secondary-display');
const historyList = document.getElementById('history-list');

// Initialize history on load
document.addEventListener('DOMContentLoaded', loadHistory);

// Keyboard Support
window.addEventListener('keydown', (e) => {
    const key = e.key;

    if (/[0-9]/.test(key)) {
        appendToDisplay(key);
    } else if (['+', '-', '*', '/'].includes(key)) {
        appendToDisplay(key);
    } else if (key === '.' || key === ',') {
        appendToDisplay('.');
    } else if (key === 'Enter' || key === '=') {
        e.preventDefault();
        calculate();
    } else if (key === 'Backspace') {
        deleteLast();
    } else if (key === 'Escape') {
        clearDisplay();
    }
});

function appendToDisplay(input) {
    // If the main display was showing a result, clear it if a new number is typed
    if (secondaryDisplay.innerText.includes('=') && /[0-9]/.test(input)) {
        clearDisplay();
    }
    display.value += input;
}

function clearDisplay() {
    display.value = "";
    secondaryDisplay.innerText = "";
}

function deleteLast() {
    display.value = display.value.slice(0, -1);
}

function calculate() {
    const expression = display.value;
    if (!expression) return;

    try {
        // Clean expression for eval (e.g. handle multiple operators if any)
        const result = eval(expression);
        
        // Update Displays
        secondaryDisplay.innerText = `${expression} =`;
        display.value = result;
        
        saveCalculation(expression, result);
    } catch (error) {
        display.value = "Error";
        secondaryDisplay.innerText = expression;
        setTimeout(clearDisplay, 1500);
    }
}

async function saveCalculation(expression, result) {
    try {
        await fetch('index.php?api=1&action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ expression, result })
        });
        loadHistory();
    } catch (error) {
        console.error('Error saving calculation:', error);
    }
}

async function loadHistory() {
    try {
        const response = await fetch('index.php?api=1&action=fetch');
        const history = await response.json();
        
        if (history.length === 0 || history.status === 'error') {
            historyList.innerHTML = '<div class="no-history">No history yet</div>';
            return;
        }

        historyList.innerHTML = history.map(item => `
            <div class="history-item" onclick="loadItem('${item.expression}')">
                <div class="history-expr">${item.expression}</div>
                <div class="history-res">${item.result}</div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading history:', error);
    }
}

async function clearHistory() {
    if (!confirm('Clear all calculation history?')) return;
    try {
        await fetch('index.php?api=1&action=clear');
        loadHistory();
    } catch (error) {
        console.error('Error clearing history:', error);
    }
}

function loadItem(expression) {
    display.value = expression;
}
