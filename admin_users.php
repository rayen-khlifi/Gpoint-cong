<?php
session_start();

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['admin'])) {
    header('Location: Guser.php');
    exit();
}

require_once 'db_config.php';

$message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        // Création d'un nouvel utilisateur
        $worker_code = trim($_POST['worker_code']);
        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $mot_de_passe = trim($_POST['mot_de_passe']);
        $role = trim($_POST['role']);

        // Validation des données
        if (empty($worker_code) || empty($nom) || empty($email) || empty($mot_de_passe)) {
            $message = '<div class="alert alert-danger">Tous les champs sont obligatoires</div>';
        } else {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $message = '<div class="alert alert-danger">Cet email est déjà utilisé</div>';
            } else {
                // Hash du mot de passe
                $password_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                
                // Insertion dans la base de données
                $stmt = $pdo->prepare("INSERT INTO users (worker_code, nom, email, mot_de_passe, date_inscription, role) 
                                      VALUES (?, ?, ?, ?, NOW(), ?)");
                try {
                    $stmt->execute([$worker_code, $nom, $email, $password_hash, $role]);
                    $message = '<div class="alert alert-success">Utilisateur créé avec succès</div>';
                } catch (PDOException $e) {
                    $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
                }
            }
        }
    } elseif (isset($_POST['edit_user'])) {
        // Modification d'un utilisateur existant
        $id = $_POST['user_id'];
        $worker_code = trim($_POST['worker_code']);
        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);

        // Vérifier si le mot de passe est modifié
        $update_password = !empty($_POST['mot_de_passe']);
        
        // Préparation de la requête
        if ($update_password) {
            $password_hash = password_hash(trim($_POST['mot_de_passe']), PASSWORD_DEFAULT);
            $sql = "UPDATE users SET worker_code = ?, nom = ?, email = ?, mot_de_passe = ?, role = ? WHERE id = ?";
            $params = [$worker_code, $nom, $email, $password_hash, $role, $id];
        } else {
            $sql = "UPDATE users SET worker_code = ?, nom = ?, email = ?, role = ? WHERE id = ?";
            $params = [$worker_code, $nom, $email, $role, $id];
        }

        // Exécution
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $message = '<div class="alert alert-success">Utilisateur mis à jour avec succès</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
        }
    }
}

// Suppression d'un utilisateur
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success">Utilisateur supprimé avec succès</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
    }
}

// Récupération de tous les utilisateurs
$users = $pdo->query("SELECT * FROM users ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4">Gestion des Utilisateurs</h1>
    
    <?= $message ?>
    
    <div class="form-container">
        <h2><?= isset($_GET['edit']) ? 'Modifier' : 'Créer' ?> un utilisateur</h2>
        
        <?php
        $editing_user = null;
        if (isset($_GET['edit'])) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_GET['edit']]);
            $editing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        
        <form method="POST">
            <?php if ($editing_user): ?>
                <input type="hidden" name="user_id" value="<?= $editing_user['id'] ?>">
            <?php endif; ?>
            
            <div class="mb-3">
                <label class="form-label">Code travailleur</label>
                <input type="text" name="worker_code" class="form-control" 
                       value="<?= $editing_user ? htmlspecialchars($editing_user['worker_code']) : '' ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Nom complet</label>
                <input type="text" name="nom" class="form-control" 
                       value="<?= $editing_user ? htmlspecialchars($editing_user['nom']) : '' ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= $editing_user ? htmlspecialchars($editing_user['email']) : '' ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="mot_de_passe" class="form-control" 
                       placeholder="<?= $editing_user ? 'Laisser vide pour ne pas changer' : '' ?>" 
                       <?= !$editing_user ? 'required' : '' ?>>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Rôle</label>
                <select name="role" class="form-control" required>
                    <option value="user" <?= $editing_user && $editing_user['role'] === 'user' ? 'selected' : '' ?>>Utilisateur</option>
                    <option value="admin" <?= $editing_user && $editing_user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                </select>
            </div>
            
            <button type="submit" name="<?= $editing_user ? 'edit_user' : 'create_user' ?>" class="btn btn-primary">
                <?= $editing_user ? 'Mettre à jour' : 'Créer' ?>
            </button>
            
            <?php if ($editing_user): ?>
                <a href="admin_users.php" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-container">
        <h2>Liste des utilisateurs</h2>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Date d'inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['worker_code']) ?></td>
                    <td><?= htmlspecialchars($user['nom']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= htmlspecialchars($user['date_inscription']) ?></td>
                    <td>
                        <a href="admin_users.php?edit=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="admin_users.php?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" 
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        <a href="choix_interface.php" class="btn btn-secondary">Retour</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>