
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Créer un jeton CSRF pour protéger les formulaires.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Récupérer toutes les demandes et distinguer
// les nouveaux candidats des clients existants.
$sql = "SELECT
            demandes.ID,
            demandes.CLIENT_ID,
            COALESCE(demandes.NOM_COMPLET, clients.NOM) AS NOM,
            demandes.TYPE_DEMANDE,
            demandes.STATUT,
            demandes.DATE_DEMANDE
        FROM demandes
        LEFT JOIN clients
            ON demandes.CLIENT_ID = clients.ID
        ORDER BY demandes.DATE_DEMANDE DESC";

$stmt = $pdo->query($sql);
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nomConseiller = $_SESSION["nom"] ?? "Conseiller";

// Sécuriser les valeurs affichées dans le HTML.
function e($valeur)
{
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

$nombreDemandes = count($demandes);
$nombreEnAttente = 0;

foreach ($demandes as $demande) {
    if ($demande["STATUT"] === "EN_ATTENTE") {
        $nombreEnAttente++;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Demandes des clients - BANK-SIM</title>

    <link rel="stylesheet" href="style.css?v=3">

    <style>
        .resume-demandes {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 22px;
        }

        .carte-resume {
            flex: 1;
            min-width: 180px;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px;
            background: #FFFFFF;
            border: 1px solid #E7E0D2;
            border-radius: 10px;
        }

        .resume-icone {
            width: 45px;
            height: 45px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F5F0E4;
            border-radius: 9px;
            font-size: 21px;
        }

        .resume-texte p {
            margin: 0 0 5px;
            color: #78808D;
            font-size: 12px;
        }

        .resume-texte strong {
            color: #14243A;
            font-size: 23px;
        }

        .demande-id {
            color: #78808D;
        }

        .nom-client {
            color: #14243A;
            font-weight: 600;
        }

        .badge-demande {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-attente {
            color: #80642D;
            background: #F8F0DD;
            border: 1px solid #E8D9B5;
        }

        .badge-acceptee {
            color: #216647;
            background: #E7F5EC;
            border: 1px solid #C9E8D5;
        }

        .badge-refusee {
            color: #A83B3B;
            background: #FFF0EE;
            border: 1px solid #F0D5D1;
        }

        .badge-autre {
            color: #475569;
            background: #EEF1F5;
            border: 1px solid #DCE2EA;
        }

        .actions-demande {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .actions-demande form {
            margin: 0;
        }

        .btn-demande {
            display: inline-block;
            padding: 8px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
        }

        .btn-examiner {
            color: #14243A;
            background: #F5F0E4;
            border: 1px solid #E7E0D2;
        }

        .btn-examiner:hover {
            background: #EAE0CB;
        }

        .btn-accepter {
            color: #216647;
            background: #E7F5EC;
            border: 1px solid #C9E8D5;
        }

        .btn-accepter:hover {
            background: #D5EEDD;
        }

        .btn-refuser {
            color: #A83B3B;
            background: #FFF0EE;
            border: 1px solid #F0D5D1;
        }

        .btn-refuser:hover {
            background: #F9DFDB;
        }

        .message-vide {
            padding: 35px 20px;
            text-align: center;
            color: #78808D;
        }

        .date-demande {
            white-space: nowrap;
            color: #5F6876;
            font-size: 12px;
        }

        @media (max-width: 600px) {
            .carte-resume {
                min-width: 100%;
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

            <a href="conseiller_clients.php" class="nav-link">
                <span class="nav-icon">♙</span>
                Gestion des clients
            </a>

            <a href="conseiller_demandes.php" class="nav-link active">
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

        <header class="topbar">
            <div class="breadcrumb">
                BANK-SIM / Demandes
            </div>

            <div class="topbar-date">
                Espace conseiller
            </div>
        </header>

        <section class="section-block">

            <div class="section-heading">
                <div>
                    <h2>Demandes des clients</h2>

                    <p>
                        Consultez les demandes et suivez leur statut.
                    </p>
                </div>
            </div>

            <!-- MESSAGES DE CONFIRMATION -->
            <?php if (!empty($_SESSION["message_succes"])): ?>

                <div style="padding:14px 18px;margin-bottom:18px;background:#E7F5EC;color:#216647;border:1px solid #C9E8D5;border-radius:8px;">
                    <?= e($_SESSION["message_succes"]) ?>
                </div>

                <?php unset($_SESSION["message_succes"]); ?>

            <?php endif; ?>

            <?php if (!empty($_SESSION["message_erreur"])): ?>

                <div style="padding:14px 18px;margin-bottom:18px;background:#FFF0EE;color:#A83B3B;border:1px solid #F0D5D1;border-radius:8px;">
                    <?= e($_SESSION["message_erreur"]) ?>
                </div>

                <?php unset($_SESSION["message_erreur"]); ?>

            <?php endif; ?>

            <!-- INDICATEURS -->
            <div class="resume-demandes">

                <div class="carte-resume">
                    <div class="resume-icone">▤</div>

                    <div class="resume-texte">
                        <p>Total des demandes</p>
                        <strong><?= $nombreDemandes ?></strong>
                    </div>
                </div>

                <div class="carte-resume">
                    <div class="resume-icone">◷</div>

                    <div class="resume-texte">
                        <p>En attente</p>
                        <strong><?= $nombreEnAttente ?></strong>
                    </div>
                </div>

            </div>

            <!-- TABLEAU DES DEMANDES -->
            <div class="panel">

                <div class="table-responsive">

                    <table class="transactions-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Type de demande</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (empty($demandes)): ?>

                                <tr>
                                    <td colspan="6" class="message-vide">
                                        Aucune demande pour le moment.
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($demandes as $demande): ?>

                                    <tr>

                                        <td class="demande-id">
                                            #<?= (int) $demande["ID"] ?>
                                        </td>

                                        <td class="nom-client">
                                            <?= e($demande["NOM"] ?: "Nom non renseigné") ?>
                                        </td>

                                        <td>
                                            <?= e($demande["TYPE_DEMANDE"]) ?>
                                        </td>

                                        <td>
                                            <?php
                                            $statut = (string) $demande["STATUT"];

                                            if ($statut === "EN_ATTENTE") {
                                                $classeStatut = "badge-attente";
                                            } elseif ($statut === "ACCEPTEE") {
                                                $classeStatut = "badge-acceptee";
                                            } elseif ($statut === "REFUSEE") {
                                                $classeStatut = "badge-refusee";
                                            } else {
                                                $classeStatut = "badge-autre";
                                            }
                                            ?>

                                            <span class="badge-demande <?= $classeStatut ?>">
                                                <?= e($statut) ?>
                                            </span>
                                        </td>

                                        <td class="date-demande">
                                            <?= e($demande["DATE_DEMANDE"]) ?>
                                        </td>

                                        <td>

                                            <?php if ($demande["STATUT"] === "EN_ATTENTE"): ?>

                                                <div class="actions-demande">

                                                    <!-- EXAMINER UNIQUEMENT LES NOUVEAUX CANDIDATS -->
                                                    <?php if ($demande["CLIENT_ID"] === null): ?>

                                                        <a
                                                            href="examiner_demande.php?id=<?= (int) $demande["ID"] ?>"
                                                            class="btn-demande btn-examiner"
                                                        >
                                                            🔎 Examiner
                                                        </a>

                                                    <?php endif; ?>

                                                    <!-- FORMULAIRE ACCEPTER -->
                                                    <form
                                                        action="traiter_demande.php"
                                                        method="POST"
                                                        onsubmit="return confirm('Confirmer l’acceptation de cette demande ?');"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= (int) $demande["ID"] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="accepter"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= e($_SESSION["csrf_token"]) ?>"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="btn-demande btn-accepter"
                                                        >
                                                            ✓ Accepter
                                                        </button>

                                                    </form>

                                                    <!-- FORMULAIRE REFUSER -->
                                                    <form
                                                        action="traiter_demande.php"
                                                        method="POST"
                                                        onsubmit="return confirm('Confirmer le refus de cette demande ?');"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= (int) $demande["ID"] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="refuser"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= e($_SESSION["csrf_token"]) ?>"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="btn-demande btn-refuser"
                                                        >
                                                            ✕ Refuser
                                                        </button>

                                                    </form>

                                                </div>

                                            <?php else: ?>

                                                <span>Demande traitée</span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

        <footer class="dashboard-footer">
            BANK-SIM — Gestion des demandes
        </footer>

    </main>

</div>

</body>
</html>
