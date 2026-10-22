
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

$client_id = (int) ($_SESSION["client_id"] ?? 0);

if ($client_id <= 0) {
    http_response_code(403);
    exit("Session client invalide.");
}

$message_beneficiaire = $_SESSION["message_beneficiaire"] ?? "";
unset($_SESSION["message_beneficiaire"]);

/*
 * Fonction de protection de l'affichage HTML.
 */
if (!function_exists("e")) {
    function e($valeur): string
    {
        return htmlspecialchars(
            (string) ($valeur ?? ""),
            ENT_QUOTES,
            "UTF-8"
        );
    }
}

/*
 * Création du jeton CSRF.
 */
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];

$numero_compte = "";
$message = "";
$type_message = "";
$compte_a_ajouter = null;

/*
 * Vérification du compte soumis.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token_recu = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token_recu) ||
        !hash_equals($_SESSION["csrf_token"], $token_recu)
    ) {
        http_response_code(403);
        exit("Requête refusée. Recharge la page et réessaie.");
    }

    $numero_compte = trim(
        (string) ($_POST["numero_compte"] ?? "")
    );

    if ($numero_compte === "") {

        $message = "Veuillez saisir un numéro de compte.";
        $type_message = "erreur";

    } else {

        /*
         * Rechercher un compte actif et son titulaire.
         */
        $sql = "SELECT
                    c.ID,
                    c.CLIENT_ID,
                    c.NUMERO_COMPTE,
                    cl.NOM
                FROM comptes c
                INNER JOIN clients cl
                    ON cl.ID = c.CLIENT_ID
                WHERE c.NUMERO_COMPTE = :numero
                  AND c.STATUT = 'ACTIF'
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":numero" => $numero_compte
        ]);

        $compte_trouve = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$compte_trouve) {

            $message = "Aucun compte actif ne correspond à ce numéro.";
            $type_message = "erreur";

        } elseif ((int) $compte_trouve["CLIENT_ID"] === $client_id) {

            $message = "Tu ne peux pas ajouter ton propre compte comme bénéficiaire.";
            $type_message = "erreur";

        } else {

            /*
             * Vérifier si le bénéficiaire existe déjà.
             */
            $sqlExiste = "SELECT ID
                          FROM beneficiaires
                          WHERE CLIENT_ID = :client_id
                            AND COMPTE_BENEFICIAIRE_ID = :compte_id
                          LIMIT 1";

            $stmtExiste = $pdo->prepare($sqlExiste);

            $stmtExiste->execute([
                ":client_id" => $client_id,
                ":compte_id" => (int) $compte_trouve["ID"]
            ]);

            if ($stmtExiste->fetch(PDO::FETCH_ASSOC)) {

                $message = "Ce bénéficiaire figure déjà dans ta liste.";
                $type_message = "erreur";

            } else {

                $message = "Compte vérifié. Titulaire : "
                    . $compte_trouve["NOM"]
                    . ". Tu peux maintenant ajouter ce bénéficiaire.";

                $type_message = "succes";

                $compte_a_ajouter = [
                    "ID" => (int) $compte_trouve["ID"],
                    "NUMERO_COMPTE" => $compte_trouve["NUMERO_COMPTE"],
                    "NOM" => $compte_trouve["NOM"]
                ];
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes bénéficiaires - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .beneficiaires-content {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        .beneficiaires-panel {
            width: 100%;
            max-width: 650px;
            margin: 25px auto;
            padding: 30px;
            box-sizing: border-box;
            background: #fff;
            border: 1px solid #e8e2d4;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(23, 43, 77, 0.07);
        }

        .beneficiaires-panel h3 {
            margin-top: 0;
            color: #172b4d;
        }

        .beneficiaires-panel p {
            color: #667085;
            line-height: 1.7;
        }

        .beneficiaires-form label {
            display: block;
            margin: 20px 0 8px;
            color: #172b4d;
            font-weight: 700;
        }

        .beneficiaires-form input[type="text"] {
            display: block;
            width: 100%;
            min-height: 49px;
            padding: 12px 14px;
            box-sizing: border-box;
            border: 1px solid #d8dce4;
            border-radius: 8px;
            background: #fff;
            color: #172b4d;
            font-family: inherit;
            font-size: 15px;
        }

        .beneficiaires-form input:focus {
            outline: none;
            border-color: #c9a227;
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.15);
        }

        .beneficiaires-form button,
        .beneficiaires-add-form button {
            display: block;
            width: 100%;
            min-height: 49px;
            margin-top: 20px;
            padding: 12px 16px;
            box-sizing: border-box;
            border: none;
            border-radius: 8px;
            background: #c9a227;
            color: #172b4d;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .beneficiaires-form button:hover,
        .beneficiaires-add-form button:hover {
            background: #b18c16;
        }

        .beneficiaires-message {
            max-width: 650px;
            margin: 20px auto;
            padding: 15px;
            box-sizing: border-box;
            border-radius: 10px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .beneficiaires-message.succes {
            background: #e8f7ed;
            border: 1px solid #9bd3ad;
            color: #185c30;
        }

        .beneficiaires-message.erreur {
            background: #fff0ef;
            border: 1px solid #e5aaa5;
            color: #8b2420;
        }

        .beneficiaire-info {
            margin-top: 20px;
            padding: 16px;
            border: 1px solid #e8e2d4;
            border-radius: 10px;
            background: #faf9f5;
            color: #172b4d;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .beneficiaires-back {
            display: inline-block;
            margin-top: 15px;
            color: #172b4d;
            font-weight: 600;
            text-decoration: none;
        }

        .beneficiaires-back:hover {
            color: #b18c16;
        }

        @media (max-width: 600px) {
            .beneficiaires-panel {
                padding: 22px 16px;
            }
        }
    </style>
</head>

<body class="premium-dashboard">

<div class="app-layout">

    <!-- MENU LATERAL BANK-SIM -->
    <aside class="sidebar">

        <div class="sidebar-brand">
            <div class="brand-icon">B</div>

            <div class="brand-text">
                <strong>BANK-SIM</strong>
                <span>Espace client</span>
            </div>
        </div>

        <p class="sidebar-label">MENU PRINCIPAL</p>

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="nav-link">
                <span class="nav-icon">🏠</span>
                <span>Accueil</span>
            </a>

            <a href="client_comptes.php" class="nav-link">
                <span class="nav-icon">💳</span>
                <span>Mes comptes</span>
            </a>

            <a href="client_informations.php" class="nav-link">
                <span class="nav-icon">👤</span>
                <span>Mes informations personnelles</span>
            </a>

            <a href="client_depot.php" class="nav-link">
                <span class="nav-icon">💰</span>
                <span>Dépôt</span>
            </a>

            <a href="client_retrait.php" class="nav-link">
                <span class="nav-icon">🏧</span>
                <span>Retrait</span>
            </a>

            <a href="client_virement.php" class="nav-link">
                <span class="nav-icon">💸</span>
                <span>Virement</span>
            </a>

            <a href="client_beneficiaires.php"
               class="nav-link active"
               aria-current="page">
                <span class="nav-icon">👥</span>
                <span>Mes bénéficiaires</span>
            </a>

            <a href="client_historique.php" class="nav-link">
                <span class="nav-icon">📊</span>
                <span>Historique</span>
            </a>

            <a href="client_notifications.php" class="nav-link">
                <span class="nav-icon">🔔</span>
                <span>Notifications</span>
            </a>

            <a href="client_demandes.php" class="nav-link">
                <span class="nav-icon">📝</span>
                <span>Mes demandes</span>
            </a>

            <a href="client_support.php" class="nav-link">
                <span class="nav-icon">🆘</span>
                <span>Support</span>
            </a>

            <a href="client_mes_support.php" class="nav-link">
                <span class="nav-icon">📂</span>
                <span>Mes tickets</span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a href="logout.php" class="nav-link">
                <span class="nav-icon">🚪</span>
                <span>Déconnexion</span>
            </a>
        </div>

    </aside>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <p class="topbar-label">BANK-SIM / Opérations</p>
                <h2>Mes bénéficiaires</h2>
            </div>
        </header>

        <section class="section-block beneficiaires-content">

            <div class="section-heading">
                <div>
                    <h2>Ajouter un bénéficiaire</h2>

                    <p>
                        Vérifie le numéro de compte avant d'ajouter
                        une personne à ta liste personnelle.
                    </p>
                </div>
            </div>

            <?php if ($message !== ""): ?>
                <div class="beneficiaires-message <?= e($type_message) ?>">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($message_beneficiaire !== ""): ?>
                <div class="beneficiaires-message succes">
                    <?= e($message_beneficiaire) ?>
                </div>
            <?php endif; ?>

            <div class="beneficiaires-panel">

                <h3>Vérifier un compte</h3>

                <p>
                    Saisis le numéro de compte de la personne.
                    Seuls les comptes actifs appartenant à un autre
                    client peuvent être ajoutés.
                </p>

                <form
                    class="beneficiaires-form"
                    method="POST"
                    action="client_beneficiaires.php"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrf_token) ?>"
                    >

                    <label for="numero_compte">
                        Numéro de compte du bénéficiaire
                    </label>

                    <input
                        type="text"
                        id="numero_compte"
                        name="numero_compte"
                        maxlength="30"
                        value="<?= e($numero_compte) ?>"
                        placeholder="Saisir le numéro de compte"
                        required
                    >

                    <button type="submit">
                        Vérifier le compte
                    </button>

                </form>

                <?php if ($compte_a_ajouter !== null): ?>

                    <div class="beneficiaire-info">

                        <strong>Compte vérifié</strong><br>

                        Titulaire :
                        <?= e($compte_a_ajouter["NOM"]) ?><br>

                        Numéro de compte :
                        <?= e($compte_a_ajouter["NUMERO_COMPTE"]) ?>

                    </div>

                    <form
                        class="beneficiaires-add-form"
                        method="POST"
                        action="ajouter_beneficiaire.php"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrf_token) ?>"
                        >

                        <input
                            type="hidden"
                            name="compte_beneficiaire_id"
                            value="<?= (int) $compte_a_ajouter["ID"] ?>"
                        >

                        <button type="submit">
                            Ajouter ce bénéficiaire
                        </button>

                    </form>

                <?php endif; ?>

            </div>

            <a href="client_virement.php" class="beneficiaires-back">
                ← Retour aux virements
            </a>

        </section>

        <footer class="dashboard-footer">
            BANK-SIM — Espace sécurisé
        </footer>

    </main>

</div>

</body>
</html>
