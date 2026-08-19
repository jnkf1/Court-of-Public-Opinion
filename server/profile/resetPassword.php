<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["email"])) {
    $email = $_POST["email"];
}
else {
    $email = "";
    exit;
}

if (isset($_POST["newPassword"])) {
    $newPassword = $_POST["newPassword"];
}
else {
    $newPassword = "";
    exit;
}

$sql = "SELECT id FROM users WHERE email = ?";
$query = $mysql->prepare($sql);
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "No account found with that email."]);
    exit;
}

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = ? WHERE id = ?";
$query = $mysql->prepare($sql);
$query->bind_param("si", $hashedPassword, $user["id"]);
$query->execute();

echo json_encode(["success" => true, "message" => "Password reset successfully!"]);
?>
