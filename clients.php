
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("ADMINISTRATEUR");

// Créer un jeton CSRF pour les actions sensibles.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

// Récupérer les clients.
$sql = "SELECT * FROM clients ORDER BY ID DESC";
$resultat = $pdo->query($sql);
$clients = $resultat->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour sécuriser l'affichage HTML.
function e($valeur) {
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

$nombreClients = count($clients);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BANK-SIM - Gestion des clients</title>

    <style>
        :root {
            --ivoire: #F5F0E4;
            --bleu: #14243A;
            --dore: #C7A45D;
            --blanc: #FFFFFF;
            --texte: #354052;
            --gris: #78808D;
            --bordure: #E7E0D2;
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
            font-weight: 700;
        }

        .logo span {
            color: var(--dore);
        }

        .retour {
            color: var(--blanc);
            text-decoration: none;
            font-size: 13px;
        }

        .retour:hover {
            color: var(--dore);
        }

        .conteneur {
            width: 92%;
            max-width: 1180px;
            margin: 35px auto;
        }

        .entete-page {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 25px;
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
            margin: 9px 0 0;
            color: var(--gris);
            font-size: 14px;
        }

        .bouton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 17px;
            background: var(--bleu);
            color: var(--blanc);
            border: 1px solid var(--bleu);
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .bouton:hover {
            background: #203956;
            border-color: var(--dore);
            transform: translateY(-1px);
        }

        .resume {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--blanc);
            border: 1px solid var(--bordure);
            border-radius: 10px;
            padding: 17px 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(20, 36, 58, 0.04);
        }

        .icone-resume {
            width: 43px;
            height: 43px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--ivoire);
            border-radius: 9px;
            font-size: 21px;
        }

        .resume p {
            margin: 0 0 4px;
            color: var(--gris);
            font-size: 12px;
        }

        .resume strong {
            color: var(--bleu);
            font-size: 21px;
        }

        .carte {
            background: var(--blanc);
            border: 1px solid var(--bordure);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 7px 25px rgba(20, 36, 58, 0.05);
        }

        .carte-entete {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 18px 21px;
            border-bottom: 1px solid var(--bordure);
        }

        .carte-entete h3 {
            margin: 0;
            color: var(--bleu);
            font-size: 15px;
        }

        .etiquette {
            color: #80642D;
            background: #F8F0DD;
            border: 1px solid #E8D9B5;
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .table-conteneur {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        thead {
            background: #FAF8F2;
        }

        th {
            padding: 14px 20px;
            text-align: left;
            color: #6E7480;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            font-weight: 700;
            border-bottom: 1px solid var(--bordure);
        }

        td {
            padding: 15px 20px;
            font-size: 13px;
            border-bottom: 1px solid #F0ECE4;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #FCFAF5;
        }

        .identifiant {
            color: var(--gris);
            font-size: 12px;
        }

        .nom-client {
            color: var(--bleu);
            font-weight: 600;
        }

        .solde {
            color: var(--bleu);
            font-weight: 700;
            white-space: nowrap;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action {
            display: inline-block;
            padding: 7px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .modifier {
            color: #80642D;
            background: #F8F0DD;
            border: 1px solid #E8D9B5;
        }

        .modifier:hover {
            background: #EFE2C2;
        }

        .supprimer {
            color: var(--rouge);
            background: var(--rouge-fond);
            border: 1px solid #F0D5D1;
        }

        .supprimer:hover {
            background: #F9DFDB;
        }

        button.action {
            font-family: inherit;
            line-height: inherit;
            cursor: pointer;
        }

        .vide {
            text-align: center;
            padding: 40px 20px;
            color: var(--gris);
        }

        .pied-page {
            text-align: center;
            padding: 24px 10px;
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

            .entete-page {
                align-items: stretch;
                flex-direction: column;
            }

            .entete-page .bouton {
                align-self: flex-start;
            }

            h2 {
                font-size: 23px;
            }

            .carte-entete {
                padding: 15px;
            }

            th,
            td {
                padding: 12px 14px;
            }
        }
    </style>
</head>

<body>

    <header class="barre-haut">
        <h1 class="logo">BANK-<span>SIM</span></h1>

        <a class="retour" href="admin.php">
            ← Tableau de bord
        </a>
    </header>

    <main class="conteneur">

        <section class="entete-page">
            <div>
                <p class="surtitre">Administration</p>
                <h2>Gestion des clients</h2>
                <p class="description">
                    Consultez et gérez les clients de BANK-SIM.
                </p>
            </div>

            <a class="bouton" href="ajouter_client.php">
                <span>＋</span> Ajouter un client
            </a>
        </section>

        <section class="resume">
            <div class="icone-resume">♙</div>
            <div>
                <p>Nombre total de clients</p>
                <strong><?= $nombreClients ?></strong>
            </div>
        </section>

        <section class="carte">

            <div class="carte-entete">
                <h3>Liste des clients</h3>
                <span class="etiquette">
                    <?= $nombreClients ?> client(s)
                </span>
            </div>

            <div class="table-conteneur">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom du client</th>
                            <th>Solde</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($clients)): ?>

                            <tr>
                                <td colspan="4" class="vide">
                                    Aucun client enregistré pour le moment.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($clients as $client): ?>

                                <tr>
                                    <td class="identifiant">
                                        #<?= e($client["ID"]) ?>
                                    </td>

                                    <td class="nom-client">
                                        <?= e($client["NOM"]) ?>
                                    </td>

                                    <td class="solde">
                                        <?= number_format(
                                            (float) $client["SOLDE"],
                                            2,
                                            ",",
                                            " "
                                        ) ?> MAD
                                    </td>

                                    <td>
                                        <div class="actions">

                                            <a
                                                class="action modifier"
                                                href="modifier_client.php?id=<?= urlencode((string) $client["ID"]) ?>"
                                            >
                                                ✏ Modifier
                                            </a>

                                            <form
                                                method="POST"
                                                action="supprimer_client.php"
                                                onsubmit="return confirm('Voulez-vous vraiment supprimer ce client ?');"
                                                style="display: inline; margin: 0;"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= e($client["ID"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($csrfToken) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="action supprimer"
                                                >
                                                    🗑 Supprimer
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </section>

        <footer class="pied-page">
            BANK-SIM · Espace d'administration
        </footer>

    </main>

</body>
</html>
