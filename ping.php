<?php
header('Content-Type: application/json');

if (isset($_GET['host'])) {
    $host = escapeshellarg($_GET['host']);
    $pingresult = shell_exec("ping -c 1 $host");

    if (preg_match('/time=([\d.]+) ms/', $pingresult, $matches)) {
        echo json_encode(['ping' => $matches[1]]);
    } else {
        echo json_encode(['error' => 'Ping failed']);
    }
} else {
    echo json_encode(['error' => 'No host specified']);
}
?>

