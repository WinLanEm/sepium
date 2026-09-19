(function ($) {
    'use strict';

    function collectCategories() {
        var cats = [];

        $('.js-category:checked').each(function () {
            cats[cats.length] = $(this)
                .closest('.add_good_name_category')
                .attr('data-category-chpu');
        });

        return cats;
    }

    function collectPropertyValues() {
        var propertyMas = {};

        $('.property_all .name_select_rielt').each(function () {
            var $property = $(this);
            var propertyId = $property.attr('data-property');
            var $multiple = $property.find('.checkbox_property');
            var values = [];
            var $field;
            var value;

            if (propertyId === undefined || propertyId === '') {
                return;
            }

            if ($multiple.length > 0) {
                $multiple.find('input[type="checkbox"]:checked').each(function () {
                    values[values.length] = $(this)
                        .closest('.line_chek')
                        .find('.ckeck_param')
                        .attr('data-val');
                });

                if (values.length > 0) {
                    propertyMas[propertyId] = values.join(':::');
                }
                return;
            }

            $field = $property.find('input.ag_pole_good, select.ag_pole_good').first();
            value = $field.val();

            if (value !== undefined && value !== null && value !== '') {
                propertyMas[propertyId] = value;
            }
        });

        return propertyMas;
    }

    $('body').on('click', '.addgood_click', function () {
        var $button = $(this);

        if ($button.data('property-refresh-pending')
            || $button.data('properties-synchronized') === false) {
            return;
        }

        $button.data('preview-pending', true).prop('disabled', true).text('Проверяем…');

        $.ajax({
            type: 'POST',
            url: './admin/ajax/Preview_Good_Payload.php',
            dataType: 'json',
            data: {
                cats: collectCategories(),
                property_mas: collectPropertyValues()
            },
            success: function (data) {
                $('.js-payload-preview').text(JSON.stringify(data, null, 2));
            },
            error: function () {
                $('.js-payload-preview').text('Не удалось проверить отправку.');
            },
            complete: function () {
                $button.data('preview-pending', false)
                    .prop(
                        'disabled',
                        !!$button.data('property-refresh-pending')
                            || $button.data('properties-synchronized') === false
                    )
                    .text('Проверить отправку');
            }
        });
    });
}(jQuery));
