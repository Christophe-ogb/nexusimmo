<?php
// Chemin : public/index.php

require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/header.php';
require_once __DIR__ . '/../src/includes/footer.php';
require_once __DIR__ . '/../src/models/Listing.php';

$localities = Listing::getLocalities();

$filters = array_filter([
    'type'        => $_GET['type'] ?? '',
    'category'    => $_GET['category'] ?? '',
    'locality_id' => $_GET['locality_id'] ?? '',
    'budget_max'  => $_GET['budget_max'] ?? '',
    'sort'        => $_GET['sort'] ?? '',
]);

$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$listingPage = Listing::searchPage($filters, $requestedPage, 12);
$listings = $listingPage['items'];
$totalListings = $listingPage['total'];
$currentPage = $listingPage['page'];
$totalPages = $listingPage['total_pages'];
$hasActiveFilters = (bool) array_filter($filters, fn($k) => $k !== 'sort', ARRAY_FILTER_USE_KEY);
$pageUrl = static function (int $page) use ($filters): string {
    $query = array_filter($filters, static fn($value) => $value !== '');
    if ($page > 1) {
        $query['page'] = $page;
    }
    return 'index.php' . ($query ? '?' . http_build_query($query) : '');
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NEXUS — Annonces immobilières</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=9">
</head>
<body>

<?php renderSiteHeader(); ?>

<section class="hero">
  <div class="hero-inner">
    <h1>Trouvez votre <span class="accent">chez-vous</span><br>idéal au Bénin</h1>
    <p class="hero-sub">Maisons et parcelles à louer ou à vendre, vérifiées et mises à jour chaque jour.</p>

    <form class="filters" method="GET" action="index.php">
      <div class="toggle-group">
        <?php foreach (['' => 'Tous', 'location' => 'À louer', 'vente' => 'À vendre'] as $val => $label): ?>
          <label class="toggle-radio">
            <input type="radio" name="type" value="<?= e($val) ?>" <?= ($filters['type'] ?? '') === $val ? 'checked' : '' ?> onchange="this.form.submit()">
            <span class="toggle"><?= e($label) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <select class="select" name="category" onchange="this.form.submit()">
        <option value="">Toutes catégories</option>
        <option value="maison" <?= ($filters['category'] ?? '') === 'maison' ? 'selected' : '' ?>>Maison</option>
        <option value="parcelle" <?= ($filters['category'] ?? '') === 'parcelle' ? 'selected' : '' ?>>Parcelle</option>
      </select>

      <select class="select" name="locality_id" onchange="this.form.submit()">
        <option value="">Toutes localités</option>
        <?php foreach ($localities as $loc): ?>
          <option value="<?= (int) $loc['id'] ?>" <?= (string) ($filters['locality_id'] ?? '') === (string) $loc['id'] ? 'selected' : '' ?>><?= e($loc['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <select class="select" name="budget_max" onchange="this.form.submit()">
        <option value="">Budget max</option>
        <option value="200000" <?= ($filters['budget_max'] ?? '') === '200000' ? 'selected' : '' ?>>≤ 200 000 FCFA</option>
        <option value="1000000" <?= ($filters['budget_max'] ?? '') === '1000000' ? 'selected' : '' ?>>≤ 1 000 000 FCFA</option>
        <option value="20000000" <?= ($filters['budget_max'] ?? '') === '20000000' ? 'selected' : '' ?>>≤ 20 000 000 FCFA</option>
        <option value="100000000" <?= ($filters['budget_max'] ?? '') === '100000000' ? 'selected' : '' ?>>≤ 100 000 000 FCFA</option>
      </select>

      <select class="select" name="sort" onchange="this.form.submit()">
        <option value="recent" <?= ($filters['sort'] ?? 'recent') === 'recent' ? 'selected' : '' ?>>Plus récents</option>
        <option value="price_asc" <?= ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
        <option value="price_desc" <?= ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
      </select>

      <?php if ($hasActiveFilters): ?>
        <a href="index.php" class="reset-link">Réinitialiser</a>
      <?php endif; ?>

      <button type="submit" class="search-btn" aria-label="Rechercher">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      </button>
    </form>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <div class="section-title"><?= $hasActiveFilters ? $totalListings . ' résultat' . ($totalListings > 1 ? 's' : '') : 'Annonces récentes' ?></div>
  </div>

  <div class="grid">
    <?php if (!$listings): ?>
      <p class="no-results">Aucune annonce ne correspond à ta recherche pour le moment.</p>
    <?php endif; ?>
    <?php foreach ($listings as $listing): ?>
      <?php $isNew = (new DateTime($listing['created_at']))->diff(new DateTime())->days < 7; ?>
      <div class="card" data-href="annonce.php?id=<?= (int) $listing['id'] ?>">
        <div class="card-media">
          <?php if ($listing['media']): ?>
            <?php foreach ($listing['media'] as $i => $media): ?>
              <?php if ($media['media_type'] === 'video'): ?>
                <video class="<?= $i === 0 ? 'active' : '' ?>" src="<?= e($media['file_path']) ?>" muted loop playsinline preload="metadata" aria-label="Vidéo de <?= e($listing['title']) ?>"></video>
              <?php else: ?>
                <img class="<?= $i === 0 ? 'active' : '' ?>" src="<?= e($media['file_path']) ?>" alt="<?= e($listing['title']) ?>" loading="lazy">
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="card-no-image">Pas de photo</div>
          <?php endif; ?>

          <?php if ($isNew): ?>
            <span class="badge new">Nouveau</span>
          <?php elseif ($listing['is_featured']): ?>
            <span class="badge hot">Populaire</span>
          <?php endif; ?>

          <span class="type-tag"><?= $listing['listing_type'] === 'location' ? 'À louer' : 'À vendre' ?></span>

          <?php if (count($listing['media']) > 1): ?>
            <button type="button" class="arrow prev" data-dir="-1" aria-label="Photo précédente">‹</button>
            <button type="button" class="arrow next" data-dir="1" aria-label="Photo suivante">›</button>
            <div class="dots">
              <?php foreach ($listing['media'] as $i => $_): ?>
                <button type="button" class="dot <?= $i === 0 ? 'active' : '' ?>" aria-label="Photo <?= $i + 1 ?>"></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <h3 class="card-title"><?= e($listing['title']) ?></h3>
          <div class="card-loc">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?= e($listing['locality_name']) ?>
          </div>
          <div class="card-footer">
            <div class="price"><?= number_format((float) $listing['price'], 0, ',', ' ') ?> <small>FCFA<?= $listing['listing_type'] === 'location' ? ' / mois' : '' ?></small></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <?php $firstPage = max(1, $currentPage - 2); $lastPage = min($totalPages, $currentPage + 2); ?>
    <nav class="pagination" aria-label="Pagination des annonces">
      <?php if ($currentPage > 1): ?>
        <a href="<?= e($pageUrl($currentPage - 1)) ?>" class="pagination-link pagination-prev">Précédent</a>
      <?php endif; ?>
      <?php if ($firstPage > 1): ?>
        <a href="<?= e($pageUrl(1)) ?>" class="pagination-link">1</a>
        <?php if ($firstPage > 2): ?><span class="pagination-gap">…</span><?php endif; ?>
      <?php endif; ?>
      <?php for ($page = $firstPage; $page <= $lastPage; $page++): ?>
        <a href="<?= e($pageUrl($page)) ?>" class="pagination-link <?= $page === $currentPage ? 'is-current' : '' ?>" <?= $page === $currentPage ? 'aria-current="page"' : '' ?>><?= $page ?></a>
      <?php endfor; ?>
      <?php if ($lastPage < $totalPages): ?>
        <?php if ($lastPage < $totalPages - 1): ?><span class="pagination-gap">…</span><?php endif; ?>
        <a href="<?= e($pageUrl($totalPages)) ?>" class="pagination-link"><?= $totalPages ?></a>
      <?php endif; ?>
      <?php if ($currentPage < $totalPages): ?>
        <a href="<?= e($pageUrl($currentPage + 1)) ?>" class="pagination-link pagination-next">Suivant</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</section>

<?php renderSiteFooter(); ?>
<script src="assets/js/main.js?v=2"></script>

</body>
</html>
