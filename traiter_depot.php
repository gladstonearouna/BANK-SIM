<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CLIENT");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Méthode non autorisée.");
}

$client_id = $_SESSION["client_id"];

$compte_id = filter_input(INPUT_POST, "compte_id", FILTER_VALIDATE_INT);

$montant = filter_input(
    INPUT_POST,
    "montant",
    FILTER_VALIDATE_FLOAT
);

if (!$compte_id) {
    die("Compte invalide.");
}


if (
    $montant === false ||
    $montant === null ||
    !is_finite((float) $montant) ||
    $montant <= 0 ||
    $montant > 100000
) {
    die("Montant invalide. Le dépôt doit être supérieur à 0 MAD et ne pas dépasser 100 000 MAD.");
}

try {

    /*
     * Début de la transaction SQL
     */
    $pdo->beginTransaction();


    /*
     * Vérifier que le compte appartient
     * bien au client connecté.
     *
     * FOR UPDATE permet de verrouiller
     * le compte pendant l'opération.
     */
    $sql = "SELECT ID, NUMERO_COMPTE, SOLDE, STATUT
            FROM comptes
            WHERE ID = :compte_id
            AND CLIENT_ID = :client_id
            FOR UPDATE";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":compte_id" => $compte_id,
        ":client_id" => $client_id
    ]);

    $compte = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$compte) {
        throw new Exception("Compte introuvable.");
    }


    /*
     * Vérifier que le compte est actif.
     */
    if ($compte["STATUT"] !== "ACTIF") {
        throw new Exception("Ce compte n'est pas actif.");
    }


    /*
     * Calculer le nouveau solde.
     */
    $nouveau_solde = (float) $compte["SOLDE"] + $montant;


    /*
     * Mettre à jour le solde du compte.
     */
    $sql = "UPDATE comptes
            SET SOLDE = :solde
            WHERE ID = :compte_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":solde" => $nouveau_solde,
        ":compte_id" => $compte_id
    ]);


    /*
     * Enregistrer la transaction.
     */
    $sql = "INSERT INTO transactions
            (CLIENT_ID, TYPE, MONTANT)
            VALUES
            (:client_id, 'DEPOT', :montant)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":client_id" => $client_id,
        ":montant" => $montant
    ]);


    /*
     * Créer une notification.
     */
    $message = "Votre dépôt de "
             . number_format($montant, 2, ',', ' ')
             . " MAD a été effectué avec succès.";

    $sql = "INSERT INTO notifications
            (CLIENT_ID, MESSAGE)
            VALUES
            (:client_id, :message)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":client_id" => $client_id,
        ":message" => $message
    ]);


    /*
     * Valider toutes les opérations.
     */
    $pdo->commit();


    /*
     * Message de succès.
     */
    $_SESSION["message_succes"] =
        "Dépôt de "
        . number_format($montant, 2, ',', ' ')
        . " MAD effectué avec succès.";


    /*
     * Retour au tableau de bord.
     */
    header("Location: dashboard.php");
    exit;


} catch (Exception $e) {

    /*
     * Annuler toutes les opérations
     * si une erreur survient.
     */
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Erreur lors du dépôt : " . $e->getMessage());
}