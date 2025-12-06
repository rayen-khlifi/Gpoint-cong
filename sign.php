<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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


$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $mot_de_passe = $_POST['password'] ?? '';
    $confirm = $_POST['confirmPassword'] ?? '';

    if ($mot_de_passe !== $confirm) {
        $message = "⚠️ Les mots de passe ne correspondent pas.";
    } elseif (!empty($nom) && !empty($email) && !empty($mot_de_passe)) {
        
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $message = "⚠️ Cet email est déjà utilisé.";
        } else {
            
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            
            $stmt = $pdo->prepare("INSERT INTO users (nom, email, mot_de_passe) VALUES (?, ?, ?)");
            if ($stmt->execute([$nom, $email, $hash])) {
                $message = "✅ Inscription réussie.";
            } else {
                $message = "❌ Erreur lors de l'inscription.";
            }
        }
    } else {
        $message = "❌ Tous les champs sont requis.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Créer un compte</title>
    <link rel="stylesheet" href="sign.css">
</head>
<body>
<div class="signup-container">
    <h2>Créer un compte</h2>
    <form id="signupForm" action="sign.php" method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required />
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Mot de passe" required />
        <input type="password" name="confirmPassword" placeholder="Confirmer le mot de passe" required />
        <div class="error" id="errorMsg" style="color:red;"><?php echo $message; ?></div>
        <button type="submit">S'inscrire</button>
    </form>
</div>
</body>
</html>