
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("ADMINISTRATEUR");
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

$sql = "SELECT users.ID,
               users.EMAIL,
               users.ROLE,
               users.ACTIF,
               clients.NOM AS NOM_CLIENT
        FROM users
        LEFT JOIN clients ON users.CLIENT_ID = clients.ID
        ORDER BY users.ID DESC";

$stmt = $pdo->query($sql);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($valeur) {
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

$nombreUtilisateurs = count($utilisateurs);
$nombreActifs = 0;

foreach ($utilisateurs as $utilisateur) {
    if ((int) $utilisateur["ACTIF"] === 1) {
        $nombreActifs++;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BANK-SIM - Gestion des utilisateurs</title>

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
            max-width: 1200px;
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
        }

        .resume {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .resume-carte {
            flex: 1;
            min-width: 180px;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 17px 19px;
            background: var(--blanc);
            border: 1px solid var(--bordure);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(20, 36, 58, 0.04);
        }

        .icone {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--ivoire);
            border-radius: 9px;
            font-size: 20px;
        }

        .resume-carte p {
            margin: 0 0 5px;
            color: var(--gris);
            font-size: 12px;
        }

        .resume-carte strong {
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
            white-space: nowrap;
        }

        .table-conteneur {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 780px;
            border-collapse: collapse;
        }

        thead {
            background: #FAF8F2;
        }

        th {
            padding: 14px 17px;
            text-align: left;
            color: #6E7480;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--bordure);
        }

        td {
            padding: 15px 17px;
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

        .email {
            color: var(--bleu);
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .role {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .role-client {
            color: #365C87;
            background: #EDF3FA;
        }

        .role-conseiller {
            color: #80642D;
            background: #F8F0DD;
        }

        .role-admin {
            color: #67418A;
            background: #F3ECFA;
        }

        .statut {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .statut-actif {
            color: var(--vert);
            background: var(--vert-fond);
        }

        .statut-inactif {
            color: var(--rouge);
            background: var(--rouge-fond);
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

        .desactiver {
            color: var(--rouge);
            background: var(--rouge-fond);
            border: 1px solid #F0D5D1;
        }

        .desactiver:hover {
            background: #F9DFDB;
        }

        .activer {
            color: var(--vert);
            background: var(--vert-fond);
            border: 1px solid #CDE8D4;
        }

        .activer:hover {
            background: #DDF0E2;
        }

        .compte-actuel {
            color: var(--gris);
            font-size: 12px;
            font-style: italic;
        }

        .vide {
            text-align: center;
            padding: 35px 20px;
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
                <h2>Gestion des utilisateurs</h2>
                <p class="description">
                    Consultez les accès et gérez les statuts des utilisateurs.
                </p>
            </div>

            <a class="bouton" href="ajouter_utilisateur.php">
                ＋ Ajouter un utilisateur
            </a>
        </section>

        <section class="resume">

            <div class="resume-carte">
                <div class="icone">♙</div>
                <div>
                    <p>Total des utilisateurs</p>
                    <strong><?= $nombreUtilisateurs ?></strong>
                </div>
            </div>

            <div class="resume-carte">
                <div class="icone">✓</div>
                <div>
                    <p>Comptes actifs</p>
                    <strong><?= $nombreActifs ?></strong>
                </div>
            </div>

        </section>

        <section class="carte">

            <div class="carte-entete">
                <h3>Liste des utilisateurs</h3>
                <span class="etiquette">
                    <?= $nombreUtilisateurs ?> compte(s)
                </span>
            </div>

            <div class="table-conteneur">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Adresse e-mail</th>
                            <th>Rôle</th>
                            <th>Client associé</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($utilisateurs)): ?>

                            <tr>
                                <td colspan="6" class="vide">
                                    Aucun utilisateur enregistré.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($utilisateurs as $utilisateur): ?>

                                <?php
                                $classeRole = "role-client";

                                if ($utilisateur["ROLE"] === "CONSEILLER") {
                                    $classeRole = "role-conseiller";
                                } elseif ($utilisateur["ROLE"] === "ADMINISTRATEUR") {
                                    $classeRole = "role-admin";
                                }

                                $estActif = (int) $utilisateur["ACTIF"] === 1;
                                ?>

                                <tr>
                                    <td class="identifiant">
                                        #<?= e($utilisateur["ID"]) ?>
                                    </td>

                                    <td class="email">
                                        <?= e($utilisateur["EMAIL"]) ?>
                                    </td>

                                    <td>
                                        <span class="role <?= $classeRole ?>">
                                            <?= e($utilisateur["ROLE"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= $utilisateur["NOM_CLIENT"] !== null
                                            ? e($utilisateur["NOM_CLIENT"])
                                            : "Aucun" ?>
                                    </td>

                                    <td>
                                        <?php if ($estActif): ?>
                                            <span class="statut statut-actif">
                                                ● Actif
                                            </span>
                                        <?php else: ?>
                                            <span class="statut statut-inactif">
                                                ● Inactif
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (
                                            (string) $utilisateur["ID"]
                                            === (string) $_SESSION["user_id"]
                                        ): ?>

                                            <span class="compte-actuel">
                                                Compte actuel
                                            </span>

                                        <?php else: ?>

                                            
<form
    method="POST"
    action="changer_statut.php"
    onsubmit="return confirm('Confirmer cette modification du statut ?');"
    style="margin: 0;"
>
    <input
        type="hidden"
        name="id"
        value="<?= e($utilisateur["ID"]) ?>"
    >

    <input
        type="hidden"
        name="csrf_token"
        value="<?= e($csrfToken) ?>"
    >

    <button
        type="submit"
        class="action <?= $estActif ? 'desactiver' : 'activer' ?>"
        style="cursor: pointer;"
    >
        <?= $estActif ? "Désactiver" : "Activer" ?>
    </button>
</form>


                                        <?php endif; ?>
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
