<?php

require_once "config.php";

$message = "";
$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = $_POST["id"] ?? "";
    $montant = $_POST["montant"] ?? "";

    // Vérifier l'ID
    if (!filter_var($id, FILTER_VALIDATE_INT)) {

        $erreur = "ID du client invalide.";

    // Vérifier le montant
    } elseif (!is_numeric($montant) || $montant <= 0) {

        $erreur = "Le montant doit être supérieur à 0.";

    } else {

        try {

            // 1. Commencer la transaction
            $pdo->beginTransaction();

            // 2. Récupérer et verrouiller le client
            $sql = "SELECT *
                    FROM clients
                    WHERE ID = :id
                    FOR UPDATE";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":id" => $id
            ]);

            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Vérifier l'existence du client
            if (!$client) {

                throw new Exception("Client introuvable.");
            }

            // 4. Vérifier le solde
            if ($montant > $client["SOLDE"]) {

                throw new Exception("Solde insuffisant.");
            }

            // 5. Calculer le nouveau solde
            $nouveau_solde = $client["SOLDE"] - $montant;

            // 6. Modifier le solde
            $sql = "UPDATE clients
                    SET SOLDE = :solde
                    WHERE ID = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":solde" => $nouveau_solde,
                ":id" => $id
            ]);

            // 7. Enregistrer le retrait dans l'historique
            $sql = "INSERT INTO transactions
                    (CLIENT_ID, TYPE, MONTANT)
                    VALUES
                    (:client_id, :type, :montant)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":client_id" => $id,
                ":type" => "RETRAIT",
                ":montant" => $montant
            ]);

            // 8. Valider toute la transaction
            $pdo->commit();

            $message = "Retrait effectué avec succès.";

        } catch (Exception $e) {

            // 9. Annuler si quelque chose s'est mal passé
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

    <title>BANK-SIM - Retrait</title>

</head>

<body>

    <h1>BANK-SIM</h1>

    <h2>Effectuer un retrait</h2>

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

        <label>Montant du retrait :</label>

        <input
            type="number"
            name="montant"
            step="0.01"
            min="0.01"
            required
        >

        <br><br>

        <button type="submit">
            Retirer
        </button>

    </form>

</body>

</html>