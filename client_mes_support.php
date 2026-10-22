
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

$client_id = (int) $_SESSION["client_id"];

// Récupérer uniquement les tickets du client connecté.
$sql = "SELECT ID, SUJET, MESSAGE, STATUT, DATE_CREATION
        FROM support
        WHERE CLIENT_ID = :client_id
        ORDER BY DATE_CREATION DESC, ID DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([":client_id" => $client_id]);

$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Éviter une redéclaration si cette fonction existe déjà.
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

function libelleSupport($statut): string
{
    $statuts = [
        "OUVERT" => "Ouvert",
        "TRAITE" => "Traité"
    ];

    return $statuts[$statut] ?? "En attente";
}

function classeSupport($statut): string
{
    switch ($statut) {
        case "OUVERT":
            return "ticket-ouvert";

        case "TRAITE":
            return "ticket-traite";

        default:
            return "ticket-autre";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes tickets - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .tickets-content {
            padding-bottom: 25px;
        }

        .tickets-intro {
            margin-bottom: 24px;
            padding: 24px;
            border: 1px solid #e8e2d6;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 5px 18px rgba(20, 36, 59, 0.04);
        }

        .tickets-intro h3 {
            margin: 0 0 10px;
            color: #14243b;
        }

        .tickets-intro p {
            margin: 0;
            color: #64748b;
            line-height: 1.8;
        }

        .ticket-card {
            margin-bottom: 20px;
            padding: 24px;
            border: 1px solid #e8e2d6;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 5px 18px rgba(20, 36, 59, 0.04);
        }

        .ticket-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 15px;
        }

        .ticket-header h3 {
            margin: 0;
            color: #14243b;
            overflow-wrap: anywhere;
        }

        .ticket-id {
            display: inline-block;
            margin-bottom: 8px;
            color: #9a793d;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .ticket-statut {
            display: inline-block;
            flex-shrink: 0;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .ticket-ouvert {
            color: #8a5a00;
            background: #fff2d7;
        }

        .ticket-traite {
            color: #17653a;
            background: #e2f6e9;
        }

        .ticket-autre {
            color: #46566c;
            background: #edf0f4;
        }

        .ticket-separateur {
            height: 1px;
            margin: 18px 0;
            background: #edf0f4;
        }

        .ticket-label {
            margin-bottom: 8px;
            color: #14243b;
            font-size: 14px;
            font-weight: 700;
        }

        .ticket-message {
            color: #475569;
            line-height: 1.8;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .ticket-date {
            color: #64748b;
            font-size: 13px;
        }

        .tickets-vides {
            padding: 36px 20px;
            text-align: center;
        }

        .icone-vide {
            margin-bottom: 12px;
            font-size: 38px;
        }

        .tickets-vides h3 {
            margin: 0 0 10px;
            color: #14243b;
        }

        .tickets-vides p {
            margin: 0;
            color: #64748b;
            line-height: 1.7;
        }

        .ticket-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .ticket-actions a {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 9px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .ticket-btn-principal {
            color: #fff;
            background: #14243b;
        }

        .ticket-btn-principal:hover {
            background: #203955;
        }

        .ticket-btn-secondaire {
            border: 1px solid #d8c59e;
            color: #80632e;
            background: #fff;
        }

        .ticket-btn-secondaire:hover {
            background: #fbf7ed;
        }

        @media (max-width: 600px) {
            .ticket-card {
                padding: 18px;
            }

            .ticket-header {
                flex-direction: column;
            }

            .ticket-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body class="premium-dashboard">

<div class="app-layout">

    <!-- MENU LATERAL COMMUN À BANK-SIM -->
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

            <a href="client_support.php" class="nav-link">
                <span class="nav-icon">🆘</span>
                <span>Support</span>
            </a>

            <!-- SEUL LIEN ACTIF SUR CETTE PAGE -->
            <a href="client_mes_support.php"
               class="nav-link active"
               aria-current="page">
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
                <h2>Mes tickets</h2>
            </div>
        </header>

        <section class="section-block tickets-content">

            <div class="section-heading">
                <h2>Suivi de mes tickets</h2>
            </div>

            <div class="tickets-intro">
                <h3>📂 Mes demandes d'assistance</h3>
                <p>
                    Retrouvez ici les messages envoyés au support,
                    leur date de création et leur état de traitement.
                </p>
            </div>

            <?php if (empty($demandes)): ?>

                <div class="panel tickets-vides">

                    <div class="icone-vide">📨</div>

                    <h3>Aucun ticket pour le moment</h3>

                    <p>
                        Vous n'avez pas encore envoyé de demande
                        d'assistance. Vos tickets apparaîtront ici
                        après leur création.
                    </p>

                    <div class="ticket-actions" style="justify-content:center;">
                        <a href="client_support.php"
                           class="ticket-btn-principal">
                            ➕ Créer une demande
                        </a>
                    </div>

                </div>

            <?php else: ?>

                <?php foreach ($demandes as $demande): ?>

                    <article class="ticket-card">

                        <div class="ticket-header">
                            <div>
                                <span class="ticket-id">
                                    TICKET N° <?= e($demande["ID"]) ?>
                                </span>

                                <h3><?= e($demande["SUJET"]) ?></h3>
                            </div>

                            <span class="ticket-statut <?= e(classeSupport($demande["STATUT"])) ?>">
                                <?= e(libelleSupport($demande["STATUT"])) ?>
                            </span>
                        </div>

                        <div class="ticket-separateur"></div>

                        <div class="ticket-label">Message envoyé</div>

                        <div class="ticket-message">
                            <?= nl2br(e($demande["MESSAGE"])) ?>
                        </div>

                        <div class="ticket-separateur"></div>

                        <div class="ticket-date">
                            <strong>Date de création :</strong>
                            <?= e($demande["DATE_CREATION"]) ?>
                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

            <div class="ticket-actions">

                <a href="client_support.php"
                   class="ticket-btn-principal">
                    ➕ Nouvelle demande
                </a>

                <a href="dashboard.php"
                   class="ticket-btn-secondaire">
                    ← Retour au tableau de bord
                </a>

            </div>

        </section>

        <footer class="dashboard-footer">
            BANK-SIM — Espace client sécurisé
        </footer>

    </main>

</div>

</body>
</html>
