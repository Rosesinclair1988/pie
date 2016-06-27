AmazonTemplateDescriptionHandler = Class.create();
AmazonTemplateDescriptionHandler.prototype = Object.extend(new CommonHandler(), {

    //----------------------------------

    initialize: function()
    {
        // todo duplicate

        this.setValidationCheckRepetitionValue('M2ePro-description-tpl-title',
                                                M2ePro.text.title_not_unique_error,
                                                'Template_Description', 'title', 'id',
                                                M2ePro.formData.id);
    },

    //----------------------------------

    duplicate_click: function($headId)
    {
        var attrSetEl = $('attribute_sets_fake');

        if (attrSetEl) {
            $('attribute_sets').remove();
            attrSetEl.observe('change', AttributeSetHandlerObj.changeAttributeSets);
            attrSetEl.id = 'attribute_sets';
            attrSetEl.name = 'attribute_sets[]';
            attrSetEl.addClassName('M2ePro-validate-attribute-sets');

            AttributeSetHandlerObj.confirmAttributeSets();
        }

        if ($('attribute_sets_breadcrumb')) {
            $('attribute_sets_breadcrumb').remove();
        }
        $('attribute_sets_container').show();
        $('attribute_sets_buttons_container').show();

        CommonHandlerObj.duplicate_click($headId);
    },

    preview_click: function()
    {
        this.submitForm(M2ePro.url.preview, true);
    },

    //----------------------------------

    attribute_sets_confirm: function()
    {
        var self = AmazonTemplateDescriptionHandlerObj;

        AttributeSetHandlerObj.confirmAttributeSets();

        AttributeSetHandlerObj.renderAttributesWithEmptyOption('image_main_attribute', 'image_main_attribute_td');
        AttributeSetHandlerObj.renderAttributes('select_attributes_for_title', 'select_attributes_for_title_span', 0, '150');
        AttributeSetHandlerObj.renderAttributes('select_attributes_for_brand', 'select_attributes_for_brand_span', 0, '150');
        AttributeSetHandlerObj.renderAttributes('select_attributes_for_manufacturer', 'select_attributes_for_manufacturer_span', 0, '150');

        for (var i = 0; i < 5; i++) {
            AttributeSetHandlerObj.renderAttributes('select_attributes_for_bullet_points_' + i, 'select_attributes_for_bullet_points_' + i + '_span', 0, '150');
        }

        AttributeSetHandlerObj.renderAttributes('select_attributes', 'select_attributes_span');
    },

    //----------------------------------

    title_mode_change: function()
    {
        var self = AmazonTemplateDescriptionHandlerObj;
        self.setTextVisibilityMode(this, 'custom_title_tr');
    },

    brand_mode_change: function()
    {
        var self = AmazonTemplateDescriptionHandlerObj;
        self.setTextVisibilityMode(this, 'custom_brand_tr');
    },

    manufacturer_mode_change: function()
    {
        var self = AmazonTemplateDescriptionHandlerObj;
        self.setTextVisibilityMode(this, 'custom_manufacturer_tr');
    },

    bullet_points_mode_change: function()
    {
        var self = AmazonTemplateDescriptionHandlerObj;

        if (this.value == self.BULLET_POINTS_MODE_NONE) {
            $$('.bullet-points-tr').invoke('hide');
            $$('input[name="bullet_points[]"]').each(function(obj) {
                obj.value = '';
            });
            $('bullet_point_actions_tr').hide();
        } else {

            if (AttributeSetHandlerObj.checkAttributeSetSelection()) {
                var visibleBulletPointsCounter = 0;

                $$('.bullet-points-tr').each(function(obj) {
                    if (visibleBulletPointsCounter == 0 || $(obj).select('input[name="bullet_points[]"]')[0].value != '') {
                        $(obj).show();
                        visibleBulletPointsCounter++;
                    }
                });

                $('bullet_point_actions_tr').show();

                if (visibleBulletPointsCounter > 1) {
                    $('hide_bullet_point_action').removeClassName('action-disabled');
                } else {
                    $('hide_bullet_point_action').addClassName('action-disabled');
                }

                if (visibleBulletPointsCounter < 5) {
                    $('show_bullet_point_action').removeClassName('action-disabled');
                } else {
                    $('show_bullet_point_action').addClassName('action-disabled');
                }
            } else {
                this.value = self.BULLET_POINTS_MODE_NONE;
            }
        }
    },

    description_mode_change: function()
    {
        var self = AmazonTemplateDescriptionHandlerObj;

        $$('.c-custom_description_tr').invoke('hide');

        if (this.value == self.DESCRIPTION_MODE_CUSTOM) {
            if (AttributeSetHandlerObj.checkAttributeSetSelection()) {
                $$('.c-custom_description_tr').invoke('show');
            } else {
                this.value = 0;
            }
        }
    },

    image_main_mode_change: function()
    {
        var self = AmazonTemplateDescriptionHandlerObj;

        if (this.value == self.IMAGE_MAIN_MODE_NONE) {
            $('gallery_images_mode_tr').hide();
            $('gallery_images_mode').value = 0;
        } else {
            $('gallery_images_mode_tr').show();
        }

        if (this.value == self.IMAGE_MAIN_MODE_ATTRIBUTE && !AttributeSetHandlerObj.checkAttributeSetSelection()) {
            this.value = M2ePro.formData.image_main_mode;
            return;
        }

        $('image_main_attribute_tr')[this.value == self.IMAGE_MAIN_MODE_ATTRIBUTE ? 'show' : 'hide']();
    },

    //----------------------------------

    setTextVisibilityMode: function(obj, elementName)
    {
        var self = AmazonTemplateDescriptionHandlerObj;

        if (obj.value == 1) {

            if (!AttributeSetHandlerObj.checkAttributeSetSelection()) {
                this.value = 0;
                obj.value = 0;
                return;
            }
            $(elementName).show();

        } else {
            $(elementName).hide();
        }
    },

    //----------------------------------

    showBulletPoint: function()
    {
        var emptyVisibleBulletPointExist = $$('.bullet-points-tr').any(function(obj) {
            return $(obj).visible() && $(obj).select('input[name="bullet_points[]"]')[0].value == '';
        });

        if (emptyVisibleBulletPointExist) {
            return;
        }

        var hiddenBulletPoints = $$('.bullet-points-tr').findAll(function(obj) {
            return !$(obj).visible();
        });

        if (hiddenBulletPoints.size() == 0) {
            return;
        }

        hiddenBulletPoints.shift().show();
        $('hide_bullet_point_action').removeClassName('action-disabled');

        if (hiddenBulletPoints.size() == 0) {
            $('show_bullet_point_action').addClassName('action-disabled');
        }
    },

    hideBulletPoint: function()
    {
        var visibleBulletPoints = $$('.bullet-points-tr').findAll(Element.visible);

        if (visibleBulletPoints.size() > 1) {
            var lastVisibleBulletPoint = visibleBulletPoints.pop();
            lastVisibleBulletPoint.select('input[name="bullet_points[]"]')[0].value = '';
            lastVisibleBulletPoint.hide();

            if (visibleBulletPoints.size() == 1) {
                $('hide_bullet_point_action').addClassName('action-disabled');
            }
        }

        $('show_bullet_point_action').removeClassName('action-disabled');
    }

    //----------------------------------
});
