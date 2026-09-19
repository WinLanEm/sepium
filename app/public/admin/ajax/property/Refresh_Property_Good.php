<?php

require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/src/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

$category = isset($_POST['category']) ? $_POST['category'] : array();

echo (new PropertyForm(db()))->render($category);
