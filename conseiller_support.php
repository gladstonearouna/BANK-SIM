
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Protection contre les attaques CSRF.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Protection des données affichées en HTML.
function e($valeur)
{
    return htmlspecialchars(
        (string) ($valeur ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Nom du conseiller connecté.
$nomConseiller = $_SESSION["nom"] ?? "Conseiller";

$initialeConseiller = strtoupper(
    substr(trim($nomConseiller), 0, 1)
);

if ($initialeConseiller === "") {
    $initialeConseiller = "C";
}

// Récupérer les demandes de support.
$sql = "
    SELECT
        s.ID,
        s.CLIENT_ID,
        s.SUJET,
        s.MESSAGE,
        s.STATUT,
        s.DATE_CREATION,
        c.NOM AS NOM_CLIENT
    FROM support s
    LEFT JOIN clients c ON c.ID = s.CLIENT_ID
    ORDER BY
        CASE WHEN s.STATUT = 'OUVERT' THEN 0 ELSE 1 END,
        s.DATE_CREATION DESC,
        s.ID DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques.
$totalDemandes = count($demandes);
$demandesOuvertes = 0;
$demandesTraitees = 0;

foreach ($demandes as $demande) {
    if ($demande["STATUT"] === "OUVERT") {
        $demandesOuvertes++;
    } elseif ($demande["STATUT"] === "TRAITE") {
        $demandesTraitees++;
    }
}

// Récupérer les messages de retour.
$messageSucces = $_SESSION["message_succes"] ?? null;
$messageErreur = $_SESSION["message_erreur"] ?? null;

unset($_SESSION["message_succes"]);
unset($_SESSION["message_erreur"]);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Support client - BANK-SIM</title>

    <!-- Même feuille de style que la page de référence -->
    <link rel="stylesheet" href="style.css?v=4">

    <style>
        /*
         * Styles réservés au contenu du support.
         * La navigation latérale utilise style.css.
         */

        .support-page,
        .support-page * {
            box-sizing: border-box;
        }

        .support-page {
            width: 100%;
            max-width: 1250px;
            margin: 0 auto;
        }

        .support-page .support-heading {
            margin-bottom: 25px;
        }

        .support-page .support-heading h1 {
            margin: 0 0 8px;
            color: var(--navy);
            font-size: clamp(24px, 3vw, 32px);
            letter-spacing: -0.5px;
        }

        .support-page .support-heading p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        /* Messages de confirmation et d'erreur */

        .support-page .message-succes,
        .support-page .message-erreur {
            margin-bottom: 22px;
        }

        /* Statistiques du support */

        .support-page .support-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 32px;
        }

        .support-page .support-stat-card {
            min-width: 0;
            padding: 22px;
            background: var(--white);
            border: 1px solid var(--border);
            border-top: 3px solid var(--gold);
            border-radius: 13px;
            box-shadow: var(--shadow);
        }

        .support-page .support-stat-label {
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }

        .support-page .support-stat-number {
            margin: 0;
            color: var(--navy);
            font-size: 29px;
            font-weight: 700;
            line-height: 1.3;
        }

        .support-page .support-stat-description {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }

        /* En-tête de la liste */

        .support-page .support-section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 18px;
        }

        .support-page .support-section-heading h2 {
            margin: 0;
            color: var(--navy);
            font-size: 17px;
        }

        .support-page .support-section-heading p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .support-page .support-count {
            display: inline-block;
            flex-shrink: 0;
            padding: 7px 11px;
            border: 1px solid #e8d9b5;
            border-radius: 20px;
            background: #f8f1e3;
            color: #8b6b2d;
            font-size: 11px;
            font-weight: 700;
        }

        /* Carte de chaque demande */

        .support-page .support-ticket {
            min-width: 0;
            margin-bottom: 20px;
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 13px;
            box-shadow: var(--shadow);
        }

        .support-page .support-ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding: 22px;
            border-bottom: 1px solid #f0f1f4;
        }

        .support-page .support-ticket-title {
            min-width: 0;
        }

        .support-page .support-ticket-reference {
            margin: 0 0 7px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .support-page .support-ticket-title h3 {
            margin: 0;
            color: var(--navy);
            font-size: 16px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        /* Badges de statut */

        .support-page .support-status {
            display: inline-block;
            flex-shrink: 0;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .support-page .support-status-ouvert {
            background: #fff1d8;
            color: #885b13;
        }

        .support-page .support-status-traite {
            background: #e7f5ef;
            color: var(--green);
        }

        .support-page .support-status-autre {
            background: #edf0f5;
            color: var(--navy);
        }

        /* Contenu de la demande */

        .support-page .support-ticket-body {
            padding: 22px;
        }

        .support-page .support-ticket-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 22px;
            margin-bottom: 20px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        .support-page .support-ticket-meta strong {
            color: var(--navy);
        }

        .support-page .support-client-reference {
            color: #8b6b2d;
            font-weight: 700;
        }

        .support-page .support-message-label {
            margin: 0 0 9px;
            color: var(--navy);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.7px;
            text-transform: uppercase;
        }

        .support-page .support-client-message {
            margin: 0;
            padding: 16px;
            border-left: 3px solid var(--gold);
            border-radius: 0 8px 8px 0;
            background: #faf9f6;
            color: var(--text);
            font-size: 13px;
            line-height: 1.8;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        /* Formulaire de réponse */

        .support-page .support-response {
            margin-top: 22px;
            padding-top: 22px;
            border-top: 1px solid var(--border);
        }

        .support-page .support-response h4 {
            margin: 0 0 17px;
            color: var(--navy);
            font-size: 15px;
        }

        .support-page .support-response label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-size: 12px;
            font-weight: 700;
        }

        .support-page .support-response textarea {
            display: block;
            width: 100%;
            min-height: 115px;
            padding: 13px 14px;
            border: 1px solid #d8dee7;
            border-radius: 9px;
            outline: none;
            background: var(--white);
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            line-height: 1.7;
            resize: vertical;
        }

        .support-page .support-response textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(216, 181, 109, 0.2);
        }

        .support-page .support-help {
            margin: 8px 0 16px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        .support-page .support-response-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .support-page .support-security-note {
            color: var(--muted);
            font-size: 11px;
        }

        .support-page .support-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 43px;
            padding: 11px 17px;
            border: 1px solid var(--navy);
            border-radius: 8px;
            background: var(--navy);
            color: var(--white);
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .support-page .support-submit:hover {
            background: var(--navy-light);
            border-color: var(--gold);
        }

        /* État vide */

        .support-page .support-empty {
            padding: 42px 22px;
            border: 1px dashed #d9dce3;
            border-radius: 13px;
            background: var(--white);
            text-align: center;
        }

        .support-page .support-empty-icon {
            margin-bottom: 13px;
            color: var(--gold);
            font-size: 30px;
        }

        .support-page .support-empty h3 {
            margin: 0 0 8px;
            color: var(--navy);
            font-size: 16px;
        }

        .support-page .support-empty p {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.8;
        }

        .support-page .support-footer-note {
            margin-top: 20px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        /* Adaptation mobile du contenu uniquement */

        @media (max-width: 900px) {
            .support-page .support-stats {
                gap: 12px;
            }

            .support-page .support-stat-card {
                padding: 17px;
            }
        }

        @media (max-width: 600px) {
            .support-page .support-stats {
                grid-template-columns: 1fr;
            }

            .support-page .support-section-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .support-page .support-ticket-header {
                flex-direction: column;
                padding: 18px;
            }

            .support-page .support-ticket-body {
                padding: 18px;
            }

            .support-page .support-response-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .support-page .support-submit {
                width: 100%;
            }
        }
    </style>
</head>

<body class="premium-dashboard">

<div class="app-layout">

    <!-- MENU LATÉRAL : STRUCTURE DE LA PAGE DE RÉFÉRENCE -->

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

            <a href="conseiller_toutes_transactions.php" class="nav-link">
                <span class="nav-icon">↔</span>
                Transactions
            </a>

            <a
                href="conseiller_support.php"
                class="nav-link active"
                aria-current="page"
            >
                <span class="nav-icon">◉</span>
                Support
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="sidebar-user">

                <div class="user-avatar">
                    <?= e($initialeConseiller) ?>
                </div>

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

        <div class="support-page">

            <header class="topbar">

                <div class="breadcrumb">
                    BANK-SIM / Espace conseiller / Support
                </div>

                <div class="topbar-date">
                    Gestion des demandes clients
                </div>

            </header>

            <section class="section-block">

                <div class="support-heading">

                    <h1>Support client</h1>

                    <p>
                        Consultez les demandes des clients, rédigez vos réponses
                        et suivez le traitement de chaque ticket.
                    </p>

                </div>

                <!-- MESSAGES DE RETOUR -->

                <?php if (!empty($messageSucces)): ?>

                    <div class="message-succes" role="status">
                        <?= e($messageSucces) ?>
                    </div>

                <?php endif; ?>

                <?php if (!empty($messageErreur)): ?>

                    <div class="message-erreur" role="alert">
                        <?= e($messageErreur) ?>
                    </div>

                <?php endif; ?>

                <!-- STATISTIQUES -->

                <div class="support-stats">

                    <article class="support-stat-card">

                        <p class="support-stat-label">
                            Total des demandes
                        </p>

                        <p class="support-stat-number">
                            <?= $totalDemandes ?>
                        </p>

                        <p class="support-stat-description">
                            Ensemble des tickets enregistrés
                        </p>

                    </article>

                    <article class="support-stat-card">

                        <p class="support-stat-label">
                            Demandes ouvertes
                        </p>

                        <p class="support-stat-number">
                            <?= $demandesOuvertes ?>
                        </p>

                        <p class="support-stat-description">
                            Demandes en attente de traitement
                        </p>

                    </article>

                    <article class="support-stat-card">

                        <p class="support-stat-label">
                            Demandes traitées
                        </p>

                        <p class="support-stat-number">
                            <?= $demandesTraitees ?>
                        </p>

                        <p class="support-stat-description">
                            Demandes marquées comme traitées
                        </p>

                    </article>

                </div>

                <!-- LISTE DES TICKETS -->

                <div class="support-section-heading">

                    <div>
                        <h2>Liste des demandes</h2>

                        <p>
                            Les demandes ouvertes sont affichées en premier.
                        </p>
                    </div>

                    <span class="support-count">
                        <?= $totalDemandes ?>
                        ticket<?= $totalDemandes > 1 ? "s" : "" ?>
                    </span>

                </div>

                <?php if (empty($demandes)): ?>

                    <div class="support-empty">

                        <div class="support-empty-icon">▤</div>

                        <h3>Aucune demande pour le moment</h3>

                        <p>
                            Les demandes envoyées par les clients apparaîtront
                            ici dès qu'elles seront enregistrées.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach ($demandes as $demande): ?>

                        <article class="support-ticket">

                            <header class="support-ticket-header">

                                <div class="support-ticket-title">

                                    <p class="support-ticket-reference">
                                        Ticket #<?= (int) $demande["ID"] ?>
                                    </p>

                                    <h3>
                                        <?= e($demande["SUJET"]) ?>
                                    </h3>

                                </div>

                                <?php if ($demande["STATUT"] === "OUVERT"): ?>

                                    <span class="support-status support-status-ouvert">
                                        Demande ouverte
                                    </span>

                                <?php elseif ($demande["STATUT"] === "TRAITE"): ?>

                                    <span class="support-status support-status-traite">
                                        Demande traitée
                                    </span>

                                <?php else: ?>

                                    <span class="support-status support-status-autre">
                                        <?= e($demande["STATUT"]) ?>
                                    </span>

                                <?php endif; ?>

                            </header>

                            <div class="support-ticket-body">

                                <div class="support-ticket-meta">

                                    <span>
                                        Client :
                                        <strong>
                                            <?= e(
                                                $demande["NOM_CLIENT"]
                                                ?? "Client inconnu"
                                            ) ?>
                                        </strong>
                                    </span>

                                    <span>
                                        Référence client :
                                        <strong class="support-client-reference">
                                            #<?= (int) $demande["CLIENT_ID"] ?>
                                        </strong>
                                    </span>

                                    <span>
                                        Date :
                                        <strong>
                                            <?= e($demande["DATE_CREATION"]) ?>
                                        </strong>
                                    </span>

                                </div>

                                <p class="support-message-label">
                                    Message du client
                                </p>

                                <div class="support-client-message"><?= e($demande["MESSAGE"]) ?></div>

                                <?php if ($demande["STATUT"] === "OUVERT"): ?>

                                    <form
                                        class="support-response"
                                        method="POST"
                                        action="fermer_support.php"
                                        onsubmit="return confirm('Envoyer cette réponse et marquer le ticket comme traité ?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="support_id"
                                            value="<?= (int) $demande["ID"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION["csrf_token"]) ?>"
                                        >

                                        <h4>
                                            Répondre au client
                                        </h4>

                                        <label
                                            for="reponse_<?= (int) $demande["ID"] ?>"
                                        >
                                            Votre réponse
                                        </label>

                                        <textarea
                                            id="reponse_<?= (int) $demande["ID"] ?>"
                                            name="reponse"
                                            maxlength="200"
                                            required
                                            placeholder="Rédigez une réponse claire et professionnelle..."
                                        ></textarea>

                                        <p class="support-help">
                                            200 caractères maximum. La réponse
                                            sera envoyée au client sous forme de
                                            notification. Le ticket sera marqué
                                            comme traité.
                                        </p>

                                        <div class="support-response-footer">

                                            <span class="support-security-note">
                                                Traitement sécurisé
                                            </span>

                                            <button
                                                type="submit"
                                                class="support-submit"
                                            >
                                                Envoyer la réponse
                                                <span aria-hidden="true">→</span>
                                            </button>

                                        </div>

                                    </form>

                                <?php endif; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

                <p class="support-footer-note">
                    BANK-SIM — Les demandes sont réservées aux conseillers
                    authentifiés. Les réponses envoyées sont enregistrées
                    selon le traitement prévu par l'application.
                </p>

            </section>

            <footer class="dashboard-footer">
                <span>BANK-SIM — Premium Banking</span>
                <span>Espace professionnel conseiller</span>
            </footer>

        </div>

    </main>

</div>

</body>
</html>
