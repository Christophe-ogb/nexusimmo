<?php
// Chemin : public/admin/index.php

require_once __DIR__ . '/../../src/middleware/admin.php';
require_once __DIR__ . '/../../src/includes/header.php';
require_once __DIR__ . '/../../src/includes/footer.php';
require_once __DIR__ . '/../../src/models/Listing.php';

$listings = Listing::findAllForAdmin();
$flash = getFlash();

$statusLabels = [
    'active'      => ['label' => 'En ligne',    'class' => 'status-active'],
    'sold_rented' => ['label' => 'Vendu / Loué', 'class' => 'status-sold'],
    'archived'    => ['label' => 'Archivée',     'class' => 'status-archived'],
];
$typeLabels = ['location' => 'À louer', 'vente' => 'À vendre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administration — NEXUS</title>
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
    <h1>Administration — <?= count($listings) ?> annonce<?= count($listings) > 1 ? 's' : '' ?></h1>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
  <?php endif; ?>

  <?php if (!$listings): ?>
    <div class="empty-state"><p>Aucune annonce publiée pour le moment.</p></div>
  <?php else: ?>
    <div class="listing-table">
      <?php foreach ($listings as $listing): ?>
        <div class="listing-row">
          <div class="lr-media">
            <?php if ($listing['cover_image']): ?>
              <img src="../<?= e($listing['cover_image']) ?>" alt="">
            <?php else: ?>
              <div class="lr-media-placeholder"></div>
            <?php endif; ?>
          </div>
          <div class="lr-info">
            <div class="lr-title"><?= e($listing['title']) ?></div>
            <div class="lr-meta"><?= e($typeLabels[$listing['listing_type']]) ?> · <?= e($listing['locality_name']) ?> · <?= e($listing['seller_email']) ?></div>
            <div class="lr-price"><?= number_format((float) $listing['price'], 0, ',', ' ') ?> FCFA</div>
          </div>
          <div class="lr-status">
            <span class="status-badge <?= $statusLabels[$listing['status']]['class'] ?>"><?= $statusLabels[$listing['status']]['label'] ?></span>
          </div>
          <div class="lr-actions">
            <a href="../annonce.php?id=<?= (int) $listing['id'] ?>" target="_blank" class="btn-small">Voir</a>
            <form method="POST" action="delete-listing.php" onsubmit="return confirm('Supprimer définitivement cette annonce ?');">
              <input type="hidden" name="listing_id" value="<?= (int) $listing['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
              <button type="submit" class="btn-small btn-danger">Supprimer</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php renderSiteFooter(); ?>
</body>
</html>
