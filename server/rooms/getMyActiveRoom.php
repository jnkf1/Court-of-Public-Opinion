<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["token"])) {
    $token = $_POST["token"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing token."]);
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

$user_id = $user["id"];

// Lazily close this user's rooms whose 1 minute has already run out
$sql = "UPDATE rooms SET status = 'closed'
        WHERE (host_id = ? OR joiner_id = ?)
        AND status = 'in_progress'
        AND started_at IS NOT NULL
        AND TIMESTAMPDIFF(SECOND, started_at, NOW()) >= 60";
$query = $mysql->prepare($sql);
$query->bind_param("ii", $user_id, $user_id);
$query->execute();

// Also includes a room they're hosting that's still 'open' (waiting for an opponent) -
// not just 'in_progress' - so the host has a way back in even before anyone joins
$sql = "SELECT id, status FROM rooms
        WHERE (host_id = ? AND status IN ('open', 'in_progress'))
           OR (joiner_id = ? AND status = 'in_progress')";
$query = $mysql->prepare($sql);
$query->bind_param("ii", $user_id, $user_id);
$query->execute();
$result = $query->get_result();
$activeRoom = $result->fetch_assoc();

if ($activeRoom) {
    echo json_encode(["success" => true, "room_id" => $activeRoom["id"], "room_status" => $activeRoom["status"]]);
}
else {
    echo json_encode(["success" => true, "room_id" => null]);
}
?>
