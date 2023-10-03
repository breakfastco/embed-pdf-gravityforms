=== Embed PDF for Gravity Forms ===

Contributors: salzano
Tags: gravityforms, gravity forms, pdf, inkless
Requires at least: 4.0
Tested up to: 6.3.0
Requires PHP: 5.6
Stable tag: 1.0.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
 
An add-on for Gravity Forms. Provides a PDF Viewer field.


== Description ==
 
Embed PDF for Gravity Forms provides a PDF Viewer field type. Include PDF files in forms without requiring users to download the PDF. Supports multi-page documents. Provides zoom controls.

= Features = 

* Drag a PDF Viewer field onto any Gravity Form
* Choose PDF from Media Library or provide local URL
* Supports multi-page PDFs
* Supports Dynamic Population
* Initial Scale setting changes default zoom level

= Demo =

[https://breakfastco.xyz/embed-pdf-for-gravity-forms/](https://breakfastco.xyz/embed-pdf-for-gravity-forms/)

Have an idea for a new feature? Please create an Issue on Github or Support Topic on wordpress.org.


== Installation ==
 
1. Search for Embed PDF for Gravity Forms in the Add New tab of the dashboard plugins page and press the Install Now button
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Open the form editor through the 'Forms' menu in WordPress
1. Add a 'PDF Viewer' field from the Advanced Fields tab in the form editor.


== Frequently Asked Questions ==
 
= How can I suggest a new feature for this plugin? =
 
Please create an [Issue on Github](https://github.com/breakfastco/embed-pdf-gravityforms/issues) or Support Topic on wordpress.org.


== Screenshots ==

1. Screenshot of a PDF embedded in a Gravity Form. 
1. Screenshot of a PDF Viewer field type in the Gravity Forms form editor Advanced Fields tab.
1. Screenshot of the form editor sidebar showing the PDF Viewer field settings.


== Changelog ==

= 1.0.5 =
* [Fixed] Adds a "file not found" error to the form editor so users know that PDF files are missing without previewing the form
* [Fixed] Changes CSS so the previous, next, zoom in, and zoom out buttons look better on smaller screens

= 1.0.4 =
* [Fixed] Splits scripting for the Form Editor out into its own file so only necessary scripts are loading in the form editor and on the front end where the field appears.
* [Changed] Updates banner art.

= 1.0.3 =
* [Fixed] Moves inline JavaScript required for each PDF Viewer field to the register_form_init_scripts() method of the Gravity Forms Field Framework.
* [Fixed] Stops writing errors to the browser developer console unless SCRIPT_DEBUG is enabled.

= 1.0.2 =
* [Fixed] Save the PDF URL with the entry when the form is submitted.
* [Changed] Changes the tested up to version to 6.3.0.

= 1.0.1 =
* [Fixed] Corrects stable tag to 1.0.1. The previous version had an incorrect stable tag version of 3.1.1.
* [Fixed] Prevent errors around defining constants if a user accidentally runs two copies of the plugin.
* [Fixed] Updates LICENSE with a copy of the GPLv3.

= 1.0.0 =
* [Added] First version.


== Upgrade Notice ==

= 1.0.0 =
First version.