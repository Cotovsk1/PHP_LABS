/**
 * Практична робота №2 — Варіант 6: Кулінарна книга
 * Клієнтська валідація (Крок 5) та взаємодія з localStorage (Крок 6)
 */

document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_KEY_UNIT = 'cookbook_preferred_unit';
    const STORAGE_KEY_DRAFT = 'cookbook_form_draft';

    // Елементи DOM
    const form = document.getElementById('recipeForm');
    const titleInput = document.getElementById('title');
    const cookTimeInput = document.getElementById('cookTimeMin');
    const ingredientsTextarea = document.getElementById('ingredients');
    const unitRadios = document.querySelectorAll('input[name="unit"]');
    const activeUnitIndicator = document.getElementById('activeUnitIndicator');
    const storagePreview = document.getElementById('storagePreview');
    const clearStorageBtn = document.getElementById('clearStorageBtn');
    const jsErrorAlert = document.getElementById('jsErrorAlert');
    const jsErrorMessage = document.getElementById('jsErrorMessage');
    const resetBtn = document.getElementById('resetBtn');

    // Повідомлення про помилки полів
    const titleError = document.getElementById('titleError');
    const cookTimeError = document.getElementById('cookTimeError');
    const ingredientsError = document.getElementById('ingredientsError');

    // ------------------------------------------------------------------------
    // КРОК 6: Реалізація сценарію localStorage (Варіант 6)
    // "запам'ятати обрану одиницю виміру (грами/унції) для полів інгредієнтів"
    // ------------------------------------------------------------------------

    const unitLabels = {
        'g': 'Грами (г / g)',
        'oz': 'Унції (унц / oz)'
    };

    /**
     * Оновлює візуальні індикатори та текст поточної одиниці виміру
     */
    function updateUnitDisplay(unitValue) {
        const readable = unitLabels[unitValue] || unitValue;
        if (activeUnitIndicator) {
            activeUnitIndicator.innerHTML = `Поточна міра: <strong>${readable}</strong>`;
        }
        if (storagePreview) {
            const saved = localStorage.getItem(STORAGE_KEY_UNIT);
            if (saved) {
                storagePreview.textContent = `${STORAGE_KEY_UNIT} = "${saved}" (${unitLabels[saved] || saved})`;
                storagePreview.classList.add('has-value');
            } else {
                storagePreview.textContent = 'немає запису (за замовчуванням: грами)';
                storagePreview.classList.remove('has-value');
            }
        }
    }

    /**
     * Відновлення збереженої одиниці виміру з localStorage
     */
    function restoreSavedUnit() {
        const savedUnit = localStorage.getItem(STORAGE_KEY_UNIT);
        if (savedUnit && (savedUnit === 'g' || savedUnit === 'oz')) {
            unitRadios.forEach(radio => {
                if (radio.value === savedUnit) {
                    radio.checked = true;
                }
            });
            updateUnitDisplay(savedUnit);
        } else {
            const checkedRadio = document.querySelector('input[name="unit"]:checked');
            updateUnitDisplay(checkedRadio ? checkedRadio.value : 'g');
        }
    }

    // Слухач зміни одиниці виміру -> збереження в localStorage
    unitRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            const selectedUnit = e.target.value;
            localStorage.setItem(STORAGE_KEY_UNIT, selectedUnit);
            updateUnitDisplay(selectedUnit);
            showNotification(`Одиницю виміру «${unitLabels[selectedUnit]}» збережено в localStorage!`);
        });
    });

    // Кнопка скидання localStorage
    if (clearStorageBtn) {
        clearStorageBtn.addEventListener('click', () => {
            localStorage.removeItem(STORAGE_KEY_UNIT);
            localStorage.removeItem(STORAGE_KEY_DRAFT);
            // Повертаємо радіо на грами
            unitRadios.forEach(radio => {
                radio.checked = (radio.value === 'g');
            });
            updateUnitDisplay('g');
            showNotification('Налаштування localStorage очищено (скинуто до грамів)');
        });
    }

    // Відновлення при першому запуску
    restoreSavedUnit();

    // ------------------------------------------------------------------------
    // Додатково: автозбереження чернетки тексту в localStorage
    // ------------------------------------------------------------------------
    function saveDraft() {
        const draft = {
            title: titleInput.value,
            cookTimeMin: cookTimeInput.value,
            ingredients: ingredientsTextarea.value
        };
        localStorage.setItem(STORAGE_KEY_DRAFT, JSON.stringify(draft));
    }

    function restoreDraftIfEmpty() {
        // Відновлюємо чернетку, тільки якщо поля форми порожні (не після POST-помилки від сервера)
        if (!titleInput.value && !cookTimeInput.value && !ingredientsTextarea.value) {
            const draftRaw = localStorage.getItem(STORAGE_KEY_DRAFT);
            if (draftRaw) {
                try {
                    const draft = JSON.parse(draftRaw);
                    if (draft.title) titleInput.value = draft.title;
                    if (draft.cookTimeMin) cookTimeInput.value = draft.cookTimeMin;
                    if (draft.ingredients) ingredientsTextarea.value = draft.ingredients;
                } catch (e) {
                    console.error('Помилка розбору чернетки:', e);
                }
            }
        }
    }

    restoreDraftIfEmpty();

    [titleInput, cookTimeInput, ingredientsTextarea].forEach(input => {
        input.addEventListener('input', () => {
            saveDraft();
            clearFieldError(input);
        });
    });

    // ------------------------------------------------------------------------
    // КРОК 5: Клієнтська валідація JavaScript (з event.preventDefault)
    // ------------------------------------------------------------------------

    function showFieldError(inputElement, errorElement, message) {
        const formGroup = inputElement.closest('.form-group');
        if (formGroup) {
            formGroup.classList.add('has-error');
        }
        if (errorElement) {
            errorElement.textContent = message;
        }
    }

    function clearFieldError(inputElement) {
        const formGroup = inputElement.closest('.form-group');
        if (formGroup) {
            formGroup.classList.remove('has-error');
        }
        const errorEl = formGroup.querySelector('.error-message');
        if (errorEl) {
            errorEl.textContent = '';
        }
        if (jsErrorAlert) {
            jsErrorAlert.style.display = 'none';
        }
    }

    function clearAllErrors() {
        document.querySelectorAll('.form-group.has-error').forEach(el => el.classList.remove('has-error'));
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
        if (jsErrorAlert) jsErrorAlert.style.display = 'none';
    }

    form.addEventListener('submit', (event) => {
        let hasClientError = false;
        let firstErrorMessage = '';
        clearAllErrors();

        // 1. Перевірка назви рецепта (title)
        const titleVal = titleInput.value.trim();
        if (titleVal === '') {
            hasClientError = true;
            firstErrorMessage = firstErrorMessage || 'Вкажіть назву рецепта.';
            showFieldError(titleInput, titleError, 'Клієнтська валідація: поле обов\'язкове!');
        } else if (titleVal.length < 3) {
            hasClientError = true;
            firstErrorMessage = firstErrorMessage || 'Назва рецепта має містити щонайменше 3 символи.';
            showFieldError(titleInput, titleError, 'Мінімум 3 символи для назви рецепта.');
        }

        // 2. Перевірка часу приготування (cookTimeMin > 0)
        const cookTimeVal = cookTimeInput.value.trim();
        const cookTimeNum = Number(cookTimeVal);
        if (cookTimeVal === '') {
            hasClientError = true;
            firstErrorMessage = firstErrorMessage || 'Введіть час приготування.';
            showFieldError(cookTimeInput, cookTimeError, 'Клієнтська валідація: час обов\'язковий!');
        } else if (isNaN(cookTimeNum) || !Number.isInteger(cookTimeNum) || cookTimeNum <= 0) {
            hasClientError = true;
            firstErrorMessage = firstErrorMessage || 'Час приготування має бути додатним цілим числом (> 0).';
            showFieldError(cookTimeInput, cookTimeError, 'Число повинно бути більше 0 (хв).');
        }

        // 3. Перевірка інгредієнтів (непорожній перелік)
        const ingredientsVal = ingredientsTextarea.value.trim();
        if (ingredientsVal === '') {
            hasClientError = true;
            firstErrorMessage = firstErrorMessage || 'Додайте хоча б один інгредієнт.';
            showFieldError(ingredientsTextarea, ingredientsError, 'Клієнтська валідація: введіть хоча б один інгредієнт!');
        } else {
            // Перевіряємо чи є непорожні рядки
            const items = ingredientsVal.split(/[\n,]+/).map(s => s.trim()).filter(Boolean);
            if (items.length === 0) {
                hasClientError = true;
                firstErrorMessage = firstErrorMessage || 'Всі введені рядки інгредієнтів порожні.';
                showFieldError(ingredientsTextarea, ingredientsError, 'Потрібен мінімум один непорожній інгредієнт.');
            }
        }

        // Зупинка відправки форми у разі помилки (Крок 5)
        if (hasClientError) {
            event.preventDefault();
            if (jsErrorAlert && jsErrorMessage) {
                jsErrorMessage.textContent = firstErrorMessage;
                jsErrorAlert.style.display = 'flex';
                jsErrorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            // Якщо валідація пройшла — очищаємо збережену чернетку перед надсиланням
            localStorage.removeItem(STORAGE_KEY_DRAFT);
        }
    });

    // Очистити форму
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (confirm('Очистити форму та видалити поточну чернетку?')) {
                titleInput.value = '';
                cookTimeInput.value = '';
                ingredientsTextarea.value = '';
                localStorage.removeItem(STORAGE_KEY_DRAFT);
                clearAllErrors();
                restoreSavedUnit();
                showNotification('Форму очищено');
            }
        });
    }

    /**
     * Спливаюче міні-повідомлення
     */
    function showNotification(msg) {
        let toast = document.getElementById('miniToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'miniToast';
            toast.className = 'mini-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2800);
    }
});
