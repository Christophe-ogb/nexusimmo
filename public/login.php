<?php
// Chemin : public/login.php

require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/header.php';
require_once __DIR__ . '/../src/includes/footer.php';
require_once __DIR__ . '/../src/models/User.php';

if (isLoggedIn()) {
    redirect('dashboard/index.php');
}

$errors     = [];
$flash      = getFlash();
$identifier = '';
$csrfToken  = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Requête non autorisée. Actualise la page puis réessaie.';
    } else {
        $now = time();
        $loginAttempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'started_at' => $now, 'locked_until' => 0];

        if (($loginAttempts['locked_until'] ?? 0) > $now) {
            $errors[] = 'Trop de tentatives. Réessaie dans quelques minutes.';
        } else {
            if (($loginAttempts['started_at'] ?? 0) < ($now - 900)) {
                $loginAttempts = ['count' => 0, 'started_at' => $now, 'locked_until' => 0];
            }

            $identifier = trim($_POST['identifier'] ?? '');
            $password   = $_POST['password'] ?? '';
            $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
                ? User::findByEmail($identifier)
                : User::findByPhone($identifier);

            if (!$user || !User::verifyPassword($user, $password)) {
                $loginAttempts['count']++;
                if ($loginAttempts['count'] >= 5) {
                    $loginAttempts['locked_until'] = $now + 900;
                }
                $_SESSION['login_attempts'] = $loginAttempts;
                $errors[] = 'Email/téléphone ou mot de passe incorrect.';
            } elseif (!$user['is_active']) {
                $errors[] = 'Ce compte n’est pas activé. Vérifie ton e-mail ou contacte l’administrateur.';
            } else {
                unset($_SESSION['login_attempts']);
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                redirect($user['role'] === 'admin' ? 'admin/index.php' : 'dashboard/index.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — NEXUS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body>

<?php renderSiteHeader(); ?>

<div class="auth-wrap">
  <div class="auth-card">
    <h1 class="auth-title">Connexion</h1>
    <p class="auth-sub">Accède à ton espace vendeur NEXUS.</p>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
      <div class="form-group">
        <label class="form-label" for="identifier">Email ou téléphone</label>
        <input class="form-input" type="text" id="identifier" name="identifier" value="<?= e($identifier) ?>" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Mot de passe</label>
        <div class="password-field">
          <input class="form-input" type="password" id="password" name="password" required>
          <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Afficher le mot de passe" aria-pressed="false" title="Afficher le mot de passe">
            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.75"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn-primary auth-submit">Se connecter</button>
    </form>

    <div class="auth-footer">Pas encore de compte ? <a href="register.php">S'inscrire</a></div>
  </div>
</div>

<script>
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
  button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.passwordToggle);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(isHidden));
    button.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    button.setAttribute('title', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    button.classList.toggle('is-visible', isHidden);
  });
});
</script>

<?php renderSiteFooter(); ?>
</body>
</html>
