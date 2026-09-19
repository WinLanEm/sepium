<?php $propertyForm = new PropertyForm(db()); ?>
<section class="scenario-column app-window">
    <header class="app-window-bar">
        <div class="app-window-brand"><span>S</span><strong>Demo CMS</strong></div>
        <div class="app-window-caption">
            <b>Здесь нужно починить</b>
            <span>Интерфейс товара</span>
        </div>
    </header>

    <div class="product-header">
        <div>
            <small>Редактирование товара</small>
            <h2>Настольная лампа Nordic</h2>
        </div>
        <span>Тестовый стенд</span>
    </div>

    <div class="editor">
        <div class="editor-grid">
            <section class="categories-panel">
                <div class="section-heading">
                    <h2>Категории</h2>
                    <p>Можно выбрать несколько.</p>
                </div>

                <div class="category-list">
                    <?php foreach ($categories as $category): ?>
                        <label class="category-row add_good_name_category" data-category-id="<?php echo h($category['ID_category']); ?>" data-category-chpu="<?php echo h($category['chpu_category']); ?>">
                            <input class="js-category" type="checkbox" value="<?php echo h($category['ID_category']); ?>">
                            <span><?php echo h($category['name_category']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="properties-panel">
                <div class="section-heading properties-heading">
                    <div>
                        <h2>Характеристики</h2>
                        <p>HTML возвращает AJAX-обработчик.</p>
                    </div>
                </div>

                <div class="properties property_all" aria-live="polite">
                    <?php echo $propertyForm->render(array()); ?>
                </div>
            </section>
        </div>

        <footer class="editor-footer">
            <span>Выбор категорий должен сразу обновлять содержимое <code>.property_all</code>.</span>
            <button class="addgood_click" type="button">Проверить отправку</button>
        </footer>

        <section class="payload-preview" aria-live="polite">
            <div>
                <b>Данные, которые получит PHP</b>
                <span>Записи в базу не выполняются</span>
            </div>
            <pre class="js-payload-preview">Нажмите «Проверить отправку»</pre>
        </section>
    </div>
</section>
