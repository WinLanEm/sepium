<?php

require_once '/var/www/app/src/bootstrap.php';

$passed = 0;
$failed = 0;

function checkPropertyForm($condition, $message)
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo '[PASS] ' . $message . PHP_EOL;
        return;
    }

    $failed++;
    echo '[FAIL] ' . $message . PHP_EOL;
}

function renderedPropertyIds($html)
{
    preg_match_all('/\bdata-property-id=(?:"|\')(\d+)(?:"|\')/', $html, $matches);

    return array_map('intval', $matches[1]);
}

$form = new PropertyForm(db());

$globalHtml = $form->render(array());
checkPropertyForm(
    renderedPropertyIds($globalHtml) === array(1, 6),
    'Пустой выбор возвращает общие свойства в стабильном порядке.'
);
checkPropertyForm(
    strpos($globalHtml, '&lt;текст&gt;') !== false && strpos($globalHtml, '<текст>') === false,
    'Текст из БД экранируется при рендеринге.'
);

$categoryOneHtml = $form->render(array('1'));
checkPropertyForm(
    renderedPropertyIds($categoryOneHtml) === array(1, 2, 3, 4, 6),
    'Категория 1 выбирается точно, без свойства категории 11.'
);
checkPropertyForm(
    strpos($categoryOneHtml, '<select class="text-input ag_pole_good">') !== false
        && strpos($categoryOneHtml, 'checkbox_property ag_pole_good') !== false
        && strpos($categoryOneHtml, 'inputmode="decimal"') !== false,
    'Рендер поддерживает одиночный, множественный и числовой типы.'
);
$woodPosition = strpos($categoryOneHtml, '>Дерево</option>');
$metalPosition = strpos($categoryOneHtml, '>Металл</option>');
$glassPosition = strpos($categoryOneHtml, '>Стекло</option>');
checkPropertyForm(
    $woodPosition !== false
        && $metalPosition !== false
        && $glassPosition !== false
        && $woodPosition < $metalPosition
        && $metalPosition < $glassPosition,
    'Варианты выводятся в стабильном порядке.'
);

$mixedHtml = $form->render(array('1', '1', array('2'), '3', '0', '-1', 'text'));
checkPropertyForm(
    renderedPropertyIds($mixedHtml) === array(1, 2, 3, 4, 6),
    'Некорректные и повторяющиеся category безопасно нормализуются.'
);

$unknownHtml = $form->render(array('999999'));
checkPropertyForm(
    renderedPropertyIds($unknownHtml) === array(1, 6),
    'Неизвестная категория возвращает только общие свойства.'
);

echo PHP_EOL . 'Результат: ' . $passed . ' успешно, ' . $failed . ' с ошибкой.' . PHP_EOL;

exit($failed > 0 ? 1 : 0);
