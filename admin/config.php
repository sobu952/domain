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
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
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