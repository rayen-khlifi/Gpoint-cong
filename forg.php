<?php
ini_set('display_errors', 1);
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
    $ancien_mdp = $_POST['nb'] ?? '';
    $nouveau_mdp = $_POST['mdp'] ?? '';
    $conf_mdp = $_POST['rmdp'] ?? '';

    if (empty($nom) || empty($ancien_mdp) || empty($nouveau_mdp) || empty($conf_mdp)) {
        $message = "❌ Tous les champs sont requis.";
    } elseif ($nouveau_mdp !== $conf_mdp) {
        $message = "⚠️ Les mots de passe ne correspondent pas.";
    } else {
        
        $stmt = $pdo->prepare("SELECT id, mot_de_passe FROM users WHERE nom = ?");
        $stmt->execute([$nom]);
        $user = $stmt->fetch();

        if ($user && password_verify($ancien_mdp, $user['mot_de_passe'])) {
        
            $hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET mot_de_passe = ? WHERE id = ?");
            if ($update->execute([$hash, $user['id']])) {
                $message = "✅ Mot de passe modifié avec succès.";
            } else {
                $message = "❌ Échec de la mise à jour.";
            }
        } 
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation du mot de passe</title>
    <link rel="stylesheet" href="forg.css">
</head>
<body>
<div class="cont">
    <h2>Réinitialisation du mot de passe</h2>
    <?php if (!empty($message)) echo "<p style='color:red;'>$message</p>"; ?>
    <form id="resetForm" method="post" action="forg.php">
        <label for="username">Nom :</label>
        <input type="text" id="username" name="username" placeholder="Nom d'utilisateur" required>

        <label for="nb">Ancien mot de passe :</label>
        <input type="password" id="nb" name="nb" placeholder="Votre ancien mot de passe" required>

        <label for="mdp">Nouveau mot de passe :</label>
        <input type="password" id="mdp" name="mdp" placeholder="Nouveau mot de passe" required>

        <label for="rmdp">Répéter le nouveau mot de passe :</label>
        <input type="password" id="rmdp" name="rmdp" placeholder="Répétez votre mot de passe" required>

        <button type="submit">Envoyer</button>
    </form>
</div>
</body>
</html>