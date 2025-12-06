<?php
session_start();

// 🔐 Protection : rediriger si pas connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: S1.php");
    exit();
}

// Récupération du rôle
$role = $_SESSION['role'] ?? 'user'; // Par défaut, user

// Connexion à la base de données
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

// Traitement du formulaire
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $workerName   = trim($_POST['workerName'] ?? '');
    $workerCode   = trim($_POST['workerCode'] ?? '');
    $checkInTime  = $_POST['checkInTime'] ?? '';
    $checkOutTime = $_POST['checkOutTime'] ?? '';

    if ($workerName === '' || $workerCode === '' || $checkInTime === '' || $checkOutTime === '') {
        $message = '<div class="alert error">Tous les champs sont obligatoires.</div>';
    } else {
        $timeIn  = new DateTime($checkInTime);
        $timeOut = new DateTime($checkOutTime);
        $seuil   = new DateTime("07:00");

        if ($timeOut <= $timeIn) {
            $message = '<div class="alert error">L\'heure de départ doit être après l\'heure d\'arrivée.</div>';
        } else {
            $duration = $timeIn->diff($timeOut)->format('%H:%I:%S');
            $retard = "À l'heure";
            $tempsRetard = "00:00:00";

            if ($timeIn > $seuil) {
                $retard = "En retard";
                $tempsRetard = $seuil->diff($timeIn)->format('%H:%I:%S');
            }

            // Insertion
            $sql = "INSERT INTO pointage (worker_name, worker_code, check_in, check_out, duration, retard)
                    VALUES (:worker_name, :worker_code, :check_in, :check_out, :duration, :retard)";
            $stmt = $pdo->prepare($sql);

            try {
                $stmt->execute([
                    ':worker_name' => $workerName,
                    ':worker_code' => $workerCode,
                    ':check_in'    => $checkInTime,
                    ':check_out'   => $checkOutTime,
                    ':duration'    => $duration,
                    ':retard'      => $retard
                ]);

                if ($role === 'admin') {
                    $message = '<div class="alert success">✅ Pointage enregistré avec succès !</div>';
                } else {
                    $message = '<div class="alert success">✅ Pointage enregistré !<br>🕒 Durée travaillée : <strong>' . htmlspecialchars($duration) . '</strong></div>';
                }
            } catch (PDOException $e) {
                $message = '<div class="alert error">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Système de Pointage</title>
    <style>
        :root {
            --primary: #5d5fef;
            --success: #10b981;
            --error: #ef4444;
            --bg: #f7fafc;
            --text: #2d3748;
            --text-light: #718096;
            --warning: #f59e0b;
        }
        body {
            background: var(--bg);
            font-family: sans-serif;
            color: var(--text);
            padding: 2rem;
            max-width: 600px;
            margin: auto;
        }
        h1 {
            text-align: center;
            color: var(--primary);
            font-size: 1.8rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 0.375rem;
        }
        button, .btn {
            width: 100%;
            margin-top: 1rem;
            padding: 0.75rem;
            border: none;
            border-radius: 0.375rem;
            background: var(--primary);
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        button:hover, .btn:hover {
            opacity: 0.9;
        }
        .btn-list {
            background: #6c757d;
        }
        .btn-list:hover {
            background: #5a6268;
        }
        .btn-conge {
            background: var(--warning);
        }
        .btn-conge:hover {
            background: #e69009;
        }
        .alert {
            padding: 1rem;
            border-left: 5px solid;
            margin-bottom: 1.25rem;
            border-radius: 0.375rem;
        }
        .success {
            background: #f0fdf4;
            border-color: var(--success);
            color: var(--success);
        }
        .error {
            background: #fef2f2;
            border-color: var(--error);
            color: var(--error);
        }
        .btn-container {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
    </style>
</head>
<body>

    <h1>🕒 Système de Pointage</h1>

    <?= $message ?>

    <form method="post" action="">
        <div class="form-group">
            <label for="workerName">Nom du travailleur</label>
            <input type="text" id="workerName" name="workerName" required value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="workerCode">Code du travailleur</label>
            <input type="text" id="workerCode" name="workerCode" required value="<?= htmlspecialchars($_SESSION['user_code'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="checkInTime">Heure d'arrivée</label>
            <input type="time" id="checkInTime" name="checkInTime" required>
        </div>

        <div class="form-group">
            <label for="checkOutTime">Heure de départ</label>
            <input type="time" id="checkOutTime" name="checkOutTime" required>
        </div>

        <button type="submit">Enregistrer le pointage</button>
    </form>

    <div class="btn-container">
        <!-- Bouton visible pour tous les utilisateurs -->
        <a href="conge.php" class="btn btn-conge">📅 Demander un congé</a>
        
        <?php if ($role === 'admin'): ?>
            <a href="list.php" class="btn btn-list">📋 Voir la liste des pointages</a>
            <a href="conge.php" class="btn btn-list">👨‍💼 Gérer les congés (Admin)</a>
        <?php endif; ?>
    </div>
</body>
</html>