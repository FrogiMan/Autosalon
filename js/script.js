document.addEventListener('DOMContentLoaded', function () {
    // Валидация формы тест-драйва
 const testDriveForm = document.querySelector('#test-drive-form');
    if (testDriveForm) {
        testDriveForm.addEventListener('submit', function(e) {
            const name = document.querySelector('#name').value;
            const phone = document.querySelector('#phone').value;
            const requestDate = document.querySelector('#request_date').value;

            if (!/^[a-zA-Zа-яА-Я\s]{2,100}$/u.test(name)) {
                alert('Имя должно содержать только буквы и быть от 2 до 100 символов.');
                e.preventDefault();
            } else if (!/^\+?[1-9]\d{1,14}$/.test(phone)) {
                alert('Введите корректный номер телефона (например, +79991234567).');
                e.preventDefault();
            } else if (requestDate) {
                const selectedDate = new Date(requestDate);
                const now = new Date();
                if (selectedDate <= now) {
                    alert('Дата тест-драйва должна быть в будущем.');
                    e.preventDefault();
                }
            }
        });
    }

    // Валидация формы отзыва
    const reviewForm = document.querySelector('form[action="submit_review.php"]');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function (e) {
            const carId = reviewForm.querySelector('#car_id').value;
            const rating = reviewForm.querySelector('#rating').value;
            const comment = reviewForm.querySelector('#comment').value.trim();

            if (!carId) {
                e.preventDefault();
                alert('Выберите автомобиль.');
                return;
            }

            if (!rating || rating < 1 || rating > 5) {
                e.preventDefault();
                alert('Выберите рейтинг от 1 до 5.');
                return;
            }

            if (!comment || comment.length < 10) {
                e.preventDefault();
                alert('Комментарий должен содержать не менее 10 символов.');
                return;
            }
        });
    }

    // Валидация формы обратной связи
    const contactForm = document.querySelector('form[action="contacts.php"]');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            const name = contactForm.querySelector('#name').value.trim();
            const email = contactForm.querySelector('#email').value.trim();
            const message = contactForm.querySelector('#message').value.trim();

            if (!name.match(/^[a-zA-Zа-яА-Я\s]{2,100}$/)) {
                e.preventDefault();
                alert('Имя должно содержать только буквы и пробелы, от 2 до 100 символов.');
                return;
            }

            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                e.preventDefault();
                alert('Введите корректный email.');
                return;
            }

            if (!message || message.length < 10) {
                e.preventDefault();
                alert('Сообщение должно содержать не менее 10 символов.');
                return;
            }
        });
    }

     // Example: Validate payment form
    const paymentForm = document.querySelector('#payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            const cardNumber = document.querySelector('#card_number').value;
            const expiry = document.querySelector('#expiry').value;
            const cvv = document.querySelector('#cvv').value;
            if (!/^\d{16}$/.test(cardNumber)) {
                alert('Номер карты должен содержать 16 цифр.');
                e.preventDefault();
            } else if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                alert('Срок действия должен быть в формате MM/YY.');
                e.preventDefault();
            } else if (!/^\d{3}$/.test(cvv)) {
                alert('CVV должен содержать 3 цифры.');
                e.preventDefault();
            }
        });
    }

    // Подтверждение удаления в админ-панели
    const deleteLinks = document.querySelectorAll('a[href*="action=delete"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            if (!confirm('Вы уверены, что хотите удалить этот элемент?')) {
                e.preventDefault();
            }
        });
    });

    // Динамическое обновление фильтров в каталоге
    const filterForm = document.querySelector('.filters form');
    if (filterForm) {
        const minPrice = filterForm.querySelector('#min_price');
        const maxPrice = filterForm.querySelector('#max_price');

        filterForm.addEventListener('submit', function (e) {
            const min = parseFloat(minPrice.value);
            const max = parseFloat(maxPrice.value);
            if (min && max && min > max) {
                e.preventDefault();
                alert('Минимальная цена не может быть больше максимальной.');
            }
        });
    }
});