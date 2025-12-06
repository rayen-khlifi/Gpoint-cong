<?php
session_start();
require_once 'db_config.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: S1.php");
    exit();
}

// Récupérer le code utilisateur
if (!isset($_SESSION['user_code'])) {
    // Si user_code n'est pas dans la session, le récupérer depuis la base
    $stmt = $pdo->prepare("SELECT worker_code FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $_SESSION['user_code'] = $user['worker_code'] ?? null;
    
    if (!$_SESSION['user_code']) {
        header("Location: S1.php");
        exit();
    }
}

$user_code = $_SESSION['user_code'];

// Marquer comme lues si demandé
if (isset($_GET['mark_as_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_code = ?")->execute([$user_code]);
    header("Location: notifications.php");
    exit();
}

// Récupérer les notifications
$stmt = $pdo->prepare("
    SELECT n.*, c.date_debut, c.date_fin, c.statut as demande_statut
    FROM notifications n
    LEFT JOIN conges c ON n.demande_id = c.id
    WHERE n.user_code = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$user_code]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter les non lues
$unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_code = ? AND is_read = FALSE");
$unread->execute([$user_code]);
$unread_count = $unread->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .unread { 
            background-color: #f8f9fa; 
            font-weight: 500; 
            border-left: 4px solid #0d6efd;
        }
        .notification-date { 
            font-size: 0.8rem; 
            color: #6c757d; 
        }
        .notification-message {
            white-space: pre-line;
        }
        .badge-accepte {
            background-color: #198754;
        }
        .badge-refuse {
            background-color: #dc3545;
        }
        .badge-info {
            background-color: #6c757d;
        }
        .empty-notifications {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Mes Notifications 
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger"><?= $unread_count ?> non lues</span>
                <?php endif; ?>
            </h2>
            <div>
                <a href="<?= $estAdmin ? 'Dconge.php' : 's2.php' ?>" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_as_read=1" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-check-all"></i> Tout marquer comme lu
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                    <div class="empty-notifications">
                        <i class="bi bi-bell-slash" style="font-size: 2rem;"></i>
                        <p class="mt-2">Aucune notification</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="list-group-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <span class="badge badge-<?= 
                                            strpos($notif['message'], 'acceptée') !== false ? 'accepte' : 
                                            (strpos($notif['message'], 'refusée') !== false ? 'refuse' : 'info') 
                                        ?> me-2">
                                            <?= 
                                                strpos($notif['message'], 'acceptée') !== false ? 'Accepté' : 
                                                (strpos($notif['message'], 'refusée') !== false ? 'Refusé' : 'Info') 
                                            ?>
                                        </span>
                                        <span class="notification-message"><?= htmlspecialchars($notif['message']) ?></span>
                                    </div>
                                    <small class="notification-date">
                                        <?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?>
                                    </small>
                                </div>
                                <?php if ($notif['date_debut']): ?>
                                    <div class="mt-2 text-muted small">
                                        <i class="bi bi-calendar"></i> 
                                        <?= date('d/m/Y', strtotime($notif['date_debut'])) ?> - 
                                        <?= date('d/m/Y', strtotime($notif['date_fin'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>