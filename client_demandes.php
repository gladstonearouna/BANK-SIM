
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

// Fonction pour sécuriser l'affichage HTML.
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

// Créer un jeton CSRF s'il n'existe pas.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Récupérer la dernière notification du client connecté.
$sql = "SELECT ID, MESSAGE, LUE, DATE_CREATION
        FROM notifications
        WHERE CLIENT_ID = :client_id
        ORDER BY DATE_CREATION DESC, ID DESC
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":client_id" => $client_id
]);

$notification = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .notifications-content {
            width: 100%;
            max-width: 950px;
            margin: 0 auto;
        }

        .notifications-description {
            color: #667085;
            line-height: 1.7;
        }

        .notification-panel {
            max-width: 760px;
            margin: 25px 0;
            padding: 28px;
            box-sizing: border-box;
            background: #ffffff;
            border: 1px solid #e8e2d4;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(23, 43, 77, 0.07);
            overflow-wrap: anywhere;
        }

        .notification-panel h3 {
            margin-top: 0;
            color: #172b4d;
        }

        .notification-message {
            color: #344054;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .notification-date {
            margin-top: 20px;
            color: #667085;
            font-size: 13px;
        }

        .notification-status {
            display: inline-block;
            margin-bottom: 14px;
            padding: 7px 12px;
            border-radius: 20px;
            background: #fff5d8;
            color: #795b0e;
            font-size: 13px;
            font-weight: 700;
        }

        .notification-status.read {
            background: #edf2f7;
            color: #475467;
        }

        .notification-button {
            margin-top: 15px;
            padding: 12px 18px;
            border: none;
            border-radius: 9px;
            background: #c9a227;
            color: #172b4d;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .notification-button:hover {
            background: #b18c16;
        }

        .notification-empty {
            padding: 28px;
            background: #ffffff;
            border: 1px solid #e8e2d4;
            border-radius: 16px;
            color: #667085;
        }

        .notification-back {
            display: inline-block;
            margin-top: 12px;
            color: #172b4d;
            font-weight: 600;
            text-decoration: none;
        }

        .notification-back:hover {
            color: #b18c16;
        }

        @media (max-width: 600px) {
            .notification-panel {
                padding: 20px 16px;
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
<!-- Notifications : pas active sur cette page -->
<a href="client_notifications.php" class="nav-link">
    <span class="nav-icon">🔔</span>
    <span>Notifications</span>
</a>

<!-- Mes demandes : active sur cette page -->
<a href="client_demandes.php"
   class="nav-link active"
   aria-current="page">
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
                <p class="topbar-label">BANK-SIM / Espace client</p>
                <h2>Mes notifications</h2>
            </div>
        </header>

        <section class="section-block notifications-content">

            <div class="section-heading">
                <div>
                    <h2>Centre de notifications</h2>

                    <p class="notifications-description">
                        Retrouvez les informations importantes
                        concernant votre compte.
                    </p>
                </div>
            </div>

            <?php if (!$notification): ?>

                <div class="notification-empty">
                    <h3>Aucune notification</h3>

                    <p>
                        Vous n'avez actuellement aucune notification.
                    </p>
                </div>

            <?php else: ?>

                <div class="notification-panel">

                    <?php if ((int) $notification["LUE"] === 0): ?>

                        <span class="notification-status">
                            🔵 Nouvelle notification
                        </span>

                    <?php else: ?>

                        <span class="notification-status read">
                            ✓ Notification lue
                        </span>

                    <?php endif; ?>

                    <h3>Dernière notification</h3>

                    <p class="notification-message"><?= e(
                        $notification["MESSAGE"]
                    ) ?></p>

                    <p class="notification-date">
                        Date :
                        <?= e($notification["DATE_CREATION"]) ?>
                    </p>

                    <?php if ((int) $notification["LUE"] === 0): ?>

                        <form
                            action="marquer_notification_lue.php"
                            method="POST"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($_SESSION["csrf_token"]) ?>"
                            >

                            <input
                                type="hidden"
                                name="notification_id"
                                value="<?= (int) $notification["ID"] ?>"
                            >

                            <button
                                class="notification-button"
                                type="submit"
                            >
                                ✓ Marquer comme lue
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

            <a href="dashboard.php" class="notification-back">
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
