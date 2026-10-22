
<?php
session_start();

if (empty($_SESSION["demande_envoyee"])) {
    header("Location: index.php");
    exit;
}

unset($_SESSION["demande_envoyee"]);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande en attente - BANK-SIM</title>
    <link rel="stylesheet" href="demande.css">
</head>

<body>

    <header>
        <h1>BANK-SIM</h1>
        <p>Votre confiance, notre engagement</p>
    </header>

    <main class="confirmation">
        <div class="symbole">✓</div>

        <h2>Demande envoyée avec succès</h2>

        <p>
            Votre demande d'ouverture de compte a bien été enregistrée.
        </p>

        <p>
            <strong>Statut : En attente de validation</strong>
        </p>

        <p>
            Un conseiller BANK-SIM devra examiner votre demande.
            Vous pourrez accéder à votre espace client après son acceptation
            et la création de votre compte.
        </p>

        <p><a href="index.php">Retour à l'accueil</a></p>
    </main>

</body>
</html>
