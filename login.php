<?php

session_start();

// Générer le jeton CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];
$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf_recu = $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrf_recu) ||
        !hash_equals($_SESSION["csrf_token"], $csrf_recu)
    ) {
        http_response_code(403);
        exit("Requête refusée. Rechargez la page et réessayez.");
    }

    $email = trim($_POST["email"] ?? "");
    $mot_de_passe = $_POST["mot_de_passe"] ?? "";

    // Compte fictif pour la démonstration
    $email_demo = "demo@banksim.test";
    $mot_de_passe_demo = "DemoBank2026!";

    if (
        is_string($email) &&
        is_string($mot_de_passe) &&
        hash_equals($email_demo, $email) &&
        hash_equals($mot_de_passe_demo, $mot_de_passe)
    ) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = "demo-001";
        $_SESSION["client_id"] = "demo-client-001";
        $_SESSION["role"] = "CLIENT";
        $_SESSION["demo_mode"] = true;

        $_SESSION["demo_user"] = [
            "nom" => "Client Démonstration",
            "email" => $email_demo,
            "numero_compte" => "BS-DEMO-0001",
            "solde" => 10000.00,
            "devise" => "MAD"
        ];

        header("Location: dashboard.php");
        exit;
    }

    $erreur = "Email ou mot de passe incorrect. Utilisez le compte de démonstration.";
}
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Connexion - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="page-connexion">

    <main class="connexion-container">

        <section class="connexion-decoration">
            <div class="logo-banksim">
                <span class="logo-symbole">B</span>
                <span>BANK-SIM</span>
            </div>

            <div class="texte-bienvenue">
                <p class="surtitre">VOTRE ESPACE BANCAIRE</p>

                <h1>Bienvenue chez BANK-SIM</h1>

                <p>
                    Gérez vos opérations bancaires dans un espace
                    conçu pour allier simplicité, élégance et sécurité.
                </p>
            </div>

            <p class="mention-securite">
                Votre espace bancaire, en toute simplicité.
            </p>
        </section>

        <section class="connexion-formulaire">

            <div class="formulaire-entete">
                <p class="surtitre">RAVI DE VOUS REVOIR</p>
                <h2>Connexion</h2>

                <p class="description-connexion">
                    Entrez vos identifiants pour accéder à votre espace.
                </p>
            </div>

            <?php if ($erreur !== ""): ?>
                <p class="message-erreur" role="alert">
                    <?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>"
                >

                <div class="champ-formulaire">
                    <label for="email">Adresse email</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="exemple@email.com"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="champ-formulaire">
                    <label for="mot_de_passe">Mot de passe</label>

                    <input
                        type="password"
                        id="mot_de_passe"
                        name="mot_de_passe"
                        placeholder="Saisissez votre mot de passe"
                        autocomplete="current-password"
                        required
                    >
                </div>
                
<div class="lien-mot-de-passe-oublie">
    <a href="mot_de_passe_oublie.php">
        Mot de passe oublié ?
    </a>
</div>


                <button type="submit" class="btn-principal btn-connexion">
                    Se connecter
                    <span aria-hidden="true">→</span>
                </button>

            </form>

            <p class="pied-connexion">
                <span class="point-dore"></span>
                Accès réservé aux utilisateurs autorisés
            </p>

        </section>

    </main>

</body>

</html>
<!DOCTYPE html>
