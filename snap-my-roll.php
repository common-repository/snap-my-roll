<?php
/*
Plugin Name: Snap My Roll
Plugin URI: http://wordpress.org/extend/plugins/snap-my-roll/
Description: This sidebar <a href="widgets.php">widget</a> randomly displays a snapshot of websites from your blogroll.
Version: 1.0.7
Author: Denis Balencourt
Author URI: http://www.balencourt.com/blog/snap-my-roll
*/

include('functions.php');

function add_javascript(){
	$js_url = get_option('siteurl') . '/wp-content/plugins/'. dirname( plugin_basename(__FILE__)) . '/js/';
	wp_enqueue_script('jquery');
	wp_enqueue_script('innerfade',$js_url."jquery.innerfade.js",array('jquery'),'1.0' );
}

function snap_my_roll($size,$key,$category,$timeout){
	global $wpdb;
	if($key == 'Enter your key'){
		echo 'Please <a href="http://www.websnapr.com/#register">register to websnapr</a>';
		return;
	}
	
	//Transform seconds into milliseconds
	if($timeout < 1){ 
		$timeout = 1000;
	}else{
		$timeout = $timeout * 1000;
	}
	
	//Define dir & path to plugin components
	$snap_my_roll_url = dirname( __FILE__ ) . '/';

	// Create SQL request & get links from DB according to category
	if($category){
		$my_query = "SELECT ".$wpdb->prefix."links.link_name, ".$wpdb->prefix."links.link_url FROM ".$wpdb->prefix."term_relationships, ".$wpdb->prefix."links WHERE link_visible = 'Y' and ".$wpdb->prefix."links.link_id = ".$wpdb->prefix."term_relationships.object_id and ".$wpdb->prefix."term_relationships.term_taxonomy_id = $category"; 
	}else{
		$my_query = "SELECT ".$wpdb->prefix."links.link_name, ".$wpdb->prefix."links.link_url FROM ".$wpdb->prefix."links WHERE link_visible = 'Y'";
	}
	$links = $wpdb->get_results($my_query, OBJECT);

	//Rand the the array of links and slice it to 20 elements to reduce loading time and ensure that all links have a chance
	if($links){
		shuffle($links);
		if (count($links)>20){
			$links = array_slice($links,0,20); 
		}

		//build the javascript
		echo '<script type="text/javascript">'."\n";
		echo "\$balencourt_smr=jQuery.noConflict();\n";
		echo "\$balencourt_smr(document).ready( function(){\n"; 
		echo "\$balencourt_smr('#blogroll').innerfade(\n";
		echo "{ timeout: $timeout ,type: 'sequence', containerheight: '";

		//adapt the height of the container accordingly to the size of the snapshot
		if($size == 't'){
		echo "100px";
		}
		if($size == 's'){
		echo "180px";
		}
		if($size == 'm'){
		echo "330px";
		}
		echo "', runningclass: 'snap_li'});\n"; 
		echo "});\n";
		echo "</script>\n";
		
		//determine upload directory
		$directory = array();
		$directory = wp_upload_dir(); 
		$charset = get_option('blog_charset');
		if (!is_writable($directory['basedir']. '/')){
			echo("Wordpress upload directory is not writable");
			return;
		}
		//create error image to compare them with file downloaded
		$queued = imagecreatefromjpeg($snap_my_roll_url.$size."queued.jpg");
		$exceeded = imagecreatefrompng($snap_my_roll_url.$size."exceeded.png");
		//echo "$snap_my_roll_url$size"."queued.jpg";	
		
		//lets print the list of images
		echo '<ul id="blogroll">'."\n";
		foreach($links as $link){
			$img_file = $directory['basedir'] .'/'.sanitize_filename($link->link_url."_".$size.".png");
			$img_url = $directory['baseurl'] .'/'.sanitize_filename($link->link_url."_".$size.".png");
			// If file does not exist or is older than 10 days update it
			if(!file_exists($img_file) || time() - fileatime($img_file) > 864000 ){
				$f = curl_get_file_contents('http://images.websnapr.com/?size='.$size.'&key='.$key.'&url='.rawurlencode($link->link_url));
				$i = imagecreatefromstring($f);
				if(!imagecompare($i,$queued) && !imagecompare($i,$exceeded)){
					imagepng($i,$img_file,8);
				}else{
					continue;
				}
			}
			echo '<li style="width:252px;">'."\n\t";
			echo '<a href="' .$link->link_url.'" title="'.$link->link_name.'">'."\n\t";
			//echo '<img src="http://images.websnapr.com/?size='.$size.'&amp;key='.$key.'&amp;url='.$link->link_url.'" alt="'.$link->link_name.'" /><br />&raquo;'.$link->link_name."\n\t".'</a></li>';
			echo '<img src="'.$img_url.'" alt="'.$link->link_name.'" /></a><br />';
			echo '<a href="http://www.balencourt.com/blog" title="Widget made by Denis Balencourt">&raquo;</a>&nbsp;';
			echo '<a href="' .$link->link_url.'" title="'.$link->link_name.'">'.$link->link_name."\n\t".'</a></li>';
			echo "\n";
		}
		echo "</ul>\n";
	}else{
		echo "Add some links !";
		return;
	}
}

function widget_snap_my_roll_init() {

        if (!function_exists('register_sidebar_widget')) { return; }

        function widget_snap_my_roll($args){
		extract($args);
		if(!$options = get_option('snap_my_roll')) $options = array('title'=>'Snap My Roll', 'size'=>'s', 'key'=>'Enter your key', 'category'=>'0', 'timeout'=>'4' );
		
		//Print widget HTML element from the functions.php template
		
		echo $before_widget . $before_title . $options['title'] . $after_title;
		snap_my_roll($options['size'], $options['key'], $options['category'], $options['timeout']);
		echo $after_widget;
        }

	function widget_snap_my_roll_options(){
		global $wpdb;
		if(!$options = get_option('snap_my_roll')) $options = array('title'=>'Snap My Roll', 'size'=>'s', 'key'=>'Enter your key', 'category'=>'0', 'timeout'=>'4' );
		if($_POST['snap_my_roll-submit']){
			$options = array('title' => $_POST['snap_my_roll-title'], 'size' => $_POST['snap_my_roll-size'], 'key' => $_POST['snap_my_roll-key'], 'category' => $_POST['snap_my_roll-category'], 'timeout' => $_POST['snap_my_roll-timeout']);
			update_option('snap_my_roll', $options);
		}
		
		$my_query = 'SELECT '.$wpdb->prefix.'terms.name, '.$wpdb->prefix.'terms.term_id FROM '.$wpdb->prefix.'terms where '.$wpdb->prefix.'terms.term_id IN (SELECT '.$wpdb->prefix.'term_taxonomy.term_id FROM `wp_term_taxonomy` where '.$wpdb->prefix.'term_taxonomy.taxonomy = "link_category")';
			
		$cats = $wpdb->get_results($my_query, OBJECT);
		echo '<p>Widget Title:<input type="text" name="snap_my_roll-title" value="'.$options['title'].'" id="snap_my_roll-title" /></p>';
		echo '<p>Websnapr Key:<input type="text" name="snap_my_roll-key" value="'.$options['key'].'" id="snap_my_roll-key"/></p>';
		echo 'No Key ? <a href="http://www.websnapr.com/#register" target="_blank">Get One !</a><br><br>';
		echo '<p>Select a snapshot size <br>';
		echo '<input type="radio" name="snap_my_roll-size" value="t"';
		if($options['size'] == 't'){echo'checked="checked"';}
		echo '" id="snap_my_roll-size" />&nbsp;Tiny [92x70 pixels]<br>';
		echo '<input type="radio" name="snap_my_roll-size" value="s"';
		if($options['size'] == 's'){echo'checked="checked"';}
		echo '" id="snap_my_roll-size" />&nbsp;Small [202x152 pixels]<br>';
		echo '<br/><em> Not available for free</em><br/><br/>';
		echo '<input type="radio" name="snap_my_roll-size" value="m"';
		if($options['size'] == 'm'){echo'checked="checked"';}
		echo '" id="snap_my_roll-size" />&nbsp;Medium [400x300 pixels]</p>';
		echo '<p>Limit to one category of links<br>';
		echo '<select name="snap_my_roll-category">';
		echo '<option value ="0">None</option>';
		foreach($cats as $cat){
			if($options['category'] == $cat->term_id){
				$selected = "selected=SELECTED";
			}else{
				$selected = "";
			}
			echo '<option value ="'.$cat->term_id.'" '.$selected.'>'.$cat->name.'</option>';
		}
		echo '</select></p>';
		echo '<p>Diplay snapshot for <input type="text" name="snap_my_roll-timeout" value="'.$options['timeout'].'" id="snap_my_roll-timeout" size="2"/> seconds</p>';
		echo '<input type="hidden" id="snap_my_roll-submit" name="snap_my_roll-submit" value="1" />';
        }

        register_sidebar_widget('Snap My Roll','widget_snap_my_roll');
        register_widget_control('Snap My Roll','widget_snap_my_roll_options', 200, 300);

    }

    //Hooks
	add_action('plugins_loaded', 'widget_snap_my_roll_init');
	add_action('get_header','add_javascript');

function snap_my_roll_deactivate(){
	global $wpdb;
	remove_action('plugins_loaded', 'widget_snap_my_roll_init');
	remove_action('get_header','add_javascript');
	$wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."options` WHERE `option_name` = 'snap_my_roll'"));
}

	register_deactivation_hook( __FILE__, 'snap_my_roll_deactivate' );
?>
