document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_UNIT_KEY = 'cookbook_preferred_unit';
    const STORAGE_DRAFT_KEY = 'cookbook_lab8_draft';

    // Отримуємо CSRF-токен сесії з meta-тегу
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    let currentUnit = localStorage.getItem(STORAGE_UNIT_KEY) === 'oz' ? 'oz' : 'g';
    let lastLoadedRecipes = [];
    let debounceTimer = null;

    const searchInput = document.getElementById('searchInput');
    const maxTimeFilter = document.getElementById('maxTimeFilter');
    const resultsContainer = document.getElementById('resultsContainer');
    const unitButtons = document.querySelectorAll('.unit-btn');

    const recipeForm = document.getElementById('recipeForm');
    const formHeading = document.getElementById('formHeading');
    const formAlert = document.getElementById('formAlert');
    const editRecipeId = document.getElementById('editRecipeId');
    const titleInput = document.getElementById('titleInput');
    const cookTimeInput = document.getElementById('cookTimeInput');
    const servingsInput = document.getElementById('servingsInput');
    const difficultyInput = document.getElementById('difficultyInput');
    const ingredientsInput = document.getElementById('ingredientsInput');
    const stepsInput = document.getElementById('stepsInput');
    const isVeganInput = document.getElementById('isVeganInput');
    const substitutionsGroup = document.getElementById('substitutionsGroup');
    const substitutionsInput = document.getElementById('substitutionsInput');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const resetDraftBtn = document.getElementById('resetDraftBtn');

    function syncUnitButtons() {
        unitButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.unit === currentUnit));
    }

    unitButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            currentUnit = btn.dataset.unit === 'oz' ? 'oz' : 'g';
            localStorage.setItem(STORAGE_UNIT_KEY, currentUnit);
            syncUnitButtons();
            renderTable(lastLoadedRecipes);
        });
    });
    syncUnitButtons();

    function saveDraft() {
        if (editRecipeId.value !== '') return;
        localStorage.setItem(STORAGE_DRAFT_KEY, JSON.stringify({
            title: titleInput.value,
            cookTime: cookTimeInput.value,
            servings: servingsInput.value,
            difficulty: difficultyInput.value,
            ingredients: ingredientsInput.value,
            steps: stepsInput.value,
            isVegan: isVeganInput.checked,
            substitutions: substitutionsInput.value
        }));
    }

    function restoreDraft() {
        const raw = localStorage.getItem(STORAGE_DRAFT_KEY);
        if (!raw) return;
        try {
            const d = JSON.parse(raw);
            if (d.title) titleInput.value = d.title;
            if (d.cookTime) cookTimeInput.value = d.cookTime;
            if (d.servings) servingsInput.value = d.servings;
            if (d.difficulty) difficultyInput.value = d.difficulty;
            if (d.ingredients) ingredientsInput.value = d.ingredients;
            if (d.steps) stepsInput.value = d.steps;
            isVeganInput.checked = Boolean(d.isVegan);
            substitutionsGroup.style.display = isVeganInput.checked ? 'block' : 'none';
            if (d.substitutions) substitutionsInput.value = d.substitutions;
        } catch (e) {}
    }

    isVeganInput.addEventListener('change', () => {
        substitutionsGroup.style.display = isVeganInput.checked ? 'block' : 'none';
        saveDraft();
    });

    [titleInput, cookTimeInput, servingsInput, difficultyInput, ingredientsInput, stepsInput, substitutionsInput].forEach(el => {
        el.addEventListener('input', saveDraft);
    });
    restoreDraft();

    async function loadRecipes() {
        const params = new URLSearchParams({ resource: 'recipes' });
        if (searchInput.value.trim() !== '') params.set('q', searchInput.value.trim());
        if (maxTimeFilter.value.trim() !== '') params.set('max_time', maxTimeFilter.value.trim());

        try {
            const res = await fetch(`api.php?${params.toString()}`);
            const json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.error || 'Помилка завантаження даних');
            }

            lastLoadedRecipes = json.data.recipes || [];
            updateStats(json.data.stats || {});
            updateMeta(json.meta || {});
            renderTable(lastLoadedRecipes);
        } catch (err) {
            resultsContainer.innerHTML = `<div class="alert alert-error" style="margin: 16px;">${escapeHtml(err.message)}</div>`;
        }
    }

    function updateStats(stats) {
        document.getElementById('statTotalCount').textContent = stats.total_count ?? 0;
        document.getElementById('statAvgTime').textContent = `${stats.average_cook_time ?? 0} хв`;
        document.getElementById('statQuickCount').textContent = stats.quick_count ?? 0;
        if (stats.fastest_recipe) {
            document.getElementById('statFastestTime').textContent = `${stats.fastest_recipe.cook_time_min} хв`;
            document.getElementById('statFastestTitle').textContent = `«${stats.fastest_recipe.title}»`;
        } else {
            document.getElementById('statFastestTime').textContent = '—';
            document.getElementById('statFastestTitle').textContent = 'Немає записів';
        }
    }

    function updateMeta(meta) {
        document.getElementById('metricPerf').textContent = `${meta.query_count ?? 0} SQL / ${meta.execution_time_ms ?? 0} мс`;
        document.getElementById('metricCache').textContent = meta.cache_hit ? 'HIT (з кешу)' : 'MISS (БД)';
    }

    function renderTable(recipes) {
        document.getElementById('tableCountBadge').textContent = `${recipes.length} записів`;
        if (!recipes.length) {
            resultsContainer.innerHTML = '<p style="padding: 24px; text-align: center; color: var(--gray-600);">Рецептів не знайдено.</p>';
            return;
        }

        let html = `
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Тип</th>
                        <th>Назва та кроки (<code>steps</code>)</th>
                        <th>Інгредієнти (${currentUnit === 'oz' ? 'Унції' : 'Грами'})</th>
                        <th>Час</th>
                        <th style="text-align: center;">Дії (POST + CSRF)</th>
                    </tr>
                </thead>
                <tbody>
        `;

        // Крок 2 (ЛР №8): Кожне поле користувача (title, ingredients, steps) екранується через escapeHtml()
        recipes.forEach(r => {
            const ingText = currentUnit === 'oz' ? (r.formatted_oz || r.ingredients) : (r.formatted_g || r.ingredients);
            const badgeClass = r.badge ? r.badge.class : 'badge-medium';
            const badgeLabel = r.badge ? r.badge.label : `${r.cook_time_min} хв`;
            const typeBadge = r.is_vegan ? '<span class="badge badge-vegan">🌱 Веганський</span>' : '<span class="badge badge-base">Звичайний</span>';

            html += `
                <tr>
                    <td><strong>#${Number(r.id)}</strong></td>
                    <td>${typeBadge}</td>
                    <td>
                        <div class="recipe-name">${escapeHtml(r.title)}</div>
                        <div class="small-meta" style="margin-top: 4px; color: var(--gray-700);">${escapeHtml(r.steps || '')}</div>
                    </td>
                    <td class="ingredients-cell">${escapeHtml(ingText)}</td>
                    <td>
                        <div><strong>${escapeHtml(r.cook_time_human || (r.cook_time_min + ' хв'))}</strong></div>
                        <span class="badge ${escapeHtml(badgeClass)}">${escapeHtml(badgeLabel)}</span>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <button type="button" class="btn btn-sm btn-edit" data-id="${Number(r.id)}">✏️ Змінити</button>
                            <button type="button" class="btn btn-sm btn-delete" data-id="${Number(r.id)}">🗑️ Видалити</button>
                        </div>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        resultsContainer.innerHTML = html;
    }

    function triggerDebouncedLoad() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadRecipes, 220);
    }

    searchInput.addEventListener('input', triggerDebouncedLoad);
    maxTimeFilter.addEventListener('input', triggerDebouncedLoad);

    recipeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        formAlert.style.display = 'none';

        const payload = {
            csrf_token: csrfToken,
            title: titleInput.value.trim(),
            cook_time_min: parseInt(cookTimeInput.value, 10),
            servings: parseInt(servingsInput.value, 10) || 2,
            difficulty: difficultyInput.value,
            ingredients: ingredientsInput.value.trim(),
            steps: stepsInput.value.trim(),
            is_vegan: isVeganInput.checked ? 1 : 0,
            substitutions: substitutionsInput.value.trim()
        };

        if (payload.title.length < 3 || !Number.isInteger(payload.cook_time_min) || payload.cook_time_min <= 0 || !payload.ingredients || payload.steps.length < 5) {
            showFormError('Перевірте правильність заповнення всіх обов\'язкових полів (назва ≥ 3 симв., час > 0, інгредієнти, кроки ≥ 5 симв.).');
            return;
        }

        const isEditing = editRecipeId.value !== '';
        const url = isEditing ? 'api.php?resource=recipes&action=update' : 'api.php?resource=recipes';
        if (isEditing) payload.id = parseInt(editRecipeId.value, 10);

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (!res.ok || !json.success) {
                const details = json.details ? Object.values(json.details).join(' ') : '';
                throw new Error((json.error || 'Помилка збереження') + (details ? ': ' + details : ''));
            }

            if (!isEditing) localStorage.removeItem(STORAGE_DRAFT_KEY);
            resetFormState();
            loadRecipes();
        } catch (err) {
            showFormError(err.message);
        }
    });

    resultsContainer.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.btn-edit');
        if (editBtn) {
            const id = parseInt(editBtn.dataset.id, 10);
            const r = lastLoadedRecipes.find(item => Number(item.id) === id);
            if (!r) return;

            editRecipeId.value = String(r.id);
            titleInput.value = r.title;
            cookTimeInput.value = r.cook_time_min;
            servingsInput.value = r.servings || 2;
            difficultyInput.value = r.difficulty || 'Середній';
            ingredientsInput.value = r.ingredients;
            stepsInput.value = r.steps || '';
            isVeganInput.checked = Boolean(r.is_vegan);
            substitutionsGroup.style.display = isVeganInput.checked ? 'block' : 'none';
            substitutionsInput.value = r.substitutions || '';

            formHeading.textContent = `✏️ Редагування рецепта #${r.id}`;
            cancelEditBtn.style.display = 'inline-flex';
            recipeForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Крок 5 (ЛР №8): Видалення виконується виключно через POST із підтвердженням та CSRF-токеном
        const deleteBtn = e.target.closest('.btn-delete');
        if (deleteBtn) {
            const id = parseInt(deleteBtn.dataset.id, 10);
            if (!confirm(`Ви справді бажаєте видалити рецепт #${id}? Цю дію неможливо скасувати.`)) return;

            try {
                const res = await fetch('api.php?resource=recipes&action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ id, csrf_token: csrfToken })
                });
                const json = await res.json();
                if (!res.ok || !json.success) {
                    throw new Error(json.error || 'Не вдалося видалити рецепт.');
                }
                loadRecipes();
            } catch (err) {
                alert('Помилка: ' + err.message);
            }
        }
    });

    cancelEditBtn.addEventListener('click', resetFormState);
    resetDraftBtn.addEventListener('click', () => {
        localStorage.removeItem(STORAGE_DRAFT_KEY);
        resetFormState();
    });

    function resetFormState() {
        editRecipeId.value = '';
        recipeForm.reset();
        substitutionsGroup.style.display = 'none';
        formAlert.style.display = 'none';
        formHeading.textContent = '➕ Додати новий рецепт (із CSRF-захистом)';
        cancelEditBtn.style.display = 'none';
    }

    function showFormError(msg) {
        formAlert.textContent = msg;
        formAlert.style.display = 'block';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = String(text ?? '');
        return div.innerHTML;
    }

    loadRecipes();
});
