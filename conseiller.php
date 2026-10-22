
<?php
require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");


// 1. Nombre total de clients
$totalClients = (int) $pdo
    ->query("SELECT COUNT(*) FROM clients")
    ->fetchColumn();

// 2. Demandes en attente
$totalDemandes = (int) $pdo
    ->query("SELECT COUNT(*) FROM demandes WHERE STATUT = 'EN_ATTENTE'")
    ->fetchColumn();

// 3. Comptes actifs
$totalComptes = (int) $pdo
    ->query("SELECT COUNT(*) FROM comptes WHERE STATUT = 'ACTIF'")
    ->fetchColumn();

// 4. Tickets de support ouverts
$totalSupport = (int) $pdo
    ->query("SELECT COUNT(*) FROM support WHERE STATUT = 'OUVERT'")
    ->fetchColumn();

// 5. Transactions récentes
$sql = "SELECT t.ID, t.TYPE, t.MONTANT, t.DATE_TRANSACTION,
               c.NOM
        FROM transactions t
        LEFT JOIN clients c ON c.ID = t.CLIENT_ID
        ORDER BY t.DATE_TRANSACTION DESC
        LIMIT 6";

$transactions = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// 6. Statistiques des transactions pour le graphique
$sqlGraphique = "SELECT TYPE, COUNT(*) AS NOMBRE
                 FROM transactions
                 GROUP BY TYPE
                 ORDER BY NOMBRE DESC";

$statistiquesTransactions = $pdo
    ->query($sqlGraphique)
    ->fetchAll(PDO::FETCH_ASSOC);

$totalTransactions = array_sum(
    array_column($statistiquesTransactions, "NOMBRE")
);

// 7. Fonctions d'affichage
function echapper($valeur)
{
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

function nomTransaction($type)
{
    $types = [
        "DEPOT" => "Dépôt",
        "RETRAIT" => "Retrait",
        "VIREMENT_ENVOYE" => "Virement envoyé",
        "VIREMENT_RECU" => "Virement reçu"
    ];

    return $types[$type] ?? $type;
}

function classeTransaction($type)
{
    $classes = [
        "DEPOT" => "deposit",
        "RETRAIT" => "withdrawal",
        "VIREMENT_ENVOYE" => "virement-envoye",
        "VIREMENT_RECU" => "virement-recu"
    ];

    return $classes[$type] ?? "";
}

$nomConseiller = $_SESSION["nom"] ?? "Conseiller";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tableau de bord | BANK-SIM</title>

    <link rel="stylesheet" href="/BANK-SIM/style.css?v=2">
</head>

<body class="premium-dashboard">

<div class="app-layout">

    <!-- MENU LATERAL -->
    <aside class="sidebar">

        <div class="sidebar-brand">
            <div class="brand-icon">B</div>

            <div class="brand-text">
                <strong>BANK-SIM</strong>
                <span>Private Banking</span>
            </div>
        </div>

        <p class="sidebar-label">Menu principal</p>

        <nav class="sidebar-nav">

            <a href="conseiller.php" class="nav-link active">
                <span class="nav-icon">▦</span>
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
                Comptes bancaires
            </a>

            <a href="conseiller_toutes_transactions.php" class="nav-link">
                <span class="nav-icon">↔</span>
                Transactions
            </a>

            <a href="conseiller_support.php" class="nav-link">
                <span class="nav-icon">✉</span>
                Support
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="sidebar-user">
                <div class="user-avatar">C</div>

                <div class="user-info">
                    <strong><?= echapper($nomConseiller) ?></strong>
                    <span>Conseiller bancaire</span>
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
                BANK-SIM / <strong>Tableau de bord</strong>
            </div>

            <div class="topbar-date">
                Espace professionnel
            </div>
        </header>

        <!-- BANNIERE D'ACCUEIL -->
        <section class="welcome-banner">

            <h1>
                Bienvenue,
                <span class="welcome-accent">
                    <?= echapper($nomConseiller) ?>
                </span>
            </h1>

            <p>
                Retrouvez ici les indicateurs de votre activité,
                les transactions récentes et les outils de gestion
                    de vos clients.
            </p>

        </section>

        <!-- STATISTIQUES -->
        <section class="section-block">

            <div class="section-heading">
                <div>
                    <h2>Vue d'ensemble</h2>
                    <p>Les principaux indicateurs de BANK-SIM</p>
                </div>

                <span class="live-label">Données de la base</span>
            </div>

            <div class="stats-grid">

                <article class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon">♙</div>
                        <span class="stat-caption">CLIENTÈLE</span>
                    </div>

                    <h3><?= $totalClients ?></h3>
                    <p class="stat-description">Clients enregistrés</p>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon">▤</div>
                        <span class="stat-caption">DEMANDES</span>
                    </div>

                    <h3><?= $totalDemandes ?></h3>
                    <p class="stat-description">Demandes en attente</p>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon">▣</div>
                        <span class="stat-caption">COMPTES</span>
                    </div>

                    <h3><?= $totalComptes ?></h3>
                    <p class="stat-description">Comptes actifs</p>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon">✉</div>
                        <span class="stat-caption">SUPPORT</span>
                    </div>

                    <h3><?= $totalSupport ?></h3>
                    <p class="stat-description">Tickets ouverts</p>
                </article>

            </div>

        </section>

        <!-- GRAPHIQUE ET ACCES RAPIDES -->
        <section class="section-block">

            <div class="section-heading">
                <div>
                    <h2>Activité bancaire</h2>
                    <p>Répartition des transactions enregistrées</p>
                </div>
            </div>

            <div class="analytics-grid">

                <!-- GRAPHIQUE -->
                <article class="panel">

                    <div class="panel-heading">
                        <div>
                            <h3>Transactions par type</h3>
                            <p>Nombre d'opérations dans la base</p>
                        </div>

                        <span class="chart-summary">
                            <?= $totalTransactions ?> opérations
                        </span>
                    </div>

                    <?php if (empty($statistiquesTransactions)): ?>

                        <div class="empty-chart">
                            Aucune transaction à afficher pour le moment.
                        </div>

                    <?php else: ?>

                        <div class="bar-chart">

                            <?php foreach ($statistiquesTransactions as $stat): ?>

                                <?php
                                $nombre = (int) $stat["NOMBRE"];

                                $pourcentage = $totalTransactions > 0
                                    ? ($nombre / $totalTransactions) * 100
                                    : 0;
                                ?>

                                <div class="bar-row">

                                    <div class="bar-label-line">
                                        <span>
                                            <?= echapper(
                                                nomTransaction($stat["TYPE"])
                                            ) ?>
                                        </span>

                                        <strong>
                                            <?= $nombre ?>
                                            (<?= number_format(
                                                $pourcentage,
                                                1,
                                                ",",
                                                " "
                                            ) ?> %)
                                        </strong>
                                    </div>

                                    <div class="bar-track">
                                        <span
                                            class="bar-fill"
                                            style="width: <?= $pourcentage ?>%;"
                                        ></span>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </article>

                <!-- ACCES RAPIDES -->
                <article class="panel">

                    <div class="panel-heading">
                        <div>
                            <h3>Accès rapides</h3>
                            <p>Les outils de votre espace</p>
                        </div>
                    </div>

                    <div class="quick-actions">

                        <a href="conseiller_clients.php"
                           class="quick-action">

                            <div class="quick-icon">♙</div>

                            <div class="quick-copy">
                                <strong>Gérer les clients</strong>
                                <span>Consulter les dossiers clients</span>
                            </div>

                            <span class="quick-arrow">›</span>
                        </a>

                        <a href="conseiller_demandes.php"
                           class="quick-action">

                            <div class="quick-icon">▤</div>

                            <div class="quick-copy">
                                <strong>Traiter les demandes</strong>
                                <span>Consulter les demandes reçues</span>
                            </div>

                            <span class="quick-arrow">›</span>
                        </a>

                        <a href="conseiller_toutes_transactions.php"
                           class="quick-action">

                            <div class="quick-icon">↔</div>

                            <div class="quick-copy">
                                <strong>Voir les transactions</strong>
                                <span>Consulter l'historique bancaire</span>
                            </div>

                            <span class="quick-arrow">›</span>
                        </a>

                    </div>

                    <div class="premium-note">
                        BANK-SIM est un environnement de simulation
                        destiné à l'apprentissage.
                    </div>

                </article>

            </div>

        </section>

        <!-- DERNIERES TRANSACTIONS -->
        <section class="panel transactions-panel">

            <div class="transactions-heading">
                <div>
                    <h3>Dernières transactions</h3>
                    <p>Les six opérations les plus récentes</p>
                </div>

                <a href="conseiller_toutes_transactions.php"
                   class="table-link">
                    Voir tout →
                </a>
            </div>

            <div class="table-responsive">

                <table class="transactions-table">

                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($transactions)): ?>

                        <tr>
                            <td colspan="4" class="empty-table">
                                Aucune transaction enregistrée.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($transactions as $transaction): ?>

                            <tr>

                                <td>
                                    <div class="client-cell">

                                        <div class="client-avatar">
                                            <?= echapper(
                                                strtoupper(
                                                    substr(
                                                        $transaction["NOM"] ?? "?",
                                                        0,
                                                        1
                                                    )
                                                )
                                            ) ?>
                                        </div>

                                        <span class="client-name">
                                            <?= echapper(
                                                $transaction["NOM"]
                                                    ?? "Client inconnu"
                                            ) ?>
                                        </span>

                                    </div>
                                </td>

                                <td>
                                    <span class="transaction-type <?= echapper(
                                        classeTransaction(
                                            $transaction["TYPE"]
                                        )
                                    ) ?>">
                                        <?= echapper(
                                            nomTransaction(
                                                $transaction["TYPE"]
                                            )
                                        ) ?>
                                    </span>
                                </td>

                                <td class="amount-column">
                                    <?= number_format(
                                        (float) $transaction["MONTANT"],
                                        2,
                                        ",",
                                        " "
                                    ) ?>
                                    MAD
                                </td>

                                <td>
                                    <?= echapper(
                                        $transaction["DATE_TRANSACTION"]
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <footer class="dashboard-footer">
            <span>© <?= date("Y") ?> BANK-SIM</span>
            <span>Plateforme bancaire de simulation</span>
        </footer>

    </main>

</div>

</body>
</html>