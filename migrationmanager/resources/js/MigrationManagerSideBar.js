(function($) {

    Craft.MigrationManagerSideBar = Garnish.Base.extend({
        init: function (count) {
           $('#nav-migrationmanager').prepend('<span data-icon="newstamp"><span>' + count +'</span></span>');
        }
    });

})(jQuery);