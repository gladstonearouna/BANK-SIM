
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

/*
|--------------------------------------------------------------------------
| 1. Vérifier la session du client
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

$message = "";
$typeMessage = "";

/*
|--------------------------------------------------------------------------
| 4. Récupérer les informations du client
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        ID,
        NOM,
        DATE_NAISSANCE,
        NATIONALITE,
        PAYS_RESIDENCE,
        TELEPHONE,
        EMAIL,
        ADRESSE
    FROM clients
    WHERE ID = :client_id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":client_id" => $client_id
]);

$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    http_response_code(404);
    exit("Les informations du client sont introuvables.");
}

/*
|--------------------------------------------------------------------------
| 5. Traiter le formulaire
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrfRecu = $_POST["csrf_token"] ?? "";

    /*
     * Vérifier le jeton CSRF.
     */
    if (
        !is_string($csrfRecu) ||
        !hash_equals($_SESSION["csrf_token"], $csrfRecu)
    ) {
        $message = "La demande a expiré ou n'est pas valide. Rechargez la page.";
        $typeMessage = "erreur";

    } else {

        /*
         * Récupérer les données du formulaire.
         */
        $nom = trim($_POST["nom"] ?? "");
        $dateNaissance = trim($_POST["date_naissance"] ?? "");
        $nationalite = trim($_POST["nationalite"] ?? "");
        $paysResidence = trim($_POST["pays_residence"] ?? "");
        $telephone = trim($_POST["telephone"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $adresse = trim($_POST["adresse"] ?? "");

        /*
         * Vérifier que les champs sont remplis.
         */
        if (
            $nom === "" ||
            $dateNaissance === "" ||
            $nationalite === "" ||
            $paysResidence === "" ||
            $telephone === "" ||
            $email === "" ||
            $adresse === ""
        ) {
            $message = "Veuillez remplir tous les champs.";
            $typeMessage = "erreur";

        } elseif (
            mb_strlen($nom) > 150 ||
            mb_strlen($nationalite) > 100 ||
            mb_strlen($paysResidence) > 100 ||
            mb_strlen($telephone) > 30 ||
            mb_strlen($email) > 150 ||
            mb_strlen($adresse) > 255
        ) {
            $message = "Un ou plusieurs champs dépassent la longueur autorisée.";
            $typeMessage = "erreur";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Veuillez saisir une adresse e-mail valide.";
            $typeMessage = "erreur";

        } elseif (
            !preg_match('/^[0-9+\s().-]{6,30}$/', $telephone)
        ) {
            $message = "Le numéro de téléphone n'est pas valide.";
            $typeMessage = "erreur";

        } else {

            /*
             * Vérifier la date de naissance.
             */
            $date = DateTime::createFromFormat(
                "!Y-m-d",
                $dateNaissance
            );

            $erreursDate = DateTime::getLastErrors();

            $dateValide = (
                $date !== false &&
                $date->format("Y-m-d") === $dateNaissance &&
                (
                    $erreursDate === false ||
                    (
                        $erreursDate["warning_count"] === 0 &&
                        $erreursDate["error_count"] === 0
                    )
                ) &&
                $date <= new DateTime("today")
            );

            if (!$dateValide) {
                $message = "Veuillez saisir une date de naissance valide.";
                $typeMessage = "erreur";

            } else {

                try {

                    /*
                     * Commencer une transaction SQL.
                     * En cas d'erreur, les modifications seront annulées.
                     */
                    $pdo->beginTransaction();

                    /*
                     * Vérifier que l'adresse e-mail n'appartient
                     * pas à un autre client.
                     */
                    $sqlEmail = "
                        SELECT ID
                        FROM clients
                        WHERE EMAIL = :email
                          AND ID <> :client_id
                        LIMIT 1
                    ";

                    $stmtEmail = $pdo->prepare($sqlEmail);

                    $stmtEmail->execute([
                        ":email" => $email,
                        ":client_id" => $client_id
                    ]);

                    if ($stmtEmail->fetch(PDO::FETCH_ASSOC)) {
                        throw new RuntimeException(
                            "Cette adresse e-mail est déjà utilisée par un autre client."
                        );
                    }

                    /*
                     * Rechercher le compte utilisateur associé
                     * au client connecté.
                     */
                    $sqlUser = "
                        SELECT ID
                        FROM users
                        WHERE CLIENT_ID = :client_id
                        LIMIT 1
                    ";

                    $stmtUser = $pdo->prepare($sqlUser);

                    $stmtUser->execute([
                        ":client_id" => $client_id
                    ]);

                    $utilisateur = $stmtUser->fetch(PDO::FETCH_ASSOC);

                    /*
                     * Si un utilisateur existe, vérifier que
                     * l'e-mail n'est pas utilisé par un autre utilisateur.
                     */
                    if ($utilisateur) {

                        $sqlUserEmail = "
                            SELECT ID
                            FROM users
                            WHERE EMAIL = :email
                              AND ID <> :user_id
                            LIMIT 1
                        ";

                        $stmtUserEmail = $pdo->prepare($sqlUserEmail);

                        $stmtUserEmail->execute([
                            ":email" => $email,
                            ":user_id" => (int) $utilisateur["ID"]
                        ]);

                        if ($stmtUserEmail->fetch(PDO::FETCH_ASSOC)) {
                            throw new RuntimeException(
                                "Cette adresse e-mail est déjà utilisée pour un autre compte de connexion."
                            );
                        }
                    }

                    /*
                     * Mettre à jour les informations du client.
                     */
                    $sqlUpdate = "
                        UPDATE clients
                        SET NOM = :nom,
                            DATE_NAISSANCE = :date_naissance,
                            NATIONALITE = :nationalite,
                            PAYS_RESIDENCE = :pays_residence,
                            TELEPHONE = :telephone,
                            EMAIL = :email,
                            ADRESSE = :adresse
                        WHERE ID = :client_id
                    ";

                    $stmtUpdate = $pdo->prepare($sqlUpdate);

                    $stmtUpdate->execute([
                        ":nom" => $nom,
                        ":date_naissance" => $dateNaissance,
                        ":nationalite" => $nationalite,
                        ":pays_residence" => $paysResidence,
                        ":telephone" => $telephone,
                        ":email" => $email,
                        ":adresse" => $adresse,
                        ":client_id" => $client_id
                    ]);

                    /*
                     * Synchroniser l'e-mail de connexion.
                     */
                    if ($utilisateur) {

                        $sqlUpdateUser = "
                            UPDATE users
                            SET EMAIL = :email
                            WHERE ID = :user_id
                        ";

                        $stmtUpdateUser = $pdo->prepare($sqlUpdateUser);

                        $stmtUpdateUser->execute([
                            ":email" => $email,
                            ":user_id" => (int) $utilisateur["ID"]
                        ]);
                    }

                    /*
                     * Valider toutes les modifications.
                     */
                    $pdo->commit();

                    /*
                     * Renouveler le jeton CSRF.
                     */
                    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                    $message = "Vos informations personnelles ont été mises à jour.";
                    $typeMessage = "succes";

                } catch (Throwable $exception) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    if (
                        $exception instanceof RuntimeException &&
                        str_contains($exception->getMessage(), "déjà utilisée")
                    ) {
                        $message = $exception->getMessage();

                    } else {
                        error_log($exception->getMessage());

                        $message = "Une erreur est survenue pendant l'enregistrement. Vérifiez les informations et réessayez.";
                    }

                    $typeMessage = "erreur";
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| 6. Recharger les informations à jour
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        ID,
        NOM,
        DATE_NAISSANCE,
        NATIONALITE,
        PAYS_RESIDENCE,
        TELEPHONE,
        EMAIL,
        ADRESSE
    FROM clients
    WHERE ID = :client_id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":client_id" => $client_id
]);

$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    http_response_code(404);
    exit("Les informations du client sont introuvables.");
}

/*
|--------------------------------------------------------------------------
| 7. Compter les notifications non lues
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

    <title>Mes informations personnelles - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .informations-page {
            max-width: 1000px;
            margin: 0 auto;
        }

        .informations-intro {
            margin-bottom: 25px;
            color: #777;
            line-height: 1.7;
        }

        .informations-card {
            background: #fff;
            border: 1px solid #e8e3d9;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 5px 20px rgba(20, 35, 60, 0.04);
        }

        .informations-card h2 {
            margin-top: 0;
            margin-bottom: 22px;
            color: #172b4d;
            font-size: 20px;
        }

        .informations-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .information-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .information-field.full-width {
            grid-column: 1 / -1;
        }

        .information-field label {
            color: #172b4d;
            font-size: 14px;
            font-weight: 600;
        }

        .information-field input,
        .information-field textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #ded8cc;
            border-radius: 8px;
            background: #fff;
            color: #172b4d;
            font: inherit;
        }

        .information-field input:focus,
        .information-field textarea:focus {
            outline: 2px solid #c9a45c;
            outline-offset: 1px;
        }

        .information-field textarea {
            min-height: 90px;
            resize: vertical;
        }

        .information-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-save,
        .btn-cancel {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-save {
            border: none;
            background: #172b4d;
            color: #fff;
        }

        .btn-save:hover {
            background: #243e68;
        }

        .btn-cancel {
            border: 1px solid #ded8cc;
            background: #faf9f6;
            color: #172b4d;
        }

        .message {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 8px;
            line-height: 1.5;
        }

        .message.succes {
            background: #e9f6ec;
            border: 1px solid #b9dfc1;
            color: #216638;
        }

        .message.erreur {
            background: #fff0ef;
            border: 1px solid #f0c5c1;
            color: #922d27;
        }

        .information-note {
            margin-top: 20px;
            color: #777;
            font-size: 13px;
            line-height: 1.6;
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
            min-width: 0;
            gap: 3px;
        }

        .user-info strong {
            overflow-wrap: anywhere;
        }

        .user-info span {
            font-size: 12px;
            opacity: 0.8;
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

        @media (max-width: 650px) {
            .informations-grid {
                grid-template-columns: 1fr;
            }

            .information-field.full-width {
                grid-column: auto;
            }

            .informations-card {
                padding: 18px;
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

                <a
                    href="client_informations.php"
                    class="nav-link active"
                    aria-current="page"
                >
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
                                mb_substr(
                                    $client["NOM"],
                                    0,
                                    1,
                                    "UTF-8"
                                ),
                                "UTF-8"
                            )
                        ) ?>
                    </div>

                    <div class="user-info">
                        <strong><?= e($client["NOM"]) ?></strong>
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
                <div class="breadcrumb">
                    BANK-SIM / Mes informations personnelles
                </div>
            </header>

            <section class="section-block informations-page">

                <h1>Mes informations personnelles</h1>

                <p class="informations-intro">
                    Vous pouvez consulter et mettre à jour les informations
                    personnelles associées à votre espace client.
                </p>

                <?php if ($message !== ""): ?>
                    <div
                        class="message <?= e($typeMessage) ?>"
                        role="status"
                    >
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>

                <div class="informations-card">

                    <h2>Modifier mes informations</h2>

                    <form
                        method="POST"
                        action="client_informations.php"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION["csrf_token"]) ?>"
                        >

                        <div class="informations-grid">

                            <div class="information-field">
                                <label for="nom">Nom complet</label>

                                <input
                                    type="text"
                                    id="nom"
                                    name="nom"
                                    maxlength="150"
                                    autocomplete="name"
                                    value="<?= e($client["NOM"]) ?>"
                                    required
                                >
                            </div>

                            <div class="information-field">
                                <label for="date_naissance">
                                    Date de naissance
                                </label>

                                <input
                                    type="date"
                                    id="date_naissance"
                                    name="date_naissance"
                                    value="<?= e($client["DATE_NAISSANCE"]) ?>"
                                    max="<?= date("Y-m-d") ?>"
                                    required
                                >
                            </div>

                            <div class="information-field">
                                <label for="nationalite">Nationalité</label>

                                <input
                                    type="text"
                                    id="nationalite"
                                    name="nationalite"
                                    maxlength="100"
                                    value="<?= e($client["NATIONALITE"]) ?>"
                                    required
                                >
                            </div>

                            <div class="information-field">
                                <label for="pays_residence">
                                    Pays de résidence
                                </label>

                                <input
                                    type="text"
                                    id="pays_residence"
                                    name="pays_residence"
                                    maxlength="100"
                                    autocomplete="country-name"
                                    value="<?= e($client["PAYS_RESIDENCE"]) ?>"
                                    required
                                >
                            </div>

                            <div class="information-field">
                                <label for="telephone">Téléphone</label>

                                <input
                                    type="tel"
                                    id="telephone"
                                    name="telephone"
                                    maxlength="30"
                                    autocomplete="tel"
                                    value="<?= e($client["TELEPHONE"]) ?>"
                                    required
                                >
                            </div>

                            <div class="information-field">
                                <label for="email">Adresse e-mail</label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    maxlength="150"
                                    autocomplete="email"
                                    value="<?= e($client["EMAIL"]) ?>"
                                    required
                                >
                            </div>

                            <div class="information-field full-width">
                                <label for="adresse">Adresse</label>

                                <textarea
                                    id="adresse"
                                    name="adresse"
                                    maxlength="255"
                                    autocomplete="street-address"
                                    required
                                ><?= e($client["ADRESSE"]) ?></textarea>
                            </div>

                        </div>

                        <div class="information-actions">

                            <button
                                type="submit"
                                class="btn-save"
                            >
                                Enregistrer les modifications
                            </button>

                            <a
                                href="dashboard.php"
                                class="btn-cancel"
                            >
                                Annuler
                            </a>

                        </div>

                    </form>

                    <p class="information-note">
                        Pour votre sécurité, cette page ne permet pas
                        de modifier votre solde, votre identifiant client
                        ou votre rôle.
                    </p>

                </div>

            </section>

        </main>

    </div>

</body>
</html>
