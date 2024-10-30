<?php
/*  Copyright 2012 Raphael Reitzig (wordpress@verrech.net)

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
?>
<?php

if ( !class_exists('LmazyPlugin') ) {

/**
 * Abstract implementation of a Wordpress plugin that factors some standard
 * tasks out. These are the things this class does:
 * - Stores plugin name, pretty name and location centrally
 * - Registers (de)activation hooks and binds them to (overwritable) member methods
 * If the subclassing plugin has options, it additionally:
 * - Registers an option under the plugin's name
 * - Provides a default settings page implementation
 * - Links to the settings page from both menu and plugin list
 *
 * Use LmazyPlugin by extending it and overwriting methods as appropriate. Take care
 * to call its constructor and method implementations via parent::name(...).
 *
 * @author Raphael Reitzig
 * @version 1.0
 */
abstract class LmazyPlugin {
  /**
   * This plugin's name as used internally.
   */
  protected $name;

  /**
   * This plugin's name as shown to the user.
   */
  protected $prettyName;

  /**
   * Corresponds to __FILE__ called from the main plugin file.
   */
  protected $mainFile;

  /**
   * Additional resources linked to on the default settings page.
   * An array of pairs [label, URL].
   */
  protected $resources;

  private $hasOptions = true;

  /**
   * This plugin's current options (as per the start of the current Wordpress
   * call).
   */
  protected $options;

  /**
   * Creates a new instance.
   * Has to be called from extending classes.
   *
   * @param props can contain
   *              - 'name'       -- This plugin's name as used internally
   *              - 'prettyName' -- This plugin's name as shown to the user
   *              - 'mainFile'   -- Result of __FILE__ called from the
   *                                main plugin file.
   *              - 'resources'  -- An array of pairs [label, URL] for the
   *                                default options page.
   *
   * @param hasOptions settings are set up if and only if this is true
   *                   (default: true).
   */
  function __construct($props, $hasOptions = true) {
    $this->name = $props['name'];
    $this->prettyName = $props['prettyName'];
    $this->mainFile = $props['mainFile'];
    $this->resources = $props['resources'];
    $this->hasOptions = $hasOptions;

    if ( $hasOptions ) {
      if ( is_admin() ) {
        // Register Options Page
        add_action('admin_menu', array(&$this, 'admin_menu_init'));
        // Add link to options page to plugin list
        add_filter('plugin_action_links', array(&$this, 'plugin_list_link'), 10, 2);
        // Register settings
        add_action('admin_init', array(&$this, 'setup_settings'));
      }

      $this->options = get_option($this->name);
    }

    register_activation_hook($this->mainFile, array(&$this, 'activate'));
    register_deactivation_hook($this->mainFile, array(&$this, 'deactivate'));
    // TODO implement upgrade for options --> version?
  }

  /**
   * Bound to hook 'admin_menu'. Adds a link to this plugin's options page to
   * the settings menu.
   */
  function admin_menu_init() {
    add_options_page($this->prettyName.' -- Settings', $this->prettyName, 'manage_options', basename($this->mainFile), array(&$this, 'options_page'));
  }

  /**
   * Bound to hook 'plugin_action_links'. Adds a link to this plugin's options page to
   * its entry in the plugins list.
   */
  function plugin_list_link( $links, $file ) {
    static $this_plugin;
    if( ! $this_plugin ) $this_plugin = plugin_basename($this->mainFile);

    if ( $file == $this_plugin ) {
      $settings_link = '<a href="options-general.php?page='.basename($this->mainFile).'">Settings</a>';
      array_unshift( $links, $settings_link );
    }
    return $links;
  }

  /**
   * Used by the backend to sanitise this plugin's settings.
   * @param input array of wannabe options (key/value pairs)
   * @return an array of key/value pairs containing the new options
   */
  function options_validate($input) {
    return shortcode_atts($this->options, $input);
  }

  /**
   * Used to create the options page form by the default implementation
   * of options_page. Should register settings via Settings API.
   * Default registers a setting with plugin name and options_validate as
   * validation function.
   */
  function setup_settings() {
    register_setting($this->name, $this->name, array(&$this, 'options_validate'));
  }

  /**
   * Prints this plugin's options page.
   * The default implementation uses the settings API to provide a simple
   * settings page as well as a floating box listing the additional resources
   * specified in the constructor as links.
   */
  function options_page() { ?>
    <div class="wrap">
      <h2><?php echo $this->prettyName; ?> &ndash; Settings</h2>

      <?php if ( !empty($this->resources) ) { ?>
      <div style="float:right; position:fixed; right:10px; width:120px; border:1px solid grey; padding:5px; margin:10px; border-radius:4px; -moz-border-radius:4px;-webkit-radius:4px; background-color: rgb(255, 255, 224);">
        <h3>Resources</h3>
        <ul>
        <?php foreach ( $this->resources as $r ) { if ( !empty($r[0]) && !empty($r[1]) ) { ?>
          <li><a href="<?php echo $r[1]; ?>" target="_blank"><?php echo $r[0]; ?></a></li>
        <?php } } ?>
        </ul>
      </div>
      <?php } ?>

      <form action="options.php" method="post">
        <?php submit_button(); ?>
        <?php settings_fields($this->name); ?>
        <?php do_settings_sections($this->name); ?>
        <?php submit_button(); ?>
      </form>
    </div> <?php
  }

  /**
   * Bound to this plugin's activation hook.
   */
  abstract function activate();

  /**
   * Bound to this plugin's deactivation hook.
   */
  abstract function deactivate();
}

}
?>
