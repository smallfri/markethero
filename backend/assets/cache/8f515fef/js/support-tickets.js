jQuery(document).ready(function($){
	
	var ajaxData = {};
	if ($('meta[name=csrf-token-name]').length && $('meta[name=csrf-token-value]').length) {
			var csrfTokenName = $('meta[name=csrf-token-name]').attr('content');
			var csrfTokenValue = $('meta[name=csrf-token-value]').attr('content');
			ajaxData[csrfTokenName] = csrfTokenValue;
	}
	
	$('.btn-delete-reply').on('click', function() {
		var $this = $(this);
		if (!confirm($this.data('confirm'))) {
			return false;
		}
        $this.closest('div.box').fadeOut('slow');
        var count = parseInt($('.replies-counter').text()) - 1;
        $('.replies-counter').text(count);
        if (count == 0) {
            $('.replies-counter').parent().fadeOut('slow');
        }
		$.post($this.attr('href'), ajaxData, function(){});
		return false;
	});
    
    var hash = window.location.hash;
    if (hash && $(hash).length) {
        var $hash = $(hash);
        $hash.css({'background': 'yellow'}).find('.box-footer').css({'background': 'yellow'});
        setTimeout(function(){
            $hash.css({'background': 'white'}).find('.box-footer').css({'background': 'white'});;
        }, 5000);
    }
});