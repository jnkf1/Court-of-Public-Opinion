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

// Delete this user's own case history (must happen before their rooms are deleted below,
// since cases.room_id references rooms.id and would block the delete otherwise)
$sql = "DELETE FROM cases WHERE user_id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();

// The other participant's case rows for rooms this user hosted still point at those rooms —
// detach the reference (preserving their record) so the rooms can be deleted next
$sql = "UPDATE cases SET room_id = NULL WHERE room_id IN (SELECT id FROM rooms WHERE host_id = ?)";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();

// Delete all messages in rooms this user hosted (the whole room is theirs to delete)
$sql = "DELETE room_messages FROM room_messages
        JOIN rooms ON room_messages.room_id = rooms.id
        WHERE rooms.host_id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();

// Delete the rooms this user hosted
$sql = "DELETE FROM rooms WHERE host_id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();

// Delete this user's own messages in rooms they only joined (preserves the room + host's messages)
$sql = "DELETE FROM room_messages WHERE user_id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();

// Detach this user as joiner from rooms they didn't host, and reopen those rooms
$sql = "UPDATE rooms SET joiner_id = NULL, status = 'open', started_at = NULL WHERE joiner_id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();

// Detach this user as an opponent in other people's cases, without deleting their record
$sql = "UPDATE cases SET opponent_id = NULL WHERE opponent_id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);
$query->execute();

$sql = "DELETE FROM users WHERE id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);

try {
    $query->execute();
}
catch (mysqli_sql_exception $e) {
    if ($mysql->errno === 1451) {
        echo json_encode(["success" => false, "message" => "Can't delete an account with existing room activity."]);
    }
    else {
        echo json_encode(["success" => false, "message" => "Something went wrong."]);
    }
    exit;
}

echo json_encode(["success" => true, "message" => "Account deleted."]);
?>
