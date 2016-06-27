
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

function venedor_refresh_elements() {
    jQuery('[data-hover="dropdown"]').dropdownHover(); // bootstrap dropdown hover
    product_grid_effect();
    product_list_effect();
    if (typeof eternal_ajaxcart != 'undefined')
        eternal_ajaxcart.bindEvents();
}

jQuery(document).ajaxComplete(function(event, xhr, settings) {
    setTimeout(venedor_refresh_elements, 400);
})

function products_grid_focus($item_inner) {
    var $ = jQuery;
    $item_inner.parent().addClass('item-active');

    $add_links = $item_inner.find('.add-to-links');
    if ($item_inner.parent().hasClass('addlinks-block')) {
        $add_links.css({width:'auto', height:0});
        $add_links.stop().animate({'height': '35px'});
    } else {
        $add_links.css({width:'auto', display:'inline-block'});
        w = $add_links.outerWidth() + 20;
        $add_links.css({width:0});
        $add_links.stop().animate({'width': w + 'px', 'padding-left': '15px', 'margin-left': '0'});
    }

    $review_link = $item_inner.find('.ratings .amount');
    if ($review_link.length) {
        $review_link.css({width:'auto'});
        $review_link.css({display:'inline-block'});
        w = $review_link.outerWidth() + 5;
        $review_link.css({width:0});
        $review_link.stop().animate({'width': w + 'px', 'padding-left': '0px', 'margin-left': '0'});
    }
}

function products_grid_lost($item_inner) {
    var $ = jQuery;
    $btn_cart = $item_inner.find('.btn-cart');
    w = $btn_cart.outerWidth();

    $item_inner.parent().removeClass('item-active');

    $btn_cart.css({'width': 'auto'});
    w1 = $btn_cart.outerWidth();
    $btn_cart.css({'width': w + 'px'});
    $btn_cart.stop().animate({'width': w1 + 'px'}, 200);

    $add_links = $item_inner.find('.add-to-links');
    if ($item_inner.parent().hasClass('addlinks-block')) {
        $add_links.stop().animate({'height': '0'}, 400);
    } else {
        $add_links.stop().animate({'width': '0', 'padding-left': 0, 'margin-left': '-3px'}, 400, function() {
            $(this).hide();
        });
    }

    $review_link = $item_inner.find('.ratings .amount');
    $review_link.stop().animate({'width': '0', 'padding-left': 0, 'margin-left': '-3px'}, 400, function() {
        $(this).hide();
    });
}

// Products Grid
var products_grid_timer;

function products_grid_resize() {
    var $ = jQuery;
    var column2_count = 3;
    if (VENEDOR_RESPONSIVE) {
        var winWidth = $(window).outerWidth();

        if ((!$('body').hasClass('bv3') && winWidth >= 963) || ($('body').hasClass('bv3') && winWidth >= 975)) {
            if ($('.2columns .products-grid .item').hasClass(SPAN_CSS + '6')) {
                $('.2columns .products-grid .item').removeClass('item-first');
                $('.2columns .products-grid .item').removeClass(SPAN_CSS + '6');
                $('.2columns .products-grid .item').addClass(SPAN_CSS + '4');
                $('.2columns .products-grid .col3-1').addClass('item-first');
            }
            column2_count = 3;
        } else {
            if ($('.2columns .products-grid .item').hasClass(SPAN_CSS + '4')) {
                $('.2columns .products-grid .item').removeClass('item-first');
                $('.2columns .products-grid .item').removeClass(SPAN_CSS + '4');
                $('.2columns .products-grid .item').addClass(SPAN_CSS + '6');
                $('.2columns .products-grid .col2-1').addClass('item-first');
            }
            column2_count = 2;
        }
    } else {
        $('.2columns .products-grid .col3-1').addClass('item-first');
    }
    $('.2columns .products-grid').each(function() {
        if ($(this).hasClass('flexslider'))
            return;

        if ($(this).parents('.tab-pane').length) {
            if (!$(this).parents('.tab-pane').hasClass('active'))
                return;
        }

        var $grid = $(this);
        $grid.find('.product-image img').imgpreload({
            all: function() {
                var h = 0;
                var index = 0;
                var size = $grid.find('.item').length;
                var items = new Array();
                $grid.find('.item-inner').each(function() {
                    index++;
                    $(this).css('height', 'auto');
                    $parent = $(this).parent();
                    if ($parent.hasClass('addlinks-block') && !$parent.hasClass('hover-disable') && $parent.find('.add-to-links').length) {
                        if (h < $(this).innerHeight())
                            h = $(this).innerHeight();
                    } else {
                        if (h < $(this).innerHeight() + 10)
                            h = $(this).innerHeight() + 10;
                    }
                    if (index % column2_count == 0 || index == size) {
                        items.push(this);
                        $.each(items, function(index, value) {
                            $(value).css({height: h+'px'});
                            $(value).parent().css({height: (h + 4)+'px'});
                        });
                        h = 0;
                        items = new Array();
                    } else {
                        items.push(this);
                    }
                })
            }
        });
    });

    var column_count = 4;
    if (VENEDOR_RESPONSIVE) {
        var winWidth = $(window).outerWidth();

        if ((!$('body').hasClass('bv3') && winWidth >= 963) || ($('body').hasClass('bv3') && winWidth >= 975)) {
            if ($('.1column .products-grid .item').hasClass(SPAN_CSS + '4')) {
                $('.1column .products-grid .item').removeClass('item-first');
                $('.1column .products-grid .item').removeClass(SPAN_CSS + '4');
                $('.1column .products-grid .item').addClass(SPAN_CSS + '3');
                $('.1column .products-grid .col4-1').addClass('item-first');
            }
            column_count = 4;
        } else {
            if ($('.1column .products-grid .item').hasClass(SPAN_CSS + '3')) {
                $('.1column .products-grid .item').removeClass('item-first');
                $('.1column .products-grid .item').removeClass(SPAN_CSS + '3');
                $('.1column .products-grid .item').addClass(SPAN_CSS + '4');
                $('.1column .products-grid .col3-1').addClass('item-first');
            }
            column_count = 3;
        }
    } else {
        $('.1column .products-grid .col4-1').addClass('item-first');
    }
    $('.1column .products-grid').each(function() {
        if ($(this).hasClass('flexslider'))
            return;

        if ($(this).parents('.tab-pane').length) {
            if (!$(this).parents('.tab-pane').hasClass('active'))
                return;
        }

        var $grid = $(this);
        $grid.find('.product-image img').imgpreload({
            all: function() {
                var h = 0;
                var index = 0;
                var size = $grid.find('.item').length;
                var items = new Array();
                $grid.find('.item-inner').each(function() {
                    index++;
                    $(this).css('height', 'auto');
                    if (h < $(this).innerHeight() + 10)
                        h = $(this).innerHeight() + 10;
                    if (index % column_count == 0 || index == size) {
                        items.push(this);
                        $.each(items, function(index, value) {
                            $(value).css({height: h+'px'});
                            $(value).parent().css({height: (h + 4)+'px'});
                        });
                        h = 0;
                        items = new Array();
                    } else {
                        items.push(this);
                    }
                })
            }
        });
    });

    if (products_grid_timer) clearTimeout(products_grid_timer);
}

function product_grid_effect() {
    var $ = jQuery;
    if (!!('ontouchstart' in window)) {
        if ($('.products-grid .item').length) {
            $('.products-grid .item-inner').bind('touchstart', function() {
                products_grid_focus($(this));
            });
            $('.products-grid .item-inner').bind('touchend', function() {
                products_grid_lost($(this));
            });
        }
    } else {
        if ($('.products-grid .item').length) {
            $('.products-grid .item-inner').hover(function() {
                products_grid_focus($(this));
            }, function() {
                products_grid_lost($(this));
            });
        }
    }
    products_grid_resize();
}

function product_list_effect() {
    var $ = jQuery;
    if (!!('ontouchstart' in window)) {
        if ($('.products-list .item').length) {
            $('.products-list .item').bind('touchstart', function() {
                $(this).addClass('item-active');
            });
            $('.products-list .item').bind('touchend', function() {
                $(this).removeClass('item-active');
            });
        }
    } else {
        if ($('.products-list .item').length) {
            $('.products-list .item').hover(function() {
                $(this).addClass('item-active');
            }, function() {
                $(this).removeClass('item-active');
            });
        }
    }
}

// Prototype trigger event
Element.prototype.triggerEvent = function(eventName)
{
    if (document.createEvent)
    {
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent(eventName, true, true);

        return this.dispatchEvent(evt);
    }

    if (this.fireEvent)
        return this.fireEvent('on' + eventName);
}

jQuery(document).ready(function($) {

    // add mobile class for mobile devices
    if (eternalIsMobile.any()) {
        $('body').addClass('mobile');
    }

    if (navigator.userAgent.search("Safari") >= 0 && navigator.userAgent.search("Chrome") < 0) {
        $('body').addClass('safari');
    }

    menu_resize();
    if ( VENEDOR_HEADER_FIXED && !eternalIsMobile.any() )
        header_fixed();

    var menu_timer;

    $(window).resize(function() {
        clearTimeout(menu_timer);
        menu_timer = setTimeout(function() {
            menu_resize();
            if ( VENEDOR_HEADER_FIXED && !eternalIsMobile.any() ) header_fixed();
            if (menu_timer) clearTimeout(menu_timer);
        }, 200);
        clearTimeout(products_grid_timer);
        products_grid_timer = setTimeout(products_grid_resize, 200);
    });

    $(window).scroll(function(){
        if ( VENEDOR_HEADER_FIXED && !eternalIsMobile.any() )
            header_fixed();
    });

    product_grid_effect();
    product_list_effect();

    // bootstrap tab events
    $('a[data-toggle="tab"]').click(function (e) {
        setTimeout(products_grid_resize, 50);
    });
});

function menu_resize() {
    var $ = jQuery;
    c_w = $('.header-menu > div').width();
    menu_h = $(".header-container .header-menu").height();
    if (c_w < 940) {
        $('.quick-access').css('bottom', 39 + menu_h);
        $('.quick-access1').css('bottom', 19 + menu_h);
    } else {
        $('.quick-access').css('bottom', 0);
    }
}

function header_fixed() {
    var $ = jQuery;
    var flag = true;
    c_w = $('.header-menu > div').width();
    if (c_w <= 740) {
        flag = false;
    }
    window_y = $(window).scrollTop();
    menu_h = $(".header-container .header-menu").height();
    if (!$(".header-container").hasClass("fixed")) {
        off_y = $('.notices-container').height() + $('.header-container').height() - menu_h - parseInt($('.header-menu').css('padding-bottom')) + 1;
    }
    if (window_y > off_y && flag) {
        if (!($(".header-container").hasClass("fixed"))){
            menu_t = parseInt($('.header-menu').css('margin-top'));
            $(".header-container").addClass("fixed");
            menu_h = $(".header-container .header-menu").height();
            $('body').css('padding-top', menu_h + menu_t);
            $('.header-menu').stop().animate({'padding-bottom': 0});
            $('.eternal-custom-menu-popup').hide();
            if (VENEDOR_HEADER_FIXED_LOGO) {
                $logo_img = $('.header-container.fixed .header .logo img');
                logo_w = $logo_img.innerWidth();
                //$logo_img.css('width',0).animate({'width': logo_w});
                $logo_img.css({'width': logo_w});
                logo_w += 15;
                //$('.header-menu #custommenu, .header-menu #custommenu-mobile').stop().animate({'padding-left': logo_w});
                $('.header-menu #custommenu, .header-menu #custommenu-mobile').css({'padding-left': logo_w});
            } else {
                $('.header-container.fixed .header .logo').hide();
            }
        }
    } else {
        if (($(".header-container").hasClass("fixed"))){
            $('.header-container.fixed .header .logo').show();
            $('.header-container.fixed .header .logo img').css({'width':'auto'});
            $('body').css('padding-top', '0');
            $('.header-menu').stop().css({'padding-bottom': VENEDOR_MENU_PADDING});
            $('.eternal-custom-menu-popup').hide();
            $('.header-menu #custommenu, .header-menu #custommenu-mobile').css('padding-left', 0);
            $(".header-container").removeClass("fixed");
        }
    }
}

