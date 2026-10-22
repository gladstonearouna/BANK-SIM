
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

// Vérifier l'identifiant du client connecté.
if (
    !isset($_SESSION["client_id"]) ||
    !filter_var($_SESSION["client_id"], FILTER_VALIDATE_INT)
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

// Créer un jeton CSRF pour protéger le formulaire.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Récupérer uniquement les comptes actifs du client connecté.
$sql = "SELECT ID, NUMERO_COMPTE, TYPE_COMPTE, SOLDE
        FROM comptes
        WHERE CLIENT_ID = :client_id
          AND STATUT = 'ACTIF'
        ORDER BY ID ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":client_id" => $client_id
]);

$comptes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Retrait - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .withdraw-content {
            max-width: 850px;
            margin: 0 auto;
        }

        .withdraw-description {
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 26px;
        }

        .withdraw-panel {
            max-width: 720px;
            padding: 32px;
            background: #ffffff;
            border: 1px solid #eee4d0;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(18, 35, 65, 0.07);
        }

        .withdraw-panel h3 {
            color: #142744;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .withdraw-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 24px;
        }

        .withdraw-field {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .withdraw-field label {
            color: #26364d;
            font-weight: 600;
        }

        .withdraw-field select,
        .withdraw-field input {
            box-sizing: border-box;
            width: 100%;
            min-height: 49px;
            padding: 12px 14px;
            border: 1px solid #d9dee7;
            border-radius: 10px;
            background: #fff;
            color: #18283f;
            font: inherit;
        }

        .withdraw-field select:focus,
        .withdraw-field input:focus {
            outline: 2px solid #d9b66f;
            outline-offset: 1px;
            border-color: #bd9748;
        }

        .withdraw-help {
            color: #6b7280;
            font-size: 13px;
        }

        .withdraw-button {
            width: 100%;
            min-height: 50px;
            padding: 13px 18px;
            border: none;
            border-radius: 10px;
            background: #c6a15b;
            color: #142744;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .withdraw-button:hover {
            background: #b58d43;
        }

        .withdraw-button:focus-visible {
            outline: 2px solid #142744;
            outline-offset: 3px;
        }

        .withdraw-empty {
            padding: 24px;
            border: 1px solid #eee4d0;
            border-radius: 14px;
            background: #fff;
        }

        .withdraw-empty h3 {
            margin-top: 0;
            color: #142744;
        }

        .withdraw-back {
            display: inline-block;
            margin-top: 24px;
            color: #142744;
            font-weight: 600;
            text-decoration: none;
        }

        .withdraw-back:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .withdraw-panel {
                padding: 22px 18px;
            }

            .withdraw-content {
                width: 100%;
            }
        }
    </style>
</head>

<body class="premium-dashboard">

<div class="app-layout">

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

            <a href="client_retrait.php"
               class="nav-link active"
               aria-current="page">
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

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Effectuer un retrait</h1>
                <p>Retirez de l'argent depuis l'un de vos comptes actifs.</p>
            </div>
        </header>

        <section class="section-block withdraw-content">

            <p class="withdraw-description">
                Sélectionnez le compte concerné et indiquez
                le montant que vous souhaitez retirer.
            </p>

            <?php if (empty($comptes)): ?>

                <div class="withdraw-empty">
                    <h3>Aucun compte disponible</h3>

                    <p>
                        Vous ne possédez actuellement aucun compte actif.
                        Contactez votre conseiller si nécessaire.
                    </p>
                </div>

            <?php else: ?>

                <div class="withdraw-panel">

                    <h3>Nouveau retrait</h3>

                    <p>
                        Vérifiez les informations avant de continuer.
                    </p>

                    <form
                        class="withdraw-form"
                        action="traiter_retrait.php"
                        method="POST"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION["csrf_token"]) ?>"
                        >

                        <div class="withdraw-field">

                            <label for="compte_id">
                                Compte à débiter
                            </label>

                            <select
                                name="compte_id"
                                id="compte_id"
                                required
                            >
                                <option value="">
                                    -- Sélectionner un compte --
                                </option>

                                <?php foreach ($comptes as $compte): ?>

                                    <option value="<?= e($compte["ID"]) ?>">
                                        <?= e($compte["NUMERO_COMPTE"]) ?>
                                        -
                                        <?= e($compte["TYPE_COMPTE"]) ?>
                                        -
                                        <?= number_format(
                                            (float) $compte["SOLDE"],
                                            2,
                                            ",",
                                            " "
                                        ) ?> MAD
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <span class="withdraw-help">
                                Seuls vos comptes actifs sont affichés.
                            </span>

                        </div>

                        <div class="withdraw-field">

                            <label for="montant">
                                Montant du retrait (MAD)
                            </label>

                            <input
                                type="number"
                                name="montant"
                                id="montant"
                                min="0.01"
                                step="0.01"
                                placeholder="Ex. : 200.00"
                                required
                            >

                            <span class="withdraw-help">
                                Le montant doit être positif et ne pas
                                dépasser le solde disponible.
                            </span>

                        </div>

                        <button class="withdraw-button" type="submit">
                            🏧 Effectuer le retrait
                        </button>

                    </form>

                </div>

            <?php endif; ?>

            <a href="dashboard.php" class="withdraw-back">
                ← Retour au tableau de bord
            </a>

        </section>

    </main>

</div>

</body>
</html>
