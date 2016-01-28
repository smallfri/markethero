jQuery(document).ready(function($){
	
	var ajaxData = {};
	if ($('meta[name=csrf-token-name]').length && $('meta[name=csrf-token-value]').length) {
			var csrfTokenName = $('meta[name=csrf-token-name]').attr('content');
			var csrfTokenValue = $('meta[name=csrf-token-value]').attr('content');
			ajaxData[csrfTokenName] = csrfTokenValue;
	}
    
    $('#SupportTicketReply_rating').on('change', function(){
        var $this = $(this);
        if ($this.data('running')) {
            return false;
        }
        $this.data('running', true);
        var data = {
            ticket_id: $this.data('ticket'),
            reply_id: $this.data('reply'),
            rating: parseInt($this.val())
        };
        $.get($this.data('url'), data, function(json){
            $this.data('running', false);
            alert(json.message);
        }, 'json');        
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