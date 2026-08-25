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

$sql = "SELECT id, host_id, status FROM rooms WHERE id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $room_id);
$query->execute();
$result = $query->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo json_encode(["success" => false, "message" => "Room not found."]);
    return;
}

if ($room["status"] !== "open") {
    echo json_encode(["success" => false, "message" => "This room is no longer open."]);
    return;
}

if ($room["host_id"] == $user["id"]) {
    echo json_encode(["success" => false, "message" => "You can't join your own room."]);
    return;
}

$sql = "SELECT id FROM rooms WHERE (host_id = ? OR joiner_id = ?) AND status IN ('open', 'in_progress')";
$query = $mysql->prepare($sql);
$query->bind_param("ii", $user["id"], $user["id"]);
$query->execute();
$result = $query->get_result();
$existingRoom = $result->fetch_assoc();

if ($existingRoom) {
    echo json_encode(["success" => false, "message" => "You're already in a room. Leave or finish that one first."]);
    return;
}

$sql = "UPDATE rooms SET joiner_id = ?, status = 'in_progress', started_at = NOW() WHERE id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("ii", $user["id"], $room_id);
$query->execute();

echo json_encode(["success" => true, "message" => "Joined the room!"]);
?>
