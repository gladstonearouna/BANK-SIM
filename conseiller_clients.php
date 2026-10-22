
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Récupérer les clients depuis la base de données.
$sql = "SELECT ID, NOM, SOLDE
        FROM clients
        ORDER BY ID DESC";

$stmt = $pdo->query($sql);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nom du conseiller connecté.
$nomConseiller = $_SESSION["nom"] ?? "Conseiller";

// Sécuriser les données affichées dans le HTML.
function e($valeur)
{
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

$nombreClients = count($clients);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des clients - BANK-SIM</title>

    <link rel="stylesheet" href="style.css?v=4">

    <style>
        :root {
            --navy: #14243A;
            --navy-light: #203651;
            --gold: #C7A45D;
            --ivory: #F5F0E4;
            --white: #FFFFFF;
            --text: #253348;
            --muted: #78808D;
            --border: #E7E0D2;
            --green: #216647;
        }

        .clients-page {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
        }

        .clients-page,
        .clients-page * {
            box-sizing: border-box;
        }

        .clients-page .topbar {
            margin-bottom: 30px;
        }

        .clients-page .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .clients-page .section-heading h2 {
            margin: 0 0 9px;
            color: var(--navy);
            font-size: clamp(24px, 3vw, 32px);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .section-description {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
        }

        /* Bandeau d'introduction */
        .clients-intro {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            margin-bottom: 25px;
            padding: 28px;
            color: var(--white);
            background: linear-gradient(
                120deg,
                var(--navy),
                var(--navy-light)
            );
            border: 1px solid #263B55;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(20, 36, 58, 0.08);
        }

        .clients-intro::after {
            content: "";
            position: absolute;
            width: 200px;
            height: 200px;
            right: -60px;
            top: -100px;
            border: 1px solid rgba(199, 164, 93, 0.35);
            border-radius: 50%;
            box-shadow:
                0 0 0 22px rgba(199, 164, 93, 0.04),
                0 0 0 44px rgba(199, 164, 93, 0.04);
            pointer-events: none;
        }

        .intro-content {
            position: relative;
            z-index: 1;
        }

        .intro-label {
            margin: 0 0 9px;
            color: #E2C98F;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.7px;
            text-transform: uppercase;
        }

        .intro-title {
            margin: 0 0 8px;
            color: var(--white);
            font-size: 23px;
            font-weight: 600;
        }

        .intro-description {
            margin: 0;
            color: #D4DCE6;
            font-size: 13px;
            line-height: 1.7;
        }

        .intro-symbol {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            flex-shrink: 0;
            color: #E2C98F;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(199, 164, 93, 0.5);
            border-radius: 16px;
            font-size: 30px;
        }

        /* Carte statistique */
        .resume-clients {
            display: flex;
            align-items: center;
            gap: 17px;
            max-width: 360px;
            margin-bottom: 25px;
            padding: 22px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(20, 36, 58, 0.035);
        }

        .resume-icone {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            color: #80642D;
            background: var(--ivory);
            border: 1px solid #E8D9B5;
            border-radius: 12px;
            font-size: 25px;
        }

        .resume-texte p {
            margin: 0 0 6px;
            color: var(--muted);
            font-size: 12px;
        }

        .resume-texte strong {
            display: block;
            color: var(--navy);
            font-size: 29px;
            line-height: 1.2;
        }

        .resume-texte small {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 11px;
        }

        /* Panneau de la liste */
        .clients-panel {
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 13px;
            box-shadow: 0 5px 20px rgba(20, 36, 58, 0.035);
        }

        .clients-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 22px 24px;
            border-bottom: 1px solid #EEE8DC;
        }

        .clients-panel-header h3 {
            margin: 0 0 5px;
            color: var(--navy);
            font-size: 16px;
        }

        .clients-panel-header p {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
        }

        .clients-count {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 11px;
            color: #80642D;
            background: #F8F0DD;
            border: 1px solid #E8D9B5;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .clients-count span {
            font-size: 14px;
        }

        .clients-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .clients-table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
            text-align: left;
        }

        .clients-table thead {
            background: #FAF8F3;
        }

        .clients-table th {
            padding: 15px 22px;
            color: #687385;
            border-bottom: 1px solid var(--border);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.9px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .clients-table td {
            padding: 17px 22px;
            color: var(--text);
            border-bottom: 1px solid #F0ECE4;
            font-size: 13px;
            vertical-align: middle;
        }

        .clients-table tbody tr {
            transition: background 0.2s ease;
        }

        .clients-table tbody tr:hover {
            background: #FCFAF5;
        }

        .clients-table tbody tr:last-child td {
            border-bottom: none;
        }

        .client-identite {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .client-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            color: #80642D;
            background: var(--ivory);
            border: 1px solid #E8D9B5;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
        }

        .client-name {
            color: var(--navy);
            font-weight: 700;
        }

        .client-reference {
            margin-top: 4px;
            color: var(--muted);
            font-size: 10px;
        }

        .client-id {
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }

        .solde-client {
            color: var(--navy);
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .solde-label {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 400;
        }

        .btn-voir-client {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 13px;
            color: #80642D;
            background: #F8F0DD;
            border: 1px solid #E8D9B5;
            border-radius: 7px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .btn-voir-client:hover {
            background: #EFE2C2;
            transform: translateY(-1px);
        }

        .message-vide {
            padding: 45px 20px !important;
            text-align: center;
            color: var(--muted) !important;
            line-height: 1.8;
        }

        .message-vide .empty-icon {
            display: block;
            margin-bottom: 12px;
            color: var(--gold);
            font-size: 30px;
        }

        .clients-panel-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 15px 22px;
            background: #FCFAF6;
            border-top: 1px solid #EEE8DC;
            color: var(--muted);
            font-size: 11px;
        }

        .footer-secure {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .footer-secure span {
            color: var(--green);
        }

        @media (max-width: 800px) {
            .clients-page .section-heading {
                flex-direction: column;
            }

            .clients-intro {
                padding: 23px;
            }

            .resume-clients {
                max-width: none;
            }

            .clients-panel-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .clients-intro {
                gap: 12px;
                padding: 19px;
            }

            .intro-title {
                font-size: 19px;
            }

            .intro-symbol {
                width: 48px;
                height: 48px;
                border-radius: 11px;
                font-size: 22px;
            }

            .clients-panel-header,
            .clients-panel-footer {
                padding: 16px;
            }

            .clients-table th,
            .clients-table td {
                padding: 13px 15px;
            }
        }
    </style>
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

            <a href="conseiller_clients.php" class="nav-link active">
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

            <a href="conseiller_toutes_transactions.php" class="nav-link">
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
                    <strong><?= e($nomConseiller) ?></strong>
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

        <div class="clients-page">

            <header class="topbar">
                <div class="breadcrumb">
                    BANK-SIM / Gestion des clients
                </div>

                <div class="topbar-date">
                    Espace conseiller
                </div>
            </header>

            <section class="section-block">

                <div class="section-heading">
                    <div>
                        <h2>Gestion des clients</h2>

                        <p class="section-description">
                            Consultez la liste des clients et accédez à leurs informations.
                        </p>
                    </div>
                </div>

                <!-- BANDEAU D'INTRODUCTION -->
                <div class="clients-intro">

                    <div class="intro-content">
                        <p class="intro-label">Espace professionnel</p>

                        <h3 class="intro-title">
                            Relations clients
                        </h3>

                        <p class="intro-description">
                            Retrouvez les clients enregistrés et consultez
                            leurs informations en toute simplicité.
                        </p>
                    </div>

                    <div class="intro-symbol" aria-hidden="true">
                        ♙
                    </div>

                </div>

                <!-- STATISTIQUE -->
                <div class="resume-clients">

                    <div class="resume-icone" aria-hidden="true">
                        ♙
                    </div>

                    <div class="resume-texte">
                        <p>Nombre de clients enregistrés</p>

                        <strong><?= $nombreClients ?></strong>

                        <small>Clients présents dans la base de données</small>
                    </div>

                </div>

                <!-- TABLEAU DES CLIENTS -->
                <div class="clients-panel">

                    <div class="clients-panel-header">

                        <div>
                            <h3>Répertoire des clients</h3>

                            <p>
                                Consultez les références et les soldes disponibles.
                            </p>
                        </div>

                        <div class="clients-count">
                            <span>♙</span>
                            <?= $nombreClients ?>
                            client<?= $nombreClients > 1 ? "s" : "" ?>
                        </div>

                    </div>

                    <div class="clients-table-wrap">

                        <table class="clients-table">

                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Nom du client</th>
                                    <th>Solde disponible</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if (empty($clients)): ?>

                                    <tr>
                                        <td colspan="4" class="message-vide">
                                            <span class="empty-icon">♙</span>

                                            Aucun client n'est enregistré pour le moment.
                                        </td>
                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($clients as $client): ?>

                                        <tr>

                                            <td class="client-id">
                                                #<?= (int) $client["ID"] ?>
                                            </td>

                                            <td>
                                                <div class="client-identite">

                                                    <div class="client-avatar">
                                                        <?= e(
                                                            strtoupper(
                                                                substr(
                                                                    trim((string) $client["NOM"]),
                                                                    0,
                                                                    1
                                                                )
                                                            )
                                                        ) ?>
                                                    </div>

                                                    <div>
                                                        <div class="client-name">
                                                            <?= e($client["NOM"]) ?>
                                                        </div>

                                                        <div class="client-reference">
                                                            Client BANK-SIM
                                                        </div>
                                                    </div>

                                                </div>
                                            </td>

                                            <td class="solde-client">

                                                <?= number_format(
                                                    (float) $client["SOLDE"],
                                                    2,
                                                    ",",
                                                    " "
                                                ) ?> MAD

                                                <span class="solde-label">
                                                    Solde enregistré
                                                </span>

                                            </td>

                                            <td>
                                                <a
                                                    class="btn-voir-client"
                                                    href="conseiller_client.php?id=<?= (int) $client["ID"] ?>"
                                                >
                                                    <span aria-hidden="true">⌕</span>
                                                    Voir les détails
                                                </a>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                    <div class="clients-panel-footer">

                        <span>
                            <?= $nombreClients ?>
                            client<?= $nombreClients > 1 ? "s" : "" ?>
                            enregistré<?= $nombreClients > 1 ? "s" : "" ?>
                        </span>

                        <span class="footer-secure">
                            <span aria-hidden="true">●</span>
                            Espace conseiller sécurisé
                        </span>

                    </div>

                </div>

            </section>

            <footer class="dashboard-footer">
                BANK-SIM — Gestion sécurisée des clients
            </footer>

        </div>

    </main>

</div>

</body>
</html>
```