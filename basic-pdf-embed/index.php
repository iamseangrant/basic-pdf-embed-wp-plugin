<?php
/*
Plugin Name: Basic PDF Embed
Plugin URI: https://wordpress.org
Description: Basic PDF display using the <object> tag.
Author: Sean Grant
Author URI: http://iamseangrant.com
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Version: 0.1.0

  Copyright 2015 Sean Grant  (email : sean@iamseangrant.com)

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

/* Recommended by WordPress for Security @ http://codex.wordpress.org/Writing_a_Plugin */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( !class_exists( 'BasicPDFEmbed' ) ) {
  // define base class
  class BasicPDFEmbed {

    function __construct() {
      // Initialize Admin features of plugin
      add_action( 'admin_init', array( $this, "bpdfe_admin_init" ) );

      // Register the Embed Handler (checks for http://anythinghere.any/anyfilename.pdf and if found formats to use shortcode.)
      wp_embed_register_handler( 'autopdf', '/^http:\/\/.+\.pdf$/', array( $this, "bpdfe_embed_handler_pdf" ) , 1 );

      // Add shortcode support [pdfembed]
      add_shortcode( 'pdfembed', array( $this, "pdfembed_process_shortcode" ) );

      // Add menu item to admin menu
      add_action('admin_menu', array( $this, "bpdfe_add_custom_options" ) );

      // Create button on TinyMCE for easy usage (PDF icon)
      add_action( 'admin_init', array( $this, "bpdfe_add_button" ) );
      
    }

    function bpdfe_admin_init() {
      // Create Settings link on Plugins page
      add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, "bpdfe_action_links" ) );

      // Register Plugin Settings Options
      register_setting( 'bpdfe_options_group', 'bpdfe_options', array( $this, "bpdfe_options_validate" ) );

      // Create "Section" of Settings
      add_settings_section('bpdfe_main', 'Main Settings', array( $this, "bpdfe_section_text" ), 'pdf_embed_options');

      // Get options/settings array
      $settings = get_option('bpdfe_options');

      // Add fields to "Section" of Settings defined above
      // Add Width Field
      add_settings_field('bpdfe_text_width', 'Default Width:', array( $this, "bpdfe_text_input" ), 'pdf_embed_options', 'bpdfe_main', array(
        'name' => 'bpdfe_options[width]',
        'value' => $settings['width'],
        'helpText' => 'Default is 100% for responsive layouts.',
        ) 
      );

      // Add Height Field
      add_settings_field('bpdfe_text_height', 'Default Height:', array( $this, "bpdfe_text_input" ), 'pdf_embed_options', 'bpdfe_main', array(
        'name' => 'bpdfe_options[height]',
        'value' => $settings['height'],
        'helpText' => 'ie. 900px',
        ) 
      );

      // Add View Field
      add_settings_field('bpdfe_text_view', 'Default View:', array( $this, "bpdfe_text_input" ), 'pdf_embed_options', 'bpdfe_main', array(
        'name' => 'bpdfe_options[view]',
        'value' => $settings['view'],
        'helpText' => 'Default is "Fit".',
        ) 
      );

      // Add Scrollbars Field
      add_settings_field('bpdfe_text_scrollbar', 'Show Scrollbar?', array( $this, "bpdfe_checkbox_input" ), 'pdf_embed_options', 'bpdfe_main', array(
        'name' => 'bpdfe_options[scrollbar]',
        'value' => $settings['scrollbar'],
        'helpText' => 'Default is checked (do show).',
        ) 
      );

      // Add Toolbars Field
      add_settings_field('bpdfe_text_toolbar', 'Show Toolbar?', array( $this, "bpdfe_checkbox_input" ), 'pdf_embed_options', 'bpdfe_main', array(
        'name' => 'bpdfe_options[toolbar]',
        'value' => $settings['toolbar'],
        'helpText' => 'Default is unchecked (do NOT show).',
        ) 
      );

      // Add Statusbar Field
      add_settings_field('bpdfe_text_statusbar', 'Show Statusbar?', array( $this, "bpdfe_checkbox_input" ), 'pdf_embed_options', 'bpdfe_main', array(
        'name' => 'bpdfe_options[statusbar]',
        'value' => $settings['statusbar'],
        'helpText' => 'Default is unchecked (do NOT show).',
        ) 
      );

      // Add Messages Field
      add_settings_field('bpdfe_text_messages', 'Show Messages?', array( $this, "bpdfe_checkbox_input" ), 'pdf_embed_options', 'bpdfe_main', array(
        'name' => 'bpdfe_options[messages]',
        'value' => $settings['messages'],
        'helpText' => 'Default is unchecked (do NOT show).',
        ) 
      );

      // Add Navpanes Field
      add_settings_field('bpdfe_text_navpanes', 'Show Navpanes?', array( $this, "bpdfe_checkbox_input" ), 'pdf_embed_options', 'bpdfe_main', array(
        'name' => 'bpdfe_options[navpanes]',
        'value' => $settings['navpanes'],
        'helpText' => 'Default is unchecked (do NOT show).',
        ) 
      );
    }

    // Create Settings link and prepend to $links array
    function bpdfe_action_links( $links ) {
      $added_links = '<a href="'. get_admin_url(null, 'options-general.php?page=pdf-embed-options') .'">Settings</a>';
      //$added_links[] = '<a href="http://iamseangrant.com" target="_blank">Plugin Site</a>';

      // make Settings link first
      array_unshift( $links, $added_links );

      return $links;
    }

    // Set default settings in DB
    public static function bpdfe_set_defaults() {
      // get default values
      $defaults = BasicPDFEmbed::bpdfe_get_defaults();

      // If DB entry isn't an array (aka if activating plugin and no settings set)
      if( !is_array( get_option('bpdfe_options') ) ) {
        // update with defaults
        update_option( 'bpdfe_options', $defaults );
      }
    }

    // Returns Default Settings
    static function bpdfe_get_defaults() {
      // set default values into array
      $defaultsArray = array(
                'width' => '100%',
                'height' => '1000px',
                'view' => 'Fit',
                'toolbar' => '0',
                'scrollbar' => '1',
                'statusbar' => '0',
                'messages' => '0',
                'navpanes' => '0'
                 );

      return $defaultsArray;
    }

    // Sanitize options (set checkbox values) or reset to default settings
    function bpdfe_options_validate( $input ) {
      
      // Check for Reset Settings Flag
      if ( isset($input['setdefault']) ) {
        
        // grab default settings array
        $settings = $this->bpdfe_get_defaults();

      } else {
        $settings = get_option( 'bpdfe_options' ); //grab options from DB

        // Overwrite settings array with what was input via options page
        // text fields
        $settings['width'] = ( isset($input['width'])  ) ? $input['width'] : $settings['width'] ;
        $settings['height'] = ( isset($input['height'])  ) ? $input['height'] : $settings['height'] ;
        $settings['view'] = ( isset($input['view'])  ) ? $input['view'] : $settings['view'] ;

        // Checkboxes
        $settings['scrollbar'] = ( ! isset( $input['scrollbar'] ) || $input['scrollbar'] != '1' ) ? 0 : 1 ;
        $settings['toolbar'] = ( ! isset( $input['toolbar'] ) || $input['toolbar'] != '1' ) ? 0 : 1 ;
        $settings['statusbar'] = ( ! isset( $input['statusbar'] ) || $input['statusbar'] != '1' ) ? 0 : 1 ;
        $settings['messages'] = ( ! isset( $input['messages'] ) || $input['messages'] != '1' ) ? 0 : 1 ;
        $settings['navpanes'] = ( ! isset( $input['navpanes'] ) || $input['navpanes'] != '1' ) ? 0 : 1 ;
      }

    return $settings;
    }

    // Create Options Page
    function bpdfe_options_page() {
    ?>
        <div class="bpdfe-options-wrap">
            <h2>Basic PDF Embed Options</h2>
            <form method="POST" action="options.php">
                <?php settings_fields('bpdfe_options_group'); // adds hidden fields needed for working option form ?>
                <?php do_settings_sections('pdf_embed_options'); // add our custom setting/option fields ?>

                <?php submit_button(); ?>
                <?php submit_button( 'Reset to Default Settings', 'secondary', 'bpdfe_options[setdefault]' ); // creates Reset Options button ?>
            </form>
        </div>
    <?php
    }

    // add options page and call bpdfe_options_page to print it
    function bpdfe_add_custom_options() {
        add_options_page('Basic PDF Embed Options', 'PDF Embed Options', 'manage_options', 'pdf-embed-options', array( $this, "bpdfe_options_page" ) );
    }

    // define section text
    function bpdfe_section_text() {
      echo '<p>These are the basic options you can choose for displaying embdded PDFs.</p>
            <p>These options are based off the <a href="http://wwwimages.adobe.com/content/dam/Adobe/en/devnet/acrobat/pdfs/pdf_open_parameters_v9.pdf" 
            target="_blank">Adobe PDF Parameters</a> for opening PDFs. 
            Not all PDF plugins for browsers respect these options (Chrome, FF) although if the user\'s browser 
            is using Adobe\'s PDF Reader (example IE9) then these parameters will be respected.
            ' ;
    }

    // create text input fields
    function bpdfe_text_input( $args ) {
      $name = esc_attr( $args['name'] );
      $value = esc_attr( $args['value'] );
      $helpText = esc_attr( $args['helpText'] );

      echo "<input type='text' name='$name' value='$value' /> $helpText";
    }

    // create checkbox fields
    function bpdfe_checkbox_input( $args ) {
      $name = esc_attr( $args['name'] );
      $value = esc_attr( $args['value'] );
      $helpText = esc_attr( $args['helpText'] );

      echo "<input type='checkbox' name='$name' value='1' id='$value'" . checked( 1, $value, false ) . " /> $helpText";
    }


    // validate our options? REMOVE?
    /*function xxbpdfe_options_validate($input) {
      $options = get_option('bpdfe_options');
      $options['text_height'] = trim($input['text_height']);

      if(!preg_match('/^(auto|0)$|^[+-]?[0-9]+\.?([0-9]+)?(px|em|ex|%|in|cm|mm|pt|pc)$/i', $options['text_height'])) {
        $options['text_height'] = 'no-match';
      }

      return $options;
    }*/

    // adds PDF button to TinyMCE when editing posts/pages
    function bpdfe_add_button() {
      // if user can edit post and edit pages, then display PDF button
      if ( !current_user_can('edit_posts') &&  !current_user_can('edit_pages') ) {
        return;
      }
      // check if WYSIWYG is enabled
      if ( 'true' == get_user_option( 'rich_editing' ) ) {
        add_filter('mce_external_plugins', array( $this, "bpdfe_add_plugin" ) );   // add js plugin
        add_filter('mce_buttons', array( $this, "bpdfe_register_button" ) );       // register the button
      }
    }

    // Register Button
    function bpdfe_register_button( $buttons ) {
       array_push($buttons, "pdfembed");

       return $buttons;
    }

    // Register TinyMCE Plugin
    function bpdfe_add_plugin( $plugin_array ) {
       $plugin_array['pdfembed'] = plugins_url( 'shortcodebutton.js', __FILE__ ); // js file is in root of this plugin

       return $plugin_array;
    }

    /**
     * Auto Embed Code
     * Example: Paste PDF URL into post and it will auto embed/wrap it with the shortcode we created above tag
     */
    function bpdfe_embed_handler_pdf( $matches, $attr, $url, $rawattr ) {

      $embed = '[pdfembed url="' . $url . '"]'; // format the URL to use our custom PDF shortcode

      return apply_filters( 'embed_pdf', $embed, $matches, $attr, $url, $rawattr );
    }

    // Parameters for PDF Open are listed here: http://wwwimages.adobe.com/content/dam/Adobe/en/devnet/acrobat/pdfs/pdf_open_parameters_v9.pdf

    // basic usage = [pdfembed url="document_url_here"]
    function pdfembed_process_shortcode( $atts, $content = null ) {

      $setSettings = get_option( 'bpdfe_options' );
        
      // Attributes; setting defaults
      extract( shortcode_atts( $setSettings, $atts ) );

      // Code to ouput when using shortcode
      return '<div class ="basicpdfembed-wrapper"><object data="' . $atts['url']
           . '#view=' . $view
           . '&scrollbar=' . $scrollbar
           . '&toolbar=' . $toolbar
           . '&statusbar=' . $statusbar
           . '&messages=' . $messages
           . '&navpanes=' . $navpanes
           . '" type="application/pdf" 
          width="' . $width . '" 
          height="' . $height . '">
          </object></div>';
    }

  } // end of class BasicPDFEmbed

  function start_BasicPDFEmbed_plugin() {
    // start plugin by declaring new class
    new BasicPDFEmbed();
  }

  add_action( 'plugins_loaded', 'start_BasicPDFEmbed_plugin' );

  // When Activated set default values in options
  register_activation_hook(__FILE__, array( 'BasicPDFEmbed', 'bpdfe_set_defaults' ) );

  // For error checking/handling when activating plugin
  /*add_action('activated_plugin','bpdfe_save_error');
  function bpdfe_save_error(){
    // Add option to DB to explain error
    update_option('bpdfe_plugin_error',  ob_get_contents());
  }*/

} // end class_exists check

?>
