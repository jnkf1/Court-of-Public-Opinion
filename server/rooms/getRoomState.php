<?php
include(__DIR__ . "/../database/connection.php");
include(__DIR__ . "/../ai/api.php");

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
    return;
}

if ($room["host_id"] != $user["id"] && $room["joiner_id"] != $user["id"]) {
    echo json_encode(["success" => false, "message" => "You're not part of this room."]);
    return;
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

// Lazily close the debate once 1 minute has passed, and judge it right then
if ($room["status"] === "in_progress" && $room["started_at"] !== null) {
    $sql = "SELECT TIMESTAMPDIFF(SECOND, ?, NOW()) AS seconds_elapsed";
    $query = $mysql->prepare($sql);
    $query->bind_param("s", $room["started_at"]);
    $query->execute();
    $result = $query->get_result();
    $elapsed = $result->fetch_assoc();

    if ($elapsed["seconds_elapsed"] >= 60) {
        $sql = "UPDATE rooms SET status = 'closed' WHERE id = ? AND status = 'in_progress'";
        $query = $mysql->prepare($sql);
        $query->bind_param("i", $room_id);
        $query->execute();

        $room["status"] = "closed";

        // Only the request that actually flipped the status judges the debate,
        // so two participants polling at the same time don't produce duplicate verdicts
        if ($query->affected_rows > 0 && count($messages) > 0) {
            judgeRoomDebate($mysql, $room, $messages);
        }
    }
}

$myVerdict = null;

if ($room["status"] === "closed") {
    $sql = "SELECT verdict, score, logic_score, rebuttal_score, evidence_score, persuasion_score
            FROM cases WHERE room_id = ? AND user_id = ?";
    $query = $mysql->prepare($sql);
    $query->bind_param("ii", $room_id, $user["id"]);
    $query->execute();
    $result = $query->get_result();
    $myVerdict = $result->fetch_assoc();
}

echo json_encode([
    "success" => true,
    "room" => $room,
    "messages" => $messages,
    "myVerdict" => $myVerdict
]);

function judgeRoomDebate($mysql, $room, $messages) {
    $transcript = "";

    foreach ($messages as $m) {
        $transcript .= $m["username"] . ": " . $m["message"] . "\n\n";
    }

    $systemInstruction = "You are an impartial debate judge. You will be given the topic, the two debaters' names and stances, and a full transcript of their debate. " .
        "Evaluate each debater's performance separately, scoring each category from 0 to 100: logic, rebuttal, evidence, persuasion. " .
        "Then decide the overall winner: HOST_WON, JOINER_WON, or DRAW. " .
        "Respond with ONLY valid JSON in exactly this format, no other text: " .
        '{"winner": "HOST_WON", "host_scores": {"logic": 80, "rebuttal": 75, "evidence": 70, "persuasion": 85}, "joiner_scores": {"logic": 80, "rebuttal": 75, "evidence": 70, "persuasion": 85}}';

    $prompt = "Topic: " . $room["topic"] . "\n" .
        "Host: " . $room["host_username"] . " (" . $room["host_stance"] . ")\n" .
        "Joiner: " . $room["joiner_username"] . " (" . $room["joiner_stance"] . ")\n\n" .
        "Transcript:\n\n" . $transcript . "\nPlease judge this debate now.";

    $judgeInput = [
        ["type" => "user_input", "content" => [["type" => "text", "text" => $prompt]]]
    ];

    $result = callGemini($systemInstruction, $judgeInput);

    if (!$result["success"]) {
        return;
    }

    $verdictData = extractJsonFromText($result["text"]);

    if (!$verdictData || !isset($verdictData["winner"])) {
        return;
    }

    $winner = $verdictData["winner"];
    $hostScores = isset($verdictData["host_scores"]) ? $verdictData["host_scores"] : [];
    $joinerScores = isset($verdictData["joiner_scores"]) ? $verdictData["joiner_scores"] : [];

    if ($winner === "HOST_WON") {
        $hostVerdict = "WON";
        $joinerVerdict = "LOST";
    }
    else if ($winner === "JOINER_WON") {
        $hostVerdict = "LOST";
        $joinerVerdict = "WON";
    }
    else {
        $hostVerdict = "DRAW";
        $joinerVerdict = "DRAW";
    }

    insertRoomCase($mysql, $room["host_id"], $room["joiner_id"], $room["id"], $room["topic"], $room["host_stance"], $hostVerdict, $hostScores);
    insertRoomCase($mysql, $room["joiner_id"], $room["host_id"], $room["id"], $room["topic"], $room["joiner_stance"], $joinerVerdict, $joinerScores);
}

function insertRoomCase($mysql, $userId, $opponentId, $roomId, $topic, $stance, $verdict, $scores) {
    $logicScore = isset($scores["logic"]) ? (int) $scores["logic"] : 50;
    $rebuttalScore = isset($scores["rebuttal"]) ? (int) $scores["rebuttal"] : 50;
    $evidenceScore = isset($scores["evidence"]) ? (int) $scores["evidence"] : 50;
    $persuasionScore = isset($scores["persuasion"]) ? (int) $scores["persuasion"] : 50;
    $overallScore = (int) round(($logicScore + $rebuttalScore + $evidenceScore + $persuasionScore) / 4);

    $sql = "INSERT INTO cases(user_id, opponent_id, room_id, topic, user_stance, verdict, score, logic_score, rebuttal_score, evidence_score, persuasion_score)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $query = $mysql->prepare($sql);
    $query->bind_param("iiisssiiiii", $userId, $opponentId, $roomId, $topic, $stance, $verdict, $overallScore, $logicScore, $rebuttalScore, $evidenceScore, $persuasionScore);
    $query->execute();
}
?>
