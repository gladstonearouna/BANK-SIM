
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

// Vérifier l'identifiant du client connecté.
if (
    !isset($_SESSION["client_id"]) ||
    !filter_var($_SESSION["client_id"], FILTER_VALIDATE_INT) ||
    (int) $_SESSION["client_id"] <= 0
) {
    http_response_code(403);
    exit("Accès refusé.");
}

$client_id = (int) $_SESSION["client_id"];

// Fonction de protection de l'affichage HTML.
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

// Créer le jeton CSRF.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];

// Récupérer les comptes actifs du client connecté.
$sql = "SELECT ID, NUMERO_COMPTE, TYPE_COMPTE, SOLDE
        FROM comptes
        WHERE CLIENT_ID = :client_id
          AND STATUT = 'ACTIF'
        ORDER BY ID ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":client_id" => $client_id
]);

$comptes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les bénéficiaires enregistrés par le client.
// Ici, ID désigne l'identifiant du compte bénéficiaire.
$sqlBeneficiaires = "
    SELECT
        c.ID AS ID,
        c.NUMERO_COMPTE,
        cl.NOM
    FROM beneficiaires b
    INNER JOIN comptes c
        ON c.ID = b.COMPTE_BENEFICIAIRE_ID
    INNER JOIN clients cl
        ON cl.ID = c.CLIENT_ID
    WHERE b.CLIENT_ID = :client_id
      AND c.STATUT = 'ACTIF'
      AND c.CLIENT_ID != :client_id2
    ORDER BY cl.NOM ASC, c.NUMERO_COMPTE ASC
";

$stmtBeneficiaires = $pdo->prepare($sqlBeneficiaires);
$stmtBeneficiaires->execute([
    ":client_id" => $client_id,
    ":client_id2" => $client_id
]);

$beneficiaires = $stmtBeneficiaires->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le message transmis après un traitement.
$message = $_SESSION["message_virement"] ?? "";
unset($_SESSION["message_virement"]);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Virement - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .transfer-content {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }

        .transfer-description {
            color: #667085;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .transfer-panel {
            width: 100%;
            max-width: 720px;
            margin: 25px auto;
            padding: 32px;
            box-sizing: border-box;
            background: #ffffff;
            border: 1px solid #e8e2d4;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(23, 43, 77, 0.07);
        }

        .transfer-panel h3 {
            margin: 0 0 10px;
            color: #172b4d;
            font-size: 21px;
        }

        .transfer-panel .form-intro {
            margin: 0 0 25px;
            color: #667085;
            line-height: 1.6;
        }

        .transfer-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .transfer-form label {
            display: block;
            margin: 12px 0 5px;
            color: #172b4d;
            font-size: 14px;
            font-weight: 700;
        }

        .transfer-form select,
        .transfer-form input[type="number"] {
            display: block;
            width: 100%;
            min-height: 49px;
            padding: 12px 14px;
            box-sizing: border-box;
            border: 1px solid #d8dce4;
            border-radius: 8px;
            background: #ffffff;
            color: #172b4d;
            font-family: inherit;
            font-size: 15px;
        }

        .transfer-form select:focus,
        .transfer-form input[type="number"]:focus {
            outline: none;
            border-color: #c9a227;
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.15);
        }

        .transfer-form button {
            display: block;
            width: 100%;
            min-height: 50px;
            margin-top: 24px;
            padding: 13px 18px;
            box-sizing: border-box;
            border: none;
            border-radius: 8px;
            background: #c9a227;
            color: #172b4d;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .transfer-form button:hover {
            background: #b18c16;
        }

        .transfer-message,
        .transfer-empty {
            max-width: 720px;
            margin: 20px auto;
            padding: 18px;
            box-sizing: border-box;
            border: 1px solid #e8d69a;
            border-radius: 10px;
            background: #fff8e5;
            color: #172b4d;
            overflow-wrap: anywhere;
            line-height: 1.7;
        }

        .transfer-back {
            display: inline-block;
            margin-top: 10px;
            color: #172b4d;
            font-weight: 600;
            text-decoration: none;
        }

        .transfer-back:hover {
            color: #b18c16;
        }

        @media (max-width: 600px) {
            .transfer-panel {
                padding: 22px 16px;
                margin: 18px auto;
            }

            .transfer-panel h3 {
                font-size: 19px;
            }
        }
    </style>
</head>

<body class="premium-dashboard">

<div class="app-layout">

    <!-- SIDEBAR BANK-SIM -->
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

            <a href="client_virement.php"
               class="nav-link active"
               aria-current="page">
                <span class="nav-icon">💸</span>
                <span>Virement</span>
            </a>

            <a href="client_beneficiaires.php" class="nav-link">
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
                <h2>Effectuer un virement</h2>
            </div>
        </header>

        <section class="section-block transfer-content">

            <div class="section-heading">
                <div>
                    <h2>Nouveau virement</h2>

                    <p class="transfer-description">
                        Transfère de l'argent vers un bénéficiaire
                        que tu as préalablement enregistré.
                    </p>
                </div>
            </div>

            <?php if ($message !== ""): ?>
                <div class="transfer-message" role="status">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($comptes)): ?>

                <div class="transfer-empty">
                    <h3>Aucun compte actif</h3>

                    <p>
                        Tu ne possèdes aucun compte actif permettant
                        d'effectuer un virement.
                    </p>
                </div>

            <?php elseif (empty($beneficiaires)): ?>

                <div class="transfer-empty">
                    <h3>Aucun bénéficiaire enregistré</h3>

                    <p>
                        Tu dois d'abord ajouter un bénéficiaire
                        avant de pouvoir effectuer un virement.
                    </p>

                    <a
                        href="client_beneficiaires.php"
                        class="transfer-back"
                    >
                        Ajouter un bénéficiaire →
                    </a>
                </div>

            <?php else: ?>

                <div class="transfer-panel">

                    <h3>Informations du virement</h3>

                    <p class="form-intro">
                        Vérifie les informations avant de poursuivre.
                    </p>

                    <form
                        class="transfer-form"
                        method="POST"
                        action="traiter_virement.php"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrf_token) ?>"
                        >

                        <label for="compte_id">
                            Compte à débiter
                        </label>

                        <select
                            id="compte_id"
                            name="compte_id"
                            required
                        >
                            <option value="">
                                -- Choisir un compte --
                            </option>

                            <?php foreach ($comptes as $compte): ?>

                                <option value="<?= (int) $compte["ID"] ?>">
                                    <?= e($compte["NUMERO_COMPTE"]) ?>
                                    — <?= e($compte["TYPE_COMPTE"]) ?>
                                    — Solde :
                                    <?= number_format(
                                        (float) $compte["SOLDE"],
                                        2,
                                        ",",
                                        " "
                                    ) ?> MAD
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <label for="beneficiaire_id">
                            Bénéficiaire
                        </label>

                        <select
                            id="beneficiaire_id"
                            name="beneficiaire_id"
                            required
                        >
                            <option value="">
                                -- Choisir un bénéficiaire --
                            </option>

                            <?php foreach ($beneficiaires as $beneficiaire): ?>

                                <option
                                    value="<?= (int) $beneficiaire["ID"] ?>"
                                >
                                    <?= e($beneficiaire["NOM"]) ?>
                                    — Compte :
                                    <?= e($beneficiaire["NUMERO_COMPTE"]) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <label for="montant">
                            Montant à transférer (MAD)
                        </label>

                        <input
                            type="number"
                            id="montant"
                            name="montant"
                            min="0.01"
                            max="100000"
                            step="0.01"
                            placeholder="Ex. : 60"
                            required
                        >

                        <button type="submit">
                            Continuer le virement
                        </button>

                    </form>

                </div>

            <?php endif; ?>

            <a href="dashboard.php" class="transfer-back">
                ← Retour au tableau de bord
            </a>

        </section>

        <footer class="dashboard-footer">
            BANK-SIM — Espace sécurisé
        </footer>

    </main>

</div>

</body>
</html>