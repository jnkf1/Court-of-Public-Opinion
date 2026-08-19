<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["token"])) {
    $token = $_POST["token"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing token."]);
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

$user_id = $user["id"];

$sql = "DELETE FROM users WHERE id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("i", $user_id);

try {
    $query->execute();
}
catch (mysqli_sql_exception $e) {
    if ($mysql->errno === 1451) {
        echo json_encode(["success" => false, "message" => "Can't delete an account with existing case history."]);
    }
    else {
        echo json_encode(["success" => false, "message" => "Something went wrong."]);
    }
    exit;
}

echo json_encode(["success" => true, "message" => "Account deleted."]);
?>
