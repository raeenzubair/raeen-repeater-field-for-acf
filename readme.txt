=== Raeen Repeater Field for ACF ===
Contributors: moha12351
Donate link: https://wordpress.org/plugins/raeen-repeater-field-for-acf/
Tags: acf, advanced-custom-fields, repeater, custom-fields, flexible-content
Keywords: repeater, acf, advanced custom fields, wordpress plugin, free, multisite, rest api, gutenberg
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: advanced-custom-fields
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


== Description ==
Powerful and premium repeater field for the free version of Advanced Custom Fields (ACF). Seamlessly integrates with ACF’s native API (`get_field`, `have_rows`, `the_row`, `get_sub_field`) and supports table, block, and row layouts, drag‑and‑drop ordering, nested repeaters, Gutenberg & classic editor, REST API, multisite, and full ACF JSON sync. Ideal for developers seeking a feature‑rich, free‑plugin solution with a sleek modern UI matching ACF’s visual style.

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

* GitHub repository: https://github.com/raeenzubair/repeater-field-for-acf

The unminified JavaScript and CSS sources used to generate the compiled assets under `assets/dist/` are bundled in the plugin under `src/`. To rebuild the compiled assets from source:

```bash
npm install
npm run build
```

Run the test suite and code quality checks with:

```bash
npm test            # JavaScript unit tests
composer test       # PHP unit tests
composer phpcs      # PHP coding standards
composer phpstan    # PHP static analysis
```

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

= 1.0.1 =
* Updated plugin display name to "Raeen Repeater Field for ACF" and changed text domain to "repeater-field-for-acf" to match the WordPress.org plugin slug.
* Updated code namespaces to Raeen_Repeater to avoid prefix collisions.
* Added nonce sanitization and hardened AJAX input handling.
* Documented public source code repository and build instructions in readme.txt.
* Added branding overhaul: new logo, banner, screenshots, and GIF demo.

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