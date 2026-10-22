<?php

require_once "config.php";

$message = "";
$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = $_POST["id"] ?? "";
    $montant = $_POST["montant"] ?? "";

    if (!filter_var($id, FILTER_VALIDATE_INT)) {

        $erreur = "ID du client invalide.";

    } elseif (!is_numeric($montant) || $montant <= 0) {

        $erreur = "Le montant doit être supérieur à 0.";

    } else {

        try {

            // Commencer la transaction
            $pdo->beginTransaction();

            // Récupérer et verrouiller le client
            $sql = "SELECT *
                    FROM clients
                    WHERE ID = :id
                    FOR UPDATE";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":id" => $id
            ]);

            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$client) {

                throw new Exception("Client introuvable.");
            }

            // Calcul du nouveau solde
            $nouveau_solde = $client["SOLDE"] + $montant;

            // Modifier le solde
            $sql = "UPDATE clients
                    SET SOLDE = :solde
                    WHERE ID = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":solde" => $nouveau_solde,
                ":id" => $id
            ]);

            // Enregistrer la transaction
            $sql = "INSERT INTO transactions
                    (CLIENT_ID, TYPE, MONTANT)
                    VALUES
                    (:client_id, :type, :montant)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":client_id" => $id,
                ":type" => "DEPOT",
                ":montant" => $montant
            ]);

            // Tout s'est bien passé
            $pdo->commit();

            $message = "Dépôt effectué avec succès.";

        } catch (Exception $e) {

            // Annuler les modifications
            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }

            $erreur = $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <title>BANK-SIM - Dépôt</title>

</head>

<body>

    <h1>BANK-SIM</h1>

    <h2>Effectuer un dépôt</h2>

    <?php if ($message !== ""): ?>

        <p>
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>

    <?php if ($erreur !== ""): ?>

        <p>
            <?= htmlspecialchars($erreur) ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <label>ID du client :</label>

        <input
            type="number"
            name="id"
            required
        >

        <br><br>

        <label>Montant du dépôt :</label>

        <input
            type="number"
            name="montant"
            step="0.01"
            min="0.01"
            required
        >

        <br><br>

        <button type="submit">
            Déposer
        </button>

    </form>

</body>

</html>