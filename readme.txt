=== ACF Repeater ===
Contributors: acf-repeater
Donate link: https://wordpress.org/plugins/acf-repeater/
Tags: acf, advanced-custom-fields, repeater, field, custom-fields, layout, flexible-content
Requires at least: 5.8
Requires PHP: 7.4
Requires Plugins: advanced-custom-fields
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ACF Repeater adds a powerful Repeater field type to the free version of Advanced Custom Fields (ACF). Supports table/block layouts, drag-and-drop sorting, nested repeaters, REST API, and ACF JSON sync.

== Description ==

ACF Repeater is a feature-complete Repeater field implementation for the free version of Advanced Custom Fields. It provides a seamless experience that feels native to ACF.

**Key Features:**

* **Two Layout Modes**: Table (spreadsheet-like) and Block (card-based) layouts
* **Drag & Drop Reordering**: Intuitive row sorting with keyboard support
* **Row Operations**: Add, delete, duplicate, collapse/expand rows
* **Unlimited Sub Fields**: Supports all ACF Free field types including nested repeaters
* **Field Settings**: Min/max rows, button labels, collapsed titles, validation
* **Performance Optimized**: Handles hundreds of rows with lazy initialization
* **REST API Support**: Exposes repeater data through WordPress REST API
* **ACF JSON Export/Import**: Full compatibility with ACF's field group export
* **PHP Export**: Generate PHP code for field groups
* **Gutenberg & Classic Editor**: Works in both editors
* **Multisite Compatible**: Network activatable
* **Accessibility Ready**: WCAG 2.1 AA compliant
* **Mobile Responsive**: Works on all device sizes
* **Internationalization Ready**: Translation functions throughout

**Field Settings:**
- Minimum/Maximum rows
- Custom "Add Row" button label
- Table or Block layout
- Collapsed row title (uses sub-field value)
- Enable/disable row sorting
- Enable/disable row duplication
- Delete confirmation dialog
- Default rows (pre-populate on new posts)

**Sub Field Support:**
All ACF Free field types: Text, Textarea, Number, Email, URL, Image, File, Select, Radio, Checkbox, True/False, WYSIWYG, Date Picker, Time Picker, Color Picker, Link, and more.

**Developer Features:**
- PSR-4 autoloading
- Comprehensive hook system (actions/filters)
- WordPress Coding Standards compliant
- Extensible architecture

== Installation ==

1. Upload the `acf-repeater` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Requires Advanced Custom Fields (Free) version 5.8 or higher
4. Go to Custom Fields > Field Groups and add a new "Repeater" field (under Layout fields)

== Frequently Asked Questions ==

= Does this require ACF Pro? =
No! This plugin works with the free version of Advanced Custom Fields (5.8+).

= Can I nest repeaters? =
Yes, repeater fields can contain other repeater fields as sub-fields.

= Is it compatible with ACF JSON sync? =
Yes, full support for ACF's JSON export/import and PHP export.

= Does it work with Gutenberg? =
Yes, fully compatible with both Gutenberg and Classic Editor.

= How does data storage work? =
Data is stored in the standard ACF format (serialized arrays in postmeta), fully compatible with ACF's get_field(), the_field(), and other functions.

== Changelog ==

= 1.0.0 =
* Initial release
* Full Repeater field implementation
* Table and Block layouts
* Drag-and-drop sorting
* Nested repeater support
* REST API integration
* ACF JSON/PHP export support

== Upgrade Notice ==

= 1.0.0 =
Initial release. Requires ACF 5.8+ and PHP 7.4+.