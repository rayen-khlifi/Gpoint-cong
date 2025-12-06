<?php
ob_start();
session_start();

require_once 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: S1.php");
    exit();
}

// Fonction pour calculer le total des congés approuvés
function getTotalCongesApprouves($pdo, $worker_code) {
    try {
        $current_year = date('Y');
        $stmt = $pdo->prepare("SELECT SUM(jours_pris) as total FROM conges 
                              WHERE worker_code = ? AND statut = 'approuve' 
                              AND annee = ?");
        $stmt->execute([$worker_code, $current_year]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Erreur calcul congés: ".$e->getMessage());
        return 0;
    }
}

// Récupérer les informations de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT id, nom, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("Utilisateur non trouvé");
    }
    
    $worker_code = $_SESSION['user_id'];
    $worker_name = $user['nom'] ?? '';
    
    $_SESSION['user_code'] = $worker_code;
    $_SESSION['username'] = $worker_name;
} catch (PDOException $e) {
    die("Erreur de récupération utilisateur: " . $e->getMessage());
}

$current_year = date('Y');
$message = '';

// Fonction pour calculer les jours ouvrés (incluant la date de début)
function calculerJoursOuvrables($start, $end) {
    try {
        $start = new DateTime($start);
        $end = new DateTime($end);
        $end->modify('+1 day'); // Pour inclure la date de fin
        
        $workingDays = 0;
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        
        foreach ($period as $dt) {
            $dayOfWeek = $dt->format('N'); // 1 (lundi) à 7 (dimanche)
            if ($dayOfWeek <= 5) { // Lundi à vendredi
                $workingDays++;
            }
        }
        return $workingDays;
    } catch (Exception $e) {
        error_log("Erreur calcul jours: ".$e->getMessage());
        return 0;
    }
}

// Calcul du quota initial
$total_conges = getTotalCongesApprouves($pdo, $worker_code);
$jours_restants = max(0, 22 - $total_conges);
$pourcentage = min(100, ($total_conges / 22) * 100);

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin = $_POST['date_fin'] ?? '';
    $commentaire = trim(htmlspecialchars($_POST['commentaire'] ?? ''));

    // Validation
    $errors = [];
    
    if (empty($date_debut) || empty($date_fin)) {
        $errors[] = "Les dates sont requises";
    } elseif (strtotime($date_fin) < strtotime($date_debut)) {
        $errors[] = "La date de fin doit être après la date de début";
    } else {
        $jours_demandes = calculerJoursOuvrables($date_debut, $date_fin);
        if ($jours_demandes <= 0) {
            $errors[] = "Aucun jour ouvré dans cette période";
        } elseif ($jours_demandes > $jours_restants) {
            $errors[] = "Dépassement le delai! Vous demandez $jours_demandes jours mais il ne vous reste que $jours_restants jours disponibles.";
        }
    }

    if (empty($errors)) {
        try {
            // Requête SQL adaptée à votre table
            $sql = "INSERT INTO conges (
                worker_code, 
                worker_name, 
                date_debut, 
                date_fin, 
                jours_pris, 
                statut, 
                annee, 
                created_at,
                raison_refus,
                traite_par,
                traite_le
            ) VALUES (?, ?, ?, ?, ?, 'en_attente', ?, NOW(), NULL, NULL, NULL)";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $worker_code,
                $worker_name,
                $date_debut,
                $date_fin,
                $jours_demandes,
                $current_year
            ]);
            
            if ($success) {
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => 'Votre demande de congé a été enregistrée avec succès'
                ];
                header("Location: conge.php");
                exit();
            } else {
                throw new Exception("Échec de l'enregistrement");
            }
        } catch (PDOException $e) {
            error_log("Erreur SQL: ".$e->getMessage());
            $errors[] = "Erreur lors de l'enregistrement. Code: ".$e->getCode();
        }
    }
    
    if (!empty($errors)) {
        $message = '<div class="alert alert-danger">'.implode('<br>', $errors).'</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Congé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #4e73df;
            --success: #28a745;
            --danger: #dc3545;
        }
        .quota-bar {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .quota-progress {
            height: 100%;
            background-color: var(--primary);
            width: <?= $pourcentage ?>%;
            transition: width 0.3s ease;
        }
        .jours-info {
            font-size: 0.9rem;
        }
        .jours-restants {
            font-weight: bold;
            color: var(--primary);
        }
        .jours-utilises {
            font-weight: bold;
            color: var(--success);
        }
        #jours-demandes-container {
            display: none;
            margin-top: 10px;
        }
        #jours-demandes {
            font-weight: bold;
        }
        #quota-alert {
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h4 class="m-0 font-weight-bold">
                            <i class="bi bi-calendar-plus me-2"></i> Demande de congé
                        </h4>
                        <a href="s2.php" class="btn btn-sm btn-light">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                    <div class="card-body">
                        <?php 
                        if (isset($_SESSION['flash'])) {
                            echo '<div class="alert alert-'.$_SESSION['flash']['type'].'">'.$_SESSION['flash']['message'].'</div>';
                            unset($_SESSION['flash']);
                        }
                        echo $message;
                        ?>

                        <!-- Barre de quota -->
                        <div class="mb-4">
                            <h5>nombre de congés annuels</h5>
                            <div class="quota-bar">
                                <div class="quota-progress"></div>
                            </div>
                            <div class="jours-info">
                                <span class="jours-utilises"><?= $total_conges ?> jours</span> utilisés sur 22 | 
                                <span class="jours-restants"><?= $jours_restants ?> jours</span> restants
                            </div>
                            <div id="jours-demandes-container" class="alert alert-info">
                                Jours demandés: <span id="jours-demandes">0</span>
                            </div>
                            <div id="quota-alert" class="alert alert-danger">
                                Attention: vous allez dépasser votre delai de congés!
                            </div>
                        </div>

                        <form method="post" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="worker_code" class="form-label">Code Employé</label>
                                    <input type="text" class="form-control" id="worker_code" 
                                           value="<?= htmlspecialchars($worker_code) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="worker_name" class="form-label">Nom Complet</label>
                                    <input type="text" class="form-control" id="worker_name" 
                                           value="<?= htmlspecialchars($worker_name) ?>" readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="date_debut" class="form-label">Date de début</label>
                                    <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="date_fin" class="form-label">Date de fin</label>
                                    <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="commentaire" class="form-label">Commentaire (optionnel)</label>
                                <textarea class="form-control" id="commentaire" name="commentaire" rows="3"></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send-fill me-2"></i> Soumettre la demande
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        const joursRestants = <?= $jours_restants ?>;
        const quotaAlert = document.getElementById('quota-alert');
        const joursDemandesContainer = document.getElementById('jours-demandes-container');
        const joursDemandesSpan = document.getElementById('jours-demandes');
        
        // Fonction pour calculer les jours ouvrés entre deux dates
        function calculerJoursOuvrables(debut, fin) {
            if (!debut || !fin) return 0;
            
            const start = new Date(debut);
            const end = new Date(fin);
            if (end < start) return 0;
            
            let count = 0;
            const current = new Date(start);
            end.setDate(end.getDate() + 1); // Inclure la date de fin
            
            while (current < end) {
                const day = current.getDay();
                if (day !== 0 && day !== 6) { // Pas dimanche (0) ni samedi (6)
                    count++;
                }
                current.setDate(current.getDate() + 1);
            }
            
            return count;
        }
        
        // Écouteurs d'événements pour les dates
        document.getElementById('date_debut').addEventListener('change', updateJoursDemandes);
        document.getElementById('date_fin').addEventListener('change', updateJoursDemandes);
        
        function updateJoursDemandes() {
            const dateDebut = document.getElementById('date_debut').value;
            const dateFin = document.getElementById('date_fin').value;
            
            if (dateDebut && dateFin) {
                const jours = calculerJoursOuvrables(dateDebut, dateFin);
                
                if (jours > 0) {
                    joursDemandesSpan.textContent = jours;
                    joursDemandesContainer.style.display = 'block';
                    
                    if (jours > joursRestants) {
                        quotaAlert.style.display = 'block';
                    } else {
                        quotaAlert.style.display = 'none';
                    }
                } else {
                    joursDemandesContainer.style.display = 'none';
                    quotaAlert.style.display = 'none';
                }
            } else {
                joursDemandesContainer.style.display = 'none';
                quotaAlert.style.display = 'none';
            }
        }
        
        // Validation des dates
        document.getElementById('date_fin').addEventListener('change', function() {
            const dateDebut = document.getElementById('date_debut').value;
            const dateFin = this.value;
            
            if (dateDebut && dateFin && new Date(dateFin) < new Date(dateDebut)) {
                alert('La date de fin doit être après la date de début');
                this.value = '';
            }
        });
    </script>
</body>
</html>