<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["token"])) {
    $token = $_POST["token"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing token."]);
    return;
}

if (isset($_POST["room_id"])) {
    $room_id = $_POST["room_id"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing room."]);
    return;
}

if (isset($_POST["message"])) {
    $message = $_POST["message"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing message."]);
    return;
}

$sql = "SELECT id FROM users WHERE token = ?";
$query = $mysql->prepare($sql);
$query->bind_param("s", $token);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid or expired session."]);
    return;
}

$sql = "SELECT id, host_id, joiner_id, status FROM rooms WHERE id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $room_id);
$query->execute();
$result = $query->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo json_encode(["success" => false, "message" => "Room not found."]);
    return;
}

if ($room["host_id"] != $user["id"] && $room["joiner_id"] != $user["id"]) {
    echo json_encode(["success" => false, "message" => "You're not part of this room."]);
    return;
}

if ($room["status"] !== "in_progress") {
    echo json_encode(["success" => false, "message" => "This debate isn't active."]);
    return;
}

$sql = "INSERT INTO room_messages(room_id, user_id, message) VALUES (?, ?, ?)";
$query = $mysql->prepare($sql);
$query->bind_param("iis", $room_id, $user["id"], $message);
$query->execute();

echo json_encode(["success" => true]);
?>
