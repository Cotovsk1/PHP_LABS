document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_UNIT_KEY = 'cookbook_preferred_unit';
    const STORAGE_DRAFT_KEY = 'cookbook_lab7_draft';

    let currentUnit = localStorage.getItem(STORAGE_UNIT_KEY) === 'oz' ? 'oz' : 'g';
    let lastLoadedRecipes = [];
    let debounceTimer = null;

    // Елементи інтерфейсу
    const searchInput = document.getElementById('searchInput');
    const maxTimeFilter = document.getElementById('maxTimeFilter');
    const unoptimizedToggle = document.getElementById('unoptimizedToggle');
    const clearCacheBtn = document.getElementById('clearCacheBtn');
    const resultsContainer = document.getElementById('resultsContainer');
    const unitButtons = document.querySelectorAll('.unit-btn');

    // Елементи форми
    const recipeForm = document.getElementById('recipeForm');
    const formHeading = document.getElementById('formHeading');
    const formAlert = document.getElementById('formAlert');
    const editRecipeId = document.getElementById('editRecipeId');
    const titleInput = document.getElementById('titleInput');
    const cookTimeInput = document.getElementById('cookTimeInput');
    const servingsInput = document.getElementById('servingsInput');
    const difficultyInput = document.getElementById('difficultyInput');
    const ingredientsInput = document.getElementById('ingredientsInput');
    const isVeganInput = document.getElementById('isVeganInput');
    const substitutionsGroup = document.getElementById('substitutionsGroup');
    const substitutionsInput = document.getElementById('substitutionsInput');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const resetDraftBtn = document.getElementById('resetDraftBtn');

    // ------------------------------------------------------------------------
    // 1. Робота з localStorage (ЛР №2): одиниці виміру (г / унц) та чернетка форми
    // ------------------------------------------------------------------------
    function syncUnitButtons() {
        unitButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.unit === currentUnit);
        });
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
        if (editRecipeId.value !== '') return; // Не перезаписуємо чернетку під час редагування
        const draft = {
            title: titleInput.value,
            cookTime: cookTimeInput.value,
            servings: servingsInput.value,
            difficulty: difficultyInput.value,
            ingredients: ingredientsInput.value,
            isVegan: isVeganInput.checked,
            substitutions: substitutionsInput.value
        };
        localStorage.setItem(STORAGE_DRAFT_KEY, JSON.stringify(draft));
    }

    function restoreDraft() {
        const raw = localStorage.getItem(STORAGE_DRAFT_KEY);
        if (!raw) return;
        try {
            const draft = JSON.parse(raw);
            if (draft.title) titleInput.value = draft.title;
            if (draft.cookTime) cookTimeInput.value = draft.cookTime;
            if (draft.servings) servingsInput.value = draft.servings;
            if (draft.difficulty) difficultyInput.value = draft.difficulty;
            if (draft.ingredients) ingredientsInput.value = draft.ingredients;
            isVeganInput.checked = Boolean(draft.isVegan);
            substitutionsGroup.style.display = isVeganInput.checked ? 'block' : 'none';
            if (draft.substitutions) substitutionsInput.value = draft.substitutions;
        } catch (e) {
            console.warn('Не вдалося відновити чернетку:', e);
        }
    }

    isVeganInput.addEventListener('change', () => {
        substitutionsGroup.style.display = isVeganInput.checked ? 'block' : 'none';
        saveDraft();
    });

    [titleInput, cookTimeInput, servingsInput, difficultyInput, ingredientsInput, substitutionsInput].forEach(el => {
        el.addEventListener('input', saveDraft);
    });

    restoreDraft();

    // ------------------------------------------------------------------------
    // 2. Завантаження списку рецептів та оновлення метрик профайлера (ЛР №5 + №7)
    // ------------------------------------------------------------------------
    async function loadRecipes() {
        const params = new URLSearchParams({ resource: 'recipes' });
        const q = searchInput.value.trim();
        const maxTime = maxTimeFilter.value.trim();
        const mode = unoptimizedToggle.checked ? 'unoptimized' : 'optimized';

        if (q !== '') params.set('q', q);
        if (maxTime !== '') params.set('max_time', maxTime);
        params.set('mode', mode);

        try {
            const response = await fetch(`api.php?${params.toString()}`);
            const json = await response.json();

            if (!response.ok || !json.success) {
                throw new Error(json.error || 'Помилка завантаження даних від API');
            }

            lastLoadedRecipes = json.data.recipes || [];
            updateStats(json.data.stats || {});
            updateProfiler(json.meta || {});
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

    function updateProfiler(meta) {
        const modeEl = document.getElementById('metricMode');
        const queriesEl = document.getElementById('metricQueries');
        const timeEl = document.getElementById('metricTime');
        const memEl = document.getElementById('metricMemory');
        const cacheEl = document.getElementById('metricCache');

        modeEl.textContent = meta.mode || 'optimized';
        modeEl.style.color = meta.mode === 'unoptimized' ? '#dc2626' : '#16a34a';

        queriesEl.textContent = `${meta.query_count ?? 0} шт. (SQL: ${meta.sql_time_ms ?? 0} мс)`;
        queriesEl.style.color = (meta.query_count ?? 0) > 3 ? '#dc2626' : '#16a34a';

        timeEl.textContent = `${meta.execution_time_ms ?? 0} мс`;
        memEl.textContent = `${meta.peak_memory_mb ?? 0} МБ`;

        if (meta.cache_hit) {
            cacheEl.textContent = 'HIT (з кешу, 0 SQL)';
            cacheEl.style.color = '#16a34a';
        } else {
            cacheEl.textContent = `MISS (файлів: ${meta.active_cache_files ?? 0})`;
            cacheEl.style.color = '#d97706';
        }
    }

    function renderTable(recipes) {
        document.getElementById('tableCountBadge').textContent = `${recipes.length} записів`;

        if (!recipes.length) {
            resultsContainer.innerHTML = '<p style="padding: 24px; text-align: center; color: var(--gray-600);">За вашим запитом рецептів не знайдено.</p>';
            return;
        }

        let html = `
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Тип (ООП)</th>
                        <th>Назва страви</th>
                        <th>Інгредієнти (${currentUnit === 'oz' ? 'Унції' : 'Грами'})</th>
                        <th>Час</th>
                        <th>Схожі рецепти</th>
                        <th style="text-align: center;">Дії</th>
                    </tr>
                </thead>
                <tbody>
        `;

        recipes.forEach(r => {
            const formattedIng = currentUnit === 'oz' ? (r.formatted_oz || r.ingredients) : (r.formatted_g || r.ingredients);
            const badgeClass = r.badge ? r.badge.class : 'badge-medium';
            const badgeLabel = r.badge ? r.badge.label : `${r.cook_time_min} хв`;
            const typeBadge = r.is_vegan ? '<span class="badge badge-vegan">🌱 Веганський</span>' : '<span class="badge badge-base">Звичайний</span>';
            const primaryInfo = r.primary_ingredient
                ? `Ще <strong>${r.similar_recipes_count}</strong> з «${escapeHtml(r.primary_ingredient)}»`
                : '—';

            html += `
                <tr>
                    <td><strong>#${r.id}</strong></td>
                    <td>${typeBadge}</td>
                    <td>
                        <div class="recipe-name">${escapeHtml(r.title)}</div>
                        <div class="small-meta">${escapeHtml(r.info || '')}</div>
                    </td>
                    <td class="ingredients-cell">${escapeHtml(formattedIng)}</td>
                    <td>
                        <div><strong>${escapeHtml(r.cook_time_human || (r.cook_time_min + ' хв'))}</strong></div>
                        <span class="badge ${escapeHtml(badgeClass)}">${escapeHtml(badgeLabel)}</span>
                    </td>
                    <td style="font-size: 0.85rem; color: var(--gray-600);">${primaryInfo}</td>
                    <td>
                        <div class="actions-cell">
                            <button type="button" class="btn btn-sm btn-edit" data-id="${r.id}">✏️ Змінити</button>
                            <button type="button" class="btn btn-sm btn-delete" data-id="${r.id}">🗑️ Видалити</button>
                        </div>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        resultsContainer.innerHTML = html;
    }

    // ------------------------------------------------------------------------
    // 3. Обробники фільтрів та кнопок профайлера
    // ------------------------------------------------------------------------
    function triggerDebouncedLoad() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadRecipes, 220);
    }

    searchInput.addEventListener('input', triggerDebouncedLoad);
    maxTimeFilter.addEventListener('input', triggerDebouncedLoad);
    unoptimizedToggle.addEventListener('change', loadRecipes);

    clearCacheBtn.addEventListener('click', async () => {
        try {
            const res = await fetch('api.php?resource=recipes&action=clear_cache', { method: 'POST' });
            const json = await res.json();
            if (json.success) {
                loadRecipes();
            }
        } catch (err) {
            alert('Помилка очищення кешу: ' + err.message);
        }
    });

    // ------------------------------------------------------------------------
    // 4. Створення та редагування рецепта через AJAX (POST / PUT)
    // ------------------------------------------------------------------------
    recipeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        formAlert.style.display = 'none';

        const payload = {
            title: titleInput.value.trim(),
            cook_time_min: parseInt(cookTimeInput.value, 10),
            servings: parseInt(servingsInput.value, 10) || 2,
            difficulty: difficultyInput.value,
            ingredients: ingredientsInput.value.trim(),
            is_vegan: isVeganInput.checked ? 1 : 0,
            substitutions: substitutionsInput.value.trim()
        };

        // Клієнтська валідація (ЛР №2)
        if (payload.title.length < 3) {
            showFormError('Назва рецепта повинна містити щонайменше 3 символи.');
            return;
        }
        if (!Number.isInteger(payload.cook_time_min) || payload.cook_time_min <= 0) {
            showFormError('Час приготування має бути цілим числом більше 0.');
            return;
        }
        if (payload.ingredients === '') {
            showFormError('Вкажіть хоча б один інгредієнт.');
            return;
        }

        const isEditing = editRecipeId.value !== '';
        const url = isEditing
            ? 'api.php?resource=recipes&action=update'
            : 'api.php?resource=recipes';

        if (isEditing) {
            payload.id = parseInt(editRecipeId.value, 10);
        }

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();

            if (!res.ok || !json.success) {
                const details = json.details ? Object.values(json.details).join(' ') : '';
                throw new Error((json.error || 'Помилка збереження') + (details ? ': ' + details : ''));
            }

            if (!isEditing) {
                localStorage.removeItem(STORAGE_DRAFT_KEY);
            }
            resetFormState();
            loadRecipes();
        } catch (err) {
            showFormError(err.message);
        }
    });

    // Делегування кнопок Редагувати / Видалити у таблиці
    resultsContainer.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.btn-edit');
        if (editBtn) {
            const id = parseInt(editBtn.dataset.id, 10);
            const recipe = lastLoadedRecipes.find(item => Number(item.id) === id);
            if (!recipe) return;

            editRecipeId.value = String(recipe.id);
            titleInput.value = recipe.title;
            cookTimeInput.value = recipe.cook_time_min;
            servingsInput.value = recipe.servings || 2;
            difficultyInput.value = recipe.difficulty || 'Середній';
            ingredientsInput.value = recipe.ingredients;
            isVeganInput.checked = Boolean(recipe.is_vegan);
            substitutionsGroup.style.display = isVeganInput.checked ? 'block' : 'none';
            substitutionsInput.value = recipe.substitutions || '';

            formHeading.textContent = `✏️ Редагування рецепта #${recipe.id}`;
            cancelEditBtn.style.display = 'inline-flex';
            recipeForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const deleteBtn = e.target.closest('.btn-delete');
        if (deleteBtn) {
            const id = parseInt(deleteBtn.dataset.id, 10);
            if (!confirm(`Видалити рецепт #${id}?`)) return;

            try {
                const res = await fetch('api.php?resource=recipes&action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const json = await res.json();
                if (!res.ok || !json.success) {
                    throw new Error(json.error || 'Не вдалося видалити запис.');
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
        formHeading.textContent = '➕ Додати новий рецепт';
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

    // Початкове завантаження
    loadRecipes();
});
