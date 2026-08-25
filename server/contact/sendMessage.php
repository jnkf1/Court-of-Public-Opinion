<?php
include(__DIR__ . "/../database/connection.php");

if (isset($_POST["name"])) {
    $name = $_POST["name"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing name."]);
    return;
}

if (isset($_POST["email"])) {
    $email = $_POST["email"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing email."]);
    return;
}

if (isset($_POST["message"])) {
    $message = $_POST["message"];
}
else {
    echo json_encode(["success" => false, "message" => "Missing message."]);
    return;
}

$sql = "INSERT INTO contact_messages(name, email, message) VALUES (?, ?, ?)";
$query = $mysql->prepare($sql);
$query->bind_param("sss", $name, $email, $message);
$query->execute();

if ($query->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Message sent! We'll get back to you soon."]);
} else {
    echo json_encode(["success" => false, "message" => "Something went wrong."]);
}
?>
