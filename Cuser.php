<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: Luser.php");
    exit();
}

// Vérification supplémentaire pour éviter les doublons
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $worker_code = (int)$_POST['worker_code'];
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        // Vérifier d'abord si le worker_code existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE worker_code = ?");
        $stmt->execute([$worker_code]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Ce code travailleur existe déjà";
        } else {
            // Insérer le nouvel utilisateur
            $stmt = $pdo->prepare("INSERT INTO users (worker_code, username, password, role) VALUES (?, ?, ?, 'worker')");
            $stmt->execute([$worker_code, $username, $password]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Compte créé avec succès'];
            header("Location: Guser.php");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Erreur: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Création Utilisateur</title>
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Créer un compte</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Code travailleur (unique):</label>
                <input type="number" name="worker_code" required min="1">
            </div>
            <div class="form-group">
                <label>Nom d'utilisateur:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Mot de passe:</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <button type="submit" class="btn">Créer</button>
            <a href="Guser.php" class="btn">Annuler</a>
        </form>
    </div>
</body>
</html>