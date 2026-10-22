<?php

session_start();

require_once "config.php";

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}

// Vérifier que c'est bien un client
if ($_SESSION["role"] !== "CLIENT") {

    die("Accès refusé.");
}

// Récupérer automatiquement le client connecté
$client_id = $_SESSION["client_id"];

// Récupérer uniquement ses transactions
$sql = "SELECT
            transactions.ID,
            clients.NOM,
            transactions.TYPE,
            transactions.MONTANT,
            transactions.DATE_TRANSACTION

        FROM transactions

        JOIN clients
        ON transactions.CLIENT_ID = clients.ID

        WHERE transactions.CLIENT_ID = :client_id

        ORDER BY transactions.DATE_TRANSACTION DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":client_id" => $client_id
]);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <title>Mes transactions - BANK-SIM</title>

</head>

<body>

    <h1>BANK-SIM</h1>

    <h2>Mon historique</h2>

    <p>
        Client connecté :
        <?= htmlspecialchars($_SESSION["client_id"]) ?>
    </p>

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
                    Aucune transaction.
                </td>

            </tr>

        <?php endif; ?>

    </table>

    <br>

    <a href="dashboard.php">
        Retour au dashboard
    </a>

</body>

</html>