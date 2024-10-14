document.addEventListener('DOMContentLoaded', () => {
  const articleCards = document.querySelectorAll('.article-card');
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('fade-in');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  articleCards.forEach(card => {
    observer.observe(card);
  });
});
