<?php
/**
 * ACF Repeater Field Type.
 *
 * Compatible with ACF Free (5.8+). Uses ACF PRO-compatible flat meta storage
 * so that all native ACF template functions (get_field, have_rows, the_row,
 * get_sub_field) work out of the box without any additional wrappers.
 *
 * Storage schema (mirrors ACF PRO):
 *   {meta_key}                         → integer row count
 *   {meta_key}_{i}_{sub_field_name}    → sub-field value for row i
 *   _{meta_key}_{i}_{sub_field_name}   → sub-field key reference
 *
 * @package ACF_Repeater\Field
 */

if ( ! class_exists( 'acf_field_repeater' ) ) :

	/**
	 * Repeater field class.
	 */
	class acf_field_repeater extends acf_field {

		/**
		 * Prevent double-loading of value during render.
		 *
		 * @var bool
		 */
		public $is_rendering = false;

		/**
		 * Current post ID being edited.
		 *
		 * @var int|false
		 */
		public $post_id = false;

		/**
		 * Initialize field settings.
		 *
		 * @return void
		 */
		public function initialize() {
			$this->name        = 'repeater';
			$this->label       = __( 'Repeater', 'acf-repeater' );
			$this->category    = 'layout';
			$this->description = __( 'The Repeater field allows you to create a set of sub fields which can be repeated again and again whilst editing content.', 'acf-repeater' );
			$this->doc_url     = 'https://www.advancedcustomfields.com/resources/repeater/';
			$this->pro         = false; // This is NOT a PRO-only field when this plugin is active.
			$this->defaults    = array(
				'sub_fields'     => array(),
				'min'            => 0,
				'max'            => 0,
				'min_rows'       => 0,
				'max_rows'       => 0,
				'layout'         => 'table',
				'button_label'   => '',
				'collapsed'      => '',
				'sortable'       => 1,
				'duplicate'      => 1,
				'delete_confirm' => 1,
			);
			$this->l10n = array(
				'min_rows' => __( 'minimum rows required', 'acf-repeater' ),
				'max_rows' => __( 'maximum rows allowed', 'acf-repeater' ),
			);
		}

		// =====================================================================
		// Rendering — Admin Edit Screen
		// =====================================================================

		/**
		 * Render the repeater field in the post edit screen.
		 *
		 * @param array $field Field settings.
		 * @return void
		 */
		public function render_field( $field ) {
			$this->is_rendering = true;
			$this->post_id      = acf_get_valid_post_id();

			$layout     = ! empty( $field['layout'] ) ? $field['layout'] : 'table';
			$value      = is_array( $field['value'] ) ? $field['value'] : array();
			$sub_fields = ! empty( $field['sub_fields'] ) ? $field['sub_fields'] : array();

			// Rich field types (WYSIWYG, gallery, flexible, etc.) cannot fit in table
			// cells — automatically switch to row layout for those.
			$rich_types = array( 'wysiwyg', 'gallery', 'flexible_content', 'repeater', 'google_map', 'clone' );
			$has_rich   = false;
			foreach ( $sub_fields as $sf ) {
				if ( in_array( $sf['type'] ?? '', $rich_types, true ) ) {
					$has_rich = true;
					break;
				}
			}
			if ( 'table' === $layout && $has_rich ) {
				$layout = 'row';
			}

			// Always add the clone row placeholder (hidden).
			$value['acfcloneindex'] = array();

			$min_rows       = (int) ( $field['min'] > 0 ? $field['min'] : ( $field['min_rows'] ?? 0 ) );
			$max_rows       = (int) ( $field['max'] > 0 ? $field['max'] : ( $field['max_rows'] ?? 0 ) );
			$button_label   = ! empty( $field['button_label'] ) ? $field['button_label'] : __( 'Add Row', 'acf-repeater' );
			$sortable       = ! empty( $field['sortable'] );
			$duplicate      = ! empty( $field['duplicate'] );
			$delete_confirm = ! empty( $field['delete_confirm'] );
			$collapsed      = $field['collapsed'] ?? '';

			// Wrapper data attributes for the JS controller.
			$wrapper_attrs = array(
				'data-field-key'       => esc_attr( $field['key'] ),
				'data-field-name'      => esc_attr( $field['name'] ),
				'data-min-rows'        => $min_rows,
				'data-max-rows'        => $max_rows,
				'data-button-label'    => esc_attr( $button_label ),
				'data-collapsed-field' => esc_attr( $collapsed ),
				'data-sortable'        => $sortable ? 'true' : 'false',
				'data-duplicate'       => $duplicate ? 'true' : 'false',
				'data-delete-confirm'  => $delete_confirm ? 'true' : 'false',
				'data-layout'          => esc_attr( $layout ),
			);

			$attrs_str = '';
			foreach ( $wrapper_attrs as $k => $v ) {
				$attrs_str .= ' ' . $k . '="' . $v . '"';
			}

			echo '<div class="acf-repeater acf-repeater-' . esc_attr( $layout ) . '"' . $attrs_str . '>';

			if ( empty( $sub_fields ) ) {
				echo '<p class="acf-no-fields acf-cf"><span>' . esc_html__( 'Click the "+ Add Row" button below to start creating your content', 'acf-repeater' ) . '</span></p>';
			} else {
				// All layouts now use the same clean stacked-row HTML.
				echo '<div class="acf-repeater-rows">';
				foreach ( $value as $row_index => $row ) {
					$this->render_row( $field, $row, $row_index, $sub_fields );
				}
				echo '</div>';
			}

			// Add Row button footer.
			echo '<div class="acf-repeater-add-row">';
			echo '<a href="#" class="button acf-button blue acf-repeater-add-row-btn">';
			echo '<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>';
			echo esc_html( $button_label );
			echo '</a>';
			if ( $min_rows > 0 ) {
				echo '<span class="acf-min-rows" data-min="' . esc_attr( $min_rows ) . '">';
				/* translators: %d = minimum number of rows */
				echo sprintf( esc_html__( 'Minimum %d row(s) required', 'acf-repeater' ), $min_rows );
				echo '</span>';
			}
			echo '</div>';

			echo '</div>';
			$this->is_rendering = false;
		}

		/**
		 * Render a single repeater row (unified across all layouts).
		 *
		 * HTML structure matches ACF PRO "row" layout exactly:
		 *   .acf-row
		 *     .acf-row-handle.order   — row number + drag handle
		 *     .acf-fields             — all sub-fields stacked vertically (with labels)
		 *     .acf-row-handle.remove  — remove / duplicate buttons
		 *
		 * @param array      $field      Parent field.
		 * @param array      $row        Row data.
		 * @param string|int $row_index  Row index or 'acfcloneindex'.
		 * @param array      $sub_fields Sub-fields.
		 * @return void
		 */
		private function render_row( $field, $row, $row_index, $sub_fields ) {
			$is_clone = 'acfcloneindex' === $row_index;
			$classes  = 'acf-row' . ( $is_clone ? ' acf-clone' : '' );
			$style    = $is_clone ? ' style="display:none;"' : '';
			$row_num  = $is_clone ? '' : (string) ( (int) $row_index + 1 );
			?>
			<div class="<?php echo esc_attr( $classes ); ?>" data-id="<?php echo esc_attr( $row_index ); ?>"<?php echo $style; ?>>

				<!-- Left: drag handle + row number -->
				<div class="acf-row-handle order">
					<span class="acf-sortable-handle" title="<?php esc_attr_e( 'Drag to reorder', 'acf-repeater' ); ?>"></span>
					<span class="acf-row-number"><?php echo esc_html( $row_num ); ?></span>
				</div>

				<!-- Centre: sub-fields stacked with labels -->
				<div class="acf-fields -clear">
					<?php foreach ( $sub_fields as $sub_field ) : ?>
						<?php
						$sub_field = $this->prepare_sub_field_for_render( $field, $sub_field, $row, $row_index );
						acf_render_field_wrap( $sub_field );
						?>
					<?php endforeach; ?>
				</div>

				<!-- Right: remove / duplicate buttons -->
				<div class="acf-row-handle remove">
					<a class="acf-remove-row" href="#"
						title="<?php esc_attr_e( 'Remove row', 'acf-repeater' ); ?>"
						aria-label="<?php esc_attr_e( 'Remove row', 'acf-repeater' ); ?>">
						<span class="dashicons dashicons-minus" aria-hidden="true"></span>
					</a>
					<?php if ( ! $is_clone && ! empty( $field['duplicate'] ) ) : ?>
					<a class="acf-duplicate-row" href="#"
						title="<?php esc_attr_e( 'Duplicate row', 'acf-repeater' ); ?>"
						aria-label="<?php esc_attr_e( 'Duplicate row', 'acf-repeater' ); ?>">
						<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
					</a>
					<?php endif; ?>
				</div>

			</div><!-- .acf-row -->
			<?php
		}

		/* Legacy method aliases kept to avoid breaking any subclass overrides. */
		private function render_table( $field, $value, $sub_fields ) {
			echo '<div class="acf-repeater-rows">';
			foreach ( $value as $row_index => $row ) {
				$this->render_row( $field, $row, $row_index, $sub_fields );
			}
			echo '</div>';
		}

		private function render_block( $field, $value, $sub_fields ) {
			echo '<div class="acf-repeater-rows">';
			foreach ( $value as $row_index => $row ) {
				$this->render_row( $field, $row, $row_index, $sub_fields );
			}
			echo '</div>';
		}

		private function render_table_row( $field, $row, $row_index, $sub_fields ) {
			$this->render_row( $field, $row, $row_index, $sub_fields );
		}

		private function render_block_row( $field, $row, $row_index, $sub_fields ) {
			$this->render_row( $field, $row, $row_index, $sub_fields );
		}

		/**
		 * Prepare a sub-field array for rendering inside a repeater row.
		 *
		 * Correctly sets the prefix, name, and value so ACF generates
		 * input names in the form: acf[{parent_name}][{row_index}][{sub_name}]
		 *
		 * @param array      $parent_field Parent field settings.
		 * @param array      $sub_field    Sub-field settings.
		 * @param array      $row          Current row data.
		 * @param string|int $row_index    Row index (or 'acfcloneindex').
		 * @return array Modified sub-field.
		 */
		private function prepare_sub_field_for_render( $parent_field, $sub_field, $row, $row_index ) {
			$sub_field['value'] =
				$row[ $sub_field['key'] ]
				?? $row[ $sub_field['name'] ]
				?? $sub_field['default_value']
				?? '';

			// acf_prepare_field() builds the HTML name as:
			//   "{prefix}[{field_key}]"
			// So to get: acf[parent_key][row_index][sub_key]
			// We must set prefix = "acf[parent_key][row_index]".
			$parent_key          = $parent_field['key'];
			$sub_field['prefix'] = 'acf[' . $parent_key . '][' . $row_index . ']';

			// Keep _name as the human name (used for data-name attribute).
			$sub_field['_name']  = $sub_field['name'];

			// Ensure the sub-field has proper parent reference.
			$sub_field['parent'] = $parent_key;

			// Mark as not yet prepared so acf_prepare_field() runs fresh.
			unset( $sub_field['_prepare'] );

			return $sub_field;
		}

		// =====================================================================
		// Field Settings (Field Group Editor)
		// =====================================================================

		/**
		 * Render field settings in the field group editor.
		 *
		 * @param array $field Field settings.
		 * @return void
		 */
		public function render_field_settings( $field ) {
			// Sub-fields list (uses ACF's built-in field group sub-fields view).
			$args = array(
				'fields'      => ! empty( $field['sub_fields'] ) ? $field['sub_fields'] : array(),
				'parent'      => $field['ID'] ?? 0,
				'is_subfield' => true,
			);
			?>
			<div class="acf-field acf-field-setting-sub_fields" data-setting="repeater" data-name="sub_fields">
				<div class="acf-label">
					<label><?php esc_html_e( 'Sub Fields', 'acf' ); ?></label>
				</div>
				<div class="acf-input">
					<?php acf_get_view( 'acf-field-group/fields', $args ); ?>
				</div>
			</div>
			<?php

			// Min Rows.
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Minimum Rows', 'acf-repeater' ),
					'instructions' => __( 'Minimum number of rows required. Leave blank for no minimum.', 'acf-repeater' ),
					'type'         => 'number',
					'name'         => 'min',
					'ui'           => 0,
					'min'          => 0,
				)
			);

			// Max Rows.
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Maximum Rows', 'acf-repeater' ),
					'instructions' => __( 'Maximum number of rows allowed. Leave blank for no maximum.', 'acf-repeater' ),
					'type'         => 'number',
					'name'         => 'max',
					'ui'           => 0,
					'min'          => 0,
				)
			);

			// Layout.
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Layout', 'acf-repeater' ),
					'instructions' => __( 'Select the style used to display the repeater rows.', 'acf-repeater' ),
					'type'         => 'radio',
					'name'         => 'layout',
					'choices'      => array(
						'table' => __( 'Table', 'acf-repeater' ),
						'block' => __( 'Block', 'acf-repeater' ),
						'row'   => __( 'Row', 'acf-repeater' ),
					),
					'layout'       => 'horizontal',
				)
			);

			// Button Label.
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Add Row Button Label', 'acf-repeater' ),
					'instructions' => __( 'Text shown on the "Add Row" button.', 'acf-repeater' ),
					'type'         => 'text',
					'name'         => 'button_label',
				)
			);

			// Collapsed.
			$choices = array( '' => '— ' . __( 'None', 'acf-repeater' ) . ' —' );
			if ( ! empty( $field['sub_fields'] ) ) {
				foreach ( $field['sub_fields'] as $sf ) {
					$choices[ $sf['key'] ] = $sf['label'];
				}
			}
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Collapsed', 'acf-repeater' ),
					'instructions' => __( 'Select a sub field to show when the row is collapsed.', 'acf-repeater' ),
					'type'         => 'select',
					'name'         => 'collapsed',
					'choices'      => $choices,
					'allow_null'   => 0,
					'ui'           => 0,
				)
			);
		}

		// =====================================================================
		// Value — Load / Update / Format / Validate
		// =====================================================================

		/**
		 * Load the repeater field value.
		 *
		 * Reads data in ACF PRO-compatible flat meta format:
		 *   {name}                   → row count (int)
		 *   {name}_{i}_{sub_name}   → sub-field value
		 *
		 * This ensures get_field() / have_rows() / the_row() / get_sub_field()
		 * all work natively without any extra wrappers.
		 *
		 * @param mixed  $value   Raw meta value (row count).
		 * @param int    $post_id Post/term/user ID.
		 * @param array  $field   Field settings.
		 * @return array Array of rows.
		 */
		public function load_value( $value, $post_id, $field ) {
			// During rendering the value has already been prepared. Return as-is.
			if ( $this->is_rendering ) {
				return $value;
			}

			// The stored meta value is the row count (an integer).
			$count = (int) $value;

			if ( $count <= 0 ) {
				return array();
			}

			$sub_fields = ! empty( $field['sub_fields'] ) ? $field['sub_fields'] : array();
			$rows       = array();

			for ( $i = 0; $i < $count; $i++ ) {
				$row = array();
				foreach ( $sub_fields as $sub_field ) {
					$meta_key = $field['name'] . '_' . $i . '_' . $sub_field['name'];
					$value    = get_metadata( $this->get_meta_type( $post_id ), $this->get_meta_id( $post_id ), $meta_key, true );
					$row[ $sub_field['key'] ] = $value;
				}
				$rows[] = $row;
			}

			return $rows;
		}

		/**
		 * Update (save) the repeater field value.
		 *
		 * Uses ACF PRO-compatible flat meta storage:
		 *   {name}                   → row count (int)
		 *   {name}_{i}_{sub_name}   → sub-field value
		 *   _{name}_{i}_{sub_name}  → sub-field key reference
		 *
		 * @param mixed $value   Submitted rows data (array).
		 * @param int   $post_id Post ID.
		 * @param array $field   Field settings.
		 * @return mixed Processed count value for ACF's meta save.
		 */
		public function update_value( $value, $post_id, $field ) {
			// Strip the clone placeholder row.
			if ( is_array( $value ) && isset( $value['acfcloneindex'] ) ) {
				unset( $value['acfcloneindex'] );
			}

			// Ensure it's an array of rows.
			if ( ! is_array( $value ) ) {
				$value = array();
			}

			// Re-index (in case of gaps after removal).
			$value = array_values( $value );

			$sub_fields = ! empty( $field['sub_fields'] ) ? $field['sub_fields'] : array();
			$meta_type  = $this->get_meta_type( $post_id );
			$meta_id    = $this->get_meta_id( $post_id );
			$old_count  = (int) get_metadata( $meta_type, $meta_id, $field['name'], true );
			$new_count  = count( $value );

			// Build a key→name lookup for sub-fields.
			// acf_prepare_field() replaces name with key, so submitted rows
			// are keyed by sub-field KEY, not sub-field name.
			$key_to_name = array();
			foreach ( $sub_fields as $sf ) {
				$key_to_name[ $sf['key'] ] = $sf['name'];
			}

			foreach ( $value as $i => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				foreach ( $sub_fields as $sub_field ) {
					// The submitted row may be keyed by field key OR field name.
					// Try key first (what acf_prepare_field produces), then name.
					$sub_value = $row[ $sub_field['key'] ] ?? $row[ $sub_field['name'] ] ?? '';

					// Flat meta key mirrors ACF PRO storage:
					// e.g. "faq_0_question", "faq_1_answer"
					$meta_key     = $field['name'] . '_' . $i . '_' . $sub_field['name'];
					$key_meta_key = '_' . $meta_key; // ACF field-key reference.

					// Allow the sub-field type's own update_value filter to run
					// (handles image IDs, taxonomies, etc.).
					$tmp_field         = $sub_field;
					$tmp_field['name'] = $meta_key;
					$processed_value   = apply_filters(
						'acf/update_value/type=' . $sub_field['type'],
						$sub_value,
						$post_id,
						$tmp_field,
						$sub_value
					);

					update_metadata( $meta_type, $meta_id, $meta_key, $processed_value );
					update_metadata( $meta_type, $meta_id, $key_meta_key, $sub_field['key'] );
				}
			}

			// Delete meta rows that no longer exist.
			if ( $old_count > $new_count ) {
				for ( $i = $new_count; $i < $old_count; $i++ ) {
					foreach ( $sub_fields as $sub_field ) {
						$meta_key     = $field['name'] . '_' . $i . '_' . $sub_field['name'];
						$key_meta_key = '_' . $meta_key;
						delete_metadata( $meta_type, $meta_id, $meta_key );
						delete_metadata( $meta_type, $meta_id, $key_meta_key );
					}
				}
			}

			// Return the row count — stored as the primary meta value for the field.
			return $new_count;
		}

		/**
		 * Format the field value for use in templates (get_field).
		 *
		 * Formats each sub-field using ACF's own format_value system so that
		 * image IDs become image arrays, taxonomies become term objects, etc.
		 *
		 * @param mixed $value   Rows array.
		 * @param int   $post_id Post ID.
		 * @param array $field   Field settings.
		 * @return array Formatted rows.
		 */
		public function format_value( $value, $post_id, $field ) {
			if ( ! is_array( $value ) || empty( $value ) ) {
				return array();
			}

			$sub_fields = ! empty( $field['sub_fields'] ) ? $field['sub_fields'] : array();

			foreach ( $value as $row_index => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				foreach ( $sub_fields as $sub_field ) {
					if ( array_key_exists( $sub_field['key'], $row ) ) {
						$sub_field['value']                              = $row[ $sub_field['key'] ];
						$value[ $row_index ][ $sub_field['name'] ]       = acf_format_value(
							$row[ $sub_field['key'] ],
							$post_id,
							$sub_field
						);
					}
				}
			}

			return $value;
		}

		/**
		 * Validate the field value.
		 *
		 * Checks min/max row counts.
		 *
		 * @param bool   $valid Whether value is valid.
		 * @param mixed  $value Submitted value.
		 * @param array  $field Field settings.
		 * @param string $input Input name.
		 * @return bool|string True if valid, error message string if not.
		 */
		public function validate_value( $valid, $value, $field, $input ) {
			if ( ! is_array( $value ) ) {
				$value = array();
			}

			// Strip clone row from count.
			$rows = $value;
			unset( $rows['acfcloneindex'] );
			$count = count( $rows );

			$min = (int) ( $field['min'] ?? $field['min_rows'] ?? 0 );
			$max = (int) ( $field['max'] ?? $field['max_rows'] ?? 0 );

			if ( $min > 0 && $count < $min ) {
				return sprintf(
					/* translators: 1: minimum, 2: label */
					__( '%1$s requires a minimum of %2$s rows', 'acf-repeater' ),
					'<strong>' . esc_html( $field['label'] ) . '</strong>',
					'<strong>' . $min . '</strong>'
				);
			}

			if ( $max > 0 && $count > $max ) {
				return sprintf(
					/* translators: 1: maximum, 2: label */
					__( '%1$s requires a maximum of %2$s rows', 'acf-repeater' ),
					'<strong>' . esc_html( $field['label'] ) . '</strong>',
					'<strong>' . $max . '</strong>'
				);
			}

			return $valid;
		}

		// =====================================================================
		// Sub-fields — Load, Duplicate, Export, Import
		// =====================================================================

		/**
		 * Load field — populate sub_fields from database.
		 *
		 * @param array $field Field settings.
		 * @return array Modified field.
		 */
		public function load_field( $field ) {
			// Load sub-fields that belong to this repeater.
			$sub_fields = acf_get_fields( $field );
			if ( $sub_fields ) {
				$field['sub_fields'] = $sub_fields;
			} else {
				$field['sub_fields'] = array();
			}
			return $field;
		}

		/**
		 * Duplicate field — also duplicates sub-fields.
		 *
		 * @param array $field Field settings.
		 * @return array Updated field.
		 */
		public function duplicate_field( $field ) {
			$sub_fields = acf_extract_var( $field, 'sub_fields' );
			$field      = acf_update_field( $field );
			acf_duplicate_fields( $sub_fields, $field['ID'] );
			return $field;
		}

		/**
		 * Prepare field for export (ACF JSON).
		 *
		 * @param array $field Field settings.
		 * @return array Prepared field.
		 */
		public function prepare_field_for_export( $field ) {
			if ( ! empty( $field['sub_fields'] ) ) {
				foreach ( $field['sub_fields'] as $i => $sub_field ) {
					$field['sub_fields'][ $i ] = acf_prepare_field_for_export( $sub_field );
				}
			}
			return $field;
		}

		/**
		 * Prepare field for import (ACF JSON).
		 *
		 * @param array $field Field settings.
		 * @return array Prepared field.
		 */
		public function prepare_field_for_import( $field ) {
			if ( ! empty( $field['sub_fields'] ) ) {
				foreach ( $field['sub_fields'] as $i => $sub_field ) {
					$field['sub_fields'][ $i ] = acf_prepare_field_for_import( $sub_field );
				}
			}
			return $field;
		}

		/**
		 * Prepare field — called before render, sets defaults.
		 *
		 * @param array $field Field settings.
		 * @return array Prepared field.
		 */
		public function prepare_field( $field ) {
			if ( empty( $field['button_label'] ) ) {
				$field['button_label'] = __( 'Add Row', 'acf-repeater' );
			}
			if ( empty( $field['min'] ) ) {
				$field['min'] = 0;
			}
			if ( empty( $field['max'] ) ) {
				$field['max'] = 0;
			}
			return $field;
		}

		/**
		 * Translate field — translates button_label for WPML etc.
		 *
		 * @param array $field Field settings.
		 * @return array Translated field.
		 */
		public function translate_field( $field ) {
			if ( ! empty( $field['button_label'] ) ) {
				$field['button_label'] = acf_translate( $field['button_label'] );
			}
			return $field;
		}

		// =====================================================================
		// Helpers
		// =====================================================================

		/**
		 * Get the meta object type based on post_id context.
		 *
		 * ACF uses encoded post IDs for terms (term_123) and users (user_123).
		 *
		 * @param int|string $post_id ACF post ID (may be prefixed).
		 * @return string 'post', 'term', 'user', 'comment', or 'option'.
		 */
		private function get_meta_type( $post_id ) {
			if ( is_string( $post_id ) ) {
				if ( 0 === strpos( $post_id, 'term_' ) ) {
					return 'term';
				}
				if ( 0 === strpos( $post_id, 'user_' ) ) {
					return 'user';
				}
				if ( 0 === strpos( $post_id, 'comment_' ) ) {
					return 'comment';
				}
				if ( in_array( $post_id, array( 'options', 'option' ), true ) ) {
					return 'option';
				}
			}
			return 'post';
		}

		/**
		 * Get the numeric meta ID from an ACF post_id.
		 *
		 * @param int|string $post_id ACF post ID.
		 * @return int Numeric ID.
		 */
		private function get_meta_id( $post_id ) {
			if ( is_string( $post_id ) ) {
				$numeric = preg_replace( '/^(term_|user_|comment_)/', '', $post_id );
				if ( is_numeric( $numeric ) ) {
					return (int) $numeric;
				}
			}
			return (int) $post_id;
		}

		/**
		 * Delete field value and all sub-field meta.
		 *
		 * @param int   $post_id Post ID.
		 * @param array $field   Field settings.
		 * @return void
		 */
		public function delete_value( $post_id, $field ) {
			$count      = (int) get_post_meta( $post_id, $field['name'], true );
			$sub_fields = ! empty( $field['sub_fields'] ) ? $field['sub_fields'] : array();
			$meta_type  = $this->get_meta_type( $post_id );
			$meta_id    = $this->get_meta_id( $post_id );

			for ( $i = 0; $i < $count; $i++ ) {
				foreach ( $sub_fields as $sub_field ) {
					$meta_key     = $field['name'] . '_' . $i . '_' . $sub_field['name'];
					$key_meta_key = '_' . $meta_key;
					delete_metadata( $meta_type, $meta_id, $meta_key );
					delete_metadata( $meta_type, $meta_id, $key_meta_key );
				}
			}

			// Delete the primary count key.
			delete_metadata( $meta_type, $meta_id, $field['name'] );
			delete_metadata( $meta_type, $meta_id, '_' . $field['name'] );
		}
	}

endif;
