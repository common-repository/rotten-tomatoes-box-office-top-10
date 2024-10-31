<?php
/*
 Plugin Name: Rotten Tomatoes Box Office Top 10
 Plugin URI: http://www.rottentomatoes.com/
 Description: Add official Tomatometer and Flixster scores to your blog with the Box Office Top 10 widget by Flixster. Follow the links to get official movie information from Rotten Tomatoes -- the most beloved source for info and critic reviews
 Version: 1.0.0
 Author: Flixster, Inc.
 Author URI: http://www.flixster.com
 */
define(ENABLE_CACHE, true);
require_once 'lib/JSON.php';
require_once( ABSPATH . WPINC . '/class-snoopy.php' );

class RT_Widget extends WP_Widget {

	function RT_Widget() {
        parent::WP_Widget(false, $name = 'RT Box Office');	
    }

    function widget($args, $instance) {
    	extract( $args );	
    	$cache_key = "rt-boxoffice1";	
        
        $img_root = get_bloginfo(wpurl).'/wp-content/plugins/rotten-tomatoes-box-office-top-10/images/';
    	$boxoffice = get_transient($cache_key);
    	if($boxoffice == false) {
			$decoder = new Services_JSON();
 	        	$snoopy = new Snoopy();
			if(!$snoopy->fetch("http://api.flixster.com/wordpress/api/v1/movies.json?filter=box-office&view=long&country=" . strtolower($instance['country']))) {
				die ("Could not connect to the server.");
			}
			
			$json = $snoopy->results;
			$boxoffice = $decoder->decode($json);
			set_transient($cache_key, $boxoffice, 60 * 60 * 24);
		}

       	echo $before_widget;
		//echo $before_title . 'The Big Picture' . $after_title;
		echo "<img src='" . $img_root . "bigpicture.jpg' style='margin-top:5px;width:100%;' />"; 
		
		echo '<table id="wp-rt"><tr><th></th>';
		if($instance['show_rt']) { echo "<th>Critics</th>"; }
		if($instance['show_fx']) { echo "<th>Audience</th>"; }
		echo "</tr>";		

		foreach($boxoffice as &$movie) {
			if( $movie->reviews->rottenTomatoes->rating >= 60) {
				$class = "fresh";
			} else {
				$class = "rotten";
			}
			
			$rturl = $movie->url;
			foreach($movie->urls as &$url) {
				if($url->type == "rottentomatoes") {
					$rturl = $url->url; break;
				}				
			}
			
			$rturl = str_replace("?lsrc=mobile", "", $rturl);
			
			echo "<tr>";
			echo "<td class='title'><a href='" . $rturl ."'>" . $movie->title . "</a></td>";
			if($instance['show_rt']) {
				if($movie->reviews->rottenTomatoes->rating) {
					echo "<td class='rt-" . $class . "-score'>" . $movie->reviews->rottenTomatoes->rating . "%</a></td>";
				} else {
					echo "<td></td>";
				}
			}
			if($instance['show_fx']) {
				if($movie->reviews->flixster->popcornScore) {
					echo "<td class='flix-score'>" . $movie->reviews->flixster->popcornScore . "%</a></td>";
				} else {
					echo "<td></td>";
				}
			}
			echo "</tr>";
		}
		echo "</table>";
		echo "<div style='width:45%;float:left;'><div style='text-align:center;'>Critic ratings from</div> <a target='_blank' href='http://www.rottentomatoes.com'><img style='padding: 5px 10px;width:80%;border-style: none' src='" . $img_root . "logo.png' /></a></div>";
		echo "<div style='width:45%;float:right;'><div style='text-align:center;'>Audience ratings from</div> <a target='_blank' href='http://www.flixster.com'><img style='padding: 5px 10px;width:80%;border-style: none' src='" . $img_root . "flixster-logo.png' /></a></div>"; 
		?>	<style type="text/css">
				#wp-rt { margin-top:5px;  margin-bottom:10px;}
				#wp-rt td { vertical-align: top; }
				#wp-rt tr { height: 20px; }
				#wp-rt .rt-fresh-score {  background: url(<?php echo $img_root ?>fresh.png) no-repeat 0 0px; padding-left:17px; }
				#wp-rt .rt-rotten-score { background: url(<?php echo $img_root ?>rotten.png) no-repeat 0 0px; padding-left:17px; }
				#wp-rt .flix-score {  background: url(<?php echo $img_root ?>popcorn.png) no-repeat 0 0px; padding-left:17px; }
			</style> <?php  
		echo $after_widget; 
    }

    function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		$instance['show_fx'] = $new_instance['show_fx'];
		$instance['show_rt'] = $new_instance['show_rt'];
		$instance['country'] = $new_instance['country'];
        return $instance;
    }

    function form($instance) {				
        $title = esc_attr($instance['title']);
        $instance = wp_parse_args( (array) $instance, array('country'=>'US', 'show_rt'=> true, 'show_fx'=> true ));
        
        ?>	
        	<p>
	            <label for="<?php echo $this->get_field_id( 'country' ); ?>"><?php _e('Country:'); ?></label> 
				<select id="<?php echo $this->get_field_id( 'country' ); ?>" name="<?php echo $this->get_field_name( 'country' ); ?>" class="widefat" style="width:100%;">
					<option <?php if ( 'us' == $instance['country'] ) echo 'selected="selected"'; ?> value='us'>United States</option>
					<option <?php if ( 'uk' == $instance['country'] ) echo 'selected="selected"'; ?> value='uk'>United Kingdom</option>
					<option <?php if ( 'au' == $instance['country'] ) echo 'selected="selected"'; ?> value='au'>Australia</option>
				</select>
			</p>
			<p>
				<input type="checkbox" <?php if ( $instance['show_rt'] ) echo 'checked'; ?> id="<?php echo $this->get_field_id( 'show_rt' ); ?>" name="<?php echo $this->get_field_name( 'show_rt' ); ?>" /> 
				<label for="<?php echo $this->get_field_id( 'show_rt' ); ?>"><?php _e('Show critic scores?'); ?></label>
				<br />
				<input type="checkbox" <?php if ( $instance['show_fx'] ) echo 'checked'; ?> id="<?php echo $this->get_field_id( 'show_fx' ); ?>" name="<?php echo $this->get_field_name( 'show_fx' ); ?>" /> 
				<label for="<?php echo $this->get_field_id( 'show_fx' ); ?>"><?php _e('Show user scores?'); ?></label>
			</p>
				
        <?php 
    }
} 
add_action('widgets_init', create_function('', 'return register_widget("RT_Widget");'));
?>
