<?php

$passed = 0;
$failed = 0;

function check($condition, $message, $details)
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo '[PASS] ' . $message . PHP_EOL;
        return;
    }

    $failed++;
    echo '[FAIL] ' . $message . PHP_EOL;

    if ($details !== '') {
        echo '       ' . $details . PHP_EOL;
    }
}

function requestPage($path, $body)
{
    $baseUrl = getenv('TEST_BASE_URL');
    if ($baseUrl === false || $baseUrl === '') {
        $baseUrl = 'http://127.0.0.1';
    }

    $options = array(
        'http' => array(
            'method' => $body === null ? 'GET' : 'POST',
            'ignore_errors' => true,
            'timeout' => 10,
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body === null ? '' : $body,
        ),
    );

    $content = @file_get_contents(
        rtrim($baseUrl, '/') . $path,
        false,
        stream_context_create($options)
    );
    $headers = isset($http_response_header) ? $http_response_header : array();
    $status = 0;

    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $header, $matches)) {
            $status = (int) $matches[1];
        }
    }

    return array(
        'body' => $content,
        'headers' => $headers,
        'status' => $status,
    );
}

function responseHeader($response, $name)
{
    $prefix = strtolower($name) . ':';

    foreach ($response['headers'] as $header) {
        if (strpos(strtolower($header), $prefix) === 0) {
            return trim(substr($header, strlen($prefix)));
        }
    }

    return '';
}

function propertyIds($html)
{
    if (!is_string($html)) {
        return array();
    }

    preg_match_all('/\bdata-property-id=(?:"|\')(\d+)(?:"|\')/', $html, $matches);

    return array_map('intval', $matches[1]);
}

function formatIds($ids)
{
    return '[' . implode(',', $ids) . ']';
}

function checkPropertyIds($response, $expected, $message)
{
    $actual = propertyIds($response['body']);
    $requestSucceeded = is_string($response['body'])
        && $response['status'] >= 200
        && $response['status'] < 300;

    check(
        $requestSucceeded && $actual === $expected,
        $message,
        'Ожидалось: ' . formatIds($expected)
            . '; получено: ' . formatIds($actual)
            . '; HTTP: ' . $response['status']
    );
}

function postCategories($categories)
{
    return requestPage(
        '/admin/ajax/property/Refresh_Property_Good.php',
        http_build_query(array('category' => $categories))
    );
}

$initialPage = requestPage('/', null);
checkPropertyIds(
    $initialPage,
    array(1, 6),
    'Первый GET без выбранных категорий содержит только общие свойства.'
);

$noCategory = requestPage(
    '/admin/ajax/property/Refresh_Property_Good.php',
    ''
);
checkPropertyIds(
    $noCategory,
    array(1, 6),
    'AJAX без category возвращает общие свойства.'
);

$singleCategoryCases = array(
    array(1, array(1, 2, 3, 4, 6)),
    array(2, array(1, 3, 5, 6)),
    array(3, array(1, 2, 6)),
    array(4, array(1, 6)),
    array(11, array(1, 6, 7)),
);

foreach ($singleCategoryCases as $case) {
    checkPropertyIds(
        postCategories(array($case[0])),
        $case[1],
        'Категория ' . $case[0] . ' возвращает точный набор свойств в порядке sort_prop.'
    );
}

$multipleCategories = postCategories(array(1, 3));
checkPropertyIds(
    $multipleCategories,
    array(1, 2, 3, 4, 6),
    'Несколько категорий возвращают объединение свойств без дублей.'
);

$scalarCategory = requestPage(
    '/admin/ajax/property/Refresh_Property_Good.php',
    http_build_query(array('category' => 'broken'))
);
checkPropertyIds(
    $scalarCategory,
    array(1, 6),
    'Скалярный category игнорируется и возвращает общие свойства.'
);

$invalidCategories = postCategories(array('', 'text', '-1', '0', '999999'));
checkPropertyIds(
    $invalidCategories,
    array(1, 6),
    'Пустые, текстовые, неположительные и неизвестные category игнорируются.'
);

$mixedCategories = postCategories(array('1', '1', array('2'), '3'));
checkPropertyIds(
    $mixedCategories,
    array(1, 2, 3, 4, 6),
    'Повторяющиеся ID удаляются, вложенные значения игнорируются без потери валидных ID.'
);

$injectionShapedCategory = postCategories(array("1' OR 1=1 -- "));
checkPropertyIds(
    $injectionShapedCategory,
    array(1, 6),
    'SQL-подобное значение category не влияет на отбор свойств.'
);

$categoryOne = postCategories(array(1));
$categoryOneBody = is_string($categoryOne['body']) ? $categoryOne['body'] : '';
$contentType = responseHeader($categoryOne, 'Content-Type');
check(
    strpos(strtolower($contentType), 'text/html') === 0
        && strpos($categoryOneBody, 'property-field') !== false
        && trim($categoryOneBody) !== 'no'
        && strpos(ltrim($categoryOneBody), '{') !== 0,
    'AJAX сохраняет контракт готового HTML-ответа.',
    'Content-Type: ' . ($contentType === '' ? '(отсутствует)' : $contentType)
);

check(
    strpos($categoryOneBody, 'data-property-id="4"') !== false
        && strpos($categoryOneBody, 'inputmode="decimal"') !== false,
    'Числовое свойство присутствует в AJAX-ответе.',
    'Для категории 1 ожидается числовое поле data-property-id="4".'
);

$initialBody = is_string($initialPage['body']) ? $initialPage['body'] : '';
check(
    strpos($initialBody, '&lt;текст&gt;') !== false
        && strpos($initialBody, '<текст>') === false,
    'Текст из БД экранируется и не создаёт HTML-элементы.',
    'Ожидается &lt;текст&gt; и отсутствие сырого <текст>.'
);

$mixedBody = is_string($mixedCategories['body']) ? $mixedCategories['body'] : '';
check(
    $mixedCategories['status'] >= 200
        && $mixedCategories['status'] < 300
        && !preg_match('/(?:Warning|Notice|Fatal error|PDOException)\b/i', $mixedBody),
    'Некорректные элементы category не вызывают видимых PHP-ошибок.',
    'HTTP: ' . $mixedCategories['status']
);

echo PHP_EOL . 'Результат: ' . $passed . ' успешно, ' . $failed . ' с ошибкой.' . PHP_EOL;

exit($failed > 0 ? 1 : 0);
