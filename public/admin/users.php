<?php
// Chemin : public/admin/users.php

require_once __DIR__ . '/../../src/middleware/admin.php';
require_once __DIR__ . '/../../src/includes/header.php';
require_once __DIR__ . '/../../src/includes/footer.php';
require_once __DIR__ . '/../../src/models/User.php';

$csrfToken = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Requête non autorisée. Actualise la page puis réessaie.');
    } elseif ($userId === currentUserId()) {
        setFlash('error', 'Tu ne peux pas te désactiver toi-même.');
    } elseif ($userId > 0 && in_array($action, ['activate', 'deactivate'], true)) {
        User::setActive($userId, $action === 'activate');
        setFlash('success', $action === 'activate' ? 'Utilisateur réactivé.' : 'Utilisateur désactivé.');
    }

    redirect('users.php');
}

$users = User::findAll();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Utilisateurs — NEXUS Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<?php renderSiteHeader('../', 'admin'); ?>

<div class="dash-wrap">
  <div class="dash-head">
    <h1>Utilisateurs — <?= count($users) ?></h1>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
  <?php endif; ?>

  <div class="listing-table">
    <?php foreach ($users as $u): ?>
      <div class="listing-row user-row">
        <div class="lr-info">
          <div class="lr-title">
            <?= e($u['email']) ?>
            <?php if ($u['role'] === 'admin'): ?><span class="role-tag">Admin</span><?php endif; ?>
          </div>
          <div class="lr-meta">
            <?= e($u['phone']) ?> · <?= (int) $u['listing_count'] ?> annonce<?= $u['listing_count'] > 1 ? 's' : '' ?> · inscrit le <?= (new DateTime($u['created_at']))->format('d/m/Y') ?>
          </div>
        </div>
        <div class="lr-status">
          <span class="status-badge <?= $u['is_active'] ? 'status-active' : 'status-archived' ?>"><?= $u['is_active'] ? 'Actif' : 'Désactivé' ?></span>
        </div>
        <div class="lr-actions">
          <?php if ((int) $u['id'] === currentUserId()): ?>
            <span class="form-hint">C'est toi</span>
          <?php else: ?>
            <form method="POST" action="users.php" onsubmit="return confirm('<?= $u['is_active'] ? 'Désactiver' : 'Réactiver' ?> cet utilisateur ?');">
              <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
              <input type="hidden" name="action" value="<?= $u['is_active'] ? 'deactivate' : 'activate' ?>">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
              <button type="submit" class="btn-small <?= $u['is_active'] ? 'btn-danger' : '' ?>"><?= $u['is_active'] ? 'Désactiver' : 'Réactiver' ?></button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php renderSiteFooter(); ?>
</body>
</html>
