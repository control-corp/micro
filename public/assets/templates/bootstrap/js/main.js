$(document).ajaxStop(function () {

}).ajaxError(function(event, request, settings) {
    var error = request.responseText;
    var match = error.match(/^\{\"error\"\:/);
    if (match) {
        eval('var err = ' + error +';');
        if (typeof err == 'object' && err.error) {
        	if ($.isPlainObject(err.error) && err.error.message) {
        		alert(err.error.message);
        	} else {
        		alert(err.error);
        	}
        }
    }
});

function moveToErrors()
{
	if ($('span.element-error').length) {
	    $('html, body').animate({
	        scrollTop: $('span.element-error').first().prev().offset().top - 30
	    },500);
	}
}

bootbox.setDefaults({
	locale: 'bg_BG'
});

$(function () {
	
	moveToErrors();
	
	$('.remove').on('click', function () {
		var that = $(this);
		bootbox.confirm('Сигурни ли сте, че искате да изтриете записа ?', function (res) {
			if (+res) {
				window.location.href = that.attr('href');
			}
		});
        return false;
    });

	if ($.fn.selectpicker) {
		$('.selectpicker').selectpicker({
			liveSearch: 1,
			selectedTextFormat: 'count>3'
		});
	}
	
	if ($.fn.datepicker) {
		var datepickerFormat = 'yyyy-mm-dd';
		if (siteConfig && siteConfig.datepicker && siteConfig.datepicker.format) {
			datepickerFormat = siteConfig.datepicker.format;
		}
		$.fn.datepicker.defaults.format = datepickerFormat;
		$.fn.datepicker.defaults.language = 'bg';	
		$('.datepicker').datepicker();
	}
	
	$('.breadcrumb a[href="#"]').on('click', function (e) {
		e.preventDefault();
		return false;
	});
	
	$('body').on('click', '[data-url]', function () {
	    window.location.href = $(this).attr('data-url');
	    return false;
	});

	if ($.summernote) {
		$('.summernote').summernote({
			//airMode: true
			lang: 'bg-BG',
			height: 300,                 // set editor height
			minHeight: null,             // set minimum height of editor
			maxHeight: null,             // set maximum height of editor
			//focus: true,                 // set focus to editable area after initializing summernote
		});
	}
	
	$('table.sortable tbody').sortable({
		handle: '.sortHandle',
		axis: 'y',
	    update: function (event, ui) {
	    	if (this !== ui.item.parent()[0]) {
			    return;
			}
	    	var self = ui.item.parent();
	    	$holder  = self.closest('table');
			var url = $holder.attr('data-sort-url');
			if (!url) {
				self.sortable('cancel');
				return;
			}
			$holder.block();
			$.post($holder.attr('data-sort-url'), self.sortable('serialize'), function (response) {
	            if (response.error) {
	            	self.sortable('cancel');
	            	alert(response.errorText);
	            }
	            $holder.unblock();
	        }, 'json');
	    }
	});

	$('table.sortable td').each(function () {
	    $(this).css('width', $(this).width());
	});
});

$(function () {
	
	var gridCheckAllLogic = {};
	
	$('body').on('click', '.grid-form input[data-handler="grid.checkall"]', function () {
		var that = $(this);
		var rel  = that.attr('data-rel');
		var form = that.closest('form');
		var inputs = form.find('input[name="' + rel + '"]');
		var inputsChecked = form.find('input[name="' + rel + '"]:checked');
 		if (that.is(':checked')) {
 			inputs.attr('checked', true);
		} else {
			inputs.attr('checked', false);
		}
		if (gridCheckAllLogic[rel] === undefined) {
			gridCheckAllLogic[rel] = true;
			inputs.on('click', function () {
				if (inputsChecked.length === inputs.length) {
					that.attr('checked', true);
				} else {
					that.attr('checked', false);
				}
			});
		}
	});
	
	$('.grid-form .grid-buttons input[type="submit"]').on('click', function () {
		
		var that = $(this);
		var rel  = that.attr('data-rel');
		var form = that.closest('form');
		
		if (that.attr('data-action') === undefined || rel === undefined) {
			return true;
		}
		
		if (form.length === 0) {
			alert('Липсва форма');
			return false;
		}
		
		var inputsChecked = form.find('input[name="' + rel + '"]:checked');
		
		
		if (inputsChecked.length === 0) {
			bootbox.alert('Моля. изберете поне 1 запис');
		} else {
			
			function submitForm()
			{
				form.attr('action', that.attr('data-action'));
				form.submit();
			}
			
			if (that.attr('data-confirm') === undefined) {
				submitForm();
			} else {
				bootbox.confirm(that.attr('data-confirm'), function (res) {
					if (+res) {
						submitForm();
					}
				});
			}
		}
		
		return false;
	});
});