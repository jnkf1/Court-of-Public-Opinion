<?php
include(__DIR__ . "/database/connection.php");

if (isset($_POST["username"])) {
    $username = $_POST["username"];
}
else {
    $username = "";
    exit;
}

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

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users(username, email, password) VALUES (?, ?, ?)";
$query = $mysql->prepare($sql);
$query->bind_param("sss", $username, $email, $hashedPassword);
$query->execute();

if ($query->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Account created successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Something went wrong."]);
}
?>