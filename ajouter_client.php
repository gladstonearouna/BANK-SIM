
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("ADMINISTRATEUR");

$message = "";
$erreur = "";

$nom = "";
$solde = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom"] ?? "");
    $solde = trim($_POST["solde"] ?? "");

    // Vérifier le nom
    if ($nom === "") {

        $erreur = "Le nom du client est obligatoire.";

    // Vérifier que le solde est numérique et non négatif
    } elseif (
        $solde === "" ||
        !is_numeric($solde) ||
        !is_finite((float)$solde) ||
        (float)$solde < 0
    ) {

        $erreur = "Le solde doit être un nombre positif ou égal à zéro.";

    } else {

        try {

            $sql = "INSERT INTO clients (NOM, SOLDE)
                    VALUES (:nom, :solde)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":nom" => $nom,
                ":solde" => $solde
            ]);

            $message = "Client ajouté avec succès !";

            // Réinitialiser les champs après réussite
            $nom = "";
            $solde = "";

        } catch (PDOException $e) {

            error_log($e->getMessage());
            $erreur = "Impossible d'ajouter le client. Veuillez réessayer.";

        }
    }
}

function e($valeur) {
    return htmlspecialchars(
        (string)$valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ajouter un client - BANK-SIM</title>

    <style>
        :root {
            --ivoire: #F5F0E4;
            --blanc: #FFFEFB;
            --bleu: #14243A;
            --bleu-clair: #263E5C;
            --dore: #C7A45D;
            --texte: #343B45;
            --gris: #777F89;
            --bordure: #E5DDCE;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--ivoire);
            color: var(--texte);
            font-size: 14px;
        }

        header {
            background: var(--bleu);
            color: white;
            padding: 17px 5%;
            border-bottom: 3px solid var(--dore);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .brand {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 25px;
            letter-spacing: 1.5px;
        }

        .subtitle {
            margin: 4px 0 0;
            color: #D8CBAF;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .header-link {
            color: var(--bleu);
            background: var(--dore);
            padding: 9px 13px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
        }

        .header-link:hover {
            background: #E0C47F;
        }

        main {
            max-width: 700px;
            margin: 30px auto;
            padding: 0 18px;
        }

        .breadcrumb {
            margin-bottom: 15px;
            color: var(--gris);
            font-size: 12px;
        }

        .breadcrumb a {
            color: var(--bleu-clair);
            text-decoration: none;
        }

        .form-card {
            background: var(--blanc);
            border: 1px solid var(--bordure);
            border-top: 4px solid var(--dore);
            border-radius: 9px;
            padding: 25px;
            box-shadow: 0 5px 18px rgba(20, 36, 58, 0.05);
        }

        h2 {
            color: var(--bleu);
            font-family: Georgia, "Times New Roman", serif;
            font-size: 25px;
            margin: 0 0 8px;
        }

        .intro {
            color: var(--gris);
            line-height: 1.6;
            margin: 0 0 23px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            color: var(--bleu);
            font-weight: 600;
            margin-bottom: 7px;
        }

        .hint {
            color: var(--gris);
            font-size: 12px;
            margin-top: 6px;
        }

        input {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #D8CEB9;
            border-radius: 5px;
            background: white;
            color: var(--texte);
            font: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--dore);
            box-shadow: 0 0 0 3px rgba(199, 164, 93, 0.17);
        }

        .message {
            padding: 12px 14px;
            border-radius: 5px;
            margin-bottom: 18px;
            line-height: 1.5;
        }

        .success {
            background: #E5F1E6;
            color: #245B32;
            border: 1px solid #B9D7BD;
        }

        .error {
            background: #F8E8E5;
            color: #8D3028;
            border: 1px solid #E8C2BC;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .submit-button {
            border: 1px solid var(--bleu);
            background: var(--bleu);
            color: #F1D18A;
            padding: 12px 18px;
            border-radius: 5px;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .submit-button:hover {
            background: var(--bleu-clair);
        }

        .cancel-link {
            color: var(--bleu);
            text-decoration: none;
            padding: 10px 5px;
        }

        .cancel-link:hover {
            text-decoration: underline;
        }

        footer {
            text-align: center;
            color: var(--gris);
            font-size: 12px;
            padding: 20px;
        }

        @media (max-width: 480px) {
            header {
                padding: 15px;
            }

            .brand {
                font-size: 22px;
            }

            main {
                margin: 20px auto;
                padding: 0 12px;
            }

            .form-card {
                padding: 18px;
            }

            h2 {
                font-size: 22px;
            }

            .actions {
                align-items: stretch;
                flex-direction: column;
            }

            .submit-button {
                width: 100%;
            }

            .cancel-link {
                text-align: center;
            }
        }
    </style>
</head>

<body>

<header>
    <div>
        <h1 class="brand">BANK-SIM</h1>
        <p class="subtitle">Administration bancaire</p>
    </div>

    <a class="header-link" href="admin.php">
        ← Tableau de bord
    </a>
</header>

<main>

    <div class="breadcrumb">
        <a href="admin.php">Administration</a>
        &nbsp; / &nbsp; Ajouter un client
    </div>

    <section class="form-card">

        <h2>Ajouter un client</h2>

        <p class="intro">
            Renseignez les informations du nouveau client
            pour l'enregistrer dans BANK-SIM.
        </p>

        <?php if ($message !== ""): ?>
            <div class="message success" role="status">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($erreur !== ""): ?>
            <div class="message error" role="alert">
                <?= e($erreur) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="ajouter_client.php">

            <div class="form-group">
                <label for="nom">Nom du client *</label>

                <input
                    type="text"
                    id="nom"
                    name="nom"
                    value="<?= e($nom) ?>"
                    maxlength="150"
                    autocomplete="off"
                    required
                >

                <div class="hint">
                    Saisissez le nom à afficher dans la liste des clients.
                </div>
            </div>

            <div class="form-group">
                <label for="solde">Solde initial (MAD) *</label>

                <input
                    type="number"
                    id="solde"
                    name="solde"
                    value="<?= e($solde) ?>"
                    min="0"
                    step="0.01"
                    required
                >

                <div class="hint">
                    Le solde doit être positif ou égal à zéro.
                </div>
            </div>

            <div class="actions">
                <button class="submit-button" type="submit">
                    + Ajouter le client
                </button>

                <a class="cancel-link" href="clients.php">
                    Annuler
                </a>
            </div>

        </form>

    </section>

</main>

<footer>
    BANK-SIM — Administration bancaire
</footer>

</body>
</html>
