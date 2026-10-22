<?php

require_once "config.php";

$recherche = $_GET["recherche"] ?? "";
$type = $_GET["type"] ?? "";


/*
|--------------------------------------------------------------------------
| Construire la requête
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            transactions.ID,
            clients.NOM,
            transactions.TYPE,
            transactions.MONTANT,
            transactions.DATE_TRANSACTION

        FROM transactions

        JOIN clients
        ON transactions.CLIENT_ID = clients.ID";

$conditions = [];
$parametres = [];


/*
|--------------------------------------------------------------------------
| Recherche par nom
|--------------------------------------------------------------------------
*/

if ($recherche !== "") {

    $conditions[] = "clients.NOM LIKE :recherche";

    $parametres[":recherche"] = "%" . $recherche . "%";
}


/*
|--------------------------------------------------------------------------
| Filtre par type
|--------------------------------------------------------------------------
*/

if ($type !== "") {

    $conditions[] = "transactions.TYPE = :type";

    $parametres[":type"] = $type;
}


/*
|--------------------------------------------------------------------------
| Ajouter WHERE si nécessaire
|--------------------------------------------------------------------------
*/

if (count($conditions) > 0) {

    $sql .= " WHERE " . implode(" AND ", $conditions);
}


/*
|--------------------------------------------------------------------------
| Trier
|--------------------------------------------------------------------------
*/

$sql .= " ORDER BY transactions.DATE_TRANSACTION DESC";


/*
|--------------------------------------------------------------------------
| Exécuter
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);

$stmt->execute($parametres);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <title>BANK-SIM - Historique</title>

</head>

<body>

    <h1>BANK-SIM</h1>

    <h2>Historique des transactions</h2>


    <form method="GET">

        <label>
            Rechercher un client :
        </label>

        <input
            type="text"
            name="recherche"
            value="<?= htmlspecialchars($recherche) ?>"
            placeholder="Exemple : AROUNA"
        >


        <label>
            Type :
        </label>

        <select name="type">

            <option value="">
                Toutes
            </option>

            <option value="DEPOT"
                <?= $type === "DEPOT" ? "selected" : "" ?>>
                Dépôts
            </option>

            <option value="RETRAIT"
                <?= $type === "RETRAIT" ? "selected" : "" ?>>
                Retraits
            </option>

            <option value="VIREMENT_ENVOYE"
                <?= $type === "VIREMENT_ENVOYE" ? "selected" : "" ?>>
                Virements envoyés
            </option>

            <option value="VIREMENT_RECU"
                <?= $type === "VIREMENT_RECU" ? "selected" : "" ?>>
                Virements reçus
            </option>

        </select>


        <button type="submit">
            Rechercher
        </button>

    </form>


    <br>


    <table border="1" cellpadding="10">

        <tr>

            <th>ID</th>
            <th>Client</th>
            <th>Type</th>
            <th>Montant</th>
            <th>Date</th>

        </tr>


        <?php if (count($transactions) > 0): ?>

            <?php foreach ($transactions as $transaction): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($transaction["ID"]) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($transaction["NOM"]) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($transaction["TYPE"]) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($transaction["MONTANT"]) ?>
                        MAD
                    </td>

                    <td>
                        <?= htmlspecialchars($transaction["DATE_TRANSACTION"]) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>

                <td colspan="5">
                    Aucune transaction trouvée.
                </td>

            </tr>

        <?php endif; ?>

    </table>

</body>

</html>