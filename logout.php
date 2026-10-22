```php
<?php

session_start();

// Vider les données de session
$_SESSION = [];

// Supprimer le cookie de session du navigateur
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        [
            "expires" => time() - 42000,
            "path" => $params["path"],
            "domain" => $params["domain"],
            "secure" => $params["secure"],
            "httponly" => $params["httponly"],
            "samesite" => $params["samesite"] ?? "Lax"
        ]
    );
}

// Détruire la session côté serveur
session_destroy();

// Retour à la connexion
header("Location: login.php");
exit;
