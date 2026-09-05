<?php
// Chemin : public/dashboard/add-listing.php

require_once __DIR__ . '/../../src/middleware/auth.php';
require_once __DIR__ . '/../../src/includes/header.php';
require_once __DIR__ . '/../../src/includes/footer.php';
require_once __DIR__ . '/../../src/models/Listing.php';
require_once __DIR__ . '/../../src/models/Media.php';
require_once __DIR__ . '/../../src/includes/upload.php';

$localities  = Listing::getLocalities();
$errors      = [];
$csrfToken   = csrfToken();

// Les 3 seuls choix prévus au cahier des charges (pas de "Parcelle à louer")
$typeOptions = [
    'location:maison' => 'Maison à louer',
    'vente:maison'    => 'Maison à vendre',
    'vente:parcelle'  => 'Parcelle à vendre',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeKey     = $_POST['type_key'] ?? '';
    $localityId  = (int) ($_POST['locality_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $address     = trim($_POST['address_detail'] ?? '');
    $phoneCall   = trim($_POST['phone_call'] ?? '');
    $whatsapp    = trim($_POST['whatsapp_number'] ?? '');

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Requête non autorisée. Actualise la page puis réessaie.';
    }

    if (!isset($typeOptions[$typeKey])) {
        $errors[] = 'Choisis un type de bien.';
    }
    if ($localityId <= 0) {
        $errors[] = 'Choisis une localité.';
    }
    if ($title === '') {
        $errors[] = 'Le titre est obligatoire.';
    }
    if ($description === '') {
        $errors[] = 'La description est obligatoire.';
    }
    if (!is_numeric($price) || (float) $price <= 0) {
        $errors[] = 'Le prix doit être un nombre valide.';
    }
    if (!preg_match('/^\+229\d{10}$/', $phoneCall)) {
        $errors[] = "Le numéro d'appel doit contenir +229 suivi de 10 chiffres.";
    }
    if (!preg_match('/^\+229\d{10}$/', $whatsapp)) {
        $errors[] = 'Le numéro WhatsApp doit contenir +229 suivi de 10 chiffres.';
    }

    if (!$errors) {
        [$listingType, $category] = explode(':', $typeKey);

        $listingId = Listing::create([
            'user_id'         => currentUserId(),
            'locality_id'     => $localityId,
            'listing_type'    => $listingType,
            'category'        => $category,
            'title'           => $title,
            'description'     => $description,
            'price'           => $price,
            'address_detail'  => $address,
            'phone_call'      => $phoneCall,
            'whatsapp_number' => $whatsapp,
        ]);

        $skipped = 0;
        if (!empty($_FILES['media']['name'][0])) {
            $uploaded = handleMultipleUploads($_FILES['media'], $skipped);
            foreach ($uploaded as $position => $file) {
                Media::create($listingId, $file['type'], $file['path'], $position);
            }
        }

        setFlash('success', $skipped > 0
            ? 'Annonce publiée. Certains fichiers ont été ignorés (2 Mo par fichier, 4 Mo et 5 fichiers maximum).'
            : 'Annonce publiée avec succès.');
        redirect('index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Publier une annonce — NEXUS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<?php renderSiteHeader('../', 'dashboard'); ?>

<div class="dash-wrap">
  <div class="dash-head">
    <h1>Publier une annonce</h1>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <ul>
        <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form class="form-card" method="POST" action="add-listing.php" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <div class="form-group">
      <label class="form-label">Type de bien</label>
      <div class="type-options">
        <?php foreach ($typeOptions as $key => $label): ?>
          <label class="type-option">
            <input type="radio" name="type_key" value="<?= e($key) ?>" <?= ($_POST['type_key'] ?? '') === $key ? 'checked' : '' ?> required>
            <span><?= e($label) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="locality_id">Localité</label>
        <select class="select" id="locality_id" name="locality_id" required>
          <option value="">Choisir…</option>
          <?php foreach ($localities as $loc): ?>
            <option value="<?= (int) $loc['id'] ?>" <?= (int) ($_POST['locality_id'] ?? 0) === (int) $loc['id'] ? 'selected' : '' ?>><?= e($loc['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="price">Prix (FCFA)</label>
        <input class="form-input" type="number" id="price" name="price" min="0" step="1" value="<?= e($_POST['price'] ?? '') ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="title">Titre de l'annonce</label>
      <input class="form-input" type="text" id="title" name="title" placeholder="Ex : Villa moderne 4 pièces" value="<?= e($_POST['title'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="address_detail">Adresse / quartier précis (optionnel)</label>
      <input class="form-input" type="text" id="address_detail" name="address_detail" value="<?= e($_POST['address_detail'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label class="form-label" for="description">Description</label>
      <textarea class="form-textarea" id="description" name="description" required><?= e($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="phone_call">Numéro d'appel</label>
        <input class="form-input" type="tel" id="phone_call" name="phone_call" placeholder="+229XXXXXXXXXX" value="<?= e($_POST['phone_call'] ?? '') ?>" required>
        <div class="form-hint">Format : +229 suivi de 10 chiffres.</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="whatsapp_number">Numéro WhatsApp</label>
        <input class="form-input" type="tel" id="whatsapp_number" name="whatsapp_number" placeholder="+229XXXXXXXXXX" value="<?= e($_POST['whatsapp_number'] ?? '') ?>" required>
        <div class="form-hint">Format : +229 suivi de 10 chiffres.</div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Photos et vidéos</label>
      <div class="upload-zone" id="uploadZone">
        <p>Glisse tes fichiers ici, ou clique pour parcourir<br>(5 fichiers maximum, 2 Mo chacun, 4 Mo au total)</p>
      </div>
      <input type="file" id="mediaInput" name="media[]" multiple accept="image/*,video/*" hidden>
      <div class="upload-preview" id="uploadPreview"></div>
    </div>

    <button type="submit" class="btn-primary auth-submit">Publier l'annonce</button>
  </form>
</div>

<script>
const fileInput = document.getElementById('mediaInput');
const zone = document.getElementById('uploadZone');
const preview = document.getElementById('uploadPreview');
let selectedFiles = [];
const maxFiles = 5;
const maxFileSize = 2 * 1024 * 1024;
const maxTotalSize = 4 * 1024 * 1024;

function addFiles(files) {
  const candidates = selectedFiles.concat(Array.from(files));
  const accepted = [];
  let total = 0;
  for (const file of candidates) {
    if (accepted.length >= maxFiles || file.size > maxFileSize || total + file.size > maxTotalSize) continue;
    accepted.push(file);
    total += file.size;
  }
  if (accepted.length < candidates.length) alert('Maximum : 5 fichiers, 2 Mo par fichier et 4 Mo au total.');
  selectedFiles = accepted;
  syncInput();
  renderPreview();
}

function renderPreview() {
  preview.innerHTML = '';
  selectedFiles.forEach((file, i) => {
    const thumb = document.createElement('div');
    thumb.className = 'upload-thumb';
    const url = URL.createObjectURL(file);
    thumb.innerHTML = file.type.startsWith('video')
      ? `<video src="${url}" muted></video>`
      : `<img src="${url}" alt="">`;
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'remove-thumb';
    removeBtn.textContent = '×';
    removeBtn.addEventListener('click', () => {
      selectedFiles.splice(i, 1);
      syncInput();
      renderPreview();
    });
    thumb.appendChild(removeBtn);
    preview.appendChild(thumb);
  });
}

function syncInput() {
  const dt = new DataTransfer();
  selectedFiles.forEach(file => dt.items.add(file));
  fileInput.files = dt.files;
}

zone.addEventListener('click', () => fileInput.click());
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
  e.preventDefault();
  zone.classList.remove('dragover');
  addFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', () => {
  addFiles(fileInput.files);
});
</script>

<?php renderSiteFooter(); ?>
</body>
</html>
