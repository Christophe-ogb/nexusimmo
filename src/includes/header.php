<?php

/**
 * Affiche la navigation commune à toutes les pages du site.
 * $base correspond au chemin vers le dossier public depuis la page courante.
 */
function renderSiteHeader(string $base = '', string $area = 'public'): void
{
    $home = $base . 'index.php';
    ?>
    <nav class="nav" aria-label="Navigation principale">
      <a href="<?= $home ?>" class="logo" aria-label="NEXUS Immobilier, accueil">NEX<span>US</span></a>
      <ul class="nav-links">
        <li><a href="<?= $home ?>">Accueil</a></li>
        <?php if ($area === 'public'): ?>
          <li><a href="<?= $home ?>?type=location">Louer</a></li>
          <li><a href="<?= $home ?>?type=vente">Acheter</a></li>
          <li><a href="<?= $home ?>?category=parcelle">Parcelles</a></li>
        <?php elseif ($area === 'dashboard'): ?>
          <li><a href="index.php">Mes annonces</a></li>
          <li><a href="add-listing.php">Publier</a></li>
        <?php else: ?>
          <li><a href="index.php">Toutes les annonces</a></li>
          <li><a href="users.php">Utilisateurs</a></li>
        <?php endif; ?>
      </ul>
      <div class="nav-actions">
        <?php if ($area === 'public'): ?>
          <?php if (isLoggedIn()): ?>
            <a href="<?= $base . (isAdmin() ? 'admin/index.php' : 'dashboard/index.php') ?>" class="btn-ghost">Mon espace</a>
          <?php else: ?>
            <a href="<?= $base ?>login.php" class="btn-ghost">Connexion</a>
          <?php endif; ?>
          <a href="<?= $base ?>dashboard/add-listing.php" class="btn-primary">Publier une annonce</a>
        <?php else: ?>
          <a href="<?= $base ?>logout.php" class="btn-ghost">Déconnexion</a>
        <?php endif; ?>
      </div>
    </nav>
    <?php
}
