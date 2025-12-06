<?php
session_start();
require_once 'db_config.php';

// Vérification des droits admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: S1.php");
    exit();
}

$message = "";
$user = null;

// Récupérer les données de l'utilisateur à modifier
if (isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT id, worker_code, nom, email, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $message = "❌ Utilisateur non trouvé";
            header("Location: admin_users.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erreur récupération utilisateur: " . $e->getMessage());
        $message = "❌ Erreur lors de la récupération des données utilisateur";
    }
}

// Traitement de la modification
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_user'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "❌ Erreur de sécurité. Veuillez réessayer.";
    } else {
        $userId = (int)$_POST['user_id'];
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $worker_code = trim($_POST['worker_code'] ?? '');
        $role = trim($_POST['role'] ?? 'user');
        $change_password = isset($_POST['change_password']) && $_POST['change_password'] === '1';
        $password = trim($_POST['password'] ?? '');

        // Validation
        $errors = [];
        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
        if (empty($worker_code) || !preg_match('/^[A-Z0-9]{4,10}$/', $worker_code)) {
            $errors[] = "Code travailleur invalide (4-10 caractères alphanumériques majuscules)";
        }
        if ($change_password && (empty($password) || strlen($password) < 8)) {
            $errors[] = "Le mot de passe doit faire au moins 8 caractères";
        }

        if (empty($errors)) {
            try {
                // Vérifier si l'email ou le code existe déjà pour un autre utilisateur
                $stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR worker_code = ?) AND id != ?");
                $stmt->execute([$email, $worker_code, $userId]);
                if ($stmt->fetch()) {
                    $message = "❌ Cet email ou code travailleur est déjà utilisé par un autre utilisateur.";
                } else {
                    if ($change_password) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET worker_code = ?, nom = ?, email = ?, mot_de_passe = ?, role = ? WHERE id = ?");
                        $stmt->execute([$worker_code, $nom, $email, $hashed, $role, $userId]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET worker_code = ?, nom = ?, email = ?, role = ? WHERE id = ?");
                        $stmt->execute([$worker_code, $nom, $email, $role, $userId]);
                    }
                    
                    $message = "✅ Utilisateur mis à jour avec succès!";
                    // Recharger les données de l'utilisateur
                    $stmt = $pdo->prepare("SELECT id, worker_code, nom, email, role FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                error_log("Erreur modification utilisateur: " . $e->getMessage());
                $message = "❌ Erreur lors de la mise à jour de l'utilisateur.";
            }
        } else {
            $message = "❌ " . implode("<br>❌ ", $errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-container {
            max-width: 800px;
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
        .password-fields {
            display: none;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="bi bi-pencil-square"></i> Modifier Utilisateur
            </h2>
            <div>
                <a href="admin_users.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="notification <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= nl2br(htmlspecialchars($message)) ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-person-gear"></i> Modification de l'utilisateur</h4>
            </div>
            <div class="card-body">
                <form method="POST" id="editUserForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="update_user" value="1">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="nom" name="nom" 
                                   value="<?= htmlspecialchars($user['nom']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="worker_code" class="form-label">Code travailleur</label>
                            <input type="text" class="form-control" id="worker_code" name="worker_code" 
                                   pattern="[A-Z0-9]{4,10}" title="4-10 caractères alphanumériques majuscules"
                                   value="<?= htmlspecialchars($user['worker_code']) ?>" required>
                            <small class="text-muted">Exemple: EMP1234</small>
                        </div>
                   
                        
                        <div class="col-12 mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="changePasswordCheck" name="change_password" value="1">
                                <label class="form-check-label" for="changePasswordCheck">
                                    Modifier le mot de passe
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-12 password-fields" id="passwordFields">
                            <div class="row g-3 mt-2">
                                <div class="col-md-12">
                                    <label for="password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="password" name="password" minlength="8">
                                    <small class="text-muted">Minimum 8 caractères</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-danger">
            Utilisateur non trouvé ou ID non spécifié.
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Afficher/masquer les champs de mot de passe
        document.getElementById('changePasswordCheck').addEventListener('change', function() {
            const passwordFields = document.getElementById('passwordFields');
            if (this.checked) {
                passwordFields.style.display = 'block';
                document.getElementById('password').required = true;
            } else {
                passwordFields.style.display = 'none';
                document.getElementById('password').required = false;
            }
        });

        // Validation côté client pour le formulaire
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            const changePassword = document.getElementById('changePasswordCheck').checked;
            const password = document.getElementById('password').value;
            const workerCode = document.getElementById('worker_code').value;
            
            if (changePassword && password.length < 8) {
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