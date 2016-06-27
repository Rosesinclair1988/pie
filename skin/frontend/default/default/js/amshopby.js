function amshopby_start(collapsing){
    
    var btn = $('amshopby-price-btn');
    if (Object.isElement(btn)){
        Event.observe(btn, 'click', amshopby_price_click_callback);
        Event.observe($('amshopby-price-from'), 'focus', amshopby_price_focus_callback);
        Event.observe($('amshopby-price-from'), 'keypress', amshopby_price_click_callback);
        Event.observe($('amshopby-price-to'), 'focus', amshopby_price_focus_callback);
        Event.observe($('amshopby-price-to'), 'keypress', amshopby_price_click_callback);
    }
    
    $$('a.amshopby-less', 'a.amshopby-more').each(function (a){
        a.observe('click', amshopby_toggle)
    });
    
    $$('span.amshopby-plusminus').each(function (span){
        span.observe('click', amshopby_category_show)
    });
    
    if (collapsing){
        $$('.block-layered-nav dt').each(function (dt){
            dt.observe('click', amshopby_filter_show)
        });
    }
     
} 

function amshopby_price_click_callback(evt){
    if (evt && evt.type == 'keypress' && 13 != evt.keyCode)
        return;
    
    var numFrom = amshopby_price_format($('amshopby-price-from').value);
    var numTo   = amshopby_price_format($('amshopby-price-to').value);
  
    if ((numFrom < 0.01 && numTo < 0.01) || numFrom < 0 || numTo < 0)   
        return;   
        
    var url =  $('amshopby-price-url').value.gsub('price-from', numFrom).gsub('price-to', numTo);
    setLocation(url);
}

function amshopby_price_focus_callback(evt){
    var el = Event.findElement(evt, 'input');
    if (isNaN(parseFloat(el.value))){
        el.value = '';
    } 
}


function amshopby_price_format(num){
    num = parseFloat(num);
    if (isNaN(num))
        num = 0;
        
    //we need '.00' in url
    return Math.round(num) + '.00';   
}


function amshopby_slider(width, from, to, max_value, prefix) {
    var slider = $('amshopby-slider');
    
    return new Control.Slider(slider.select('.handle'), slider, {
      range: $R(0, width),
      sliderValue: [from, to],
      restricted: true,
      
      onChange: function (values){
        this.onSlide(values);  
        amshopby_price_click_callback(null);  
      },
      onSlide: function(values) { 
        $(prefix+'from').value = Math.round(max_value*values[0]/width);
        $(prefix+'to').value   = Math.round(max_value*values[1]/width);
      }
    });
}

function amshopby_toggle(evt){
    var attr = Event.findElement(evt, 'a').id.substr(14);
    
    $$('.amshopby-attr-' + attr).invoke('toggle');       
        
    $('amshopby-less-' + attr, 'amshopby-more-' + attr).invoke('toggle');
    
    Event.stop(evt);
    return false;
}

function amshopby_category_show(evt){
    var span = Event.findElement(evt, 'span');
    var id = span.id.substr(16);
    
    $$('.amshopby-cat-parentid-' + id).invoke('toggle');

    span.toggleClassName('minus'); 
    Event.stop(evt);
          
    return false;
}

function amshopby_filter_show(evt){
    var dt = Event.findElement(evt, 'dt');
    
    dt.next('dd').down('ol').toggle();
    dt.toggleClassName('amshopby-collapsed'); 
  
    Event.stop(evt);
    return false;
}
