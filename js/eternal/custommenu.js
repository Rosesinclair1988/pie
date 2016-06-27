
var eternalIsMobile = {
    Android: function() {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function() {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function() {
        return navigator.userAgent.match(/iPhone|iPad|iPod/i);
    },
    Opera: function() {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function() {
        return navigator.userAgent.match(/IEMobile/i);
    },
    any: function() {
        return (eternalIsMobile.Android() || eternalIsMobile.BlackBerry() || eternalIsMobile.iOS() || eternalIsMobile.Opera() || eternalIsMobile.Windows());
    }
};

function eternalShowMenuPopup(objMenu, event, popupId)
{
    var $ = jQuery;
    if (typeof eternalCustommenuTimerHide[popupId] != 'undefined') 
        clearTimeout(eternalCustommenuTimerHide[popupId]);
    
    var $popup = $('#' + popupId); 
    if (!$popup.length) 
        return;
    
    if (!!eternalActiveMenu)
        eternalHideMenuPopup(objMenu, event, eternalActiveMenu.popupId, eternalActiveMenu.menuId);
    
    $objMenu = $(objMenu);
    eternalActiveMenu = {menuId: objMenu.id, popupId: popupId};
    if (!$objMenu.hasClass('active')) {
        $objMenu.addClass('active');
        var pos = eternalPopupPos(objMenu);
        $popup.css({
            'top': pos.top + 'px',
            'width': jQuery('.nav-container').parent().width() + 'px'
        });
        eternalSetPopupZIndex($popup);
        
        // --- Static Block width ---
        var $block2 = $popup.find('div.block2');
        if ($block2.length) {
            block2_id = $block2.attr('id');
            var wStart = block2_id.indexOf('_w');
            if (wStart > -1) {
                var w = block2_id.substr(wStart+2);
            } else {
                var w = 0;
                $popup.find('div.block1 div.column').each(function() {
                    w += $(this).width();
                });
            }
            if (w) $block2.css('width', w + 'px');
        }
        // --- change href ---
        var $eternalMenuAnchor = $objMenu.find('a');
        eternalChangeTopMenuHref($eternalMenuAnchor);
        $popup.stop(true, true).show();
    }
}

function eternalHideMenuPopup(element, event, popupId, menuId)
{
    var $ = jQuery;
    if (typeof eternalCustommenuTimerShow[popupId] != 'undefined') 
        clearTimeout(eternalCustommenuTimerShow[popupId]);
    
    var $element = $(element); 
    var $objMenu = $('#' + menuId);
    var $popup = $('#' + popupId); 
    if (!$popup.length) 
        return;
        
    var eternalCurrentMouseTarget = getCurrentMouseTarget(event);
    if (!!eternalCurrentMouseTarget) {
        if (!eternalIsChildOf($element.get(0), eternalCurrentMouseTarget) && $element.get(0) != eternalCurrentMouseTarget) {
            if (!eternalIsChildOf($popup.get(0), eternalCurrentMouseTarget) && $popup.get(0) != eternalCurrentMouseTarget) {
                if ($objMenu.hasClass('active')) {
                    $objMenu.removeClass('active');
                    // --- change href ---
                    var $eternalMenuAnchor = $objMenu.find('a');
                    eternalChangeTopMenuHref($eternalMenuAnchor);
                    $popup.stop(true, true).hide();                
                }
            }
        }
    }
}

function eternalPopupOver(element, event, popupId, menuId)
{
    if (typeof eternalCustommenuTimerHide[popupId] != 'undefined') clearTimeout(eternalCustommenuTimerHide[popupId]);
}

function eternalPopupPos(objMenu, w)
{
    var $ = jQuery;
    $objMenu = $(objMenu);
    var pos = $objMenu.offset();
    var $wraper = $('#custommenu');
    var posWraper = $wraper.offset();
    var xTop = pos.top - posWraper.top;
    if (CUSTOMMENU_POPUP_TOP_OFFSET) {
        xTop += CUSTOMMENU_POPUP_TOP_OFFSET;
    } else {
        xTop += $objMenu.height();
    }
    var res = {'top': xTop};
    var wWraper = $wraper.width();
    var xLeft = pos.left - posWraper.left;
    if ((xLeft + w) > wWraper) 
        xLeft = wWraper - w;
    if (xLeft < 0) 
        xLeft = 0;
    res.left = xLeft;
    return res;
}

function eternalChangeTopMenuHref($eternalMenuAnchor)
{
    if ($eternalMenuAnchor.attr('rel') == '#')
        return;
    var eternalValue = $eternalMenuAnchor.attr('href');
    $eternalMenuAnchor.attr('href', $eternalMenuAnchor.attr('rel'));
    $eternalMenuAnchor.attr('rel', eternalValue);
}

function eternalIsChildOf(parent, child)
{
    if (child != null) {
        while (child.parentNode) {
            if ((child = child.parentNode) == parent) {
                return true;
            }
        }
    }
    return false;
}

function eternalSetPopupZIndex($popup)
{
    var $ = jQuery;
    $('.eternal-custom-menu-popup').each(function(){
       $(this).css('z-index', '9999');
    });
    $popup.css('z-index', '10000');
}

function getCurrentMouseTarget(xEvent)
{
    var eternalCurrentMouseTarget = null;
    if (xEvent.toElement) {
        eternalCurrentMouseTarget = xEvent.toElement;
    } else if (xEvent.relatedTarget) {
        eternalCurrentMouseTarget = xEvent.relatedTarget;
    }
    return eternalCurrentMouseTarget;
}

function getCurrentMouseTargetMobile(xEvent)
{
    if (!xEvent) var xEvent = window.event;
    var eternalCurrentMouseTarget = null;
    if (xEvent.target) eternalCurrentMouseTarget = xEvent.target;
        else if (xEvent.srcElement) eternalCurrentMouseTarget = xEvent.srcElement;
    if (eternalCurrentMouseTarget.nodeType == 3) // defeat Safari bug
        eternalCurrentMouseTarget = eternalCurrentMouseTarget.parentNode;
    return eternalCurrentMouseTarget;
}

/* Mobile */
function eternalMenuButtonToggle()
{
    var $ = jQuery;
    $('#menu-content').slideToggle(400);
}

function eternalGetMobileSubMenuLevel(id)
{
    var $ = jQuery;
    var rel = $('#' + id).attr('rel');
    if (!rel) return 0;
    return parseInt(rel.replace('level', ''));
}

function eternalSubMenuToggle(obj, activeMenuId, activeSubMenuId)
{
    var $ = jQuery;
    var $obj = $(obj);
    var currLevel = eternalGetMobileSubMenuLevel(activeSubMenuId);
    // --- hide submenus ---
    $('.eternal-custom-menu-submenu').each(function() {
        item_id = $(this).attr('id');
        if (item_id == activeSubMenuId) 
            return;
        /*var xLevel = eternalGetMobileSubMenuLevel(item_id);
        if (xLevel >= currLevel) {
            $('#' + item_id).stop(true, true).slideUp(400);
        }*/
    });
    // --- reset button state ---
    setTimeout(function() {
        $('#custommenu-mobile').find('span.button').each(function() {
            var subMenuId = $(this).attr('rel');
            if (!subMenuId) subMenuId = 'submenu-mobile-custom';
            if ($('#' + subMenuId).css('display') == 'none') {
                $(this).removeClass('open');
            }
        }); 
    }, 700)
    // ---
    $activeSubMenu = $('#' + activeSubMenuId);
    if ($activeSubMenu.css('display') == 'none') {
        $activeSubMenu.stop(true, true).slideDown(400);
        $obj.addClass('open');
    } else {
        $activeSubMenu.stop(true, true).slideUp(400);
        $obj.removeClass('open');
    }
}

function eternalResetMobileMenuState()
{
    var $ = jQuery;
    $('#menu-content').hide();
    $('.eternal-custom-menu-submenu').each(function() {
        $(this).hide();
    });
    $('#custommenu-mobile').find('span.button').each(function() {
        $(this).removeClass('open');
    });
}

function eternalCustomMenuMobileToggle()
{
    var $ = jQuery;
    var w = window,
        d = document,
        e = d.documentElement,
        g = d.getElementsByTagName('body')[0],
        x = w.innerWidth || e.clientWidth || g.clientWidth,
        y = w.innerHeight|| e.clientHeight|| g.clientHeight;
    if (x < 768 || eternalIsMobile.any()) {
        $('#custommenu').hide();
        $('#custommenu-mobile').show();
    } else {
        $('#custommenu-mobile').hide();
        eternalResetMobileMenuState();
        $('#custommenu').show();
    }
    if (eternalIsMobile.any()) {
        $('.header-container').addClass('mobile-header');
    }
}

Event.observe(window, 'resize', function() {
    eternalCustomMenuMobileToggle();
});

document.observe("dom:loaded", function() {
    //run navigation with delays
    mainNav("nav-links", {"show_delay":"100","hide_delay":"100"});
});

function venedor_addEvent(obj, evt, fn) {
    if (obj.addEventListener) {
        obj.addEventListener(evt, fn, false);
    }
    else if (obj.attachEvent) {
        obj.attachEvent("on" + evt, fn);
    }
}

venedor_addEvent(document, "mouseout", function(e) {
    e = e ? e : window.event;
    var from = e.relatedTarget || e.toElement;
    if (!from || from.nodeName == "HTML") {
        jQuery('.eternal-custom-menu-popup').hide();
    }
});
