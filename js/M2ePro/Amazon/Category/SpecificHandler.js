AmazonCategorySpecificHandler = Class.create();
AmazonCategorySpecificHandler.prototype = Object.extend(new CommonHandler(), {

    //----------------------------------

    initialize: function(M2ePro)
    {
        this.TYPE_TEXT = 1;
        this.TYPE_SELECT = 2;
        this.TYPE_CONTAINER = 3;
        this.TYPE_CONTAINER_SELECT = 4;
        this.TYPE_VIRTUAL_CONTAINER_SELECT = 5;

        this.M2ePro = M2ePro;
        this.specificsContainer = $('specifics_container');
        this.specificsMainContainer = $('magento_block_specific_edit_general');

        var self = this;

        Validation.add('M2ePro-container-select-choose', this.M2ePro.text.press_choose_btn, function(value, el) {
            return el.disabled;
        });

        Validation.add('M2ePro-specifics-validation', this.M2ePro.text.invalid_data, function(value, element) {
            if (!element.up('tr').visible()) {
                return true;
            }

            var params = self.specifics[element.getAttribute('id')].params.evalJSON();
            return self[params.type + 'TypeValidator'](value,params,element);
        });

        Validation.add('M2ePro-specificAttributes-validation', this.M2ePro.text.invalid_data, function(value, element) {
            if (!element.up('tr').visible()) {
                return true;
            }

            var params = self.specifics[element.getAttribute('id')].params.evalJSON().attributes[element.getAttribute('index')];
            return self[params.type + 'TypeValidator'](value,params,element);
        });

    },

    //----------------------------------

    intTypeValidator: function(value,params,element) {

        value = value.replace(',','.');

        if (isNaN(parseInt(value)) ||
            substr_count(value,'.') > 0) {
            return false;
        }

        var validators = {
            'min_value': function(value,restriction)
            {
                return parseInt(value) >= parseInt(restriction);
            },
            'max_value': function(value,restriction)
            {
                return parseInt(value) <= parseInt(restriction);
            },
            'total_digits': function(value,restriction)
            {
                return value.length <= parseInt(restriction);
            }
        };

        for (var paramName in params) {
            if (params.hasOwnProperty(paramName) && validators[paramName]) {
                if (!validators[paramName](value,params[paramName])) {
                    return false;
                }
            }
        }

        return true;
    },

    date_timeTypeValidator: function(value,params,element) {
        return /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/g.test(value);
    },

    stringTypeValidator: function(value,params,element) {

        var validators = {
            'min_length': function(value,restriction)
            {
                return value.length >= parseInt(restriction);
            },
            'max_length': function(value,restriction)
            {
                return value.length <= parseInt(restriction);
            },
            'pattern': function(value,restriction)
            {
                return value.match(new RegExp(restriction));
            }
        };

        for (var paramName in params) {
            if (params.hasOwnProperty(paramName) && validators[paramName]) {
                if (!validators[paramName](value,params[paramName])) {
                    return false;
                }
            }
        }

        return true;
    },

    floatTypeValidator: function(value,params,element) {

        value = value.replace(',','.');

        if (isNaN(parseFloat(value)) ||
            substr_count(value,'.') > 1 ||
            value.substr(-1) == '.') {
            return false;
        }

        var validators = {
            'min_value': function(value,restriction)
            {
                return parDeleteseFloat(value) >= parseFloat(restriction);
            },
            'max_value': function(value,restriction)
            {
                return parseFloat(value) <= parseFloat(restriction);
            },
            'total_digits': function(value,restriction)
            {
                return value.replace('.','').length <= restriction;
            },
            'decimal_places': function(value,restriction)
            {
                return value.indexOf('.') != -1 ? (value.replace(/\d*\./,'').length <= restriction.value) : true;
            }
        };

        for (var paramName in params) {
            if (params.hasOwnProperty(paramName) && validators[paramName]) {
                if (!validators[paramName](value,params[paramName])) {
                    return false;
                }
            }
        }

        return true;
    },

    //----------------------------------

    run: function(xsd_hash)
    {
        this.xsd_hash = xsd_hash;
        this.parent = {'id': null};
        this.specificsContainer.update();
        this.getSpecifics();
    },

    //----------------------------------

    getSpecifics: function()
    {
        var self = this;

        new Ajax.Request(self.M2ePro.url.getSpecifics,{
            method: 'get',
            asynchronous: true,
            parameters: {
                xsd_hash: self.xsd_hash
            },
            onSuccess: function(transport) {
                try {
                    self.specificsMainContainer.show();
                    self.specifics = transport.responseText.evalJSON();

                    self.M2ePro.formData.specifics.length > 0
                        ? self.renderSpecificsEditMode()
                        : self.renderSpecifics(self.getChildSpecifics(self.parent),self.specificsContainer);

                } catch (e) {
                    alert('Unexpected error happened');
                }
            }
        });
    },

    //----------------------------------

    renderSpecificsEditMode: function()
    {
        var self = this;

        self.M2ePro.formData.specifics.each(function(specific) {

            try {
                var dictionarySpecific = self.getDictionarySpecific(specific.xpath);

                // checking if this specific is parent
                if (dictionarySpecific.parent_id == null) {
                    self.renderSpecifics([dictionarySpecific],self.specificsContainer,null,true);

                    return '';
                }

                // checking if this specific is already rendered
                if (self.isAlreadyRendered(specific)) {

                    dictionarySpecific.type == self.TYPE_TEXT && self.setValues(specific);
                    dictionarySpecific.type == self.TYPE_SELECT && self.setValues(specific);

                    return '';
                }

                var orig;

                // checking if this specific was cloned
                orig = dictionarySpecific.max_occurs > 1 && self.getOriginalSpecific(specific);

                if (orig) {
                    orig.parentNode
                        .down('button[add_button_id=' + dictionarySpecific.id + ']').simulate('click');

                    var origIndex = parseInt(orig.up('tr[index_number]').getAttribute('index_number'));
                    var realXpath = specific.xpath.replace(/\d*$/,origIndex + 1);
                    var specificXpath = specific.xpath;

                    self.M2ePro.formData.specifics.each(function(tmpSpecific) {
                        tmpSpecific.xpath = tmpSpecific.xpath.replace(specificXpath,realXpath);
                    });

                    specific.xpath = realXpath;

                    dictionarySpecific.type == self.TYPE_TEXT && self.setValues(specific);
                    dictionarySpecific.type == self.TYPE_SELECT && self.setValues(specific);

                    return '';
                }

                self.makeParentXpath(specific);
                self.makeParentId(specific);

                var select;

                // checking if this specific was chosen
                select = self.getChosenSelect(specific);
                if (select) {
                    select.value = dictionarySpecific.id;
                    select.next('button[add_button_id=' + specific.parentId + ']').simulate('click');

                    dictionarySpecific.type == self.TYPE_TEXT && self.setValues(specific);
                    dictionarySpecific.type == self.TYPE_SELECT && self.setValues(specific);

                    return '';
                }

                // otherwise it was added
                select = self.getAddedSelect(specific);
                select.value = dictionarySpecific.id;
                select.next('button[add_button_id=' + specific.parentId + ']').simulate('click');

                dictionarySpecific.type == self.TYPE_TEXT && self.setValues(specific);
                dictionarySpecific.type == self.TYPE_SELECT && self.setValues(specific);
            } catch (e) {
                return ''; // continue on fail
            }
        });
    },

    getDictionarySpecific: function(xpath)
    {
        var dictionarySpecific;

        xpath = xpath.replace(/\/\-\d{1,}\//g,'/')
                     .replace(/\-\d*/g,'');

        $H(this.specifics).each(function(specificData) {
            var specific = specificData.pop();
            if (specific.xpath == xpath) {
                dictionarySpecific = specific;
                throw $break;
            }
        });

        return dictionarySpecific;
    },

    isAlreadyRendered: function(specific)
    {
        var prevSpecific = this.M2ePro.formData.specifics[this.M2ePro.formData.specifics.indexOf(specific) - 1];

        if (prevSpecific.xpath.indexOf(specific.xpath.replace(/\-\d*$/,'')) == -1) {

            var specificXpath = specific.xpath;
            var realXpath = specific.xpath.replace(/\d*$/,1);

            this.M2ePro.formData.specifics.each(function(tmpSpecific) {
                tmpSpecific.xpath = tmpSpecific.xpath.replace(specificXpath,realXpath);
            });

            specific.xpath = realXpath;
        }

        return this.specificsContainer.down("*[name='specifics[" + specific.xpath + "][mode]']");
    },

    getOriginalSpecific: function(specific)
    {
        var i = specific.xpath.match(/\d*$/).shift();

        while (i > 1) {
            var originalXpath = specific.xpath.replace(/\d*$/,--i);

            var original = this.specificsContainer.down("*[name='specifics[" + originalXpath + "][mode]']");

            if (original) {
                return original;
            }
        }

        return null;
    },

    makeParentXpath: function(specific)
    {
        specific.parentXpath = specific.xpath.split('/');
        specific.parentXpath.pop();
        specific.parentXpath = specific.parentXpath.join('/');
    },

    makeParentId: function(specific)
    {
        specific.parentId = this.getDictionarySpecific(specific.parentXpath).id;
    },

    getChosenSelect: function(specific)
    {
        try {
            return this.specificsContainer
                .down("*[name='specifics[" + specific.parentXpath + "][mode]']")
                .parentNode.down('select[id=' + specific.parentId + ']');
        } catch (e) {
           return null;
        }
    },

    getAddedSelect: function(specific)
    {
        try {
            return this.specificsContainer
                .down("*[name='specifics[" + specific.parentXpath + "][mode]']")
                .up('table').down('select[id=' + specific.parentId + ']');
        } catch (e) {
            specific.parentXpath = specific.parentXpath.split('/');
            specific.parentXpath.pop();
            specific.parentXpath = specific.parentXpath.join('/');
            this.makeParentId(specific);

            return this.specificsContainer
                .down("*[name='specifics[" + specific.parentXpath + "][mode]']")
                .up('table').down('select[id=' + specific.parentId + ']');
        }
    },

    //----------------------------------

    setValues: function(specific)
    {
        var selectMode = this.specificsContainer.down("*[name='specifics[" + specific.xpath + "][mode]']");
            selectMode.value = specific.mode;
            selectMode.simulate('change');

        var table = selectMode.up('table');
            table.down("*[name='specifics[" + specific.xpath + "][" + specific.mode + "]']")
                 .value = specific[specific.mode];

        specific.attributes.evalJSON().each(function(attribute,attrIndex) {
            for (var attributeName in attribute);

            var selectAttrMode = table.down("*[name='specifics[" + specific.xpath + "][attributes][" + attrIndex + "][" + attributeName + "][mode]']");
                selectAttrMode.value = attribute[attributeName].mode;
                selectAttrMode.simulate('change');

            table.down("*[name='specifics[" + specific.xpath + "][attributes][" + attrIndex + "][" + attributeName + "][" + attribute[attributeName].mode + "]']")
                 .value = attribute[attributeName][attribute[attributeName].mode];
        });
    },

    //----------------------------------

    renderSpecifics: function(specifics,container,renderCallback,force)
    {
        if (specifics.length < 1) {
            return '';
        }

        var self = this;
        renderCallback = renderCallback || self.renderSpecific;

        var tempSpecifics = self.getRequiredAndUnrequiredSpecifics(specifics);

        var requiredSpecifics   = force ? specifics : tempSpecifics.required;
        var unrequiredSpecifics = force ? [] : tempSpecifics.unrequired;

        requiredSpecifics.length > 0 && self.renderRequiredSpecifics(requiredSpecifics,container,renderCallback);
        unrequiredSpecifics.length > 0 && self.renderUnrequiredSpecifics(unrequiredSpecifics,container);
    },

    //----------------------------------

    getRequiredAndUnrequiredSpecifics: function(specifics)
    {
        var requiredSpecifics = [];
        var unrequiredSpecifics = [];

        specifics.each(function(specific) {
            (specific.min_occurs == 1 || specific.title == 'Product Type')
                ? requiredSpecifics.push(specific)
                : unrequiredSpecifics.push(specific);
        });

        return {'required': requiredSpecifics,
                'unrequired': unrequiredSpecifics};
    },

    //----------------------------------

    renderRequiredSpecifics: function(specifics,container,renderCallback)
    {
        var self = this;

        specifics.each(function(specific) {

            switch (parseInt(specific.type)) {

                case self.TYPE_TEXT:
                    renderCallback.call(self,self.getTextTypeContent,specific,container);
                    break;

                case self.TYPE_SELECT:
                    renderCallback.call(self,self.getSelectTypeContent,specific,container);
                    break;

                case self.TYPE_CONTAINER:
                    self.getContainerTypeContent(specific,container);
                    break;

                case self.TYPE_CONTAINER_SELECT:
                    self.getContainerSelectTypeContent(specific,container);
                    break;

                case self.TYPE_VIRTUAL_CONTAINER_SELECT:
                    self.getVirtualContainerSelectTypeContent(specific,container);
                    break;
            }
        });
    },

    //----------------------------------

    renderUnrequiredSpecifics: function(specifics,container,renderCallback)
    {
        var self = this;
        var parent_id = specifics[0].parent_id;

        var labelDiv = new Element('div',{'style': 'padding: 15px 0'});
        var label = labelDiv.appendChild(new Element('label',{'style': 'font-weight: bold; font-style: italic'}).insert('Choose an option: '));

        var contentDiv = new Element('div',{'style': 'padding: 15px 0'});
        var select = contentDiv.appendChild(new Element('select',{'id': parent_id}));

        select.appendChild(new Element('option',{'style': 'display: none; '}));
        specifics.each(function(specific) {

            if (specific.type == self.TYPE_VIRTUAL_CONTAINER_SELECT) {

                self.getChildSpecifics(specific).each(function(childSpecific) {
                    select.appendChild(new Element('option',{
                        'choice': true,
                        'parent_id': specific.id,
                        'value': childSpecific.id
                    })).insert(childSpecific.title);
                });

                return '';
            }

            select.appendChild(new Element('option',{'value': specific.id})).insert(specific.title);
        });

        select.observe('change',function() {
            select.next('button').show();
        });

        var tr = container.appendChild(new Element('tr'));

        tr.appendChild(new Element('td',{'class': 'label'})).insert(labelDiv);
        tr.appendChild(new Element('td',{'class': 'value'})).insert(contentDiv);

        self.appendAddButton({'id': parent_id},contentDiv,container);
    },

    //----------------------------------

    renderSpecific: function(contentCallback,specific,container)
    {
        container = this.newContainer(container,{
            'tr': {'id': specific.id,'index_number': 1},
            'table': {'class': 'form-list','cellspacing': 0,'cellpadding': 0}
        });

        specific.container = container;

        this.makePath(specific);
        this.renderHr(container);
        this.renderChooseMode(specific,container);
        this.renderModeCustomValue(specific,container,contentCallback);
        this.renderModeCustomAttribute(specific,container);
        this.renderAttributes(specific,container);
    },

    //----------------------------------

    getLabel: function(specific)
    {
        var title = specific.title + ': <span class="required">*</span>';
        var div = new Element('div');
            div.appendChild((new Element('label').insert(title)));

        return div;
    },

    //----------------------------------

    getTextTypeContent: function(specific)
    {
        var div = new Element('div');
        var params = specific.params.evalJSON();

        var input = div.appendChild(new Element('input',{
            'id': specific.id,
            'name': 'specifics[' + specific.path + "][custom_value]",
            'type': 'text',
            'class': 'input-text M2ePro-required-when-visible M2ePro-specifics-validation'
        }));

        params.type == 'date_time' && Calendar.setup({
            'inputField': input,
            'ifFormat': "%Y-%m-%d %H:%M:%S",
            'showsTime': true,
            'button': input,
            'align': 'Bl',
            'singleClick' : true
        });

        div.appendChild(new Element('input',{
            'id': specific.id,
            'name': 'specifics[' + specific.path + "][type]",
            'type': 'hidden',
            'value': params.type
        }));

        var note = this['get' + ucwords(params.type) + 'TypeNote'](params);

        div.appendChild(new Element('p',{'class': 'note'}))
           .appendChild(new Element('span'))
           .insert(note);

        return div;
    },

    getSelectTypeContent: function(specific)
    {
        var div = new Element('div');

        var select = div.appendChild(new Element('select',{
            'id': specific.id,
            'name': 'specifics[' + specific.path + "][custom_value]",
            'class': 'M2ePro-required-when-visible'
        }));

        select.appendChild(new Element('option',{'style': 'display: none; '}));

        specific.values.evalJSON().each(function(value) {
            var label = value == 'true'  ? 'Yes' :
                       (value == 'false' ? 'No'  : value);

            select.appendChild(new Element('option',{'value': value})).insert(label);
        });

        return div;
    },

    getContainerTypeContent: function(specific,container)
    {
        var self = this;

        container = self.newContainer(container,{
            'tr': {
                'id': specific.id,
                'index_number': 1
            },
            'table': {
                'class': 'form-list',
                'style': specific.parent_id == null ? '' : 'padding: 0; margin: 10px 0; border-right: 1px solid #D6D6D6 !important; border-bottom: 1px solid #D6D6D6 !important; border-left: 1px solid #D6D6D6 !important',
                'cellspacing': 0,
                'cellpadding': 0
            }
        });

        specific.container = container;

        var div = new Element('div',{
            'style': specific.parent_id == null ? '' : 'padding: 2px 0 2px 10px; color: white; background: #6F8992; border-bottom: 1px solid #D6D6D6 !important'
        });
        if (specific.parent_id != null) {
            div.appendChild(new Element('span',{'style': 'font-weight: bold'}))
                .insert(specific.title);
            self.appendCloneButton(specific,div,container);
            self.appendRemoveButton(specific,div,container);
        }

        self.makePath(specific);
        div.appendChild(new Element('input',{'type': 'hidden','name': 'specifics[' + specific.path + '][mode]','value':'none'}));

        container.appendChild(new Element('tr'))
                 .appendChild(new Element('td',{'colspan': 2})).insert(div);

        self.renderSpecifics(self.getChildSpecifics(specific),self.newContainer(container,{
            'tr': {},
            'table': {
                'style': 'padding: 7px ' + (specific.parent_id == null ? '7px' : '200px') + ' 7px 7px',
                'class': 'form-list',
                'cellspacing': 0,
                'cellpadding': 0
            }
        }));
    },

    getContainerSelectTypeContent: function(specific,container)
    {
        var self = this;
        var childSpecifics = self.getChildSpecifics(specific);

        var div = new Element('div');
        var isRequired = (specific.min_occurs > 0 || specific.title == 'Product Type');

        var select = div.appendChild(new Element('select',{
            'id': specific.id,
            'class': (isRequired ? 'M2ePro-required-when-visible' : '') + ' M2ePro-container-select-choose'
        }));

        select.appendChild(new Element('option',{'style': 'display: none; '}));

        childSpecifics.each(function(childSpecific) {
            select.appendChild(new Element('option',{'value': childSpecific.id}))
                  .insert(childSpecific.title);
        });

        select.observe('change',function() {
            select.next('button').show();
        });

        var title   = specific.title + ': ' + (isRequired ? '<span class="required">*</span>' : '');
        var label   = new Element('label').insert(title);

        container = self.newContainer(container,{
            'tr': {
                'id': specific.id,
                'index_number': 1
            },
            'table': {
                'class': 'form-list',
                'cellspacing': 0,
                'cellpadding': 0
            }
        });

        specific.container = container;

        self.makePath(specific);
        div.appendChild(new Element('input',{'type': 'hidden','name': 'specifics[' + specific.path + '][mode]','value':'none'}));

        var tr = container.appendChild(new Element('tr'));

        tr.appendChild(new Element('td',{'class': 'label'})).insert(label);
        tr.appendChild(new Element('td',{'class': 'value'})).insert(div);

        self.appendChooseButton(specific,div,container);
    },

    getVirtualContainerSelectTypeContent: function(specific,container)
    {
        var self = this;
        var childSpecifics = self.getChildSpecifics(specific);

        var div = new Element('div');

        var select = div.appendChild(new Element('select',{
            'id': specific.id,
            'class': (specific.min_occurs > 0 ? 'M2ePro-required-when-visible' : '') + ' M2ePro-container-select-choose'
        }));

        select.appendChild(new Element('option',{'style': 'display: none; '}));

        childSpecifics.each(function(childSpecific) {
            select.appendChild(new Element('option',{'value': childSpecific.id}))
                  .insert(childSpecific.title);
        });

        select.observe('change',function() {
            select.next('button').show();
        });

        var title   = new Element('span').insert('Choose an option: ' + (specific.min_occurs > 0 ? '<span class="required">*</span>' : ''));
        var label   = new Element('label').insert(title);

        container = self.newContainer(container,{
            'tr': {
                'id': specific.id,
                'index_number': 1
            },
            'table': {
                'class': 'form-list',
                'cellspacing': 0,
                'cellpadding': 0
            }
        });

        self.renderHr(container);

        specific.container = container;

        self.makePath(specific);
        div.appendChild(new Element('input',{'type': 'hidden','name': 'specifics[' + specific.path + '][mode]','value':'none'}));

        var tr = container.appendChild(new Element('tr'));

        tr.appendChild(new Element('td',{'class': 'label'})).insert(label);
        tr.appendChild(new Element('td',{'class': 'value'})).insert(div);

        self.appendChooseButton(specific,div,container);
    },

    //----------------------------------

    getChildSpecifics: function(parent)
    {
        var specifics = [];
        $H(this.specifics).each(function(data) {
            data[1].parent_id == parent.id && specifics.push(data[1]);
        });
        return specifics;
    },

    //----------------------------------

    newContainer: function(oldContainer,attributes)
    {
        return oldContainer
            .appendChild(new Element('tr',attributes.tr))
            .appendChild(new Element('td',{'colspan': 2}))
            .appendChild(new Element('table',attributes.table));
    },

    //----------------------------------

    appendChooseButton: function(specific,div,container)
    {
        var self = this;

        var chooseButton = div.appendChild(new Element('button',{
            'type': 'button',
            'add_button_id': specific.id,
            'class': 'scalable add',
            'style': 'margin-left: 5px; display: none'
        }));
        chooseButton.appendChild(new Element('span')).insert('Choose');

        chooseButton.observe('click',(function() {

            var newContainer = new Element('table',{'id':specific.id,'class': 'form-list','style': 'margin: 0 0 0 50px','cellspacing': 0,'cellpadding': 0});
            var refTr = div.up('tr');

            refTr.insert({'after': (function() {
                var newTr = new Element('tr');
                newTr.appendChild(new Element('td',{'colspan': 2}))
                     .appendChild(newContainer);
                return newTr;
            })()});

            return function() {
                var select = refTr.down('select[id=' + this.getAttribute('add_button_id') + ']');
                self.chooseButtonClick(select,newContainer,this);
            }
        })());
    },

    appendAddButton: function(specific,div,container)
    {
        var self = this;

        var addButton = div.appendChild(new Element('button',{
            'type': 'button',
            'add_button_id': specific.id,
            'class': 'scalable add',
            'style': 'margin-left: 5px; display: none'
        }));
        addButton.appendChild(new Element('span')).insert('Add');

        addButton.observe('click',(function() {

            var newContainer = self.newContainer(container,{
                'tr': {},
                'table': {
                    'id': specific.id,
                    'class': 'form-list',
                    'style': 'margin: 0 0 0 50px',
                    'cellspacing': 0,
                    'cellpadding': 0
                }
            });

            return function() {
                var select = container.down('select[id=' + this.getAttribute('add_button_id') + ']');
                self.addButtonClick(select,newContainer,this);
            }
        })());
    },

    appendCloneButton: function(specific,div,container)
    {
        var self = this;
        var cloneButton = div.appendChild(new Element('button',{
            'type': 'button',
            'add_button_id': specific.id,
            'class': 'scalable add',
            'style': 'margin-left: 5px'
        }));
        cloneButton.appendChild(new Element('span')).insert('Clone');

        cloneButton.observe('click',function() {
            specific.type != self.TYPE_CONTAINER
                ? self.cloneButtonClick(specific,div,container)
                : self.containerCloneButtonClick(specific,div,container);
        });

        var occurs = container.up('table').select('tr[id=' + specific.id + ']').length;

        occurs >= parseInt(specific.max_occurs) && cloneButton.hide();
    },

    appendRemoveButton: function(specific,div,container)
    {
        var self = this;
        var removeButton = div.appendChild(new Element('button',{
            'type': 'button',
            'remove_button_id': specific.id,
            'class': 'scalable delete',
            'style': 'margin-left: 5px'
        }));
        removeButton.appendChild(new Element('span')).insert('Delete');

        removeButton.observe('click',function() {
            self.removeButtonClick(specific,div,container);
        });

        var occurs = container.up('table').select('tr[id=' + specific.id + ']').length;

        specific.min_occurs == 1 && occurs == 1 && removeButton.hide();

        specific.title == 'Product Type' && removeButton.hide();

        self.specifics[specific.parent_id].type == self.TYPE_VIRTUAL_CONTAINER_SELECT && removeButton.show();
    },

    //----------------------------------

    chooseButtonClick: function(select,container,button)
    {
        button.hide();
        var specific = this.specifics[select.value];
        select.disabled = true;
        this.renderSpecifics([specific],container);
        container.down('button[remove_button_id=' + specific.id + ']').show();
    },

    addButtonClick: function(select,container,button)
    {
        button.hide();
        var specific = this.specifics[select.value];
        var option = select.down('option[value=' + select.value + ']');

        option.hide();

        option.getAttribute('parent_id') && select.select('option[parent_id=' + option.getAttribute('parent_id') + ']').each(function(choiceOption) {
            choiceOption.hide();
        });

        select.firstChild.selected = true;
        this.isAllOptionsHidden(select) && select.writeAttribute('disabled');
        this.renderSpecifics([specific],container,null,true);
    },

    cloneButtonClick: function(specific,div,container)
    {
        var self = this;
        var style = container.getAttribute('style');

        container = container.up('table');
        div.down('button').hide();

        container.select('button[remove_button_id=' + specific.id + ']').each(function(button) {
            button.show();
        });

        this.renderSpecifics([specific],container,function(contentCallback,specific,container) {

            var refTr = div.up('tr[id=' + specific.id + ']');
            var newContainer = new Element('table',{'class': 'form-list','style': style,'cellspacing': 0,'cellpadding': 0});
            var newTr = new Element('tr',{
                'id': specific.id,
                'index_number': parseInt(refTr.getAttribute('index_number')) + 1
            });

            newTr.appendChild(new Element('td',{'colspan': 2}))
                 .appendChild(newContainer);

            refTr.insert({'after': newTr});

            container = newContainer;

            specific.container = container;

            self.makePath(specific);
            self.renderHr(container);
            self.renderChooseMode(specific,container);
            self.renderModeCustomValue(specific,container,contentCallback);
            self.renderModeCustomAttribute(specific,container);
            self.renderAttributes(specific,container);
        },true);
    },

    containerCloneButtonClick: function(specific,div,container)
    {
        var self  = this;
        var style = container.getAttribute('style');

        container = container.up('table');
        div.down('button').hide();

        container.select('button[remove_button_id=' + specific.id + ']').each(function(button) {
            button.show();
        });

        var refTr = div.up('tr[id=' + specific.id + ']');

        var newContainer = new Element('table',{'class': 'form-list','style': style,'cellspacing': 0,'cellpadding': 0});
        var newTr = new Element('tr',{'id': specific.id,'index_number': parseInt(refTr.getAttribute('index_number')) + 1});

        newTr.appendChild(new Element('td',{'colspan': 2}))
             .appendChild(newContainer);

        refTr.insert({'after': newTr});
        container = newContainer;

        specific.container = container;
        self.makePath(specific);

        div = new Element('div',{'style': 'padding: 2px 0 2px 10px; color: white; background: #6F8992; border-bottom: 1px solid #D6D6D6 !important'});
        div.appendChild(new Element('span',{'style': 'font-weight: bold'})).insert(specific.title);
        div.appendChild(new Element('input',{'type': 'hidden','name': 'specifics[' + specific.path + '][mode]','value':'none'}));

        self.appendCloneButton(specific,div,container);
        self.appendRemoveButton(specific,div,container);

        container.appendChild(new Element('tr')).appendChild(new Element('td',{'colspan': 2})).insert(div);

        self.renderSpecifics(self.getChildSpecifics(specific),self.newContainer(container,{
            'tr': {},
            'table': {'style': 'padding: 7px 200px 7px 7px','class': 'form-list','cellspacing': 0,'cellpadding': 0}
        }),null,true);
    },

    removeButtonClick: function(specific,div,container)
    {
        container = container.up('table');

        var parentTr = div.up('tr[id=' + specific.id +']');

        var select = container
            .up('table')
            .down('select[id=' + specific.parent_id + ']');

        select = select || container.up('table').down('select[id=' + this.specifics[specific.parent_id].parent_id + ']');

        select.removeAttribute('disabled');
        select.firstChild.selected = true;

        parentTr.parentNode.removeChild(parentTr);

        var option = select.down('option[value=' + specific.id + ']');
        var founds = container.select('tr[id=' + specific.id + ']');

        option && option.getAttribute('parent_id') && select.select('option[parent_id=' + option.getAttribute('parent_id') + ']').each(function(choiceOption) {
            choiceOption.show();
        });

        if (founds.length == 0) {
            option.show();
        } else if (founds.length == 1 && specific.min_occurs == 1) {
            var found = founds.shift();
            found.down('button[remove_button_id=' + specific.id + ']').hide();
            found.down('button[add_button_id=' + specific.id + ']').show();
        } else {
            founds.pop().down('button[add_button_id=' + specific.id + ']').show();
        }

        this.isAllOptionsHidden(select) || select.removeAttribute('disabled');
    },

    //----------------------------------

    isAllOptionsHidden: function(select)
    {
        var isAllOptionsHidden = true;
        for (var i = 0; i < select.options.length; i++) {
            if (select.options.item(i).style.display == '') {
                isAllOptionsHidden = false;
                break;
            }
        }
        return isAllOptionsHidden;
    },

    //----------------------------------

    makePath: function(specific)
    {
        function makePath(parent,specific) {
            if (!parent) {
                return;
            }

            var parentContainer = parent.container && parent.container.up('tr[id=' + parent.id + ']');
            var indexNumber = parentContainer
                ? parseInt(parentContainer.getAttribute('index_number'))
                : 1;

            specific.path = '/' + parent.xml_tag  + '-' + indexNumber + specific.path;

            parent && makePath.call(this,parent.parent_id && this.specifics[parent.parent_id],specific);
        }

        specific.path = '';
        makePath.call(this,specific,specific);
    },

    //----------------------------------

    renderChooseMode: function(specific,container)
    {
        var div = new Element('div');
        var select = div.appendChild(new Element('select',{
            'name': 'specifics[' + specific.path + "][mode]",
            'class': 'M2ePro-required-when-visible'
        }));

        select.appendChild(new Element('option',{'style': 'display: none'}));
        select.appendChild(new Element('option',{'value': 'custom_value'})).insert('Custom Value');
        select.appendChild(new Element('option',{'value': 'custom_attribute'})).insert('Custom Attribute');

        var tr = container.appendChild(new Element('tr'));

        tr.appendChild(new Element('td',{'class': 'label'})).insert(this.getLabel(specific));
        tr.appendChild(new Element('td',{'class': 'value'})).insert(div);

        select.observe('change',function() {
            this.value == 'custom_value' && container.down('tr[mode="custom_value"]').show() && container.down('tr[mode="custom_attribute"]').hide();
            this.value == 'custom_attribute' && container.down('tr[mode="custom_attribute"]').show() && container.down('tr[mode="custom_value"]').hide();
        });

        this.appendCloneButton(specific,div,container);
        this.appendRemoveButton(specific,div,container);
    },

    renderModeCustomValue: function(specific,container,contentCallback)
    {
        var tr = container.appendChild(new Element('tr',{'mode':'custom_value','style': 'display: none'}));
        var div = new Element('div');

        div.appendChild(new Element('label')).insert('Custom Value: <span class="required">*</span>');

        tr.appendChild(new Element('td',{'class': 'label'})).insert(div);
        tr.appendChild(new Element('td',{'class': 'value'})).insert(contentCallback.call(this,specific));
    },

    renderModeCustomAttribute: function(specific,container)
    {
        var tr = container.appendChild(new Element('tr',{'mode':'custom_attribute','style': 'display: none'}));
        var div = new Element('div');

        div.appendChild(new Element('label')).insert('Custom Attribute: <span class="required">*</span>');

        tr.appendChild(new Element('td',{'class': 'label'})).insert(div);

        tr.appendChild(new Element('td',{'class': 'value'})).insert(this.getMagentoAttributes(
            'specifics[' + specific.path + "][custom_attribute]",
            true,
            specific.params.evalJSON(),
            specific.values.evalJSON()
        ));
    },

    renderAttributes: function(specific,container)
    {
        var self = this;
        var attributes = specific.params.evalJSON().attributes;
        if (!attributes) {
            return '';
        }

        attributes.each(function(attribute,index) {
            self.renderAttributeChooseMode(attribute,index,specific,container);
            self.renderAttributesModeCustomValue(attribute,index,specific,container);
            self.renderAttributesModeCustomAttribute(attribute,index,specific,container);
        });
    },

    renderAttributeChooseMode: function(attribute,index,specific,container)
    {
        var div = new Element('div');
        var select = div.appendChild(new Element('select',{
            'class': attribute.required ? 'M2ePro-required-when-visible' : '',
            'name': 'specifics[' + specific.path + "][attributes][" + index + "][" + attribute.title + "][mode]"
        }));

        select.appendChild(new Element('option',{'style': 'display: none'}));
        select.appendChild(new Element('option',{'value': 'custom_value'})).insert('Custom Value');
        select.appendChild(new Element('option',{'value': 'custom_attribute'})).insert('Custom Attribute');

        var tr = container.appendChild(new Element('tr'));
        var label = new Element('label').insert(specific.title + ' (' + attribute.title + '): ' + (attribute.required ? '<span class="required">*</span>' : ''));

        tr.appendChild(new Element('td',{'class': 'label'})).insert(label);
        tr.appendChild(new Element('td',{'class': 'value'})).insert(div);

        select.observe('change',function() {
            this.value == 'custom_value' && container.down('tr[mode="attribute_custom_value"]').show() && container.down('tr[mode="attribute_custom_attribute"]').hide();
            this.value == 'custom_attribute' && container.down('tr[mode="attribute_custom_attribute"]').show() && container.down('tr[mode="attribute_custom_value"]').hide();
        });
    },

    renderAttributesModeCustomValue: function(attribute,index,specific,container)
    {
        var tr = container.appendChild(new Element('tr',{'mode':'attribute_custom_value','style': 'display: none'}));
        var div = new Element('div');

        div.appendChild(new Element('label')).insert('Custom Value: ' + (attribute.required ? '<span class="required">*</span>' : ''));

        tr.appendChild(new Element('td',{'class': 'label'})).insert(div);
        tr.appendChild(new Element('td',{'class': 'value'})).insert(this.getAttributeContent(attribute,index,specific));
    },

    renderAttributesModeCustomAttribute: function(attribute,index,specific,container)
    {
        var tr = container.appendChild(new Element('tr',{'mode':'attribute_custom_attribute','style': 'display: none'}));
        var div = new Element('div');

        div.appendChild(new Element('label')).insert('Custom Attribute: ' + (attribute.required ? '<span class="required">*</span>' : ''));

        tr.appendChild(new Element('td',{'class': 'label'})).insert(div);

        tr.appendChild(new Element('td',{'class': 'value'})).insert(this.getMagentoAttributes(
            'specifics[' + specific.path + "][attributes][" + index + "][" + attribute.title + "][custom_attribute]",
            attribute.required,
            attribute,
            attribute.values.evalJSON()
        ));
    },

    getAttributeContent: function(attribute,index,specific)
    {
        var div = new Element('div');

        if (attribute.values) {
            var select = div.appendChild(new Element('select',{
                'name': 'specifics[' + specific.path + "][attributes][" + index + "][" + attribute.title + "][custom_value]",
                'class': attribute.required ? 'M2ePro-required-when-visible' : ''
            }));
            select.appendChild(new Element('option',{'style': 'display: none'}));
            attribute.values.evalJSON().each(function(value) {
                select.appendChild(new Element('option',{'value': value}).insert(value));
            });
        } else {
            div.appendChild(new Element('input',{
                'id': specific.id,
                'index': index,
                'type': 'text',
                'class': 'input-text M2ePro-specificAttributes-validation' + (attribute.required ? ' M2ePro-required-when-visible' : ''),
                'name': 'specifics[' + specific.path + "][attributes][" + index + "][" + attribute.title + "][custom_value]"
            }));

            var note = this['get' + ucwords(attribute.type) + 'TypeNote'](attribute);

            div.appendChild(new Element('p',{'class': 'note'}))
               .appendChild(new Element('span'))
               .insert(note);
        }

        return div;
    },

    //----------------------------------

    renderHr: function(container)
    {
        var prev = container.up('tr').previous();

        if (prev && prev.getAttribute('id') && this.specifics[prev.getAttribute('id')].type != this.TYPE_CONTAINER) {
            container.appendChild(new Element('tr'))
                     .appendChild(new Element('td',{'colspan': 2,'style': 'padding: 5px !important'}))
                     .appendChild(new Element('hr',{'style': 'border: 1px solid silver; border-bottom: none;'}));
        }
    },

    //----------------------------------

    getMagentoAttributes: function(name,isRequired,params,values)
    {
        var container = new Element('div');

        var div    = container.appendChild(new Element('div',{
            'style': 'float: left'
        }));

        var select = div.appendChild(new Element('select',{
            'name': name,
            'class': isRequired ? 'M2ePro-required-when-visible' : ''
        }));

        select.appendChild(new Element('option',{'style': 'display: none'}));

        this.M2ePro.formData.attributes.each(function(attribute) {
            select.appendChild(new Element('option',{'value': attribute.code})).insert(attribute.label);
        });

        params.type && div.appendChild(new Element('p',{'class': 'note'}))
                          .appendChild(new Element('span'))
                          .insert(this['get' + ucwords(params.type) + 'TypeNote'](params));

        values.length > 0 && this.renderHelpIcon(values,container);

        return container;
    },

    //----------------------------------

    renderHelpIcon: function(values,container)
    {
        var helpIcon = container.appendChild(new Element('img',{
            'src': this.M2ePro.url.helpIcon,
            'title': 'Allowed Values',
            'style': 'margin-left: 10px'
        }));

        var win;
        var self = this;

        helpIcon.observe('click',function() {
            var position = helpIcon.positionedOffset();

            win = win || new Window({
                className: "magento",
                zIndex: 100,
                title: self.M2ePro.text.allower_values,
                width: 210,
                height: 260,
                top: position.top - 30,
                left: position.left + 30
            });

//            var titleEl = $(win.getId() + '_row1');
//                titleEl && titleEl.remove();

//            win = Dialog.info('', {
//                draggable: true,
//                resizable: true,
//                closable: true,
//                className: "magento",
//                windowClassName: "popup-window",
////                title: self.M2ePro.text.mapping_product_title + ' "' + productTitle + '"',
////                top: 100,
//                width: 750,
//                height: 500,
//                zIndex: 100,
//                recenterAuto: true,
//                hideEffect: Element.hide,
//                showEffect: Element.show
//            });

//            $(win.getId() + '_content').setStyle({
//                'backgroundColor': 'white',
//                'color': 'black'
//            });

            var winContent = '<div style="margin: 10px 0 0 0;"></div>' +
                             '<ul style="margin: 10px 0 5px 10px">%content%</ul></div>';

            var content = '';
            values.each(function(value) {
                content += '<li><h4>' + value + '</h4></li>';
            });

            win.setHTMLContent(winContent.replace('%content%',content));

            win.visible ? win.hide() : win.show();
        });
    },

    //----------------------------------

    getUndefinedTypeNote: function(params)
    {
        return 'Can take any value.';
    },

    getIntTypeNote: function(params)
    {
        var notes = [];

        var handler = {
            'type': function()
            {
                notes[0] = 'Type: Numeric. ';
            },
            'min_value': function(restriction)
            {
                notes[1] = 'Min: ' + restriction + '. ';
            },
            'max_value': function(restriction)
            {
                notes[2] = 'Max: ' + restriction + '. ';
            },
            'total_digits': function(restriction)
            {
                notes[3] = 'Total digits (not more): ' + restriction + '. ';
            }
        };

        for (var paramName in params) {
            params.hasOwnProperty(paramName) && handler[paramName] && handler[paramName](params[paramName]);
        }

        return notes.join('');
    },

    getStringTypeNote: function(params)
    {
        var notes = [];

        var handler = {
            'type': function()
            {
                notes[0] = 'Type: String. ';
            },
            'min_length': function(restriction)
            {
                notes[1] = restriction != 1 ? 'Min length: ' + restriction : '';
            },
            'max_length': function(restriction)
            {
                notes[2] = 'Max length: ' + restriction;
            }
        };

        for (var paramName in params) {
            params.hasOwnProperty(paramName) && handler[paramName] && handler[paramName](params[paramName]);
        }

        return notes.join('');
    },

    getDate_timeTypeNote: function(params)
    {
        var notes = [];

        var handler = {
            'type': function(restriction)
            {
                notes.push('Type: Date time. Format: YYYY-MM-DD hh:mm:ss');
            }
        };

        for (var paramName in params) {
            params.hasOwnProperty(paramName) && handler[paramName] && handler[paramName](params[paramName]);
        }

        return notes.join('');
    },

    getFloatTypeNote: function(params)
    {
        var notes = [];

        var handler = {
            'type': function()
            {
                notes[0] = 'Type: Numeric floating point. ';
            },
            'min_value': function(restriction)
            {
                notes[1] = 'Min: ' + restriction + '. ';
            },
            'max_value': function(restriction)
            {
                notes[2] = 'Max: ' + restriction + '. ';
            },
            'decimal_places': function(restriction)
            {
                notes[3] = 'Decimal places (not more): ' + restriction.value + '. ';
            },
            'total_digits': function(restriction)
            {
                notes[4] = 'Total digits (not more): ' + restriction + '. ';
            }
        };

        for (var paramName in params) {
            params.hasOwnProperty(paramName) && handler[paramName] && handler[paramName](params[paramName]);
        }

        return notes.join('');
    }

    //----------------------------------

});
