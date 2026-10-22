
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

/*
|--------------------------------------------------------------------------
| 1. Vérifier le client connecté
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["client_id"]) ||
    !is_numeric($_SESSION["client_id"])
) {
    header("Location: login.php");
    exit;
}

$client_id = (int) $_SESSION["client_id"];

/*
|--------------------------------------------------------------------------
| 2. Fonction de protection de l'affichage HTML
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| 3. Créer le jeton CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| 4. Récupérer uniquement les comptes actifs du client connecté
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        ID,
        NUMERO_COMPTE,
        TYPE_COMPTE,
        SOLDE
    FROM comptes
    WHERE CLIENT_ID = :client_id
      AND STATUT = 'ACTIF'
    ORDER BY ID ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":client_id" => $client_id
]);

$comptes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 5. Récupérer le nom du client
|--------------------------------------------------------------------------
*/

$sqlClient = "
    SELECT NOM
    FROM clients
    WHERE ID = :client_id
";

$stmtClient = $pdo->prepare($sqlClient);

$stmtClient->execute([
    ":client_id" => $client_id
]);

$nomClient = $stmtClient->fetchColumn();

if ($nomClient === false) {
    http_response_code(404);
    exit("Client introuvable.");
}

/*
|--------------------------------------------------------------------------
| 6. Compter les notifications non lues
|--------------------------------------------------------------------------
*/

$sqlNotifications = "
    SELECT COUNT(*)
    FROM notifications
    WHERE CLIENT_ID = :client_id
      AND LUE = 0
";

$stmtNotifications = $pdo->prepare($sqlNotifications);

$stmtNotifications->execute([
    ":client_id" => $client_id
]);

$nombreNotifications = (int) $stmtNotifications->fetchColumn();

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dépôt - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .deposit-content {
            max-width: 850px;
            margin: 0 auto;
        }

        .deposit-description {
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 26px;
        }

        .deposit-panel {
            max-width: 720px;
            padding: 32px;
            background: #ffffff;
            border: 1px solid #eee4d0;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(18, 35, 65, 0.07);
        }

        .deposit-panel h3 {
            color: #142744;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .deposit-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 24px;
        }

        .deposit-field {
            display: flex;
            flex-direction: column;
            gap: 9px;
            min-width: 0;
        }

        .deposit-field label {
            color: #26364d;
            font-weight: 600;
        }

        .deposit-field select,
        .deposit-field input {
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

        .deposit-field select:focus,
        .deposit-field input:focus {
            outline: 2px solid #d9b66f;
            outline-offset: 1px;
            border-color: #bd9748;
        }

        .deposit-help {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
        }

        .deposit-button {
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

        .deposit-button:hover {
            background: #b58d43;
        }

        .deposit-empty {
            padding: 24px;
            border: 1px solid #eee4d0;
            border-radius: 14px;
            background: #fff;
        }

        .deposit-empty h3 {
            margin-top: 0;
            color: #142744;
        }

        .deposit-back {
            display: inline-block;
            margin-top: 24px;
            color: #142744;
            font-weight: 600;
            text-decoration: none;
        }

        .deposit-back:hover {
            text-decoration: underline;
        }

        .notification-count {
            display: inline-block;
            margin-left: 4px;
            padding: 2px 7px;
            border-radius: 12px;
            background: #e6c783;
            color: #142744;
            font-size: 11px;
            font-weight: 700;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
        }

        .user-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #e6c783;
            color: #142744;
            font-weight: 700;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }

        .user-info strong {
            overflow-wrap: anywhere;
        }

        .user-info span {
            font-size: 12px;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .deposit-panel {
                padding: 22px 18px;
            }

            .deposit-content {
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

            <a
                href="client_depot.php"
                class="nav-link active"
                aria-current="page"
            >
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

                <span>
                    Notifications

                    <?php if ($nombreNotifications > 0): ?>
                        <span class="notification-count">
                            <?= $nombreNotifications ?>
                        </span>
                    <?php endif; ?>
                </span>
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

            <div class="sidebar-user">
                <div class="user-avatar">
                    <?= e(
                        mb_strtoupper(
                            mb_substr($nomClient, 0, 1, "UTF-8"),
                            "UTF-8"
                        )
                    ) ?>
                </div>

                <div class="user-info">
                    <strong><?= e($nomClient) ?></strong>
                    <span>Client BANK-SIM</span>
                </div>
            </div>

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
                <h1>Effectuer un dépôt</h1>

                <p>
                    Ajoutez de l'argent sur l'un de vos comptes actifs.
                </p>
            </div>
        </header>

        <section class="section-block deposit-content">

            <p class="deposit-description">
                Sélectionnez le compte à créditer, puis indiquez
                le montant que vous souhaitez déposer.
            </p>

            <?php if (empty($comptes)): ?>

                <div class="deposit-empty">
                    <h3>Aucun compte disponible</h3>

                    <p>
                        Vous ne possédez actuellement aucun compte actif.
                        Contactez votre conseiller si vous pensez qu'il
                        s'agit d'une erreur.
                    </p>
                </div>

            <?php else: ?>

                <div class="deposit-panel">

                    <h3>Nouveau dépôt</h3>

                    <p>
                        Vérifiez les informations avant de continuer.
                    </p>

                    <form
                        class="deposit-form"
                        action="traiter_depot.php"
                        method="POST"
                    >

                        <!-- Protection CSRF -->
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION["csrf_token"]) ?>"
                        >

                        <div class="deposit-field">

                            <label for="compte_id">
                                Compte à créditer
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

                                    <option
                                        value="<?= (int) $compte["ID"] ?>"
                                    >
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

                            <span class="deposit-help">
                                Seuls vos comptes actifs sont affichés.
                            </span>

                        </div>

                        <div class="deposit-field">

                            <label for="montant">
                                Montant du dépôt (MAD)
                            </label>

                            <input
                                type="number"
                                name="montant"
                                id="montant"
                                min="0.01"
                                step="0.01"
                                inputmode="decimal"
                                placeholder="Ex. : 500.00"
                                required
                            >

                            <span class="deposit-help">
                                Saisissez un montant positif.
                            </span>

                        </div>

                        <button
                            class="deposit-button"
                            type="submit"
                        >
                            💰 Effectuer le dépôt
                        </button>

                    </form>

                </div>

            <?php endif; ?>

            <a href="dashboard.php" class="deposit-back">
                ← Retour au tableau de bord
            </a>

        </section>

    </main>

</div>

</body>
</html>