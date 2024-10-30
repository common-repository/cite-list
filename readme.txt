=== Cite & List ===
Contributors: akerbos87
Tags: bibtex, publications, citation, academia
Requires at least: 3.2.0
Tested up to: 3.3.1
Stable tag: 1.0

Use BibTeX to cite articles in your posts and create publication lists.

== Description ==
This plugin is intended for two use cases:

* Easily reference books, articles and websites in your posts.
* Provide a neat list of your own publications.

There is probably potential for more; be creative!

*Cite & List* uses standard format [BibTeX](http://www.bibtex.org/) and [bib2tpl](http://lmazy.verrech.net/bib2tpl/) in order to give you maximal control over the output and ease of use at the same time. You provide your bibliographic data in BibTeX format; the plugin can read it from custom fields as well as local and remote files. Parts of that data are rendered when you need it using templates powerful enough to create [sophisticated pages](http://verrech.net/demo/bib2tpl/example.html). As BibTeX sources are often dotted with LaTeX commands, we allow you to clean them up on the fly---no need to change your sources.

See the Usage page for documentation.

For a list of known issues see [here](http://bugs.verrech.net/thebuggenie/citelist/issues/open).

== Installation ==

1. Install *Cite & List* via your blog's plugin administration
2. Activate the plugin through the 'Plugins' menu in *WordPress*
3. Configure *Cite & List* via the 'Settings' menu in *WordPress*

== Usage ==

= Setup =
*Cite & List* works out of the box. You may want to change the way its output looks, however. To that end, visit the option page in the `Settings` menu. You can control the following parameters:

* **Bibliography Key Format**: `Number` will cause entries to number in order of citation. Else the keys provided in the respective sources are used.
* **Citation Key Format**: Controls how a single entry is represented in citations. Use the same syntax as for templates (see below).
* **Citation Format**: Specifies a container for citations. `@keys@` is replaced with a comma-separated list of citations according to citation key format.
* **Bibliography/Publication List Template**: Controls how bibliographies and publication lists look, respectively. See below for details.
* **Sanitisation Rules**: BibTeX files might contain LaTeX syntax and other surprises. Here you can enter [regexps](http://www.php.net/manual/en/reference.pcre.pattern.syntax.php) to amend that.

= Templates =
Please read bib2tpl's [documentation](http://lmazy.verrech.net/bib2tpl/templates/). You can use all features mentioned there. There is an additional tag; use `@postid@` in cite key format and bibliography template as unique identifier for the current post or page.

= Sources =
The recommended way to add BibTeX to a post is using a custom field named `bibtex`. Additionally, you can specify (local and remote) files via shortcode `bibsource`, for example `[bibsource file=http://myhp.org/example.bib]`.

In case of duplicate keys, files overwrite the custom field and later specified files overwrite earlier specified ones.

Note that you might have to avoid special characters in entry keys, for example if you want to use them as HTML IDs.

= Citations =
In order to cite entries use shortcode `cite` in your text, for instance `[cite key="aaa,bbb,ccc"]`. As you can see, you can cite multiple entries at once. The shortcode is replaced with a citation based on your settings. All keys cited in the current post are added to the bibliography which is automatically appended to the post if necessary.

Note that you can use keys from any source; it does not matter in which order `cite` and `bibsource` shortcodes appear.

= Publication Lists =
Shortcode `[publications]` creates a list of entries from all sources added so far and the `bibtex` custom field. For explanation of possible parameters `group`, `order_groups`, `sort_by`, `order` and `lang` see [bib2tpl's documention](http://lmazy.verrech.net/bib2tpl/api/); they behave exactly in the same way as the corresponding API parameters. Filtering is done by parameters `only_xyz=abc` which corresponds to an array entry `'xyz' => 'abc'` in API parameter `only`. Note that you have to escape backslashes in regular expressions with another `\` in filter parameter values.

== Frequently Asked Questions ==

= BibTeX? What is that? =
BibTeX is a long-time standard for storing bibliographic data in academia. Syntactically, it is not too different from JSON though the details differ. You find a description of the format [here](http://www.bibtex.org/Format/). Note that most publishers of academic texts offer valid BibTeX on article and book pages; you can just put such entries in a file sequentially.

= If I enter HTML as citation (key) format, everything breaks! What to do? =
Due to how strings are handled internally, you have to use `'` instead of `"` to delimit HTML attributes.

= Can I use shortcodes in my templates? =
Yes! Specifically, you can use them in citation key format, citation format as well as bibliography and publication list template. Note that shortcodes are only evaluated after all output has been properly assembled as far as *Cite & List* is concerned.

= Does grouping work in sanitisation? =
Yes, we got the full power of [PCRE](http://de2.php.net/manual/en/reference.pcre.pattern.syntax.php)! More specifically, if you enter a line `a,,b` into the sanitisation box, the plugin will execute `preg_replace('/a/', 'b', $string)` on every string printed directly from BibTeX. For instance, `(\w)b,,$1c` will replace all `b` that are not the first character in their respective word to be replaced with `c`.

= Why is it so slow? =
Every source file you specify is loaded and parsed once per page load and the templates you use are unfolded with that data. Especially the first part is bound to take its time if you use huge BibTeX files (the PEAR parser is not the summit of engineering, unfortunately); try using only the BibTeX you absolutely need, preferably via the `bibtex` custom field.

If this does not help or you have huge publication lists (wow, you've been busy!), you are out of luck. The way to go is caching so you can avoid running through the whole process every time. You should use a plugin dedicated to that task, for example [WP Super Cache](http://wordpress.org/extend/plugins/wp-super-cache/). Take care to purge page caches when you change your BibTeX sources, though.

= How can I help? =
You can

* use *Cite & List*,
* vote on the *Wordpress* plugin portal for it,
* report bugs and/or propose ideas for improvement [here](http://bugs.verrech.net/thebuggenie/citelist/issues/new/) and
* blog about your experience with it.

== Screenshots ==

1. A simple bibliography. Note how you can access bibliographic information at citations, here used for an informative tooltip.
2. A simple publication list, aggregated from several source files.
3. Overview of the settings used to create the other two screenshots.

== Changelog ==

= 1.0 =
Initial Release
