
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Protection des données affichées en HTML.
function e($valeur)
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, "UTF-8");
}

// Afficher une information même si elle n'est pas renseignée.
function afficherInformation($valeur)
{
    if ($valeur === null || trim((string) $valeur) === "") {
        return "Non renseigné";
    }

    return (string) $valeur;
}

// Valider l'identifiant du client reçu dans l'URL.
$id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id <= 0) {
    http_response_code(400);
    exit("Identifiant client invalide.");
}

// Récupérer les informations du client.
$sql = "SELECT
            ID,
            NOM,
            SOLDE,
            DATE_NAISSANCE,
            NATIONALITE,
            PAYS_RESIDENCE,
            TELEPHONE,
            EMAIL,
            ADRESSE
        FROM clients
        WHERE ID = :id
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $id]);

$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier que le client existe.
if (!$client) {
    http_response_code(404);
    exit("Client introuvable.");
}

// Récupérer tous les comptes associés à ce client.
$sqlComptes = "SELECT
                    ID,
                    NUMERO_COMPTE,
                    TYPE_COMPTE,
                    SOLDE,
                    STATUT
               FROM comptes
               WHERE CLIENT_ID = :client_id
               ORDER BY ID ASC";

$stmtComptes = $pdo->prepare($sqlComptes);
$stmtComptes->execute([":client_id" => $id]);

$comptesClient = $stmtComptes->fetchAll(PDO::FETCH_ASSOC);

// Nom du conseiller connecté.
$nomConseiller = $_SESSION["nom"] ?? "Conseiller";

// Préparer l'affichage.
$nomClient = trim((string) $client["NOM"]);

$initiale = $nomClient !== ""
    ? strtoupper(substr($nomClient, 0, 1))
    : "C";

$solde = number_format(
    (float) $client["SOLDE"],
    2,
    ",",
    " "
);

// Formater la date de naissance si elle existe.
$dateNaissance = "Non renseigné";

if (!empty($client["DATE_NAISSANCE"])) {
    $date = DateTime::createFromFormat(
        "!Y-m-d",
        $client["DATE_NAISSANCE"]
    );

    if ($date !== false) {
        $dateNaissance = $date->format("d/m/Y");
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Détail client - BANK-SIM</title>

    <link rel="stylesheet" href="style.css?v=4">

    <style>
        :root {
            --detail-navy: #14243A;
            --detail-navy-light: #203651;
            --detail-gold: #C7A45D;
            --detail-ivory: #F5F0E4;
            --detail-white: #FFFFFF;
            --detail-text: #253348;
            --detail-muted: #78808D;
            --detail-border: #E7E0D2;
        }

        .detail-page,
        .detail-page * {
            box-sizing: border-box;
        }

        .detail-page {
            width: 100%;
            max-width: 1250px;
            margin: 0 auto;
        }

        .detail-page .topbar {
            margin-bottom: 28px;
        }

        .detail-heading {
            margin-bottom: 25px;
        }

        .detail-heading h2 {
            margin: 0 0 8px;
            color: var(--detail-navy);
            font-size: clamp(24px, 3vw, 32px);
            letter-spacing: -0.5px;
        }

        .detail-heading p {
            margin: 0;
            color: var(--detail-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        /* Bannière du client */
        .client-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 22px;
            padding: 30px;
            margin-bottom: 25px;
            color: var(--detail-white);
            background: linear-gradient(
                120deg,
                var(--detail-navy),
                var(--detail-navy-light)
            );
            border: 1px solid #263B55;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(20, 36, 58, 0.08);
        }

        .client-banner-main {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .client-avatar-large {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 68px;
            height: 68px;
            flex-shrink: 0;
            color: #E2C98F;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(199, 164, 93, 0.65);
            border-radius: 16px;
            font-size: 28px;
            font-weight: 700;
        }

        .client-banner-label {
            margin: 0 0 8px;
            color: #E2C98F;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.6px;
            text-transform: uppercase;
        }

        .client-banner h3 {
            margin: 0 0 8px;
            color: #FFFFFF;
            font-size: clamp(20px, 3vw, 27px);
            overflow-wrap: anywhere;
        }

        .client-banner-reference {
            margin: 0;
            color: #D4DCE6;
            font-size: 12px;
        }

        .client-banner-badge {
            flex-shrink: 0;
            padding: 9px 12px;
            color: #E2C98F;
            background: rgba(199, 164, 93, 0.1);
            border: 1px solid rgba(199, 164, 93, 0.4);
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
        }

        /* Organisation des informations */
        .detail-section-title {
            margin: 30px 0 15px;
            color: var(--detail-navy);
            font-size: 17px;
            font-weight: 700;
        }

        .detail-cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .detail-card {
            min-width: 0;
            padding: 25px;
            background: var(--detail-white);
            border: 1px solid var(--detail-border);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(20, 36, 58, 0.035);
        }

        .detail-card-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
        }

        .detail-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            color: #80642D;
            background: var(--detail-ivory);
            border: 1px solid #E8D9B5;
            border-radius: 11px;
            font-size: 22px;
        }

        .detail-card-label {
            margin: 0 0 5px;
            color: var(--detail-muted);
            font-size: 12px;
        }

        .detail-card h4 {
            margin: 0;
            color: var(--detail-navy);
            font-size: 15px;
        }

        .detail-value {
            padding-top: 18px;
            border-top: 1px solid #F0ECE4;
            color: var(--detail-navy);
            font-size: 17px;
            font-weight: 600;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .detail-value + .detail-value {
            margin-top: 15px;
        }

        .detail-value small {
            display: block;
            margin-top: 7px;
            color: var(--detail-muted);
            font-size: 11px;
            font-weight: 400;
        }

        .detail-value-empty {
            color: var(--detail-muted);
            font-style: italic;
            font-weight: 400;
        }

        .detail-solde {
            color: #216647;
            font-size: clamp(22px, 3vw, 28px);
        }

        .detail-account-number {
            color: #80642D;
            font-size: 18px;
            letter-spacing: 0.3px;
            overflow-wrap: anywhere;
        }

        .detail-account-status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            background: var(--detail-ivory);
            color: var(--detail-navy);
            font-size: 12px;
            font-weight: 700;
        }

        .detail-card-full {
            grid-column: 1 / -1;
        }

        /* Panneau des actions */
        .detail-actions-panel {
            padding: 25px;
            background: var(--detail-white);
            border: 1px solid var(--detail-border);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(20, 36, 58, 0.035);
        }

        .detail-actions-panel h3 {
            margin: 0 0 8px;
            color: var(--detail-navy);
            font-size: 17px;
        }

        .detail-actions-description {
            margin: 0 0 22px;
            color: var(--detail-muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .detail-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .detail-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 43px;
            padding: 11px 16px;
            border-radius: 7px;
            text-decoration: none;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .detail-button-primary {
            color: #FFFFFF;
            background: var(--detail-navy);
            border: 1px solid var(--detail-navy);
        }

        .detail-button-primary:hover {
            background: var(--detail-navy-light);
            transform: translateY(-1px);
        }

        .detail-button-secondary {
            color: #80642D;
            background: #F8F0DD;
            border: 1px solid #E8D9B5;
        }

        .detail-button-secondary:hover {
            background: #EFE2C2;
            transform: translateY(-1px);
        }

        .detail-footer-note {
            margin-top: 20px;
            color: var(--detail-muted);
            font-size: 11px;
            line-height: 1.7;
        }

        @media (max-width: 700px) {
            .detail-cards {
                grid-template-columns: 1fr;
            }

            .detail-card-full {
                grid-column: auto;
            }

            .client-banner {
                align-items: flex-start;
                flex-direction: column;
                padding: 22px;
            }

            .client-banner-main {
                align-items: flex-start;
            }

            .client-avatar-large {
                width: 52px;
                height: 52px;
                font-size: 22px;
            }

            .detail-card,
            .detail-actions-panel {
                padding: 20px;
            }
        }

        @media (max-width: 420px) {
            .detail-buttons {
                flex-direction: column;
            }

            .detail-button {
                width: 100%;
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

        <div class="detail-page">

            <header class="topbar">
                <div class="breadcrumb">
                    BANK-SIM / Clients / Détail client
                </div>

                <div class="topbar-date">
                    Espace conseiller
                </div>
            </header>

            <section class="section-block">

                <div class="detail-heading">
                    <h2>Détail du client</h2>

                    <p>
                        Consultez les informations personnelles et financières
                        enregistrées pour ce client.
                    </p>
                </div>

                <!-- IDENTITÉ DU CLIENT -->
                <div class="client-banner">

                    <div class="client-banner-main">

                        <div class="client-avatar-large">
                            <?= e($initiale) ?>
                        </div>

                        <div>
                            <p class="client-banner-label">
                                Fiche client
                            </p>

                            <h3><?= e($nomClient) ?></h3>

                            <p class="client-banner-reference">
                                Référence client #<?= (int) $client["ID"] ?>
                            </p>
                        </div>

                    </div>

                    <div class="client-banner-badge">
                        Fiche consultée
                    </div>

                </div>

                <!-- INFORMATIONS PERSONNELLES -->
                <h3 class="detail-section-title">
                    Informations personnelles
                </h3>

                <div class="detail-cards">

                    <article class="detail-card">
                        <div class="detail-card-top">
                            <div class="detail-card-icon">♙</div>
                            <div>
                                <p class="detail-card-label">Identification</p>
                                <h4>Nom complet</h4>
                            </div>
                        </div>

                        <div class="detail-value">
                            <?= e($nomClient) ?>
                            <small>Nom enregistré dans BANK-SIM</small>
                        </div>
                    </article>

                    <article class="detail-card">
                        <div class="detail-card-top">
                            <div class="detail-card-icon">▦</div>
                            <div>
                                <p class="detail-card-label">État civil</p>
                                <h4>Date de naissance</h4>
                            </div>
                        </div>

                        <div class="detail-value <?= $dateNaissance === "Non renseigné" ? "detail-value-empty" : "" ?>">
                            <?= e($dateNaissance) ?>
                        </div>
                    </article>

                    <article class="detail-card">
                        <div class="detail-card-top">
                            <div class="detail-card-icon">◎</div>
                            <div>
                                <p class="detail-card-label">Identité</p>
                                <h4>Nationalité</h4>
                            </div>
                        </div>

                        <?php $nationalite = afficherInformation($client["NATIONALITE"]); ?>

                        <div class="detail-value <?= $nationalite === "Non renseigné" ? "detail-value-empty" : "" ?>">
                            <?= e($nationalite) ?>
                        </div>
                    </article>

                    <article class="detail-card">
                        <div class="detail-card-top">
                            <div class="detail-card-icon">⌖</div>
                            <div>
                                <p class="detail-card-label">Résidence</p>
                                <h4>Pays de résidence</h4>
                            </div>
                        </div>

                        <?php $pays = afficherInformation($client["PAYS_RESIDENCE"]); ?>

                        <div class="detail-value <?= $pays === "Non renseigné" ? "detail-value-empty" : "" ?>">
                            <?= e($pays) ?>
                        </div>
                    </article>

                    <article class="detail-card">
                        <div class="detail-card-top">
                            <div class="detail-card-icon">☎</div>
                            <div>
                                <p class="detail-card-label">Contact</p>
                                <h4>Téléphone</h4>
                            </div>
                        </div>

                        <?php $telephone = afficherInformation($client["TELEPHONE"]); ?>

                        <div class="detail-value <?= $telephone === "Non renseigné" ? "detail-value-empty" : "" ?>">
                            <?= e($telephone) ?>
                        </div>
                    </article>

                    <article class="detail-card">
                        <div class="detail-card-top">
                            <div class="detail-card-icon">✉</div>
                            <div>
                                <p class="detail-card-label">Contact</p>
                                <h4>Adresse e-mail</h4>
                            </div>
                        </div>

                        <?php $email = afficherInformation($client["EMAIL"]); ?>

                        <div class="detail-value <?= $email === "Non renseigné" ? "detail-value-empty" : "" ?>">
                            <?= e($email) ?>
                        </div>
                    </article>

                    <article class="detail-card detail-card-full">
                        <div class="detail-card-top">
                            <div class="detail-card-icon">⌂</div>
                            <div>
                                <p class="detail-card-label">Coordonnées</p>
                                <h4>Adresse personnelle</h4>
                            </div>
                        </div>

                        <?php $adresse = afficherInformation($client["ADRESSE"]); ?>

                        <div class="detail-value <?= $adresse === "Non renseigné" ? "detail-value-empty" : "" ?>">
                            <?= nl2br(e($adresse)) ?>
                        </div>
                    </article>

                </div>

                <!-- INFORMATIONS FINANCIÈRES -->
                <h3 class="detail-section-title">
                    Informations financières
                </h3>

                <!-- COMPTES BANCAIRES ET NUMÉROS DE COMPTE -->
                <div class="detail-cards">

                    <?php if (count($comptesClient) > 0): ?>

                        <?php foreach ($comptesClient as $compte): ?>

                            <article class="detail-card">

                                <div class="detail-card-top">
                                    <div class="detail-card-icon">▣</div>

                                    <div>
                                        <p class="detail-card-label">
                                            Compte bancaire
                                        </p>

                                        <h4>
                                            <?= e($compte["TYPE_COMPTE"]) ?>
                                        </h4>
                                    </div>
                                </div>

                                <div class="detail-value detail-account-number">
                                    <?= e($compte["NUMERO_COMPTE"]) ?>

                                    <small>Numéro de compte</small>
                                </div>

                                <div class="detail-value detail-solde">
                                    <?= number_format(
                                        (float) $compte["SOLDE"],
                                        2,
                                        ",",
                                        " "
                                    ) ?> MAD

                                    <small>Solde de ce compte</small>
                                </div>

                                <div class="detail-value">
                                    <span class="detail-account-status">
                                        <?= e($compte["STATUT"]) ?>
                                    </span>

                                    <small>Statut du compte</small>
                                </div>

                            </article>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <article class="detail-card detail-card-full">

                            <div class="detail-card-top">
                                <div class="detail-card-icon">▣</div>

                                <div>
                                    <p class="detail-card-label">
                                        Comptes bancaires
                                    </p>

                                    <h4>Aucun compte disponible</h4>
                                </div>
                            </div>

                            <div class="detail-value detail-value-empty">
                                Aucun compte n'est encore associé à ce client.
                            </div>

                        </article>

                    <?php endif; ?>

                </div>

                <!-- SOLDE ENREGISTRÉ DANS LA TABLE CLIENTS -->
                <div class="detail-cards">

                    <article class="detail-card">
                        <div class="detail-card-top">
                            <div class="detail-card-icon">◈</div>
                            <div>
                                <p class="detail-card-label">Situation financière</p>
                                <h4>Solde enregistré du client</h4>
                            </div>
                        </div>

                        <div class="detail-value detail-solde">
                            <?= $solde ?> MAD

                            <small>
                                Solde présent dans la table clients.
                                Les soldes des comptes sont gérés séparément.
                            </small>
                        </div>
                    </article>

                    <article class="detail-card">
                        <div class="detail-card-top">
                            <div class="detail-card-icon">▣</div>
                            <div>
                                <p class="detail-card-label">Référence</p>
                                <h4>Identifiant client</h4>
                            </div>
                        </div>

                        <div class="detail-value">
                            #<?= (int) $client["ID"] ?>

                            <small>
                                Identifiant interne BANK-SIM
                            </small>
                        </div>
                    </article>

                </div>

                <!-- ACTIONS -->
                <div class="detail-actions-panel">

                    <h3>Actions disponibles</h3>

                    <p class="detail-actions-description">
                        Consultez l'historique des transactions ou revenez
                        à la liste des clients.
                    </p>

                    <div class="detail-buttons">

                        <a
                            class="detail-button detail-button-primary"
                            href="conseiller_transactions.php?id=<?= (int) $client["ID"] ?>"
                        >
                            <span aria-hidden="true">↔</span>
                            Voir les transactions
                        </a>

                        <a
                            class="detail-button detail-button-secondary"
                            href="conseiller_clients.php"
                        >
                            <span aria-hidden="true">←</span>
                            Retour aux clients
                        </a>

                    </div>

                    <p class="detail-footer-note">
                        Ces informations sont réservées aux conseillers
                        authentifiés. Les données personnelles non encore
                        enregistrées seront indiquées comme non renseignées.
                    </p>

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
