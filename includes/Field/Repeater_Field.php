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

namespace ACF_Repeater\Field;

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Repeater_Field')):

	/**
	 * Repeater field class.
	 *
	 * The class is namespaced under the plugin's own prefix to avoid
	 * collisions with ACF PRO's `acf_field_repeater` class. The field type
	 * key ("repeater") is defined by the $name property, so ACF registers it
	 * exactly as it would ACF PRO's repeater.
	 */
	class Repeater_Field extends \acf_field
	{

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
		public function initialize()
		{
			$this->name = 'repeater';
			$this->label = __('Repeater', 'advanced-repeater-for-custom-fields');
			$this->category = 'layout';
			$this->description = __('The Repeater field allows you to create a set of sub fields which can be repeated again and again whilst editing content.', 'advanced-repeater-for-custom-fields');
			$this->doc_url = 'https://www.advancedcustomfields.com/resources/repeater/';
			$this->pro = false; // This is NOT a PRO-only field when this plugin is active.
			$this->defaults = array(
				'sub_fields' => array(),
				'min' => 0,
				'max' => 0,
				'min_rows' => 0,
				'max_rows' => 0,
				'layout' => 'table',
				'button_label' => '',
				'collapsed' => '',
				'sortable' => 1,
				'duplicate' => 1,
				'delete_confirm' => 1,
			);
			$this->l10n = array(
				'min_rows' => __('minimum rows required', 'advanced-repeater-for-custom-fields'),
				'max_rows' => __('maximum rows allowed', 'advanced-repeater-for-custom-fields'),
			);

			// Preserve pre-loaded sub-field values inside repeater rows during render.
			add_filter('acf/load_value', array($this, 'filter_load_value_sub_field'), 10, 3);
		}

		/**
		 * Filter to preserve sub-field values inside repeater rows.
		 *
		 * When ACF Free calls acf_get_value() during acf_render_field_wrap() for a sub-field,
		 * default get_post_meta() looks up postmeta by the sub-field's standalone name,
		 * returning empty and overwriting the value populated from repeater rows.
		 *
		 * @param mixed      $value   Loaded value.
		 * @param int|string $post_id Post ID.
		 * @param array      $field   Field settings.
		 * @return mixed
		 */
		public function filter_load_value_sub_field($value, $post_id, $field)
		{
			if (!empty($field['prefix']) && 0 === strpos($field['prefix'], 'acf[') && isset($field['value']) && null !== $field['value'] && '' !== $field['value']) {
				return $field['value'];
			}
			return $value;
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
		public function render_field($field)
		{
			$this->is_rendering = true;
			$this->post_id = acf_get_valid_post_id();

			// Parse input name to retrieve meta field name (ACF PRO pattern)
			$input_name = $field['name'];
			if (strpos($input_name, 'acf[') === 0 || strpos($input_name, '[') !== false) {
				$parsed_name = $this->get_field_name_from_input_name($input_name);
				if ($parsed_name) {
					$field['name'] = $parsed_name;
				}
			}

			if (empty($field['sub_fields'])) {
				$field = $this->load_field($field);
			}

			if (!isset($field['value']) || !is_array($field['value'])) {
				$field['value'] = $this->load_value(null, $this->post_id, $field);
			}

			$layout = !empty($field['layout']) ? $field['layout'] : 'table';
			$value = is_array($field['value']) ? $field['value'] : array();
			$sub_fields = !empty($field['sub_fields']) ? $field['sub_fields'] : array();

			// Rich field types (WYSIWYG, gallery, flexible, etc.) cannot fit in table
			// cells — automatically switch to row layout for those.
			$rich_types = array('wysiwyg', 'gallery', 'flexible_content', 'repeater', 'google_map', 'clone');
			$has_rich = false;
			foreach ($sub_fields as $sf) {
				if (in_array($sf['type'] ?? '', $rich_types, true)) {
					$has_rich = true;
					break;
				}
			}
			if ('table' === $layout && $has_rich) {
				$layout = 'row';
			}

			// Always add the clone row placeholder (hidden).
			$value['acfcloneindex'] = array();

			$min_rows = (int) ($field['min'] > 0 ? $field['min'] : ($field['min_rows'] ?? 0));
			$max_rows = (int) ($field['max'] > 0 ? $field['max'] : ($field['max_rows'] ?? 0));
			$button_label = !empty($field['button_label']) ? $field['button_label'] : __('Add Row', 'advanced-repeater-for-custom-fields');
			$sortable = !empty($field['sortable']);
			$duplicate = !empty($field['duplicate']);
			$delete_confirm = !empty($field['delete_confirm']);
			$collapsed = $field['collapsed'] ?? '';

			// Wrapper data attributes for the JS controller.
			$wrapper_attrs = array(
				'data-field-key' => esc_attr($field['key']),
				'data-field-name' => esc_attr($field['name']),
				'data-min-rows' => $min_rows,
				'data-max-rows' => $max_rows,
				'data-button-label' => esc_attr($button_label),
				'data-collapsed-field' => esc_attr($collapsed),
				'data-sortable' => $sortable ? 'true' : 'false',
				'data-duplicate' => $duplicate ? 'true' : 'false',
				'data-delete-confirm' => $delete_confirm ? 'true' : 'false',
				'data-layout' => esc_attr($layout),
			);

			$attrs_str = '';
			foreach ($wrapper_attrs as $k => $v) {
				$attrs_str .= ' ' . $k . '="' . $v . '"';
			}

			echo '<div class="repeater-field-for-acf repeater-field-for-acf-' . esc_attr($layout) . '"' . rtrim($attrs_str) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if (empty($sub_fields)) {
				echo '<p class="acf-no-fields acf-cf"><span>' . esc_html__('Click the "+ Add Row" button below to start creating your content', 'advanced-repeater-for-custom-fields') . '</span></p>';
			} else {
				// All layouts now use the same clean stacked-row HTML.
				echo '<div class="repeater-field-for-acf-rows">';
				foreach ($value as $row_index => $row) {
					$this->render_row($field, $row, $row_index, $sub_fields);
				}
				echo '</div>';
			}

			// Add Row button footer.
			echo '<div class="repeater-field-for-acf-add-row">';
			echo '<a href="#" class="button acf-button blue repeater-field-for-acf-add-row-btn">';
			echo '<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>';
			echo esc_html($button_label);
			echo '</a>';
			if ($min_rows > 0) {
				echo '<span class="acf-min-rows" data-min="' . esc_attr($min_rows) . '">';
				/* translators: %d = minimum number of rows */
				printf(esc_html__('Minimum %d row(s) required', 'advanced-repeater-for-custom-fields'), (int) $min_rows);
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
		/**
		 * Render a single repeater row (unified across all layouts).
		 *
		 * HTML structure matches ACF PRO "row" layout:
		 *   .acf-row
		 *     .acf-row-handle.order   — row number + drag handle + collapse toggle
		 *     .acf-fields             — all sub-fields stacked vertically
		 *     .acf-row-handle.remove  — remove / duplicate buttons
		 *
		 * @param array      $field      Parent field.
		 * @param array      $row        Row data.
		 * @param string|int $row_index  Row index or 'acfcloneindex'.
		 * @param array      $sub_fields Sub-fields.
		 * @return void
		 */
		private function render_row($field, $row, $row_index, $sub_fields)
		{
			$is_clone = 'acfcloneindex' === $row_index;
			$classes = 'acf-row' . ($is_clone ? ' acf-clone' : '');
			$style = $is_clone ? 'display:none' : '';
			$row_num = $is_clone ? '' : (string) ((int) $row_index + 1);
			$collapsed_key = $field['collapsed'] ?? '';

			// Get collapsed title if set.
			$collapsed_title = '';
			if ($collapsed_key && !empty($sub_fields)) {
				foreach ($sub_fields as $sf) {
					if ($sf['key'] === $collapsed_key || $sf['name'] === $collapsed_key) {
						$val = $row[$sf['key']] ?? $row[$sf['name']] ?? '';
						if (is_string($val) || is_numeric($val)) {
							$collapsed_title = (string) $val;
						}
						break;
					}
				}
			}
			?>
			<div class="<?php echo esc_attr($classes); ?>" data-id="<?php echo esc_attr($row_index); ?>" <?php echo $style ? ' style="' . esc_attr($style) . '"' : ''; ?>>

				<!-- Left: drag handle + row number + optional collapse icon -->
				<div class="acf-row-handle order"
					title="<?php echo $collapsed_key ? esc_attr__('Click to toggle collapse / Drag to reorder', 'advanced-repeater-for-custom-fields') : esc_attr__('Drag to reorder', 'advanced-repeater-for-custom-fields'); ?>">
					<span class="acf-sortable-handle"
						title="<?php esc_attr_e('Drag to reorder', 'advanced-repeater-for-custom-fields'); ?>"></span>
					<span class="acf-row-number"><?php echo esc_html($row_num); ?></span>
					<?php if ($collapsed_key): ?>
						<a class="acf-icon -collapse small" href="#"
							title="<?php esc_attr_e('Click to toggle row', 'advanced-repeater-for-custom-fields'); ?>" data-event="collapse-row">
						</a>
						<span class="acf-row-compact-title"><?php echo esc_html($collapsed_title); ?></span>
					<?php endif; ?>
				</div>

				<!-- Centre: sub-fields stacked with labels -->
				<div class="acf-fields -clear">
					<?php foreach ($sub_fields as $sub_field): ?>
						<?php
						$sub_field = $this->prepare_sub_field_for_render($field, $sub_field, $row, $row_index);
						acf_render_field_wrap($sub_field);
						?>
					<?php endforeach; ?>
				</div>

				<!-- Right: remove / duplicate buttons -->
				<div class="acf-row-handle remove">
					<a class="acf-remove-row" href="#" title="<?php esc_attr_e('Remove row', 'advanced-repeater-for-custom-fields'); ?>"
						aria-label="<?php esc_attr_e('Remove row', 'advanced-repeater-for-custom-fields'); ?>">
						<span class="dashicons dashicons-minus" aria-hidden="true"></span>
					</a>
					<?php if (!$is_clone && !empty($field['duplicate'])): ?>
						<a class="acf-duplicate-row" href="#" title="<?php esc_attr_e('Duplicate row', 'advanced-repeater-for-custom-fields'); ?>"
							aria-label="<?php esc_attr_e('Duplicate row', 'advanced-repeater-for-custom-fields'); ?>">
							<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
						</a>
					<?php endif; ?>
				</div>

			</div><!-- .acf-row -->
			<?php
		}

		/* Legacy method aliases kept to avoid breaking any subclass overrides. */
		private function render_table($field, $value, $sub_fields)
		{
			echo '<div class="repeater-field-for-acf-rows">';
			foreach ($value as $row_index => $row) {
				$this->render_row($field, $row, $row_index, $sub_fields);
			}
			echo '</div>';
		}

		private function render_block($field, $value, $sub_fields)
		{
			echo '<div class="repeater-field-for-acf-rows">';
			foreach ($value as $row_index => $row) {
				$this->render_row($field, $row, $row_index, $sub_fields);
			}
			echo '</div>';
		}

		private function render_table_row($field, $row, $row_index, $sub_fields)
		{
			$this->render_row($field, $row, $row_index, $sub_fields);
		}

		private function render_block_row($field, $row, $row_index, $sub_fields)
		{
			$this->render_row($field, $row, $row_index, $sub_fields);
		}

		/**
		 * Prepare a sub-field array for rendering inside a repeater row.
		 *
		 * Correctly chains the prefix so nested repeaters generate proper input names:
		 *   acf[{parent_key}][{row_index}][{nested_key}][{nested_row_index}][{sub_key}]
		 *
		 * @param array      $parent_field Parent field settings.
		 * @param array      $sub_field    Sub-field settings.
		 * @param array      $row          Current row data.
		 * @param string|int $row_index    Row index (or 'acfcloneindex').
		 * @return array Modified sub-field.
		 */
		private function prepare_sub_field_for_render($parent_field, $sub_field, $row, $row_index)
		{
			if ('repeater' === $sub_field['type'] && empty($sub_field['sub_fields'])) {
				$sub_field = $this->load_field($sub_field);
			}

			$sub_field['value'] =
				$row[$sub_field['key']]
				?? $row[$sub_field['name']]
				?? $sub_field['default_value']
				?? '';

			// Chain prefix from parent to support nested repeaters to any depth.
			$parent_prefix = !empty($parent_field['prefix']) ? $parent_field['prefix'] : 'acf';
			$parent_key = $parent_field['key'];
			$sub_field['prefix'] = $parent_prefix . '[' . $parent_key . '][' . $row_index . ']';

			// Keep _name as human name.
			$sub_field['_name'] = $sub_field['name'];

			// Ensure sub-field has proper parent reference.
			$sub_field['parent'] = $parent_key;

			// Mark as not yet prepared so acf_prepare_field() runs fresh.
			unset($sub_field['_prepare']);

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
		public function render_field_settings($field)
		{
			// Sub-fields list (uses ACF's built-in field group sub-fields view).
			$args = array(
				'fields' => !empty($field['sub_fields']) ? $field['sub_fields'] : array(),
				'parent' => $field['ID'] ?? 0,
				'is_subfield' => true,
			);
			?>
			<div class="acf-field acf-field-setting-sub_fields" data-setting="repeater" data-name="sub_fields">
				<div class="acf-label">
					<label><?php esc_html_e('Sub Fields', 'advanced-repeater-for-custom-fields'); ?></label>
				</div>
				<div class="acf-input">
					<?php acf_get_view('acf-field-group/fields', $args); ?>
				</div>
			</div>
			<?php

			// Min Rows.
			acf_render_field_setting(
				$field,
				array(
					'label' => __('Minimum Rows', 'advanced-repeater-for-custom-fields'),
					'instructions' => __('Minimum number of rows required. Leave blank for no minimum.', 'advanced-repeater-for-custom-fields'),
					'type' => 'number',
					'name' => 'min',
					'ui' => 0,
					'min' => 0,
				)
			);

			// Max Rows.
			acf_render_field_setting(
				$field,
				array(
					'label' => __('Maximum Rows', 'advanced-repeater-for-custom-fields'),
					'instructions' => __('Maximum number of rows allowed. Leave blank for no maximum.', 'advanced-repeater-for-custom-fields'),
					'type' => 'number',
					'name' => 'max',
					'ui' => 0,
					'min' => 0,
				)
			);

			// Layout.
			acf_render_field_setting(
				$field,
				array(
					'label' => __('Layout', 'advanced-repeater-for-custom-fields'),
					'instructions' => __('Select the style used to display the repeater rows.', 'advanced-repeater-for-custom-fields'),
					'type' => 'radio',
					'name' => 'layout',
					'choices' => array(
						'table' => __('Table', 'advanced-repeater-for-custom-fields'),
						'block' => __('Block', 'advanced-repeater-for-custom-fields'),
						'row' => __('Row', 'advanced-repeater-for-custom-fields'),
					),
					'layout' => 'horizontal',
				)
			);

			// Button Label.
			acf_render_field_setting(
				$field,
				array(
					'label' => __('Add Row Button Label', 'advanced-repeater-for-custom-fields'),
					'instructions' => __('Text shown on the "Add Row" button.', 'advanced-repeater-for-custom-fields'),
					'type' => 'text',
					'name' => 'button_label',
				)
			);

			// Collapsed.
			$choices = array('' => '— ' . __('None', 'advanced-repeater-for-custom-fields') . ' —');
			if (!empty($field['sub_fields'])) {
				foreach ($field['sub_fields'] as $sf) {
					$choices[$sf['key']] = $sf['label'];
				}
			}
			acf_render_field_setting(
				$field,
				array(
					'label' => __('Collapsed', 'advanced-repeater-for-custom-fields'),
					'instructions' => __('Select a sub field to show when the row is collapsed.', 'advanced-repeater-for-custom-fields'),
					'type' => 'select',
					'name' => 'collapsed',
					'choices' => $choices,
					'allow_null' => 0,
					'ui' => 0,
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
		 * @param mixed      $value   Raw meta value (row count) or already loaded array.
		 * @param int|string $post_id Post/term/user ID.
		 * @param array      $field   Field settings.
		 * @return array Array of rows.
		 */
		public function get_field_name_from_input_name($input_name)
		{
			$parts = array();
			preg_match_all('/\[([^\]]*)\]/', is_null($input_name) ? '' : $input_name, $parts);

			if (!isset($parts[1]) || empty($parts[1])) {
				return false;
			}

			$field_keys = $parts[1];
			$name_parts = array();

			foreach ($field_keys as $field_key) {
				if ('acfcloneindex' === $field_key) {
					$name_parts[] = 'acfcloneindex';
					continue;
				}

				if (0 === strpos($field_key, 'row-')) {
					$row_num = substr($field_key, 4);
					if (is_numeric($row_num)) {
						$name_parts[] = (int) $row_num;
						continue;
					}
				}

				if (is_numeric($field_key)) {
					$name_parts[] = (int) $field_key;
					continue;
				}

				if (0 === strpos($field_key, 'field_')) {
					$field = acf_get_field($field_key);
					$name_parts[] = ($field && !empty($field['name'])) ? $field['name'] : $field_key;
					continue;
				}

				$name_parts[] = $field_key;
			}

			return implode('_', $name_parts);
		}

		/**
		 * Filters the field $value after it is loaded from the database.
		 *
		 * Reads data in ACF PRO-compatible flat meta format:
		 *   {name}                   → row count (int)
		 *   {name}_{i}_{sub_name}   → sub-field value
		 *
		 * @param mixed      $value   Raw meta value (row count) or already loaded array.
		 * @param int|string $post_id Post/term/user ID.
		 * @param array      $field   Field settings.
		 * @return array Array of rows.
		 */
		public function load_value($value, $post_id, $field)
		{
			if (is_array($value)) {
				return $value;
			}

			if (empty($value) || !is_numeric($value)) {
				$value = acf_get_metadata_by_field($post_id, $field);
				if (empty($value) || !is_numeric($value)) {
					$value = $this->get_meta_value($post_id, $field['name']);
				}
			}

			if (empty($value) || !is_numeric($value)) {
				return array();
			}

			if (empty($field['sub_fields'])) {
				$field = $this->load_field($field);
			}

			if (empty($field['sub_fields'])) {
				return array();
			}

			$count = (int) $value;
			$rows = array();

			for ($i = 0; $i < $count; $i++) {
				$rows[$i] = array();

				foreach ($field['sub_fields'] as $sub_field) {
					if (acf_is_empty($sub_field['name'])) {
						continue;
					}

					$_sub_field = $sub_field;
					$_sub_field['name'] = "{$field['name']}_{$i}_{$sub_field['name']}";
					$sub_value = acf_get_value($post_id, $_sub_field);

					$rows[$i][$sub_field['key']] = $sub_value;
					$rows[$i][$sub_field['name']] = $sub_value;
				}
			}

			return $rows;
		}

		/**
		 * Update a single row's subfield values (ACF PRO compatible).
		 *
		 * @param array      $row     Row array.
		 * @param int        $i       Row index.
		 * @param array      $field   Field settings.
		 * @param int|string $post_id Post ID.
		 * @return bool
		 */
		public function update_row($row, $i, $field, $post_id)
		{
			if (!is_array($row) || empty($field['sub_fields'])) {
				return false;
			}

			foreach ($field['sub_fields'] as $sub_field) {
				$value = null;

				if (array_key_exists($sub_field['key'], $row)) {
					$value = $row[$sub_field['key']];
				} elseif (array_key_exists($sub_field['name'], $row)) {
					$value = $row[$sub_field['name']];
				} else {
					continue;
				}

				$_sub_field = $sub_field;
				$_sub_field['name'] = "{$field['name']}_{$i}_{$sub_field['name']}";

				acf_update_value($value, $post_id, $_sub_field);
			}

			return true;
		}

		/**
		 * Delete a single row's subfield values (ACF PRO compatible).
		 *
		 * @param int        $i       Row index.
		 * @param array      $field   Field settings.
		 * @param int|string $post_id Post ID.
		 * @return bool
		 */
		public function delete_row($i, $field, $post_id)
		{
			if (empty($field['sub_fields'])) {
				return false;
			}

			foreach ($field['sub_fields'] as $sub_field) {
				$_sub_field = $sub_field;
				$_sub_field['name'] = "{$field['name']}_{$i}_{$sub_field['name']}";

				acf_delete_value($post_id, $_sub_field);
			}

			return true;
		}

		/**
		 * Update (save) the repeater field value (ACF PRO compatible).
		 *
		 * @param mixed      $value   Submitted rows data (array).
		 * @param int|string $post_id Post/term/user ID.
		 * @param array      $field   Field settings.
		 * @return mixed Processed count value for ACF's meta save.
		 */
		public function update_value($value, $post_id, $field)
		{
			if (empty($field['sub_fields'])) {
				$field = $this->load_field($field);
			}

			if (empty($field['sub_fields'])) {
				return $value;
			}

			if (!is_array($value)) {
				$value = array();
			}

			if (isset($value['acfcloneindex'])) {
				unset($value['acfcloneindex']);
			}

			$old_value = (int) acf_get_metadata_by_field($post_id, $field);
			if (!$old_value) {
				$old_value = (int) $this->get_meta_value($post_id, $field['name']);
			}

			$new_value = 0;
			$i = -1;

			foreach ($value as $row) {
				if (!is_array($row)) {
					continue;
				}

				++$i;
				$this->update_row($row, $i, $field, $post_id);
				++$new_value;
			}

			// Delete removed rows.
			if ($old_value > $new_value) {
				for ($i = $new_value; $i < $old_value; $i++) {
					$this->delete_row($i, $field, $post_id);
				}
			}

			if (empty($new_value)) {
				$new_value = '';
			}

			return $new_value;
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
		public function format_value($value, $post_id, $field)
		{
			if (!is_array($value) || empty($value)) {
				return array();
			}

			$sub_fields = !empty($field['sub_fields']) ? $field['sub_fields'] : array();

			foreach ($value as $row_index => $row) {
				if (!is_array($row)) {
					continue;
				}
				foreach ($sub_fields as $sub_field) {
					if (array_key_exists($sub_field['key'], $row)) {
						$sub_field['value'] = $row[$sub_field['key']];
						$value[$row_index][$sub_field['name']] = acf_format_value(
							$row[$sub_field['key']],
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
		 * Checks min/max row counts, required status, and validates sub-fields.
		 *
		 * @param bool   $valid Whether value is valid.
		 * @param mixed  $value Submitted value.
		 * @param array  $field Field settings.
		 * @string $input Input name.
		 * @return bool|string True if valid, error message string if not.
		 */
		public function validate_value($valid, $value, $field, $input)
		{
			if (!is_array($value)) {
				$value = array();
			}

			// Strip clone row from count.
			$rows = $value;
			if (isset($rows['acfcloneindex'])) {
				unset($rows['acfcloneindex']);
			}

			$count = count($rows);
			$min = (int) ($field['min'] ?? $field['min_rows'] ?? 0);
			$max = (int) ($field['max'] ?? $field['max_rows'] ?? 0);

			if ($min > 0 && $count < $min) {
				return sprintf(
					/* translators: 1: label, 2: minimum */
					__('%1$s requires a minimum of %2$s rows', 'advanced-repeater-for-custom-fields'),
					'<strong>' . esc_html($field['label']) . '</strong>',
					'<strong>' . $min . '</strong>'
				);
			}

			if ($max > 0 && $count > $max) {
				return sprintf(
					/* translators: 1: label, 2: maximum */
					__('%1$s requires a maximum of %2$s rows', 'advanced-repeater-for-custom-fields'),
					'<strong>' . esc_html($field['label']) . '</strong>',
					'<strong>' . $max . '</strong>'
				);
			}

			if (!empty($field['required']) && 0 === $count) {
				return sprintf(
					/* translators: %s: label */
					__('%s value is required', 'advanced-repeater-for-custom-fields'),
					'<strong>' . esc_html($field['label']) . '</strong>'
				);
			}

			// Validate sub-fields inside submitted rows.
			if ($count > 0 && !empty($field['sub_fields'])) {
				foreach ($rows as $i => $row) {
					if (!is_array($row)) {
						continue;
					}
					foreach ($field['sub_fields'] as $sub_field) {
						$sub_input = $input . '[' . $i . '][' . $sub_field['key'] . ']';
						$sub_value = $row[$sub_field['key']] ?? $row[$sub_field['name']] ?? null;

						acf_validate_value($sub_value, $sub_field, $sub_input);
					}
				}
			}

			return $valid;
		}

		// =====================================================================
		// Sub-fields — Load, Duplicate, Export, Import
		// =====================================================================

		/**
		 * Load field — populate sub_fields recursively from database.
		 *
		 * @param array $field Field settings.
		 * @return array Modified field.
		 */
		public function load_field($field)
		{
			// Load sub-fields that belong to this repeater.
			$sub_fields = acf_get_fields($field);
			if ($sub_fields) {
				foreach ($sub_fields as $i => $sub_field) {
					if ('repeater' === $sub_field['type']) {
						$sub_fields[$i] = $this->load_field($sub_field);
					}
				}
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
		public function duplicate_field($field)
		{
			$sub_fields = acf_extract_var($field, 'sub_fields');
			$field = acf_update_field($field);
			acf_duplicate_fields($sub_fields, $field['ID']);
			return $field;
		}

		/**
		 * Prepare field for export (ACF JSON).
		 *
		 * @param array $field Field settings.
		 * @return array Prepared field.
		 */
		public function prepare_field_for_export($field)
		{
			if (!empty($field['sub_fields'])) {
				foreach ($field['sub_fields'] as $i => $sub_field) {
					$field['sub_fields'][$i] = acf_prepare_field_for_export($sub_field);
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
		public function prepare_field_for_import($field)
		{
			if (!empty($field['sub_fields'])) {
				foreach ($field['sub_fields'] as $i => $sub_field) {
					$field['sub_fields'][$i] = acf_prepare_field_for_import($sub_field);
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
		public function prepare_field($field)
		{
			if (empty($field['button_label'])) {
				$field['button_label'] = __('Add Row', 'advanced-repeater-for-custom-fields');
			}
			if (empty($field['min'])) {
				$field['min'] = 0;
			}
			if (empty($field['max'])) {
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
		public function translate_field($field)
		{
			if (!empty($field['button_label'])) {
				$field['button_label'] = acf_translate($field['button_label']);
			}
			return $field;
		}

		/**
		 * Read metadata using acf_get_metadata() with fallback to WordPress functions.
		 * Handles posts, terms (category_123, term_123), users (user_123), options, comments.
		 *
		 * @param int|string $post_id Post/term/user ID.
		 * @param string     $name    Meta key.
		 * @return mixed
		 */
		private function get_meta_value($post_id, $name)
		{
			if (function_exists('acf_get_metadata')) {
				return acf_get_metadata($post_id, $name);
			}
			return get_metadata($this->get_meta_type($post_id), $this->get_meta_id($post_id), $name, true);
		}

		/**
		 * Update metadata using acf_update_metadata() with fallback.
		 *
		 * @param int|string $post_id Post/term/user ID.
		 * @param string     $name    Meta key.
		 * @param mixed      $value   Value to update.
		 * @return bool|int
		 */
		private function update_meta_value($post_id, $name, $value)
		{
			if (function_exists('acf_update_metadata')) {
				return acf_update_metadata($post_id, $name, $value);
			}
			return update_metadata($this->get_meta_type($post_id), $this->get_meta_id($post_id), $name, $value);
		}

		/**
		 * Delete metadata using acf_delete_metadata() with fallback.
		 *
		 * @param int|string $post_id Post/term/user ID.
		 * @param string     $name    Meta key.
		 * @return bool
		 */
		private function delete_meta_value($post_id, $name)
		{
			if (function_exists('acf_delete_metadata')) {
				return acf_delete_metadata($post_id, $name);
			}
			return delete_metadata($this->get_meta_type($post_id), $this->get_meta_id($post_id), $name);
		}

		/**
		 * Get the meta object type based on post_id context.
		 *
		 * @param int|string $post_id ACF post ID (may be prefixed).
		 * @return string 'post', 'term', 'user', 'comment', or 'option'.
		 */
		private function get_meta_type($post_id)
		{
			if (is_string($post_id)) {
				if (0 === strpos($post_id, 'term_') || 0 === strpos($post_id, 'category_') || preg_match('/^[a-z0-9_\-]+_\d+$/i', $post_id)) {
					return 'term';
				}
				if (0 === strpos($post_id, 'user_')) {
					return 'user';
				}
				if (0 === strpos($post_id, 'comment_')) {
					return 'comment';
				}
				if (in_array($post_id, array('options', 'option'), true)) {
					return 'option';
				}
			}
			return 'post';
		}

		/**
		 * Get the numeric meta ID from an ACF post_id.
		 *
		 * @param int|string $post_id ACF post ID.
		 * @return int|string Numeric ID or string.
		 */
		private function get_meta_id($post_id)
		{
			if (is_string($post_id)) {
				$numeric = preg_replace('/^([a-z0-9_\-]+_)/i', '', $post_id);
				if (is_numeric($numeric)) {
					return (int) $numeric;
				}
			}
			return (int) $post_id;
		}

		/**
		 * Delete field value and all sub-field meta.
		 *
		 * @param int|string $post_id Post ID.
		 * @param array      $field   Field settings.
		 * @return void
		 */
		public function delete_value($post_id, $field)
		{
			$count = (int) $this->get_meta_value($post_id, $field['name']);
			$sub_fields = !empty($field['sub_fields']) ? $field['sub_fields'] : array();

			for ($i = 0; $i < $count; $i++) {
				foreach ($sub_fields as $sub_field) {
					$meta_key = $field['name'] . '_' . $i . '_' . $sub_field['name'];
					if ('repeater' === $sub_field['type']) {
						$tmp_field = $sub_field;
						$tmp_field['name'] = $meta_key;
						$this->delete_value($post_id, $tmp_field);
					} else {
						$this->delete_meta_value($post_id, $meta_key);
						$this->delete_meta_value($post_id, '_' . $meta_key);
					}
				}
			}

			// Delete the primary count key.
			$this->delete_meta_value($post_id, $field['name']);
			$this->delete_meta_value($post_id, '_' . $field['name']);
		}
	}

endif;
