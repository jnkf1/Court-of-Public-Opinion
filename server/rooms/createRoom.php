<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["token"])) {
    $token = $_POST["token"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing token."]);
    exit;
}

if (isset($_POST["topic"])) {
    $topic = $_POST["topic"];
}
else {
    $topic = "";
    exit;
}

if (isset($_POST["stance"])) {
    $stance = $_POST["stance"];
}
else {
    $stance = "";
    exit;
}

$sql = "SELECT id, username FROM users WHERE token = ?";
$query = $mysql->prepare($sql);
$query->bind_param("s", $token);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid or expired session."]);
    exit;
}

$sql = "SELECT id FROM rooms WHERE (host_id = ? OR joiner_id = ?) AND status IN ('open', 'in_progress')";
$query = $mysql->prepare($sql);
$query->bind_param("ii", $user["id"], $user["id"]);
$query->execute();
$result = $query->get_result();
$existingRoom = $result->fetch_assoc();

if ($existingRoom) {
    echo json_encode(["success" => false, "message" => "You're already in a room. Leave or finish that one first."]);
    exit;
}

$sql = "INSERT INTO rooms(host_id, topic, host_stance) VALUES (?, ?, ?)";
$query = $mysql->prepare($sql);
$query->bind_param("iss", $user["id"], $topic, $stance);
$query->execute();

if ($query->affected_rows > 0) {
    echo json_encode([
        "success" => true,
        "message" => "Room created!",
        "room" => [
            "id" => $mysql->insert_id,
            "topic" => $topic,
            "host_stance" => $stance,
            "host_username" => $user["username"]
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Something went wrong."]);
}
?>
