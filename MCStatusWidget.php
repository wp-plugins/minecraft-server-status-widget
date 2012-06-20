<?php
/*
Plugin Name: MCStatusWidget
Plugin URI: Waiting for URI
Description: Display Minecraft Server Status of all types.
Version: 1.1
Author: WhiteSK
Author URI: Waiting for URI
License: GPL3
*/

/*
 * MCStatusWidget
 * Copyright (C) 2012 WhiteSK.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!class_exists('MinecraftQuery')) {
	require('statusclass.php');
}

defined('ABSPATH') or die("Cannot access pages directly.");

add_action( 'widgets_init', create_function( '', 'register_widget("MCStatusWidget");' ) );

class MCStatusWidget extends WP_Widget
{
	function MCStatusWidget() {
		$widget_ops = array( 
			'classname' => 'MCStatusWidget', 
			'description' => 'Minecraft server status...' 
		);
		$this->WP_Widget( 'MCStatusWidget', 'MCStatusWidget', $widget_ops );
	}
	
	function widget($args, $instance) {
		extract( $args ); 
		$title = apply_filters('widget_title', $instance['title'] );
		$mq_ip = $instance['mq_ip'];
		$mq_port = $instance['mq_port'];
    echo "
    <script type='text/javascript'>
function togluj(id) {
$('#' + id).toggle('slow');
} 

    </script>";
		echo $before_widget;
    $mc = new MinecraftQuery();
    $mc->connect($mq_ip,$mq_port);
    $info = $mc->GetInfo();
    $players = $mc->GetPlayers();
	  if(empty($title) || $info) echo $before_title . $info['HostName'] . $after_title;
    else echo $before_title . $title . $after_title;
        echo "<table>";
		if (!$info) {
			echo"<tr><td>Status: &nbsp;</td><td><b><font style='color:red;'>Offline</font></b></td></tr>";
      echo"<tr><td>IP Port: &nbsp;</td><td><b>{$mq_ip}:{$mq_port}</b></td></tr>";
		} else {
			echo"<tr><td>Status: &nbsp;</td><td><b><font style='color:green;'>Online</font></b></td></tr>";
      echo"<tr><td>IP Port: &nbsp;</td><td><b>{$mq_ip}:{$mq_port}</b></td></tr>";
      echo "<tr><td>Verze: &nbsp;</td><td><b>{$info['Version']}</b></td></tr>";
      echo "<tr><td>Odozva: &nbsp;</td><td><b>{$info['Latency']} ms</b></td></tr>";
      echo "<tr><td>Mapa: &nbsp;</td><td><b>{$info['Map']}</b></td></tr>";
			echo("<tr><td>Hráči: &nbsp;</td><td><b>{$info['Players']} / {$info['MaxPlayers']}</b></td></tr>"); 
		}  
    echo "</table>";
    if($info) {
    ?>
    <a href='javascript:void(0)' onClick='togluj("<?=$mq_port?>")'>Hráči Online / Pluginy</a>
    <?php 
    echo "<div id='{$mq_port}' style='display: none; font-weight: bold;'>";
    if($info['Players'] == 0) echo "Žádny hráči na serveru..<br>";
    else {
    foreach($players as $player):
    echo "- $player <br>";
    endforeach;
    }
    echo "<br> Pluginy: <br><i>";
    foreach($info['Plugins'] as $plugin) {
    echo "$plugin, ";
    }
    echo "</i>";
    echo "</div>";
    }
		echo $after_widget;
	}
	

	function form( $instance ) {
		$defaults = array( 'title' => 'Survival', 'mq_ip' => 'mine-crafters.eu', 'mq_port' => '25566', 'mq_portas' => '25565');
		$instance = wp_parse_args( (array) $instance, $defaults ); 
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
	
		<p>
			<label for="<?php echo $this->get_field_id( 'mq_ip' ); ?>">Server IP</label>
			<input id="<?php echo $this->get_field_id( 'mq_ip' ); ?>" name="<?php echo $this->get_field_name( 'mq_ip' ); ?>" value="<?php echo $instance['mq_ip']; ?>" style="width:100%;" />
		</p>
    		<p>
			<label for="<?php echo $this->get_field_id( 'mq_port' ); ?>">Port Serveru</label>
			<input id="<?php echo $this->get_field_id( 'mq_port' ); ?>" name="<?php echo $this->get_field_name( 'mq_port' ); ?>" value="<?php echo $instance['mq_port']; ?>" style="width:100%;" />
		</p>
		<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['mq_ip'] = strip_tags( $new_instance['mq_ip'] );
		$instance['mq_port'] = strip_tags( $new_instance['mq_port'] );
		return $instance;
	}
}