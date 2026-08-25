<?php
include(__DIR__ . "/../database/connection.php");
include(__DIR__ . "/api.php");

if (isset($_POST["topic"])) {
    $topic = $_POST["topic"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing topic."]);
    return;
}

if (isset($_POST["aiStance"])) {
    $aiStance = $_POST["aiStance"];
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

if (isset($_POST["message"])) {
    $message = $_POST["message"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing message."]);
    return;
}

$systemInstruction = "You are debating the topic '" . $topic . "'. You must argue the " . $aiStance . " position, no matter what. " .
    "Respond as a skilled debater directly countering the user's most recent point. " .
    "Keep each response to 2-4 sentences - this is one turn in a back-and-forth debate, not an essay. " .
    "Never break character or mention that you are an AI.";

$history[] = [
    "type" => "user_input",
    "content" => [["type" => "text", "text" => $message]]
];

$result = callGemini($systemInstruction, $history);

if (!$result["success"]) {
    echo json_encode(["success" => false, "message" => $result["message"]]);
    return;
}

echo json_encode(["success" => true, "reply" => $result["text"]]);
?>
