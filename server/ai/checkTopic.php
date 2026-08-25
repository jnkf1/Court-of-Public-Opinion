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

$systemInstruction = "You are checking whether a proposed debate topic is genuinely debatable - meaning it has two reasonable opposing sides, isn't gibberish, isn't just a statement of pure fact, and isn't nonsensical. " .
    "Respond with ONLY valid JSON in exactly this format, no other text: " .
    '{"debatable": true, "reason": "short reason"}';

$input = [
    ["type" => "user_input", "content" => [["type" => "text", "text" => "Topic: " . $topic]]]
];

$result = callGemini($systemInstruction, $input);

if (!$result["success"]) {
    // The check itself failed (e.g. quota) - don't block the user over that, let the topic through
    echo json_encode(["success" => true, "debatable" => true]);
    return;
}

$data = extractJsonFromText($result["text"]);

if (!$data || !isset($data["debatable"])) {
    echo json_encode(["success" => true, "debatable" => true]);
    return;
}

echo json_encode([
    "success" => true,
    "debatable" => (bool) $data["debatable"],
    "reason" => isset($data["reason"]) ? $data["reason"] : ""
]);
?>
