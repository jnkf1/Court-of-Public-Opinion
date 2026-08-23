<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["token"])) {
    $token = $_POST["token"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing token."]);
    exit;
}

if (isset($_POST["room_id"])) {
    $room_id = $_POST["room_id"];
}
else {
    $room_id = -1;
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

$sql = "SELECT id, host_id, status FROM rooms WHERE id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $room_id);
$query->execute();
$result = $query->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo json_encode(["success" => false, "message" => "Room not found."]);
    exit;
}

if ($room["host_id"] != $user["id"]) {
    echo json_encode(["success" => false, "message" => "You can only cancel your own room."]);
    exit;
}

if ($room["status"] !== "open") {
    echo json_encode(["success" => false, "message" => "This room can no longer be cancelled."]);
    exit;
}

$sql = "UPDATE rooms SET status = 'closed' WHERE id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $room_id);
$query->execute();

echo json_encode(["success" => true, "message" => "Room cancelled."]);
?>
