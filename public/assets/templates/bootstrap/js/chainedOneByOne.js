function chainedOneByOne(config)
{
	var defaultOpt = '';
	var loader = '';
	var passEmptyOption = false;
	
	if (config.defaultOpt) {
	    defaultOpt = '<option value="">' + config.defaultOpt + '</option>';
	}
	
	if (config.loader) {
		loader = config.loader;
	}
	
	if (config.passEmptyOption) {
		passEmptyOption = config.passEmptyOption;
	}

	$.each(config.chained, function (i, o) {
		
	    var el = o.el;
	    var to = o.to.e;
	    var val = o.to.v;
	    var url = o.to.u;
	    var params = o.to.p;
	    var cb = o.to.cb;
	    var tmpDefaultOpt = (typeof o.defaultOpt === 'undefined') ? defaultOpt : o.defaultOpt;
	    var tmpDefaultOptTo = (typeof o.to.defaultOpt === 'undefined') ? true : o.to.defaultOpt;
	    
	    if ($.isArray(val) && val.length) {
	    	val = $.map(val, function (i) {
	    		return i.toString();
	    	});
	    }
	    
	    function clearSelect () {
	    	
	    	if (!$(to).length) {
	    		return;
	    	}

	    	$(to)[0].options.length = 0;
	    	
	    	if (tmpDefaultOptTo) {
	    		$(to).html(tmpDefaultOpt);
	    	}
	    	
	    	if (config.allowDisable) {
	    		$(to).attr('disabled', true);
	    	}
	    	
	    	if (config.allowDisableColor) {
	    		$(to).css('background-color', config.allowDisableColor);
	    	}
	    }
	   
	    clearSelect();
	    
	    $(el).bind('change', function () {
	    	
	    	clearSelect();
	    	
	    	if (!$(to).length) {
	    		
	    		if (cb && typeof cb === 'function') {
        	    	cb.call($(to));
        	    }
	    		
	    		return;
	    	}
	    	
	    	if (passEmptyOption || $(this).val()) {
	    		if (loader) {
	    			showLoaderEx(loader);
	    		}
	    		var keyParam = (typeof el == 'object' ? (o.keyParam || 'id') : el.substr(1));
	    		var pstr = keyParam + '=' + $(this).val();
	    		if (params) {
	    			for (var i = 0; i < params.length; i++) {
	    				pstr += '&' + params[i].substr(1) + '=' + $(params[i]).val();
	    			}
	    		}
	    		
	        	$.getJSON(url + '?' + pstr, function (data) {
	        	    var key, value, sel, idx, options = '';
	        	    for (var i in data) {
	        		    sel = '';
	        		    key = i;
	        		    
	        		    if (typeof data[i] === 'object' && data[i]) {
	        		    	var keys = Object.keys(data[i]);
	        		    	key   = data[i][keys[0]];
	        		    	value = data[i][keys[1]];
	        		    } else {
	        		    	value = data[i];
	        		    }
	        		    if ($.isArray(val) && val.length) {
	        		    	idx = $.inArray(key.toString(), val);
	        		    	if (idx !== -1) {
	        		    		val.splice(idx, 1);
	        		    		sel = ' selected="selected"';
	        		    	}
	        		    } else if (val && key == val) {
	        		    	val = 0;
	        		        sel = ' selected="selected"';
	        		    }
	        	        options += '<option' + sel + ' value="' + key + '">' + value + '</option>';
	        	    }
	        	    $(to).append(options);
	        	    if (config.allowDisable) {
	    	    		$(to).attr('disabled', false);
	    	    	}
	        	    if (config.allowDisableColor) {
	    	    		$(to).css('background-color', '');
	    	    	}
	        	    if (loader) {
		    			hideLoaderEx(loader);
		    		}
	        	    if (cb && typeof cb === 'function') {
	        	    	cb.call($(to));
	        	    }
	        	    $(to).trigger('change', [true]);
	        	});
	    	} else {
	    		clearSelect();
	    		$(to).trigger('change', [true]);
	    		if (cb && typeof cb === 'function') {
        	    	cb.call($(to));
        	    }
	    	}
	    });
	});

	$(config.chained[0].el).trigger('change', [true]);
}