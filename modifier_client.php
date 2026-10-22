
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("ADMINISTRATEUR");

function e($valeur) {
    return htmlspecialchars((string) $valeur, ENT_QUOTES, "UTF-8");
}

$id = $_GET["id"] ?? $_POST["id"] ?? null;

if (filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1) {
    die("ID du client invalide.");
}

$id = (int) $id;

// Récupérer le client à modifier
$sql = "SELECT * FROM clients WHERE ID = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $id]);

$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    die("Client introuvable.");
}

$nom = $client["NOM"];
$solde = $client["SOLDE"];
$erreur = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom"] ?? "");
    $solde = trim($_POST["solde"] ?? "");

    if ($nom === "") {
        $erreur = "Le nom du client est obligatoire.";

    } elseif (!is_numeric($solde) || !is_finite((float) $solde) || (float) $solde < 0) {
        $erreur = "Le solde doit être un nombre positif ou égal à zéro.";

    } else {

        $sql = "UPDATE clients
                SET NOM = :nom,
                    SOLDE = :solde
                WHERE ID = :id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":nom" => $nom,
            ":solde" => $solde,
            ":id" => $id
        ]);

        header("Location: clients.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Modifier un client | BANK-SIM</title>

    <style>
        :root {
            --ivoire: #F5F0E4;
            --marine: #14243A;
            --or: #C7A45D;
            --blanc: #FFFFFF;
            --texte: #333D4B;
            --bordure: #E5DDCE;
            --erreur: #A33333;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 35px 18px;
            min-height: 100vh;
            background: var(--ivoire);
            color: var(--texte);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .conteneur {
            width: 100%;
            max-width: 620px;
            margin: 0 auto;
        }

        .entete {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo {
            color: var(--marine);
            font-family: Georgia, serif;
            font-size: 30px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 0 0 8px;
        }

        .ligne-or {
            width: 65px;
            height: 3px;
            margin: 0 auto 18px;
            background: var(--or);
            border-radius: 3px;
        }

        h2 {
            color: var(--marine);
            font-size: 24px;
            margin: 0 0 8px;
        }

        .description {
            color: #737373;
            font-size: 14px;
            margin: 0;
        }

        .carte {
            background: var(--blanc);
            border: 1px solid var(--bordure);
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(20, 36, 58, 0.07);
        }

        .information {
            background: #F8F5EE;
            border-left: 3px solid var(--or);
            padding: 12px 14px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .information strong {
            color: var(--marine);
        }

        .champ {
            margin-bottom: 21px;
        }

        label {
            display: block;
            color: var(--marine);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input {
            display: block;
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #D9D5CD;
            border-radius: 7px;
            background: #FFFEFC;
            color: var(--texte);
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: var(--or);
            box-shadow: 0 0 0 3px rgba(199, 164, 93, 0.17);
        }

        .erreur {
            background: #FFF0F0;
            color: var(--erreur);
            border: 1px solid #E9C5C5;
            border-radius: 7px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .bouton {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 44px;
            padding: 12px 18px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .principal {
            flex: 1;
            border: 1px solid var(--marine);
            background: var(--marine);
            color: white;
        }

        .principal:hover {
            background: #203955;
        }

        .secondaire {
            border: 1px solid var(--bordure);
            background: white;
            color: var(--marine);
        }

        .secondaire:hover {
            border-color: var(--or);
            background: #FAF7F0;
        }

        .pied {
            text-align: center;
            color: #888;
            font-size: 12px;
            margin-top: 22px;
        }

        @media (max-width: 480px) {
            body {
                padding: 24px 12px;
            }

            .carte {
                padding: 21px 17px;
            }

            .logo {
                font-size: 26px;
            }

            h2 {
                font-size: 21px;
            }

            .actions {
                flex-direction: column;
            }

            .bouton {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <main class="conteneur">

        <header class="entete">
            <p class="logo">BANK-SIM</p>
            <div class="ligne-or"></div>
            <h2>Modifier un client</h2>
            <p class="description">
                Mise à jour des informations du client
            </p>
        </header>

        <section class="carte">

            <div class="information">
                <strong>Client n° <?= e($client["ID"]) ?></strong>
                <br>
                Modifiez les informations ci-dessous, puis enregistrez.
            </div>

            <?php if ($erreur !== ""): ?>
                <div class="erreur" role="alert">
                    <?= e($erreur) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="modifier_client.php?id=<?= e($id) ?>">

                <input type="hidden" name="id" value="<?= e($id) ?>">

                <div class="champ">
                    <label for="nom">Nom du client</label>
                    <input
                        type="text"
                        id="nom"
                        name="nom"
                        value="<?= e($nom) ?>"
                        maxlength="150"
                        autocomplete="off"
                        required
                    >
                </div>

                <div class="champ">
                    <label for="solde">Solde (MAD)</label>
                    <input
                        type="number"
                        id="solde"
                        name="solde"
                        min="0"
                        step="0.01"
                        value="<?= e($solde) ?>"
                        required
                    >
                </div>

                <div class="actions">
                    <button type="submit" class="bouton principal">
                        Enregistrer les modifications
                    </button>

                    <a href="clients.php" class="bouton secondaire">
                        Retour aux clients
                    </a>
                </div>

            </form>

        </section>

        <footer class="pied">
            BANK-SIM — Gestion sécurisée de la banque
        </footer>

    </main>

</body>
</html>
