<?php
include(__DIR__ . "/../database/connection.php");
include(__DIR__ . "/api.php");

if (isset($_POST["token"])) {
    $token = $_POST["token"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing token."]);
    return;
}

if (isset($_POST["topic"])) {
    $topic = $_POST["topic"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing topic."]);
    return;
}

if (isset($_POST["userStance"])) {
    $userStance = $_POST["userStance"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing stance."]);
    return;
}

if (isset($_POST["history"])) {
    $history = json_decode($_POST["history"], true);
}
else {
    $history = [];
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

$aiStance = $userStance === "FOR" ? "AGAINST" : "FOR";

if (count($history) === 0) {
    echo json_encode(["success" => false, "message" => "There's nothing to judge yet."]);
    return;
}

$transcript = "";

foreach ($history as $turn) {
    $speaker = $turn["type"] === "user_input" ? "USER (" . $userStance . ")" : "AI (" . $aiStance . ")";
    $transcript .= $speaker . ": " . $turn["content"][0]["text"] . "\n\n";
}

$systemInstruction = "You are an impartial debate judge. You will be given the topic and a full transcript of a debate between a USER and an AI, arguing opposite sides. " .
    "Evaluate only the USER's performance, scoring each category from 0 to 100: logic, rebuttal, evidence, persuasion. " .
    "Then decide the verdict from the USER's perspective: WON, LOST, or DRAW. " .
    "Respond with ONLY valid JSON in exactly this format, no other text: " .
    '{"verdict": "WON", "logic_score": 80, "rebuttal_score": 75, "evidence_score": 70, "persuasion_score": 85}';

$judgeInput = [
    [
        "type" => "user_input",
        "content" => [["type" => "text", "text" => "Topic: " . $topic . "\n\nTranscript:\n\n" . $transcript . "\n\nPlease judge this debate now."]]
    ]
];

$result = callGemini($systemInstruction, $judgeInput);

if (!$result["success"]) {
    echo json_encode(["success" => false, "message" => $result["message"]]);
    return;
}

$verdictData = extractJsonFromText($result["text"]);

if (!$verdictData || !isset($verdictData["verdict"])) {
    echo json_encode(["success" => false, "message" => "The AI's verdict couldn't be read. Try again."]);
    return;
}

$verdict = $verdictData["verdict"];
$logicScore = isset($verdictData["logic_score"]) ? (int) $verdictData["logic_score"] : 50;
$rebuttalScore = isset($verdictData["rebuttal_score"]) ? (int) $verdictData["rebuttal_score"] : 50;
$evidenceScore = isset($verdictData["evidence_score"]) ? (int) $verdictData["evidence_score"] : 50;
$persuasionScore = isset($verdictData["persuasion_score"]) ? (int) $verdictData["persuasion_score"] : 50;
$overallScore = (int) round(($logicScore + $rebuttalScore + $evidenceScore + $persuasionScore) / 4);

$sql = "INSERT INTO cases(user_id, topic, user_stance, verdict, score, logic_score, rebuttal_score, evidence_score, persuasion_score)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$query = $mysql->prepare($sql);
$query->bind_param("isssiiiii", $user["id"], $topic, $userStance, $verdict, $overallScore, $logicScore, $rebuttalScore, $evidenceScore, $persuasionScore);
$query->execute();

echo json_encode([
    "success" => true,
    "verdict" => $verdict,
    "score" => $overallScore,
    "logic_score" => $logicScore,
    "rebuttal_score" => $rebuttalScore,
    "evidence_score" => $evidenceScore,
    "persuasion_score" => $persuasionScore
]);
?>
