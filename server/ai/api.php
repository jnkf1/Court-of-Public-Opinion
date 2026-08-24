<?php
include(__DIR__ . "/config.php");

function callGemini($systemInstruction, $conversationHistory) {
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/interactions");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-goog-api-key: " . GEMINI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "gemini-3.6-flash",
        "system_instruction" => $systemInstruction,
        "store" => false,
        "input" => $conversationHistory
    ]));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 429) {
        return ["success" => false, "message" => "The AI is getting a lot of requests right now. Please wait a moment and try again."];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 || !isset($data["steps"])) {
        return ["success" => false, "message" => "The AI couldn't respond. Try again."];
    }

    foreach ($data["steps"] as $step) {
        if ($step["type"] === "model_output") {
            return ["success" => true, "text" => $step["content"][0]["text"]];
        }
    }

    return ["success" => false, "message" => "The AI couldn't respond. Try again."];
}

function extractJsonFromText($text) {
    $start = strpos($text, "{");
    $end = strrpos($text, "}");

    if ($start === false || $end === false) {
        return null;
    }

    $jsonText = substr($text, $start, $end - $start + 1);
    $parsed = json_decode($jsonText, true);

    return $parsed;
}
?>
