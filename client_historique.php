
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

$client_id = (int) ($_SESSION["client_id"] ?? 0);

if ($client_id <= 0) {
    die("Session client invalide.");
}

/*
 * Protection de l'affichage HTML.
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
 * Traduction des types de transactions.
 */
function libelleTransaction(string $type): string
{
    switch ($type) {
        case "VIREMENT_ENVOYE":
            return "Virement envoyé";

        case "VIREMENT_RECU":
            return "Virement reçu";

        case "DEPOT":
            return "Dépôt";

        case "RETRAIT":
            return "Retrait";

        default:
            return $type;
    }
}

/*
 * Informations du client connecté.
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
 * Historique des transactions du client connecté.
 */
$sql = "SELECT ID, TYPE, MONTANT, DATE_TRANSACTION
        FROM transactions
        WHERE CLIENT_ID = :client_id
        ORDER BY DATE_TRANSACTION DESC, ID DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":client_id" => $client_id
]);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Historique - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .history-content {
            max-width: 1100px;
            margin: 0 auto;
        }

        .history-description {
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .history-panel {
            padding: 26px;
            background: #ffffff;
            border: 1px solid #eee4d0;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(18, 35, 65, 0.07);
        }

        .history-panel h2,
        .history-empty h2 {
            margin-top: 0;
            color: #142744;
        }

        .history-table-wrapper {
            width: 100%;
            overflow-x: auto;
            margin-top: 22px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .history-table th {
            padding: 15px 14px;
            background: #142744;
            color: #ffffff;
            font-size: 13px;
            white-space: nowrap;
        }

        .history-table td {
            padding: 15px 14px;
            border-bottom: 1px solid #edf0f4;
            color: #26364d;
            vertical-align: middle;
        }

        .history-table tbody tr:hover {
            background: #faf8f2;
        }

        .history-type {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            background: #f4f0e6;
            color: #6f5524;
            font-weight: 600;
            font-size: 13px;
        }

        .history-amount {
            font-weight: 700;
            white-space: nowrap;
        }

        .history-date {
            white-space: nowrap;
            color: #596579;
        }

        .history-empty {
            padding: 26px;
            background: #ffffff;
            border: 1px solid #eee4d0;
            border-radius: 16px;
            box-shadow: 0 8px 28px rgba(18, 35, 65, 0.05);
        }

        .history-empty p {
            color: #6b7280;
            line-height: 1.7;
        }

        .history-back {
            display: inline-block;
            margin-top: 24px;
            color: #142744;
            font-weight: 600;
            text-decoration: none;
        }

        .history-back:hover {
            color: #a7833d;
            text-decoration: underline;
        }

        /* Affichage sur les petits écrans */
        @media (max-width: 768px) {
            .history-panel {
                padding: 18px 14px;
            }

            .history-table th,
            .history-table td {
                padding: 12px 10px;
            }
        }
    </style>
</head>

<body class="premium-dashboard">

<div class="app-layout">

    <!-- SIDEBAR -->
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

    <a href="client_beneficiaires.php" class="nav-link">
        <span class="nav-icon">👥</span>
        <span>Mes bénéficiaires</span>
    </a>

    <a href="client_historique.php"
       class="nav-link active"
       aria-current="page">
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
                <h1>Mon historique</h1>

                <p>
                    Bonjour <?= e($client["NOM"]) ?>,
                    retrouvez vos opérations enregistrées.
                </p>
            </div>
        </header>

        <section class="section-block history-content">

            <p class="history-description">
                Consultez les dépôts, retraits et virements enregistrés
                dans l'historique de votre espace client.
            </p>

            <?php if (empty($transactions)): ?>

                <section class="history-empty">

                    <h2>Aucune transaction</h2>

                    <p>
                        Vous n'avez encore aucune transaction enregistrée.
                        Vos opérations apparaîtront ici lorsqu'elles seront
                        enregistrées dans la base de données.
                    </p>

                </section>

            <?php else: ?>

                <section class="history-panel">

                    <h2>Mes transactions</h2>

                    <div class="history-table-wrapper">

                        <table class="history-table">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type d'opération</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($transactions as $transaction): ?>

                                    <tr>

                                        <td>
                                            <?= e($transaction["ID"]) ?>
                                        </td>

                                        <td>
                                            <span class="history-type">
                                                <?= e(
                                                    libelleTransaction(
                                                        (string) $transaction["TYPE"]
                                                    )
                                                ) ?>
                                            </span>
                                        </td>

                                        <td class="history-amount">
                                            <?= number_format(
                                                (float) $transaction["MONTANT"],
                                                2,
                                                ',',
                                                ' '
                                            ) ?> MAD
                                        </td>

                                        <td class="history-date">
                                            <?= e(
                                                $transaction["DATE_TRANSACTION"]
                                            ) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </section>

            <?php endif; ?>

            <a href="dashboard.php" class="history-back">
                ← Retour au tableau de bord
            </a>

        </section>

    </main>

</div>

</body>
</html>
```