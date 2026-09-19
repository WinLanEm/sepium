<?php

class PropertyForm
{
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function render($categoryInput)
    {
        $categoryIds = $this->normalizeCategoryIds($categoryInput);
        $properties = $this->fetchProperties($categoryIds);
        $answersByProperty = $this->fetchAnswersByProperty($properties);
        $html = '';

        foreach ($properties as $property) {
            $html .= $this->renderProperty($property, $answersByProperty);
        }

        return $html;
    }

    private function normalizeCategoryIds($categoryInput)
    {
        if (!is_array($categoryInput)) {
            return array();
        }

        $categoryIds = array();

        foreach ($categoryInput as $categoryId) {
            if (is_int($categoryId)) {
                $normalizedId = $categoryId;
            } elseif (is_string($categoryId) && ctype_digit($categoryId)) {
                $normalizedId = (int) $categoryId;
            } else {
                continue;
            }

            if ($normalizedId > 0) {
                $categoryIds[$normalizedId] = $normalizedId;
            }
        }

        return array_values($categoryIds);
    }

    private function fetchProperties($categoryIds)
    {
        $sql = 'SELECT id, name_prop, place_prop, type_prop, cat_prop, sort_prop
            FROM property_s
            WHERE cat_prop = ?';
        $parameters = array('');

        if (count($categoryIds) > 0) {
            $categoryConditions = array();

            foreach ($categoryIds as $categoryId) {
                $categoryConditions[] = "FIND_IN_SET(?, REPLACE(cat_prop, ' ', '')) > 0";
                $parameters[] = (string) $categoryId;
            }

            $sql .= ' OR (' . implode(' OR ', $categoryConditions) . ')';
        }

        $sql .= ' ORDER BY sort_prop, id';

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    private function fetchAnswersByProperty($properties)
    {
        $propertyIds = array();

        foreach ($properties as $property) {
            $type = (int) $property['type_prop'];

            if ($type === 2 || $type === 3) {
                $propertyIds[] = (int) $property['id'];
            }
        }

        if (count($propertyIds) === 0) {
            return array();
        }

        $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
        $sql = 'SELECT id, id_prop, answer_prop, sort_answer
            FROM property_answer_s
            WHERE id_prop IN (' . $placeholders . ')
            ORDER BY id_prop, sort_answer, id';
        $statement = $this->connection->prepare($sql);
        $statement->execute($propertyIds);
        $answersByProperty = array();

        while ($answer = $statement->fetch()) {
            $propertyId = (int) $answer['id_prop'];

            if (!isset($answersByProperty[$propertyId])) {
                $answersByProperty[$propertyId] = array();
            }

            $answersByProperty[$propertyId][] = $answer;
        }

        return $answersByProperty;
    }

    private function renderProperty($property, $answersByProperty)
    {
        $propertyId = (int) $property['id'];
        $propertyIdHtml = h($propertyId);
        $name = h($property['name_prop']);
        $help = '';

        if ($property['place_prop'] !== '') {
            $help = '<div class="field-help">' . h($property['place_prop']) . '</div>';
        }

        $field = $this->renderField(
            (int) $property['type_prop'],
            $name,
            isset($answersByProperty[$propertyId]) ? $answersByProperty[$propertyId] : array()
        );

        if ($field === '') {
            return '';
        }

        return '<div class="property-field name_select_rielt" data-property="' . $propertyIdHtml
            . '" data-property-id="' . $propertyIdHtml . '">
            <div class="field-label name">' . $name . '</div>
            ' . $help . '
            ' . $field . '
        </div>';
    }

    private function renderField($type, $name, $answers)
    {
        if ($type === 1) {
            return '<input type="text" class="text-input add-inp ag_pole_good" placeholder="'
                . $name . '">';
        }

        if ($type === 2) {
            return '<select class="text-input ag_pole_good">
                <option value="">Не выбрано</option>' . $this->renderOptions($answers) . '
            </select>';
        }

        if ($type === 3) {
            return '<div class="choice-grid checkbox_property ag_pole_good">'
                . $this->renderCheckboxes($answers) . '</div>';
        }

        if ($type === 4) {
            return '<input type="text" inputmode="decimal" class="text-input add-inp ag_pole_good" placeholder="Числовое значение">';
        }

        return '';
    }

    private function renderOptions($answers)
    {
        $html = '';

        foreach ($answers as $answer) {
            $html .= '<option value="' . h($answer['id']) . '">'
                . h($answer['answer_prop']) . '</option>';
        }

        return $html;
    }

    private function renderCheckboxes($answers)
    {
        $html = '';

        foreach ($answers as $answer) {
            $html .= '<label class="choice line_chek">
                <input type="checkbox">
                <span class="ckeck_param" data-val="' . h($answer['id']) . '">'
                . h($answer['answer_prop']) . '</span>
            </label>';
        }

        return $html;
    }
}
