
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Fonction de protection de l'affichage HTML
function e($valeur) {
    return htmlspecialchars((string) $valeur, ENT_QUOTES, "UTF-8");
}

// Vérifier l'identifiant du client
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit("ID client invalide.");
}

// Récupérer les informations du client
$sql = "SELECT ID, NOM
        FROM clients
        WHERE ID = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $id]);

$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    http_response_code(404);
    exit("Client introuvable.");
}

// Récupérer les transactions du client
$sql = "SELECT ID, TYPE, MONTANT, DATE_TRANSACTION
        FROM transactions
        WHERE CLIENT_ID = :client_id
        ORDER BY DATE_TRANSACTION DESC, ID DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([":client_id" => $id]);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nombreTransactions = count($transactions);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Transactions du client - BANK-SIM</title>

    <style>
        :root {
            --ivoire: #F5F0E4;
            --marine: #14243A;
            --dore: #C7A45D;
            --blanc: #FFFFFF;
            --texte: #293548;
            --gris: #788292;
            --bordure: #E7E0D3;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--ivoire);
            color: var(--texte);
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            flex-shrink: 0;
            background: var(--marine);
            color: var(--blanc);
            padding: 30px 20px;
        }

        .brand {
            margin-bottom: 8px;
            color: var(--dore);
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .brand-subtitle {
            margin: 0 0 35px;
            color: #C5CDDA;
            font-size: 12px;
        }

        .menu-title {
            margin: 25px 0 12px;
            color: #AEB9C9;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .nav-link {
            display: block;
            margin-bottom: 8px;
            padding: 13px 12px;
            border-radius: 8px;
            color: #E7ECF3;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #263A55;
            color: var(--dore);
        }

        .logout {
            margin-top: 35px;
            border: 1px solid #526176;
        }

        .main {
            flex: 1;
            min-width: 0;
            padding: 35px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 28px;
        }

        .eyebrow {
            margin: 0 0 8px;
            color: #8D7443;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        h1 {
            margin: 0;
            color: var(--marine);
            font-size: 29px;
        }

        .subtitle {
            margin: 10px 0 0;
            color: var(--gris);
            line-height: 1.6;
        }

        .back-button {
            display: inline-block;
            padding: 12px 16px;
            border: 1px solid var(--bordure);
            border-radius: 8px;
            background: var(--blanc);
            color: var(--marine);
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
        }

        .back-button:hover {
            border-color: var(--dore);
        }

        .client-banner {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 25px;
            padding: 25px;
            border-radius: 14px;
            background: var(--marine);
            color: var(--blanc);
            box-shadow: 0 8px 22px rgba(20, 36, 58, 0.08);
        }

        .avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            flex-shrink: 0;
            border-radius: 14px;
            background: var(--dore);
            color: var(--marine);
            font-size: 22px;
            font-weight: bold;
        }

        .client-label {
            margin: 0 0 7px;
            color: #C5CDDA;
            font-size: 12px;
        }

        .client-name {
            margin: 0;
            color: var(--blanc);
            font-size: 22px;
        }

        .client-id {
            margin-top: 7px;
            color: #C5CDDA;
            font-size: 12px;
        }

        .summary-card {
            margin-bottom: 25px;
            padding: 22px;
            border: 1px solid var(--bordure);
            border-radius: 12px;
            background: var(--blanc);
        }

        .summary-label {
            margin: 0 0 10px;
            color: var(--gris);
            font-size: 13px;
        }

        .summary-number {
            margin: 0;
            color: var(--marine);
            font-size: 30px;
            font-weight: bold;
        }

        .table-card {
            overflow: hidden;
            border: 1px solid var(--bordure);
            border-radius: 14px;
            background: var(--blanc);
            box-shadow: 0 5px 18px rgba(20, 36, 58, 0.04);
        }

        .table-heading {
            padding: 22px 24px;
            border-bottom: 1px solid var(--bordure);
        }

        .table-heading h2 {
            margin: 0;
            color: var(--marine);
            font-size: 18px;
        }

        .table-heading p {
            margin: 8px 0 0;
            color: var(--gris);
            font-size: 13px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        th {
            padding: 16px 20px;
            background: #FAF8F2;
            color: #657084;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        td {
            padding: 17px 20px;
            border-top: 1px solid #F0ECE4;
            font-size: 13px;
        }

        tbody tr:hover {
            background: #FCFAF5;
        }

        .transaction-id {
            color: var(--gris);
            font-weight: bold;
        }

        .type-badge {
            display: inline-block;
            padding: 7px 10px;
            border: 1px solid #E8D9B8;
            border-radius: 20px;
            background: #FBF5E8;
            color: #80652F;
            font-size: 11px;
            font-weight: bold;
        }

        .amount {
            color: var(--marine);
            font-weight: bold;
        }

        .date {
            color: #647084;
        }

        .empty-state {
            padding: 45px 20px;
            text-align: center;
        }

        .empty-icon {
            margin-bottom: 12px;
            color: var(--dore);
            font-size: 32px;
        }

        .empty-state h3 {
            margin: 0 0 8px;
            color: var(--marine);
            font-size: 17px;
        }

        .empty-state p {
            margin: 0;
            color: var(--gris);
            font-size: 13px;
            line-height: 1.6;
        }

        .footer-note {
            margin-top: 22px;
            color: var(--gris);
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 850px) {
            .layout {
                display: block;
            }

            .sidebar {
                width: 100%;
                padding: 22px;
            }

            .brand-subtitle {
                margin-bottom: 18px;
            }

            .menu-title {
                margin-top: 18px;
            }

            .main {
                padding: 24px 16px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 24px;
            }

            .client-banner {
                padding: 18px;
            }

            .client-name {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="brand">BANK-SIM</div>
        <p class="brand-subtitle">Espace professionnel</p>

        <div class="menu-title">Navigation</div>

        <a class="nav-link" href="conseiller.php">
            Tableau de bord
        </a>

        <a class="nav-link active" href="conseiller_clients.php">
            Gestion des clients
        </a>

        <a class="nav-link" href="conseiller_demandes.php">
            Demandes
        </a>

        <a class="nav-link" href="conseiller_comptes.php">
            Comptes
        </a>

        <a class="nav-link" href="conseiller_toutes_transactions.php">
            Toutes les transactions
        </a>

        <a class="nav-link" href="conseiller_support.php">
            Support
        </a>

        <a class="nav-link logout" href="logout.php">
            Déconnexion
        </a>

    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <p class="eyebrow">Gestion bancaire</p>
                <h1>Historique des transactions</h1>
                <p class="subtitle">
                    Consulte les opérations enregistrées pour ce client.
                </p>
            </div>

            <a class="back-button"
               href="conseiller_client.php?id=<?= (int) $client["ID"] ?>">
                &larr; Retour au client
            </a>
        </header>

        <section class="client-banner">

            <div class="avatar">
                <?= e(mb_strtoupper(mb_substr($client["NOM"], 0, 1, "UTF-8"), "UTF-8")) ?>
            </div>

            <div>
                <p class="client-label">Client concerné</p>

                <h2 class="client-name">
                    <?= e($client["NOM"]) ?>
                </h2>

                <div class="client-id">
                    Référence client : #<?= (int) $client["ID"] ?>
                </div>
            </div>

        </section>

        <section class="summary-card">
            <p class="summary-label">Nombre total de transactions</p>

            <p class="summary-number">
                <?= $nombreTransactions ?>
            </p>
        </section>

        <section class="table-card">

            <div class="table-heading">
                <h2>Historique des opérations</h2>
                <p>Les transactions les plus récentes apparaissent en premier.</p>
            </div>

            <?php if (empty($transactions)): ?>

                <div class="empty-state">
                    <div class="empty-icon">&#128203;</div>

                    <h3>Aucune transaction pour le moment</h3>

                    <p>
                        Aucune opération n'est enregistrée pour ce client.
                        L'historique apparaîtra ici lorsqu'une transaction
                        sera disponible.
                    </p>
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table>
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Type d'opération</th>
                                <th>Montant</th>
                                <th>Date et heure</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($transactions as $transaction): ?>

                                <tr>
                                    <td class="transaction-id">
                                        #<?= (int) $transaction["ID"] ?>
                                    </td>

                                    <td>
                                        <span class="type-badge">
                                            <?= e($transaction["TYPE"]) ?>
                                        </span>
                                    </td>

                                    <td class="amount">
                                        <?= number_format(
                                            (float) $transaction["MONTANT"],
                                            2,
                                            ",",
                                            " "
                                        ) ?> MAD
                                    </td>

                                    <td class="date">
                                        <?= e($transaction["DATE_TRANSACTION"]) ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

            <?php endif; ?>

        </section>

        <p class="footer-note">
            BANK-SIM — Consultation des opérations bancaires.
            Les montants affichés proviennent de la base de données.
        </p>

    </main>

</div>

</body>
</html>
```