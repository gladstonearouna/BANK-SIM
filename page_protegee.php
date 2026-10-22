<?php

session_start();

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Page protégée</title>
</head>

<body>

    <h1>BANK-SIM</h1>

    <h2>Bienvenue</h2>

    <p>
        Vous êtes connecté.
    </p>

</body>

</html>