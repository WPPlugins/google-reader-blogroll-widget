<?php
/*
Plugin Name: Google Reader Blogroll Widget
Plugin URI: http://blog.acidchaos.de/google-reader-blogroll-widget/
Description: Lists your Google Reader Subscriptions in a Widget
Author: Marcus Himmel	
Version: 0.1.0
Author URI: http://acidchaos.de
*/

/*  Copyright 2008  Marcus Himmel  (email : ac@acidchaos.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// version 0.0.1: initial release

// version 0.0.3: some fixes, added css for "Read more..."

// version 0.1.0: support for multiple widget instances


class GReaderBlogroll {
	
	var $grDesigns = array("Black", "Blue", "Gray", "Green", "Khaki", "Pink", "Slate");
	
	var $default_options = array('pubpagelink' => '', 'title' => '', 'design' => 'None');
	
	function GReaderBlogroll()
	{
		//constructor
		add_action('wp_head', array(&$this, 'add_js_to_head'));
	}
	
	function init()
	{
		if (!$options = get_option('widget_greader_blogroll'))
		        $options = array();
			
	        $widget_ops = array('classname' => 'widget_greader_blogroll', 'description' => 'Google Reader Blogroll');
	        $control_ops = array('width' => 300, 'height' => 300, 'id_base' => 'greaderbr');
	        $name = 'Google Reader Blogroll';
		
	        $registered = false;
	        foreach (array_keys($options) as $o)
		{
		        if (!isset($options[$o]['pubpagelink']))
		                continue;
			
		        $id = "greaderbr-$o";
		        $registered = true;
			
			wp_register_sidebar_widget($id, $name, array(&$this, 'widget'), $widget_ops, array( 'number' => $o ) );
			wp_register_widget_control($id, $name, array(&$this, 'control'), $control_ops, array( 'number' => $o ) );

		}
		
		if (!$registered)
		{
			wp_register_sidebar_widget('greaderbr-1', $name, array(&$this, 'widget'), $widget_ops, array( 'number' => -1 ) );
			wp_register_widget_control('greaderbr-1', $name, array(&$this, 'control'), $control_ops, array( 'number' => -1 ) );
		}
	}
	
	function add_js_to_head(){
		?>
			<script type="text/javascript" src="https://www.google.com/reader/ui/publisher-en.js"></script>
		<?php
	}



  # output for sidebar
	function widget($args, $widget_args = 1)
	{
		extract($args);
		global $post;

		if (is_numeric($widget_args))
			$widget_args = array('number' => $widget_args);
			
		$widget_args = wp_parse_args($widget_args, array( 'number' => -1 ));
		
		extract($widget_args, EXTR_SKIP);
		
		$options_all = get_option('widget_greader_blogroll');
		
		
		if (!isset($options_all[$number]))
			return;
	
		$options = $options_all[$number];
		
		$pubpagelink  = $options['pubpagelink']; 
		$title        = $options['title']; 
		$design       = $options['design'];
		
		if(!in_array($design, $this->grDesigns)) {		
			$design = 'None';
		}
			
		//TODO: clean up here... an there was something you wanted to do.... o0
		$thetitle = ( $design == 'None' ) ? ( $before_title . $title . $after_title ) : '' ;

		$output = $before_widget . $thetitle;
		
		$thetitle = ( $design == 'None' ) ? '': $title ;
		
		$theroll = $this->do_the_roll(urldecode($pubpagelink),  $thetitle , $design);
                
                if($theroll)
                {
                       echo $output . $theroll . $after_widget;
                }
	}

	function do_the_roll($pubpagelink, $title, $design)
	{ 
		$_result = '';
    
		$_preStr = '/shared/user/';
    
		$_sspos = stripos($pubpagelink, $_preStr);
    
		if($_sspos)
		{
    
			$_urlStr = substr($pubpagelink, $_sspos + strlen($_preStr));
			    
			$_optStr = '({c:"' . (( $design != 'None' ) ? strtolower($design) : '-' ) . '",t:"' . $title . '",b:"true"});new GRC';
			    
			$_result =  '<script type="text/javascript" src="https://www.google.com/reader/ui/publisher-en.js"></script>';
			$_result .= '<script type="text/javascript" src="https://www.google.com/reader/public/javascript-sub/user/';
			$_result .= $_urlStr;
			$_result .= '?callback=GRC_p';
			$_result .= urlencode($_optStr);
			$_result .= '"></script>';
		    
			if('' == $title)
			{
				$_result .= '<script type="text/javascript">';
				$_result .= 'var thehead = document.getElementsByTagName("head");';
				$_result .= 'var thestyle = document.createElement("style");';
				$_result .= 'thestyle.setAttribute("type","text/css");';
				$_result .= 'thestyle.innerHTML="div.reader-publisher-module div{background:transparent url(http://www.google.com/reader/ui/favicon.ico) no-repeat scroll left center;min-height:16px;padding-left:18px;margin-top:7px;margin-left:10px;}";';
				$_result .= 'thehead[0].appendChild(thestyle);';
				$_result .= '</script>';
			}
		}
    
		return ('' == $_result) ? FALSE : $_result;
	}


	function control($widget_args = 1)
	{
		global $wp_registered_widgets;
		static $updated = false;
	
		if (is_numeric($widget_args))
			$widget_args = array('number' => $widget_args);
			
		$widget_args = wp_parse_args($widget_args, array('number' => -1));
		
		extract($widget_args, EXTR_SKIP);
		
		$options_all = get_option('widget_greader_blogroll');
		
		if (!is_array($options_all))
			$options_all = array();
					
		if (!$updated && !empty($_POST['sidebar'])) {
			$sidebar = (string)$_POST['sidebar'];
	 
			$sidebars_widgets = wp_get_sidebars_widgets();
			if (isset($sidebars_widgets[$sidebar]))
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();
				
			foreach ($this_sidebar as $_widget_id) {
				if ('widget_greader_blogroll' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']))
				{
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if (!in_array("greaderbr-$widget_number", $_POST['widget-id']))
						unset($options_all[$widget_number]);
				}
			}
			
			foreach ((array)$_POST['widget_greader_blogroll'] as $widget_number => $posted)
			{			
				if (!isset($posted['pubpagelink']) && isset($options_all[$widget_number]))
					continue;
	  
				$pubpagelink	= strip_tags(urlencode($posted['pubpagelink']));
				$title       	= strip_tags(stripslashes($posted['title']));
				$design    	= strip_tags(stripslashes($posted['design']));
	 
				$options_all[$widget_number] = array('pubpagelink' => $pubpagelink, 'title' => $title, 'design' => $design);
			}
			update_option('widget_greader_blogroll', $options_all);
			$updated = true;
		}
		
		if (-1 == $number) {
		    $number = '%i%';
		    $values = $this->default_options;
		}
		else {
		    $values = $options_all[$number];
		}
	 
		//the form /////////////////////////////////////////////////////////////
		
		$pubpagelink	= urldecode($values['pubpagelink']);
		$title       	= htmlspecialchars($values['title'], ENT_QUOTES);
		$design    	= htmlspecialchars($values['design'], ENT_QUOTES);
	
		$tmpdesigns = array_pad($this->grDesigns, sizeof($this->grDesigns), 0);
		$tmpdesigns[] = 'None';
		
		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		echo '<p style="text-align:right;"><label for="greaderblogroll-pubpagelink">Public page link: <input style="width: 200px;" id="widget_greader_blogroll-'.$number.'-pubpagelink" name="widget_greader_blogroll['.$number.'][pubpagelink]" type="text" value="'.$pubpagelink.'" /></label></p>';
		echo '<p style="text-align:right;">(Link to the public page of your public label, can be found in the <a href="https://www.google.com/reader/settings">Google Reader settings</a>, in the "Folders and Tags"-tab, the "<i>view public page</i>"-link)</p>';
		echo '<p style="text-align:right;"><label for="greaderblogroll-title">Title: <input style="width: 200px;" id="widget_greader_blogroll-'.$number.'-title" name="widget_greader_blogroll['.$number.'][title]" type="text" value="'.$title.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="greaderblogroll-design">Color scheme: <select size="1" style="width: 200px;" id="widget_greader_blogroll-'.$number.'-design" name="widget_greader_blogroll['.$number.'][design]" type="text" value="'.$design.'">';
		foreach($tmpdesigns as $d_opt) {
			echo '<option ';
			if($d_opt == $design) echo 'selected';
			echo '>' . $d_opt . '</option>';
		}
		echo '</select></label></p>';
		echo '<p style="text-align:right;">(Select "None" to match your theme)</p>';
	
	}
}

$greaderblogroll = new GReaderBlogroll();
add_action('widgets_init', array($greaderblogroll, 'init'));

?>