
<?php

session_start();

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/mail_config.php";

// Protection CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];
$message = "";
$envoye = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrf_recu = $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrf_recu) ||
        !hash_equals($_SESSION["csrf_token"], $csrf_recu)
    ) {
        http_response_code(403);
        exit("Requête refusée. Rechargez la page et réessayez.");
    }

    $email = $_POST["email"] ?? "";

    if (!is_string($email)) {
        $email = "";
    }

    $email = trim($email);

    // Message générique : ne pas révéler si le compte existe.
    $message = "Si cette adresse correspond à un compte actif, "
             . "un lien de récupération sera envoyé. "
             . "Vérifiez votre boîte de réception et vos courriers indésirables.";

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

        try {
            // Rechercher uniquement un compte actif.
            $sql = "SELECT ID, EMAIL
                    FROM users
                    WHERE EMAIL = :email
                      AND ACTIF = 1
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":email" => $email
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {

                // Jeton secret de 256 bits.
                $token = bin2hex(random_bytes(32));

                // Seule l'empreinte du jeton sera stockée.
                $token_hash = hash("sha256", $token);

                // Supprimer les anciennes demandes non utilisées
                // de ce compte avant d'en créer une nouvelle.
                $delete = $pdo->prepare(
                    "DELETE FROM password_resets
                     WHERE USER_ID = :user_id
                       AND USED_AT IS NULL"
                );

                $delete->execute([
                    ":user_id" => $user["ID"]
                ]);

                // Le lien expire après 30 minutes.
                $insert = $pdo->prepare(
                    "INSERT INTO password_resets
                        (USER_ID, TOKEN_HASH, EXPIRES_AT)
                     VALUES
                        (:user_id, :token_hash,
                         DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
                );

                $insert->execute([
                    ":user_id" => $user["ID"],
                    ":token_hash" => $token_hash
                ]);

                $reset_id = (int) $pdo->lastInsertId();

                // URL locale : adaptée aux tests sur cet ordinateur.
                $lien = "http://localhost/BANK-SIM/"
                      . "reinitialiser_mot_de_passe.php?token="
                      . urlencode($token);

                try {
                    $mail = creerMailer();

                    $mail->addAddress($user["EMAIL"]);
                    $mail->isHTML(true);
                    $mail->Subject = "Réinitialisation du mot de passe - BANK-SIM";

                    $lien_html = htmlspecialchars(
                        $lien,
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    $mail->Body = '
                        <div style="font-family:Arial,sans-serif;
                                    max-width:600px;margin:auto;
                                    color:#101b32;line-height:1.6">
                            <div style="background:#101b32;padding:24px;
                                        text-align:center;border-radius:10px 10px 0 0">
                                <h1 style="color:#d8b56d;margin:0">
                                    BANK-SIM
                                </h1>
                            </div>

                            <div style="padding:28px;border:1px solid #eee">
                                <h2>Mot de passe oublié ?</h2>

                                <p>
                                    Une demande de réinitialisation du mot
                                    de passe de votre compte BANK-SIM a été reçue.
                                </p>

                                <p>
                                    Cliquez sur le bouton ci-dessous pour
                                    choisir un nouveau mot de passe.
                                </p>

                                <p style="text-align:center;margin:30px 0">
                                    <a href="' . $lien_html . '"
                                       style="background:#101b32;color:#d8b56d;
                                              padding:14px 22px;
                                              text-decoration:none;
                                              border-radius:6px;
                                              display:inline-block;
                                              font-weight:bold">
                                        Réinitialiser mon mot de passe
                                    </a>
                                </p>

                                <p>
                                    Ce lien expire dans 30 minutes et ne peut
                                    être utilisé qu’une seule fois.
                                </p>

                                <p>
                                    Si vous n’avez pas demandé cette
                                    réinitialisation, ignorez ce message.
                                </p>
                            </div>
                        </div>
                    ';

                    $mail->AltBody =
                        "Pour réinitialiser votre mot de passe BANK-SIM, "
                        . "ouvrez ce lien dans les 30 minutes : "
                        . $lien
                        . "\nSi vous n'avez pas demandé cette opération, "
                        . "ignorez cet e-mail.";

                    $mail->send();

                    $envoye = true;

                } catch (Throwable $e) {

                    // Ne jamais inscrire le jeton dans les journaux.
                    error_log(
                        "Échec d'envoi du mail de récupération BANK-SIM : "
                        . $e->getMessage()
                    );

                    // Supprimer la demande si l'envoi a échoué.
                    $delete_reset = $pdo->prepare(
                        "DELETE FROM password_resets
                         WHERE ID = :id
                           AND USED_AT IS NULL"
                    );

                    $delete_reset->execute([
                        ":id" => $reset_id
                    ]);
                }
            }

        } catch (Throwable $e) {

            error_log(
                "Erreur de récupération BANK-SIM : "
                . $e->getMessage()
            );
        }
    }

    // Renouveler le jeton CSRF après la soumission.
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    $csrf_token = $_SESSION["csrf_token"];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mot de passe oublié - BANK-SIM</title>

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

                <h1>Retrouvez l’accès à votre espace</h1>

                <p>
                    Nous vous aiderons à réinitialiser votre mot de passe
                    dans un environnement conçu pour protéger votre compte.
                </p>
            </div>

            <p class="mention-securite">
                Votre espace bancaire, en toute simplicité.
            </p>

        </section>

        <section class="connexion-formulaire">

            <div class="formulaire-entete">
                <p class="surtitre">RÉCUPÉRATION DU COMPTE</p>

                <h2>Mot de passe oublié ?</h2>

                <p class="description-connexion">
                    Saisissez l’adresse e-mail associée à votre compte.
                </p>
            </div>

            <?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>

                <p
                    role="status"
                    style="padding:14px;border-radius:8px;
                           background:#f5f3ee;color:#101b32;
                           line-height:1.6;margin-bottom:20px"
                >
                    <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
                </p>

            <?php endif; ?>

            <form method="POST" action="mot_de_passe_oublie.php">

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
                    <label for="email">Adresse e-mail</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="exemple@email.com"
                        autocomplete="email"
                        maxlength="150"
                        required
                    >
                </div>

                <button type="submit" class="btn-principal btn-connexion">
                    Recevoir un lien de récupération
                    <span aria-hidden="true">→</span>
                </button>

            </form>

            <p class="pied-connexion">
                <a href="login.php">← Retour à la connexion</a>
            </p>

        </section>

    </main>

</body>
</html>
