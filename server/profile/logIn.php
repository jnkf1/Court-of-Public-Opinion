<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["email"])) {
    $email = $_POST["email"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing email."]);
    return;
}

if (isset($_POST["password"])) {
    $password = $_POST["password"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing password."]);
    return;
}

$sql = "SELECT * FROM users WHERE email = ?";
$query = $mysql->prepare($sql);
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    return;
}

if (!password_verify($password, $user["password"])) {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    return;
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
