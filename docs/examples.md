# Advanced Repeater for ACF - Examples

## Basic Repeater Field

```php
// Register a simple repeater field
acf_add_local_field_group( [
    'key'                   => 'group_123',
    'title'                 => 'Team Members',
    'fields'                => [
        [
            'key'               => 'field_team_members',
            'label'             => 'Team Members',
            'name'              => 'team_members',
            'type'              => 'repeater',
            'layout'            => 'table',
            'button_label'      => 'Add Team Member',
            'min_rows'          => 1,
            'max_rows'          => 10,
            'sub_fields'        => [
                [
                    'key'   => 'field_member_name',
                    'label' => 'Name',
                    'name'  => 'member_name',
                    'type'  => 'text',
                    'required' => 1,
                ],
                [
                    'key'   => 'field_member_role',
                    'label' => 'Role',
                    'name'  => 'member_role',
                    'type'  => 'text',
                ],
                [
                    'key'   => 'field_member_photo',
                    'label' => 'Photo',
                    'name'  => 'member_photo',
                    'type'  => 'image',
                    'return_format' => 'array',
                ],
                [
                    'key'   => 'field_member_bio',
                    'label' => 'Bio',
                    'name'  => 'member_bio',
                    'type'  => 'textarea',
                ],
            ],
        ],
    ],
    'location'              => [
        [
            [
                'param'    => 'post_type',
                'operator' => '==',
                'value'    => 'page',
            ],
        ],
    ],
] );
```

## Displaying Repeater Data in Templates

### Basic Loop

```php
<?php if ( have_rows( 'team_members' ) ): ?>
    <div class="team-grid">
        <?php while ( have_rows( 'team_members' ) ): the_row(); ?>
            <div class="team-member">
                <?php 
                $photo = get_sub_field( 'member_photo' );
                if ( $photo ): ?>
                    <img src="<?php echo esc_url( $photo['url'] ); ?>" 
                         alt="<?php echo esc_attr( $photo['alt'] ); ?>" 
                         class="member-photo" />
                <?php endif; ?>
                
                <h3 class="member-name"><?php the_sub_field( 'member_name' ); ?></h3>
                <p class="member-role"><?php the_sub_field( 'member_role' ); ?></p>
                <div class="member-bio"><?php the_sub_field( 'member_bio' ); ?></div>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>
```

### Using get_field() for Array Access

```php
$team_members = get_field( 'team_members' );

if ( $team_members ) {
    foreach ( $team_members as $index => $member ) {
        echo '<div class="member">';
        echo '<h3>' . esc_html( $member['member_name'] ) . '</h3>';
        echo '<p>' . esc_html( $member['member_role'] ) . '</p>';
        echo '</div>';
    }
}
```

## Nested Repeater Example

### Field Group with Nested Repeater

```php
acf_add_local_field_group( [
    'key'                   => 'group_courses',
    'title'                 => 'Courses',
    'fields'                => [
        [
            'key'               => 'field_courses',
            'label'             => 'Courses',
            'name'              => 'courses',
            'type'              => 'repeater',
            'layout'            => 'block',
            'button_label'      => 'Add Course',
            'collapsed'         => 'course_title',
            'sub_fields'        => [
                [
                    'key'   => 'field_course_title',
                    'label' => 'Course Title',
                    'name'  => 'course_title',
                    'type'  => 'text',
                    'required' => 1,
                ],
                [
                    'key'   => 'field_course_description',
                    'label' => 'Description',
                    'name'  => 'course_description',
                    'type'  => 'wysiwyg',
                ],
                [
                    'key'               => 'field_course_modules',
                    'label'             => 'Modules',
                    'name'              => 'course_modules',
                    'type'              => 'repeater',
                    'layout'            => 'table',
                    'button_label'      => 'Add Module',
                    'min_rows'          => 1,
                    'sub_fields'        => [
                        [
                            'key'   => 'field_module_title',
                            'label' => 'Module Title',
                            'name'  => 'module_title',
                            'type'  => 'text',
                            'required' => 1,
                        ],
                        [
                            'key'   => 'field_module_lessons',
                            'label' => 'Lessons',
                            'name'  => 'module_lessons',
                            'type'  => 'repeater',
                            'layout'            => 'table',
                            'button_label'      => 'Add Lesson',
                            'sub_fields'        => [
                                [
                                    'key'   => 'field_lesson_title',
                                    'label' => 'Lesson Title',
                                    'name'  => 'lesson_title',
                                    'type'  => 'text',
                                ],
                                [
                                    'key'   => 'field_lesson_duration',
                                    'label' => 'Duration (minutes)',
                                    'name'  => 'lesson_duration',
                                    'type'  => 'number',
                                    'min'   => 1,
                                ],
                                [
                                    'key'   => 'field_lesson_video',
                                    'label' => 'Video URL',
                                    'name'  => 'lesson_video',
                                    'type'  => 'url',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'location'              => [
        [
            [
                'param'    => 'post_type',
                'operator' => '==',
                'value'    => 'course',
            ],
        ],
    ],
] );
```

### Displaying Nested Repeaters

```php
<?php if ( have_rows( 'courses' ) ): ?>
    <div class="courses-list">
        <?php while ( have_rows( 'courses' ) ): the_row(); ?>
            <article class="course">
                <h2><?php the_sub_field( 'course_title' ); ?></h2>
                <div class="course-description">
                    <?php the_sub_field( 'course_description' ); ?>
                </div>
                
                <?php if ( have_rows( 'course_modules' ) ): ?>
                    <div class="course-modules">
                        <?php while ( have_rows( 'course_modules' ) ): the_row(); ?>
                            <section class="module">
                                <h3><?php the_sub_field( 'module_title' ); ?></h3>
                                
                                <?php if ( have_rows( 'module_lessons' ) ): ?>
                                    <ul class="lessons-list">
                                        <?php while ( have_rows( 'module_lessons' ) ): the_row(); ?>
                                            <li class="lesson">
                                                <span class="lesson-title"><?php the_sub_field( 'lesson_title' ); ?></span>
                                                <span class="lesson-duration">
                                                    <?php the_sub_field( 'lesson_duration' ); ?> min
                                                </span>
                                                <?php 
                                                $video = get_sub_field( 'lesson_video' );
                                                if ( $video ): ?>
                                                    <a href="<?php echo esc_url( $video ); ?>" class="lesson-video" target="_blank">
                                                        Watch Video
                                                    </a>
                                                <?php endif; ?>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php endif; ?>
                            </section>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endwhile; ?>
    </div>
<?php endif; ?>
```

## Using with Gutenberg Blocks

### ACF Block with Repeater

```php
// Register block
acf_register_block_type( [
    'name'              => 'team-grid',
    'title'             => 'Team Grid',
    'description'       => 'Display a grid of team members',
    'render_template'   => 'template-parts/blocks/team-grid.php',
    'category'          => 'layout',
    'icon'              => 'groups',
    'keywords'          => [ 'team', 'staff', 'members', 'grid' ],
    'mode'              => 'preview',
    'supports'          => [
        'align'         => true,
        'anchor'        => true,
        'mode'          => false,
    ],
] );
```

### Block Template (template-parts/blocks/team-grid.php)

```php
<?php
/**
 * Team Grid Block Template
 */

$team_members = get_field( 'team_members' );
$columns = get_field( 'columns' ) ?: 3;

if ( ! $team_members ) {
    return;
}
?>

<div class="wp-block-acf-team-grid align<?php echo esc_attr( $align ); ?>" style="--columns: <?php echo esc_attr( $columns ); ?>">
    <div class="team-grid">
        <?php foreach ( $team_members as $member ): ?>
            <div class="team-member">
                <?php if ( ! empty( $member['member_photo'] ) ): ?>
                    <div class="member-photo">
                        <?php echo wp_get_attachment_image( $member['member_photo']['ID'], 'medium', false, [
                            'class' => 'member-image',
                            'alt'   => $member['member_photo']['alt'] ?: $member['member_name'],
                        ] ); ?>
                    </div>
                <?php endif; ?>
                
                <div class="member-info">
                    <h3 class="member-name"><?php echo esc_html( $member['member_name'] ); ?></h3>
                    <?php if ( $member['member_role'] ): ?>
                        <p class="member-role"><?php echo esc_html( $member['member_role'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( $member['member_bio'] ): ?>
                        <div class="member-bio"><?php echo wp_kses_post( $member['member_bio'] ); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
```

## REST API Examples

### Fetch Repeater Data

```javascript
// Get all repeater fields for a post
fetch( '/wp-json/wp/v2/posts/123?fields=acf_repeater' )
    .then( response => response.json() )
    .then( data => {
        console.log( data.acf_repeater );
        // {
        //   team_members: {
        //     field_key: 'field_abc123',
        //     field_name: 'team_members',
        //     layout: 'table',
        //     rows: [
        //       { index: 0, id: 'row_1', data: { member_name: 'John', member_role: 'Developer' } },
        //       { index: 1, id: 'row_2', data: { member_name: 'Jane', member_role: 'Designer' } }
        //     ]
        //   }
        // }
    } );

// Get specific repeater field
fetch( '/wp-json/advanced-repeater-for-custom-fields/v1/repeater/field_abc123?post_id=123' )
    .then( response => response.json() )
    .then( data => console.log( data ) );

// Add a new row
fetch( '/wp-json/advanced-repeater-for-custom-fields/v1/repeater/field_abc123/rows', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': 'your-nonce-here'
    },
    body: JSON.stringify({
        post_id: 123,
        row_data: {
            member_name: 'New Member',
            member_role: 'Intern',
            member_bio: 'New team member bio'
        }
    })
})
.then( response => response.json() )
.then( data => console.log( 'Row added:', data ) );

// Update a row
fetch( '/wp-json/advanced-repeater-for-custom-fields/v1/repeater/field_abc123/rows/0', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': 'your-nonce-here'
    },
    body: JSON.stringify({
        post_id: 123,
        row_data: {
            member_name: 'Updated Name'
        }
    })
})
.then( response => response.json() )
.then( data => console.log( 'Row updated:', data ) );

// Delete a row
fetch( '/wp-json/advanced-repeater-for-custom-fields/v1/repeater/field_abc123/rows/0?post_id=123', {
    method: 'DELETE',
    headers: {
        'X-WP-Nonce': 'your-nonce-here'
    }
})
.then( response => response.json() )
.then( data => console.log( 'Row deleted:', data ) );
```

## Custom Field Type Integration

### Adding a Custom Sub-Field Type

```php
// In your plugin/theme functions.php
add_action( 'acf/register_fields', function() {
    // Your custom field type registration
} );

// Add custom validation
add_filter( 'acf_repeater_validate_field_my_custom_type', function( $errors, $value, $sub_field, $row_index ) {
    if ( $value === 'invalid' ) {
        $errors[] = 'This value is not allowed in repeater rows';
    }
    return $errors;
}, 10, 4 );

// Add custom sanitization
add_filter( 'acf_repeater_sanitize_field_my_custom_type', function( $value, $sub_field ) {
    return sanitize_text_field( $value );
}, 10, 2 );
```

## Default Rows for New Posts

```php
// Programmatically set default rows
add_filter( 'acf/load_field/key=field_team_members', function( $field ) {
    if ( $field['name'] === 'team_members' && get_post_type() === 'page' ) {
        $field['default_rows'] = [
            [
                'member_name'  => 'John Doe',
                'member_role'  => 'Team Lead',
                'member_bio'   => 'Default team lead bio',
            ],
            [
                'member_name'  => 'Jane Smith',
                'member_role'  => 'Developer',
                'member_bio'   => 'Default developer bio',
            ],
        ];
    }
    return $field;
} );
```

## Conditional Logic Based on Row Data

```php
// Hide end_date when is_ongoing is checked
add_filter( 'acf_repeater_sub_field', function( $sub_field, $field, $row, $row_index ) {
    if ( $sub_field['name'] === 'event_end_date' ) {
        $sub_field['conditional_logic'] = [
            [
                [
                    'field'    => $field['key'] . '[{' . $row_index . '}][event_is_ongoing]',
                    'operator' => '!=',
                    'value'    => '1',
                ],
            ],
        ];
    }
    return $sub_field;
}, 10, 4 );
```

## Export/Import Example

### PHP Export

```php
// Get field group for export
$field_group = acf_get_field_group( 'group_123' );

// Export includes repeater fields with all sub-fields
// Use ACF's built-in export tools or:
// Tools > Export > Advanced Custom Fields
```

### JSON Sync

```php
// Configure custom JSON path in settings
// Custom Fields > Repeater Settings > ACF JSON Sync > Custom Save Path

// Or programmatically
add_filter( 'acf/settings/save_json', function( $path ) {
    return get_stylesheet_directory() . '/acf-json';
} );

add_filter( 'acf/settings/load_json', function( $paths ) {
    $paths[] = get_stylesheet_directory() . '/acf-json';
    return $paths;
} );
```

## Performance Optimization

### Lazy Loading for Large Repeaters

```php
// For repeaters with many rows, enable lazy loading
add_filter( 'acf_repeater_field_settings', function( $field ) {
    if ( $field['name'] === 'large_repeater' ) {
        $field['lazy_load'] = true;
        $field['rows_per_page'] = 20;
    }
    return $field;
} );
```

### Limiting Row Count

```php
// Enforce strict limits
add_filter( 'acf_repeater_field_settings', function( $field ) {
    if ( $field['name'] === 'limited_repeater' ) {
        $field['min_rows'] = 1;
        $field['max_rows'] = 50;
    }
    return $field;
} );
```

## Multilingual Support

### WPML / Polylang Integration

```php
// Translate repeater field labels
add_filter( 'acf_repeater_field_settings', function( $field ) {
    $field['button_label'] = __( 'Add Item', 'text-domain' );
    return $field;
} );

// Sub-field labels are automatically handled by ACF's translation functions
```