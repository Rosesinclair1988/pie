AmazonListingCategoryHandler = Class.create();
AmazonListingCategoryHandler.prototype = Object.extend(new CommonHandler(), {

    //----------------------------------

    initialize: function() {},

    //----------------------------------

    save_click: function(action)
    {
        array_unique(categories_selected_items);

        if (categories_selected_items.length <= 0) {
            alert(M2ePro.text.select_items_message);
            return;
        }

        var selectedCategories = implode(',',categories_selected_items);

        var url = action+'selected_categories/'+selectedCategories+'/';
        url += 'categories_add_action/' + $('categories_add_action').value + '/';
        url += 'categories_delete_action/' + $('categories_delete_action').value + '/';
        document.location.assign(url);
    },

    //----------------------------------

    categories_products_from_change: function() {

        var value = $('categories_products_from').value;

        if (value == 'manual') {
            $$('.save_and_next_button').each(function(o) { o.show(); });
            $$('.save_and_go_to_listings_list_button').each(function(o) { o.hide(); });
            $$('.save_and_go_to_listing_view_button').each(function(o) { o.hide(); });
        } else {
            $$('.save_and_next_button').each(function(o) { o.hide(); });
            $$('.save_and_go_to_listings_list_button').each(function(o) { o.show(); });
            $$('.save_and_go_to_listing_view_button').each(function(o) { o.show(); });
        }
    }

    //----------------------------------
});
