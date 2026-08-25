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

$sql = "SELECT host_id, joiner_id, topic, host_stance, status FROM rooms WHERE id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $room_id);
$query->execute();
$result = $query->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo json_encode(["success" => false, "message" => "Room not found."]);
    return;
}

$isHost = $room["host_id"] == $user["id"];
$isJoiner = $room["joiner_id"] == $user["id"];

if (!$isHost && !$isJoiner) {
    echo json_encode(["success" => false, "message" => "You're not part of this room."]);
    return;
}

if ($room["status"] === "open") {
    echo json_encode(["success" => false, "message" => "This room hasn't started yet - cancel it instead if you want to close it."]);
    return;
}

if ($room["status"] !== "in_progress") {
    $sql = "SELECT verdict, score, logic_score, rebuttal_score, evidence_score, persuasion_score
            FROM cases WHERE room_id = ? AND user_id = ?";
    $query = $mysql->prepare($sql);
    $query->bind_param("ii", $room_id, $user["id"]);
    $query->execute();
    $verdict = $query->get_result()->fetch_assoc();

    if ($verdict) {
        echo json_encode(array_merge(["success" => true], $verdict));
    }
    else {
        echo json_encode(["success" => false, "message" => "This debate has already ended."]);
    }

    return;
}

$hostStance = $room["host_stance"];
$joinerStance = $hostStance === "FOR" ? "AGAINST" : "FOR";

$forfeiterId = $user["id"];
$opponentId = $isHost ? $room["joiner_id"] : $room["host_id"];
$forfeiterStance = $isHost ? $hostStance : $joinerStance;
$opponentStance = $isHost ? $joinerStance : $hostStance;

$sql = "UPDATE rooms SET status = 'closed' WHERE id = ? AND status = 'in_progress'";
$query = $mysql->prepare($sql);
$query->bind_param("i", $room_id);
$query->execute();

if ($query->affected_rows === 0) {
    $sql = "SELECT verdict, score, logic_score, rebuttal_score, evidence_score, persuasion_score
            FROM cases WHERE room_id = ? AND user_id = ?";
    $query = $mysql->prepare($sql);
    $query->bind_param("ii", $room_id, $forfeiterId);
    $query->execute();
    $verdict = $query->get_result()->fetch_assoc();

    if ($verdict) {
        echo json_encode(array_merge(["success" => true], $verdict));
    }
    else {
        echo json_encode(["success" => false, "message" => "This debate just ended."]);
    }

    return;
}

$sql = "INSERT INTO cases(user_id, opponent_id, room_id, topic, user_stance, verdict, score, logic_score, rebuttal_score, evidence_score, persuasion_score)
        VALUES (?, ?, ?, ?, ?, 'LOST', 0, 0, 0, 0, 0)";
$query = $mysql->prepare($sql);
$query->bind_param("iiiss", $forfeiterId, $opponentId, $room_id, $room["topic"], $forfeiterStance);
$query->execute();

$sql = "INSERT INTO cases(user_id, opponent_id, room_id, topic, user_stance, verdict, score, logic_score, rebuttal_score, evidence_score, persuasion_score)
        VALUES (?, ?, ?, ?, ?, 'WON', 100, 100, 100, 100, 100)";
$query = $mysql->prepare($sql);
$query->bind_param("iiiss", $opponentId, $forfeiterId, $room_id, $room["topic"], $opponentStance);
$query->execute();

echo json_encode([
    "success" => true,
    "verdict" => "LOST",
    "score" => 0,
    "logic_score" => 0,
    "rebuttal_score" => 0,
    "evidence_score" => 0,
    "persuasion_score" => 0
]);
?>
