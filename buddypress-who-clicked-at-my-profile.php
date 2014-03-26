<?php
/**
 * Plugin Name: Buddypress - Who clicked at my Profile?
 * Plugin URI: http://ifs-net.de
 * Description: This Plugin provides at Widget that shows who visited your profile. This increases networking and communication at your community website!
 * Version: 1.7
 * Author: Florian Schiessl
 * Author URI: http://ifs-net.de
 * License: GPL2
 * Text Domain: buddypress-wcamp
 * Domain Path: /languages/
 */
// recreate pot file? excute this in the plugin's directory  
// xgettext --language=PHP --from-code=utf-8 --keyword=__ --keyword=_e *.php -o languages/buddypresswcamp.pot
// Load translations and text domain
add_action('init', 'buddypresswcamp_load_textdomain');

/**
 * This function just loads language files
 */
function buddypresswcamp_load_textdomain() {
    load_plugin_textdomain('buddypresswcamp', false, dirname(plugin_basename(__FILE__)) . "/languages/");
}

// Register Action for this PLugin
add_action('bp_before_member_header', 'buddypresswcamp_action');

/**
 * This function loggs visits at your profile page
 * @global type $bp
 * @global type $wpdb
 */
function buddypresswcamp_action() {
    global $bp;
    $displayed_user_id = $bp->displayed_user->id;
    $current_user = wp_get_current_user();
    $viewing_user_id = $current_user->ID;
    if (($displayed_user_id != $viewing_user_id) && ($viewing_user_id > 0)) {

        // get user meta data (clickedme_tracking is a serialized array containing the last visits)
        $meta = get_user_meta($displayed_user_id, 'clickedme_tracking', true);
        $trackingList = unserialize($meta);
        if (!is_array($trackingList)) {
            $trackingList = array();
        }
        // remove double clicks. latest click will be the interesting click for us
        if (in_array($viewing_user_id, $trackingList)) {
            $trackingList = array_flip($trackingList);
            unset($trackingList[$viewing_user_id]);
            $trackingList = array_flip($trackingList);
        }
        // latest visit will be first to display later
        $newTrackingList = array();
        $newTrackingList[] = $viewing_user_id;
        // we track ten visits maximum
        if (count($trackingList) > 10) {
            array_pop($trackingList);
        }
        foreach ($trackingList as $item) {
            $newTrackingList[] = $item;
        }

        $trackingList = $newTrackingList;
        // Store new user meta data
        update_user_meta($displayed_user_id, 'clickedme_tracking', serialize($trackingList), $meta);
    }
}

add_action('widgets_init', 'buddypresswcamp_widget_showMyVisitors');

function buddypresswcamp_widget_showMyVisitors() {
    register_widget('BuddypressWCAMP_Widget_showMyVisitors');
}

class BuddypressWCAMP_Widget_showMyVisitors extends WP_Widget {

    function BuddypressWCAMP_Widget_showMyVisitors() {
        $widget_ops = array('classname' => 'buddypresswcamp', 'description' => __('Show visitors of my buddypress profile page', 'buddypresswcamp'));
        $this->WP_Widget('buddypresswcamp-widget-showMyVisitors', __('Show bp profile visitors', 'buddypresswcamp'), $widget_ops);
    }

    function widget($args, $instance) {
        extract($args);

        global $bp;

        //Our variables from the widget settings.
        $title = apply_filters('widget_title', $instance['title']);
        $showAvatars = apply_filters('widget_avatar', $instance['showAvatars']);

        echo $before_widget;

        // Display the widget title 
        if ($title)
            echo $before_title . $title . $after_title;

        // Main content
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $meta = get_user_meta($current_user->ID, 'clickedme_tracking', true);
            $trackingList = unserialize($meta);
            if (empty($trackingList)) {
                $content.=__('Your profile has not been visited yet by another member of the community.', 'buddypresswcamp');
            } else {
                foreach ($trackingList as $item) {
                    $userdata = get_userdata($item);
                    $data = $userdata->data;
                    $current_user = wp_get_current_user();
                    if ($showAvatars == 1) {
                        $content.= '<a href="' . bp_core_get_userlink($data->ID, false, true) . '">' . bp_core_fetch_avatar(array('object' => 'user', 'item_id' => $data->ID));
                    } else {
                        $resultLinks[] = str_replace('href=', 'class="avatar" rel="user_' . $data->ID . '" href=', bp_core_get_userlink($data->ID));
                    }
                }
                if ($showAvatars == 0) {
                    $content.=__('Your profile has been visited by:', 'buddypresswcamp') . ' ' . implode(', ', $resultLinks);
                } else {
                    $content.='<br style="clear:both;">';
                }
            }
        } else {
            $content.=__('Please log in to view the visitors of your profile', 'buddypresswcamp');
        }
        echo '<p>' . $content . '</p>';

        echo $after_widget;
    }

    //Update the widget 

    function update($new_instance, $old_instance) {
        $instance = $old_instance;

        //Strip tags from title and name to remove HTML 
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['showAvatars'] = strip_tags($new_instance['showAvatars']);

        return $instance;
    }

    function form($instance) {

        //Set up some default widget settings.
        $defaults = array(
            'title' => __('Last visitors of your profile', 'buddypresswcamp'),
            'showAvatars' => 0
        );
        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'buddypresswcamp'); ?>:</label>
            <input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
        </p>
        <p>
            <input type="checkbox" id="<?php echo $this->get_field_id('showAvatars'); ?>" name="<?php echo $this->get_field_name('showAvatars'); ?>" value="1" <?php if ($instance['showAvatars'] == 1) echo 'checked="checked" ' ?> />
            <label for="<?php echo $this->get_field_id('showAvatars'); ?>"><?php _e('Show Avatars instead of links to last visitors profile pages', 'buddypresswcamp'); ?>:</label>
        </p>
        <?php
    }

}
?>