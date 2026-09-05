<?php
// Chemin : src/includes/upload.php

require_once __DIR__ . '/../config/config.php';

const ALLOWED_IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const ALLOWED_VIDEO_TYPES = ['video/mp4' => 'mp4', 'video/quicktime' => 'mov', 'video/webm' => 'webm'];

/**
 * Traite un tableau $_FILES['champ'] (multiple) et retourne les fichiers valides
 * sous la forme [['type' => 'image'|'video', 'path' => 'uploads/...'], ...].
 * Les fichiers invalides (mauvais type, trop lourds, erreur d'upload) sont ignorés
 * et comptabilisés dans $skipped, sans bloquer les fichiers valides.
 */
function handleMultipleUploads(array $filesField, int &$skipped = 0): array
{
    $results = [];
    $count   = count($filesField['name'] ?? []);

    if ($count > MAX_UPLOAD_FILES) {
        $skipped += $count - MAX_UPLOAD_FILES;
        $count = MAX_UPLOAD_FILES;
    }

    $totalSize = 0;

    for ($i = 0; $i < $count; $i++) {
        if ($filesField['error'][$i] !== UPLOAD_ERR_OK) {
            if ($filesField['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $skipped++;
            }
            continue;
        }
        $size = (int) $filesField['size'][$i];
        if ($size > MAX_UPLOAD_SIZE || ($totalSize + $size) > MAX_UPLOAD_TOTAL_SIZE) {
            $skipped++;
            continue;
        }

        $totalSize += $size;

        $tmpPath = $filesField['tmp_name'][$i];
        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($tmpPath); // vrai type détecté, jamais celui envoyé par le navigateur

        if (isset(ALLOWED_IMAGE_TYPES[$mime])) {
            $ext  = ALLOWED_IMAGE_TYPES[$mime];
            $dir  = UPLOAD_IMAGES_DIR;
            $type = 'image';
        } elseif (isset(ALLOWED_VIDEO_TYPES[$mime])) {
            $ext  = ALLOWED_VIDEO_TYPES[$mime];
            $dir  = UPLOAD_VIDEOS_DIR;
            $type = 'video';
        } else {
            $skipped++; // type non autorisé (ex: .php déguisé en .jpg)
            continue;
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Nom aléatoire : jamais le nom d'origine (évite collisions, traversal, injection)
        $filename    = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $dir . $filename;

        if (move_uploaded_file($tmpPath, $destination)) {
            $results[] = [
                'type' => $type,
                'path' => ($type === 'image' ? 'uploads/images/' : 'uploads/videos/') . $filename,
            ];
        } else {
            $skipped++;
        }
    }

    return $results;
}
?>
