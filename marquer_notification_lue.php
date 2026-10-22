<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

// Vérifier le jeton CSRF
$csrf_recu = $_POST["csrf_token"] ?? "";
$csrf_session = $_SESSION["csrf_token"] ?? "";

if (
    !is_string($csrf_recu) ||
    !is_string($csrf_session) ||
    $csrf_session === "" ||
    !hash_equals($csrf_session, $csrf_recu)
) {
    http_response_code(403);
    exit("Requête refusée. Veuillez recharger la page et réessayer.");
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