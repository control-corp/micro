function roundTo(v, to)
{
	to = to || 0;
	
	if (to == 0) {
		to = 1;
	} else if (to == 1) {
		to = 10;
	} else if (to == 2) {
		to = 100;
	} else if (to == 3) {
		to = 1000;
	}

	return Math.round(v * to) / to;
}

$(function () {
	
	$(document).ajaxStop(function () {

	}).ajaxError(function(event, request, settings) {
	    var error = request.responseText;
	    var match = error.match(/^\{\"error\"\:/);
	    if (match) {
	        eval('var err = ' + error +';');
	        if (typeof err == 'object' && err.error) {
	            alert(err.error);
	        }
	    }
	});
	
	$('a[href="#"], a[href=""]').on('click', function () {
		return false;
	});
	
    if ($('#navigation li ul li a.active').length) {
    	$('#navigation li ul li a.active').parent().parent().parent().find('a:first').addClass('active');
    }

	$(document).on('keypress', '.checkFloat', function (evt) {
		evt = (evt) ? evt : window.event;
	    var charCode = (evt.which) ? evt.which : evt.keyCode;
	    if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode != 46 && charCode != 116) {
	        return false;
	    }
	    return true;
	});

	if ($.datepicker) {
		if (typeof siteLanguage !== 'undefined') {
			$.datepicker.setDefaults($.datepicker.regional[siteLanguage['code']]);
		}
		$.datepicker.setDefaults({
			changeMonth : true,
	        changeYear  : true,
	        dateFormat  : 'yy-mm-dd'
		});
		$('.onlyDate').datepicker();
	}
	
	if ($.timepicker) {
		if (typeof siteLanguage !== 'undefined') {
			$.timepicker.setDefaults($.timepicker.regional[siteLanguage['code']]);
		}
		$('.date').datetimepicker();
	}
	
	$('.print').bind('click', function () {
        $('.' + $(this).attr('data-rel')).jqprint({
        	debug : 0,
        	callBack : function (doc) {
        	    doc.find('.noprint').each(function () {
        			$(this).remove();
        		});
        	    doc.find('input').each(function () {
        	    	$(this).replaceWith($(this).val());
        	    });
        	    doc.find('select').each(function () {
        	    	$(this).replaceWith($(this).find('option:selected').text());
        	    });
        	}
        });
        return false;
    });
});