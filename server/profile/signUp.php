<?php
include(__DIR__ . "/../database/connection.php");

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
$token = bin2hex(random_bytes(32));

$sql = "INSERT INTO users(username, email, password, token) VALUES (?, ?, ?, ?)";
$query = $mysql->prepare($sql);
$query->bind_param("ssss", $username, $email, $hashedPassword, $token);

try {
    $query->execute();
}
catch (mysqli_sql_exception $e) {
    if ($mysql->errno === 1062) {
        echo json_encode(["success" => false, "message" => "That username or email is already taken."]);
    }
    else {
        echo json_encode(["success" => false, "message" => "Something went wrong."]);
    }
    exit;
}

if ($query->affected_rows > 0) {
    echo json_encode([
        "success" => true,
        "message" => "Account created successfully!",
        "user" => [
            "id" => $mysql->insert_id,
            "username" => $username,
            "email" => $email,
            "token" => $token
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Something went wrong."]);
}
?>