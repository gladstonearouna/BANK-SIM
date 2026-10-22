
<?php

require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("CONSEILLER");

// Toutes les modifications doivent passer par POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

// Vérification du jeton CSRF.
$sessionToken = $_SESSION["csrf_token"] ?? "";
$postedToken = $_POST["csrf_token"] ?? "";

if (
    !is_string($sessionToken) ||
    $sessionToken === "" ||
    !is_string($postedToken) ||
    !hash_equals($sessionToken, $postedToken)
) {
    $_SESSION["message_erreur"] =
        "Vérification de sécurité échouée. Réessayez.";

    header("Location: conseiller_demandes.php");
    exit;
}

// Vérifier l'identifiant de la demande.
$id = filter_var(
    $_POST["id"] ?? null,
    FILTER_VALIDATE_INT
);

if ($id === false || $id === null || $id <= 0) {
    $_SESSION["message_erreur"] =
        "Identifiant de demande invalide.";

    header("Location: conseiller_demandes.php");
    exit;
}

// Vérifier l'action demandée.
$action = $_POST["action"] ?? "";

if (!in_array($action, ["accepter", "refuser"], true)) {
    $_SESSION["message_erreur"] = "Action invalide.";

    header("Location: conseiller_demandes.php");
    exit;
}

try {
    // Toutes les opérations seront validées ensemble.
    $pdo->beginTransaction();

    // Verrouiller la demande pour empêcher un traitement simultané.
    $sql = "SELECT *
            FROM demandes
            WHERE ID = :id
            FOR UPDATE";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $id]);

    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        throw new RuntimeException("DEMANDE_INTROUVABLE");
    }

    if ($demande["STATUT"] !== "EN_ATTENTE") {
        throw new RuntimeException("DEMANDE_DEJA_TRAITEE");
    }

    // ================================================
    // 1. REFUSER UNE DEMANDE
    // ================================================

    if ($action === "refuser") {
        $sql = "UPDATE demandes
                SET STATUT = 'REFUSEE',
                    MOT_DE_PASSE_HASH = NULL
                WHERE ID = :id
                  AND STATUT = 'EN_ATTENTE'";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([":id" => $id]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException("MISE_A_JOUR_IMPOSSIBLE");
        }

        $pdo->commit();

        $_SESSION["message_succes"] =
            "La demande a été refusée.";

    } else {

        // ================================================
        // 2. ACCEPTER UNE DEMANDE D'OUVERTURE DE COMPTE
        // ================================================

        if ($demande["TYPE_DEMANDE"] === "OUVERTURE_COMPTE") {

            // --------------------------------------------
            // CAS A : LE CANDIDAT N'EST PAS ENCORE CLIENT
            // CLIENT_ID est NULL.
            // --------------------------------------------

            if ($demande["CLIENT_ID"] === null) {

                // Vérifier les informations indispensables.
                $champsObligatoires = [
                    "NOM_COMPLET",
                    "DATE_NAISSANCE",
                    "NATIONALITE",
                    "PAYS_RESIDENCE",
                    "TELEPHONE",
                    "EMAIL",
                    "ADRESSE",
                    "MOT_DE_PASSE_HASH"
                ];

                foreach ($champsObligatoires as $champ) {
                    if (
                        !isset($demande[$champ]) ||
                        trim((string) $demande[$champ]) === ""
                    ) {
                        throw new RuntimeException(
                            "INFORMATIONS_INCOMPLETES"
                        );
                    }
                }

                $email = trim($demande["EMAIL"]);

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException("EMAIL_INVALIDE");
                }

                // Vérifier qu'un compte utilisateur n'utilise
                // pas déjà cette adresse e-mail.
                $sql = "SELECT ID
                        FROM users
                        WHERE EMAIL = :email
                        LIMIT 1
                        FOR UPDATE";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([":email" => $email]);

                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    throw new RuntimeException("EMAIL_DEJA_UTILISE");
                }

                // Vérifier également les adresses enregistrées
                // dans les fiches clients.
                $sql = "SELECT ID
                        FROM clients
                        WHERE EMAIL = :email
                        LIMIT 1
                        FOR UPDATE";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([":email" => $email]);

                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    throw new RuntimeException("EMAIL_DEJA_UTILISE");
                }

                // Créer la fiche client avec les informations
                // personnelles du formulaire.
                $sql = "INSERT INTO clients (
                            NOM,
                            SOLDE,
                            DATE_NAISSANCE,
                            NATIONALITE,
                            PAYS_RESIDENCE,
                            TELEPHONE,
                            EMAIL,
                            ADRESSE
                        ) VALUES (
                            :nom,
                            0.00,
                            :date_naissance,
                            :nationalite,
                            :pays_residence,
                            :telephone,
                            :email,
                            :adresse
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":nom" => $demande["NOM_COMPLET"],
                    ":date_naissance" => $demande["DATE_NAISSANCE"],
                    ":nationalite" => $demande["NATIONALITE"],
                    ":pays_residence" => $demande["PAYS_RESIDENCE"],
                    ":telephone" => $demande["TELEPHONE"],
                    ":email" => $email,
                    ":adresse" => $demande["ADRESSE"]
                ]);

                $clientId = (int) $pdo->lastInsertId();

                // Créer l'accès client en réutilisant le hash
                // du mot de passe choisi lors de la demande.
                // Le mot de passe n'est jamais enregistré en clair.
                $sql = "INSERT INTO users (
                            CLIENT_ID,
                            EMAIL,
                            MOT_DE_PASSE_HASH,
                            ROLE,
                            ACTIF
                        ) VALUES (
                            :client_id,
                            :email,
                            :hash,
                            'CLIENT',
                            1
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":client_id" => $clientId,
                    ":email" => $email,
                    ":hash" => $demande["MOT_DE_PASSE_HASH"]
                ]);

                // Associer la demande au client créé.
                $sql = "UPDATE demandes
                        SET CLIENT_ID = :client_id
                        WHERE ID = :id
                          AND CLIENT_ID IS NULL";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":client_id" => $clientId,
                    ":id" => $id
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        "ASSOCIATION_CLIENT_IMPOSSIBLE"
                    );
                }

            } else {

                // --------------------------------------------
                // CAS B : LE CLIENT EXISTE DÉJÀ
                // Conserver le fonctionnement précédent.
                // --------------------------------------------

                $clientId = (int) $demande["CLIENT_ID"];

                $sql = "SELECT ID
                        FROM clients
                        WHERE ID = :client_id
                        FOR UPDATE";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":client_id" => $clientId
                ]);

                if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                    throw new RuntimeException("CLIENT_INTROUVABLE");
                }
            }

            // --------------------------------------------
            // CRÉER LE COMPTE COURANT
            // --------------------------------------------

            $sql = "SELECT ID
                    FROM comptes
                    WHERE CLIENT_ID = :client_id
                      AND TYPE_COMPTE = 'COURANT'
                    LIMIT 1
                    FOR UPDATE";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":client_id" => $clientId
            ]);

            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException("COMPTE_EXISTANT");
            }

            // Numéro temporaire unique avant génération
            // du numéro définitif.
            $numeroTemporaire = "TEMP-" . bin2hex(random_bytes(8));

            $sql = "INSERT INTO comptes (
                        CLIENT_ID,
                        NUMERO_COMPTE,
                        TYPE_COMPTE,
                        SOLDE,
                        STATUT
                    ) VALUES (
                        :client_id,
                        :numero,
                        'COURANT',
                        0.00,
                        'ACTIF'
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":client_id" => $clientId,
                ":numero" => $numeroTemporaire
            ]);

            $compteId = (int) $pdo->lastInsertId();

            $numeroCompte = "BANKSIM-" .
                str_pad((string) $compteId, 4, "0", STR_PAD_LEFT);

            $sql = "UPDATE comptes
                    SET NUMERO_COMPTE = :numero
                    WHERE ID = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":numero" => $numeroCompte,
                ":id" => $compteId
            ]);
        }

        // Accepter la demande et supprimer le hash conservé
        // dans celle-ci : il existe désormais dans users.
        $sql = "UPDATE demandes
                SET STATUT = 'ACCEPTEE',
                    MOT_DE_PASSE_HASH = NULL
                WHERE ID = :id
                  AND STATUT = 'EN_ATTENTE'";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([":id" => $id]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException("MISE_A_JOUR_IMPOSSIBLE");
        }

        $pdo->commit();

        $_SESSION["message_succes"] =
            "La demande a été acceptée.";
    }

} catch (Throwable $e) {

    // Annuler toutes les modifications en cas d'échec.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Journal technique côté serveur.
    error_log(
        "Erreur traiter_demande.php : " . $e->getMessage()
    );

    if ($e instanceof RuntimeException) {
        switch ($e->getMessage()) {

            case "DEMANDE_INTROUVABLE":
                $_SESSION["message_erreur"] =
                    "Demande introuvable.";
                break;

            case "DEMANDE_DEJA_TRAITEE":
                $_SESSION["message_erreur"] =
                    "Cette demande a déjà été traitée.";
                break;

            case "CLIENT_INTROUVABLE":
                $_SESSION["message_erreur"] =
                    "Le client associé à cette demande est introuvable.";
                break;

            case "COMPTE_EXISTANT":
                $_SESSION["message_erreur"] =
                    "Ce client possède déjà un compte courant. La demande n'a pas été acceptée.";
                break;

            case "INFORMATIONS_INCOMPLETES":
                $_SESSION["message_erreur"] =
                    "Le dossier est incomplet. Vérifiez les informations du candidat.";
                break;

            case "EMAIL_INVALIDE":
                $_SESSION["message_erreur"] =
                    "L'adresse e-mail enregistrée dans la demande est invalide.";
                break;

            case "EMAIL_DEJA_UTILISE":
                $_SESSION["message_erreur"] =
                    "Cette adresse e-mail est déjà utilisée par un autre compte.";
                break;

            case "ASSOCIATION_CLIENT_IMPOSSIBLE":
                $_SESSION["message_erreur"] =
                    "Impossible d'associer le client à la demande.";
                break;

            default:
                $_SESSION["message_erreur"] =
                    "Le traitement a échoué. Aucune modification n'a été conservée.";
                break;
        }
    } else {
        $_SESSION["message_erreur"] =
            "Une erreur technique est survenue. Réessayez plus tard.";
    }
}

// Retourner à la liste des demandes.
header("Location: conseiller_demandes.php");
exit;
