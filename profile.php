<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$messageType = '';

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        if (!empty($username) && !empty($email)) {
            try {
                // Sprawdź czy username/email nie są już zajęte przez innego użytkownika
                $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $_SESSION['user_id']]);
                
                if ($stmt->fetch()) {
                    $message = "Nazwa użytkownika lub email są już zajęte.";
                    $messageType = "warning";
                } else {
                    $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$username, $email, $_SESSION['user_id']])) {
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $message = "Profil został zaktualizowany pomyślnie.";
                        $messageType = "success";
                    }
                }
            } catch (Exception $e) {
                $message = "Błąd podczas aktualizacji profilu: " . $e->getMessage();
                $messageType = "danger";
            }
        } else {
            $message = "Wypełnij wszystkie pola.";
            $messageType = "warning";
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (!empty($currentPassword) && !empty($newPassword) && !empty($confirmPassword)) {
            if ($newPassword === $confirmPassword) {
                try {
                    // Sprawdź aktualne hasło
                    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user && password_verify($currentPassword, $user['password'])) {
                        if (strlen($newPassword) >= 6) {
                            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                            $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                            if ($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                                $message = "Hasło zostało zmienione pomyślnie.";
                                $messageType = "success";
                            }
                        } else {
                            $message = "Hasło musi mieć co najmniej 6 znaków.";
                            $messageType = "warning";
                        }
                    } else {
                        $message = "Aktualne hasło jest nieprawidłowe.";
                        $messageType = "danger";
                    }
                } catch (Exception $e) {
                    $message = "Błąd podczas zmiany hasła: " . $e->getMessage();
                    $messageType = "danger";
                }
            } else {
                $message = "Nowe hasła nie są identyczne.";
                $messageType = "warning";
            }
        } else {
            $message = "Wypełnij wszystkie pola.";
            $messageType = "warning";
        }
    }
}

// Pobierz dane użytkownika
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = [];
    $message = "Błąd podczas pobierania danych użytkownika: " . $e->getMessage();
    $messageType = "danger";
}

// Pobierz statystyki użytkownika
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM favorite_domains WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $favoritesCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Nadchodzące rejestracje
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM domains d
        JOIN favorite_domains fd ON d.id = fd.domain_id
        WHERE fd.user_id = ? 
        AND d.registration_available_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $upcomingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    $favoritesCount = 0;
    $upcomingCount = 0;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Domain Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-user"></i> Mój profil</h1>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Statystyki użytkownika -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Twoje statystyki</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="border-end">
                                            <div class="h4 text-primary"><?php echo number_format($favoritesCount); ?></div>
                                            <small class="text-muted">Ulubione domeny</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="h4 text-warning"><?php echo number_format($upcomingCount); ?></div>
                                        <small class="text-muted">Nadchodzące rejestracje</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <small class="text-muted">
                                        Konto utworzone: <?php echo date('d.m.Y', strtotime($user['created_at'] ?? '')); ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Informacje o koncie -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informacje o koncie</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Rola:</strong> 
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($user['role'] ?? 'user'); ?>
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <strong>Ostatnia aktualizacja:</strong><br>
                                    <small class="text-muted">
                                        <?php echo $user['updated_at'] ? date('d.m.Y H:i', strtotime($user['updated_at'])) : 'Nigdy'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formularz edycji profilu -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-edit"></i> Edytuj profil</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Nazwa użytkownika</label>
                                                <input type="text" class="form-control" name="username" 
                                                       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Adres email</label>
                                                <input type="email" class="form-control" name="email" 
                                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Zapisz zmiany
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Zmiana hasła -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-lock"></i> Zmiana hasła</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3">
                                        <label class="form-label">Aktualne hasło</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Nowe hasło</label>
                                                <input type="password" class="form-control" name="new_password" 
                                                       minlength="6" required>
                                                <div class="form-text">Minimum 6 znaków</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Potwierdź nowe hasło</label>
                                                <input type="password" class="form-control" name="confirm_password" 
                                                       minlength="6" required>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key"></i> Zmień hasło
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>