document.querySelectorAll('[data-password-toggle]').forEach((button) => {
  button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.passwordToggle);
    if (!input) return;

    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(isHidden));
    button.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    button.setAttribute('title', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    button.classList.toggle('is-visible', isHidden);
  });
});

document.querySelectorAll('.card[data-href]').forEach((card) => {
  card.addEventListener('click', (event) => {
    if (event.target.closest('button, a')) return;
    window.location.href = card.dataset.href;
  });
});

document.querySelectorAll('.card-media').forEach((media) => {
  const items = [...media.querySelectorAll('img, video')];
  const dots = [...media.querySelectorAll('.dot')];
  if (items.length < 2) return;

  let index = items.findIndex((item) => item.classList.contains('active'));
  const show = (nextIndex) => {
    index = (nextIndex + items.length) % items.length;
    items.forEach((item, itemIndex) => {
      const isActive = itemIndex === index;
      item.classList.toggle('active', isActive);
      if (item.tagName === 'VIDEO') {
        if (isActive) item.play().catch(() => {});
        else item.pause();
      }
    });
    dots.forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === index));
  };

  media.querySelectorAll('.arrow').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      show(index + Number(button.dataset.dir));
    });
  });
  dots.forEach((dot, dotIndex) => dot.addEventListener('click', (event) => {
    event.stopPropagation();
    show(dotIndex);
  }));
});

document.querySelectorAll('#galleryThumbs .thumb').forEach((thumb, index) => {
  thumb.addEventListener('click', () => {
    document.querySelectorAll('#galleryThumbs .thumb').forEach((item) => item.classList.remove('active'));
    thumb.classList.add('active');
    document.querySelectorAll('#galleryMain img, #galleryMain video').forEach((item, itemIndex) => {
      item.classList.toggle('active', itemIndex === index);
      if (item.tagName === 'VIDEO' && itemIndex !== index) item.pause();
    });
  });
});
