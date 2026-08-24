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
    echo json_encode(["success" => false, "message" => "Missing topic."]);
    exit;
}

if (isset($_POST["userStance"])) {
    $userStance = $_POST["userStance"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing stance."]);
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

$sql = "INSERT INTO cases(user_id, topic, user_stance, verdict, score, logic_score, rebuttal_score, evidence_score, persuasion_score)
        VALUES (?, ?, ?, 'LOST', 0, 0, 0, 0, 0)";
$query = $mysql->prepare($sql);
$query->bind_param("iss", $user["id"], $topic, $userStance);
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
