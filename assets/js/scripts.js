document.addEventListener('DOMContentLoaded', function() {
  // Плавная прокрутка для якорей
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth'
        });
      }
    });
  });

  // Инициализация всех модальных окон
  document.querySelectorAll('[data-modal]').forEach(trigger => {
    const modalId = trigger.getAttribute('data-modal');
    const modal = document.getElementById(modalId);
    
    if (modal) {
      trigger.addEventListener('click', () => {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      });
      
      modal.querySelector('.close').addEventListener('click', () => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      });
    }
  });

  // Закрытие модальных окон при клике вне контента
  window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
      e.target.style.display = 'none';
      document.body.style.overflow = '';
    }
  });

  // Валидация форм
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
      let valid = true;
      
      this.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
          input.style.borderColor = 'var(--danger-color)';
          valid = false;
        } else {
          input.style.borderColor = '';
        }
      });
      
      if (!valid) {
        e.preventDefault();
        alert('Пожалуйста, заполните все обязательные поля');
      }
    });
  });
  
  // Смена главного изображения в галерее
  document.querySelectorAll('.thumbnails img').forEach(thumb => {
    thumb.addEventListener('click', function() {
      const mainImg = document.querySelector('.main-image img');
      if (mainImg) {
        mainImg.src = this.src;
      }
    });
  });
});