
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

function e($valeur)
{
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

function afficherInformation($valeur)
{
    if ($valeur === null || trim((string) $valeur) === "") {
        return "Non renseigné";
    }

    return e($valeur);
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit("Identifiant de demande invalide.");
}

// Récupérer la demande et, si possible, le numéro du compte.
$sql = "SELECT
            d.ID,
            d.CLIENT_ID,
            d.NOM_COMPLET,
            d.DATE_NAISSANCE,
            d.NATIONALITE,
            d.PAYS_RESIDENCE,
            d.TELEPHONE,
            d.EMAIL,
            d.ADRESSE,
            d.TYPE_DEMANDE,
            d.STATUT,
            d.DATE_DEMANDE,
            c.NUMERO_COMPTE
        FROM demandes d
        LEFT JOIN comptes c
            ON c.CLIENT_ID = d.CLIENT_ID
           AND c.TYPE_COMPTE = 'COURANT'
        WHERE d.ID = :id
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $id]);

$demande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$demande) {
    http_response_code(404);
    exit("Demande introuvable.");
}

$nomConseiller = $_SESSION["nom"] ?? "Conseiller";

// Formater la date de naissance.
$dateNaissance = "Non renseignée";

if (!empty($demande["DATE_NAISSANCE"])) {
    $date = DateTime::createFromFormat(
        "!Y-m-d",
        $demande["DATE_NAISSANCE"]
    );

    if ($date !== false) {
        $dateNaissance = $date->format("d/m/Y");
    }
}

// Formater la date de la demande.
$dateDemande = "Non renseignée";

if (!empty($demande["DATE_DEMANDE"])) {
    try {
        $date = new DateTime($demande["DATE_DEMANDE"]);
        $dateDemande = $date->format("d/m/Y à H:i");
    } catch (Exception $exception) {
        $dateDemande = e($demande["DATE_DEMANDE"]);
    }
}

$numeroCompte = $demande["NUMERO_COMPTE"] ?? null;

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Examiner une demande - BANK-SIM</title>

    <link rel="stylesheet" href="style.css?v=3">

    <style>
        .examiner-intro {
            margin-bottom: 24px;
        }

        .examiner-intro h2 {
            margin-bottom: 8px;
            color: #14243A;
        }

        .examiner-intro p {
            color: #78808D;
            margin: 0;
        }

        .dossier-entete {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 22px;
            margin-bottom: 22px;
            background: #FFFFFF;
            border: 1px solid #E7E0D2;
            border-radius: 10px;
        }

        .dossier-identite {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .dossier-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            flex-shrink: 0;
            color: #14243A;
            background: #F5F0E4;
            border: 1px solid #E7E0D2;
            border-radius: 50%;
            font-size: 23px;
            font-weight: 700;
        }

        .dossier-identite h3 {
            margin: 0 0 6px;
            color: #14243A;
            font-size: 18px;
        }

        .dossier-identite p {
            margin: 0;
            color: #78808D;
            font-size: 12px;
        }

        .badge-statut {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            color: #80642D;
            background: #F8F0DD;
            border: 1px solid #E8D9B5;
            font-size: 11px;
            font-weight: 700;
        }

        .grille-informations {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 22px;
        }

        .carte-information {
            padding: 22px;
            background: #FFFFFF;
            border: 1px solid #E7E0D2;
            border-radius: 10px;
            min-width: 0;
        }

        .carte-information h3 {
            margin: 0 0 20px;
            padding-bottom: 13px;
            color: #14243A;
            border-bottom: 1px solid #EEE8DD;
            font-size: 15px;
        }

        .information-ligne {
            margin-bottom: 18px;
        }

        .information-ligne:last-child {
            margin-bottom: 0;
        }

        .information-label {
            display: block;
            margin-bottom: 6px;
            color: #78808D;
            font-size: 12px;
        }

        .information-valeur {
            display: block;
            color: #14243A;
            font-size: 14px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .carte-adresse {
            grid-column: 1 / -1;
        }

        .numero-compte {
            color: #80642D;
            font-size: 18px;
            letter-spacing: 0.4px;
        }

        .note-compte {
            margin-top: 10px;
            color: #78808D;
            font-size: 12px;
            line-height: 1.6;
        }

        .actions-examen {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 22px;
        }

        .btn-retour {
            display: inline-block;
            padding: 11px 16px;
            color: #FFFFFF;
            background: #14243A;
            border: 1px solid #14243A;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .btn-retour:hover {
            background: #243B59;
        }

        .note-examen {
            margin-top: 18px;
            padding: 15px 18px;
            color: #5F6876;
            background: #F8F6F1;
            border: 1px solid #E7E0D2;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 700px) {
            .grille-informations {
                grid-template-columns: 1fr;
            }

            .carte-adresse {
                grid-column: auto;
            }

            .dossier-entete {
                align-items: flex-start;
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
                BANK-SIM / Demandes / Examiner
            </div>

            <div class="topbar-date">
                Espace conseiller
            </div>
        </header>

        <section class="section-block">

            <div class="examiner-intro">
                <h2>Examen du dossier</h2>

                <p>
                    Consultez les informations du candidat et son compte,
                    lorsqu'il existe.
                </p>
            </div>

            <!-- IDENTITE ET STATUT -->
            <div class="dossier-entete">

                <div class="dossier-identite">

                    <div class="dossier-avatar">♙</div>

                    <div>
                        <h3>
                            <?= afficherInformation($demande["NOM_COMPLET"]) ?>
                        </h3>

                        <p>
                            Demande n° #<?= (int) $demande["ID"] ?>
                        </p>
                    </div>

                </div>

                <span class="badge-statut">
                    <?= e($demande["STATUT"]) ?>
                </span>

            </div>

            <!-- INFORMATIONS -->
            <div class="grille-informations">

                <div class="carte-information">

                    <h3>Informations personnelles</h3>

                    <div class="information-ligne">
                        <span class="information-label">Nom complet</span>
                        <span class="information-valeur">
                            <?= afficherInformation($demande["NOM_COMPLET"]) ?>
                        </span>
                    </div>

                    <div class="information-ligne">
                        <span class="information-label">Date de naissance</span>
                        <span class="information-valeur">
                            <?= e($dateNaissance) ?>
                        </span>
                    </div>

                    <div class="information-ligne">
                        <span class="information-label">Nationalité</span>
                        <span class="information-valeur">
                            <?= afficherInformation($demande["NATIONALITE"]) ?>
                        </span>
                    </div>

                    <div class="information-ligne">
                        <span class="information-label">Pays de résidence</span>
                        <span class="information-valeur">
                            <?= afficherInformation($demande["PAYS_RESIDENCE"]) ?>
                        </span>
                    </div>

                </div>

                <div class="carte-information">

                    <h3>Coordonnées</h3>

                    <div class="information-ligne">
                        <span class="information-label">Adresse e-mail</span>
                        <span class="information-valeur">
                            <?= afficherInformation($demande["EMAIL"]) ?>
                        </span>
                    </div>

                    <div class="information-ligne">
                        <span class="information-label">Téléphone</span>
                        <span class="information-valeur">
                            <?= afficherInformation($demande["TELEPHONE"]) ?>
                        </span>
                    </div>

                </div>

                <div class="carte-information carte-adresse">

                    <h3>Adresse de résidence</h3>

                    <div class="information-ligne">
                        <span class="information-label">Adresse complète</span>
                        <span class="information-valeur">
                            <?= afficherInformation($demande["ADRESSE"]) ?>
                        </span>
                    </div>

                </div>

                <!-- NOUVELLE CARTE : INFORMATIONS DU COMPTE -->
                <div class="carte-information">

                    <h3>Informations du compte</h3>

                    <div class="information-ligne">
                        <span class="information-label">Numéro de compte courant</span>

                        <?php if (!empty($numeroCompte)): ?>

                            <span class="information-valeur numero-compte">
                                <?= e($numeroCompte) ?>
                            </span>

                        <?php else: ?>

                            <span class="information-valeur">
                                Aucun compte créé pour le moment
                            </span>

                            <p class="note-compte">
                                Le numéro sera disponible après la création
                                du compte.
                            </p>

                        <?php endif; ?>
                    </div>

                </div>

                <div class="carte-information">

                    <h3>Détails de la demande</h3>

                    <div class="information-ligne">
                        <span class="information-label">Type de demande</span>
                        <span class="information-valeur">
                            <?= e($demande["TYPE_DEMANDE"]) ?>
                        </span>
                    </div>

                    <div class="information-ligne">
                        <span class="information-label">Date de soumission</span>
                        <span class="information-valeur">
                            <?= e($dateDemande) ?>
                        </span>
                    </div>

                </div>

            </div>

            <div class="note-examen">
                <strong>Information :</strong>
                cette page permet de consulter le dossier.
                Elle ne modifie pas la demande. Pour prendre une décision,
                retournez à la liste des demandes.
            </div>

            <!-- RETOUR A LA LISTE -->
            <div class="actions-examen">

                <a
                    href="conseiller_demandes.php"
                    class="btn-retour"
                >
                    ← Retour aux demandes
                </a>

            </div>

        </section>

        <footer class="dashboard-footer">
            BANK-SIM — Examen des demandes
        </footer>

    </main>

</div>

</body>
</html>
