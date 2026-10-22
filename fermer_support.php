
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Autoriser uniquement les formulaires POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

// Vérifier le jeton CSRF.
$sessionToken = $_SESSION["csrf_token"] ?? "";
$postedToken = $_POST["csrf_token"] ?? "";

if (
    !is_string($sessionToken) ||
    $sessionToken === "" ||
    !is_string($postedToken) ||
    !hash_equals($sessionToken, $postedToken)
) {
    $_SESSION["message_erreur"] =
        "La vérification de sécurité a échoué. Réessayez.";

    header("Location: conseiller_support.php");
    exit;
}

// Valider l'identifiant du ticket.
$support_id = filter_var(
    $_POST["support_id"] ?? null,
    FILTER_VALIDATE_INT
);

if ($support_id === false || $support_id === null || $support_id <= 0) {
    $_SESSION["message_erreur"] = "Identifiant de demande invalide.";

    header("Location: conseiller_support.php");
    exit;
}

// Récupérer et vérifier la réponse du conseiller.
$reponse = $_POST["reponse"] ?? "";

if (!is_string($reponse)) {
    $_SESSION["message_erreur"] = "La réponse fournie est invalide.";

    header("Location: conseiller_support.php");
    exit;
}

$reponse = trim($reponse);

$longueurReponse = function_exists("mb_strlen")
    ? mb_strlen($reponse, "UTF-8")
    : strlen($reponse);

if ($reponse === "") {
    $_SESSION["message_erreur"] =
        "Vous devez rédiger une réponse avant de traiter la demande.";

    header("Location: conseiller_support.php");
    exit;
}

if ($longueurReponse > 200) {
    $_SESSION["message_erreur"] =
        "Votre réponse dépasse la limite de 200 caractères.";

    header("Location: conseiller_support.php");
    exit;
}

try {
    // Début de la transaction SQL.
    $pdo->beginTransaction();

    // Verrouiller le ticket pendant son traitement.
    $sql = "
        SELECT ID, CLIENT_ID, SUJET, STATUT
        FROM support
        WHERE ID = :support_id
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":support_id" => $support_id
    ]);

    $support = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$support) {
        throw new RuntimeException("DEMANDE_INTROUVABLE");
    }

    if ($support["STATUT"] !== "OUVERT") {
        throw new RuntimeException("DEMANDE_NON_OUVERTE");
    }

    $client_id = filter_var(
        $support["CLIENT_ID"],
        FILTER_VALIDATE_INT
    );

    if ($client_id === false || $client_id === null || $client_id <= 0) {
        throw new RuntimeException("CLIENT_INVALIDE");
    }

    // Construire la notification avec la réponse du conseiller.
    $messageNotification =
        "Réponse support ticket #" . $support["ID"] . " : " . $reponse;

    $longueurNotification = function_exists("mb_strlen")
        ? mb_strlen($messageNotification, "UTF-8")
        : strlen($messageNotification);

    // La colonne MESSAGE accepte au maximum 255 caractères.
    if ($longueurNotification > 255) {
        throw new RuntimeException("NOTIFICATION_TROP_LONGUE");
    }

    // Marquer le ticket comme traité uniquement s'il est encore ouvert.
    $sql = "
        UPDATE support
        SET STATUT = 'TRAITE'
        WHERE ID = :support_id
          AND STATUT = 'OUVERT'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":support_id" => $support_id
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException("MISE_A_JOUR_IMPOSSIBLE");
    }

    // Enregistrer la réponse comme notification du client concerné.
    $sql = "
        INSERT INTO notifications (CLIENT_ID, MESSAGE)
        VALUES (:client_id, :message)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":client_id" => $client_id,
        ":message" => $messageNotification
    ]);

    // Valider les deux opérations ensemble.
    $pdo->commit();

    $_SESSION["message_succes"] =
        "Le ticket #" . $support_id .
        " est traité. Votre réponse a été envoyée au client.";

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e instanceof RuntimeException) {

        switch ($e->getMessage()) {
            case "DEMANDE_INTROUVABLE":
                $_SESSION["message_erreur"] =
                    "Cette demande de support est introuvable.";
                break;

            case "DEMANDE_NON_OUVERTE":
                $_SESSION["message_erreur"] =
                    "Cette demande a déjà été traitée ou n'est plus ouverte.";
                break;

            case "CLIENT_INVALIDE":
                $_SESSION["message_erreur"] =
                    "Le client associé à cette demande est invalide.";
                break;

            case "NOTIFICATION_TROP_LONGUE":
                $_SESSION["message_erreur"] =
                    "La notification dépasse 255 caractères. Raccourcissez votre réponse.";
                break;

            default:
                $_SESSION["message_erreur"] =
                    "La demande n'a pas pu être traitée. Réessayez.";
                break;
        }

    } else {
        // Les détails techniques restent dans le journal du serveur.
        error_log("Erreur traitement support : " . $e->getMessage());

        $_SESSION["message_erreur"] =
            "Une erreur est survenue. Aucune modification n'a été conservée.";
    }
}

// Retourner à la liste des demandes.
header("Location: conseiller_support.php");
exit;
