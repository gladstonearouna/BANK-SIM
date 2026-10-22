
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

/*
 * Vérifier l'identifiant du client connecté.
 */
if (
    !isset($_SESSION["client_id"]) ||
    !is_numeric($_SESSION["client_id"])
) {
    header("Location: login.php");
    exit;
}

$client_id = (int) $_SESSION["client_id"];

/*
 * Fonction pour sécuriser l'affichage HTML.
 */
if (!function_exists("e")) {
    function e($valeur): string
    {
        return htmlspecialchars(
            (string) $valeur,
            ENT_QUOTES,
            "UTF-8"
        );
    }
}

/*
 * Récupérer les informations du client connecté.
 */
$sql = "SELECT ID, NOM
        FROM clients
        WHERE ID = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":id" => $client_id
]);

$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    http_response_code(404);
    exit("Client introuvable.");
}

/*
 * Récupérer uniquement les comptes du client connecté.
 */
$sql = "SELECT
            ID,
            NUMERO_COMPTE,
            TYPE_COMPTE,
            SOLDE,
            STATUT,
            DATE_CREATION
        FROM comptes
        WHERE CLIENT_ID = :client_id
        ORDER BY ID ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":client_id" => $client_id
]);

$comptes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
 * Calculer le solde total des comptes.
 */
$soldeTotal = 0;

foreach ($comptes as $compte) {
    $soldeTotal += (float) $compte["SOLDE"];
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes comptes - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .accounts-content {
            max-width: 1100px;
            margin: 0 auto;
        }

        .accounts-intro {
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 26px;
        }

        .accounts-total {
            padding: 26px 30px;
            margin-bottom: 30px;
            border-radius: 18px;
            background: #142744;
            color: #ffffff;
            box-shadow: 0 8px 28px rgba(18, 35, 65, 0.12);
        }

        .accounts-total h2 {
            margin: 0 0 12px;
            color: #e6c783;
            font-size: 15px;
            font-weight: 600;
        }

        .accounts-total p {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .accounts-heading {
            color: #142744;
            margin: 0 0 18px;
        }

        .accounts-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .account-card {
            min-width: 0;
            padding: 24px;
            background: #ffffff;
            border: 1px solid #eee4d0;
            border-radius: 16px;
            box-shadow: 0 6px 22px rgba(18, 35, 65, 0.06);
        }

        .account-card h3 {
            margin: 0 0 20px;
            color: #142744;
            font-size: 19px;
        }

        .account-number {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .account-balance {
            margin: 0 0 22px;
            color: #142744;
            font-size: 25px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .account-details {
            display: grid;
            gap: 13px;
            margin: 0;
        }

        .account-details div {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f1f4;
        }

        .account-details dt {
            color: #6b7280;
        }

        .account-details dd {
            margin: 0;
            color: #26364d;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .account-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #edf2f7;
            color: #34445b;
            font-size: 12px;
            font-weight: 700;
        }

        .account-status.active {
            background: #e5f5eb;
            color: #21683a;
        }

        .account-history {
            display: inline-block;
            margin-top: 20px;
            color: #142744;
            font-weight: 600;
            text-decoration: none;
        }

        .account-history:hover,
        .accounts-back:hover {
            text-decoration: underline;
        }

        .accounts-empty {
            padding: 26px;
            background: #ffffff;
            border: 1px solid #eee4d0;
            border-radius: 16px;
        }

        .accounts-empty h3 {
            margin-top: 0;
            color: #142744;
        }

        .accounts-back {
            display: inline-block;
            margin-top: 26px;
            color: #142744;
            font-weight: 600;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .accounts-grid {
                grid-template-columns: 1fr;
            }

            .accounts-total {
                padding: 22px;
            }

            .accounts-total p {
                font-size: 27px;
            }

            .account-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body class="premium-dashboard">

<div class="app-layout">

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

            <a
                href="client_comptes.php"
                class="nav-link active"
                aria-current="page"
            >
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

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Mes comptes</h1>
                <p>
                    Bonjour <?= e($client["NOM"]) ?>,
                    voici vos comptes bancaires.
                </p>
            </div>
        </header>

        <section class="section-block accounts-content">

            <p class="accounts-intro">
                Consultez les soldes, les statuts et les informations
                de vos comptes BANK-SIM.
            </p>

            <section class="accounts-total">
                <h2>Solde total de vos comptes</h2>

                <p>
                    <?= number_format(
                        $soldeTotal,
                        2,
                        ',',
                        ' '
                    ) ?> MAD
                </p>
            </section>

            <?php if (empty($comptes)): ?>

                <section class="accounts-empty">
                    <h3>Aucun compte bancaire</h3>

                    <p>
                        Vous ne possédez actuellement aucun compte.
                        Contactez votre conseiller pour plus d'informations.
                    </p>
                </section>

            <?php else: ?>

                <h2 class="accounts-heading">Vos comptes</h2>

                <div class="accounts-grid">

                    <?php foreach ($comptes as $compte): ?>

                        <?php
                        $statut = strtoupper(
                            (string) $compte["STATUT"]
                        );

                        $statutActif = ($statut === "ACTIF");
                        ?>

                        <article class="account-card">

                            <h3>
                                <?= e($compte["TYPE_COMPTE"]) ?>
                            </h3>

                            <p class="account-number">
                                Numéro de compte
                            </p>

                            <p>
                                <?= e($compte["NUMERO_COMPTE"]) ?>
                            </p>

                            <p class="account-number">
                                Solde disponible affiché
                            </p>

                            <p class="account-balance">
                                <?= number_format(
                                    (float) $compte["SOLDE"],
                                    2,
                                    ',',
                                    ' '
                                ) ?> MAD
                            </p>

                            <dl class="account-details">

                                <div>
                                    <dt>Statut</dt>

                                    <dd>
                                        <span class="account-status <?= $statutActif ? 'active' : '' ?>">
                                            <?= e($statut) ?>
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt>Création</dt>

                                    <dd>
                                        <?= e($compte["DATE_CREATION"]) ?>
                                    </dd>
                                </div>

                            </dl>

                            <a
                                href="client_historique.php"
                                class="account-history"
                            >
                                📊 Voir les opérations →
                            </a>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <a href="dashboard.php" class="accounts-back">
                ← Retour au tableau de bord
            </a>

        </section>

    </main>

</div>

</body>
</html>
