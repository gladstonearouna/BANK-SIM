
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

// Récupération du message de succès
$messageSucces = $_SESSION["message_succes"] ?? null;
unset($_SESSION["message_succes"]);

// Identifiant du client connecté
$client_id = (int) $_SESSION["client_id"];

// Informations du client
$sql = "SELECT ID, NOM, SOLDE
        FROM clients
        WHERE ID = :client_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([":client_id" => $client_id]);

$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    die("Client introuvable.");
}
// Compter les notifications non lues du client connecté
$sqlNotifications = "
    SELECT COUNT(*)
    FROM notifications
    WHERE CLIENT_ID = :client_id
      AND LUE = 0
";

$stmtNotifications = $pdo->prepare($sqlNotifications);
$stmtNotifications->execute([
    ":client_id" => $client_id
]);

$nombreNotifications = (int) $stmtNotifications->fetchColumn();

// Comptes du client
$sql = "SELECT ID, NUMERO_COMPTE, TYPE_COMPTE, SOLDE, STATUT
        FROM comptes
        WHERE CLIENT_ID = :client_id
        ORDER BY ID";

$stmt = $pdo->prepare($sql);
$stmt->execute([":client_id" => $client_id]);

$comptes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul du solde total des comptes
$soldeTotal = 0;

foreach ($comptes as $compte) {
    $soldeTotal += (float) $compte["SOLDE"];
}


 // Notifications non lues : récupérer uniquement la plus récente
$sql = "SELECT ID, MESSAGE, DATE_CREATION
        FROM notifications
        WHERE CLIENT_ID = :client_id
          AND LUE = 0
        ORDER BY DATE_CREATION DESC, ID DESC
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([":client_id" => $client_id]);

$notificationsNonLues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter toutes les notifications non lues
$sqlNombre = "SELECT COUNT(*)
              FROM notifications
              WHERE CLIENT_ID = :client_id
                AND LUE = 0";

$stmtNombre = $pdo->prepare($sqlNombre);
$stmtNombre->execute([":client_id" => $client_id]);

$nombreNotifications = (int) $stmtNombre->fetchColumn();

$stmt = $pdo->prepare($sql);
$stmt->execute([":client_id" => $client_id]);

$notificationsNonLues = $stmt->fetchAll(PDO::FETCH_ASSOC);
$nombreNotifications = count($notificationsNonLues);


// Protection des données affichées
function e($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, "UTF-8");
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tableau de bord - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="premium-dashboard">

<div class="app-layout">

    <!-- MENU LATERAL -->
<!-- MENU LATERAL -->
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

        <a href="dashboard.php" class="nav-link active"
           aria-current="page">
            <span class="nav-icon">🏠</span>
            <span>Accueil</span>
        </a>

        <a href="client_comptes.php" class="nav-link">
            <span class="nav-icon">💳</span>
            <span>Mes comptes</span>
        </a>

        <a href="client_informations.php"
           class="nav-link">
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
            <span>Notifications
                <?php if ($nombreNotifications > 0): ?>
                    (<?= $nombreNotifications ?>)
                <?php endif; ?>
            </span>
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

        <div class="sidebar-user">
            <div class="user-avatar">
                <?= e(mb_strtoupper(mb_substr($client["NOM"], 0, 1, "UTF-8"), "UTF-8")) ?>
            </div>

            <div class="user-info">
                <strong><?= e($client["NOM"]) ?></strong>
                <span>Client BANK-SIM</span>
            </div>
        </div>

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
                <p class="breadcrumb">BANK-SIM / <strong>Accueil</strong></p>
            </div>

            <p class="topbar-date">Espace bancaire sécurisé</p>
        </header>

        <!-- MESSAGE DE BIENVENUE -->
        <section class="welcome-banner">
            <h1>
                Bienvenue,
                <span class="welcome-accent"><?= e($client["NOM"]) ?></span>
            </h1>

            <p>
                Retrouvez vos comptes, consultez vos soldes et gérez
                vos opérations bancaires depuis votre espace personnel.
            </p>
        </section>

        <!-- MESSAGE DE SUCCES -->
        <?php if ($messageSucces): ?>
            <section class="panel section-block">
                <p class="live-label">
                    <?= e($messageSucces) ?>
                </p>
            </section>
        <?php endif; ?>

        <!-- SOLDE TOTAL -->
        <section class="section-block">

            <div class="section-heading">
                <div>
                    <h2>Vue d'ensemble</h2>
                    <p>Situation de vos comptes bancaires</p>
                </div>

                <span class="live-label">Comptes consultés</span>
            </div>

            <div class="stats-grid">

                <article class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon">💰</div>
                        <span class="stat-caption">SOLDE TOTAL</span>
                    </div>

                    <p class="stat-value">
                        <?= number_format($soldeTotal, 2, ',', ' ') ?>
                        <span style="font-size: 13px;">MAD</span>
                    </p>

                    <p class="stat-description">
                        Somme des soldes de vos comptes
                    </p>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon">💳</div>
                        <span class="stat-caption">COMPTES</span>
                    </div>

                    <p class="stat-value"><?= count($comptes) ?></p>

                    <p class="stat-description">
                        Nombre de comptes enregistrés
                    </p>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon">🔔</div>
                        <span class="stat-caption">NOTIFICATIONS</span>
                    </div>

                    <p class="stat-value"><?= $nombreNotifications ?></p>

                    <p class="stat-description">
                        Notifications non lues
                    </p>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon">🛡️</div>
                        <span class="stat-caption">SÉCURITÉ</span>
                    </div>

                    <p class="stat-value" style="font-size: 20px;">
                        BANK-SIM
                    </p>

                    <p class="stat-description">
                        Votre espace client
                    </p>
                </article>

            </div>
        </section>

        <!-- MES COMPTES -->
        <section class="section-block">

            <div class="section-heading">
                <div>
                    <h2>Mes comptes bancaires</h2>
                    <p>Consultez le solde et le statut de chaque compte</p>
                </div>

                <a href="client_comptes.php" class="table-link">
                    Voir mes comptes →
                </a>
            </div>

            <?php if (empty($comptes)): ?>

                <div class="panel">
                    <p>Vous n'avez actuellement aucun compte bancaire.</p>
                </div>

            <?php else: ?>

                <div class="stats-grid">

                    <?php foreach ($comptes as $compte): ?>

                        <article class="stat-card">

                            <div class="stat-top">
                                <div class="stat-icon">💳</div>

                                <span class="stat-caption">
                                    <?= e($compte["STATUT"]) ?>
                                </span>
                            </div>

                            <h3 style="font-size: 17px;">
                                <?= e($compte["TYPE_COMPTE"]) ?>
                            </h3>

                            <p class="stat-description">
                                Numéro :
                                <?= e($compte["NUMERO_COMPTE"]) ?>
                            </p>

                            <p class="stat-value"
                               style="font-size: 22px; margin-top: 15px;">
                                <?= number_format(
                                    (float) $compte["SOLDE"],
                                    2,
                                    ',',
                                    ' '
                                ) ?>
                                <span style="font-size: 12px;">MAD</span>
                            </p>

                            <p class="stat-description">
                                Statut :
                                <?= e($compte["STATUT"]) ?>
                            </p>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <!-- NOTIFICATIONS -->
        <section class="section-block">

            <div class="section-heading">
                <div>
                    <h2>🔔 Notifications récentes</h2>
                    <p>Les messages qui nécessitent votre attention</p>
                </div>

                <a href="client_notifications.php" class="table-link">
                    Toutes les notifications →
                </a>
            </div>

            <?php if (empty($notificationsNonLues)): ?>

                <div class="panel">
                    <p>Vous n'avez aucune nouvelle notification.</p>
                </div>

            <?php else: ?>

                <div class="quick-actions">

                    <?php foreach ($notificationsNonLues as $notification): ?>

                        <article class="quick-action">

                            <div class="quick-icon">🔔</div>

                            <div class="quick-copy">
                                <strong>Nouvelle notification</strong>

                                <span>
                                    <?= e($notification["MESSAGE"]) ?>
                                </span>

                                <span>
                                    <?= e($notification["DATE_CREATION"]) ?>
                                </span>
                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <!-- ACTIONS RAPIDES -->
        <section class="section-block">

            <div class="section-heading">
                <div>
                    <h2>Actions rapides</h2>
                    <p>Accédez directement aux opérations courantes</p>
                </div>
            </div>

            <div class="analytics-grid">

                <div class="quick-actions">

                    <a href="client_depot.php" class="quick-action">
                        <div class="quick-icon">💰</div>
                        <div class="quick-copy">
                            <strong>Faire un dépôt</strong>
                            <span>Ajouter de l'argent à votre compte</span>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a href="client_retrait.php" class="quick-action">
                        <div class="quick-icon">🏧</div>
                        <div class="quick-copy">
                            <strong>Faire un retrait</strong>
                            <span>Retirer de l'argent de votre compte</span>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a href="client_virement.php" class="quick-action">
                        <div class="quick-icon">💸</div>
                        <div class="quick-copy">
                            <strong>Effectuer un virement</strong>
                            <span>Transférer de l'argent vers un autre compte</span>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>
                    

                </div>

                <div class="quick-actions">

                    <a href="client_historique.php" class="quick-action">
                        <div class="quick-icon">📊</div>
                        <div class="quick-copy">
                            <strong>Historique</strong>
                            <span>Consulter vos transactions</span>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a href="client_mes_support.php" class="quick-action">
                        <div class="quick-icon">📂</div>
                        <div class="quick-copy">
                            <strong>Mes tickets</strong>
                            <span>Suivre vos demandes bancaires</span>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a href="client_support.php" class="quick-action">
                        <div class="quick-icon">🆘</div>
                        <div class="quick-copy">
                            <strong>Contacter le support</strong>
                            <span>Obtenir de l'aide</span>
                        </div>
                        <span class="quick-arrow">→</span>
                    </a>

                </div>

            </div>

            <div class="premium-note">
                🔐 Pour votre sécurité, déconnectez-vous après avoir utilisé
                votre espace client sur un ordinateur partagé.
            </div>

        </section>

        <footer class="dashboard-footer">
            <span>BANK-SIM — Espace client</span>
            <span>Plateforme de simulation bancaire à but pédagogique</span>
        </footer>

    </main>

</div>

</body>
</html>