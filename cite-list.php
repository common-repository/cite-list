<?php
/*
Plugin Name: Cite & List
Plugin URI: http://wordpress.org/extend/plugins/cite-list/
Description: Use BibTeX to cite articles in your posts and create publication lists.
Version: 1.0
Author: Raphael Reitzig
Author URI: http://lmazy.verrech.net/
License: GPL2
*/
?>
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

require_once('LmazyPlugin.php');

/**
 * Main class of plugin Cite & List. See readme.txt for details.
 *
 * @author Raphael Reitzig
 * @version 1.0
 */
class CiteAndList extends LmazyPlugin {

  /**
   * Creates a new instance
   *
   * Registers shortcodes, settings and necessary hooks.
   * @see Plugin::__construct()
   */
  function __construct() {
    parent::__construct(array('name' => 'cite_and_list',
                              'prettyName' => 'Cite & List',
                              'mainFile' => __FILE__,
                              'resources' => array(array('Homepage', 'http://wordpress.org/extend/plugins/cite-list/'),
                                                   array('Blog', 'http://lmazy.verrech.net/tag/cite-list/'),
                                                   array('FAQ', 'http://wordpress.org/extend/plugins/cite-list/faq'),
                                                   array('Bugtracker', 'http://bugs.verrech.net/thebuggenie/citelist'),
                                                   array('Support', 'http://wordpress.org/tags/cite-list?forum_id=10'),
                                                   array('Contact', 'mailto:wordpress@verrech.net'))));

    // Register Shortcodes
    add_shortcode('cite', array(&$this, 'cite'));
    add_shortcode('bibsource', array(&$this, 'bibsource'));
    add_shortcode('publications', array(&$this, 'pub'));

    // Register hooks
    add_action('the_post', array(&$this, 'reset_cites'), 10, 0);
    add_filter('the_content', array(&$this, 'build_bib'), 12); // shortcode evaluation has priority 11, we need to be just after that.
  }

  /**
   * Called when this plugin is activated.
   * Sets default options if there are no options yet.
   */
  function activate() {
    if ( !get_option($this->name) ) {
      $options = array( 'sanitisation'=> '\{|\},,',
                        'keyFormat'   => 'nr',
                        'citeKeyFormat' => "<a href='#@postid@_@entrykey@' style='text-decoration:none;' title='@author@: @title@'>@entrykey@</a>",
                        'citeFormat'    => '[@keys@]',
                        'bibTemplate'   => "<hr />\n".
                                           "<table style='border:none;background-color:transparent;'>\n".
                                           "  @{entry@<tr>\n".
                                           "    <td style='border:none;'>\n".
                                           "      <a id='@postid@_@entrykey@' style='text-decoration:none;'>\n".
                                           "        [@entrykey@]\n".
                                           "      </a>\n".
                                           "    </td>\n".
                                           "    <td  style='border:none;'>@author@, <em>@title@</em>, @year@</td>\n".
                                           "  </tr>@}entry@\n".
                                           "</table>",
                        'pubTemplate'   => "@{group@\n".
                                           "  <h3>@groupkey@ (@groupcount@)</h3>\n".
                                           "  <ul>\n".
                                           "    @{entry@<li>\n".
                                           "      @author@, <em>@title@</em>, @year@\n".
                                           "    </li>@}entry@\n".
                                           "  </ul>\n".
                                           "@}group@");
      add_option($this->name, $options);
    }
  }

  /**
   * Called when this plugin is deactivated.
   * Does nothing.
   */
  function deactivate() {}

  /**
   * This function validates option input.
   * Reports invalid inputs (via `add_settings_error`).
   * @param array $input key/value pairs of wannabe options.
   * @return array a valid option array updated by valid pairs from the
   *               input array
   */
  function options_validate($input) {
    $options = get_option($this->name);

    $options['sanitisation'] = $input['sanitisation'];

    if ( in_array($input['keyFormat'], array('nr', 'key')) ) {
      $options['keyFormat'] = $input['keyFormat'];
    }
    else {
      add_settings_error('keyFormat', 'invalid choice', 'Key format has to be one of \'key\' and \'nr\'.');
    }

    if ( empty($input['citeKeyFormat']) || trim($input['citeKeyFormat']) === '' ) {
      add_settings_error('citeKeyFormat', 'empty_setting', 'Empty citation key format.');
    }
    else if ( !strstr($input['citeKeyFormat'], '@entrykey@') ) {
      add_settings_error('citeKeyFormat', 'useless_citeKeyFormat', 'Citation key format does not contain @entrykey@.');
    }
    else {
      $options['citeKeyFormat'] = trim($input['citeKeyFormat']);
    }

    if ( empty($input['citeFormat']) || trim($input['citeFormat']) === '' ) {
      add_settings_error('citeFormat', 'empty_setting', 'Empty citation format.');
    }
    else if ( !strstr($input['citeFormat'], '@keys@') ) {
      add_settings_error('citeFormat', 'useless_citeFormat', 'Citation format does not contain @keys@.');
    }
    else {
      $options['citeFormat'] = trim($input['citeFormat']);
    }

    if ( !empty($input['bibTemplate']) && strlen(trim($input['bibTemplate'])) > 0 ) {
      $options['bibTemplate'] = $input['bibTemplate'];
    }
    else {
      add_settings_error('bibTemplate', 'empty_setting', 'Empty bibliography template.');
    }

    if ( !empty($input['pubTemplate']) && strlen(trim($input['pubTemplate'])) > 0 ) {
      $options['pubTemplate'] = $input['pubTemplate'];
    }
    else {
      add_settings_error('pubTemplate', 'empty_setting', 'Empty publication list template.');
    }

    return $options;
  }

  /**
   * Used to create the options page form by the default implementation
   * of options_page. Should register settings via Settings API.
   */
  function setup_settings() {
    parent::setup_settings();
    include('settings.inc.php');
  }

  /*******************************************
   *          Functionality below            *
   *******************************************/

  /**
   * Contains keys cited in the current post.
   * @var array
   */
  private $cites = array();

  /**
   * Contains source file names encountered during this run and the files'
   * parsed contents if they were retrieved already (false else).
   * @var array
   */
  private $sources = array();

  /**
   * Contains source file names encountered during the current post.
   * @var array
   */
  private $postSources = array();

  /**
   * Called before a post is processed (hook 'the_post').
   * Empties the citation and post source collection.
   */
  function reset_cites() {
    if ( !empty($this->cites) || !empty($this->postSources) ) {
      if ( WP_DEBUG ) { echo '<div class="debugbox">Resetting citation stack</div>'; }
      $this->cites = array();
      $this->postSources = array();
    }
  }

  private $sanitising_patterns = array();
  private $sanitising_goals = array();

  /**
   * Sanitises the specified string according to the options.
   * @param string $string Input string
   * @return string Sanitised string
   */
  function sanitise($string) {
    if ( empty($this->sanitisating_patterns) ) {
      foreach ( preg_split('/\n/', $this->options['sanitisation']) as $line ) {
        $line = preg_split('/,,/', trim($line));
        $this->sanitising_patterns []= '/'.$line[0].'/';
        $this->sanitising_goals    []= $line[1];
      }
    }

    return preg_replace($this->sanitising_patterns, $this->sanitising_goals, $string);
  }

  /**
   * Collects all BibTeX specified for the current post (if necessary) and
   * parses it (if necessary).
   * @return array Array with collected, parsed BibTeX in first component and
   *               debug/error messages as display-ready string in the second.
   */
  private function collect_post_sources() {
    require_once('bib2tpl/bibtex_converter.php');

    $output = '';
    $bibtex = BibtexConverter::parse(get_post_meta(get_the_ID(), 'bibtex', true));
    if ( PEAR::isError($bibtex) ) {
      $output .= '<div class="errorbox">Custom field BibTeX could not be parsed: '.
                      $bibtex->getMessage().'</div>';
      $bibtex = array();
    }

    // Collect sources
    foreach ( $this->postSources as $file ) {
      // Only retrieve source file if it has not been processes earlier
      if ( $this->sources[$file] === false ) {
        if ( WP_DEBUG ) { $output .= '<div class="debugbox">Trying to get '.$file.'</div>'; }
        $res = @file_get_contents($file);

        if ( $res === false ) {
          $output .= '<div class="errorbox">Source file '.$file.' could not be read.</div>';
          $parsed = array(); // storing the empty array prevents further attempts
        }
        else {
          if ( WP_DEBUG ) { $output .= '<div class="debugbox">Trying to parse '.$file.'</div>'; }
          $parsed = BibtexConverter::parse($res);

          if ( PEAR::isError($parsed) ) {
            $output .= '<div class="errorbox">Source file '.$file.' could not be parsed: '.
                      $parsed->getMessage().'</div>';
            $parsed = array();
          }
        }

        $this->sources[$file] = $parsed;
      }

      // If this source could be parsed successfully, add its content
      if ( !empty($this->sources[$file]) ) {
        $bibtex = array_merge($bibtex, $this->sources[$file]);
      }
    }

    return array($bibtex, $output);
  }

  /**
   * Holds an array of parsed BibTeX data temporarily (see replacer).
   * @access private
   */
  private $bibtex_tmp;

  /**
   * Callback for preg_replace_callback in build_bib.
   * Workaround because PHP does not have proper closures.
   * Looks up an entry's value as matched.
   * @access private
   * @param array $matches result from a regexp match. Should contain an entrykey
   *                       as first component and a value key in the second.
   * @return string The matched value, sanitised.
   */
  private function replacer($matches) {
    if ( !empty($this->bibtex_tmp[$matches[1]][$matches[2]]) ) {
      return $this->sanitise($this->bibtex_tmp[$matches[1]][$matches[2]]);
    }
    else {
      return '?';
    }
  }

  /**
   * Called after the post's content has been assembled (hook 'the_content').
   * Appends a bibliography if there have been any citations. Displays an error
   * message if there are any major problems.
   * @param string $content The post content
   * @return string The post content with a bibliography appended if necessary
   */
  function build_bib($content) {
    $bib = "";

    if ( !empty($this->cites) ) {
      $collected = $this->collect_post_sources();
      $bibtex = $collected[0];
      $bib .= $collected[1];

      // Setup bib2tpl
      $bib2tpl = new BibtexConverter(array(
        'only' => array('entrykey' => join('|', array_keys($this->cites))),
        'group' => 'none',
        'sort_by' => 'entrykey',
        'order' => 'asc'
      ), array(&$this, 'sanitise'));

      // Get template
      $template = get_post_meta(get_the_ID(), 'bibtemplate', true);
      if ( empty($template) ) {
        $template = $this->options['bibTemplate'];
      }

      // Create bibliography
      $res = $bib2tpl->convert($bibtex, $template, $this->cites);

      if ( PEAR::isError($res) ) {
        $bib .= '<div class="errorbox">'.$res->getMessage().'</div>';
      }
      else {
        $bib .= do_shortcode($res); // shortcodes have already been processed, so we have to do it explicitly here
      }

      $bib = preg_replace('/@postid@/', get_the_ID(), $bib);

      // Fill in all gaps that are left at citations
      $this->bibtex_tmp = &$bibtex;
      $content = preg_replace_callback('/@(.+?)@(\w+?)@/', array(&$this, 'replacer'), $content);
      $this->bibtex_tmp = NULL;
    }

    return $content.$bib;
  }

  /**
   * Called by shortcode 'cite'.
   * Marks the specified key as cited, causing its entry to be listed
   * in the bibliography after the post.
   * @param array $param Array which may contain:
   *                     - 'key' -- comma list of keys to be cited.
   * @return string Expanded shortcode. Still contains bib2tpl template elements;
   *                those are cleaned up later (see build_bib).
   */
  function cite($param) {
    if ( !empty($param['key']) ) {
      $keys = explode(",", trim($param['key']));
      $printKeys = array();

      foreach ( $keys as $key ) {
        if ( !empty($key) ) {
          if ( !array_key_exists($key, $this->cites) ) {
            if ( $this->options['keyFormat'] === 'nr' ) {
              $this->cites[$key] = sizeof($this->cites) + 1;
            }
            else {
              $this->cites[$key] = $key;
            }
          }

          $print = preg_replace(array('/@entrykey@/', '/@postid@/', '/@author@/'),
                                array($this->cites[$key], get_the_ID(), '@niceauthor@'),
                                $this->options['citeKeyFormat']);
          $printKeys []= preg_replace('/@(\w+?)@/', '@'.$key.'@\1@', $print);
        }
      }
    }
    else {
      $printKeys = array('?');
    }

    return do_shortcode(preg_replace('/@keys@/', join(',', $printKeys), $this->options['citeFormat']));
  }

  /**
   * Called by shortcode 'bibsource'.
   * Adds the specified file to the list of BibTeX sources.
   * @param array $param Array which may contain:
   *                     - 'file' -- BibTeX source file
   * @return string Debug messages, if any.
   */
  function bibsource($param) {
    $result = '';

    if ( !empty($param['file']) ) {
      if ( !array_key_exists($param['file'], $this->sources) ) {
        if ( WP_DEBUG ) {
          $result .= '<div class="debugbox">Adding BibTeX source file '.$param['file'].'.</div>';
        }

        $this->sources[$param['file']] = false;
      }

      if ( !in_array($param['file'], $this->postSources) ) {
        if ( WP_DEBUG ) {
          $result .= '<div class="debugbox">Adding BibTeX source file '.$param['file'].' for this post.</div>';
        }

        $this->postSources []= $param['file'];
      }
    }
    elseif ( WP_DEBUG ) {
      $result .= '<div class="debugbox">No file specified in bibsource shortcode, dropping it.</div>';
    }

    return $result;
  }

  /**
   * Called by shortcode 'publications'.
   * Prints a list of publications according to the specified parameters.
   * @param array $param Array which may contain:
   * @return string Publication list
   */
  function pub($params) {
    $collected = $this->collect_post_sources();
    $bibtex = $collected[0];
    $content = $collected[1];

    $b2topt = array(
      'only'  => array(),
      'group' => 'year',
      'order_groups' => 'desc',
      'sort_by' => 'DATE',
      'order' => 'desc',
      'lang' => 'en'
    );

    if ( !empty($params) ) {
      $keys = array('group', 'order_groups', 'sort_by', 'order', 'lang');
      foreach ( $params as $key => $val ) {
        $val = trim($val);

        if ( strlen($val) > 0 ) {
          $matches = array();

          if ( in_array($key, $keys) ) {
            // Normal, flat paramater
            $b2topt[$key] = $val;
          }
          elseif ( preg_match('/^only_(\w+)$/', $key, $matches) > 0 ) {
            // filtering parameter
            $b2topt['only'][$matches[1]] = $val;
          }
          elseif ( WP_DEBUG ) {
            $content .= '<div class="debugbox">Unknown parameter name \''.$key.'\'.</div>';
          }
        }
        elseif  ( WP_DEBUG ) {
          $content .= '<div class="debugbox">Empty value for parameter \''.$key.'\'.</div>';
        }
      }
    }

    if ( WP_DEBUG ) {
      $content .= '<div class="debugbox"><p>Creating publication list with these options:</p><pre>'.print_r($b2topt, TRUE).'</pre></div>';
    }

    // Setup bib2tpl
    $bib2tpl = new BibtexConverter($b2topt, array(&$this, 'sanitise'));

    // Get template
    $template = get_post_meta(get_the_ID(), 'pubtemplate', true);
    if ( empty($template) ) {
      $template = $this->options['pubTemplate'];
    }

    // Create publication list
    $res = $bib2tpl->convert($bibtex, $template);

    if ( PEAR::isError($res) ) {
      $content .= '<div class="errorbox">'.$res->getMessage().'</div>';
    }
    else {
      $content .= do_shortcode($res);
    }

    return $content;
  }
}

new CiteAndList();
?>
