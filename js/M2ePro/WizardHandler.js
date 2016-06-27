WizardHandler = Class.create();
WizardHandler.prototype = Object.extend(new CommonHandler(), {

    //----------------------------------

    initialize : function(currentStepId, hiddenIds)
    {
        this.steps = {};
        this.steps.currentId = currentStepId;
        this.steps.hiddenIds = hiddenIds || [];
        this.steps.ids = [];
    },

    //----------------------------------

    skip : function(url)
    {
        if (!confirm(M2ePro.text.skip_confirm)) {
            return;
        }

        setLocation(url);
    },

    complete : function(url)
    {
        setLocation(url);
    },

    //----------------------------------

    setStatus : function(status, callback)
    {
        var self = WizardHandlerObj;

        new Ajax.Request( M2ePro.url.setStatus + 'status/' + status,
        {
            method: 'get',
            asynchronous: true,
            onSuccess: function(transport) {
                self.steps.currentId = status;

                if (typeof callback == 'function') {
                    callback();
                }

                self.renderStep(status);
            }
        });
    },

    //----------------------------------

    addStep : function(stepId, stepContainerId)
    {
        var self = WizardHandlerObj;
        stepId = parseInt(stepId);

        if (self.steps.hiddenIds.indexOf(stepId) != -1) {
            return;
        }

        self.steps[stepId] = stepContainerId;
        self.steps.ids.push(stepId);
        self.renderStep(stepId);
    },

    removeStep : function(stepId)
    {
        stepId = parseInt(stepId);

        delete this.steps[stepId];
        if (this.steps.ids.indexOf(stepId) != -1) {
            this.steps.ids.splice(this.steps.ids.indexOf(stepId),1);
        }
    },

    getNextStepById : function(stepId)
    {
        var self = WizardHandlerObj;
        var stepIndex = self.steps.ids.indexOf(parseInt(stepId));

        if (stepIndex == -1) {
            return null;
        }

        var nextStepId = self.steps.ids[stepIndex + 1];

        if (typeof nextStepId == 'undefined') {
            return null;
        }

        return nextStepId;
    },

    //----------------------------------

    renderStep : function(stepId)
    {
        stepId = parseInt(stepId);

        var self = WizardHandlerObj;
        var stepContainerId = self.steps[stepId];

        if (typeof stepContainerId == 'undefined') {
            return;
        }

        // Render step subtitle
        //----------------
        var stepNumber = self.steps.ids.indexOf(stepId) + 1;
        var subtitle = '[' + M2ePro.text.step_word + ' ' + stepNumber + ']';

        $(stepContainerId).writeAttribute('subtitle', subtitle);

        if (typeof $$('#' + stepContainerId + ' span.subtitle')[0] != 'undefined') {
            $$('#' + stepContainerId + ' span.subtitle')[0].innerHTML = subtitle;
        }
        //----------------

        $$('#'+stepContainerId+' .step_completed').each(function(obj) {
            obj.hide();
        });
        $$('#'+stepContainerId+' .step_skip').each(function(obj) {
            obj.hide();
        });
        $$('#'+stepContainerId+' .step_process').each(function(obj) {
            obj.hide();
        });
        $$('#'+stepContainerId+' .step_incomplete').each(function(obj) {
            obj.hide();
        });

        if (self.steps.currentId >= stepId) {
            $(stepContainerId).show();
        } else {
            $(stepContainerId).hide();
        }

        if (self.steps.currentId > stepId) {
            $$('#'+stepContainerId+' .step_completed').each(function(obj) {
                obj.show();
            });
            $$('#'+stepContainerId+' .step_container_buttons').each(function(obj) {
                obj.remove();
            });
            $(stepContainerId).writeAttribute('style','background-color: #F2EFEF !important; border-color: #008035 !important;');
        } else {
            $$('#'+stepContainerId+' .step_skip').each(function(obj) {
                obj.show();
            });
            $$('#'+stepContainerId+' .step_process').each(function(obj) {
                obj.show();
            });
            if (window.completeStep == 0) {
                $$('#'+stepContainerId+' .step_incomplete').each(function(obj) {
                    obj.show();
                });
            }
        }
    },

    //----------------------------------

    processStep : function(stepWindowUrl, stepId, callback)
    {
        stepId = parseInt(stepId);

        var self = WizardHandlerObj;
        var win = window.open(stepWindowUrl);

        window.completeStep = 0;

        var intervalId = setInterval(function(){
            if (!win.closed) {
                return;
            }

            clearInterval(intervalId);

            if (window.completeStep == 1) {
                var nextStepId = self.getNextStepById(stepId) || self.STATUS_COMPLETE;

                self.setStatus(nextStepId, function() {
                    if (typeof callback == 'function') {
                        callback();
                    }
                    self.renderStep(stepId);
                });

            } else {
                self.renderStep(stepId);
            }
        }, 1000);
    },

    skipStep : function(stepId, callback)
    {
        stepId = parseInt(stepId);

        var self = WizardHandlerObj;
        var nextStepId = self.getNextStepById(stepId) || self.STATUS_SKIP;

        self.setStatus(nextStepId, function() {
            if (typeof callback == 'function') {
                callback();
            }
            self.renderStep(stepId);
        });
    },

    //----------------------------------

    callBackAfterSettings : function()
    {
        var self = WizardHandlerObj;

        new Ajax.Request( M2ePro.url.getHiddenSteps ,
        {
            method: 'get',
            asynchronous: false,
            onSuccess: function(transport)
            {
                self.steps.hiddenIds = transport.responseText.evalJSON(true);

                if (self.steps.hiddenIds.indexOf(parseInt(self.STATUS_ACCOUNTS_EBAY)) != -1) {
                    self.removeStep(self.STATUS_ACCOUNTS_EBAY);
                }
                if (self.steps.hiddenIds.indexOf(parseInt(self.STATUS_ACCOUNTS_AMAZON)) != -1) {
                    self.removeStep(self.STATUS_ACCOUNTS_AMAZON);
                }
            }
        });
    },

    callBackAfterEndConfiguration : function()
    {
        $('wizard_installation_complete').show();
    }

    //----------------------------------
});
