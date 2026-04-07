<?php
$favourite_technologies = ['PHP', 'JavaScript', 'CSS3'];
$eol_literal = str_replace(["\r", "\n"], ['\r', '\n'], PHP_EOL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System & Environment Information</title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            line-height: 1.6;
            margin: 0;
            padding: 40px 20px;
        }
        .main-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        h1 {
            color: #4f5b93; /* PHP Purpleish Blue */
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 12px;
            margin-top: 0;
        }
        h2 {
            color: #4f5b93;
            margin-top: 30px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .info-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f5;
        }
        .info-table td:first-child {
            font-weight: 600;
            width: 35%;
            color: #495057;
        }
        .info-table td:last-child {
            font-family: 'Courier New', Courier, monospace;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            word-break: break-all;
        }
        #clock {
            color: #d63384;
            font-weight: bold;
            font-size: 1.1em;
        }
        .tech-list {
            list-style-type: none;
            padding: 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .tech-list li {
            background-color: #e3f2fd;
            color: #0d47a1;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            border: 1px solid #bbdefb;
        }
        .reminder {
            margin-top: 40px;
            padding: 15px 20px;
            background-color: #fff3cd;
            border-left: 5px solid #ffecb5;
            color: #664d03;
            border-radius: 4px;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="main-container">
    <h1>System Information</h1>
    
    <table class="info-table">
        <tr>
            <td>PHP Version</td>
            <td><?php echo PHP_VERSION; ?></td>
        </tr>
        <tr>
            <td>Operating System</td>
            <td><?php echo PHP_OS; ?></td>
        </tr>
        <tr>
            <td>Maximum Integer Limit</td>
            <td><?php echo PHP_INT_MAX; ?></td>
        </tr>
        <tr>
            <td>End-of-line Character</td>
            <td><?php echo $eol_literal; ?></td>
        </tr>
        <tr>
            <td>Today's Date</td>
            <td><?php echo date('l, d F Y'); ?></td>
        </tr>
        <tr>
            <td>Current Time</td>
            <td><span id='clock'><?php echo date('H:i:s'); ?></span></td>
        </tr>
        <tr>
            <td>Document Root</td>
            <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
        </tr>
        <tr>
            <td>Current Script Path</td>
            <td><?php echo $_SERVER['SCRIPT_FILENAME']; ?></td>
        </tr>
    </table>

    <h2>Favourite Technologies</h2>
    <ul class="tech-list">
        <?php 
        foreach ($favourite_technologies as $tech) { 
            echo '<li>' . $tech . '</li>'; 
        } 
        ?>
    </ul>

    <div class="reminder">
        💡 <strong>Note:</strong> This page refreshes each request — PHP re-runs every time
    </div>
</div>

</body>
</html>
