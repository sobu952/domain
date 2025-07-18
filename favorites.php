<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Pobierz ulubione domeny użytkownika
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    $stmt = $db->prepare("
        SELECT d.*, 
               GROUP_CONCAT(DISTINCT c.name) as categories,
               GROUP_CONCAT(DISTINCT da.description SEPARATOR ' | ') as descriptions,
               fd.created_at as favorited_at
        FROM favorite_domains fd
        JOIN domains d ON fd.domain_id = d.id
        LEFT JOIN domain_analysis da ON d.id = da.domain_id
        LEFT JOIN categories c ON da.category_id = c.id
        WHERE fd.user_id = ?
        GROUP BY d.id
        ORDER BY fd.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $perPage, $offset]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM favorite_domains WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $totalFavorites = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    error_log("Error in favorites.php: " . $e->getMessage());
    $favorites = [];
    $totalFavorites = 0;
}

$totalPages = ceil($totalFavorites / $perPage);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulubione domeny - Domain Monitor</title>
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
                    <h1 class="h2"><i class="fas fa-heart"></i> Ulubione domeny</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="domains/" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Dodaj więcej
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statystyki -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fas fa-heart"></i>
                            Masz <strong><?php echo number_format($totalFavorites); ?></strong> ulubionych domen
                        </div>
                    </div>
                </div>

                <!-- Lista ulubionych domen -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($favorites)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                            <h5>Brak ulubionych domen</h5>
                            <p class="text-muted">Nie masz jeszcze żadnych ulubionych domen.</p>
                            <a href="domains/" class="btn btn-primary">
                                <i class="fas fa-search"></i> Przeglądaj domeny
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Domena</th>
                                        <th>Data dodania</th>
                                        <th>Data rejestracji</th>
                                        <th>Kategorie</th>
                                        <th>Status</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($favorites as $domain): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($domain['domain_name']); ?></strong>
                                            <?php if ($domain['descriptions']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($domain['descriptions'], 0, 100)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($domain['favorited_at'])); ?></td>
                                        <td>
                                            <?php 
                                            $regDate = strtotime($domain['registration_available_date']);
                                            $today = time();
                                            $daysLeft = ceil(($regDate - $today) / (60 * 60 * 24));
                                            ?>
                                            <?php echo date('d.m.Y', $regDate); ?>
                                            <?php if ($daysLeft <= 7 && $daysLeft > 0): ?>
                                                <br><small class="text-warning"><i class="fas fa-clock"></i> <?php echo $daysLeft; ?> dni</small>
                                            <?php elseif ($daysLeft <= 0): ?>
                                                <br><small class="text-success"><i class="fas fa-check"></i> Dostępna</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($domain['categories']): ?>
                                                <?php foreach (explode(',', $domain['categories']) as $cat): ?>
                                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars(trim($cat)); ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Brak kategorii</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger"><i class="fas fa-heart"></i> Ulubiona</span>
                                            <?php if ($daysLeft <= 1 && $daysLeft >= 0): ?>
                                                <br><span class="badge bg-warning mt-1"><i class="fas fa-bell"></i> Przypomnienie</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="domains/view.php?id=<?php echo $domain['id']; ?>" class="btn btn-sm btn-outline-primary" title="Zobacz szczegóły">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="btn btn-sm btn-warning toggle-favorite active" 
                                                        data-domain-id="<?php echo $domain['id']; ?>" 
                                                        title="Usuń z ulubionych">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                                <a href="https://<?php echo htmlspecialchars($domain['domain_name']); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Otwórz domenę">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginacja -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Paginacja ulubionych" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Poprzednia
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>