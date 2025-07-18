<?php
session_start();

// Dodaj debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /domeny/auth/login.php');
    exit;
}

// Sprawdź czy pliki istnieją
if (!file_exists('../config/database.php')) {
    die('Błąd: Nie można znaleźć pliku config/database.php');
}

try {
    require_once '../config/database.php';
    require_once '../includes/functions.php';
} catch (Exception $e) {
    die('Błąd ładowania plików: ' . $e->getMessage());
}

$database = new Database();
$db = $database->getConnection();

// Pobierz statystyki administracyjne
try {
    $stats = [];
    
    // Użytkownicy
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Kategorie
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM categories WHERE active = 1");
    $stmt->execute();
    $stats['categories'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Domeny dzisiaj
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM domains WHERE DATE(fetch_date) = CURRENT_DATE");
    $stmt->execute();
    $stats['domains_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Ostatnie logi
    $stmt = $db->prepare("SELECT * FROM fetch_logs ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error in admin/index.php: " . $e->getMessage());
    $stats = ['users' => 0, 'categories' => 0, 'domains_today' => 0];
    $recentLogs = [];
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administracyjny - Domain Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-cog"></i> Panel Administracyjny</h1>
                </div>

                <!-- Statystyki -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Użytkownicy
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['users']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Kategorie
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['categories']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-tags fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Domeny dzisiaj
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['domains_today']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-globe fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            System
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            Aktywny
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-server fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Szybkie akcje -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bolt"></i> Szybkie akcje</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="categories.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-tags"></i><br>
                                            Zarządzaj kategoriami
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="users.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-users"></i><br>
                                            Zarządzaj użytkownikami
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="config.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-cog"></i><br>
                                            Konfiguracja systemu
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="logs.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-file-alt"></i><br>
                                            Logi systemu
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ostatnie logi -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-history"></i> Ostatnie logi pobierania</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentLogs)): ?>
                                <p class="text-muted">Brak logów pobierania.</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Liczba domen</th>
                                                <th>Status</th>
                                                <th>Błąd</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentLogs as $log): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></td>
                                                <td><?php echo number_format($log['domains_count']); ?></td>
                                                <td>
                                                    <?php if ($log['status'] === 'success'): ?>
                                                        <span class="badge bg-success">Sukces</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Błąd</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($log['error_message']): ?>
                                                        <small class="text-danger"><?php echo htmlspecialchars($log['error_message']); ?></small>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
</body>
</html>