<?php
session_start();

// Vérification stricte du rôle admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit("Accès réservé aux administrateurs");
}

// Connexion à la base de données
require_once 'db_config.php';

try {
    // Récupération des statistiques par travailleur avec calcul des heures supplémentaires
    $sql = "SELECT 
                worker_name, 
                worker_code,
                COUNT(*) as nb_pointages,
                SEC_TO_TIME(SUM(TIME_TO_SEC(duration))) as total_heures,
                SEC_TO_TIME(SUM(
                    CASE 
                        WHEN TIME_TO_SEC(duration) > 28800 THEN TIME_TO_SEC(duration) - 28800 
                        ELSE 0 
                    END
                )) as heures_supplementaires,
                SUM(retard = 'En retard') as nb_retards
            FROM pointage 
            GROUP BY worker_name, worker_code
            ORDER BY worker_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $travailleurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcul des totaux globaux
    $sql_total = "SELECT 
                    SEC_TO_TIME(SUM(TIME_TO_SEC(duration))) as total_hours,
                    SEC_TO_TIME(SUM(
                        CASE 
                            WHEN TIME_TO_SEC(duration) > 28800 THEN TIME_TO_SEC(duration) - 28800 
                            ELSE 0 
                        END
                    )) as total_heures_supp,
                    COUNT(*) as total_pointages
                  FROM pointage";
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute();
    $total = $stmt_total->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur base de données: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Pointages - Admin</title>
    <style>
        :root {
            --primary: #5d5fef;
            --primary-light: #e0e1ff;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --text: #2d3748;
            --text-light: #718096;
            --bg: #f7fafc;
        }
        
            
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background-color: var(--bg);
            padding: 2rem;
            margin: 0 auto;
        }
        
        h1 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 1.8rem;
            letter-spacing: -0.025em;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            flex: 1;
            min-width: 200px;
            margin: 0.5rem;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 500;
        }
        
        .stat-card p {
            margin-bottom: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #edf2f7;
        }
        
        th {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        
        tr:hover {
            background-color: #f8fafc;
        }
        
        .retard-count {
            color: var(--error);
            font-weight: 500;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        
        .btn:hover {
            background-color: #4b4ddf;
            transform: translateY(-1px);
        }
        
        .details-btn {
            background-color: var(--primary-light);
            color: var(--primary);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
            text-decoration: none;
        }
        
        .details-btn:hover {
            background-color: #d0d1ff;
        }
        
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            
            .stats {
                flex-direction: column;
            }
            
            .stat-card {
                width: 100%;
                margin: 0.5rem 0;
            }
        }/* [Le reste du CSS reste inchangé jusqu'à table] */
        
        .heures-supp {
            color: var(--warning);
            font-weight: 500;
        }
        
        /* [Le reste du CSS existant] */
    </style>
</head>
<body>
    <div class="container">
        <h1>Liste des Pointages</h1>
        
        <a href="choix_interface.php" class="btn">Retour au menu</a>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Nombre total de pointages</h3>
                <p><?php echo $total['total_pointages']; ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Heures travaillées totales</h3>
                <p><?php echo $total['total_hours'] ?? '00:00:00'; ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Heures supplémentaires totales</h3>
                <p><?php echo $total['total_heures_supp'] ?? '00:00:00'; ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Code</th>
                    <th>Pointages</th>
                    <th>Retards</th>
                    <th>Heures totales</th>
                    <th>Heures supplémentaires</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($travailleurs as $travailleur): ?>
                <tr>
                    <td><?php echo htmlspecialchars($travailleur['worker_name']); ?></td>
                    <td><?php echo htmlspecialchars($travailleur['worker_code']); ?></td>
                    <td><?php echo htmlspecialchars($travailleur['nb_pointages']); ?></td>
                    <td class="<?php echo $travailleur['nb_retards'] > 0 ? 'retard-count' : ''; ?>">
                        <?php echo htmlspecialchars($travailleur['nb_retards']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($travailleur['total_heures']); ?></td>
                    <td class="heures-supp">
                        <?php echo htmlspecialchars($travailleur['heures_supplementaires'] ?? '00:00:00'); ?>
                    </td>
                    <td>
                        <a href="details.php?worker_code=<?php echo urlencode($travailleur['worker_code']); ?>" class="details-btn">
                            Détails
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>