jQuery(document).ready(    
    function(){
        jQuery(".fancybox").fancybox({
            "transitionIn"	:	"elastic",
        	"transitionOut"	:	"elastic",
        	"speedIn"		:	200, 
        	"speedOut"		:	200, 
        	"overlayShow"	:	false
        });
        
        // Toggle the pitch detail div with the link
        jQuery("a#toggle-ad-pitch-detail").click(
            function(){
                jQuery("div#ad-pitch-detail").slideToggle();
                return false;
            }
        );
    }
);