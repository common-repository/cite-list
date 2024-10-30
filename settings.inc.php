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
  // Tell Wordpress what settings to handle and how show them on the options page

  /**
   * PHP has curious restrictions regarding scope of variables. Therefore, the
   * functions used as callbacks in add_settings_field can not be (method) local
   * and access the members of the outer Plugin instance. Therefore, they are
   * defined in this (method local) class.
   *
   * @author Raphael Reitzig
   */
  class CiteAndListCallbacks {
    private $name = null;
    private $options = null;

    function __construct($name, $options) {
      $this->name = $name;
      $this->options = $options;
    }

    function style_text() {
      echo 'Configure the looks of citations, bibliographies and publication lists.';
    }

    function key_format() {
      echo "<select id='keyFormat' name='{$this->name}[keyFormat]'>\n";
      $selected = $this->options['keyFormat'] === "key" ? " selected" : "";
      echo "  <option value='key'{$selected}>BibTeX Key</option>";
      $selected = $this->options['keyFormat'] === "nr" ? " selected" : "";
      echo "  <option value='nr'{$selected}>Number</option>";
      echo "</select>";
    }

    function cite_key_format() {
      echo "<input id='citeKeyFormat' name='{$this->name}[citeKeyFormat]' size='40' type='text' value=\"{$this->options['citeKeyFormat']}\" />\n";
      echo "<br /><small>Use <code>@postid@</code> and other template values, such as <code>@entrykey@</code>.</small>";
    }

    function cite_format() {
      echo "<input id='citeFormat' name='{$this->name}[citeFormat]' size='40' type='text' value=\"{$this->options['citeFormat']}\" />\n";
      echo "<br /><small>Use <code>@keys@</code>; it is replaced by all keys with citation key format applied.</small>";
    }

    function bib_template() {
      echo "<textarea id='bibTemplate' name='{$this->name}[bibTemplate]' rows='10' cols='60'>{$this->options['bibTemplate']}</textarea>\n";
      echo "<br /><small>You can use <code>@postid@</code> here, too.<br />Need <a href='http://lmazy.verrech.net/bib2tpl/templates/' title='bib2tpl template documentation'>help</a>?</small>";
    }

    function pub_template() {
      echo "<textarea id='pubTemplate' name='{$this->name}[pubTemplate]' rows='10' cols='60'>{$this->options['pubTemplate']}</textarea>";
      echo "<br /><small>Need <a href='http://lmazy.verrech.net/bib2tpl/templates/' title='bib2tpl template documentation'>help</a>?</small>";
    }

    function sanitisation() {
      echo "<textarea id='sanitisation' name='{$this->name}[sanitisation]' rows='10' cols='60'>{$this->options['sanitisation']}</textarea>\n";
      echo "<br /><small>One pair of regular expression and replacement separated by <code>,,</code> per line, e.g. <code>\{|\},,</code> to remove braces.<br />Need <a href='http://www.php.net/manual/en/reference.pcre.pattern.syntax.php' title='PHP regexp documentation'>help</a>?</small>";
    }
  }
  $callbacks = new CiteAndListCallbacks($this->name, $this->options);

  add_settings_section('style', 'Style Settings', array(&$callbacks, 'style_text'), $this->name);
  add_settings_field('keyFormat', 'Bibliography Key Format:', array(&$callbacks, 'key_format'), $this->name, 'style');
  add_settings_field('citeKeyFormat', 'Citation Key Format:', array(&$callbacks, 'cite_key_format'), $this->name, 'style');
  add_settings_field('citeFormat', 'Citation Format:', array(&$callbacks, 'cite_format'), $this->name, 'style');
  add_settings_field('bibTemplate', 'Bibliography Template:', array(&$callbacks, 'bib_template'), $this->name, 'style');
  add_settings_field('pubTemplate', 'Publication List Template:', array(&$callbacks, 'pub_template'), $this->name, 'style');
  add_settings_field('sanitisation', 'Sanitisation Rules:', array(&$callbacks, 'sanitisation'), $this->name, 'style');

?>
