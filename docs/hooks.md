# Advanced Repeater for ACF - Hooks Documentation

## Actions

### Plugin Lifecycle

#### `acf_repeater_activate`
Fired when the plugin is activated.

```php
add_action( 'acf_repeater_activate', function() {
    // Custom activation logic
} );
```

#### `acf_repeater_deactivate`
Fired when the plugin is deactivated.

```php
add_action( 'acf_repeater_deactivate', function() {
    // Custom deactivation logic
} );
```

### Row Rendering

#### `acf_repeater_before_row`
Fired before rendering a repeater row.

```php
add_action( 'acf_repeater_before_row', function( array $field, array $row, int $index ) {
    // $field - The repeater field configuration
    // $row - The row data
    // $index - The row index (0-based)
}, 10, 3 );
```

#### `acf_repeater_after_row`
Fired after rendering a repeater row.

```php
add_action( 'acf_repeater_after_row', function( array $field, array $row, int $index ) {
    // $field - The repeater field configuration
    // $row - The row data
    // $index - The row index (0-based)
}, 10, 3 );
```

### AJAX Operations

#### `acf_repeater_row_added`
Fired when a row is added via AJAX.

```php
add_action( 'acf_repeater_row_added', function( string $field_key, int $post_id, int $row_index, array $row_data ) {
    // $field_key - The repeater field key
    // $post_id - The post ID
    // $row_index - The index where the row was added
    // $row_data - The new row data
}, 10, 4 );
```

#### `acf_repeater_row_removed`
Fired when a row is removed via AJAX.

```php
add_action( 'acf_repeater_row_removed', function( string $field_key, int $post_id, int $row_index ) {
    // $field_key - The repeater field key
    // $post_id - The post ID
    // $row_index - The index of the removed row
}, 10, 3 );
```

#### `acf_repeater_row_duplicated`
Fired when a row is duplicated via AJAX.

```php
add_action( 'acf_repeater_row_duplicated', function( string $field_key, int $post_id, int $source_index, int $new_index, array $row_data ) {
    // $field_key - The repeater field key
    // $post_id - The post ID
    // $source_index - The index of the source row
    // $new_index - The index of the new duplicated row
    // $row_data - The duplicated row data
}, 10, 5 );
```

#### `acf_repeater_rows_sorted`
Fired when rows are reordered via AJAX.

```php
add_action( 'acf_repeater_rows_sorted', function( string $field_key, int $post_id, array $new_order ) {
    // $field_key - The repeater field key
    // $post_id - The post ID
    // $new_order - Array of row indices in their new order
}, 10, 3 );
```

## Filters

### Field Settings

#### `acf_repeater_field_settings`
Modify field settings before rendering.

```php
add_filter( 'acf_repeater_field_settings', function( array $field ) {
    // Modify field settings
    if ( $field['name'] === 'my_repeater' ) {
        $field['button_label'] = 'Add Item';
        $field['layout'] = 'block';
    }
    return $field;
} );
```

### Row Data

#### `acf_repeater_row_data`
Modify row data before saving.

```php
add_filter( 'acf_repeater_row_data', function( array $row_data, array $field, int $post_id, int $row_index ) {
    // $row_data - The row data being saved
    // $field - The repeater field configuration
    // $post_id - The post ID
    // $row_index - The row index

    // Add timestamp to each row
    $row_data['_created'] = current_time( 'mysql' );
    
    return $row_data;
}, 10, 4 );
```

#### `acf_repeater_sub_field`
Modify sub-field before rendering.

```php
add_filter( 'acf_repeater_sub_field', function( array $sub_field, array $field, array $row, int $row_index ) {
    // $sub_field - The sub-field configuration
    // $field - The parent repeater field
    // $row - The current row data
    // $row_index - The row index

    // Dynamically change placeholder based on row index
    if ( $sub_field['name'] === 'title' ) {
        $sub_field['placeholder'] = 'Enter title for row ' . ( $row_index + 1 );
    }

    return $sub_field;
}, 10, 4 );
```

### REST API

#### `acf_repeater_rest_response`
Modify REST API response for a repeater field.

```php
add_filter( 'acf_repeater_rest_response', function( array $response, array $field, int $post_id ) {
    // $response - The formatted REST response
    // $field - The repeater field configuration
    // $post_id - The post ID

    // Add custom meta to response
    $response['custom_meta'] = [
        'total_rows' => count( $response['rows'] ),
        'field_label' => $field['label'],
    ];

    return $response;
}, 10, 3 );
```

### Export/Import

#### `acf_repeater_export_field`
Modify field data during ACF JSON/PHP export.

```php
add_filter( 'acf_repeater_export_field', function( array $field ) {
    // $field - The field being exported

    // Remove sensitive data from export
    unset( $field['_internal_data'] );

    return $field;
} );
```

#### `acf_repeater_import_field`
Modify field data during ACF JSON/PHP import.

```php
add_filter( 'acf_repeater_import_field', function( array $field ) {
    // $field - The field being imported

    // Generate new keys if missing
    if ( empty( $field['key'] ) ) {
        $field['key'] = 'field_' . uniqid();
    }

    return $field;
} );
```

### Validation

#### `acf_repeater_validate_field_{type}`
Add custom validation for specific field types.

```php
// For email fields
add_filter( 'acf_repeater_validate_field_email', function( array $errors, $value, array $sub_field, int $row_index ) {
    if ( $value && ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
        $errors[] = 'Custom: Invalid email format';
    }
    return $errors;
}, 10, 4 );

// For custom field types
add_filter( 'acf_repeater_validate_field_my_custom_type', function( array $errors, $value, array $sub_field, int $row_index ) {
    // Custom validation logic
    if ( $value === 'forbidden' ) {
        $errors[] = 'This value is not allowed';
    }
    return $errors;
}, 10, 4 );
```

### Sanitization

#### `acf_repeater_sanitize_field_{type}`
Add custom sanitization for specific field types.

```php
// For text fields
add_filter( 'acf_repeater_sanitize_field_text', function( $value, array $sub_field ) {
    return sanitize_text_field( $value );
}, 10, 2 );

// For custom field types
add_filter( 'acf_repeater_sanitize_field_my_custom_type', function( $value, array $sub_field ) {
    // Custom sanitization logic
    return sanitize_textarea_field( $value );
}, 10, 2 );
```

### Settings

#### `acf_repeater_default_settings`
Modify default settings for new repeater fields.

```php
add_filter( 'acf_repeater_default_settings', function( array $defaults ) {
    return array_merge( $defaults, [
        'default_layout'         => 'block',
        'default_button_label'   => 'Add New Item',
        'default_sortable'       => true,
        'default_duplicate'      => false,
        'default_delete_confirm' => false,
        'default_min_rows'       => 1,
        'default_max_rows'       => 10,
    ] );
} );
```

#### `acf_repeater_admin_settings`
Modify settings page fields.

```php
add_filter( 'acf_repeater_admin_settings', function( array $settings ) {
    // Add custom setting section
    $settings['custom_section'] = [
        'title'    => 'Custom Settings',
        'fields'   => [
            'custom_option' => [
                'label'   => 'Custom Option',
                'type'    => 'text',
                'default' => '',
            ],
        ],
    ];

    return $settings;
} );
```

## JavaScript Hooks

### ACF Actions

```javascript
// Fired when a repeater field is initialized
acf.addAction( 'repeater_init', function( $field ) {
    console.log( 'Repeater initialized', $field );
} );

// Fired when a row is added
acf.addAction( 'repeater_row_added', function( $row, $field ) {
    console.log( 'Row added', $row );
} );

// Fired when a row is removed
acf.addAction( 'repeater_row_removed', function( $row, $field ) {
    console.log( 'Row removed', $row );
} );

// Fired when a row is duplicated
acf.addAction( 'repeater_row_duplicated', function( $newRow, $sourceRow, $field ) {
    console.log( 'Row duplicated', $newRow );
} );

// Fired when rows are sorted
acf.addAction( 'repeater_rows_sorted', function( $field, newOrder ) {
    console.log( 'Rows sorted', newOrder );
} );

// Fired before a row is collapsed/expanded
acf.addAction( 'repeater_row_toggle', function( $row, isCollapsed ) {
    console.log( 'Row toggled', isCollapsed );
} );
```

### ACF Filters

```javascript
// Modify row HTML before insertion
acf.addFilter( 'repeater_row_html', function( html, field, rowIndex ) {
    // Add custom attributes
    return html.replace( '<tr', '<tr data-custom="value"' );
} );

// Modify sub-field HTML
acf.addFilter( 'repeater_sub_field_html', function( html, subField, rowIndex ) {
    return html;
} );

// Modify AJAX request data
acf.addFilter( 'repeater_ajax_data', function( data, action, field ) {
    data.custom_param = 'value';
    return data;
} );
```

## Example: Custom Row Number Display

```php
// PHP: Add row number as a hidden field for sorting reference
add_filter( 'acf_repeater_row_data', function( $row_data, $field, $post_id, $row_index ) {
    $row_data['_row_number'] = $row_index + 1;
    return $row_data;
}, 10, 4 );

// JavaScript: Display row number in the handle column
acf.addAction( 'repeater_init', function( $field ) {
    $field.find( '.acf-repeater-row' ).each( function( index ) {
        const $handle = $( this ).find( '.acf-repeater-drag-handle' );
        $handle.attr( 'title', acf.__('Row') + ' ' + ( index + 1 ) );
    } );
} );
```

## Example: Conditional Sub-Fields Based on Row Data

```php
add_filter( 'acf_repeater_sub_field', function( $sub_field, $field, $row, $row_index ) {
    // Hide 'end_date' if 'is_ongoing' is true
    if ( $sub_field['name'] === 'end_date' && ! empty( $row['is_ongoing'] ) ) {
        $sub_field['conditional_logic'] = [
            [
                [
                    'field'    => $field['key'] . '[{row_index}][is_ongoing]',
                    'operator' => '!=',
                    'value'    => '1',
                ],
            ],
        ];
    }
    return $sub_field;
}, 10, 4 );
```

## Example: Auto-Save Row Data

```javascript
// JavaScript: Auto-save row data on blur
acf.addAction( 'repeater_init', function( $field ) {
    let saveTimeout;
    
    $field.on( 'blur', 'input, select, textarea', function() {
        const $row = $( this ).closest( '.acf-repeater-row' );
        const rowIndex = $row.data( 'row-index' );
        
        clearTimeout( saveTimeout );
        saveTimeout = setTimeout( () => {
            const fieldController = $field[0].acfRepeaterField;
            if ( fieldController ) {
                fieldController.saveRowData( rowIndex );
            }
        }, 500 );
    } );
} );
```