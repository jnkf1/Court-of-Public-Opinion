<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["token"])) {
    $token = $_POST["token"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing token."]);
    exit;
}

$sql = "SELECT id FROM users WHERE token = ?";
$query = $mysql->prepare($sql);
$query->bind_param("s", $token);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid or expired session."]);
    exit;
}

$user_id = $user["id"];

// Lazily close this user's rooms whose 15 minutes have already run out
$sql = "UPDATE rooms SET status = 'closed'
        WHERE (host_id = ? OR joiner_id = ?)
        AND status = 'in_progress'
        AND started_at IS NOT NULL
        AND TIMESTAMPDIFF(MINUTE, started_at, NOW()) >= 15";
$query = $mysql->prepare($sql);
$query->bind_param("ii", $user_id, $user_id);
$query->execute();

$sql = "SELECT id FROM rooms WHERE (host_id = ? OR joiner_id = ?) AND status = 'in_progress'";
$query = $mysql->prepare($sql);
$query->bind_param("ii", $user_id, $user_id);
$query->execute();
$result = $query->get_result();
$activeRoom = $result->fetch_assoc();

if ($activeRoom) {
    echo json_encode(["success" => true, "room_id" => $activeRoom["id"]]);
}
else {
    echo json_encode(["success" => true, "room_id" => null]);
}
?>
