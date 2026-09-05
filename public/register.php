<?php

require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/header.php';
require_once __DIR__ . '/../src/includes/footer.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/services/EmailVerification.php';

if (isLoggedIn()) {
    redirect('dashboard/index.php');
}

$errors = [];
$email = '';
$phone = '';
$csrfToken = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = normalizeBeninPhone((string) ($_POST['phone'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Requête non autorisée. Actualise la page puis réessaie.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse e-mail invalide.';
    }
    if ($phone === null) {
        $errors[] = 'Le numéro doit contenir 10 chiffres.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }
    if (!$errors && User::findByEmail($email)) {
        $errors[] = 'Cet e-mail est déjà utilisé.';
    }
    if (!$errors && User::findByPhone($phone)) {
        $errors[] = 'Ce numéro est déjà utilisé.';
    }

    if (!$errors) {
        $userId = User::create($email, $phone, $password);
        $token = bin2hex(random_bytes(32));
        $expiresAt = new DateTimeImmutable('+24 hours');
        User::setVerificationToken($userId, hash('sha256', $token), $expiresAt);

        try {
            EmailVerification::send($email, $userId, $token);
            setFlash('success', 'Un lien d’activation a été envoyé à ton adresse e-mail.');
            redirect('login.php');
        } catch (Throwable $exception) {
            error_log('E-mail de vérification : ' . $exception->getMessage());
            User::delete($userId);
            $errors[] = 'Impossible d’envoyer l’e-mail de vérification. Réessaie plus tard.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inscription — NEXUS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body>
<?php renderSiteHeader(); ?>

<div class="auth-wrap"><div class="auth-card">
  <h1 class="auth-title">Créer un compte</h1>
  <p class="auth-sub">Rejoins NEXUS pour publier tes annonces.</p>

  <?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="POST" action="register.php" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <div class="form-group">
      <label class="form-label" for="email">E-mail</label>
      <input class="form-input" type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
      <div class="form-hint">Un lien d’activation sera envoyé à cette adresse.</div>
    </div>
    <div class="form-group">
      <label class="form-label" for="phone">Numéro de téléphone</label>
      <div class="phone-input-wrap"><span class="country-code">+229</span><input class="form-input" type="tel" id="phone" name="phone" inputmode="numeric" autocomplete="tel" pattern="[0-9]{10}" maxlength="10" placeholder="9700000000" value="<?= e($phone ? substr($phone, 4) : '') ?>" required></div>
      <div class="form-hint">Saisis les 10 chiffres de ton numéro béninois.</div>
    </div>
    <div class="form-group">
      <label class="form-label" for="password">Mot de passe</label>
      <div class="password-field"><input class="form-input" type="password" id="password" name="password" required minlength="8" autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle="password" aria-label="Afficher le mot de passe" aria-pressed="false" title="Afficher le mot de passe"><svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.75"/></svg></button></div>
    </div>
    <div class="form-group">
      <label class="form-label" for="password_confirm">Confirmer le mot de passe</label>
      <div class="password-field"><input class="form-input" type="password" id="password_confirm" name="password_confirm" required minlength="8" autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle="password_confirm" aria-label="Afficher le mot de passe" aria-pressed="false" title="Afficher le mot de passe"><svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.75"/></svg></button></div>
    </div>
    <button type="submit" class="btn-primary auth-submit">Créer mon compte</button>
  </form>
  <div class="auth-footer">Déjà inscrit ? <a href="login.php">Se connecter</a></div>
</div></div>

<script>
document.querySelectorAll('[data-password-toggle]').forEach((button) => button.addEventListener('click', () => {
  const input = document.getElementById(button.dataset.passwordToggle);
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  button.setAttribute('aria-pressed', String(isHidden));
  button.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
  button.setAttribute('title', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
  button.classList.toggle('is-visible', isHidden);
}));
</script>
<?php renderSiteFooter(); ?>
</body>
</html>
