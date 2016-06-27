AmazonCategoryHandler = Class.create();
AmazonCategoryHandler.prototype = Object.extend(new CommonHandler(), {

    //----------------------------------

    initialize: function(M2ePro,specificHandler)
    {
        var self = this;

        self.M2ePro = M2ePro;
        self.specificHandler = specificHandler;
        self.specificHandler.categoryHandler = self;

        self.xsdsTr = $('xsds_tr');
        self.categoriesTr  = $('categories_tr');
        self.changeButton  = $('category_change_button_container');
        self.cancelButton  = $('category_cancel_button_container');
        self.confirmButton = $('category_confirm_button_container');
        self.nodeTitleEl   = $('node_title');
        self.categoriesContainer = $('categories_container');
        self.nodeHashHiddenInput = $('node_hash');
        self.categoryXsdHashHiddenInput = $('xsd_hash');

        self.categoryPathHiddenInput = self.categoriesContainer.parentNode.appendChild(
            new Element('input', {'type': 'hidden','name': "category[path]",'class': 'required-entry'})
        );

        self.M2ePro.formData.category.category_path && self.getCategories(self.nodeHashHiddenInput.value,function(transport) {
            self.categories = transport.responseText.evalJSON();
            self.categoryPathHiddenInput.value = self.M2ePro.formData.category.category_path;

            var spanEl = new Element('span',{'class': 'nobr','style': 'font-weight: bold'});

            self.categoriesContainer.parentNode.insert({'top': spanEl});
            spanEl.insert(self.M2ePro.formData.category.category_path);
            self.specificHandler.run(self.M2ePro.formData.category.xsd_hash);
        });

        Validation.add('M2ePro-category-title', self.M2ePro.text.title_not_unique_error, function(value, element) {
            var checkResult = false;

            new Ajax.Request( self.M2ePro.url.check_repetition,
                {
                    method: 'post',
                    asynchronous : false,
                    parameters : {
                        title : value,
                        id: self.M2ePro.formData.category.id
                    },
                    onSuccess: function (transport)
                    {
                        checkResult = transport.responseText.evalJSON()['result'];
                    }
                });

            return checkResult;
        });

    },

    //----------------------------------

    node_title_change: function(element)
    {
        this.parentId = null;
        this.categoriesTr.show();
        this.categoriesContainer.show();
        this.categoriesContainer.update();
        this.removeSpanWithCategoryPath();
        this.changeButton.hide();
        this.confirmButton.hide();
        this.xsdsTr.hide();

        this.getCategories(element.down('option[value=' + element.value + ']').getAttribute('node_hash'),function(transport) {
            this.categories = transport.responseText.evalJSON();
            this.showCategories(this.categoriesContainer);
        });

    },

    //----------------------------------

    getCategories: function(nodeHash,callback)
    {
        var self = this;

        new Ajax.Request(self.M2ePro.url.getCategories,
        {
            method : 'get',
            asynchronous : true,
            parameters : {
                node_hash : nodeHash
            },
            onSuccess: function(transport) {
                callback.call(self,transport);
            }
        });
    },

    //----------------------------------

    showCategories: function(container)
    {
        var self       = this;
        var categories = [];

        var old = $('xsds');
            old && old.parentNode.removeChild(old);

        self.xsdsTr.hide();
        self.categoryPathHiddenInput.value = '';
        self.confirmButton.hide();
        self.cancelButton.hide();
        self.removeContainers(container.parentNode,container);
        self.specificHandler.specificsContainer.update();
        self.specificHandler.specificsMainContainer.hide();

        var selectEl = container.appendChild(new Element('select'));
            selectEl.appendChild(new Element('option',{'value': 'empty','style': 'display: none'}));

        self.categories.each(function(category) {
            category.parent_id === self.parentId && categories.push(category)
        });

        categories.sort(function(a,b) {
            return a.sorder - b.sorder;
        });

        if (categories.length == 0 || self.getCategoryInfo('id',self.parentId).is_listable == 1) {
            self.confirmButton.show();
            if (categories.length == 0) {
                selectEl.hide();
                return '';
            }
        }

        categories.each(function(category) {
            selectEl.appendChild(new Element('option',{'value': category.id}))
                    .update(category.title);
        });

        selectEl.observe('change',function(event) {
            self.parentId = this.value;
            self.showCategories(container.appendChild(new Element('div')));
        });
    },

    //----------------------------------

    getCategoryInfo: function(key,value)
    {
        var info = {};
        this.categories.each(function(category) {
            if (value == category[key]) {
                return info = category;
            }
        });
        return info;
    },

    removeContainers: function(container,doNotDeleteContainer)
    {
        container.childElements().each(function(child) {
            child.localName == 'div' && child != doNotDeleteContainer && container.removeChild(child)
        });
    },

    //----------------------------------

    confirmCategory: function()
    {
        this.changeButton.show();
        this.categoriesContainer.hide();
        this.confirmButton.hide();
        this.cancelButton.hide();
        this.specificHandler.specificsContainer.update();
        this.specificHandler.specificsMainContainer.hide();

        var spanEl = new Element('span',{'class': 'nobr','style': 'font-weight: bold'});
        this.categoriesContainer
            .parentNode
            .insert({'top': spanEl});

        var categoryInfo = this.getCategoryInfo('id',this.parentId);

        var categoryPath = categoryInfo.path.replace(/->/g,' > ') + ' > ' + categoryInfo.title;
        spanEl.insert(categoryPath);
        this.categoryPathHiddenInput.value = categoryPath;

        categoryInfo.xsd_hash
            ? this.categoryXsdHashHiddenInput.setValue(categoryInfo.xsd_hash) && this.specificHandler.run(categoryInfo.xsd_hash)
            : this.renderGetXsdHash(categoryInfo.node_hash);
    },

    changeCategory: function()
    {
        this.changeButton.hide();
        this.confirmButton.show();
        this.cancelButton.show();
        this.removeSpanWithCategoryPath();
        this.categoriesContainer.show();
    },

    cancelCategory: function()
    {
        this.cancelButton.hide();
        this.confirmButton.hide();
        this.categoriesTr.hide();
        this.nodeTitleEl.simulate('change');
    },

    //----------------------------------

    removeSpanWithCategoryPath: function()
    {
        var spanEl = this.categoriesContainer.parentNode.firstChild;
        spanEl.localName == 'span' && this.categoriesContainer.parentNode.removeChild(spanEl);
    },

    //----------------------------------

    renderGetXsdHash: function(nodeHash)
    {
        var self = this;
            self.xsdsTr.show();

        var old = $('xsds');
            old && old.parentNode.removeChild(old);

        var select = new Element('select',{
            'id': 'xsds',
            'class': 'required-entry'
        });

        self.categoryXsdHashHiddenInput.insert({'after': select});

        select.observe('change',function() {
            self.specificHandler.specificsMainContainer.hide();
            self.specificHandler.run(this.value);
            self.categoryXsdHashHiddenInput.value = this.value;
        });

        new Ajax.Request(self.M2ePro.url.getXsds,
        {
            method : 'get',
            asynchronous : true,
            parameters : {
                node_hash : nodeHash
            },
            onSuccess: function(transport) {
                select.update();
                select.appendChild(new Element('option',{'style': 'display: none'}));

                transport.responseText.evalJSON().each(function(xsd) {
                    select.appendChild(new Element('option',{'value': xsd.hash}))
                          .insert(xsd.title);
                });
            }
        });

    }

    //----------------------------------
});
