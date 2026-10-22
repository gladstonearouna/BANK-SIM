
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Vérifier que le formulaire a été envoyé
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: conseiller_ajouter_compte.php");
    exit;
}

// Récupérer les données du formulaire
$clientId = filter_input(
    INPUT_POST,
    "client_id",
    FILTER_VALIDATE_INT
);

$typeCompte = $_POST["type_compte"] ?? "";

// Vérifier les données reçues
if (!$clientId || $typeCompte !== "COURANT") {
    exit("Données invalides. Veuillez retourner au formulaire.");
}

try {
    // Vérifier que le client existe
    $stmt = $pdo->prepare(
        "SELECT ID FROM clients WHERE ID = :id"
    );

    $stmt->execute([
        "id" => $clientId
    ]);

    if (!$stmt->fetch()) {
        exit("Le client sélectionné n'existe pas.");
    }

    // Générer un numéro de compte unique
    do {
        $numeroCompte = "BANKSIM-" . random_int(10000000, 99999999);

        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM comptes
             WHERE NUMERO_COMPTE = :numero"
        );

        $stmt->execute([
            "numero" => $numeroCompte
        ]);

        $numeroExiste = (int) $stmt->fetchColumn() > 0;

    } while ($numeroExiste);

    // Créer le compte avec un solde initial de 0 MAD
    $stmt = $pdo->prepare(
        "INSERT INTO comptes
            (
                CLIENT_ID,
                NUMERO_COMPTE,
                TYPE_COMPTE,
                SOLDE,
                STATUT,
                DATE_CREATION
            )
         VALUES
            (
                :client_id,
                :numero,
                :type,
                0,
                'ACTIF',
                NOW()
            )"
    );

    $stmt->execute([
        "client_id" => $clientId,
        "numero" => $numeroCompte,
        "type" => $typeCompte
    ]);

    // Préparer le message de confirmation
    $_SESSION["message_succes"] =
        "Le compte " . $numeroCompte .
        " a été créé avec succès.";

    // Retourner automatiquement à la liste des comptes
    header("Location: conseiller_comptes.php");
    exit;

} catch (PDOException $e) {
    // Enregistrer l'erreur dans le journal du serveur
    error_log($e->getMessage());

    http_response_code(500);

    echo "Une erreur est survenue lors de la création du compte.";
    echo '<p><a href="conseiller_ajouter_compte.php">Retour au formulaire</a></p>';
}