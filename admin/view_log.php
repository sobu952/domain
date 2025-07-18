<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
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

// Obsługa pobierania pliku
if (isset($_GET['download']) && $_GET['download'] == '1') {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($logFile));
    readfile($logFile);
    exit;
}
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-globe"></i> Domain Monitor
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../domains/">
                            <i class="fas fa-list"></i> Domeny
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../favorites.php">
                            <i class="fas fa-heart"></i> Ulubione
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Administracja
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="categories.php">Kategorie</a></li>
                            <li><a class="dropdown-item" href="users.php">Użytkownicy</a></li>
                            <li><a class="dropdown-item" href="config.php">Konfiguracja</a></li>
                            <li><a class="dropdown-item" href="logs.php">Logi</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../profile.php">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">Wyloguj</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../domains/">
                                <i class="fas fa-list"></i> Wszystkie domeny
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../favorites.php">
                                <i class="fas fa-heart"></i> Ulubione
                            </a>
                        </li>
                    </ul>

                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Administracja</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="fas fa-tags"></i> Kategorie
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i> Użytkownicy
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="config.php">
                                <i class="fas fa-cog"></i> Konfiguracja
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="logs.php">
                                <i class="fas fa-file-alt"></i> Logi
                            </a>
                        </li>
                    </ul>
                    <?php endif; ?>
                </div>
            </nav>
            
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