<?php
include(__DIR__ . "/../database/connection.php");

$sql = "SELECT id, topic, verdict, DATE_FORMAT(created_at, '%M %e, %Y') AS case_date
        FROM cases
        WHERE room_id IS NULL
        ORDER BY created_at DESC";
$query = $mysql->prepare($sql);
$query->execute();
$result = $query->get_result();
$cases = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(["success" => true, "cases" => $cases]);
?>
