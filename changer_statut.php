
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("ADMINISTRATEUR");

// Cette page doit recevoir un formulaire POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

// Vérifier le jeton CSRF.
$jetonSession = $_SESSION["csrf_token"] ?? "";
$jetonFormulaire = $_POST["csrf_token"] ?? "";

if (
    !is_string($jetonSession) ||
    !is_string($jetonFormulaire) ||
    $jetonSession === "" ||
    !hash_equals($jetonSession, $jetonFormulaire)
) {
    http_response_code(403);
    exit("Requête refusée. Rechargez la page et réessayez.");
}

// Vérifier l'identifiant de l'utilisateur.
$id = $_POST["id"] ?? null;

if (
    filter_var($id, FILTER_VALIDATE_INT) === false ||
    (int) $id < 1
) {
    exit("ID utilisateur invalide.");
}

$id = (int) $id;

// Empêcher l'administrateur de modifier son propre compte.
if ($id === (int) $_SESSION["user_id"]) {
    exit("Vous ne pouvez pas modifier votre propre compte.");
}

// Récupérer le statut actuel.
$sql = "SELECT ID, ACTIF
        FROM users
        WHERE ID = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $id]);

$utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$utilisateur) {
    exit("Utilisateur introuvable.");
}

// Inverser le statut.
$nouveauStatut = (int) $utilisateur["ACTIF"] === 1 ? 0 : 1;

// Enregistrer le nouveau statut.
$sql = "UPDATE users
        SET ACTIF = :actif
        WHERE ID = :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":actif" => $nouveauStatut,
    ":id" => $id
]);

header("Location: utilisateurs.php");
exit;
