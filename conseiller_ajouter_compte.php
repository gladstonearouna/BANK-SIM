
<?php
require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Récupérer les clients existants
$sql = "SELECT ID, NOM FROM clients ORDER BY NOM ASC";
$stmt = $pdo->query($sql);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte | BANK-SIM</title>
    <link rel="stylesheet" href="/BANK-SIM/style.css?v=2">
</head>

<body class="premium-dashboard">

<div class="app-layout">

    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">B</div>
            <div class="brand-text">
                <strong>BANK-SIM</strong>
                <span>Private Banking</span>
            </div>
        </div>

        <p class="sidebar-label">Menu principal</p>

        <nav class="sidebar-nav">
            <a href="conseiller.php" class="nav-link">
                <span class="nav-icon">▦</span> Tableau de bord
            </a>
            <a href="conseiller_clients.php" class="nav-link">
                <span class="nav-icon">♙</span> Gestion des clients
            </a>
            <a href="conseiller_demandes.php" class="nav-link">
                <span class="nav-icon">▤</span> Demandes
            </a>
            <a href="conseiller_comptes.php" class="nav-link active">
                <span class="nav-icon">▣</span> Comptes bancaires
            </a>
            <a href="conseiller_toutes_transactions.php" class="nav-link">
                <span class="nav-icon">↔</span> Transactions
            </a>
            <a href="conseiller_support.php" class="nav-link">
                <span class="nav-icon">✉</span> Support
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <div class="user-avatar">C</div>
                <div class="user-info">
                    <strong>Conseiller</strong>
                    <span>Conseiller bancaire</span>
                </div>
            </div>
            <a href="logout.php" class="nav-link">
                <span class="nav-icon">↪</span> Déconnexion
            </a>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div class="breadcrumb">
                BANK-SIM / <strong>Créer un compte</strong>
            </div>
            <div class="topbar-date">Espace professionnel</div>
        </header>

        <section class="welcome-banner">
            <div>
                <p class="sidebar-label">GESTION BANCAIRE</p>
                <h1>Créer un compte bancaire</h1>
                <p>Associez un nouveau compte à un client existant.</p>
            </div>
        </section>

        <section class="section-block">
            <div class="panel">
                <h2>Nouveau compte</h2>

                <form action="traiter_ajout_compte.php" method="POST">

                    <label for="client_id">Client</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">-- Choisir un client --</option>

                        <?php foreach ($clients as $client): ?>
                            <option value="<?= (int)$client['ID'] ?>">
                                <?= htmlspecialchars(
                                    $client['NOM'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <br><br>

                    <label for="type_compte">Type de compte</label>
                    <select name="type_compte" id="type_compte" required>
                        <option value="COURANT">Compte courant</option>
                    </select>

                    <p>Solde initial : <strong>0,00 MAD</strong></p>

                    <button type="submit">Créer le compte</button>

                </form>

                <br>
                <a href="conseiller_comptes.php">
                    ← Retour aux comptes bancaires
                </a>
            </div>
        </section>

        <footer class="dashboard-footer">
            BANK-SIM · Private Banking · Espace conseiller
        </footer>

    </main>
</div>

</body>
</html>