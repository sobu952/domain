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

// Obsługa formularzy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                
                if (!empty($username) && !empty($email) && !empty($password)) {
                    try {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                        if ($stmt->execute([$username, $email, $hashedPassword, $role])) {
                            $message = "Użytkownik został dodany pomyślnie.";
                            $messageType = "success";
                        }
                    } catch (Exception $e) {
                        $message = "Błąd podczas dodawania użytkownika: " . $e->getMessage();
                        $messageType = "danger";
                    }
                } else {
                    $message = "Wypełnij wszystkie pola.";
                    $messageType = "warning";
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                
                if (!empty($username) && !empty($email)) {
                    try {
                        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
                        if ($stmt->execute([$username, $email, $role, $id])) {
                            $message = "Użytkownik został zaktualizowany.";
                            $messageType = "success";
                        }
                    } catch (Exception $e) {
                        $message = "Błąd podczas aktualizacji: " . $e->getMessage();
                        $messageType = "danger";
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                if ($id !== $_SESSION['user_id']) { // Nie można usunąć siebie
                    try {
                        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                        if ($stmt->execute([$id])) {
                            $message = "Użytkownik został usunięty.";
                            $messageType = "success";
                        }
                    } catch (Exception $e) {
                        $message = "Błąd podczas usuwania: " . $e->getMessage();
                        $messageType = "danger";
                    }
                } else {
                    $message = "Nie możesz usunąć swojego konta.";
                    $messageType = "warning";
                }
                break;
        }
    }
}

// Pobierz użytkowników
try {
    $stmt = $db->prepare("
        SELECT u.*, 
               COUNT(fd.id) as favorites_count,
               MAX(fd.created_at) as last_favorite
        FROM users u
        LEFT JOIN favorite_domains fd ON u.id = fd.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
    $message = "Błąd podczas pobierania użytkowników: " . $e->getMessage();
    $messageType = "danger";
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Użytkownicy - Domain Monitor</title>
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
                    <h1 class="h2"><i class="fas fa-users"></i> Zarządzanie użytkownikami</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> Dodaj użytkownika
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Lista użytkowników -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>Brak użytkowników</h5>
                            <p class="text-muted">Nie ma jeszcze żadnych użytkowników.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Użytkownik</th>
                                        <th>Email</th>
                                        <th>Rola</th>
                                        <th>Ulubione</th>
                                        <th>Data utworzenia</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-info ms-1">To Ty</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo number_format($user['favorites_count']); ?>
                                            <?php if ($user['last_favorite']): ?>
                                                <br><small class="text-muted">
                                                    Ostatnie: <?php echo date('d.m.Y', strtotime($user['last_favorite'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal dodawania użytkownika -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Dodaj nowego użytkownika</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Nazwa użytkownika</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adres email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hasło</label>
                            <input type="password" class="form-control" name="password" minlength="6" required>
                            <div class="form-text">Minimum 6 znaków</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rola</label>
                            <select class="form-control" name="role" required>
                                <option value="user">Użytkownik</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Dodaj użytkownika</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal edycji użytkownika -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edytuj użytkownika</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nazwa użytkownika</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adres email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rola</label>
                            <select class="form-control" name="role" id="edit_role" required>
                                <option value="user">Użytkownik</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Formularz usuwania (ukryty) -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        function deleteUser(id, username) {
            if (confirm('Czy na pewno chcesz usunąć użytkownika "' + username + '"?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>