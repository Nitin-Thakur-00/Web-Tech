<?php
$host = 'sql107.infinityfree.com';
$db = 'if0_41597931_web_tech';
$user = 'if0_41597931';
$pass = 'mimikyu9686';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    $db_error = $e->getMessage();
}

if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    if (isset($db_error)) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $db_error]);
        exit;
    }

    $action = $_GET['action'] ?? '';

    if ($action === 'save') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['expression']) && isset($data['result'])) {
            $stmt = $pdo->prepare("INSERT INTO calculator_history (expression, result) VALUES (?, ?)");
            $stmt->execute([$data['expression'], $data['result']]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        }
    } elseif ($action === 'fetch') {
        $stmt = $pdo->query("SELECT * FROM calculator_history ORDER BY created_at DESC LIMIT 20");
        $history = $stmt->fetchAll();
        echo json_encode($history);
    } elseif ($action === 'clear') {
        $pdo->exec("TRUNCATE TABLE calculator_history");
        echo json_encode(['status' => 'success']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="calculator_css.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=JetBrains+Mono:wght@500&display=swap"
        rel="stylesheet">
</head>

<body>

    <div class="main-wrapper">
        <div class="calc-container">
            <div class="calculator">
                <div class="display-container">
                    <div id="secondary-display" class="secondary-display"></div>
                    <input type="text" id="display" readonly placeholder="0">
                </div>

                <div class="buttons">
                    <button class="btn clear" onclick="clearDisplay()">CE</button>
                    <button class="btn tool" onclick="deleteLast()">DEL</button>
                    <button class="btn tool" onclick="appendToDisplay('/')">/</button>
                    <button class="btn" onclick="appendToDisplay('7')">7</button>
                    <button class="btn" onclick="appendToDisplay('8')">8</button>
                    <button class="btn" onclick="appendToDisplay('9')">9</button>
                    <button class="btn tool" onclick="appendToDisplay('*')">×</button>
                    <button class="btn" onclick="appendToDisplay('4')">4</button>
                    <button class="btn" onclick="appendToDisplay('5')">5</button>
                    <button class="btn" onclick="appendToDisplay('6')">6</button>
                    <button class="btn tool" onclick="appendToDisplay('-')">-</button>
                    <button class="btn" onclick="appendToDisplay('1')">1</button>
                    <button class="btn" onclick="appendToDisplay('2')">2</button>
                    <button class="btn" onclick="appendToDisplay('3')">3</button>
                    <button class="btn tool" onclick="appendToDisplay('+')">+</button>
                    <button class="btn" onclick="appendToDisplay('0')">0</button>
                    <button class="btn" onclick="appendToDisplay('.')">.</button>
                    <button class="btn equal" onclick="calculate()">=</button>
                </div>
            </div>
        </div>

        <div class="history-panel">
            <div class="history-header">
                <h3>Calculation History</h3>
                <button class="clear-history" onclick="clearHistory()">Clear</button>
            </div>
            <div id="history-list" class="history-list">
                <div class="no-history">No history yet</div>
            </div>
        </div>
    </div>

    <script src="calculator_js.js"></script>
</body>

</html>
