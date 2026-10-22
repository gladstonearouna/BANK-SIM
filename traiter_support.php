<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Méthode non autorisée.");
}

$client_id = $_SESSION["client_id"];

$sujet = trim($_POST["sujet"] ?? "");
$message = trim($_POST["message"] ?? "");

if ($sujet === "") {
    die("Le sujet est obligatoire.");
}

if ($message === "") {
    die("Le message est obligatoire.");
}

if (strlen($sujet) > 150) {
    die("Le sujet est trop long.");
}

$sql = "INSERT INTO support
        (
            CLIENT_ID,
            SUJET,
            MESSAGE,
            STATUT
        )
        VALUES
        (
            :client_id,
            :sujet,
            :message,
            'OUVERT'
        )";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":client_id" => $client_id,
    ":sujet" => $sujet,
    ":message" => $message
]);

$_SESSION["message_succes"] =
    "Votre demande de support a été envoyée avec succès.";

header("Location: client_support.php");
exit;