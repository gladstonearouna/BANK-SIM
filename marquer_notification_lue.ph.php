<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Méthode non autorisée.");
}

$client_id = $_SESSION["client_id"];

$notification_id = filter_input(
    INPUT_POST,
    "notification_id",
    FILTER_VALIDATE_INT
);

if (!$notification_id) {
    die("Notification invalide.");
}

$sql = "UPDATE notifications
        SET LUE = 1
        WHERE ID = :notification_id
        AND CLIENT_ID = :client_id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":notification_id" => $notification_id,
    ":client_id" => $client_id
]);

header("Location: client_notifications.php");
exit;