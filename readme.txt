=== Repeater Field for ACF ===
Contributors: repeater-field-for-acf
Donate link: https://wordpress.org/plugins/repeater-field-for-acf/
Tags: acf, advanced-custom-fields, repeater, custom-fields, flexible-content
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: advanced-custom-fields
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a fully functional Repeater field to the free version of Advanced Custom Fields. No ACF Pro required.

== Description ==

ACF Repeater adds a native-feeling Repeater field type to the **free** version of Advanced Custom Fields (ACF). It stores data in the same flat postmeta format used by ACF Pro, so all standard template functions work out of the box.

**Key Features:**

* **Three Layout Modes**: Table, Block (card), and Row (stacked) layouts
* **Drag & Drop Reordering**: Intuitive row sorting powered by jQuery UI Sortable
* **Row Operations**: Add, remove, duplicate rows
* **All ACF Free field types**: Text, Textarea, Number, Email, URL, Image, File, WYSIWYG, Select, Radio, Checkbox, True/False, Date Picker, Color Picker, Link, and more
* **Nested Repeaters**: Repeater fields can contain other repeater fields
* **get_field() compatible**: Uses ACF Pro-compatible flat meta storage — `get_field()`, `have_rows()`, `the_row()`, and `get_sub_field()` all work natively
* **ACF JSON Sync**: Full compatibility with ACF's field group JSON export/import
* **REST API Support**: Exposes repeater data through the WordPress REST API
* **Gutenberg & Classic Editor**: Works in both editors
* **Multisite Compatible**: Network activatable
* **Internationalization Ready**: Full translation support (text domain: `repeater-field-for-acf`)
* **Accessibility Ready**: Proper ARIA labels and keyboard support

**Data Storage (ACF Pro-compatible):**

Data is stored using ACF Pro's flat postmeta format:

* `{field_name}` → row count (integer)
* `{field_name}_{i}_{sub_field_name}` → sub-field value for row `i`
* `_{field_name}_{i}_{sub_field_name}` → sub-field key reference

This means all ACF template functions work without modification:

`php
$rows = get_field( 'my_repeater' );
if ( have_rows( 'my_repeater' ) ) {
    while ( have_rows( 'my_repeater' ) ) {
        the_row();
        $name = get_sub_field( 'name' );
    }
}
`

== Installation ==

1. Make sure **Advanced Custom Fields** (free, version 5.8+) is installed and activated.
2. Upload the `repeater-field-for-acf` folder to `/wp-content/plugins/`.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Go to **Custom Fields → Field Groups**, edit a field group, and add a new **Repeater** field (under the Layout category).
5. Add sub-fields to the repeater and save.

== Frequently Asked Questions ==

= Does this require ACF Pro? =

No. This plugin works exclusively with the **free** version of Advanced Custom Fields (5.8+). If ACF Pro is active, this plugin automatically defers to Pro's built-in repeater.

= Will it conflict with ACF Pro? =

No. If ACF Pro is detected (`ACF_PRO` constant is defined), the plugin disables itself and lets Pro handle the repeater field natively.

= Can I nest repeaters? =

Yes. Repeater fields can contain other repeater fields as sub-fields, with full drag-and-drop and all row operations.

= Does get_field() work? =

Yes. Data is stored in the same flat postmeta format as ACF Pro, so `get_field()`, `have_rows()`, `the_row()`, and `get_sub_field()` all work without any code changes.

= Is it compatible with ACF JSON sync? =

Yes. Full support for ACF's field group JSON export, import, and auto-sync.

= What WYSIWYG / rich field support is there? =

The plugin automatically detects rich field types (WYSIWYG, Gallery, etc.) and switches to the stacked Row layout to prevent display issues. TinyMCE editors are properly initialized, duplicated, and cleaned up when rows are added, duplicated, or removed.

== Changelog ==

= 1.0.0 =
* Initial release
* Full Repeater field implementation compatible with ACF Free 5.8+
* Three layout modes: Table, Block, Row
* Drag-and-drop row sorting
* Add, remove, duplicate rows
* WYSIWYG / TinyMCE editor support with proper clone/remove handling
* ACF Pro-compatible flat meta storage (get_field, have_rows, get_sub_field)
* Settings page under Custom Fields menu
* REST API integration
* ACF JSON/PHP export support
* Nested repeater support
* Multisite compatible

== Upgrade Notice ==

= 1.0.0 =
Initial release. Requires Advanced Custom Fields (free) 5.8+ and PHP 7.4+.