<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
} catch (Exception $e) {
    die('Błąd ładowania database.php: ' . $e->getMessage());
}

$database = new Database();
$db = $database->getConnection();

// Parametry filtrowania
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;
$filter = $_GET['filter'] ?? '';

// Buduj zapytanie
$whereClause = '';
$params = [];

if ($filter === 'errors') {
    $whereClause = "WHERE status = 'error'";
} elseif ($filter === 'success') {
    $whereClause = "WHERE status = 'success'";
}

// Pobierz logi
try {
    $sql = "SELECT * FROM fetch_logs $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countSql = "SELECT COUNT(*) as total FROM fetch_logs $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalLogs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    error_log("Error in admin/logs.php: " . $e->getMessage());
    $logs = [];
    $totalLogs = 0;
}

$totalPages = ceil($totalLogs / $perPage);

// Pobierz statystyki
try {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_logs,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_logs,
            SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_logs,
            SUM(domains_count) as total_domains
        FROM fetch_logs
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats = ['total_logs' => 0, 'success_logs' => 0, 'error_logs' => 0, 'total_domains' => 0];
}

// Pobierz pliki logów z dysku
$logFiles = [];
$logsDir = '../logs/';
if (is_dir($logsDir)) {
    $files = glob($logsDir . '*.log');
    foreach ($files as $file) {
        $logFiles[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'modified' => filemtime($file),
            'path' => $file
        ];
    }
    // Sortuj według daty modyfikacji (najnowsze pierwsze)
    usort($logFiles, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logi systemu - Domain Monitor</title>
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
                    <h1 class="h2"><i class="fas fa-file-alt"></i> Logi systemu</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="?filter=" class="btn btn-sm btn-outline-secondary <?php echo $filter === '' ? 'active' : ''; ?>">
                                Wszystkie
                            </a>
                            <a href="?filter=success" class="btn btn-sm btn-outline-success <?php echo $filter === 'success' ? 'active' : ''; ?>">
                                Sukces
                            </a>
                            <a href="?filter=errors" class="btn btn-sm btn-outline-danger <?php echo $filter === 'errors' ? 'active' : ''; ?>">
                                Błędy
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statystyki -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Wszystkie logi
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['total_logs']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-list fa-2x text-gray-300"></i>
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
                                            Sukces
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['success_logs']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check fa-2x text-gray-300"></i>
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
                                            Błędy
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['error_logs']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                            Pobrane domeny
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['total_domains']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-globe fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Logi z bazy danych -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-database"></i> Logi pobierania domen</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($logs)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5>Brak logów</h5>
                                    <p class="text-muted">Nie ma jeszcze żadnych logów pobierania.</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Status</th>
                                                <th>Liczba domen</th>
                                                <th>Błąd</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($log['status'] === 'success'): ?>
                                                        <span class="badge bg-success">Sukces</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Błąd</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo number_format($log['domains_count']); ?></td>
                                                <td>
                                                    <?php if ($log['error_message']): ?>
                                                        <small class="text-danger" title="<?php echo htmlspecialchars($log['error_message']); ?>">
                                                            <?php echo htmlspecialchars(substr($log['error_message'], 0, 50)); ?>...
                                                        </small>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Paginacja -->
                                <?php if ($totalPages > 1): ?>
                                <nav aria-label="Paginacja logów" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i> Poprzednia
                                            </a>
                                        </li>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                Następna <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Pliki logów -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-file"></i> Pliki logów</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($logFiles)): ?>
                                <p class="text-muted">Brak plików logów.</p>
                                <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($logFiles, 0, 10) as $file): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($file['name']); ?></strong><br>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y H:i', $file['modified']); ?> 
                                                (<?php echo round($file['size'] / 1024, 1); ?> KB)
                                            </small>
                                        </div>
                                        <a href="view_log.php?file=<?php echo urlencode($file['name']); ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Zobacz plik">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
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