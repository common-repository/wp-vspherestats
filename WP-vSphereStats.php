| <?php
/*
Plugin Name: WP-vSphereStats
Plugin URI: http://nickapedia.com/WP-vSphereStats
Description: This plugin, when coupled with the vSphereStats Tool, will supply statistics from your vSphere environment on your Wordpress blog via a widgets.

Version: 1.61
Author: Nicholas Weaver
Author URI: http://nickapedia.com
License: GPL2
*/
/*  Copyright 2010  Nicholas Weaver  (email : nick@nicholasweaver.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

/*
Some minor additions and alterations by Fr3d - 13th July 2010
Contact: http://www.fr3d.org/contact/

Info:
- Improved "last updated" text
- Option to display power states in an un-ordered HTML list, with or without images

- Added a post content filter to allow you to use various "variables" in posts/pages:

--- [vsphere-cpu], [vsphere-ram], [vsphere-storage], [vsphere-vms], [vsphere-states],

--- [vsphere-states-list], [vsphere-states-list-noimg], [vsphere-updated]
*/




register_activation_hook( __FILE__, 'vspherestat_activate' );
add_action('admin_menu', 'vspherestats_plugin_menu');
add_action('init', 'vspherestats_init');

/* Add our function to the widgets_init hook. */
add_action( 'widgets_init', 'vspherestats_load_widgets' );

/* Function that registers our widget. */
function vspherestats_load_widgets() {
    register_widget( 'vSphereStats_Widget' );
}

/* Register filters for use within posts/pages */
add_filter("the_content","vspherestatsContentVariableParser");

class vSphereStats_Widget extends WP_Widget {

    /**
     * Widget setup.
     */
    function vSphereStats_Widget() {
        /* Widget settings. */
        $widget_ops = array( 'classname' => 'vspherestats', 'description' => __('This widget displays stats from the vSphere Stats Windows Service.', 'vspherestats') );


        /* Widget control settings. */
        $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'vspherestats-widget' );


        /* Create the widget. */
        $this->WP_Widget( 'vspherestats-widget', __('vSphere Stats', 'vspherestats'), $widget_ops, $control_ops );

    }

    /**
     * How to display the widget on the screen.
     */
    function widget( $args, $instance ) {
        extract( $args );

        /* Our variables from the widget settings. */
        $title = apply_filters('widget_title', $instance['title'] );
        $show_clustersize = isset( $instance['show_clustersize'] ) ? $instance['show_clustersize'] : false;

                $show_clustercount = isset( $instance['show_clustercount'] ) ? $instance['show_clustercount'] : false;

                $show_vmstat = isset( $instance['show_vmstat'] ) ? $instance['show_vmstat'] : false;

                $show_update = isset( $instance['$show_update'] ) ? $instance['$show_update'] : false;


        echo $before_widget;

                if ( $title )
            echo $before_title . $title . $after_title;
                
        if ( $show_clustersize )
            echo "<div style='text-align: left; padding-left: 10px;'>
                            <p>
                            <strong>CPU:</strong>  ".number_format(get_option( "vsph_totalcpu" ), 0, '.', ',')." MHz <br/>

                            <strong>RAM:</strong>  ".number_format(get_option( "vsph_totalram" ), 0, '.', ',')." MB <br/>

                            <strong>Storage:</strong>  ".number_format(get_option( "vsph_totalds" ), 0, '.', ',')." GB <br/>

                            </p></div><div class='hr'><hr /></div>
                            ";

                if ( $show_clustercount )
            echo "<div style='text-align: left; padding-left: 10px;'>
                            <p>
                            <strong>ESX Hosts:</strong>  ".number_format(get_option( "vsph_totalhost" ), 0, '.', ',')."<br/>

                            <strong>Resource Pools:</strong>  ".number_format(get_option( "vsph_totalrp" ), 0, '.', ',')."<br/>

                            <strong>Virtual Machines:</strong>  ".number_format(get_option( "vsph_totalvm" ), 0, '.', ',')."<br/>

                            </p></div><div class='hr'><hr /></div>
                            ";

                if ( $show_vmstat )
            echo "<div style='text-align: left; padding-left: 10px;'>
                            <p>
                            <strong>VMotions:</strong>  ".get_option( "vsph_totalvmotion" )."<br/>

                            <strong>Power States:</strong><br/>".vspherestatsPowerState(get_option( "vsph_totalpwrstate" ))."<br/>

                            </p></div><div class='hr'><hr /></div>
                            ";
                if ( $show_update )
                            echo "<div style='text-align: center;'>
                            <p><br>
                            <strong>last update</strong><br>".vspherestatsGetLastUpdate()."

                            </p></div>
                            ";
                echo "<div style='text-align: center;font-size: 6px;'>
                            <p>
                            plugin by <a href='http://nickapedia.com/wp-vspherestats'>nickapedia</a>

                            </p>
                            </div>
                            ";
        /* After widget (defined by themes). */
        echo $after_widget;
    }

    /**
     * Update the widget settings.
     */
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        /* Strip tags for title and name to remove HTML (important for text inputs). */

        $instance['title'] = strip_tags( $new_instance['title'] );
        $instance['show_clustersize'] = $new_instance['show_clustersize'];
                $instance['show_clustercount'] = $new_instance['show_clustercount'];

                $instance['show_vmstat'] = $new_instance['show_vmstat'];
                $instance['$show_update'] = $new_instance['$show_update'];
        return $instance;
    }

    /**
     * Displays the widget settings controls on the widget panel.
     * Make use of the get_field_id() and get_field_name() function
     * when creating your form elements. This handles the confusing stuff.
     */
    function form( $instance ) {

        /* Set up some default widget settings. */
        $defaults = array( 'title' => __('My vSphere Environment', 'vspherestats'), 'show_clustersize' => true, 'show_clustercount' => true, 'show_vmstat' => true );

        $instance = wp_parse_args( (array) $instance, $defaults ); ?>

        <!-- Widget Title: Text Input -->
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>

            <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />

        </p>

        <!-- Show Size Checkbox -->
        <p>
                <input class="checkbox" type="checkbox" <?php checked( $instance['show_clustersize'], 'on' ); ?> id="<?php echo $this->get_field_id( 'show_clustersize' ); ?>" name="<?php echo $this->get_field_name( 'show_clustersize' ); ?>" />

            <label for="<?php echo $this->get_field_id( 'show_clustersize' ); ?>"><?php _e('Display cluster size stats', 'vspherestats'); ?></label>

        </p>

                <!-- Show Count Checkbox -->
        <p>
                <input class="checkbox" type="checkbox" <?php checked( $instance['show_clustercount'], 'on' ); ?> id="<?php echo $this->get_field_id( 'show_clustercount' ); ?>" name="<?php echo $this->get_field_name( 'show_clustercount' ); ?>" />

            <label for="<?php echo $this->get_field_id( 'show_clustercount' ); ?>"><?php _e('Display cluster counter stats', 'vspherestats'); ?></label>

        </p>

                
                <!-- Show VMStat Checkbox -->
        <p>
                <input class="checkbox" type="checkbox" <?php checked( $instance['show_vmstat'], 'on' ); ?> id="<?php echo $this->get_field_id( 'show_vmstat' ); ?>" name="<?php echo $this->get_field_name( 'show_vmstat' ); ?>" />

            <label for="<?php echo $this->get_field_id( 'show_vmstat' ); ?>"><?php _e('Display VM Stats', 'vspherestats'); ?></label>

        </p>

                <!-- Show Last Update -->
        <p>
                <input class="checkbox" type="checkbox" <?php checked( $instance['$show_update'], 'on' ); ?> id="<?php echo $this->get_field_id( '$show_update' ); ?>" name="<?php echo $this->get_field_name( '$show_update' ); ?>" />

            <label for="<?php echo $this->get_field_id( '$show_update' ); ?>"><?php _e('Display time since last update', 'vspherestats'); ?></label>

        </p>


    <?php
    }
}


//////////////////////// Playing with widgets /////////////////////////

function vspherestats_init() {
  if( isset($_REQUEST[ 'vsphcall' ]) ) {
      vspherestats_update();
    }
}

function vspherestats_update() {
    $currentkey = get_option( "vsph_key" );
    if ($_REQUEST['vsph_key'] == $currentkey) {
        update_option( "vsph_totalcpu", $_REQUEST['vsph_totalcpu'] );
        update_option( "vsph_totalram", $_REQUEST['vsph_totalram'] );
        update_option( "vsph_totalds", $_REQUEST['vsph_totalds'] );
        update_option( "vsph_totalnic", $_REQUEST['vsph_totalnic'] );
        update_option( "vsph_totalhost", $_REQUEST['vsph_totalhost'] );
        update_option( "vsph_totalrp", $_REQUEST['vsph_totalrp'] );
        update_option( "vsph_totalvm", $_REQUEST['vsph_totalvm'] );
        update_option( "vsph_totalvmotion", $_REQUEST['vsph_totalvmotion'] );

        update_option( "vsph_totalsnap", $_REQUEST['vsph_totalsnap'] );
        update_option( "vsph_totalpwrstate", $_REQUEST['vsph_totalpwrstate'] );


        update_option( "vsph_callflag", "0" );
        update_option( "vsph_lastupdate", time() );
    } else {
        update_option( "vsph_callflag", "1" );
    }
}


function vspherestats_plugin_menu() {
  add_options_page('WP-vSphere Stats Plugin Options', 'vSphere Stats', 'manage_options', 'wp-vspherestats', 'vspherestats_plugin_options');

}

function vspherestat_activate() {

  // Check to make sure Key is set
  $opt_name = 'vsph_key';
  $opt_val = get_option( $opt_name );
  if ($opt_val == "") {
      $opt_val = vspherestatsGenerateKey();
      update_option( $opt_name, $opt_val );
  }
}


function vspherestats_plugin_options() {
  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );

  }
    $opt_name = 'vsph_key';
    $hidden_field_name = 'vsph_submit_hidden';
    $data_field_name = 'vsph_key';
    $opt_val = get_option( $opt_name );
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {

        $opt_val = vspherestatsGenerateKey();
        update_option( $opt_name, $opt_val );
?>
<div class="updated"><p><strong><?php _e('<p>Key updated. You must update the WP-vSphereStats Windows Service Key using the configuration tool.</p><p><em>Stats will not be updated unless the key matches.</em></p>', 'wp-vspherestats' ); ?></strong></p></div>

<?php

    }

    // Now display the settings editing screen

    echo '<div class="wrap">';

    // header

    echo "<h2>" . __( 'vSphere Stats Settings', 'wp-vspherestats' ) . "</h2>";


    // settings form
    echo "<p><h4>This plugin allows you to update stats on your blog from your vSphere Environment. You will need the <em><strong>key</strong></em> from this page to use

              with the vSphereStats update tool. For help please go to <a href='http://nickapedia.com/wp-vspherestats'>Nickapedia.com</a></p>

         <p>Use the widgets page to add the vSphereStats widget(s) and display the stats below on your blog.</h4></p>

             ";

    ?>

<form name="form1" method="post" action="">
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

<p><?php _e("Current Key:", 'wp-vspherestatst' ); ?>
<input type="text" readonly="readonly" name="<?php echo $data_field_name; ?>" value="<?php echo $opt_val; ?>" size="40">

</p><hr />

<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Generate New Key') ?>" />

</p>

</form>

<?php

    $callflag = get_option( "vsph_callflag" );
    if ($callflag == "1") {
        echo "<div style='color: red;' class='updated'><p><strong>";
        echo "The last update by a vSphere Stats Windows Service failed due to invalid key.";

        echo "</strong></p></div>";
    } else {
        echo "<div style='text-align: left;'>
            <p>
            <strong>Current vSphere Stats:<br/>
            </p></div><div class='hr'><hr /></div>
            ";
        echo "<div style='text-align: left; padding-left: 10%;'>
            <p>
            <strong>CPU:</strong>  ".number_format(get_option( "vsph_totalcpu" ), 0, '.', ',')." MHz <br/>

            <strong>RAM:</strong>  ".number_format(get_option( "vsph_totalram" ), 0, '.', ',')." MB <br/>

            <strong>Storage:</strong>  ".number_format(get_option( "vsph_totalds" ), 0, '.', ',')." GB <br/>

            </p></div><div class='hr'><hr /></div>
            ";

        echo "<div style='text-align: left; padding-left: 10%;'>
            <p>
            <strong>ESX Hosts:</strong>  ".number_format(get_option( "vsph_totalhost" ), 0, '.', ',')."<br/>

            <strong>Resource Pools:</strong>  ".number_format(get_option( "vsph_totalrp" ), 0, '.', ',')."<br/>

            <strong>Virtual Machines:</strong>  ".number_format(get_option( "vsph_totalvm" ), 0, '.', ',')."<br/>

            </p></div><div class='hr'><hr /></div>
            ";

        echo "<div style='text-align: left; padding-left: 10%;'>
            <p>
            <strong>VMotions:</strong>  ".number_format(get_option( "vsph_totalvmotion" ), 0, '.', ',')."<br/>

            <strong>Power States:</strong><br/>".(vspherestatsPowerState(get_option( "vsph_totalpwrstate" )))."<br/>

            </p></div><div class='hr'><hr /></div>
            <p><br>
            <strong>last update</strong><br>".vspherestatsGetLastUpdate()."
            </p></div>
            ";
        echo "<div style='text-align: center;font-size: 6px;'>
            <p>
            plugin by <a href='http://nickapedia.com/wp-vspherestats'>nickapedia</a>

            </p>
            </div>
            ";
    }
?>

</div>

<?php

}

function vspherestatsGenerateKey()
{
    $length = 32;
    $charset = "abcdefghijklmnopqrstuvwxyz";
    $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $charset .= "0123456789";
    for ($i=0; $i<$length; $i++) $key .= $charset[(mt_rand(0,(strlen($charset)-1)))];

    return $key;
}

function vspherestatsGetLastUpdate()
{
    $date2 = get_option( "vsph_lastupdate" );
    if ($date2 == "") {
        $lastupdatetext = "infinity";
    } else {
    $date1 = time();
    $dateDiff = $date1 - $date2;
    $fullDays = floor($dateDiff/(60*60*24));
    $fullHours = floor(($dateDiff-($fullDays*60*60*24))/(60*60));
    $fullMinutes = floor(($dateDiff-($fullDays*60*60*24)-($fullHours*60*60))/60);

    $fullDays > 0 ? $lastupdatetext = "$fullDays days, " : "";
    $fullHours > 0 ? $lastupdatetext .= "$fullHours hours and " : "";
    $lastupdatetext .= "$fullMinutes minutes";
    }
    return $lastupdatetext;
}
//number_format(get_option( "vsph_totalvm" ), 0, '.', ',')
function vspherestatsPowerState($powerstate, $asList = false, $img = false)
{
    $baseurl=get_option( 'siteurl' );
    $powerstate = explode(".",$powerstate);
    if ($asList) {
        //$text = "<li".($img==true?" style=\"list-style-image: url(".$baseurl."/wp-content/plugins/WP-vSphereStats/on.png); list-style-type:none;\"":"").">".$powerstate[0]."</li>\n";

    //above code needs more work - list-style-type doesn't work
    $text = "<li>".($img==true?"<img src='".$baseurl."/wp-content/plugins/WP-vSphereStats/on.png' alt='".number_format($powerstate[0], 0, '.', ',')." VMs on' /> ":"").$powerstate[0]."</li>\n";

    $text .= "<li>".($img==true?"<img src='".$baseurl."/wp-content/plugins/WP-vSphereStats/off.png' alt='".number_format($powerstate[1], 0, '.', ',')." VMs off' /> ":"").$powerstate[1]."</li>\n";

    $text .= "<li>".($img==true?"<img src='".$baseurl."/wp-content/plugins/WP-vSphereStats/suspend.png' alt='".number_format($powerstate[2], 0, '.', ',')." VMs suspended' /> ":"").$powerstate[2]."</li>\n";

    }
    else {
        $text = "<img src='".$baseurl."/wp-content/plugins/WP-vSphereStats/on.png' alt='".number_format($powerstate[0], 0, '.', ',')." VMs on' />(".number_format($powerstate[0], 0, '.', ',').")"."&nbsp;<img src='".$baseurl."/wp-content/plugins/WP-vSphereStats/off.png' alt='".number_format($powerstate[0], 0, '.', ',')." VMs off' />(".number_format($powerstate[1], 0, '.', ',').")"."&nbsp;<img src='".$baseurl."/wp-content/plugins/WP-vSphereStats/suspend.png' alt='".number_format($powerstate[0], 0, '.', ',')." VMs suspended' />(".number_format($powerstate[2], 0, '.', ',').")";

    }
    return $text;
}

function vspherestatsContentVariableParser($content)
{
    $search = array(    "[vsphere-cpu]",
                "[vsphere-ram]",
                "[vsphere-storage]",
                "[vsphere-vms]",
                "[vsphere-states]",
                "[vsphere-states-list]",
                "[vsphere-states-list-noimg]",
                "[vsphere-updated]"
            );
    
    $replace = array(    number_format(get_option("vsph_totalcpu"), 0, '.', ','),

                number_format(get_option("vsph_totalram"), 0, '.', ','),
                number_format(get_option("vsph_totalds"), 0, '.', ','),
                get_option("vsph_totalvm"),
                vspherestatsPowerState(get_option("vsph_totalpwrstate")),
                vspherestatsPowerState(get_option("vsph_totalpwrstate"),true,true),

                vspherestatsPowerState(get_option("vsph_totalpwrstate"),true,false),

                vspherestatsGetLastUpdate(),
            );
    
    $content = str_ireplace($search,$replace,$content);
    return $content;
}
?> |
