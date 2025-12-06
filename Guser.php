<?php
session_start();
require_once 'db_config.php';

// Génération du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérification des droits admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: S1.php");
    exit();
}

$message = "";

// Traitement de la création d'utilisateur
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_user'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "❌ Erreur de sécurité. Veuillez réessayer.";
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $worker_code = trim($_POST['worker_code'] ?? '');
        $role = trim($_POST['role'] ?? 'user');

        // Validation
        $errors = [];
        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
        if (empty($password) || strlen($password) < 8) $errors[] = "Le mot de passe doit faire au moins 8 caractères";
        if (empty($worker_code) || !preg_match('/^[A-Z0-9]{4,10}$/', $worker_code)) {
            $errors[] = "Code travailleur invalide (4-10 caractères alphanumériques majuscules)";
        }

        if (empty($errors)) {
            try {
                // Vérifier si l'email ou le code existe déjà
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR worker_code = ?");
                $stmt->execute([$email, $worker_code]);
                if ($stmt->fetch()) {
                    $message = "❌ Cet email ou code travailleur est déjà utilisé.";
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (worker_code, nom, email, mot_de_passe, date_inscription, role) 
                                         VALUES (?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute([$worker_code, $nom, $email, $hashed, $role]);
                    $message = "✅ Utilisateur créé avec succès!";
                }
            } catch (PDOException $e) {
                error_log("Erreur création utilisateur: " . $e->getMessage());
                $message = "❌ Erreur lors de la création de l'utilisateur.";
            }
        } else {
            $message = "❌ " . implode("<br>❌ ", $errors);
        }
    }
}

// Traitement de la suppression d'utilisateur
if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    if ($id !== $_SESSION['user_id']) { // Empêche l'auto-suppression
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = "✅ Utilisateur supprimé avec succès.";
        } catch (PDOException $e) {
            error_log("Erreur suppression utilisateur: " . $e->getMessage());
            $message = "❌ Erreur lors de la suppression de l'utilisateur.";
        }
    } else {
        $message = "❌ Vous ne pouvez pas supprimer votre propre compte.";
    }
}

// Récupération de la liste des utilisateurs
try {
    $stmt = $pdo->query("SELECT id, worker_code, nom, email, role, date_inscription FROM users ORDER BY date_inscription DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération utilisateurs: " . $e->getMessage());
    $users = [];
    $message = "❌ Erreur lors du chargement des utilisateurs.";
}

// Déconnexion
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: S1.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .badge-admin {
            background-color: #dc3545;
        }
        .badge-manager {
            background-color: #fd7e14;
        }
        .badge-user {
            background-color: #0d6efd;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="bi bi-people-fill"></i> Gestion des Utilisateurs
            </h2>
            <div>
                <a href="choix_interface.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <a href="?logout=1" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="notification <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= nl2br(htmlspecialchars($message)) ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-person-plus"></i> Créer un nouvel utilisateur</h4>
            </div>
            <div class="card-body">
                <form method="POST" id="createUserForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="create_user" value="1">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="worker_code" class="form-label">Code travailleur</label>
                            <input type="text" class="form-control" id="worker_code" name="worker_code" 
                                   pattern="[A-Z0-9]{4,10}" title="4-10 caractères alphanumériques majuscules" required>
                            <small class="text-muted">Exemple: EMP1234</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   minlength="8" required>
                            <small class="text-muted">Minimum 8 caractères</small>
                        </div>
                        
                       
                        
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-people"></i> Liste des utilisateurs</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Date d'inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Aucun utilisateur trouvé</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['id']) ?></td>
                                        <td><?= htmlspecialchars($user['worker_code']) ?></td>
                                        <td><?= htmlspecialchars($user['nom']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="badge <?= 
                                                $user['role'] === 'admin' ? 'badge-admin' : 
                                                ($user['role'] === 'manager' ? 'badge-manager' : 'badge-user') 
                                            ?>">
                                                <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($user['date_inscription'])) ?></td>
                                        <td class="action-buttons">
                                            <a href="modif.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-sm btn-primary"
                                               title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?delete_user=<?= $user['id'] ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')"
                                               title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation côté client pour le formulaire
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const workerCode = document.getElementById('worker_code').value;
            
            if (password.length < 8) {
                alert("Le mot de passe doit contenir au moins 8 caractères");
                e.preventDefault();
                return false;
            }
            
            if (!/^[A-Z0-9]{4,10}$/.test(workerCode)) {
                alert("Le code travailleur doit contenir 4 à 10 caractères alphanumériques majuscules");
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>