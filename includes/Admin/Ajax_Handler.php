<?php

namespace Raeen_Repeater\Admin;

if (!defined('ABSPATH')) {
	exit;
}

use Raeen_Repeater\Helpers\Validator;
use Raeen_Repeater\Helpers\Sanitizer;

/**
 * Class Ajax_Handler
 *
 * Handles AJAX requests for repeater field operations.
 * Note: Add/remove/duplicate are now handled client-side via clone row.
 * Only sort_rows remains for optional AJAX sort persistence.
 */
class Ajax_Handler
{

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'raeen_repeater_nonce';

	/**
	 * Validator instance.
	 *
	 * @var Validator
	 */
	private Validator $validator;

	/**
	 * Sanitizer instance.
	 *
	 * @var Sanitizer
	 */
	private Sanitizer $sanitizer;

	/**
	 * Registered AJAX actions.
	 *
	 * @var array<string, callable>
	 */
	private array $ajax_actions = array();

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->validator = new Validator();
		$this->sanitizer = new Sanitizer();

		$this->register_ajax_actions();
	}

	/**
	 * Register AJAX actions.
	 *
	 * @return void
	 */
	public function register_ajax_actions(): void
	{
		$this->ajax_actions = array(
			'raeen_repeater_sort_rows' => array($this, 'ajax_sort_rows'),
		);

		foreach ($this->ajax_actions as $action => $callback) {
			add_action('wp_ajax_' . $action, $callback);
		}
	}

	/**
	 * Verify AJAX nonce.
	 *
	 * @return bool
	 */
	private function verify_nonce(): bool
	{
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), self::NONCE_ACTION)) {
			return false;
		}

		return true;
	}

	/**
	 * Send JSON response.
	 *
	 * @param array $data Response data.
	 * @param int   $status HTTP status code.
	 * @return void
	 */
	private function send_response(array $data, int $status = 200): void
	{
		http_response_code($status);
		wp_send_json($data);
	}

	/**
	 * Send error response.
	 *
	 * @param string $message Error message.
	 * @param int    $status HTTP status code.
	 * @param mixed  $data Additional data.
	 * @return void
	 */
	private function send_error(string $message, int $status = 400, mixed $data = null): void
	{
		$response = array(
			'success' => false,
			'message' => $message,
		);

		if ($data !== null) {
			$response['data'] = $data;
		}

		$this->send_response($response, $status);
	}

	/**
	 * AJAX: Sort rows.
	 *
	 * @return void
	 */
	public function ajax_sort_rows(): void
	{
		// 1. Verify nonce first.
		if (!$this->verify_nonce()) {
			wp_send_json_error(
				array( 'message' => __('Invalid or missing nonce.', 'raeen-repeater-field-for-acf') ),
				403
			);
			return;
		}

		// 2. Read and sanitize input.
		$field_key = isset($_POST['field_key']) ? sanitize_text_field(wp_unslash($_POST['field_key'])) : '';
		$post_id   = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;

		// 3. Verify the user can edit this specific post.
		if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
			wp_send_json_error(
				array( 'message' => __('You do not have permission to edit this post.', 'raeen-repeater-field-for-acf') ),
				403
			);
			return;
		}

		$new_order = array();
		$raw_order = isset($_POST['new_order']) ? sanitize_text_field(wp_unslash($_POST['new_order'])) : '';
		if (!empty($raw_order)) {
			$decoded = json_decode($raw_order, true);
			if (is_array($decoded)) {
				$new_order = array_map('absint', $decoded);
			}
		}

		if (empty($field_key) || empty($new_order)) {
			$this->send_error(__('Invalid parameters.', 'raeen-repeater-field-for-acf'));
			return;
		}

		$field = function_exists('acf_get_field') ? acf_get_field($field_key) : null;
		if (!$field || $field['type'] !== 'repeater') {
			$this->send_error(__('Invalid repeater field.', 'raeen-repeater-field-for-acf'));
			return;
		}

		$rows = $this->get_field_rows($field_key, $post_id);
		$sorted_rows = array();

		foreach ($new_order as $index) {
			if (isset($rows[$index])) {
				$sorted_rows[] = $rows[$index];
			}
		}

		foreach ($rows as $index => $row) {
			if (!in_array($index, $new_order, true)) {
				$sorted_rows[] = $row;
			}
		}

		$this->save_field_rows($field_key, $post_id, $sorted_rows);

		$this->send_response(
			array(
				'success' => true,
				'order' => $new_order,
			)
		);
	}

	/**
	 * Get field rows from post meta.
	 *
	 * @param string $field_key Field key.
	 * @param int    $post_id Post ID.
	 * @return array<int, array>
	 */
	private function get_field_rows(string $field_key, $post_id): array
	{
		if (!is_numeric($post_id) || (int) $post_id <= 0) {
			return array();
		}
		$value = get_post_meta((int) $post_id, $field_key, true);
		if (is_array($value)) {
			return $value;
		}
		return array();
	}

	/**
	 * Save field rows to post meta.
	 *
	 * @param string       $field_key Field key.
	 * @param int|string   $post_id Post ID.
	 * @param array<array> $rows Rows data.
	 * @return bool
	 */
	private function save_field_rows(string $field_key, $post_id, array $rows): bool
	{
		if (!is_numeric($post_id) || (int) $post_id <= 0) {
			return false;
		}
		return update_post_meta((int) $post_id, $field_key, $rows);
	}
}

