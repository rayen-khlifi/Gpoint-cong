<?php
$host = "localhost";
$dbname = "site_web";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupérer tous les travailleurs avec leurs stats
$sql = "SELECT 
            worker_name,
            worker_code,
            COUNT(*) as total_pointages,
            SUM(CASE WHEN retard = 'À l\'heure' THEN 1 ELSE 0 END) as on_time,
            SUM(CASE WHEN retard = 'En retard' THEN 1 ELSE 0 END) as late,
            SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(check_in, '07:00:00')))) as avg_delay
        FROM pointage
        GROUP BY worker_name, worker_code
        ORDER BY late DESC, avg_delay DESC";

$workers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques globales
$totalWorkers = count($workers);
$totalLate = array_sum(array_column($workers, 'late'));
$totalOnTime = array_sum(array_column($workers, 'on_time'));
$totalPointages = array_sum(array_column($workers, 'total_pointages'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Personnelles</title>
    <style>
        :root {
            --primary: #4361ee;
            --success: #4cc9f0;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: var(--gray);
            font-size: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .workers-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .workers-section {
                grid-template-columns: 1fr;
            }
        }
        
        .worker-list {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .worker-list h2 {
            color: var(--primary);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            color: var(--gray);
            font-weight: 500;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #e6fcf5;
            color: #0ca678;
        }
        
        .badge-warning {
            background-color: #fff0f3;
            color: #f03e3e;
        }
        
        .progress-container {
            width: 100%;
            background-color: #e9ecef;
            border-radius: 4px;
            margin: 5px 0;
            height: 8px;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 4px;
        }
        
        .bg-success {
            background-color: var(--success);
        }
        
        .bg-warning {
            background-color: var(--warning);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Statistiques Personnelles des Travailleurs</h1>
        
        <div class="stats-summary">
            <div class="stat-card">
                <h3>Travailleurs enregistrés</h3>
                <div class="stat-value"><?= $totalWorkers ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Pointages totaux</h3>
                <div class="stat-value"><?= $totalPointages ?></div>
            </div>
            
            <div class="stat-card">
                <h3>À l'heure</h3>
                <div class="stat-value"><?= $totalOnTime ?></div>
                <div class="progress-container">
                    <div class="progress-bar bg-success" style="width: <?= $totalPointages ? ($totalOnTime/$totalPointages)*100 : 0 ?>%"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <h3>En retard</h3>
                <div class="stat-value"><?= $totalLate ?></div>
                <div class="progress-container">
                    <div class="progress-bar bg-warning" style="width: <?= $totalPointages ? ($totalLate/$totalPointages)*100 : 0 ?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="workers-section">
            <div class="worker-list">
                <h2>🏆 Travailleurs ponctuels</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Code</th>
                            <th>Pointages</th>
                            <th>Taux ponctualité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $onTimeWorkers = array_filter($workers, function($w) { return $w['on_time'] > 0; });
                        usort($onTimeWorkers, function($a, $b) { 
                            return ($b['on_time']/$b['total_pointages']) <=> ($a['on_time']/$a['total_pointages']); 
                        });
                        
                        foreach(array_slice($onTimeWorkers, 0, 10) as $worker): 
                            $rate = round(($worker['on_time']/$worker['total_pointages'])*100);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($worker['worker_name']) ?></td>
                            <td><?= htmlspecialchars($worker['worker_code']) ?></td>
                            <td><?= $worker['total_pointages'] ?></td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-bar bg-success" style="width: <?= $rate ?>%"></div>
                                </div>
                                <span class="badge badge-success"><?= $rate ?>%</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="worker-list">
                <h2>⚠️ Travailleurs en retard</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Code</th>
                            <th>Retards</th>
                            <th>Retard moyen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $lateWorkers = array_filter($workers, function($w) { return $w['late'] > 0; });
                        usort($lateWorkers, function($a, $b) { 
                            return ($b['late']/$b['total_pointages']) <=> ($a['late']/$a['total_pointages']); 
                        });
                        
                        foreach(array_slice($lateWorkers, 0, 10) as $worker): 
                            $rate = round(($worker['late']/$worker['total_pointages'])*100);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($worker['worker_name']) ?></td>
                            <td><?= htmlspecialchars($worker['worker_code']) ?></td>
                            <td>
                                <span class="badge badge-warning"><?= $worker['late'] ?> / <?= $worker['total_pointages'] ?></span>
                            </td>
                            <td><?= $worker['avg_delay'] ? substr($worker['avg_delay'], 0, 8) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>