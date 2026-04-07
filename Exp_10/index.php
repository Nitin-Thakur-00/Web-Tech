<?php
// --- DATABASE CONFIGURATION (InfinityFree Setup) ---
// 1. Log in to your InfinityFree Control Panel.
// 2. Go to "MySQL Databases".
// 3. Create a new database if you haven't already.
// 4. Copy your "MySQL Host", "MySQL Username", "MySQL Password", and "Database Name" here.

$host = 'sql107.infinityfree.com';        // Usually something like sqlXXX.infinityfree.com
$db = 'if0_41597931_web_tech';         // Usually something like if0_XXXXX_web_tech
$user = 'if0_41597931';             // Usually something like if0_XXXXX
$pass = 'mimikyu9686';                 // Your InfinityFree account password
$charset = 'utf8mb4';
// --------------------------------------------------

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

// API Request Handler
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    if (isset($db_error)) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $db_error]);
        exit;
    }

    $action = $_GET['action'] ?? '';

    if ($action === 'save') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['teamSize']) && isset($data['email'])) {
            $stmt = $pdo->prepare("INSERT INTO registrations (team_size, member_names, email, phone, year_of_study, domains, project_brief) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['teamSize'],
                $data['memberNames'],
                $data['email'],
                $data['phone'],
                $data['year'],
                $data['domains'],
                $data['idea']
            ]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        }
    } elseif ($action === 'fetch') {
        $stmt = $pdo->query("SELECT * FROM registrations ORDER BY created_at DESC");
        $history = $stmt->fetchAll();
        echo json_encode($history);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ideathon 2026 Registration</title>
    <link rel="stylesheet" href="ideathon_css.css">
</head>

<body>

    <h2>Ideathon 2026 Registration</h2>

    <form id="regForm">
        <div class="section">
            <label><strong>Team Size:</strong></label>
            <div class="radio-group">
                <label><input type="radio" name="teamSize" value="1" onclick="adjustFields(1)" checked> 1</label>
                <label><input type="radio" name="teamSize" value="2" onclick="adjustFields(2)"> 2</label>
                <label><input type="radio" name="teamSize" value="3" onclick="adjustFields(3)"> 3</label>
                <label><input type="radio" name="teamSize" value="4" onclick="adjustFields(4)"> 4</label>
            </div>
        </div>

        <div id="memberFields">
            <label>Member 1 Name:</label>
            <input type="text" class="memberName" placeholder="Full Name">
        </div>

        <label>Leader Email:</label>
        <input type="email" id="email" placeholder="leader@gmail.com">

        <label>Contact Number:</label>
        <input type="tel" id="phone" placeholder="+91 8567898754">

        <label><strong>Year of Study:</strong></label>
        <div class="radio-group">
            <label><input type="radio" name="year" value="1st" checked> 1st</label>
            <label><input type="radio" name="year" value="2nd"> 2nd</label>
            <label><input type="radio" name="year" value="3rd"> 3rd</label>
            <label><input type="radio" name="year" value="4th"> 4th</label>
        </div>

        <label><strong>Project Domain:</strong></label>
        <div class="check-group">
            <label><input type="checkbox" name="domain" value="Cybersecurity"> Cybersecurity</label>
            <label><input type="checkbox" name="domain" value="Web Dev"> Web Development</label>
            <label><input type="checkbox" name="domain" value="App Dev"> App Development</label>
            <label><input type="checkbox" name="domain" value="Database"> Database Management</label>
        </div>

        <label><strong>Project Brief:</strong></label>
        <textarea id="idea" placeholder="Briefly describe your innovative project..."></textarea>

        <label>Upload PPT/PDF:</label>
        <div class="file-container">
            <label for="pptFile" class="custom-file-upload">
                <span id="file-label-text">Click to select PPT or PDF</span>
            </label>
            <input type="file" id="pptFile" onchange="updateFileName()">
            <div id="file-name-display" class="file-name-text">No file chosen</div>
        </div>

        <button type="button" onclick="processRegistration()">Register Team</button>
    </form>

    <hr>

    <div id="displayArea" style="display:none;">
    </div>

    <hr>

    <div id="table-section">
        <h3>All Registrations</h3>
        <div class="table-responsive">
            <table id="registrationTable">
                <thead>
                    <tr>
                        <th>Members</th>
                        <th>Email</th>
                        <th>Year</th>
                        <th>Domains</th>
                        <th>Project Idea</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                </tbody>
            </table>
        </div>

        <div class="action-buttons">
            <button class="secondary-btn" onclick="copyTable()">Copy Table</button>
            <button class="secondary-btn" onclick="downloadCSV()">Download CSV</button>
        </div>
    </div>

    <script src="ideathon_js.js"></script>
</body>

</html>
