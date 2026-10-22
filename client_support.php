
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

$client_id = (int) $_SESSION["client_id"];

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

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Support client - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .support-content {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }

        .support-intro {
            color: #667085;
            line-height: 1.7;
            margin-bottom: 22px;
        }

        .support-form {
            width: 100%;
            max-width: 750px;
        }

        .support-form .form-group {
            margin-bottom: 22px;
        }

        .support-form label {
            display: block;
            margin-bottom: 9px;
            color: #172b4d;
            font-weight: 600;
        }

        .support-form input,
        .support-form textarea {
            display: block;
            width: 100%;
            box-sizing: border-box;
            padding: 13px 14px;
            border: 1px solid #d9dee7;
            border-radius: 10px;
            background: #fff;
            color: #172b4d;
            font: inherit;
        }

        .support-form textarea {
            min-height: 160px;
            resize: vertical;
        }

        .support-form input:focus,
        .support-form textarea:focus {
            outline: 2px solid #c9a227;
            outline-offset: 2px;
            border-color: #c9a227;
        }

        .support-form .btn-principal {
            cursor: pointer;
        }

        .support-info {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 24px;
            padding: 20px;
            border: 1px solid #e8e2d4;
            border-radius: 14px;
            background: #fbf7ed;
        }

        .support-icon {
            font-size: 27px;
            flex-shrink: 0;
        }

        .support-info strong {
            display: block;
            margin-bottom: 7px;
            color: #172b4d;
        }

        .support-info p {
            margin: 0;
            color: #667085;
            line-height: 1.7;
        }

        .support-form-panel {
            padding: 28px;
            background: #fff;
            border: 1px solid #e8e2d4;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(23, 43, 77, 0.07);
        }

        .support-form-panel h2 {
            margin-top: 0;
            margin-bottom: 24px;
            color: #172b4d;
        }

        .support-retour {
            margin: 24px 0;
        }

        .support-retour a {
            color: #8b6a2c;
            text-decoration: none;
            font-weight: 600;
        }

        .support-retour a:hover {
            color: #b18c16;
        }

        @media (max-width: 600px) {
            .support-form-panel {
                padding: 20px 16px;
            }

            .support-form .btn-principal {
                width: 100%;
            }

            .support-info {
                padding: 15px;
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

            <a href="client_notifications.php" class="nav-link">
                <span class="nav-icon">🔔</span>
                <span>Notifications</span>
            </a>

            <a href="client_demandes.php" class="nav-link">
                <span class="nav-icon">📝</span>
                <span>Mes demandes</span>
            </a>

            <!-- PAGE ACTUELLE -->
            <a href="client_support.php"
               class="nav-link active"
               aria-current="page">
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
                <p class="topbar-label">
                    BANK-SIM / Espace client
                </p>

                <h2>Support client</h2>
            </div>
        </header>

        <!-- BANNIÈRE DE BIENVENUE -->
        <section class="welcome-banner">
            <h1>Support client</h1>

            <p>
                Bonjour <?= e($client["NOM"]) ?>,
                comment pouvons-nous vous aider ?
            </p>
        </section>

        <section class="section-block support-content">

            <!-- INFORMATIONS -->
            <div class="support-info">
                <div class="support-icon">🛟</div>

                <div>
                    <strong>Besoin d'assistance ?</strong>

                    <p class="support-intro">
                        Décrivez votre problème ou votre question
                        avec suffisamment de détails pour faciliter
                        le traitement de votre demande.
                    </p>
                </div>
            </div>

            <!-- FORMULAIRE -->
            <div class="support-form-panel">

                <h2>Créer une demande de support</h2>

                <form
                    action="traiter_support.php"
                    method="POST"
                    class="support-form"
                >

                    <div class="form-group">
                        <label for="sujet">
                            Sujet de la demande
                        </label>

                        <input
                            type="text"
                            id="sujet"
                            name="sujet"
                            maxlength="150"
                            required
                            placeholder="Ex. : problème avec un virement"
                        >
                    </div>

                    <div class="form-group">
                        <label for="message">
                            Décrivez votre problème
                        </label>

                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            required
                            placeholder="Expliquez votre demande ici..."
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        class="btn-principal"
                    >
                        📩 Envoyer la demande
                    </button>

                </form>

            </div>

            <p class="support-retour">
                <a href="dashboard.php">
                    ← Retour au tableau de bord
                </a>
            </p>

        </section>

        <footer class="dashboard-footer">
            BANK-SIM — Espace sécurisé
        </footer>

    </main>

</div>

</body>
</html>
