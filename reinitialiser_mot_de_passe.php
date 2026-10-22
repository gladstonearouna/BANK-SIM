
<?php

session_start();

require_once __DIR__ . "/config.php";

// =============================================
// 1. PROTECTION CSRF
// =============================================

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];

$erreur = "";
$succes = "";
$demande = null;

// =============================================
// 2. RÉCUPÉRATION ET VALIDATION DU JETON
// =============================================

$token = $_GET["token"] ?? $_POST["token"] ?? "";

if (
    !is_string($token) ||
    !preg_match('/\A[a-f0-9]{64}\z/', $token)
) {
    http_response_code(400);
    exit("Lien de récupération invalide ou incomplet.");
}

$token_hash = hash("sha256", $token);

// =============================================
// 3. VÉRIFICATION DU LIEN
// =============================================

try {

    $sql = "SELECT ID, USER_ID
            FROM password_resets
            WHERE TOKEN_HASH = :token_hash
              AND EXPIRES_AT > NOW()
              AND USED_AT IS NULL
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":token_hash" => $token_hash
    ]);

    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        http_response_code(400);
        exit("Ce lien est invalide, expiré ou déjà utilisé.");
    }

    // =============================================
    // 4. TRAITEMENT DU FORMULAIRE
    // =============================================

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $csrf_recu = $_POST["csrf_token"] ?? "";

        if (
            !is_string($csrf_recu) ||
            !hash_equals($_SESSION["csrf_token"], $csrf_recu)
        ) {
            http_response_code(403);
            exit("Requête refusée. Rechargez la page et réessayez.");
        }

        $mot_de_passe = $_POST["mot_de_passe"] ?? "";
        $confirmation = $_POST["confirmation"] ?? "";

        if (
            !is_string($mot_de_passe) ||
            !is_string($confirmation)
        ) {
            $erreur = "Les données envoyées sont invalides.";

        } elseif (strlen($mot_de_passe) < 12) {
            $erreur = "Le mot de passe doit contenir au moins 12 caractères.";

        } elseif (strlen($mot_de_passe) > 1024) {
            $erreur = "Le mot de passe est trop long.";

        } elseif ($mot_de_passe !== $confirmation) {
            $erreur = "Les deux mots de passe ne correspondent pas.";

        } else {

            // Hachage sécurisé du nouveau mot de passe.
            $nouveau_hash = password_hash(
                $mot_de_passe,
                PASSWORD_DEFAULT
            );

            if ($nouveau_hash === false) {
                throw new RuntimeException(
                    "Échec du hachage du mot de passe."
                );
            }

            // =============================================
            // 5. TRANSACTION SQL
            // =============================================

            $pdo->beginTransaction();

            // Revérifier et verrouiller le jeton.
            $verif = $pdo->prepare(
                "SELECT ID, USER_ID
                 FROM password_resets
                 WHERE TOKEN_HASH = :token_hash
                   AND EXPIRES_AT > NOW()
                   AND USED_AT IS NULL
                 LIMIT 1
                 FOR UPDATE"
            );

            $verif->execute([
                ":token_hash" => $token_hash
            ]);

            $demande_verifiee = $verif->fetch(PDO::FETCH_ASSOC);

            if (
                !$demande_verifiee ||
                (int) $demande_verifiee["USER_ID"]
                    !== (int) $demande["USER_ID"]
            ) {
                $pdo->rollBack();

                $demande = null;

                $erreur = "Ce lien n'est plus valide. Demandez-en un nouveau.";

            } else {

                // Mettre à jour le mot de passe du compte actif.
                $update = $pdo->prepare(
                    "UPDATE users
                     SET MOT_DE_PASSE_HASH = :hash
                     WHERE ID = :user_id
                       AND ACTIF = 1"
                );

                $update->execute([
                    ":hash" => $nouveau_hash,
                    ":user_id" => $demande_verifiee["USER_ID"]
                ]);

                if ($update->rowCount() !== 1) {
                    throw new RuntimeException(
                        "Le compte n'a pas pu être mis à jour."
                    );
                }

                // Invalider tous les jetons non utilisés du compte.
                $invalidate = $pdo->prepare(
                    "UPDATE password_resets
                     SET USED_AT = NOW()
                     WHERE USER_ID = :user_id
                       AND USED_AT IS NULL"
                );

                $invalidate->execute([
                    ":user_id" => $demande_verifiee["USER_ID"]
                ]);

                // Confirmer les changements.
                $pdo->commit();

                // Renouveler le jeton CSRF.
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
                $csrf_token = $_SESSION["csrf_token"];

                $demande = null;

                $succes = "Votre mot de passe a été modifié avec succès.";
            }
        }
    }

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Ne jamais enregistrer le jeton secret dans les journaux.
    error_log(
        "Erreur réinitialisation BANK-SIM : " . $e->getMessage()
    );

    http_response_code(500);

    $erreur = "Une erreur est survenue. Veuillez réessayer.";
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nouveau mot de passe - BANK-SIM</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="page-connexion">

    <main class="connexion-container">

        <section class="connexion-decoration">

            <div class="logo-banksim">
                <span class="logo-symbole">B</span>
                <span>BANK-SIM</span>
            </div>

            <div class="texte-bienvenue">

                <p class="surtitre">SÉCURITÉ DE VOTRE COMPTE</p>

                <h1>Un nouveau départ</h1>

                <p>
                    Protégez votre espace bancaire avec un mot de passe
                    personnel et sécurisé.
                </p>

            </div>

            <p class="mention-securite">
                Votre espace bancaire, en toute simplicité.
            </p>

        </section>

        <section class="connexion-formulaire">

            <div class="formulaire-entete">

                <p class="surtitre">RÉINITIALISATION</p>

                <h2>Nouveau mot de passe</h2>

                <p class="description-connexion">
                    Choisissez un nouveau mot de passe pour votre compte.
                </p>

            </div>

            <?php if ($erreur !== ""): ?>

                <p class="message-erreur" role="alert">
                    <?= htmlspecialchars(
                        $erreur,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

            <?php endif; ?>

            <?php if ($succes !== ""): ?>

                <p role="status">
                    <?= htmlspecialchars(
                        $succes,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>

                <a
                    class="btn-principal btn-connexion"
                    href="login.php"
                    style="display:block; text-align:center; text-decoration:none;"
                >
                    Retour à la connexion →
                </a>

            <?php elseif ($demande !== null): ?>

                <form
                    method="POST"
                    action="reinitialiser_mot_de_passe.php"
                >

                    <input
                        type="hidden"
                        name="token"
                        value="<?= htmlspecialchars(
                            $token,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $csrf_token,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                    <div class="champ-formulaire">

                        <label for="mot_de_passe">
                            Nouveau mot de passe
                        </label>

                        <input
                            type="password"
                            id="mot_de_passe"
                            name="mot_de_passe"
                            placeholder="12 caractères minimum"
                            autocomplete="new-password"
                            minlength="12"
                            maxlength="1024"
                            required
                        >

                    </div>

                    <div class="champ-formulaire">

                        <label for="confirmation">
                            Confirmer le nouveau mot de passe
                        </label>

                        <input
                            type="password"
                            id="confirmation"
                            name="confirmation"
                            placeholder="Répétez votre mot de passe"
                            autocomplete="new-password"
                            minlength="12"
                            maxlength="1024"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        class="btn-principal btn-connexion"
                    >
                        Réinitialiser mon mot de passe
                        <span aria-hidden="true">→</span>
                    </button>

                </form>

            <?php endif; ?>

            <p class="pied-connexion">
                <a href="login.php">← Retour à la connexion</a>
            </p>

        </section>

    </main>

</body>

</html>
