<?php
session_start();  // Démarrage de session

// --- Connexion PDO ---
$host   = "localhost";
$dbname = "site_web";
$user   = "root";
$pass   = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// --- Traitement du formulaire ---
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email        = trim($_POST['email'] ?? '');
    $mot_de_passe = trim($_POST['password'] ?? '');

    if ($email !== '' && $mot_de_passe !== '') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email']   = $user['email'];

            // Détection du rôle : admin si email et mot de passe connus
            if (strtolower($user['email']) === 'kbe@gmail.com' && $mot_de_passe === 'kbe123') {
                $_SESSION['role'] = 'admin';
            } else {
                // Sécurité : si pas de champ `role`, on force 'user'
                $_SESSION['role'] = $user['role'] ?? 'user';
            }

            header("Location: choix_interface.php");
            exit();
        } else {
            $message = "❌ Email ou mot de passe incorrect.";
        }
    } else {
        $message = "❌ Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="S1.css">
</head>
<body>
<header>
    <div class="cercle-image">
        <img src="download.jpg" alt="Logo KBE">
    </div>

    <div class="logina">
        <h2>Login</h2>

        <?php if ($message !== ''): ?>
            <p style="color:red;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="S1.php">
            <div class="box">
                <span class="icon"><ion-icon name="mail"></ion-icon></span>
                <input type="email" name="email" required placeholder="Email">
                <label>User</label>
            </div>

            <div class="box">
                <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                <input type="password" name="password" required placeholder="Mot de passe">
                <label>Password</label>
            </div>

            <input type="checkbox" id="remember">
            <label for="remember">Remember me</label>

            <a href="forg.php" target="_blank" id="forg">Forget password</a><br><br>

            <button id="bt" type="submit">LOGIN</button>

            <br><br>
            <h4>Don't have an account?</h4>
            <a href="sign.php">SIGN UP</a>
        </form>

        <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
        <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    </div>
</header>
</body>
</html>
