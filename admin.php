
<?php
require_once "auth.php";
require_once "role.php";
require_once "config.php";

requireRole("ADMINISTRATEUR");

// Protection de l'affichage des données
function e($valeur) {
    return htmlspecialchars((string)$valeur, ENT_QUOTES, "UTF-8");
}

// Compter les lignes d'une table
function compter($pdo, $sql) {
    return (int) $pdo->query($sql)->fetchColumn();
}

try {
    // Statistiques générales
    $nombreClients = compter($pdo, "SELECT COUNT(*) FROM clients");
    $nombreUtilisateurs = compter($pdo, "SELECT COUNT(*) FROM users");
    $nombreConseillers = compter(
        $pdo,
        "SELECT COUNT(*) FROM users WHERE ROLE = 'CONSEILLER'"
    );
    $utilisateursActifs = compter(
        $pdo,
        "SELECT COUNT(*) FROM users WHERE ACTIF = 1"
    );

    $nombreComptes = compter($pdo, "SELECT COUNT(*) FROM comptes");
    $comptesActifs = compter(
        $pdo,
        "SELECT COUNT(*) FROM comptes WHERE STATUT = 'ACTIF'"
    );

    $soldeTotal = (float) $pdo
        ->query("SELECT COALESCE(SUM(SOLDE), 0) FROM comptes")
        ->fetchColumn();

    // Statistiques des opérations
    $operations = [
        "DEPOT" => ["nom" => "Dépôts", "nombre" => 0, "montant" => 0],
        "RETRAIT" => ["nom" => "Retraits", "nombre" => 0, "montant" => 0],
        "VIREMENT_ENVOYE" => [
            "nom" => "Virements envoyés",
            "nombre" => 0,
            "montant" => 0
        ],
        "VIREMENT_RECU" => [
            "nom" => "Virements reçus",
            "nombre" => 0,
            "montant" => 0
        ]
    ];

    $sqlOperations = "
        SELECT TYPE, COUNT(*) AS nombre, COALESCE(SUM(MONTANT), 0) AS montant
        FROM transactions
        GROUP BY TYPE
    ";

    foreach ($pdo->query($sqlOperations) as $ligne) {
        if (isset($operations[$ligne["TYPE"]])) {
            $operations[$ligne["TYPE"]]["nombre"] = (int)$ligne["nombre"];
            $operations[$ligne["TYPE"]]["montant"] = (float)$ligne["montant"];
        }
    }

    // Activité des 7 derniers jours
    $sqlGraphique = "
        SELECT
            DATE(DATE_TRANSACTION) AS jour,
            SUM(CASE WHEN TYPE = 'DEPOT' THEN 1 ELSE 0 END) AS depots,
            SUM(CASE WHEN TYPE = 'RETRAIT' THEN 1 ELSE 0 END) AS retraits,
            SUM(
                CASE
                    WHEN TYPE IN ('VIREMENT_ENVOYE', 'VIREMENT_RECU')
                    THEN 1 ELSE 0
                END
            ) AS virements
        FROM transactions
        WHERE DATE_TRANSACTION >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(DATE_TRANSACTION)
        ORDER BY jour ASC
    ";

    $lignesGraphique = $pdo->query($sqlGraphique)->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $depots = [];
    $retraits = [];
    $virements = [];

    // Préparer les 7 jours, y compris ceux sans opération
    for ($i = 6; $i >= 0; $i--) {
        $jour = date("Y-m-d", strtotime("-$i days"));
        $labels[] = date("d/m", strtotime($jour));
        $depots[$jour] = 0;
        $retraits[$jour] = 0;
        $virements[$jour] = 0;
    }

    foreach ($lignesGraphique as $ligne) {
        $jour = $ligne["jour"];

        if (array_key_exists($jour, $depots)) {
            $depots[$jour] = (int)$ligne["depots"];
            $retraits[$jour] = (int)$ligne["retraits"];
            $virements[$jour] = (int)$ligne["virements"];
        }
    }

    $depots = array_values($depots);
    $retraits = array_values($retraits);
    $virements = array_values($virements);

    // Répartition des utilisateurs par rôle
    $roles = $pdo->query("
        SELECT ROLE, COUNT(*) AS nombre
        FROM users
        GROUP BY ROLE
    ")->fetchAll(PDO::FETCH_ASSOC);

    $labelsRoles = [];
    $valeursRoles = [];

    foreach ($roles as $role) {
        $labelsRoles[] = $role["ROLE"];
        $valeursRoles[] = (int)$role["nombre"];
    }

    // Dernières transactions
    $dernieresTransactions = $pdo->query("
        SELECT
            t.ID,
            t.TYPE,
            t.MONTANT,
            t.DATE_TRANSACTION,
            c.NOM
        FROM transactions t
        LEFT JOIN clients c ON c.ID = t.CLIENT_ID
        ORDER BY t.DATE_TRANSACTION DESC, t.ID DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $erreur) {
    error_log($erreur->getMessage());
    http_response_code(500);
    exit("Impossible de charger les statistiques. Vérifiez la structure de la base de données.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - BANK-SIM</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        
:root {
    --ivoire: #F5F0E4;
    --blanc: #FFFEFB;
    --bleu: #14243A;
    --bleu-clair: #263E5C;
    --dore: #C7A45D;
    --texte: #343B45;
    --gris: #777F89;
    --bordure: #E5DDCE;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: var(--ivoire);
    color: var(--texte);
    font-size: 14px;
    line-height: 1.5;
}

header {
    background: var(--bleu);
    color: white;
    padding: 18px 4%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    border-bottom: 3px solid var(--dore);
}

header h1 {
    margin: 0;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 25px;
    letter-spacing: 1.5px;
}

header p {
    margin: 3px 0 0;
    color: #D8CBAF;
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.logout {
    color: var(--bleu);
    background: var(--dore);
    padding: 9px 14px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 600;
    transition: background 0.2s ease;
}

.logout:hover {
    background: #E0C47F;
}

main {
    max-width: 1400px;
    margin: 22px auto;
    padding: 0 24px;
}

.welcome-panel {
    background: var(--blanc);
    border: 1px solid var(--bordure);
    border-left: 4px solid var(--dore);
    border-radius: 8px;
    padding: 18px 21px;
    margin-bottom: 22px;
    box-shadow: 0 4px 14px rgba(20, 36, 58, 0.04);
}

.welcome-panel h2 {
    color: var(--bleu);
    font-family: Georgia, "Times New Roman", serif;
    font-size: 24px;
    margin: 0 0 5px;
}

.welcome-panel p {
    margin: 4px 0;
}

.section-title {
    color: var(--bleu);
    font-family: Georgia, "Times New Roman", serif;
    font-size: 19px;
    margin: 25px 0 12px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(205px, 1fr));
    gap: 13px;
}

.stat-card {
    background: var(--blanc);
    border: 1px solid var(--bordure);
    border-radius: 8px;
    padding: 17px;
    box-shadow: 0 3px 10px rgba(20, 36, 58, 0.035);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 18px rgba(20, 36, 58, 0.08);
}

.stat-label {
    color: var(--gris);
    font-size: 12px;
    margin-bottom: 8px;
}

.stat-value {
    color: var(--bleu);
    font-size: 25px;
    font-weight: 700;
    letter-spacing: -0.4px;
    margin: 0;
}

.stat-note {
    color: var(--gris);
    font-size: 12px;
    margin: 5px 0 0;
}

.stat-card h3 {
    color: var(--bleu);
    font-size: 16px;
    margin: 8px 0;
}

.admin-action-link {
    display: inline-block;
    margin-top: 8px;
    background: var(--bleu);
    color: #F1D18A;
    padding: 9px 12px;
    border: 1px solid var(--bleu);
    border-radius: 5px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.admin-action-link:hover {
    background: var(--blanc);
    color: var(--bleu);
    border-color: var(--dore);
}

.charts-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
    gap: 15px;
    margin-top: 16px;
}

.panel {
    background: var(--blanc);
    border: 1px solid var(--bordure);
    border-radius: 8px;
    padding: 17px;
    min-width: 0;
    box-shadow: 0 3px 10px rgba(20, 36, 58, 0.035);
}

.panel h3 {
    color: var(--bleu);
    font-size: 15px;
    margin: 0 0 15px;
}

.chart-container {
    position: relative;
    height: 255px;
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: var(--blanc);
    font-size: 13px;
}

th, td {
    padding: 11px 13px;
    text-align: left;
    border-bottom: 1px solid var(--bordure);
    white-space: nowrap;
}

th {
    background: var(--bleu);
    color: white;
    font-weight: 600;
    letter-spacing: 0.2px;
}

tbody tr:hover {
    background: #F8F3E9;
}

.amount {
    font-weight: 700;
    color: var(--bleu);
}

.operations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 13px;
    margin-top: 16px;
}

footer {
    text-align: center;
    padding: 20px;
    color: var(--gris);
    font-size: 12px;
}

@media (max-width: 760px) {
    main {
        padding: 0 14px;
    }

    .charts-grid {
        grid-template-columns: 1fr;
    }

    .chart-container {
        height: 230px;
    }

    header h1 {
        font-size: 22px;
    }

    .section-title {
        font-size: 18px;
    }
}

.operation-badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.badge-depot {
    background: #E3EFE5;
    color: #28613A;
}

.badge-retrait {
    background: #F8E5E2;
    color: #963D35;
}

.badge-virement-envoye {
    background: #E2EAF5;
    color: #244B7A;
}

.badge-virement-recu {
    background: #F5EBD4;
    color: #806021;
}

    </style>
</head>

<body>

<header>
    <div>
        <h1>BANK-SIM</h1>
        <p>Espace administrateur</p>
    </div>

    <a class="logout" href="logout.php">Déconnexion</a>
</header>

<main>

    <section class="welcome-panel">
        <h2>Tableau de bord</h2>
        <p>Bienvenue dans l'espace administrateur de BANK-SIM.</p>
        <p>Consultez les statistiques et accédez aux outils de gestion de la banque.</p>
    </section>

    <h2 class="section-title">Gestion de la banque</h2>

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-label">Administration</div>
            <h3>Gestion des clients</h3>
            <p>Consulter, ajouter, modifier et supprimer des clients.</p>
            <a class="admin-action-link" href="clients.php">Gérer les clients →</a>
        </article>

        <article class="stat-card">
            <div class="stat-label">Accès à la plateforme</div>
            <h3>Gestion des utilisateurs</h3>
            <p>Accéder aux comptes utilisateurs et à leur gestion.</p>
            <a class="admin-action-link" href="utilisateurs.php">Gérer les utilisateurs →</a>
        </article>
    </section>

    <h2 class="section-title">Vue d'ensemble</h2>

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-label">Clients enregistrés</div>
            <p class="stat-value"><?= e($nombreClients) ?></p>
        </article>

        <article class="stat-card">
            <div class="stat-label">Utilisateurs</div>
            <p class="stat-value"><?= e($nombreUtilisateurs) ?></p>
            <p class="stat-note"><?= e($utilisateursActifs) ?> actifs</p>
        </article>

        <article class="stat-card">
            <div class="stat-label">Conseillers</div>
            <p class="stat-value"><?= e($nombreConseillers) ?></p>
        </article>

        <article class="stat-card">
            <div class="stat-label">Comptes bancaires</div>
            <p class="stat-value"><?= e($nombreComptes) ?></p>
            <p class="stat-note"><?= e($comptesActifs) ?> actifs</p>
        </article>

        <article class="stat-card">
            <div class="stat-label">Solde total des comptes</div>
            <p class="stat-value"><?= number_format($soldeTotal, 2, ",", " ") ?></p>
            <p class="stat-note">MAD</p>
        </article>
    </section>

    <h2 class="section-title">Activité de la banque</h2>

    <section class="charts-grid">
        <article class="panel">
            <h3>Opérations des 7 derniers jours</h3>
            <div class="chart-container">
                <canvas id="operationsChart"></canvas>
            </div>
        </article>

        <article class="panel">
            <h3>Répartition des utilisateurs</h3>
            <div class="chart-container">
                <canvas id="rolesChart"></canvas>
            </div>
        </article>
    </section>

    <h2 class="section-title">Résumé des opérations</h2>

    <section class="operations-grid">
        <?php foreach ($operations as $operation): ?>
            <article class="stat-card">
                <div class="stat-label"><?= e($operation["nom"]) ?></div>
                <p class="stat-value"><?= e($operation["nombre"]) ?></p>
                <p class="stat-note">
                    Montant cumulé :
                    <?= number_format($operation["montant"], 2, ",", " ") ?> MAD
                </p>
            </article>
        <?php endforeach; ?>
    </section>

    <h2 class="section-title">Transactions récentes</h2>

    <section class="panel table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Montant</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($dernieresTransactions)): ?>
                    <tr>
                        <td colspan="5">Aucune transaction enregistrée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dernieresTransactions as $transaction): ?>
                        <tr>
                            <td><?= e($transaction["ID"]) ?></td>
                            <td><?= e($transaction["NOM"] ?? "Client inconnu") ?></td>
                            
<td>
    <?php
    $type = $transaction["TYPE"];

    $badges = [
        "DEPOT" => ["Dépôt", "badge-depot"],
        "RETRAIT" => ["Retrait", "badge-retrait"],
        "VIREMENT_ENVOYE" => ["Virement envoyé", "badge-virement-envoye"],
        "VIREMENT_RECU" => ["Virement reçu", "badge-virement-recu"]
    ];

    $libelle = $badges[$type][0] ?? $type;
    $classe = $badges[$type][1] ?? "";
    ?>
    <span class="operation-badge <?= e($classe) ?>">
        <?= e($libelle) ?>
    </span>
</td>

                            <td class="amount">
                                <?= number_format((float)$transaction["MONTANT"], 2, ",", " ") ?> MAD
                            </td>
                            <td><?= e($transaction["DATE_TRANSACTION"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</main>

<footer>
    BANK-SIM — Administration bancaire
</footer>

<script>
    const labels = <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const depots = <?= json_encode($depots) ?>;
    const retraits = <?= json_encode($retraits) ?>;
    const virements = <?= json_encode($virements) ?>;

    new Chart(document.getElementById("operationsChart"), {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Dépôts",
                    data: depots,
                    borderColor: "#C7A45D",
                    backgroundColor: "#C7A45D22",
                    tension: 0.3
                },
                {
                    label: "Retraits",
                    data: retraits,
                    borderColor: "#263E5C",
                    backgroundColor: "#263E5C22",
                    tension: 0.3
                },
                {
                    label: "Écritures de virements",
                    data: virements,
                    borderColor: "#6B8E83",
                    backgroundColor: "#6B8E8322",
                    tension: 0.3
                }
            ]
        },
        
options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        intersect: false,
        mode: "index"
    },
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                usePointStyle: true,
                pointStyle: "circle",
                padding: 18,
                color: "#14243A",
                font: {
                    size: 12
                }
            }
        },
        tooltip: {
            backgroundColor: "#14243A",
            titleColor: "#F1D18A",
            bodyColor: "#FFFFFF",
            padding: 12,
            cornerRadius: 7,
            displayColors: true
        }
    },
    scales: {
        x: {
            grid: {
                display: false
            },
            ticks: {
                color: "#777F89"
            },
            border: {
                display: false
            }
        },
        y: {
            beginAtZero: true,
            ticks: {
                precision: 0,
                color: "#777F89"
            },
            grid: {
                color: "#EAE3D7"
            },
            border: {
                display: false
            }
        }
    }
}

    });

    const labelsRoles = <?= json_encode($labelsRoles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const valeursRoles = <?= json_encode($valeursRoles) ?>;

    new Chart(document.getElementById("rolesChart"), {
        type: "doughnut",
        data: {
            labels: labelsRoles,
            datasets: [{
                data: valeursRoles,
                backgroundColor: ["#14243A", "#C7A45D", "#6B8E83", "#A6B5C5"]
            }]
        },
        
options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: "68%",
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                usePointStyle: true,
                pointStyle: "circle",
                padding: 18,
                color: "#14243A",
                font: {
                    size: 12
                }
            }
        },
        tooltip: {
            backgroundColor: "#14243A",
            titleColor: "#F1D18A",
            bodyColor: "#FFFFFF",
            padding: 12,
            cornerRadius: 7
        }
    }
}

    });
</script>

</body>
</html>
