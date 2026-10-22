
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("ADMINISTRATEUR");

$message = "";
$succes = false;

$client_id = "";
$email = "";
$role = "CLIENT";

// Fonction pour sécuriser l'affichage HTML
function e($valeur) {
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Récupérer les clients
$sql = "SELECT ID, NOM
        FROM clients
        ORDER BY NOM";

$stmt = $pdo->query($sql);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $client_id = $_POST["client_id"] ?? "";
    $email = trim($_POST["email"] ?? "");
    $mot_de_passe = $_POST["mot_de_passe"] ?? "";
    $role = $_POST["role"] ?? "CLIENT";

    if (!filter_var($client_id, FILTER_VALIDATE_INT)
        || (int) $client_id <= 0) {

        $message = "Veuillez sélectionner un client.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Veuillez saisir une adresse e-mail valide.";

    } elseif (strlen($mot_de_passe) < 6) {

        $message = "Le mot de passe doit contenir au moins 6 caractères.";

    } elseif (!in_array(
        $role,
        ["CLIENT", "CONSEILLER", "ADMINISTRATEUR"],
        true
    )) {

        $message = "Le rôle sélectionné est invalide.";

    } else {

        // Vérifier que le client existe
        $sql = "SELECT ID
                FROM clients
                WHERE ID = :client_id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":client_id" => (int) $client_id
        ]);

        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) {

            $message = "Le client sélectionné est introuvable.";

        } else {

            // Vérifier si l'e-mail est déjà utilisé
            $sql = "SELECT ID
                    FROM users
                    WHERE EMAIL = :email";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":email" => $email
            ]);

            if ($stmt->fetch()) {

                $message = "Cette adresse e-mail existe déjà.";

            } else {

                // Hacher le mot de passe avant l'enregistrement
                $hash = password_hash(
                    $mot_de_passe,
                    PASSWORD_DEFAULT
                );

                // Créer le nouvel utilisateur
                $sql = "INSERT INTO users
                        (CLIENT_ID, EMAIL, MOT_DE_PASSE_HASH, ROLE, ACTIF)
                        VALUES
                        (:client_id, :email, :hash, :role, 1)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":client_id" => (int) $client_id,
                    ":email" => $email,
                    ":hash" => $hash,
                    ":role" => $role
                ]);

                $message = "Utilisateur créé avec succès.";
                $succes = true;

                // Réinitialiser les champs après la réussite
                $client_id = "";
                $email = "";
                $role = "CLIENT";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ajouter un utilisateur - BANK-SIM</title>

    <style>
        :root {
            --ivoire: #F5F0E4;
            --bleu: #14243A;
            --dore: #C7A45D;
            --blanc: #FFFFFF;
            --texte: #354052;
            --gris: #78808D;
            --bordure: #E7E0D2;
            --vert: #236844;
            --vert-fond: #EDF8F0;
            --rouge: #A83B3B;
            --rouge-fond: #FFF0EE;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--ivoire);
            color: var(--texte);
        }

        .barre-haut {
            background: var(--bleu);
            color: var(--blanc);
            padding: 19px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-bottom: 3px solid var(--dore);
        }

        .logo {
            margin: 0;
            font-size: 22px;
            letter-spacing: 2px;
        }

        .logo span {
            color: var(--dore);
        }

        .retour-haut {
            color: var(--blanc);
            text-decoration: none;
            font-size: 13px;
        }

        .retour-haut:hover {
            color: var(--dore);
        }

        .conteneur {
            width: 92%;
            max-width: 850px;
            margin: 35px auto;
        }

        .surtitre {
            margin: 0 0 7px;
            color: #94763C;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
        }

        h2 {
            margin: 0;
            color: var(--bleu);
            font-size: 27px;
        }

        .description {
            margin: 9px 0 24px;
            color: var(--gris);
            font-size: 14px;
            line-height: 1.6;
        }

        .carte {
            background: var(--blanc);
            border: 1px solid var(--bordure);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 7px 25px rgba(20, 36, 58, 0.06);
        }

        .carte-entete {
            background: #FCFAF5;
            border-bottom: 1px solid var(--bordure);
            padding: 19px 24px;
        }

        .carte-entete h3 {
            margin: 0;
            color: var(--bleu);
            font-size: 16px;
        }

        .carte-entete p {
            margin: 6px 0 0;
            color: var(--gris);
            font-size: 12px;
        }

        .formulaire {
            padding: 25px;
        }

        .champ {
            margin-bottom: 21px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--bleu);
            font-size: 13px;
            font-weight: 600;
        }

        .obligatoire {
            color: var(--rouge);
        }

        input,
        select {
            display: block;
            width: 100%;
            min-height: 45px;
            padding: 11px 13px;
            background: var(--blanc);
            border: 1px solid #DAD7CF;
            border-radius: 7px;
            color: var(--texte);
            font: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus,
        select:focus {
            border-color: var(--dore);
            box-shadow: 0 0 0 3px rgba(199, 164, 93, 0.17);
        }

        .aide {
            display: block;
            margin-top: 7px;
            color: var(--gris);
            font-size: 11px;
            line-height: 1.5;
        }

        .message {
            padding: 13px 15px;
            margin-bottom: 22px;
            border-radius: 7px;
            font-size: 13px;
            line-height: 1.5;
        }

        .message-succes {
            background: var(--vert-fond);
            color: var(--vert);
            border: 1px solid #CDE8D4;
        }

        .message-erreur {
            background: var(--rouge-fond);
            color: var(--rouge);
            border: 1px solid #F0D5D1;
        }

        .actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 5px;
        }

        .bouton {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            min-height: 43px;
            padding: 11px 17px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .bouton-principal {
            color: var(--blanc);
            background: var(--bleu);
            border: 1px solid var(--bleu);
        }

        .bouton-principal:hover {
            background: #203956;
            border-color: var(--dore);
        }

        .bouton-secondaire {
            color: var(--bleu);
            background: var(--blanc);
            border: 1px solid var(--bordure);
        }

        .bouton-secondaire:hover {
            background: var(--ivoire);
        }

        .note-securite {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            padding: 15px;
            background: #FAF8F2;
            border: 1px solid var(--bordure);
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.6;
            color: var(--gris);
        }

        .note-securite strong {
            color: var(--bleu);
        }

        .pied-page {
            margin-top: 24px;
            text-align: center;
            color: var(--gris);
            font-size: 11px;
        }

        @media (max-width: 600px) {
            .barre-haut {
                padding: 16px 5%;
            }

            .logo {
                font-size: 19px;
            }

            .conteneur {
                width: 94%;
                margin: 24px auto;
            }

            h2 {
                font-size: 23px;
            }

            .formulaire {
                padding: 20px 16px;
            }

            .carte-entete {
                padding: 17px 16px;
            }

            .actions {
                align-items: stretch;
                flex-direction: column;
            }

            .bouton {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <header class="barre-haut">
        <h1 class="logo">BANK-<span>SIM</span></h1>

        <a class="retour-haut" href="utilisateurs.php">
            ← Liste des utilisateurs
        </a>
    </header>

    <main class="conteneur">

        <p class="surtitre">Administration</p>
        <h2>Ajouter un utilisateur</h2>

        <p class="description">
            Créez un accès à BANK-SIM et attribuez le rôle correspondant
            aux responsabilités de la personne.
        </p>

        <section class="carte">

            <div class="carte-entete">
                <h3>Informations du compte</h3>
                <p>Les champs marqués d'un astérisque sont obligatoires.</p>
            </div>

            <form class="formulaire" method="POST" action="ajouter_utilisateur.php">

                <?php if ($message !== ""): ?>
                    <div
                        class="message <?= $succes ? 'message-succes' : 'message-erreur' ?>"
                        role="status"
                    >
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>

                <div class="champ">
                    <label for="client_id">
                        Client associé <span class="obligatoire">*</span>
                    </label>

                    <select name="client_id" id="client_id" required>
                        <option value="">-- Choisir un client --</option>

                        <?php foreach ($clients as $client): ?>
                            <option
                                value="<?= e($client['ID']) ?>"
                                <?= (string) $client_id === (string) $client['ID']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($client['NOM']) ?>
                                — ID <?= e($client['ID']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <small class="aide">
                        Sélectionnez le client auquel ce compte sera associé.
                    </small>
                </div>

                <div class="champ">
                    <label for="email">
                        Adresse e-mail <span class="obligatoire">*</span>
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        maxlength="254"
                        autocomplete="email"
                        placeholder="exemple@banque.ma"
                        value="<?= e($email) ?>"
                        required
                    >

                    <small class="aide">
                        Cette adresse servira à se connecter à BANK-SIM.
                    </small>
                </div>

                <div class="champ">
                    <label for="mot_de_passe">
                        Mot de passe temporaire
                        <span class="obligatoire">*</span>
                    </label>

                    <input
                        type="password"
                        name="mot_de_passe"
                        id="mot_de_passe"
                        minlength="6"
                        autocomplete="new-password"
                        placeholder="Au moins 6 caractères"
                        required
                    >

                    <small class="aide">
                        Le mot de passe ne sera pas conservé dans le formulaire
                        après l'envoi.
                    </small>
                </div>

                <div class="champ">
                    <label for="role">
                        Rôle de l'utilisateur
                        <span class="obligatoire">*</span>
                    </label>

                    <select name="role" id="role" required>
                        <option
                            value="CLIENT"
                            <?= $role === "CLIENT" ? "selected" : "" ?>
                        >
                            Client
                        </option>

                        <option
                            value="CONSEILLER"
                            <?= $role === "CONSEILLER" ? "selected" : "" ?>
                        >
                            Conseiller
                        </option>

                        <option
                            value="ADMINISTRATEUR"
                            <?= $role === "ADMINISTRATEUR" ? "selected" : "" ?>
                        >
                            Administrateur
                        </option>
                    </select>

                    <small class="aide">
                        Choisissez le rôle selon les autorisations nécessaires.
                    </small>
                </div>

                <div class="actions">
                    <button class="bouton bouton-principal" type="submit">
                        Créer l'utilisateur
                    </button>

                    <a class="bouton bouton-secondaire" href="utilisateurs.php">
                        Annuler
                    </a>
                </div>

                <div class="note-securite">
                    <span>🔒</span>
                    <div>
                        <strong>Protection des accès</strong><br>
                        Le mot de passe est haché avant son enregistrement.
                        Attribuez les rôles privilégiés uniquement aux personnes
                        autorisées.
                    </div>
                </div>

            </form>
        </section>

        <footer class="pied-page">
            BANK-SIM · Espace d'administration
        </footer>

    </main>

</body>
</html>
