=== Raeen Repeater Field for ACF ===
Contributors: moha12351
Tags: acf, advanced-custom-fields, repeater, custom-fields, flexible-content
Keywords: repeater, acf, advanced custom fields, wordpress plugin, free, multisite, rest api, gutenberg
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: advanced-custom-fields
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a native-feeling Repeater field to the free version of Advanced Custom Fields. No ACF Pro required.

== Description ==

Raeen Repeater Field for ACF adds a native-feeling Repeater field type to the **free** version of Advanced Custom Fields (ACF). It stores data in the same flat postmeta format used by ACF Pro, so all standard template functions work out of the box.

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

    $rows = get_field( 'my_repeater' );
    if ( have_rows( 'my_repeater' ) ) {
        while ( have_rows( 'my_repeater' ) ) {
            the_row();
            $name = get_sub_field( 'name' );
        }
    }

== Screenshots ==
1. Field Group editor showing Repeater field configuration with sub-fields
2. Table Layout mode — data displayed in a clean tabular format with drag handles
3. Block Layout mode — collapsible card-style rows with rich field support
4. Row Layout mode — stacked vertical rows with full-width fields
5. Drag & Drop reordering — intuitive row sorting in action

== Installation ==

1. Make sure **Advanced Custom Fields** (free, version 5.8+) is installed and activated.
2. Upload the `raeen-repeater-field-for-acf` folder to `/wp-content/plugins/`.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Go to **Custom Fields → Field Groups**, edit a field group, and add a new **Repeater** field (under the Layout category).
5. Add sub-fields to the repeater and save.

== Source Code & Development ==

This plugin is developed in the open. The complete source code and build tooling are available at:

* GitHub repository: https://github.com/raeenzubair/raeen-repeater-field-for-acf

The unminified JavaScript and CSS sources used to generate the compiled assets under `assets/dist/` are bundled in the plugin under `src/`. To rebuild the compiled assets from source:

    npm install
    npm run build

Run the test suite and code quality checks with:

    npm test            # JavaScript unit tests
    composer test       # PHP unit tests
    composer phpcs      # PHP coding standards
    composer phpstan    # PHP static analysis

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

= 1.0.2 =
* Fix: Resolved fatal autoloader exception by explicitly loading built-in PSR-4 autoloader.
* Fix: Resolved duplicate field registration causing repeater field to render multiple times on edit screens.
* Fix: Resolved nested repeater add-row functionality by scoping clone template lookups and index replacements to parent repeaters.
* Fix: Resolved drag handle UI overlap with row numbers by increasing left column width.
* Enhancement: Enqueued frontend stylesheet for full acf_form() compatibility.
* Enhancement: Updated WordPress.org branding assets with animated GIF logo.

= 1.0.1 =
* Enhancement: Updated plugin display name to "Raeen Repeater Field for ACF" and text domain to repeater-field-for-acf to match WordPress.org plugin slug.
* Enhancement: Refactored code namespaces to Raeen_Repeater to prevent conflicts.
* Security: Hardened AJAX input handling and added strict nonce verification.
* Documentation: Documented open source repository and build instructions in readme.txt.
* Enhancement: Added full branding assets including logo, banner, screenshots, and GIF demo.

= 1.0.0 =
* Feature: Initial release of Repeater field for free Advanced Custom Fields (ACF 5.8+).
* Feature: Added three layout modes: Table, Block (card), and Row (stacked).
* Feature: Added drag-and-drop row sorting powered by jQuery UI Sortable.
* Feature: Added row operations (add, remove, duplicate, collapse/expand).
* Feature: Added WYSIWYG / TinyMCE editor support with automatic lifecycle management.
* Feature: ACF Pro-compatible flat meta storage (get_field, have_rows, the_row, get_sub_field).
* Feature: Added REST API endpoint integration.
* Feature: Added ACF JSON/PHP export and auto-sync support.
* Feature: Added nested repeater support and multisite compatibility.

== Upgrade Notice ==

= 1.0.0 =
Initial release. Requires Advanced Custom Fields (free) 5.8+ and PHP 7.4+.