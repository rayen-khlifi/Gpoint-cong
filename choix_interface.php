<?php
session_start();

// Protection : personne non connectée → retour à la page de login
if (!isset($_SESSION['user_id'])) {
    header('Location: S1.php');
    exit();
}

// Récupération des infos utilisateur depuis la session
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'] ?? '';
$user_role = $_SESSION['role'] ?? 'user';

// Détermine si l'utilisateur est admin
$estAdmin = ($user_role === 'admin');

// Utilise l'ID comme user_code si non défini
if (!isset($_SESSION['user_code'])) {
    $_SESSION['user_code'] = $user_id; // Utilisation de l'ID comme fallback
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisir l'interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 2rem;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .app-container {
            width: 100%;
            max-width: 1200px;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .app-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .welcome-container {
            text-align: center;
            margin-bottom: 2.5rem;
            animation: fadeIn 0.8s ease-out;
        }

        .welcome-container h2 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .welcome-container .lead {
            color: #7f8c8d;
            font-size: 1.25rem;
        }

        .button-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .app-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            border-radius: 12px;
            background: white;
            color: var(--dark-color);
            text-decoration: none;
            transition: var(--transition);
            box-shadow: var(--shadow);
            border: 2px solid transparent;
            text-align: center;
            min-height: 180px;
            position: relative;
            overflow: hidden;
        }

        .app-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .app-button i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .app-button:hover i {
            transform: scale(1.1);
        }

        .app-button .button-text {
            font-size: 1.1rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .app-button:hover .button-text {
            color: var(--primary-color);
        }

        .admin-button {
            border-color: var(--danger-color);
        }

        .admin-button i {
            color: var(--danger-color);
        }

        .user-button {
            border-color: var(--secondary-color);
        }

        .user-button i {
            color: var(--secondary-color);
        }

        .disabled-button {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: #f1f1f1;
        }

        .action-bar {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .notification-badge {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ffc107;
            color: #000;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .app-container {
                padding: 1.5rem;
            }
            
            .button-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="welcome-container">
            <h2>Bonjour, <?= htmlspecialchars($user_email) ?> !</h2>
            <p class="lead">Choisissez votre espace de travail</p>
        </div>

        <div class="button-grid">
            <?php if ($estAdmin): ?>
                <a href="Dconge.php" class="app-button admin-button">
                    <i class="bi bi-calendar-check"></i>
                    <span class="button-text">Gestion des Congés</span>
                </a>
                <a href="list.php" class="app-button admin-button">
                    <i class="bi bi-clock-history"></i>
                    <span class="button-text">Gestion des Pointages</span>
                </a>
                <a href="admin_users.php" class="app-button admin-button">
                    <i class="bi bi-people-fill"></i>
                    <span class="button-text">Gestion des Utilisateurs</span>
                </a>
            <?php else: ?>
                <div class="app-button disabled-button">
                    <i class="bi bi-shield-lock"></i>
                    <span class="button-text">Espace Admin</span>
                </div>
            <?php endif; ?>

            <a href="s2.php" class="app-button user-button">
                <i class="bi bi-person"></i>
                <span class="button-text">Espace Utilisateur</span>
            </a>
        </div>

        <div class="action-bar">
            <a href="notifications.php" class="btn btn-primary notification-badge">
                <i class="bi bi-bell"></i> Notifications
                <?php
                try {
                    require_once 'db_config.php';
                    $unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_code = ? AND is_read = FALSE");
                    $unread->execute([$_SESSION['user_code']]);
                    $unread_count = $unread->fetchColumn();
                    if ($unread_count > 0): ?>
                        <span class="notification-count"><?= $unread_count ?></span>
                    <?php endif;
                } catch (PDOException $e) {
                    error_log("Erreur notifications: " . $e->getMessage());
                }
                ?>
            </a>
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation au chargement de la page
        document.addEventListener('DOMContentLoaded', () => {
            const buttons = document.querySelectorAll('.app-button');
            buttons.forEach((button, index) => {
                button.style.animation = `fadeIn 0.5s ease-out ${index * 0.1}s forwards`;
                button.style.opacity = 0;
            });
        });
    </script>
</body>
</html>