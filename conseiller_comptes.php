
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Récupérer les comptes et les noms des clients associés.
$sql = "SELECT
            comptes.ID,
            comptes.NUMERO_COMPTE,
            comptes.TYPE_COMPTE,
            comptes.SOLDE,
            comptes.STATUT,
            comptes.DATE_CREATION,
            clients.NOM
        FROM comptes
        INNER JOIN clients
            ON comptes.CLIENT_ID = clients.ID
        ORDER BY comptes.DATE_CREATION DESC";

$stmt = $pdo->query($sql);
$comptes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nomConseiller = $_SESSION["nom"] ?? "Conseiller";

// Calculer les indicateurs pour le haut de la page.
$totalComptes = count($comptes);
$comptesActifs = 0;
$comptesSuspendus = 0;

foreach ($comptes as $compte) {
    if ($compte["STATUT"] === "ACTIF") {
        $comptesActifs++;
    } elseif ($compte["STATUT"] === "SUSPENDU") {
        $comptesSuspendus++;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des comptes - BANK-SIM</title>

    <link rel="stylesheet" href="style.css?v=3">
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

            <a href="conseiller_demandes.php" class="nav-link">
                <span class="nav-icon">▤</span>
                Demandes
            </a>

            <a href="conseiller_comptes.php" class="nav-link active">
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
                    <strong><?= htmlspecialchars(
                        $nomConseiller,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?></strong>

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
                BANK-SIM / Gestion des comptes
            </div>

            <div class="topbar-date">
                Espace conseiller
            </div>
        </header>

        <section class="section-block">

            <!-- MESSAGE DE CONFIRMATION -->
            <?php if (!empty($_SESSION["message_succes"])): ?>

                <div class="message-succes">
                    <?= htmlspecialchars(
                        $_SESSION["message_succes"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </div>

                <?php unset($_SESSION["message_succes"]); ?>

            <?php endif; ?>

            <!-- TITRE DE LA PAGE -->
            <div class="section-heading">

                <div>
                    <h2>Gestion des comptes</h2>

                    <p>
                        Consultez les comptes bancaires et suivez
                        leur statut dans BANK-SIM.
                    </p>
                </div>

                <a href="conseiller_ajouter_compte.php"
                   class="btn-create-account">
                    <span aria-hidden="true">＋</span>
                    Créer un compte
                </a>

            </div>

            <!-- INDICATEURS -->
            <div class="resume-clients">

                <div class="panel">
                    <div class="resume-icone">▣</div>

                    <div class="resume-texte">
                        <span>Total des comptes</span>
                        <strong><?= $totalComptes ?></strong>
                    </div>
                </div>

                <div class="panel">
                    <div class="resume-icone">✓</div>

                    <div class="resume-texte">
                        <span>Comptes actifs</span>
                        <strong><?= $comptesActifs ?></strong>
                    </div>
                </div>

                <div class="panel">
                    <div class="resume-icone">!</div>

                    <div class="resume-texte">
                        <span>Comptes suspendus</span>
                        <strong><?= $comptesSuspendus ?></strong>
                    </div>
                </div>

            </div>

            <!-- LISTE DES COMPTES -->
            <div class="section-heading">

                <div>
                    <h2>Liste des comptes</h2>

                    <p>
                        <?= $totalComptes ?>
                        compte(s) enregistré(s).
                    </p>
                </div>

            </div>

            <?php if (empty($comptes)): ?>

                <div class="panel message-vide">
                    <p>
                        Aucun compte n'est enregistré pour le moment.
                    </p>
                </div>

            <?php else: ?>

                <div class="panel">

                    <div class="table-responsive">

                        <table class="transactions-table">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Numéro de compte</th>
                                    <th>Type de compte</th>
                                    <th>Solde</th>
                                    <th>Statut</th>
                                    <th>Date de création</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($comptes as $compte): ?>

                                    <tr>

                                        <td>
                                            <?= (int) $compte["ID"] ?>
                                        </td>

                                        <td>
                                            <span class="nom-client">
                                                <?= htmlspecialchars(
                                                    (string) $compte["NOM"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $compte["NUMERO_COMPTE"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $compte["TYPE_COMPTE"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= number_format(
                                                    (float) $compte["SOLDE"],
                                                    2,
                                                    ",",
                                                    " "
                                                ) ?> MAD
                                            </strong>
                                        </td>

                                        <td>
                                            <?php if ($compte["STATUT"] === "ACTIF"): ?>

                                                <span class="badge-statut badge-actif">
                                                    ACTIF
                                                </span>

                                            <?php elseif ($compte["STATUT"] === "SUSPENDU"): ?>

                                                <span class="badge-statut badge-suspendu">
                                                    SUSPENDU
                                                </span>

                                            <?php else: ?>

                                                <span class="badge-statut">
                                                    <?= htmlspecialchars(
                                                        (string) $compte["STATUT"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ) ?>
                                                </span>

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                (string) $compte["DATE_CREATION"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </td>

                                        <td>

                                            <?php if ($compte["STATUT"] === "ACTIF"): ?>

                                                <form
                                                    action="traiter_statut_compte.php"
                                                    method="POST"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="compte_id"
                                                        value="<?= (int) $compte["ID"] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="nouveau_statut"
                                                        value="SUSPENDU"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn-suspendre"
                                                    >
                                                        Suspendre
                                                    </button>

                                                </form>

                                            <?php elseif ($compte["STATUT"] === "SUSPENDU"): ?>

                                                <form
                                                    action="traiter_statut_compte.php"
                                                    method="POST"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="compte_id"
                                                        value="<?= (int) $compte["ID"] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="nouveau_statut"
                                                        value="ACTIF"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn-reactiver"
                                                    >
                                                        Réactiver
                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <span>Action indisponible</span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            <?php endif; ?>

        </section>

        <footer class="dashboard-footer">
            BANK-SIM — Gestion sécurisée des comptes
        </footer>

    </main>

</div>

</body>
</html>
