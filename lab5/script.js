document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search');
    const resultsContainer = document.getElementById('results');
    const addForm = document.getElementById('add-form');

    // Завантажити всі рецепти
    loadRecipes();

    // Живий пошук
    searchInput.addEventListener('input', () => {
        loadRecipes(searchInput.value.trim());
    });

    // Додавання через AJAX
    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
            title: document.getElementById('title').value,
            ingredients: document.getElementById('ingredients').value,
            cook_time_min: parseInt(document.getElementById('cook_time_min').value)
        };

        try {
            const res = await fetch('api_add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.error || 'Помилка');
            }

            addForm.reset();
            loadRecipes(searchInput.value.trim()); // Оновлюємо список
        } catch (err) {
            alert('Помилка: ' + err.message);
        }
    });

    // Видалення рецепта через AJAX (делегування подій)
    resultsContainer.addEventListener('click', async (e) => {
        if (e.target.classList.contains('btn-delete')) {
            const id = e.target.dataset.id;
            if (!confirm('Ви дійсно хочете видалити цей рецепт?')) return;

            try {
                const res = await fetch('api_delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: parseInt(id) })
                });

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.error || 'Помилка видалення');
                }

                loadRecipes(searchInput.value.trim());
            } catch (err) {
                alert('Помилка: ' + err.message);
            }
        }
    });

    async function loadRecipes(query = '') {
        try {
            const url = query ? `api_list.php?q=${encodeURIComponent(query)}` : 'api_list.php';
            const res = await fetch(url);
            const recipes = await res.json();
            renderRecipes(recipes);
        } catch (err) {
            resultsContainer.innerHTML = `<p style="color:red">Помилка завантаження: ${err.message}. Перевірте чи налаштована БД через <a href="setup.php">setup.php</a>.</p>`;
        }
    }

    function renderRecipes(recipes) {
        if (!recipes.length) {
            resultsContainer.innerHTML = '<p class="empty-msg">Рецептів не знайдено.</p>';
            return;
        }

        let html = '<table class="recipes-table"><thead><tr><th>ID</th><th>Назва</th><th>Інгредієнти</th><th>Час (хв)</th><th>Дії</th></tr></thead><tbody>';
        recipes.forEach(r => {
            html += `<tr>
                <td>${r.id}</td>
                <td><strong>${escapeHtml(r.title)}</strong></td>
                <td>${escapeHtml(r.ingredients)}</td>
                <td><span class="badge ${r.cook_time_min <= 20 ? 'badge-quick' : ''}">${r.cook_time_min} хв</span></td>
                <td><button class="btn-danger btn-delete" data-id="${r.id}">🗑 Видалити</button></td>
            </tr>`;
        });
        html += '</tbody></table>';
        resultsContainer.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
