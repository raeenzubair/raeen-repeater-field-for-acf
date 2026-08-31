# Raeen Repeater Field for ACF

A powerful, performant Repeater field type for the free version of Advanced Custom Fields (ACF). Supports table/block layouts, drag-and-drop sorting, nested repeaters, REST API, and ACF JSON sync.

> **Source code:** This plugin is developed in the open. The public repository (including build tooling) is at <https://github.com/raeenzubair/repeater-field-for-acf>.

## Features

- **Three Layout Modes**: Table (spreadsheet-like), Block (card-based), and Row (stacked) layouts
- **Drag & Drop Reordering**: Intuitive row sorting with keyboard support
- **Row Operations**: Add, delete, duplicate, collapse/expand rows
- **Unlimited Sub Fields**: Supports all ACF Free field types including nested repeaters
- **Field Settings**: Min/max rows, button labels, collapsed titles, validation
- **Performance Optimized**: Handles hundreds of rows with lazy initialization
- **REST API Support**: Exposes repeater data through WordPress REST API
- **ACF JSON Export/Import**: Full compatibility with ACF's field group sync
- **PHP Export**: Generate PHP code for field groups
- **Gutenberg & Classic Editor**: Works in both editors
- **Multisite Compatible**: Network activatable
- **Accessibility Ready**: WCAG 2.1 AA compliant
- **Mobile Responsive**: Works on all device sizes
- **Internationalization Ready**: All strings translatable

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Advanced Custom Fields (Free) 5.8+

## Installation

1. Upload the `raeen-repeater-field-for-acf` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Requires Advanced Custom Fields (free version 5.8 or higher)
4. Go to Custom Fields > Field Groups and add a new "Repeater" field (under Layout fields)


## Usage

### Adding a Repeater Field

1. Edit or create a Field Group in ACF
2. Click "Add Field"
3. Select "Repeater" from the Layout field types
4. Configure field settings:
   - **Minimum Rows**: Minimum number of rows required (0 = no minimum)
   - **Maximum Rows**: Maximum number of rows allowed (0 = unlimited)
   - **Button Label**: Text for the "Add Row" button
   - **Layout**: Table, Block, or Row
   - **Collapsed Field**: Sub-field to use as row title when collapsed
   - **Enable Row Sorting**: Allow drag-and-drop reordering
   - **Enable Row Duplication**: Allow duplicating rows
   - **Delete Confirmation**: Show confirmation before deleting
   - **Default Rows**: Pre-populate with default rows for new posts
5. Add sub-fields by clicking "Add Field" within the repeater

### Supported Sub-Field Types

All ACF Free field types are supported:
- Text, Textarea, Number, Email, URL
- Image, File, Gallery
- Select, Radio, Checkbox, True/False
- WYSIWYG, Date Picker, Time Picker, DateTime Picker
- Color Picker, Link
- Nested Repeater, Flexible Content, Clone

### Template Usage

```php
// Basic loop
if ( have_rows( 'repeater_field' ) ) :
    while ( have_rows( 'repeater_field' ) ) : the_row();
        the_sub_field( 'text_field' );
        echo get_sub_field( 'image_field' );
    endwhile;
endif;

// With nested repeater
if ( have_rows( 'repeater_field' ) ) :
    while ( have_rows( 'repeater_field' ) ) : the_row();
        the_sub_field( 'title' );
        
        if ( have_rows( 'nested_repeater' ) ) :
            while ( have_rows( 'nested_repeater' ) ) : the_row();
                the_sub_field( 'nested_text' );
            endwhile;
        endif;
    endwhile;
endif;
```

## Configuration

Each Repeater field is configured directly inside the ACF Field Group editor. Options include layout selection (Table/Block/Row), row bounds (min/max), button labels, collapsed fields, and subfields.

## Hooks Reference

### Actions

```php
// Fired when plugin is activated
do_action( 'acf_repeater_activate' );

// Fired when plugin is deactivated
do_action( 'acf_repeater_deactivate' );

// Fired before rendering a repeater row
do_action( 'acf_repeater_before_row', $field, $row, $index );

// Fired after rendering a repeater row
do_action( 'acf_repeater_after_row', $field, $row, $index );

// Fired when a row is added via AJAX
do_action( 'acf_repeater_row_added', $field_key, $post_id, $row_index, $row_data );

// Fired when a row is removed via AJAX
do_action( 'acf_repeater_row_removed', $field_key, $post_id, $row_index );

// Fired when a row is duplicated via AJAX
do_action( 'acf_repeater_row_duplicated', $field_key, $post_id, $source_index, $new_index, $row_data );

// Fired when rows are reordered via AJAX
do_action( 'acf_repeater_rows_sorted', $field_key, $post_id, $new_order );
```

### Filters

```php
// Modify field settings before rendering
$field = apply_filters( 'acf_repeater_field_settings', $field );

// Modify row data before saving
$row_data = apply_filters( 'acf_repeater_row_data', $row_data, $field, $post_id, $row_index );

// Modify sub-field data before rendering
$sub_field = apply_filters( 'acf_repeater_sub_field', $sub_field, $field, $row, $row_index );

// Modify REST API response for repeater field
$response = apply_filters( 'acf_repeater_rest_response', $response, $field, $post_id );

// Modify export data for ACF JSON/PHP export
$export_data = apply_filters( 'acf_repeater_export_field', $field );

// Modify import data when importing field group
$field = apply_filters( 'acf_repeater_import_field', $field );

// Custom validation for specific field types
$errors = apply_filters( "acf_repeater_validate_field_{$type}", $errors, $value, $sub_field, $row_index );

// Custom sanitization for specific field types
$value = apply_filters( "acf_repeater_sanitize_field_{$type}", $value, $sub_field );
```

## REST API

When enabled in settings, repeater data is exposed via REST API:

### Get all repeater fields for a post
```
GET /wp-json/wp/v2/posts/123?fields=acf_repeater
```

### Get specific repeater field
```
GET /wp-json/raeen-repeater-field-for-acf/v1/repeater/field_abc123?post_id=123
```

### Get specific row
```
GET /wp-json/raeen-repeater-field-for-acf/v1/repeater/field_abc123/rows/0?post_id=123
```

### Add row
```
POST /wp-json/raeen-repeater-field-for-acf/v1/repeater/field_abc123/rows
Content-Type: application/json

{
    "post_id": 123,
    "row_data": {
        "text_field": "New Row",
        "email_field": "test@example.com"
    }
}
```

### Update row
```
PUT /wp-json/raeen-repeater-field-for-acf/v1/repeater/field_abc123/rows/0
Content-Type: application/json

{
    "post_id": 123,
    "row_data": {
        "text_field": "Updated Row"
    }
}
```

### Delete row
```
DELETE /wp-json/raeen-repeater-field-for-acf/v1/repeater/field_abc123/rows/0?post_id=123
```

## Development

### Build Assets

```bash
# Install dependencies
npm install

# Development with hot reload
npm run dev

# Production build
npm run build
```

### Run Tests

```bash
# PHP tests
composer test

# JavaScript tests
npm test
```

### Code Quality

```bash
# PHP static analysis
composer phpstan

# PHP coding standards
composer phpcs

# Auto-fix coding standards
composer phpcbf

# JavaScript linting
npm run lint
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and code quality checks
5. Submit a pull request

## License

GPL-2.0-or-later

## Changelog

### 1.0.3 (August 15, 2026)
- Compliance: Resolved WordPress.org Guideline 4 compliance by documenting public source repository URL, build tooling, and embedding license & source file banners into all compiled JS/CSS assets.
- Compliance: Corrected GitHub repository link to point to https://github.com/raeenzubair/repeater-field-for-acf.
- Enhancement: Standardized text domain to `raeen-repeater-field-for-acf` across all files.
- Fix: Updated PHPUnit test suite namespaces and added standalone polyfills for test execution.
- Build: Updated Vite build pipeline to automatically prepend source metadata banners to generated distribution assets in `assets/dist/`.

### 1.0.2 (August 12, 2026)
- Fix: Resolved fatal autoloader exception by explicitly loading built-in PSR-4 autoloader.
- Fix: Resolved duplicate field registration causing repeater field to render multiple times on edit screens.
- Fix: Resolved nested repeater add-row functionality by scoping clone template lookups and index replacements to parent repeaters.
- Fix: Resolved drag handle UI overlap with row numbers by increasing left column width.

### 1.0.1 (August 11, 2026)
- Enhancement: Updated plugin display name to "Raeen Repeater Field for ACF" and text domain to repeater-field-for-acf.
- Enhancement: Refactored code namespaces to Raeen_Repeater to prevent conflicts.
- Security: Hardened AJAX input handling and added strict nonce verification.
- Documentation: Documented open source repository and build instructions in readme.txt.

### 1.0.0 (August 10, 2026)
- Initial release of Repeater field for free Advanced Custom Fields (ACF 5.8+).
- Three layout modes: Table, Block, and Row.
- Drag-and-drop sorting and row operations.
- ACF Pro-compatible flat postmeta format.
- REST API and ACF JSON sync support.


<!-- Security scan triggered at 2026-08-31 18:23:53 -->