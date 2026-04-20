<?php
header('Content-Type: application/json');
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $headers[$key] = $value;
    }
}
if (function_exists('getallheaders')) {
    $headers['ALL_HEADERS'] = getallheaders();
}
echo json_encode(['server' => $_SERVER, 'headers' => $headers]);
?>
