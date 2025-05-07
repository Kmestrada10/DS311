<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if (!isset($_GET['lat']) || !isset($_GET['lon'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing latitude or longitude"]);
    exit;
}

$lat = escapeshellarg($_GET['lat']);
$lon = escapeshellarg($_GET['lon']);

// Call Python script
$command = "python3 fetch_crime_data.py $lat $lon";
$output = shell_exec($command);

if ($output === null) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to run script"]);
    exit;
}

// Return JSON output from Python directly
header('Content-Type: application/json');
echo $output;
?>
