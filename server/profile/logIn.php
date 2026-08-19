<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["email"])) {
    $email = $_POST["email"];
}
else {
    $email = "";
    exit;
}

if (isset($_POST["password"])) {
    $password = $_POST["password"];
}
else {
    $password = "";
    exit;
}

$sql = "SELECT * FROM users WHERE email = ?";
$query = $mysql->prepare($sql);
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    exit;
}

if (!password_verify($password, $user["password"])) {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Login successful!",
    "user" => [
        "id" => $user["id"],
        "username" => $user["username"],
        "email" => $user["email"],
        "token" => $user["token"]
    ]
]);
?>
