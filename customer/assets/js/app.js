/**
 * This file is part of the MailWizz EMA application.
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2015 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
jQuery(document).ready(function($){
	
	ajaxData = {};
	if ($('meta[name=csrf-token-name]').length && $('meta[name=csrf-token-value]').length) {
			var csrfTokenName = $('meta[name=csrf-token-name]').attr('content');
			var csrfTokenValue = $('meta[name=csrf-token-value]').attr('content');
			ajaxData[csrfTokenName] = csrfTokenValue;
	}
	
	// input/select/textarea fields help text
	$('.has-help-text').popover();
	$(document).on('blur', '.has-help-text', function(e) {
		if ($(this).data('bs.popover')) {
			// this really doesn't want to behave correct unless forced this way!
			$(this).data('bs.popover').destroy();
			$('.popover').remove();
			$(this).popover();
		}
	});
	
	$('.has-tooltip').tooltip({
		html: true,
		container: 'body'
	});
	
	// buttons with loading state
	$('button.btn-submit').button().on('click', function(){
		$(this).button('loading');
	});
	
    $('a.header-account-stats').on('click', function(){
        var $this = $(this);
        if ($this.data('loaded')) {
            return true;
        }
        
        $this.data('loaded', true);
        
        var $dd   = $this.closest('li').find('ul:first'),
            $menu = $dd.find('ul.menu');
        
        $.get($this.data('url'), {}, function(json){
            if (json.html) {
                $menu.html(json.html);
            }
        }, 'json');
    });
    
    $('.header-account-stats-refresh').on('click', function(){
        $('a.header-account-stats').data('loaded', false).trigger('click').trigger('click');
        return false;
    });
    
    $('.left-side').on('mouseenter', function(){
        $('.timeinfo').stop().fadeIn();
    }).on('mouseleave', function(){
        $('.timeinfo').stop().fadeOut();
    });
});