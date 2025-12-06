<?php
session_start();

// Vérification stricte du rôle admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit("Accès réservé aux administrateurs");
}

// Connexion à la base de données
require_once 'db_config.php';

// Validation et sécurisation du worker_code
$worker_code = isset($_GET['worker_code']) ? (int)$_GET['worker_code'] : 0;
if ($worker_code <= 0) {
    die("Code travailleur invalide");
}

// Requête pour vérifier l'existence du travailleur
$sql_worker = "SELECT DISTINCT worker_name FROM pointage WHERE worker_code = ? LIMIT 1";
$stmt_worker = $pdo->prepare($sql_worker);
$stmt_worker->execute([$worker_code]);
$travailleur = $stmt_worker->fetch(PDO::FETCH_ASSOC);

if (!$travailleur) {
    die("Travailleur non trouvé");
}

// Requête pour les pointages avec heures supplémentaires
$sql_pointages = "SELECT 
                    check_in,
                    check_out,
                    duration,
                    retard,
                    CASE 
                        WHEN TIME_TO_SEC(duration) > 28800 
                        THEN SEC_TO_TIME(TIME_TO_SEC(duration) - 28800)
                        ELSE '00:00:00'
                    END AS heures_supp
                  FROM pointage 
                  WHERE worker_code = ?
                  ORDER BY check_in DESC";
$stmt_pointages = $pdo->prepare($sql_pointages);
$stmt_pointages->execute([$worker_code]);
$pointages = $stmt_pointages->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails des Pointages</title>
    <style>
        :root {
            --primary: #5d5fef;
            --primary-light: #e0e1ff;
            --success: #10b981;
            --error: #ef4444;
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
        
        .heures-supp {
            color: #f59e0b;
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
        
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Détails des pointages pour <?php echo htmlspecialchars($travailleur['worker_name']); ?></h1>
        
        <a href="list.php" class="btn">Retour à la liste</a>
        
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Arrivée</th>
                    <th>Départ</th>
                    <th>Durée</th>
                    <th>Heures supp.</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pointages as $pointage): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($pointage['check_in'])); ?></td>
                    <td><?php echo date('H:i', strtotime($pointage['check_in'])); ?></td>
                    <td><?php echo date('H:i', strtotime($pointage['check_out'])); ?></td>
                    <td><?php echo htmlspecialchars($pointage['duration']); ?></td>
                    <td class="heures-supp"><?php echo htmlspecialchars($pointage['heures_supp']); ?></td>
                    <td class="<?php echo $pointage['retard'] == 'En retard' ? 'retard-count' : ''; ?>">
                        <?php echo htmlspecialchars($pointage['retard']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>