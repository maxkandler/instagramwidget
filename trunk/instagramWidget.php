<?php

/*
  Plugin Name: InstagramWidget
  Description: Shows the last X pictures from your instagram-feed
  Author: Max Kleucker
  Version: 0.1
  Author URI: http://www.justcurious.de
*/
/*
  Instagram Widget: Holt die letzten Bilder von Instagram und zeigt sie in einem Widgte an.
  Dazu eine einfache Verlinkung, aber keine weiterer SchnickSchnack.
  Cachet die unter uploads/instagram und velrinkt zu den Detailaufnahmen.
*/


class InstagramWidget extends WP_Widget {

  
  /**
   * WP-Widget Constructor
   */
	function InstagramWidget() {
	
		parent::WP_Widget(false, $name = 'InstagramWidget');
		
	}
	

  /**
   * The loop for displaying all the images.
   */
	function widget($args, $instance) 
	{
	
	  // Loading the JSON Data
    $json = json_decode($this->getData($instance['access_token'], $instance['count_images'], $instance['cache_time']), true);
    
    // Showing sourrounding div
		echo '<div>';
		
		// The loop
		foreach ($json["data"] as $value) 
		{ 
      echo $this->echoimage($value, $instance['image_size']); 
    }
    
		echo '</div>';
	}


  /**
   * Updating the InstagramWidget Instance and resetting the Cache
   */
	function update($new_instance, $old_instance) {
		// Speichern der Optionen
		$instance = $old_instance;
		$instance['access_token'] = $new_instance['access_token'];
		$instance['count_images'] = $new_instance['count_images'];
		$instance['cache_time'] = $new_instance['cache_time'];
		$instance['image_size'] = $new_instance['image_size'];
		
		$this->resetCache();
		
		return $new_instance;
	}

	
	
	/**
	 Gets the Data from either the Cache or the API
	*/
	function getData($token, $number, $cache_time){
    
    $wp_uploads = wp_upload_dir();
    
    $cache_folder = $wp_uploads['basedir']. "/instagram/";
    
    if(!empty($wp_uploads['error'])) echo $wp_uploads['error'];
    
    if(!is_dir($cache_folder)){
      if(mkdir($cache_folder, 0777) == false){
        return $this->getDataFromApi($token, $number);
      }
    }
    
    $cachefile = $cache_folder."user.cache";
    
    if (file_exists($cachefile) && time()-filemtime($cachefile)<$cache_time) {
      try{
        $contents = file_get_contents($cachefile);
      }catch(Exception $e){
        $contents = $this->getDataFromApi($token, $number);
        file_put_contents($cachefile, $contents);
      }
      
    } else {
      $contents = $this->getDataFromApi($token, $number);
      file_put_contents($cachefile, $contents);
    }
    
    return $contents;
	}
	
	
	/**
	 Getting the Data from the API
	*/
	function getDataFromApi($token, $number){
    // Instagram API bearbeiten
    $contents = file_get_contents("https://api.instagram.com/v1/users/self/media/recent/?access_token=$token&count=$number");
    return $contents;
	}
	
	/**
	 Displaying the single images.
	*/
	function echoimage($val, $imagesize) {
 
    $image = $val["images"][$imagesize]["url"];
    $link = $val["link"];
   
    return "<a href=\"$link\"><img src=\"$image\"/></a>";
   
  }
  
  /** Not yet functional */
  function resetCache(){
  
  }
  
  /**
   * Parsing the Form for the Admin-Area.
   */
	function form($instance) {
		// Formular des Widgets im Backend
		$defaults = array('numberphotos'=>'3');
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = "instagram stream";
?>
    <div>
    <label for="<?php echo $this->get_field_id('access_token'); ?>">Access Token:</label>
      <input type="text" name="<?php echo $this->get_field_name('access_token') ?>" id="<?php echo $this->get_field_id('access_token') ?> " value="<?php echo $instance['access_token'] ?>" size="20"/>
      <p style="font-size: 0.86em;">For getting an Access Token please <a href="http://justcurious.de/projects/instagramwidget/apiaccess.php" target="_blank">click here</a>.</p>
    </div>  
    <div>
    <label for="<?php echo $this->get_field_id('count_images'); ?>">Count Images:</label>
      <input type="text" name="<?php echo $this->get_field_name('count_images') ?>" id="<?php echo $this->get_field_id('count_images') ?> " value="<?php echo $instance['count_images'] ?>" size="3"/>
    </div> 
    <div> 
    <label for="<?php echo $this->get_field_id('cache_time'); ?>" title="How long are API results cached?">Cache:</label>
      <input type="text" name="<?php echo $this->get_field_name('cache_time') ?>" id="<?php echo $this->get_field_id('cache_time') ?> " value="<?php echo (empty($instance['cache_time']))? '600' : $instance['cache_time']; ?>" size="3"/> (ms)
    </div>
    <div>
    <label for="<?php echo $this->get_field_id('image_size'); ?>">Image Size:</label>
      <select name="<?php echo $this->get_field_name('image_size') ?>" id="<?php echo $this->get_field_id('image_size') ?> ">
        <option value="thumbnail" <?php if($instance['image_size'] == "thumbnail") echo 'selected="selected"'; ?>>Thumbnail (150x150)</option>
        <option value="low_resolution" <?php if($instance['image_size'] == "low_resolution") echo 'selected="selected"'; ?>>Low Res (306x306)</option>
        <option value="standard_resolution" <?php if($instance['image_size'] == "standard_resolution") echo 'selected="selected"'; ?>>Thumbnail (612x612)</option>
      </select>
      
    </div>
<?php
	}
  
  
}
add_action('widgets_init', create_function('', "register_widget('InstagramWidget');"));

?>