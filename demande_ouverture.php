
<?php
session_start();
require_once __DIR__ .\"config.php";

$message = "";
$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Récupérer les informations
    $nom = trim($_POST["nom_complet"] ?? "");
    $dateNaissance = $_POST["date_naissance"] ?? "";
    $nationalite = trim($_POST["nationalite"] ?? "");
    $paysResidence = trim($_POST["pays_residence"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $adresse = trim($_POST["adresse"] ?? "");
    $motDePasse = $_POST["mot_de_passe"] ?? "";
    $confirmation = $_POST["confirmation"] ?? "";

    // 2. Vérifier les champs obligatoires
    if (
        $nom === "" ||
        $dateNaissance === "" ||
        $nationalite === "" ||
        $paysResidence === "" ||
        $telephone === "" ||
        $email === "" ||
        $adresse === "" ||
        $motDePasse === "" ||
        $confirmation === ""
    ) {
        $erreur = "Tous les champs sont obligatoires.";

    } elseif (
        strlen($nom) > 150 ||
        strlen($nationalite) > 100 ||
        strlen($paysResidence) > 100 ||
        strlen($telephone) > 30 ||
        strlen($email) > 150 ||
        strlen($adresse) > 255
    ) {
        $erreur = "Une ou plusieurs informations sont trop longues.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse e-mail n'est pas valide.";

    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateNaissance) ||
        !checkdate(
            (int) substr($dateNaissance, 5, 2),
            (int) substr($dateNaissance, 8, 2),
            (int) substr($dateNaissance, 0, 4)
        ) ||
        $dateNaissance > date("Y-m-d")
    ) {
        $erreur = "La date de naissance est invalide.";

    } elseif ($motDePasse !== $confirmation) {
        $erreur = "Les deux mots de passe ne correspondent pas.";

    } elseif (strlen($motDePasse) < 12) {
        $erreur = "Le mot de passe doit contenir au moins 12 caractères.";

    } else {

        try {
            // 3. Vérifier si cet e-mail est déjà utilisé
            $verification = $pdo->prepare(
                "SELECT ID FROM users WHERE EMAIL = :email LIMIT 1"
            );
            $verification->execute(["email" => $email]);

            if ($verification->fetch()) {
                $erreur = "Cette adresse e-mail possède déjà un compte.";

            } else {
                // 4. Vérifier s'il existe déjà une demande en attente
                $verification = $pdo->prepare(
                    "SELECT ID FROM demandes
                     WHERE EMAIL = :email
                     AND STATUT = 'EN_ATTENTE'
                     LIMIT 1"
                );
                $verification->execute(["email" => $email]);

                if ($verification->fetch()) {
                    $erreur = "Une demande est déjà en attente avec cette adresse e-mail.";

                } else {
                    // 5. Protéger le mot de passe
                    $hash = password_hash(
                        $motDePasse,
                        PASSWORD_DEFAULT
                    );

                    // 6. Enregistrer la demande sans créer de client
                    $requete = $pdo->prepare(
                        "INSERT INTO demandes (
                            CLIENT_ID,
                            TYPE_DEMANDE,
                            STATUT,
                            NOM_COMPLET,
                            DATE_NAISSANCE,
                            NATIONALITE,
                            PAYS_RESIDENCE,
                            TELEPHONE,
                            EMAIL,
                            ADRESSE,
                            MOT_DE_PASSE_HASH
                        ) VALUES (
                            NULL,
                            'OUVERTURE_COMPTE',
                            'EN_ATTENTE',
                            :nom,
                            :date_naissance,
                            :nationalite,
                            :pays_residence,
                            :telephone,
                            :email,
                            :adresse,
                            :hash
                        )"
                    );

                    $requete->execute([
                        "nom" => $nom,
                        "date_naissance" => $dateNaissance,
                        "nationalite" => $nationalite,
                        "pays_residence" => $paysResidence,
                        "telephone" => $telephone,
                        "email" => $email,
                        "adresse" => $adresse,
                        "hash" => $hash
                    ]);

                    // 7. Mémoriser la confirmation puis rediriger
                    $_SESSION["demande_envoyee"] = true;

                    header("Location: attente.php");
                    exit;
                }
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erreur = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'ouverture de compte - BANK-SIM</title>
    <link rel="stylesheet" href="demande.css">
</head>

<body>

    <header>
        <h1>BANK-SIM</h1>
        <p>Demande d'ouverture de compte</p>
    </header>

    <main>
        <h2>Créer une demande</h2>

        <?php if ($erreur !== ""): ?>
            <div class="message-erreur">
                <?= htmlspecialchars($erreur, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <p>
            Remplissez ce formulaire pour demander l'ouverture
            d'un compte. Votre demande devra être validée par un conseiller.
        </p>

        <form action="" method="POST">

            <label for="nom_complet">Nom complet</label>
            <input type="text" id="nom_complet" name="nom_complet"
                   maxlength="150" required>

            <label for="date_naissance">Date de naissance</label>
            <input type="date" id="date_naissance" name="date_naissance"
                   required>

            <label for="nationalite">Nationalité</label>
            <input type="text" id="nationalite" name="nationalite"
                   maxlength="100" required>

            <label for="pays_residence">Pays de résidence</label>
            <input type="text" id="pays_residence" name="pays_residence"
                   maxlength="100" required>

            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone"
                   maxlength="30" required>

            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email"
                   maxlength="150" required>

            <label for="adresse">Adresse</label>
            <textarea id="adresse" name="adresse"
                      maxlength="255" required></textarea>

            <label for="mot_de_passe">Choisir un mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe"
                   minlength="12" required>

            <label for="confirmation">Confirmer le mot de passe</label>
            <input type="password" id="confirmation" name="confirmation"
                   minlength="12" required>

            <button type="submit">Envoyer ma demande</button>

        </form>

        <p><a href="index.php">Retour à l'accueil</a></p>
    </main>

</body>
</html>
