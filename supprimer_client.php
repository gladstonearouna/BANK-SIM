
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("ADMINISTRATEUR");

// Autoriser uniquement les requêtes POST.
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

// Vérifier l'identifiant.
$id = $_POST["id"] ?? null;

if (
    filter_var($id, FILTER_VALIDATE_INT) === false ||
    (int) $id < 1
) {
    exit("ID du client invalide.");
}

$id = (int) $id;

// Vérifier que le client existe.
$stmt = $pdo->prepare(
    "SELECT ID FROM clients WHERE ID = :id"
);

$stmt->execute([":id" => $id]);

if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    exit("Client introuvable.");
}

// Ne pas supprimer un client associé à un utilisateur.
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM users WHERE CLIENT_ID = :id"
);
$stmt->execute([":id" => $id]);

if ((int) $stmt->fetchColumn() > 0) {
    exit(
        "Suppression impossible : ce client est associé à un utilisateur. "
        . "Désactivez son accès si nécessaire."
    );
}

// Ne pas supprimer un client qui possède des comptes.
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM comptes WHERE CLIENT_ID = :id"
);
$stmt->execute([":id" => $id]);

if ((int) $stmt->fetchColumn() > 0) {
    exit(
        "Suppression impossible : ce client possède un ou plusieurs comptes."
    );
}

// Ne pas supprimer un client qui possède des transactions.
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM transactions WHERE CLIENT_ID = :id"
);
$stmt->execute([":id" => $id]);

if ((int) $stmt->fetchColumn() > 0) {
    exit(
        "Suppression impossible : ce client possède un historique de transactions."
    );
}

// Supprimer uniquement si aucune dépendance n'a été trouvée.
$stmt = $pdo->prepare(
    "DELETE FROM clients WHERE ID = :id"
);

$stmt->execute([":id" => $id]);

header("Location: clients.php");
exit;
