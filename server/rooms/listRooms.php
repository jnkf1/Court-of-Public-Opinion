<?php
include(__DIR__ . "/../database/connection.php");

$sql = "SELECT rooms.id, rooms.topic, rooms.host_stance, users.username AS host_username
        FROM rooms
        JOIN users ON rooms.host_id = users.id
        WHERE rooms.status = 'open'
        ORDER BY rooms.created_at DESC";
$query = $mysql->prepare($sql);
$query->execute();
$result = $query->get_result();
$rooms = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(["success" => true, "rooms" => $rooms]);
?>
