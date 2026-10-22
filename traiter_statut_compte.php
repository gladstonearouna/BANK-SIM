
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Accepter uniquement les formulaires envoyés en POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: conseiller_comptes.php");
    exit;
}

// Vérifier l'identifiant du compte
$compteId = filter_input(
    INPUT_POST,
    "compte_id",
    FILTER_VALIDATE_INT
);

// Récupérer le nouveau statut demandé
$nouveauStatut = $_POST["nouveau_statut"] ?? "";

// Autoriser uniquement ces deux statuts
if (
    !$compteId ||
    !in_array($nouveauStatut, ["ACTIF", "SUSPENDU"], true)
) {
    $_SESSION["message_succes"] =
        "Les informations reçues sont invalides.";

    header("Location: conseiller_comptes.php");
    exit;
}

try {

    // Vérifier que le compte existe
    $stmt = $pdo->prepare(
        "SELECT ID, STATUT
         FROM comptes
         WHERE ID = :id"
    );

    $stmt->execute([
        "id" => $compteId
    ]);

    $compte = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compte) {
        $_SESSION["message_succes"] =
            "Le compte demandé n'existe pas.";

        header("Location: conseiller_comptes.php");
        exit;
    }

    // Éviter de répéter une action déjà effectuée
    if ($compte["STATUT"] === $nouveauStatut) {
        $_SESSION["message_succes"] =
            "Le compte possède déjà ce statut.";

        header("Location: conseiller_comptes.php");
        exit;
    }

    // Mettre à jour le statut du compte
    $stmt = $pdo->prepare(
        "UPDATE comptes
         SET STATUT = :statut
         WHERE ID = :id"
    );

    $stmt->execute([
        "statut" => $nouveauStatut,
        "id" => $compteId
    ]);

    // Préparer le message de confirmation
    if ($nouveauStatut === "SUSPENDU") {
        $_SESSION["message_succes"] =
            "Le compte a été suspendu avec succès.";
    } else {
        $_SESSION["message_succes"] =
            "Le compte a été réactivé avec succès.";
    }

    // Retourner à la liste des comptes
    header("Location: conseiller_comptes.php");
    exit;

} catch (PDOException $e) {

    // Enregistrer l'erreur sans exposer les détails techniques
    error_log($e->getMessage());

    http_response_code(500);

    echo "Une erreur est survenue lors de la modification du compte.";
    echo '<p><a href="conseiller_comptes.php">Retour aux comptes</a></p>';
}