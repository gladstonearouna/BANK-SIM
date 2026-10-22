<?php

function requireRole($role)
{
    if (!isset($_SESSION["role"])) {
        header("Location: login.php");
        exit;
    }

    if ($_SESSION["role"] !== $role) {
        die("Accès refusé.");
    }
}

?>