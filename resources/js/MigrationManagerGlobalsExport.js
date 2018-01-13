(function($) {

    Craft.MigrationManagerGlobalsExport = Garnish.Base.extend({
        init: function () {
            $('input.checkbox.checkbox-toggle').on('click', this.toggleSelections);
            $('input.checkbox.checkbox-all').on('click', this.toggleAllSelections);



            $('div.body form div.buttons').append('&nbsp;<a href="#" class="btn submit create-migration">Create Migration</a>');

            $('a.create-migration').on('click', this.createMigration);
        },

        createMigration: function(evt) {
            $('form input[name="action"]').val('migrationManager/createGlobalsContentMigration');
            $('div.body form').submit();
            return true;
        },

        toggleAllSelections: function(evt) {
            //var selector = $(this).attr('data-selector');
            if ($(this).is(':checked')) {

                $('input[data-selector="' + $(this).attr('data-selector') +'"]').prop('checked', true);
            } else {
                $('input[data-selector="' + $(this).attr('data-selector') +'"]').prop('checked', false);
            }
        }


    });

})(jQuery);