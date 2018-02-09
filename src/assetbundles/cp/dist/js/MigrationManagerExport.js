(function($) {

    Craft.MigrationManagerExport = Garnish.Base.extend({
        init: function() {
            $('input.checkbox.checkbox-toggle').on('click', this.toggleSelections);
            $('input.checkbox.checkbox-all').on('click', this.toggleAllSelections);
        },

        toggleSelections: function(evt) {
            var selector = $(this).attr('data-selector');
            if ($(this).is(':checked')) {
                $('input[name^="' + selector + '"]').prop('checked', true);
            } else {
                $('input[name^="' + selector + '"]').prop('checked', false);
            }
        },

        toggleAllSelections: function(evt) {
            //var selector = $(this).attr('data-selector');
            if ($(this).is(':checked')) {

                $('input[data-selector="' + $(this).attr('data-selector') + '"]').prop('checked', true);
            } else {
                $('input[data-selector="' + $(this).attr('data-selector') + '"]').prop('checked', false);
            }
        }
    });

})(jQuery);