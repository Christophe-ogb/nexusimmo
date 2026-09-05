<?php
// Chemin : public/dashboard/edit-listing.php

require_once __DIR__ . '/../../src/middleware/auth.php';
require_once __DIR__ . '/../../src/includes/header.php';
require_once __DIR__ . '/../../src/includes/footer.php';
require_once __DIR__ . '/../../src/models/Listing.php';
require_once __DIR__ . '/../../src/models/Media.php';
require_once __DIR__ . '/../../src/includes/upload.php';

$id      = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$listing = $id > 0 ? Listing::findByIdForUser($id, currentUserId()) : null;

if (!$listing) {
    setFlash('error', 'Annonce introuvable.');
    redirect('index.php');
}

$localities  = Listing::getLocalities();
$errors      = [];
$csrfToken   = csrfToken();
$typeOptions = [
    'location:maison' => 'Maison à louer',
    'vente:maison'    => 'Maison à vendre',
    'vente:parcelle'  => 'Parcelle à vendre',
];
$currentTypeKey = $listing['listing_type'] . ':' . $listing['category'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeKey     = $_POST['type_key'] ?? '';
    $localityId  = (int) ($_POST['locality_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $address     = trim($_POST['address_detail'] ?? '');
    $phoneCall   = trim($_POST['phone_call'] ?? '');
    $whatsapp    = trim($_POST['whatsapp_number'] ?? '');
    $removeIds   = array_map('intval', $_POST['remove_media'] ?? []);

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Requête non autorisée. Actualise la page puis réessaie.';
    }

    if (!isset($typeOptions[$typeKey])) {
        $errors[] = 'Choisis un type de bien.';
    } else {
        $currentTypeKey = $typeKey;
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

        Listing::update($id, currentUserId(), [
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

        // Supprime les médias cochés (vérifie qu'ils appartiennent bien à cette annonce)
        foreach ($removeIds as $mediaId) {
            $mediaItem = Media::findById($mediaId);
            if ($mediaItem && (int) $mediaItem['listing_id'] === $id) {
                Media::delete($mediaId);
                $filePath = __DIR__ . '/../' . $mediaItem['file_path'];
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
        }

        // Ajoute les nouveaux fichiers à la suite des existants
        $skipped = 0;
        if (!empty($_FILES['media']['name'][0])) {
            $position = Media::nextPosition($id);
            $uploaded = handleMultipleUploads($_FILES['media'], $skipped);
            foreach ($uploaded as $file) {
                Media::create($id, $file['type'], $file['path'], $position);
                $position++;
            }
        }

        setFlash('success', $skipped > 0
            ? 'Annonce mise à jour. Certains fichiers ont été ignorés (2 Mo par fichier, 4 Mo et 5 fichiers maximum).'
            : 'Annonce mise à jour avec succès.');
        redirect('index.php');
    }

    // En cas d'erreur : on garde les valeurs soumises pour le ré-affichage
    $listing['locality_id']     = $localityId;
    $listing['title']           = $title;
    $listing['description']     = $description;
    $listing['price']           = $price;
    $listing['address_detail']  = $address;
    $listing['phone_call']      = $phoneCall;
    $listing['whatsapp_number'] = $whatsapp;
}

$media = Media::findByListing($id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modifier l'annonce — NEXUS</title>
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
    <h1>Modifier l'annonce</h1>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <ul>
        <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form class="form-card" method="POST" action="edit-listing.php" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <div class="form-group">
      <label class="form-label">Type de bien</label>
      <div class="type-options">
        <?php foreach ($typeOptions as $key => $label): ?>
          <label class="type-option">
            <input type="radio" name="type_key" value="<?= e($key) ?>" <?= $currentTypeKey === $key ? 'checked' : '' ?> required>
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
            <option value="<?= (int) $loc['id'] ?>" <?= (int) $listing['locality_id'] === (int) $loc['id'] ? 'selected' : '' ?>><?= e($loc['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="price">Prix (FCFA)</label>
        <input class="form-input" type="number" id="price" name="price" min="0" step="1" value="<?= e((string) $listing['price']) ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="title">Titre de l'annonce</label>
      <input class="form-input" type="text" id="title" name="title" value="<?= e($listing['title']) ?>" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="address_detail">Adresse / quartier précis (optionnel)</label>
      <input class="form-input" type="text" id="address_detail" name="address_detail" value="<?= e((string) $listing['address_detail']) ?>">
    </div>

    <div class="form-group">
      <label class="form-label" for="description">Description</label>
      <textarea class="form-textarea" id="description" name="description" required><?= e($listing['description']) ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="phone_call">Numéro d'appel</label>
        <input class="form-input" type="tel" id="phone_call" name="phone_call" value="<?= e($listing['phone_call']) ?>" required>
        <div class="form-hint">Format : +229 suivi de 10 chiffres.</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="whatsapp_number">Numéro WhatsApp</label>
        <input class="form-input" type="tel" id="whatsapp_number" name="whatsapp_number" value="<?= e($listing['whatsapp_number']) ?>" required>
        <div class="form-hint">Format : +229 suivi de 10 chiffres.</div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Photos et vidéos actuelles</label>
      <?php if ($media): ?>
        <div class="upload-preview">
          <?php foreach ($media as $m): ?>
            <label class="upload-thumb existing-thumb">
              <?php if ($m['media_type'] === 'image'): ?>
                <img src="../<?= e($m['file_path']) ?>" alt="">
              <?php else: ?>
                <video src="../<?= e($m['file_path']) ?>" muted></video>
              <?php endif; ?>
              <input type="checkbox" name="remove_media[]" value="<?= (int) $m['id'] ?>" class="remove-checkbox">
              <span class="remove-overlay">Supprimer</span>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="form-hint">Coche une photo/vidéo pour la supprimer à l'enregistrement.</div>
      <?php else: ?>
        <p class="form-hint">Aucune photo pour l'instant.</p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label class="form-label">Ajouter des photos/vidéos</label>
      <div class="upload-zone" id="uploadZone">
        <p>Glisse tes fichiers ici, ou clique pour parcourir (5 fichiers maximum, 2 Mo chacun, 4 Mo au total)</p>
      </div>
      <input type="file" id="mediaInput" name="media[]" multiple accept="image/*,video/*" hidden>
      <div class="upload-preview" id="uploadPreview"></div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-primary">Enregistrer les modifications</button>
      <a href="index.php" class="cancel-link">Annuler</a>
    </div>
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
