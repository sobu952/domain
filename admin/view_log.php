<?php
session_start();

// Dodaj debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /domeny/auth/login.php');
    exit;
}

$fileName = $_GET['file'] ?? '';
$logFile = '../logs/' . basename($fileName); // Zabezpieczenie przed path traversal

if (!$fileName || !file_exists($logFile)) {
    header('Location: logs.php');
    exit;
}

$content = file_get_contents($logFile);
$lines = explode("\n", $content);
$lines = array_reverse($lines); // Najnowsze na górze
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plik logu: <?php echo htmlspecialchars($fileName); ?> - Domain Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .log-content {
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        .log-line {
            padding: 0.25rem 0.5rem;
            border-bottom: 1px solid #333;
        }
        .log-line:hover {
            background-color: #2d2d30;
        }
        .log-error {
            background-color: #4c1f1f;
            color: #ff6b6b;
        }
        .log-success {
            color: #51cf66;
        }
        .log-timestamp {
            color: #868e96;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-file-alt"></i> 
                        <?php echo htmlspecialchars($fileName); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="logs.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Powrót do logów
                            </a>
                            <a href="?file=<?php echo urlencode($fileName); ?>&download=1" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i> Pobierz
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Plik:</strong> <?php echo htmlspecialchars($fileName); ?>
                        </div>
                        <div>
                            <small class="text-muted">
                                Rozmiar: <?php echo round(filesize($logFile) / 1024, 1); ?> KB | 
                                Linie: <?php echo count($lines); ?> |
                                Modyfikacja: <?php echo date('d.m.Y H:i:s', filemtime($logFile)); ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="log-content">
                            <?php foreach ($lines as $lineNum => $line): ?>
                                <?php if (trim($line)): ?>
                                <div class="log-line <?php 
                                    if (stripos($line, 'błąd') !== false || stripos($line, 'error') !== false) echo 'log-error';
                                    elseif (stripos($line, 'sukces') !== false || stripos($line, 'success') !== false) echo 'log-success';
                                ?>">
                                    <span class="log-timestamp"><?php echo str_pad($lineNum + 1, 4, '0', STR_PAD_LEFT); ?>:</span>
                                    <?php echo htmlspecialchars($line); ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
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

<?php
// Obsługa pobierania pliku
if (isset($_GET['download']) && $_GET['download'] == '1') {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($logFile));
    readfile($logFile);
    exit;
}
?>