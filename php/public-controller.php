<?php
if(!class_exists('ad_public_controller')){
	
class ad_public_controller {
	
	function __construct(){ }
	
	function init(){
	    add_filter("the_content", array($this, "filter_show_public_pages") );
	    
	}
	
	function filter_show_public_pages($the_content){
	    $tag = '<!-- assignment-desk-public -->';
        $start = strpos($the_content, $tag);
        $my_content  = $the_content;
        if ($start){
            $before_ad = substr($the_content, 0, $start);
            $after_ad = substr($the_content, $start + strlen($tag), strlen($the_content));
            $ad = $this->public_content();
            $my_content = $before_ad . $ad . $after_ad;
            // $my_content = 'EHHHH';
        }
        
        return $my_content;
	}
	
	function public_content(){
	    return 'Im public yo.';
	}
} // END:class ad_public_controller

} // END:if(!class_exists('ad_public_controller'))
?>