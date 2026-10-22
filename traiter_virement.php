
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

$client_id = (int) $_SESSION["client_id"];

/*
 * 1. Vérification CSRF
 */
$token_recu = $_POST["csrf_token"] ?? "";

if (
    !is_string($token_recu) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token_recu)
) {
    http_response_code(403);
    exit("Requête refusée. Recharge la page et réessaie.");
}

/*
 * 2. Validation des données
 */
$compte_id = filter_input(
    INPUT_POST,
    "compte_id",
    FILTER_VALIDATE_INT
);

$beneficiaire_id = filter_input(
    INPUT_POST,
    "beneficiaire_id",
    FILTER_VALIDATE_INT
);

$montant = filter_input(
    INPUT_POST,
    "montant",
    FILTER_VALIDATE_FLOAT
);

if ($compte_id === false || $compte_id === null || $compte_id <= 0) {
    exit("Compte expéditeur invalide.");
}

if (
    $beneficiaire_id === false ||
    $beneficiaire_id === null ||
    $beneficiaire_id <= 0
) {
    exit("Compte bénéficiaire invalide.");
}

if (
    $montant === false ||
    $montant === null ||
    !is_finite((float) $montant) ||
    $montant <= 0 ||
    $montant > 100000
) {
    exit("Montant invalide.");
}

$compte_id = (int) $compte_id;
$beneficiaire_id = (int) $beneficiaire_id;
$montant = round((float) $montant, 2);

if ($montant <= 0) {
    exit("Le montant doit être supérieur à zéro.");
}

try {

    /*
     * 3. Démarrer la transaction SQL
     */
    $pdo->beginTransaction();

    /*
     * 4. Vérifier que le compte expéditeur appartient
     * au client connecté et qu'il est actif.
     */
    $sql = "SELECT ID, SOLDE
            FROM comptes
            WHERE ID = :compte_id
              AND CLIENT_ID = :client_id
              AND STATUT = 'ACTIF'
            FOR UPDATE";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":compte_id" => $compte_id,
        ":client_id" => $client_id
    ]);

    $compte = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compte) {
        throw new Exception("Compte expéditeur invalide.");
    }

    if ((float) $compte["SOLDE"] < $montant) {
        throw new Exception("Solde insuffisant.");
    }

    /*
     * 5. Vérifier que le compte bénéficiaire choisi
     * figure dans la liste personnelle du client.
     *
     * Ici, beneficiaire_id représente l'ID du COMPTE,
     * pas l'ID du client.
     */
    $sql = "SELECT
                c.ID,
                c.CLIENT_ID,
                c.SOLDE
            FROM beneficiaires b
            INNER JOIN comptes c
                ON c.ID = b.COMPTE_BENEFICIAIRE_ID
            WHERE b.CLIENT_ID = :client_id
              AND c.ID = :compte_beneficiaire_id
              AND c.STATUT = 'ACTIF'
              AND c.CLIENT_ID != :client_id2
            FOR UPDATE";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":client_id" => $client_id,
        ":compte_beneficiaire_id" => $beneficiaire_id,
        ":client_id2" => $client_id
    ]);

    $compteBeneficiaire = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compteBeneficiaire) {
        throw new Exception(
            "Ce compte n'est pas dans ta liste de bénéficiaires ou il est inactif."
        );
    }

    $beneficiaire_client_id = (int) $compteBeneficiaire["CLIENT_ID"];
    $compte_beneficiaire_id = (int) $compteBeneficiaire["ID"];

    /*
     * 6. Interdire un virement vers le même compte.
     */
    if ($compte_beneficiaire_id === $compte_id) {
        throw new Exception(
            "Les comptes expéditeur et bénéficiaire doivent être différents."
        );
    }

    /*
     * 7. Débiter le compte expéditeur.
     */
    $sql = "UPDATE comptes
            SET SOLDE = SOLDE - :montant
            WHERE ID = :compte_id
              AND CLIENT_ID = :client_id
              AND STATUT = 'ACTIF'
              AND SOLDE >= :montant_verification";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":montant" => $montant,
        ":compte_id" => $compte_id,
        ":client_id" => $client_id,
        ":montant_verification" => $montant
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception("Le débit du compte a échoué.");
    }

    /*
     * 8. Créditer exactement le compte bénéficiaire choisi.
     */
    $sql = "UPDATE comptes
            SET SOLDE = SOLDE + :montant
            WHERE ID = :compte_id
              AND STATUT = 'ACTIF'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":montant" => $montant,
        ":compte_id" => $compte_beneficiaire_id
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception("Le crédit du compte a échoué.");
    }

    /*
     * 9. Enregistrer le virement envoyé.
     */
    $sql = "INSERT INTO transactions
            (CLIENT_ID, TYPE, MONTANT)
            VALUES
            (:client_id, 'VIREMENT_ENVOYE', :montant)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":client_id" => $client_id,
        ":montant" => $montant
    ]);

    /*
     * 10. Enregistrer le virement reçu.
     */
    $sql = "INSERT INTO transactions
            (CLIENT_ID, TYPE, MONTANT)
            VALUES
            (:client_id, 'VIREMENT_RECU', :montant)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":client_id" => $beneficiaire_client_id,
        ":montant" => $montant
    ]);

    /*
     * 11. Notification de l'expéditeur.
     */
    $messageExpediteur =
        "Votre virement de "
        . number_format($montant, 2, ',', ' ')
        . " MAD a été envoyé avec succès.";

    $sql = "INSERT INTO notifications
            (CLIENT_ID, MESSAGE)
            VALUES
            (:client_id, :message)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":client_id" => $client_id,
        ":message" => $messageExpediteur
    ]);

    /*
     * 12. Notification du bénéficiaire.
     */
    $messageBeneficiaire =
        "Vous avez reçu un virement de "
        . number_format($montant, 2, ',', ' ')
        . " MAD.";

    $stmt->execute([
        ":client_id" => $beneficiaire_client_id,
        ":message" => $messageBeneficiaire
    ]);

    /*
     * 13. Valider toutes les opérations ensemble.
     */
    $pdo->commit();

    $_SESSION["message_succes"] =
        "Virement de "
        . number_format($montant, 2, ',', ' ')
        . " MAD effectué avec succès.";

    header("Location: dashboard.php");
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Erreur virement BANK-SIM : " . $e->getMessage()
    );

    $_SESSION["message_virement"] =
        "Virement impossible. Vérifie les informations et le solde, puis réessaie.";

    header("Location: client_virement.php");
    exit;
}

