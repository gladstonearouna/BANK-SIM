
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

// Autoriser uniquement les requêtes POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

$client_id = (int) $_SESSION["client_id"];

// Vérifier le jeton CSRF
$token_recu = $_POST["csrf_token"] ?? "";

if (
    !is_string($token_recu) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token_recu)
) {
    http_response_code(403);
    exit("Requête refusée. Recharge la page et réessaie.");
}

// Valider l'identifiant du compte bénéficiaire
$compte_beneficiaire_id = filter_input(
    INPUT_POST,
    "compte_beneficiaire_id",
    FILTER_VALIDATE_INT
);

if (
    $compte_beneficiaire_id === false ||
    $compte_beneficiaire_id === null ||
    $compte_beneficiaire_id <= 0
) {
    $_SESSION["message_beneficiaire"] =
        "Le compte bénéficiaire est invalide.";

    header("Location: client_beneficiaires.php");
    exit;
}

$compte_beneficiaire_id = (int) $compte_beneficiaire_id;

try {

    // Vérifier que le compte existe, est actif et appartient à un autre client
    $sql = "SELECT ID, CLIENT_ID
            FROM comptes
            WHERE ID = :compte_id
              AND STATUT = 'ACTIF'
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":compte_id" => $compte_beneficiaire_id
    ]);

    $compte = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compte) {
        throw new Exception(
            "Ce compte n'existe pas ou n'est pas actif."
        );
    }

    if ((int) $compte["CLIENT_ID"] === $client_id) {
        throw new Exception(
            "Tu ne peux pas ajouter ton propre compte comme bénéficiaire."
        );
    }

    // Vérifier si ce bénéficiaire est déjà enregistré
    $sql = "SELECT ID
            FROM beneficiaires
            WHERE CLIENT_ID = :client_id
              AND COMPTE_BENEFICIAIRE_ID = :compte_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":client_id" => $client_id,
        ":compte_id" => $compte_beneficiaire_id
    ]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            "Ce bénéficiaire est déjà enregistré dans ta liste."
        );
    }

    // Enregistrer le bénéficiaire pour le client connecté uniquement
    $sql = "INSERT INTO beneficiaires
            (CLIENT_ID, COMPTE_BENEFICIAIRE_ID)
            VALUES
            (:client_id, :compte_id)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":client_id" => $client_id,
        ":compte_id" => $compte_beneficiaire_id
    ]);

    $_SESSION["message_beneficiaire"] =
        "Le bénéficiaire a été ajouté avec succès.";

    header("Location: client_beneficiaires.php");
    exit;

} catch (Throwable $e) {

    error_log(
        "Erreur ajout bénéficiaire BANK-SIM : "
        . $e->getMessage()
    );

    if ($e instanceof PDOException && $e->getCode() === "23000") {
        $_SESSION["message_beneficiaire"] =
            "Ce bénéficiaire est déjà enregistré ou les informations sont invalides.";
    } elseif ($e instanceof PDOException) {
        $_SESSION["message_beneficiaire"] =
            "Impossible d'enregistrer le bénéficiaire pour le moment.";
    } else {
        $_SESSION["message_beneficiaire"] = $e->getMessage();
    }

    header("Location: client_beneficiaires.php");
    exit;
}
