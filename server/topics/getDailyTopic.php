<?php
include(__DIR__ . "/../database/connection.php");

$sql = "SELECT id, categories, topic, description FROM daily_topics WHERE used_on = CURDATE()";
$query = $mysql->prepare($sql);
$query->execute();
$result = $query->get_result();
$todaysTopic = $result->fetch_assoc();

if (!$todaysTopic) {
    $sql = "SELECT id, categories, topic, description FROM daily_topics WHERE used_on IS NULL ORDER BY RAND() LIMIT 1";
    $query = $mysql->prepare($sql);
    $query->execute();
    $result = $query->get_result();
    $todaysTopic = $result->fetch_assoc();

    if (!$todaysTopic) {
        $sql = "UPDATE daily_topics SET used_on = NULL";
        $mysql->query($sql);

        $sql = "SELECT id, categories, topic, description FROM daily_topics ORDER BY RAND() LIMIT 1";
        $query = $mysql->prepare($sql);
        $query->execute();
        $result = $query->get_result();
        $todaysTopic = $result->fetch_assoc();
    }

    if (!$todaysTopic) {
        echo json_encode(["success" => false, "message" => "No topics available."]);
        return;
    }

    $sql = "UPDATE daily_topics SET used_on = CURDATE() WHERE id = ?";
    $query = $mysql->prepare($sql);
    $query->bind_param("i", $todaysTopic["id"]);
    $query->execute();
}

echo json_encode(["success" => true, "topic" => $todaysTopic]);
?>
