
<?php
// sidebar.php
// Ce fichier doit être inclus après auth.php et config.php.

// Page actuelle : chaque page doit définir $pageActive
// avant d'inclure sidebar.php.

$pageActive = $pageActive ?? basename($_SERVER["PHP_SELF"]);

if (!function_exists("e")) {
    function e($valeur)
    {
        return htmlspecialchars(
            (string) $valeur,
            ENT_QUOTES,
            "UTF-8"
        );
    }
}

// Nom du client connecté
$nomClient = $_SESSION["client_nom"] ?? null;

if ($nomClient === null && isset($_SESSION["client_id"])) {
    $stmtNom = $pdo->prepare(
        "SELECT NOM FROM clients WHERE ID = :id"
    );
    $stmtNom->execute([
        ":id" => (int) $_SESSION["client_id"]
    ]);

    $nomClient = $stmtNom->fetchColumn() ?: "Client";
}

// Nombre de notifications non lues
$nombreNotifications = 0;

if (isset($_SESSION["client_id"])) {
    $stmtNotif = $pdo->prepare(
        "SELECT COUNT(*)
         FROM notifications
         WHERE CLIENT_ID = :id AND LUE = 0"
    );

    $stmtNotif->execute([
        ":id" => (int) $_SESSION["client_id"]
    ]);

    $nombreNotifications = (int) $stmtNotif->fetchColumn();
}

// Fonction pour déterminer le lien actif
function navClass($fichier, $pageActive)
{
    return $fichier === $pageActive
        ? "nav-link active"
        : "nav-link";
}
?>

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

        <a href="dashboard.php"
           class="<?= e(navClass('dashboard.php', $pageActive)) ?>"
           <?= $pageActive === 'dashboard.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">🏠</span>
            <span>Accueil</span>
        </a>

        <a href="client_comptes.php"
           class="<?= e(navClass('client_comptes.php', $pageActive)) ?>"
           <?= $pageActive === 'client_comptes.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">💳</span>
            <span>Mes comptes</span>
        </a>

        <a href="client_informations.php"
           class="<?= e(navClass('client_informations.php', $pageActive)) ?>"
           <?= $pageActive === 'client_informations.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">👤</span>
            <span>Mes informations personnelles</span>
        </a>

        <a href="client_depot.php"
           class="<?= e(navClass('client_depot.php', $pageActive)) ?>"
           <?= $pageActive === 'client_depot.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">💰</span>
            <span>Dépôt</span>
        </a>

        <a href="client_retrait.php"
           class="<?= e(navClass('client_retrait.php', $pageActive)) ?>"
           <?= $pageActive === 'client_retrait.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">🏧</span>
            <span>Retrait</span>
        </a>

        <a href="client_virement.php"
           class="<?= e(navClass('client_virement.php', $pageActive)) ?>"
           <?= $pageActive === 'client_virement.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">💸</span>
            <span>Virement</span>
        </a>

        <a href="client_beneficiaires.php"
           class="<?= e(navClass('client_beneficiaires.php', $pageActive)) ?>"
           <?= $pageActive === 'client_beneficiaires.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">👥</span>
            <span>Mes bénéficiaires</span>
        </a>

        <a href="client_historique.php"
           class="<?= e(navClass('client_historique.php', $pageActive)) ?>"
           <?= $pageActive === 'client_historique.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">📊</span>
            <span>Historique</span>
        </a>

        <a href="client_notifications.php"
           class="<?= e(navClass('client_notifications.php', $pageActive)) ?>"
           <?= $pageActive === 'client_notifications.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">🔔</span>
            <span>Notifications
                <?php if ($nombreNotifications > 0): ?>
                    (<?= $nombreNotifications ?>)
                <?php endif; ?>
            </span>
        </a>

        <a href="client_demandes.php"
           class="<?= e(navClass('client_demandes.php', $pageActive)) ?>"
           <?= $pageActive === 'client_demandes.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">📝</span>
            <span>Mes demandes</span>
        </a>

        <a href="client_support.php"
           class="<?= e(navClass('client_support.php', $pageActive)) ?>"
           <?= $pageActive === 'client_support.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">🆘</span>
            <span>Support</span>
        </a>

        <a href="client_mes_support.php"
           class="<?= e(navClass('client_mes_support.php', $pageActive)) ?>"
           <?= $pageActive === 'client_mes_support.php' ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon">📂</span>
            <span>Mes tickets</span>
        </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="sidebar-user">

            <div class="user-avatar">
                <?= e(mb_strtoupper(
                    mb_substr($nomClient ?: "C", 0, 1, "UTF-8"),
                    "UTF-8"
                )) ?>
            </div>

            <div class="user-info">
                <strong><?= e($nomClient ?: "Client") ?></strong>
                <span>Client BANK-SIM</span>
            </div>

        </div>

        <a href="logout.php" class="nav-link">
            <span class="nav-icon">🚪</span>
            <span>Déconnexion</span>
        </a>

    </div>

</aside>