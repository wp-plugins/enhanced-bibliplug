=== Enhanced BibliPlug ===
Contributors: zuotian, Clifford Tatum
Tags: bibliography, author profile, reference, academic, zotero
Requires at least: 2.9.1
Tested up to: 3.2.1
Stable tag: 1.1

Collaborative bibliography management for authors in WordPress.

== Description ==

Enhanced Bibliplug creates a central repository for researchers to organize their academic work in a collaborate environment.

Key features include:

1. database schema for storing bibliographical references.
1. administration pages to manage the references.
1. short code for easy retrieval of references based on author, year, and publication type.
1. the ability to connect and synchronize with Zotero accounts.
1. a custom Author page template to display user's academic title, affiliation, bio, and CV content such as publications and presentations.
1. short code for listing all users of the site.
1. the ability to group and retrieve references based on custom categories and tags.

= Reference short codes =

References can be displayed on any post/page using short code bibliplug.

`[bibliplug id=2]` displays a single reference.
`[bibliplug last_name='Smith']` displays references by author/editor last name.
`[bibliplug first_name='John']` displays references by author/editor first name.
`[bibliplug type='book']` displays all books.
`[bibliplug category='chapter7']` displays all references under category chapter7.
`[bibliplug tag='biology']` displays all references with tag biology.
`[bibliplug year='2008']` displays all references published in 2008.

**Note:**

You can mix-match any arguments except:

*   If id is used, other arguments are ignored.
*   If category is used, tag is ignored.

Other than types shown in the drop down box when editing a reference, there are two super types you can use.

*   Publication => any type that's not presentation or conference paper.
*   Presentation => presentation and conference paper.

All references are shown in Chicago Style Author-Date System.

= Author profile short codes =

Author list can be displayed on any post/page using short code bibliplug_authors.

`[bibliplug_authors id=2]` display a single user by its user id.
`[bibliplug_authors format='profile']` displays all users with user info and bio.
`[bibliplug_authors format='list']` displays all users with user info.
`[bibliplug_authors format='mini']` displays all users with profile pictures.

**Note:**
You can use id and format in the same short code instance.

= Included packages =

[phpZotero](https://github.com/clioweb/phpZotero "phpZotero") version 0.2 by Jeremy Boggs, Sean Takats.

== Installation ==


1. Extract `bibliplug.zip` files and upload `bibliplug` folder to the `/wp-content/plugins/` directory. Or, you can install the plugin using WordPress "Install Plugins" admin interface.
2. Activate the plugin through the 'Plugins' menu in WordPress.

= Notes about author template =

If you are using thesis 1.8 for your theme, please copy the content of bibliplug\template\author-loop.php to Thesis custom_functions.php. 

Otherwise, copy the author.php to your theme folder.



You're done!


== Frequently Asked Questions ==


= Cannot add/sync Zotero connection =

To synchronize with Zotero, the server needs to have php curl modules installed and enabled. Please check your php.ini file to make sure curl module is present and enabled.

= Will my data be deleted during upgrade? =

Deactivating or upgrade the plugin will not delete your bibligraphical data. 


== Screenshots ==

1. Reference Manager
2. Connect to Zotero
3. Author profile template

== Upgrade Notice ==

= 1.1 =

Add debugging support for zotero synchronization issues and support for paging through Zotero API results.

== Changelog ==

= 1.1 =

* Bug fix for bibliplug setting page.
* Added debug option for bibliplug setting page. This is for easy debugging zotero connection issues.
* Added a generic reference type "Other" to handle types currently not supported by bibliplug.
* Added support for paging through Zotero API results.


= 1.0 =

First release.
