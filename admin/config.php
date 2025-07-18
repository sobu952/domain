<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$messageType = '';

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if ($key !== 'submit') {
                $stmt = $db->prepare("INSERT INTO system_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
                $stmt->execute([$key, $value]);
            }
        }
        $message = "Konfiguracja została zapisana pomyślnie.";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Błąd podczas zapisywania: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Pobierz aktualną konfigurację
try {
    $stmt = $db->prepare("SELECT config_key, config_value FROM system_config");
    $stmt->execute();
    $configData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $configData = [];
    $message = "Błąd podczas pobierania konfiguracji: " . $e->getMessage();
    $messageType = "danger";
}

// Pobierz konfigurację z pliku
$fileConfig = include '../config/config.php';

// Pobierz kategorie dla sidebara
try {
    $stmt = $db->prepare("SELECT * FROM categories WHERE active = 1 ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfiguracja - Domain Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Kategorie</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <?php foreach ($categories as $category): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../domains/?category=<?php echo $category['id']; ?>">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
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
                            <a class="nav-link active" href="config.php">
                                <i class="fas fa-cog"></i> Konfiguracja
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logs.php">
                                <i class="fas fa-file-alt"></i> Logi
                            </a>
                        </li>
                    </ul>
                    <?php endif; ?>
                </div>
            </nav>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-cog"></i> Konfiguracja systemu</h1>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row">
                        <!-- Konfiguracja Gemini API -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-robot"></i> Gemini API</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Klucz API Gemini</label>
                                        <input type="text" class="form-control" name="gemini_api_key" 
                                               value="<?php echo htmlspecialchars($fileConfig['gemini_api_key'] ?? ''); ?>" readonly>
                                        <div class="form-text">Konfigurowane w pliku config/config.php</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Model Gemini</label>
                                        <input type="text" class="form-control" name="gemini_model" 
                                               value="<?php echo htmlspecialchars($fileConfig['gemini_model'] ?? 'gemini-2.5-flash'); ?>" readonly>
                                        <div class="form-text">Konfigurowane w pliku config/config.php</div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="gemini_api_enabled" value="1" 
                                                   <?php echo ($configData['gemini_api_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label">
                                                Włącz analizę Gemini API
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Konfiguracja Email -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-envelope"></i> Powiadomienia Email</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Host SMTP</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($fileConfig['email_smtp_host'] ?? ''); ?>" readonly>
                                        <div class="form-text">Konfigurowane w pliku config/config.php</div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="email_notifications_enabled" value="1" 
                                                   <?php echo ($configData['email_notifications_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label">
                                                Włącz powiadomienia email
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Harmonogram pobierania -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-clock"></i> Harmonogram</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Godzina pobierania domen</label>
                                        <input type="time" class="form-control" name="fetch_time" 
                                               value="<?php echo htmlspecialchars($configData['fetch_time'] ?? '09:05'); ?>">
                                        <div class="form-text">Godzina codziennego pobierania domen</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ostatnie pobieranie</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($configData['last_fetch_date'] ?? 'Nigdy'); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informacje o systemie -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informacje o systemie</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>Wersja PHP:</strong> <?php echo phpversion(); ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Strefa czasowa:</strong> <?php echo date_default_timezone_get(); ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Czas serwera:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Folder logów:</strong> 
                                        <?php echo is_writable('../logs') ? '<span class="text-success">Zapisywalny</span>' : '<span class="text-danger">Brak uprawnień</span>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Instrukcje Cron -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-terminal"></i> Konfiguracja Cron Jobs</h5>
                        </div>
                        <div class="card-body">
                            <p>Dodaj następujące zadania do crontab na swoim serwerze:</p>
                            
                            <div class="mb-3">
                                <h6>1. Codzienne pobieranie domen (<?php echo $configData['fetch_time'] ?? '09:05'; ?>)</h6>
                                <code class="d-block bg-dark text-light p-2 rounded">
                                    <?php 
                                    $time = explode(':', $configData['fetch_time'] ?? '09:05');
                                    echo $time[1] . ' ' . $time[0];
                                    ?> * * * /usr/bin/php <?php echo realpath('../cron/fetch_domains.php'); ?>
                                </code>
                            </div>
                            
                            <div class="mb-3">
                                <h6>2. Wysyłanie przypomnień (8:00)</h6>
                                <code class="d-block bg-dark text-light p-2 rounded">
                                    0 8 * * * /usr/bin/php <?php echo realpath('../cron/send_reminders.php'); ?>
                                </code>
                            </div>
                            
                            <div class="mb-3">
                                <h6>3. Czyszczenie starych danych (2:00, raz w tygodniu)</h6>
                                <code class="d-block bg-dark text-light p-2 rounded">
                                    0 2 * * 0 /usr/bin/php <?php echo realpath('../cron/cleanup.php'); ?>
                                </code>
                            </div>
                            
                            <div class="alert alert-warning">
                                <strong>Uwaga:</strong> Ścieżka do PHP może być inna na Twoim hostingu. 
                                Sprawdź w panelu hostingu lub skontaktuj się z dostawcą.
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" name="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Zapisz konfigurację
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
</body>
</html>