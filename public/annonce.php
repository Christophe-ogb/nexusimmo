<?php
// Chemin : public/annonce.php

require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/header.php';
require_once __DIR__ . '/../src/includes/footer.php';
require_once __DIR__ . '/../src/models/Listing.php';
require_once __DIR__ . '/../src/models/Media.php';

$id = (int) ($_GET['id'] ?? 0);
$listing = $id > 0 ? Listing::findById($id) : null;
$notFound = !$listing || $listing['status'] !== 'active';

if (!$notFound) {
    Listing::incrementViews($id);
    $media = Media::findByListing($id);

    $typeLabel = $listing['listing_type'] === 'location' ? 'À louer' : 'À vendre';
    $priceUnit = $listing['listing_type'] === 'location' ? ' / mois' : '';

    $waDigits  = preg_replace('/\D/', '', $listing['whatsapp_number']);
    $waMessage = "Bonjour, je suis intéressé(e) par votre annonce « {$listing['title']} » sur NEXUS.";
    $waUrl     = 'https://wa.me/' . $waDigits . '?text=' . urlencode($waMessage);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $notFound ? 'Annonce introuvable' : e($listing['title']) ?> — NEXUS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php renderSiteHeader(); ?>

<?php if ($notFound): ?>

  <div class="auth-wrap">
    <div class="auth-card not-found-card">
      <h1 class="auth-title">Annonce introuvable</h1>
      <p class="auth-sub">Cette annonce n'existe plus ou a été retirée de la vente.</p>
      <a href="index.php" class="btn-primary">Retour aux annonces</a>
    </div>
  </div>

<?php else: ?>

  <div class="breadcrumb">
    <a href="index.php">Accueil</a> / <a href="index.php?category=<?= e($listing['category']) ?>"><?= $listing['category'] === 'maison' ? 'Maisons' : 'Parcelles' ?></a> / <span class="current"><?= e($listing['title']) ?></span>
  </div>

  <div class="detail">
    <div>
      <div class="gallery-main" id="galleryMain">
        <span class="gallery-badge"><?= $typeLabel ?></span>
        <?php if ($media): ?>
          <?php foreach ($media as $i => $m): ?>
            <?php if ($m['media_type'] === 'image'): ?>
              <img class="<?= $i === 0 ? 'active' : '' ?>" src="<?= e($m['file_path']) ?>" alt="<?= e($listing['title']) ?>" loading="eager">
            <?php else: ?>
              <video class="<?= $i === 0 ? 'active' : '' ?>" src="<?= e($m['file_path']) ?>" controls></video>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="gallery-empty">Aucune photo disponible</div>
        <?php endif; ?>
      </div>

      <?php if (count($media) > 1): ?>
        <div class="gallery-thumbs" id="galleryThumbs">
          <?php foreach ($media as $i => $m): ?>
            <button type="button" class="thumb <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
              <?php if ($m['media_type'] === 'image'): ?>
                <img src="<?= e($m['file_path']) ?>" alt="<?= e($listing['title']) ?>" loading="lazy">
              <?php else: ?>
                <video src="<?= e($m['file_path']) ?>" muted></video>
              <?php endif; ?>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="detail-desc">
        <h2>Description</h2>
        <p><?= nl2br(e($listing['description'])) ?></p>
        <?php if ($listing['address_detail']): ?>
          <p class="detail-address"><strong>Adresse :</strong> <?= e($listing['address_detail']) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <aside class="sidebar">
      <span class="sidebar-type"><?= $typeLabel ?></span>
      <h1><?= e($listing['title']) ?></h1>
      <div class="sidebar-loc">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <?= e($listing['locality_name']) ?>
      </div>
      <div class="sidebar-price"><?= number_format((float) $listing['price'], 0, ',', ' ') ?> <small>FCFA<?= $priceUnit ?></small></div>
      <div class="sidebar-divider"></div>
      <a href="tel:<?= e($listing['phone_call']) ?>" class="btn-call">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        Appeler le vendeur
      </a>
      <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener" class="btn-whatsapp">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm0 18.06c-1.5 0-2.97-.4-4.25-1.16l-.3-.18-3.12.82.83-3.04-.2-.31a8.07 8.07 0 0 1-1.24-4.28c0-4.47 3.64-8.1 8.1-8.1 4.47 0 8.1 3.63 8.1 8.1s-3.63 8.15-8.1 8.15zm4.44-6.07c-.24-.12-1.43-.7-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.92-1.18-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.01-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.32-.75-1.81-.2-.48-.4-.41-.55-.42-.14-.01-.3-.01-.46-.01-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2 0 1.18.86 2.32.98 2.48.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.18 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28z"/></svg>
        Contacter sur WhatsApp
      </a>
    </aside>
  </div>

<?php endif; ?>

<?php renderSiteFooter(); ?>

<?php if (!$notFound && count($media) > 1): ?>
<script>
document.querySelectorAll('#galleryThumbs .thumb').forEach((thumb, i) => {
  thumb.addEventListener('click', () => {
    document.querySelectorAll('#galleryThumbs .thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
    document.querySelectorAll('#galleryMain img, #galleryMain video').forEach((el, n) => el.classList.toggle('active', n === i));
  });
});
</script>
<?php endif; ?>
<script src="assets/js/main.js"></script>

</body>
</html>
