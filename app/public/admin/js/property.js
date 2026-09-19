(function ($) {
    'use strict';

    var refreshRequest = null;
    var refreshSequence = 0;

    function collectCategories() {
        var category = [];

        $('.js-category:checked').each(function () {
            category[category.length] = $(this).val();
        });

        return category;
    }

    function collectPropertyState($properties) {
        var state = {};

        $properties.find('.name_select_rielt').each(function () {
            var $property = $(this);
            var propertyId = $property.attr('data-property');
            var $checkboxes = $property.find('.checkbox_property input[type="checkbox"]');
            var values = [];
            var $field;

            if ($checkboxes.length > 0) {
                $checkboxes.filter(':checked').each(function () {
                    values[values.length] = $(this)
                        .closest('.line_chek')
                        .find('.ckeck_param')
                        .attr('data-val');
                });
                state[propertyId] = values;
                return;
            }

            $field = $property.find('input.ag_pole_good, select.ag_pole_good').first();
            if ($field.length > 0) {
                state[propertyId] = $field.val();
            }
        });

        return state;
    }

    function restorePropertyState($properties, state) {
        $properties.find('.name_select_rielt').each(function () {
            var $property = $(this);
            var propertyId = $property.attr('data-property');
            var $checkboxes;
            var $field;

            if (!Object.prototype.hasOwnProperty.call(state, propertyId)) {
                return;
            }

            $checkboxes = $property.find('.checkbox_property input[type="checkbox"]');
            if ($checkboxes.length > 0 && $.isArray(state[propertyId])) {
                $checkboxes.each(function () {
                    var answerId = $(this)
                        .closest('.line_chek')
                        .find('.ckeck_param')
                        .attr('data-val');

                    $(this).prop('checked', $.inArray(answerId, state[propertyId]) !== -1);
                });
                return;
            }

            $field = $property.find('input.ag_pole_good, select.ag_pole_good').first();
            if ($field.length > 0 && !$.isArray(state[propertyId])) {
                $field.val(state[propertyId]);
            }
        });
    }

    function updatePreviewAvailability($button) {
        var refreshPending = !!$button.data('property-refresh-pending');
        var propertiesSynchronized = $button.data('properties-synchronized') !== false;

        $button.prop(
            'disabled',
            refreshPending || !propertiesSynchronized || !!$button.data('preview-pending')
        );
    }

    function setRefreshPending($button, isPending) {
        $button.data('property-refresh-pending', isPending);
        updatePreviewAvailability($button);
    }

    function setPropertiesSynchronized($button, isSynchronized) {
        $button.data('properties-synchronized', isSynchronized);
        updatePreviewAvailability($button);
    }

    // Выбор категории в товаре и обновление блока характеристик.
    $('body').on('change', '.js-category', function () {
        var requestId = ++refreshSequence;
        var $properties = $('.property_all');
        var $previewButton = $('.addgood_click');

        $(this).closest('.add_good_name_category')
            .toggleClass('category_checked is-selected', this.checked);

        if (refreshRequest !== null) {
            refreshRequest.abort();
        }

        $properties.addClass('is-loading').attr('aria-busy', 'true');
        setPropertiesSynchronized($previewButton, false);
        setRefreshPending($previewButton, true);

        refreshRequest = $.ajax({
            type: 'POST',
            url: './admin/ajax/property/Refresh_Property_Good.php',
            dataType: 'html',
            data: { category: collectCategories() },
            success: function (data) {
                var state;

                if (requestId !== refreshSequence) {
                    return;
                }

                state = collectPropertyState($properties);
                $properties.html(data);
                restorePropertyState($properties, state);
                setPropertiesSynchronized($previewButton, true);
            },
            complete: function () {
                if (requestId !== refreshSequence) {
                    return;
                }

                refreshRequest = null;
                $properties.removeClass('is-loading').attr('aria-busy', 'false');
                setRefreshPending($previewButton, false);
            }
        });
    });
}(jQuery));
