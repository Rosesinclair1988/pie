MenuPlus = Class.create();

MenuPlus.prototype = {
    initialize: function() {
        this.mddm = $('mddm');
        this.level1 = this.mddm.select('.level-1');
        this.mddmdropdown = this.mddm.select('.mddm-dropdown');
        this.running = false;
        this.params = {};

        this.level1.each(function(elmt){
            elmt.observe('mouseover',function(){
                elmt.addClassName('hover');
            });
            elmt.observe('mouseout',function(){
                elmt.removeClassName('hover');
            });
        });

    },
    
    setEffect: function(effect) {
        this.params.effect = effect;
    },
    
    setDuration: function(duration) {
        this.params.duration = duration;
    },
    
    display: function(action, element) {
        if(action == 'show') {
            var opacityFrom = 0.0;
            var opacityTo = 1.0;
        } else {
            var opacityFrom = 1.0;
            var opacityTo = 0.0;
        }

        if(this.params.effect == 'fade') {
            new Effect.Appear(
                element, { 
                    from: opacityFrom, 
                    to: opacityTo,
                    duration: 0.5
                }
                );
        } else {
            element.setOpacity(opacityTo);
        }
    },
    
    attachHooks: function() {
        var $this = this;
        $this.level1.each(function(element){
            element.observe('mouseover',function(){
                $this.activateMenuWithDelay();
            }).observe('mouseout',function(){
                $this.deactivateMenuWithDelay();
            })
        });
        
        $this.mddmdropdown.each(function(element){
            element.observe('mouseover',function(){
                $this.activateMenuWithDelay();
            }).observe('mouseout',function(){
                $this.deactivateMenuWithDelay();
            })
        });
    },

    activateMenuWithDelay: function() {
        var mddmObj = this;
        
        if (mddmObj.mddm.timer) {
            window.clearTimeout(mddmObj.mddm.timer);
        }
        mddmObj.mddm.timer = window.setTimeout(function(){
            if(!mddmObj.mddm.hasClassName('active')){
                mddmObj.activateMenu();
            }
        }, mddmObj.params.duration);
    },

    deactivateMenuWithDelay: function() {
        var mddmObj = this;
        
        if (mddmObj.mddm.timer) {
            window.clearTimeout(mddmObj.mddm.timer);
        }
        mddmObj.mddm.timer = window.setTimeout(function(){
            mddmObj.deactivateMenu();
        }, 0);
    },
    
    activateMenu: function() {
        this.mddm.addClassName('active');
        this.display('show',this.mddm.select('.level-1.hover .mddm-dropdown')[0]);
    },

    deactivateMenu: function() {
        this.mddm.removeClassName('active');
    }
};
