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

// Obsługa formularzy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $prompt = trim($_POST['prompt']);
                
                if (!empty($name) && !empty($prompt)) {
                    try {
                        $stmt = $db->prepare("INSERT INTO categories (name, prompt) VALUES (?, ?)");
                        if ($stmt->execute([$name, $prompt])) {
                            $message = "Kategoria została dodana pomyślnie.";
                            $messageType = "success";
                        }
                    } catch (Exception $e) {
                        $message = "Błąd podczas dodawania kategorii: " . $e->getMessage();
                        $messageType = "danger";
                    }
                } else {
                    $message = "Wypełnij wszystkie pola.";
                    $messageType = "warning";
                }
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $name = trim($_POST['name']);
                $prompt = trim($_POST['prompt']);
                $active = isset($_POST['active']) ? 1 : 0;
                
                if (!empty($name) && !empty($prompt)) {
                    try {
                        $stmt = $db->prepare("UPDATE categories SET name = ?, prompt = ?, active = ? WHERE id = ?");
                        if ($stmt->execute([$name, $prompt, $active, $id])) {
                            $message = "Kategoria została zaktualizowana.";
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
                try {
                    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $message = "Kategoria została usunięta.";
                        $messageType = "success";
                    }
                } catch (Exception $e) {
                    $message = "Błąd podczas usuwania: " . $e->getMessage();
                    $messageType = "danger";
                }
                break;
        }
    }
}

// Pobierz kategorie
try {
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
    $message = "Błąd podczas pobierania kategorii: " . $e->getMessage();
    $messageType = "danger";
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategorie - Domain Monitor</title>
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
                            <a class="nav-link active" href="categories.php">
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
                    <h1 class="h2"><i class="fas fa-tags"></i> Zarządzanie kategoriami</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Dodaj kategorię
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Lista kategorii -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5>Brak kategorii</h5>
                            <p class="text-muted">Nie ma jeszcze żadnych kategorii.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nazwa</th>
                                        <th>Prompt</th>
                                        <th>Status</th>
                                        <th>Data utworzenia</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                        <td>
                                            <small><?php echo htmlspecialchars(substr($category['prompt'], 0, 100)); ?>...</small>
                                        </td>
                                        <td>
                                            <?php if ($category['active']): ?>
                                                <span class="badge bg-success">Aktywna</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nieaktywna</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

    <!-- Modal dodawania kategorii -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Dodaj nową kategorię</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Nazwa kategorii</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prompt dla Gemini API</label>
                            <textarea class="form-control" name="prompt" rows="5" required 
                                      placeholder="Przeanalizuj przesłaną listę domen, czy na tej liście znajdują się domeny, które..."></textarea>
                            <div class="form-text">
                                Prompt powinien instruować AI, jak analizować domeny dla tej kategorii.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Dodaj kategorię</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal edycji kategorii -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edytuj kategorię</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nazwa kategorii</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prompt dla Gemini API</label>
                            <textarea class="form-control" name="prompt" id="edit_prompt" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="active" id="edit_active">
                                <label class="form-check-label" for="edit_active">
                                    Kategoria aktywna
                                </label>
                            </div>
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
        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_prompt').value = category.prompt;
            document.getElementById('edit_active').checked = category.active == 1;
            
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }
        
        function deleteCategory(id, name) {
            if (confirm('Czy na pewno chcesz usunąć kategorię "' + name + '"?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>