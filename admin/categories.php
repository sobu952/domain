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
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
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