<?php
/**
 * REST API integration for ACF Repeater.
 *
 * @package Raeen_Repeater\Admin
 */

namespace Raeen_Repeater\Admin;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Rest_API
 *
 * Handles REST API routes for repeater field data.
 */
class Rest_API
{

	/**
	 * Namespace for REST API routes.
	 *
	 * @var string
	 */
	private const NAMESPACE = 'raeen-repeater-field-for-acf/v1';


	/**
	 * Settings instance.
	 *
	 * @var Settings|null
	 */
	private ?Settings $settings = null;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->settings = new Settings();
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void
	{
		// Check if REST API is enabled in settings.
		if (!$this->settings->get_setting('rest_api_enabled', true)) {
			return;
		}

		// Register field for posts.
		register_rest_field(
			'post',
			'acf_repeater',
			array(
				'get_callback' => array($this, 'get_repeater_fields'),
				'update_callback' => array($this, 'update_repeater_fields'),
				'schema' => array($this, 'get_repeater_schema'),
			)
		);

		// Register field for pages.
		register_rest_field(
			'page',
			'acf_repeater',
			array(
				'get_callback' => array($this, 'get_repeater_fields'),
				'update_callback' => array($this, 'update_repeater_fields'),
				'schema' => array($this, 'get_repeater_schema'),
			)
		);

		// Register for all custom post types that support ACF.
		$post_types = get_post_types(array('show_in_rest' => true), 'objects');
		foreach ($post_types as $post_type) {
			if ($post_type->name !== 'post' && $post_type->name !== 'page') {
				register_rest_field(
					$post_type->name,
					'acf_repeater',
					array(
						'get_callback' => array($this, 'get_repeater_fields'),
						'update_callback' => array($this, 'update_repeater_fields'),
						'schema' => array($this, 'get_repeater_schema'),
					)
				);
			}
		}

		// Custom endpoints for repeater operations.
		register_rest_route(
			self::NAMESPACE ,
			'/repeater/(?P<field_key>[a-zA-Z0-9_]+)',
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array($this, 'get_repeater_field'),
				'permission_callback' => array($this, 'check_read_permission'),
				'args' => $this->get_route_args(array('field_key', 'post_id')),
			)
		);

		register_rest_route(
			self::NAMESPACE ,
			'/repeater/(?P<field_key>[a-zA-Z0-9_]+)/rows',
			array(
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => array($this, 'add_repeater_row'),
				'permission_callback' => array($this, 'check_edit_permission'),
				'args' => $this->get_route_args(array('field_key', 'post_id', 'row_data')),
			)
		);

		register_rest_route(
			self::NAMESPACE ,
			'/repeater/(?P<field_key>[a-zA-Z0-9_]+)/rows/(?P<row_index>\d+)',
			array(
				array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array($this, 'get_repeater_row'),
					'permission_callback' => array($this, 'check_read_permission'),
					'args' => $this->get_route_args(array('field_key', 'post_id', 'row_index')),
				),
				array(
					'methods' => \WP_REST_Server::EDITABLE,
					'callback' => array($this, 'update_repeater_row'),
					'permission_callback' => array($this, 'check_edit_permission'),
					'args' => $this->get_route_args(array('field_key', 'post_id', 'row_index', 'row_data')),
				),
				array(
					'methods' => \WP_REST_Server::DELETABLE,
					'callback' => array($this, 'delete_repeater_row'),
					'permission_callback' => array($this, 'check_edit_permission'),
					'args' => $this->get_route_args(array('field_key', 'post_id', 'row_index')),
				),
			)
		);
	}

	/**
	 * Get route arguments.
	 *
	 * @param array $keys Array of argument keys to return.
	 * @return array
	 */
	private function get_route_args(array $keys): array
	{
		$args = array(
			'field_key' => array(
				'validate_callback' => function ($param) {
					return preg_match('/^field_[a-zA-Z0-9_]+$/', $param);
				},
				'sanitize_callback' => 'sanitize_text_field',
			),
			'post_id' => array(
				'validate_callback' => function ($param) {
					return is_numeric($param);
				},
				'sanitize_callback' => 'absint',
			),
			'row_index' => array(
				'validate_callback' => function ($param) {
					return is_numeric($param) && $param >= 0;
				},
				'sanitize_callback' => 'absint',
			),
			'row_data' => array(
				'validate_callback' => function ($param) {
					return is_array($param);
				},
				'sanitize_callback' => array($this, 'sanitize_row_data'),
			),
		);

		return array_intersect_key($args, array_flip($keys));
	}

	/**
	 * Check read permission.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_read_permission(\WP_REST_Request $request): bool|\WP_Error
	{
		$post_id = $request->get_param('post_id') ?? 0;
		$post_id = (int) $post_id;

		if ($post_id > 0) {
			return current_user_can('read_post', $post_id);
		}

		return current_user_can('read');
	}

	/**
	 * Check edit permission.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_edit_permission(\WP_REST_Request $request): bool|\WP_Error
	{
		$post_id = $request->get_param('post_id') ?? 0;
		$post_id = (int) $post_id;

		if ($post_id > 0) {
			return current_user_can('edit_post', $post_id);
		}

		return current_user_can('edit_posts');
	}

	/**
	 * Get repeater fields for a post.
	 *
	 * @param array           $object  Post object.
	 * @param string          $field_name Field name.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	public function get_repeater_fields(array $object, string $field_name, \WP_REST_Request $request): array
	{
		$post_id = $object['id'] ?? 0;
		if (!is_numeric($post_id) || (int) $post_id <= 0) {
			return array();
		}

		$repeater_fields = $this->get_all_repeater_fields((int) $post_id);
		return $repeater_fields;
	}

	/**
	 * Get all repeater fields for a post.
	 *
	 * @param int|string $post_id Post ID.
	 * @return array
	 */
	private function get_all_repeater_fields($post_id): array
	{
		if (!is_numeric($post_id) || (int) $post_id <= 0) {
			return array();
		}

		$post_id = (int) $post_id;
		$result = array();

		// Get all ACF fields for this post.
		$fields = acf_get_fields($post_id);
		if (!$fields) {
			return $result;
		}

		foreach ($fields as $field) {
			if (isset($field['type']) && $field['type'] === 'repeater') {
				$value = get_field($field['name'], $post_id, false);
				if (is_array($value)) {
					$result[$field['name']] = $this->format_repeater_value($value, $field);
				}
			}
		}

		return $result;
	}

	/**
	 * Format repeater value for REST API.
	 *
	 * @param array $value Field value.
	 * @param array $field Field config.
	 * @return array
	 */
	private function format_repeater_value(array $value, array $field): array
	{
		$formatted = array(
			'field_key' => $field['key'] ?? '',
			'field_name' => $field['name'] ?? '',
			'layout' => $field['layout'] ?? 'table',
			'button_label' => $field['button_label'] ?? '',
			'min_rows' => (int) ($field['min_rows'] ?? 0),
			'max_rows' => (int) ($field['max_rows'] ?? 0),
			'collapsed' => $field['collapsed'] ?? '',
			'sortable' => (bool) ($field['sortable'] ?? false),
			'duplicate' => (bool) ($field['duplicate'] ?? false),
			'delete_confirm' => (bool) ($field['delete_confirm'] ?? false),
			'rows' => array(),
		);

		foreach ($value as $index => $row) {
			if (is_array($row)) {
				$formatted['rows'][] = $this->format_row_data($row, $field, $index);
			}
		}

		return $formatted;
	}

	/**
	 * Format row data.
	 *
	 * @param array $row Row data.
	 * @param array $field Field config.
	 * @param int   $index Row index.
	 * @return array
	 */
	private function format_row_data(array $row, array $field, int $index): array
	{
		$sub_fields = $field['sub_fields'] ?? array();
		$formatted_row = array(
			'index' => $index,
			'id' => $row['acf_repeater_row_id'] ?? uniqid('row_'),
			'data' => array(),
		);

		foreach ($sub_fields as $sub_field) {
			$name = $sub_field['name'] ?? '';
			if ($name && isset($row[$name])) {
				$formatted_row['data'][$name] = $this->format_field_value($row[$name], $sub_field);
			}
		}

		return $formatted_row;
	}

	/**
	 * Format field value based on type.
	 *
	 * @param mixed $value Field value.
	 * @param array $field Field config.
	 * @return mixed
	 */
	private function format_field_value(mixed $value, array $field): mixed
	{
		$type = $field['type'] ?? 'text';

		switch ($type) {
			case 'image':
			case 'file':
				if (is_array($value) && isset($value['ID'])) {
					return $value;
				}
				if (is_numeric($value)) {
					return (int) $value;
				}
				return $value;

			case 'gallery':
				if (is_array($value)) {
					return array_map(
						function ($item) {
							return is_array($item) ? $item : (is_numeric($item) ? (int) $item : $item);
						},
						$value
					);
				}
				return $value;

			case 'checkbox':
			case 'select':
			case 'radio':
			case 'true_false':
				return $value;

			case 'wysiwyg':
				return is_string($value) ? wp_kses_post($value) : $value;

			default:
				return $value;
		}
	}

	/**
	 * Update repeater fields for a post.
	 *
	 * @param mixed           $value   New value.
	 * @param string          $field_name Field name.
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function update_repeater_fields($value, string $field_name, \WP_REST_Request $request): bool
	{
		$post_id = $request->get_param('id') ?? 0;
		if (!$post_id) {
			return false;
		}

		// Get the field to validate.
		$field = acf_get_field($field_name, $post_id);
		if (!$field || $field['type'] !== 'repeater') {
			return false;
		}

		// Sanitize and validate.
		$sanitized = $this->sanitize_repeater_value($value, $field);

		// Update the field.
		return update_field($field['key'], $sanitized, $post_id);
	}

	/**
	 * Sanitize repeater value.
	 *
	 * @param mixed $value Value to sanitize.
	 * @param array $field Field config.
	 * @return array
	 */
	private function sanitize_repeater_value(mixed $value, array $field): array
	{
		if (!is_array($value)) {
			return array();
		}

		$sub_fields = $field['sub_fields'] ?? array();
		$sanitized = array();

		foreach ($value as $index => $row) {
			if (!is_array($row)) {
				continue;
			}

			$sanitized_row = array();
			foreach ($sub_fields as $sub_field) {
				$name = $sub_field['name'] ?? '';
				if ($name && isset($row[$name])) {
					$sanitized_row[$name] = $this->sanitize_field_value($row[$name], $sub_field);
				}
			}

			// Preserve row ID.
			if (isset($row['acf_repeater_row_id'])) {
				$sanitized_row['acf_repeater_row_id'] = sanitize_text_field($row['acf_repeater_row_id']);
			}

			$sanitized[] = $sanitized_row;
		}

		return $sanitized;
	}

	/**
	 * Sanitize field value based on type.
	 *
	 * @param mixed $value Value to sanitize.
	 * @param array $field Field config.
	 * @return mixed
	 */
	private function sanitize_field_value(mixed $value, array $field): mixed
	{
		$type = $field['type'] ?? 'text';

		switch ($type) {
			case 'text':
			case 'textarea':
			case 'email':
			case 'url':
			case 'number':
			case 'password':
				return is_string($value) ? sanitize_text_field($value) : $value;

			case 'wysiwyg':
				return is_string($value) ? wp_kses_post($value) : $value;

			case 'select':
			case 'radio':
			case 'checkbox':
				if (is_array($value)) {
					return array_map('sanitize_text_field', $value);
				}
				return sanitize_text_field($value);

			case 'true_false':
				return (bool) $value;

			case 'image':
			case 'file':
				return is_numeric($value) ? (int) $value : $value;

			case 'date_picker':
			case 'time_picker':
			case 'datetime_picker':
				return is_string($value) ? sanitize_text_field($value) : $value;

			case 'color_picker':
				return is_string($value) ? sanitize_hex_color($value) : $value;

			case 'link':
				return is_array($value) ? $value : array();

			default:
				return $value;
		}
	}

	/**
	 * Sanitize row data from REST request.
	 *
	 * @param array $data Row data.
	 * @return array
	 */
	public function sanitize_row_data(array $data): array
	{
		$sanitized = array();
		foreach ($data as $key => $value) {
			$sanitized[sanitize_key($key)] = $this->sanitize_value_recursive($value);
		}
		return $sanitized;
	}

	/**
	 * Recursively sanitize value.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return mixed
	 */
	private function sanitize_value_recursive(mixed $value): mixed
	{
		if (is_array($value)) {
			return array_map(array($this, 'sanitize_value_recursive'), $value);
		}
		if (is_string($value)) {
			return sanitize_text_field($value);
		}
		if (is_numeric($value)) {
			return $value + 0;
		}
		if (is_bool($value)) {
			return $value;
		}
		return $value;
	}

	/**
	 * Get a single repeater field.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_repeater_field(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		$field_key = $request->get_param('field_key');
		$post_id = $request->get_param('post_id') ?? 0;

		if (!$field_key || !$post_id) {
			return new \WP_Error('missing_params', __('Field key and post ID are required.', 'raeen-repeater-field-for-acf'), array('status' => 400));
		}

		$field = acf_get_field($field_key);
		if (!$field || $field['type'] !== 'repeater') {
			return new \WP_Error('not_found', __('Repeater field not found.', 'raeen-repeater-field-for-acf'), array('status' => 404));
		}

		$value = get_field($field['name'], $post_id, false);
		if (!is_array($value)) {
			$value = array();
		}

		$formatted = $this->format_repeater_value($value, $field);

		return rest_ensure_response($formatted);
	}

	/**
	 * Get a single repeater row.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_repeater_row(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		$field_key = $request->get_param('field_key');
		$post_id = $request->get_param('post_id') ?? 0;
		$row_index = $request->get_param('row_index') ?? 0;

		if (!$field_key || !$post_id) {
			return new \WP_Error('missing_params', __('Field key and post ID are required.', 'raeen-repeater-field-for-acf'), array('status' => 400));
		}

		$field = acf_get_field($field_key);
		if (!$field || $field['type'] !== 'repeater') {
			return new \WP_Error('not_found', __('Repeater field not found.', 'raeen-repeater-field-for-acf'), array('status' => 404));
		}

		$value = get_field($field['name'], $post_id, false);
		if (!is_array($value) || !isset($value[$row_index])) {
			return new \WP_Error('not_found', __('Row not found.', 'raeen-repeater-field-for-acf'), array('status' => 404));
		}

		$formatted = $this->format_row_data($value[$row_index], $field, $row_index);

		return rest_ensure_response($formatted);
	}

	/**
	 * Add a new repeater row.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_repeater_row(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		$field_key = $request->get_param('field_key');
		$post_id = $request->get_param('post_id') ?? 0;
		$row_data = $request->get_param('row_data') ?? array();

		if (!$field_key || !$post_id) {
			return new \WP_Error('missing_params', __('Field key, post ID, and row data are required.', 'raeen-repeater-field-for-acf'), array('status' => 400));
		}

		$field = acf_get_field($field_key);
		if (!$field || $field['type'] !== 'repeater') {
			return new \WP_Error('not_found', __('Repeater field not found.', 'raeen-repeater-field-for-acf'), array('status' => 404));
		}

		// Check max rows.
		$max_rows = (int) ($field['max_rows'] ?? 0);
		if ($max_rows > 0) {
			$current_rows = get_field($field['name'], $post_id, false);
			if (is_array($current_rows) && count($current_rows) >= $max_rows) {
				/* translators: %d: maximum number of rows */
				return new \WP_Error('max_rows', sprintf(__('Maximum number of rows (%d) reached.', 'raeen-repeater-field-for-acf'), $max_rows), array('status' => 400));
			}
		}

		// Sanitize row data.
		$sanitized_row = $this->sanitize_row_data($row_data);

		// Add row ID.
		$sanitized_row['acf_repeater_row_id'] = uniqid('row_');

		// Get current rows and append.
		$current_rows = get_field($field['name'], $post_id, false);
		if (!is_array($current_rows)) {
			$current_rows = array();
		}

		$current_rows[] = $sanitized_row;

		// Save.
		if (update_field($field['key'], $current_rows, $post_id)) {
			$new_index = count($current_rows) - 1;
			$formatted = $this->format_row_data($sanitized_row, $field, $new_index);

			return rest_ensure_response($formatted);
		}

		return new \WP_Error('save_failed', __('Failed to save row.', 'raeen-repeater-field-for-acf'), array('status' => 500));
	}

	/**
	 * Update a repeater row.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_repeater_row(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		$field_key = $request->get_param('field_key');
		$post_id = $request->get_param('post_id') ?? 0;
		$row_index = $request->get_param('row_index') ?? 0;
		$row_data = $request->get_param('row_data') ?? array();

		if (!$field_key || !$post_id) {
			return new \WP_Error('missing_params', __('Field key, post ID, and row index are required.', 'raeen-repeater-field-for-acf'), array('status' => 400));
		}

		$field = acf_get_field($field_key);
		if (!$field || $field['type'] !== 'repeater') {
			return new \WP_Error('not_found', __('Repeater field not found.', 'raeen-repeater-field-for-acf'), array('status' => 404));
		}

		$current_rows = get_field($field['name'], $post_id, false);
		if (!is_array($current_rows) || !isset($current_rows[$row_index])) {
			return new \WP_Error('not_found', __('Row not found.', 'raeen-repeater-field-for-acf'), array('status' => 404));
		}

		// Sanitize and merge row data.
		$sanitized_row = $this->sanitize_row_data($row_data);

		// Preserve row ID.
		if (isset($current_rows[$row_index]['acf_repeater_row_id'])) {
			$sanitized_row['acf_repeater_row_id'] = $current_rows[$row_index]['acf_repeater_row_id'];
		}

		$current_rows[$row_index] = array_merge($current_rows[$row_index], $sanitized_row);

		if (update_field($field['key'], $current_rows, $post_id)) {
			$formatted = $this->format_row_data($current_rows[$row_index], $field, $row_index);
			return rest_ensure_response($formatted);
		}

		return new \WP_Error('save_failed', __('Failed to update row.', 'raeen-repeater-field-for-acf'), array('status' => 500));
	}

	/**
	 * Delete a repeater row.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_repeater_row(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		$field_key = $request->get_param('field_key');
		$post_id = $request->get_param('post_id') ?? 0;
		$row_index = $request->get_param('row_index') ?? 0;

		if (!$field_key || !$post_id) {
			return new \WP_Error('missing_params', __('Field key, post ID, and row index are required.', 'raeen-repeater-field-for-acf'), array('status' => 400));
		}

		$field = acf_get_field($field_key);
		if (!$field || $field['type'] !== 'repeater') {
			return new \WP_Error('not_found', __('Repeater field not found.', 'raeen-repeater-field-for-acf'), array('status' => 404));
		}

		// Check min rows.
		$min_rows = (int) ($field['min_rows'] ?? 0);
		if ($min_rows > 0) {
			$current_rows = get_field($field['name'], $post_id, false);
			if (is_array($current_rows) && count($current_rows) <= $min_rows) {
				/* translators: %d: minimum number of rows */
				return new \WP_Error('min_rows', sprintf(__('Minimum number of rows (%d) required.', 'raeen-repeater-field-for-acf'), $min_rows), array('status' => 400));
			}
		}

		$current_rows = get_field($field['name'], $post_id, false);
		if (!is_array($current_rows) || !isset($current_rows[$row_index])) {
			return new \WP_Error('not_found', __('Row not found.', 'raeen-repeater-field-for-acf'), array('status' => 404));
		}

		array_splice($current_rows, $row_index, 1);

		if (update_field($field['key'], $current_rows, $post_id)) {
			return rest_ensure_response(
				array(
					'success' => true,
					'row_index' => $row_index,
				)
			);
		}

		return new \WP_Error('delete_failed', __('Failed to delete row.', 'raeen-repeater-field-for-acf'), array('status' => 500));
	}

	/**
	 * Get repeater schema for REST API.
	 *
	 * @return array
	 */
	public function get_repeater_schema(): array
	{
		return array(
			'description' => __('Repeater field data.', 'raeen-repeater-field-for-acf'),
			'type' => 'object',
			'properties' => array(
				'field_key' => array(
					'type' => 'string',
					'description' => __('ACF field key.', 'raeen-repeater-field-for-acf'),
				),
				'field_name' => array(
					'type' => 'string',
					'description' => __('ACF field name.', 'raeen-repeater-field-for-acf'),
				),
				'layout' => array(
					'type' => 'string',
					'enum' => array('table', 'block'),
					'description' => __('Layout type.', 'raeen-repeater-field-for-acf'),
				),
				'button_label' => array(
					'type' => 'string',
					'description' => __('Add row button label.', 'raeen-repeater-field-for-acf'),
				),
				'min_rows' => array(
					'type' => 'integer',
					'description' => __('Minimum rows.', 'raeen-repeater-field-for-acf'),
				),
				'max_rows' => array(
					'type' => 'integer',
					'description' => __('Maximum rows.', 'raeen-repeater-field-for-acf'),
				),
				'collapsed' => array(
					'type' => 'string',
					'description' => __('Collapsed field key.', 'raeen-repeater-field-for-acf'),
				),
				'sortable' => array(
					'type' => 'boolean',
					'description' => __('Rows sortable.', 'raeen-repeater-field-for-acf'),
				),
				'duplicate' => array(
					'type' => 'boolean',
					'description' => __('Rows duplicatable.', 'raeen-repeater-field-for-acf'),
				),
				'delete_confirm' => array(
					'type' => 'boolean',
					'description' => __('Delete confirmation.', 'raeen-repeater-field-for-acf'),
				),
				'rows' => array(
					'type' => 'array',
					'items' => array(
						'type' => 'object',
						'properties' => array(
							'index' => array('type' => 'integer'),
							'id' => array('type' => 'string'),
							'data' => array('type' => 'object'),
						),
					),
				),
			),
		);
	}
}
