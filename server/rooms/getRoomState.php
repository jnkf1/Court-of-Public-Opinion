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

$sql = "SELECT rooms.id, rooms.host_id, rooms.joiner_id, rooms.topic, rooms.host_stance,
               rooms.status, rooms.started_at,
               host.username AS host_username, joiner.username AS joiner_username
        FROM rooms
        JOIN users AS host ON rooms.host_id = host.id
        LEFT JOIN users AS joiner ON rooms.joiner_id = joiner.id
        WHERE rooms.id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $room_id);
$query->execute();
$result = $query->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo json_encode(["success" => false, "message" => "Room not found."]);
    exit;
}

if ($room["host_id"] != $user["id"] && $room["joiner_id"] != $user["id"]) {
    echo json_encode(["success" => false, "message" => "You're not part of this room."]);
    exit;
}

// Lazily close the debate once 15 minutes have passed
if ($room["status"] === "in_progress" && $room["started_at"] !== null) {
    $sql = "SELECT TIMESTAMPDIFF(MINUTE, ?, NOW()) AS minutes_elapsed";
    $query = $mysql->prepare($sql);
    $query->bind_param("s", $room["started_at"]);
    $query->execute();
    $result = $query->get_result();
    $elapsed = $result->fetch_assoc();

    if ($elapsed["minutes_elapsed"] >= 15) {
        $sql = "UPDATE rooms SET status = 'closed' WHERE id = ?";
        $query = $mysql->prepare($sql);
        $query->bind_param("i", $room_id);
        $query->execute();

        $room["status"] = "closed";
    }
}

$room["joiner_stance"] = $room["host_stance"] === "FOR" ? "AGAINST" : "FOR";

$sql = "SELECT room_messages.user_id, users.username, room_messages.message, room_messages.created_at
        FROM room_messages
        JOIN users ON room_messages.user_id = users.id
        WHERE room_messages.room_id = ?
        ORDER BY room_messages.created_at ASC";
$query = $mysql->prepare($sql);
$query->bind_param("i", $room_id);
$query->execute();
$result = $query->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    "success" => true,
    "room" => $room,
    "messages" => $messages
]);
?>
