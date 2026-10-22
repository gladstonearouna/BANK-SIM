<?php

require_once "config.php";

$message = "";
$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $source_id = $_POST["source_id"] ?? "";
    $destination_id = $_POST["destination_id"] ?? "";
    $montant = $_POST["montant"] ?? "";

    // Vérifier les IDs
    if (
        !filter_var($source_id, FILTER_VALIDATE_INT) ||
        !filter_var($destination_id, FILTER_VALIDATE_INT)
    ) {

        $erreur = "ID invalide.";

    // Empêcher un virement vers soi-même
    } elseif ($source_id == $destination_id) {

        $erreur = "Le client source et le client destination doivent être différents.";

    // Vérifier le montant
    } elseif (!is_numeric($montant) || $montant <= 0) {

        $erreur = "Le montant doit être supérieur à 0.";

    } else {

        try {

            // Commencer la transaction
            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | 1. Récupérer et verrouiller le client source
            |--------------------------------------------------------------------------
            */

            $sql = "SELECT *
                    FROM clients
                    WHERE ID = :id
                    FOR UPDATE";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":id" => $source_id
            ]);

            $source = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$source) {

                throw new Exception("Client source introuvable.");
            }


            /*
            |--------------------------------------------------------------------------
            | 2. Récupérer et verrouiller le client destination
            |--------------------------------------------------------------------------
            */

            $sql = "SELECT *
                    FROM clients
                    WHERE ID = :id
                    FOR UPDATE";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":id" => $destination_id
            ]);

            $destination = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$destination) {

                throw new Exception("Client destination introuvable.");
            }


            /*
            |--------------------------------------------------------------------------
            | 3. Vérifier le solde
            |--------------------------------------------------------------------------
            */

            if ($montant > $source["SOLDE"]) {

                throw new Exception("Solde insuffisant.");
            }


            /*
            |--------------------------------------------------------------------------
            | 4. Calculer les nouveaux soldes
            |--------------------------------------------------------------------------
            */

            $nouveau_solde_source =
                $source["SOLDE"] - $montant;

            $nouveau_solde_destination =
                $destination["SOLDE"] + $montant;


            /*
            |--------------------------------------------------------------------------
            | 5. Débiter le client source
            |--------------------------------------------------------------------------
            */

            $sql = "UPDATE clients
                    SET SOLDE = :solde
                    WHERE ID = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":solde" => $nouveau_solde_source,
                ":id" => $source_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | 6. Créditer le client destination
            |--------------------------------------------------------------------------
            */

            $sql = "UPDATE clients
                    SET SOLDE = :solde
                    WHERE ID = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":solde" => $nouveau_solde_destination,
                ":id" => $destination_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | 7. Enregistrer le virement envoyé
            |--------------------------------------------------------------------------
            */

            $sql = "INSERT INTO transactions
                    (CLIENT_ID, TYPE, MONTANT)
                    VALUES
                    (:client_id, :type, :montant)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":client_id" => $source_id,
                ":type" => "VIREMENT_ENVOYE",
                ":montant" => $montant
            ]);


            /*
            |--------------------------------------------------------------------------
            | 8. Enregistrer le virement reçu
            |--------------------------------------------------------------------------
            */

            $stmt->execute([
                ":client_id" => $destination_id,
                ":type" => "VIREMENT_RECU",
                ":montant" => $montant
            ]);


            /*
            |--------------------------------------------------------------------------
            | 9. Valider toute la transaction
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            $message = "Virement effectué avec succès.";

        } catch (Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | En cas d'erreur : annuler
            |--------------------------------------------------------------------------
            */

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
    <title>BANK-SIM - Virement</title>
</head>

<body>

    <h1>BANK-SIM</h1>

    <h2>Effectuer un virement</h2>

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

        <label>Client source :</label>

        <input
            type="number"
            name="source_id"
            required
        >

        <br><br>

        <label>Client destination :</label>

        <input
            type="number"
            name="destination_id"
            required
        >

        <br><br>

        <label>Montant :</label>

        <input
            type="number"
            name="montant"
            step="0.01"
            min="0.01"
            required
        >

        <br><br>

        <button type="submit">
            Effectuer le virement
        </button>

    </form>

</body>

</html>