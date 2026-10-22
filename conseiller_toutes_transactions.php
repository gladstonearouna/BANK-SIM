
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Récupérer toutes les transactions avec le nom du client.
$sql = "SELECT
            transactions.ID,
            clients.NOM,
            transactions.TYPE,
            transactions.MONTANT,
            transactions.DATE_TRANSACTION
        FROM transactions
        INNER JOIN clients
            ON transactions.CLIENT_ID = clients.ID
        ORDER BY transactions.DATE_TRANSACTION DESC";

$stmt = $pdo->query($sql);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nomConseiller = $_SESSION["nom"] ?? "Conseiller";
$totalTransactions = count($transactions);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Transactions - BANK-SIM</title>

    <link rel="stylesheet" href="style.css?v=3">
</head>

<body class="premium-dashboard">

<div class="app-layout">

    <!-- MENU LATERAL -->
    <aside class="sidebar">

        <div class="sidebar-brand">
            <div class="brand-icon">B</div>

            <div class="brand-text">
                <strong>BANK-SIM</strong>
                <span>Premium Banking</span>
            </div>
        </div>

        <p class="sidebar-label">MENU PRINCIPAL</p>

        <nav class="sidebar-nav">

            <a href="conseiller.php" class="nav-link">
                <span class="nav-icon">⌂</span>
                Tableau de bord
            </a>

            <a href="conseiller_clients.php" class="nav-link">
                <span class="nav-icon">♙</span>
                Gestion des clients
            </a>

            <a href="conseiller_demandes.php" class="nav-link">
                <span class="nav-icon">▤</span>
                Demandes
            </a>

            <a href="conseiller_comptes.php" class="nav-link">
                <span class="nav-icon">▣</span>
                Gestion des comptes
            </a>

            <a href="conseiller_toutes_transactions.php"
               class="nav-link active">
                <span class="nav-icon">↔</span>
                Transactions
            </a>

            <a href="conseiller_support.php" class="nav-link">
                <span class="nav-icon">◉</span>
                Support
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="sidebar-user">
                <div class="user-avatar">C</div>

                <div class="user-info">
                    <strong><?= htmlspecialchars(
                        $nomConseiller,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?></strong>

                    <span>Conseiller</span>
                </div>
            </div>

            <a href="logout.php" class="nav-link">
                <span class="nav-icon">↪</span>
                Déconnexion
            </a>

        </div>

    </aside>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-content">

        <header class="topbar">
            <div class="breadcrumb">
                BANK-SIM / Transactions
            </div>

            <div class="topbar-date">
                Espace conseiller
            </div>
        </header>

        <section class="section-block">

            <!-- TITRE -->
            <div class="section-heading">
                <div>
                    <h2>Historique des transactions</h2>

                    <p>
                        Consultez les opérations enregistrées
                        pour les clients de BANK-SIM.
                    </p>
                </div>
            </div>

            <!-- INDICATEUR -->
            <div class="resume-clients">

                <div class="panel">
                    <div class="resume-icone">↔</div>

                    <div class="resume-texte">
                        <span>Total des transactions</span>
                        <strong><?= $totalTransactions ?></strong>
                    </div>
                </div>

            </div>

            <!-- TABLEAU -->
            <div class="section-heading">
                <div>
                    <h2>Liste des opérations</h2>

                    <p>
                        Les opérations les plus récentes apparaissent en premier.
                    </p>
                </div>
            </div>

            <?php if (empty($transactions)): ?>

                <div class="panel message-vide">
                    <p>
                        Aucune transaction n'est enregistrée pour le moment.
                    </p>
                </div>

            <?php else: ?>

                <div class="panel">

                    <div class="table-responsive">

                        <table class="transactions-table">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Type d'opération</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($transactions as $transaction): ?>

                                    <tr>
                                        <td>
                                            <?= (int) $transaction["ID"] ?>
                                        </td>

                                        <td>
                                            <span class="nom-client">
                                                <?= htmlspecialchars(
                                                    (string) $transaction["NOM"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $transaction["TYPE"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= number_format(
                                                    (float) $transaction["MONTANT"],
                                                    2,
                                                    ",",
                                                    " "
                                                ) ?> MAD
                                            </strong>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $transaction["DATE_TRANSACTION"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            <?php endif; ?>

        </section>

        <footer class="dashboard-footer">
            BANK-SIM — Historique des transactions
        </footer>

    </main>

</div>

</body>
</html>
