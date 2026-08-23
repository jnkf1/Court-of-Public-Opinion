<?php
include(__DIR__ . "/../database/connection.php");

$sql = "SELECT topic FROM daily_topics ORDER BY RAND() LIMIT 1";
$query = $mysql->prepare($sql);
$query->execute();
$result = $query->get_result();
$randomTopic = $result->fetch_assoc();

if (!$randomTopic) {
    echo json_encode(["success" => false, "message" => "No topics available."]);
    exit;
}

echo json_encode(["success" => true, "topic" => $randomTopic["topic"]]);
?>
