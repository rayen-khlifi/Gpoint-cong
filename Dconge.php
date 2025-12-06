<?php
session_start();
ob_start();

// Activation du débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification des droits admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: choix_interface.php");
    exit();
}

// Connexion à la base de données
require_once 'db_config.php';

// Fonction pour calculer les statistiques
function calculerStats($pdo) {
    $stats = ['total' => 0, 'approuve' => 0, 'refuse' => 0, 'en_attente' => 0];
    $demandes = $pdo->query("SELECT statut FROM conges")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($demandes as $d) {
        $stats['total']++;
        $stats[$d['statut']]++;
    }
    return $stats;
}

// Fonction pour envoyer une notification
function envoyerNotificationConges($pdo, $worker_code, $message, $demande_id) {
    $sql = "INSERT INTO notifications (user_code, message, demande_id, created_at) 
            VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$worker_code, $message, $demande_id]);
}

// Traitement des actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $conge_id = (int)$_POST['conge_id'];
    $action = $_POST['action'];
    $raison_refus = ($action === 'refuser') ? trim($_POST['raison_refus']) : null;

    try {
        // D'abord, récupérer les infos de la demande avant mise à jour
        $sql_select = "SELECT worker_code, worker_name, date_debut, date_fin FROM conges WHERE id = ?";
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->execute([$conge_id]);
        $demande = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if (!$demande) {
            throw new Exception("Demande introuvable");
        }

        // Ensuite, mettre à jour le statut
        $sql = "UPDATE conges SET 
                statut = ?, 
                raison_refus = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $new_status = ($action === 'accepter') ? 'approuve' : 'refuse';
        $stmt->execute([$new_status, $raison_refus, $conge_id]);
        
        // Envoyer la notification
        $message_notif = "";
        if ($action === 'accepter') {
            $message_notif = "Votre demande de congé du ".date('d/m/Y', strtotime($demande['date_debut']))." au ".date('d/m/Y', strtotime($demande['date_fin']))." a été acceptée.";
        } else {
            $message_notif = "Votre demande de congé du ".date('d/m/Y', strtotime($demande['date_debut']))." au ".date('d/m/Y', strtotime($demande['date_fin']))." a été refusée. Raison: ".htmlspecialchars($raison_refus);
        }
        
        envoyerNotificationConges($pdo, $demande['worker_code'], $message_notif, $conge_id);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Demande traitée avec succès'
        ];
    } catch (Exception $e) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => 'Erreur: ' . $e->getMessage()
        ];
    }
    header("Location: Dconge.php");
    exit();
}

// Récupération des demandes et stats
try {
    $demandes = $pdo->query("SELECT * FROM conges ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $stats = calculerStats($pdo);
} catch (PDOException $e) {
    die("Erreur base de données: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion des Congés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4e73df;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }
        .card-header {
            background-color: var(--primary);
            color: white;
        }
        .badge-en_attente {
            background-color: var(--warning);
            color: #000;
        }
        .badge-approuve {
            background-color: var(--success);
        }
        .badge-refuse {
            background-color: var(--danger);
        }
        .table-responsive {
            max-height: 70vh;
        }
        .reason-box {
            font-size: 0.85rem;
            color: #6c757d;
            font-style: italic;
        }
        .action-btn {
            min-width: 100px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show">
                <?= $_SESSION['flash']['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-shield-lock me-2"></i> Administration des Congés
                </h3>
                <div>
                    <span class="badge bg-light text-dark">
                        <i class="bi bi-people-fill me-1"></i> <?= $stats['total'] ?> demandes
                    </span>
                    <a href="notifications.php" class="btn btn-sm btn-light ms-2">
                        <i class="bi bi-bell"></i> Notifications
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h6 class="text-muted">En attente</h6>
                                <h3 class="text-warning"><?= $stats['en_attente'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Approuvés</h6>
                                <h3 class="text-success"><?= $stats['approuve'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Refusés</h6>
                                <h3 class="text-danger"><?= $stats['refuse'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Admin</h6>
                                <h5><?= $_SESSION['email'] ?? 'Admin' ?></h5>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des demandes -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employé</th>
                                <th>Code</th>
                                <th>Période</th>
                                <th>Jours</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demandes as $d): 
                                $debut = new DateTime($d['date_debut']);
                                $fin = new DateTime($d['date_fin']);
                                $created_at = new DateTime($d['created_at']);
                            ?>
                            <tr>
                                <td><?= $d['id'] ?></td>
                                <td><?= htmlspecialchars($d['worker_name']) ?></td>
                                <td><?= $d['worker_code'] ?></td>
                                <td>
                                    <?= $debut->format('d/m/Y') ?> 
                                    <i class="bi bi-arrow-right mx-1"></i>
                                    <?= $fin->format('d/m/Y') ?>
                                </td>
                                <td><?= $d['jours_pris'] ?></td>
                                <td>
                                    <span class="badge badge-<?= $d['statut'] ?>">
                                        <?= ucfirst($d['statut']) ?>
                                    </span>
                                    <?php if ($d['statut'] === 'refuse' && !empty($d['raison_refus'])): ?>
                                        <div class="reason-box mt-1"><?= htmlspecialchars($d['raison_refus']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= $created_at->format('d/m/Y') ?></td>
                                <td>
                                    <?php if ($d['statut'] === 'en_attente'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="conge_id" value="<?= $d['id'] ?>">
                                            <input type="hidden" name="action" value="accepter">
                                            <button type="submit" class="btn btn-success btn-sm action-btn">
                                                <i class="bi bi-check-circle"></i> Accepter
                                            </button>
                                        </form>
                                        <button class="btn btn-danger btn-sm action-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#refusModal"
                                                data-id="<?= $d['id'] ?>">
                                            <i class="bi bi-x-circle"></i> Refuser
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">Traité</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de refus -->
    <div class="modal fade" id="refusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Motif du refus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="conge_id" id="modalCongeId">
                        <input type="hidden" name="action" value="refuser">
                        <div class="mb-3">
                            <label class="form-label">Raison du refus :</label>
                            <textarea name="raison_refus" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="location.reload()">
                            Annuler
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Confirmer le refus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du modal de refus
        const refusModal = document.getElementById('refusModal');
        if (refusModal) {
            refusModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const congeId = button.getAttribute('data-id');
                document.getElementById('modalCongeId').value = congeId;
            });
        }
    </script>
</body>
</html>

