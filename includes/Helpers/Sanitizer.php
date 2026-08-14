<?php
/**
 * Sanitizer class for ACF Repeater.
 *
 * @package Raeen_Repeater\Helpers
 */

namespace Raeen_Repeater\Helpers;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sanitizer
 *
 * Handles sanitization of repeater field data.
 */
class Sanitizer {

	/**
	 * Sanitize field data array.
	 *
	 * @param array $data Raw field data.
	 * @return array Sanitized data.
	 */
	public function sanitize_field_data( array $data ): array {
		$sanitized = array();

		foreach ( $data as $key => $value ) {
			$clean_key               = sanitize_key( $key );
			$sanitized[ $clean_key ] = $this->sanitize_value( $value );
		}

		return $sanitized;
	}

	/**
	 * Sanitize a value recursively.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return mixed Sanitized value.
	 */
	public function sanitize_value( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'sanitize_value' ), $value );
		}

		if ( is_numeric( $value ) ) {
			return $value + 0;
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_object( $value ) ) {
			// Convert object to array if possible.
			if ( method_exists( $value, 'to_array' ) ) {
				return $this->sanitize_value( $value->to_array() );
			}
			return (string) $value;
		}

		return $value;
	}

	/**
	 * Sanitize value based on field type.
	 *
	 * @param mixed $value Value to sanitize.
	 * @param array $field Field configuration.
	 * @return mixed Sanitized value.
	 */
	public function sanitize_by_type( mixed $value, array $field ): mixed {
		$type = $field['type'] ?? 'text';

		switch ( $type ) {
			case 'text':
			case 'textarea':
			case 'password':
			case 'search':
				return is_string( $value ) ? sanitize_text_field( $value ) : $value;

			case 'email':
				return is_string( $value ) ? sanitize_email( $value ) : $value;

			case 'url':
				return is_string( $value ) ? esc_url_raw( trim( $value ) ) : $value;

			case 'number':
			case 'range':
				return is_numeric( $value ) ? ( $value + 0 ) : 0;

			case 'wysiwyg':
				return is_string( $value ) ? wp_kses_post( $value ) : '';

			case 'select':
			case 'radio':
				return is_string( $value ) ? sanitize_text_field( $value ) : '';

			case 'checkbox':
			case 'true_false':
				return is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : ( (bool) $value ? '1' : '0' );

			case 'image':
			case 'file':
				return is_numeric( $value ) ? (int) $value : 0;

			case 'gallery':
				return is_array( $value ) ? array_map( 'intval', $value ) : array();

			case 'date_picker':
			case 'time_picker':
			case 'datetime_picker':
				return is_string( $value ) ? sanitize_text_field( $value ) : '';

			case 'color_picker':
				return is_string( $value ) ? sanitize_hex_color( trim( $value ) ) : '';

			case 'link':
				return is_array( $value ) ? $this->sanitize_link( $value ) : array();

			case 'repeater':
				return is_array( $value ) ? $this->sanitize_repeater( $value, $field ) : array();

			case 'flexible_content':
				return is_array( $value ) ? $this->sanitize_flexible_content( $value ) : array();

			case 'clone':
				return is_array( $value ) ? $this->sanitize_clone( $value ) : array();

			default:
				// Apply filter for custom field types.
				return apply_filters( "acf_repeater_sanitize_field_{$type}", $this->sanitize_value( $value ), $value, $field );
		}
	}

	/**
	 * Sanitize link array.
	 *
	 * @param array $link Link data.
	 * @return array Sanitized link.
	 */
	private function sanitize_link( array $link ): array {
		return array(
			'url'    => isset( $link['url'] ) ? esc_url_raw( $link['url'] ) : '',
			'title'  => isset( $link['title'] ) ? sanitize_text_field( $link['title'] ) : '',
			'target' => isset( $link['target'] ) && $link['target'] === '_blank' ? '_blank' : '',
			'rel'    => isset( $link['rel'] ) ? sanitize_text_field( $link['rel'] ) : '',
			'class'  => isset( $link['class'] ) ? sanitize_html_class( $link['class'] ) : '',
		);
	}

	/**
	 * Sanitize repeater value.
	 *
	 * @param array $value Repeater rows.
	 * @param array $field Field configuration.
	 * @return array Sanitized rows.
	 */
	private function sanitize_repeater( array $value, array $field ): array {
		$sub_fields = $field['sub_fields'] ?? array();
		$sanitized  = array();

		foreach ( $value as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$sanitized_row = array();
			foreach ( $sub_fields as $sub_field ) {
				$name = $sub_field['name'] ?? '';
				if ( $name && isset( $row[ $name ] ) ) {
					$sanitized_row[ $name ] = $this->sanitize_by_type( $row[ $name ], $sub_field );
				}
			}

			// Preserve row metadata.
			$sanitized_row['acf_repeater_row_id']    = isset( $row['acf_repeater_row_id'] ) ? sanitize_text_field( $row['acf_repeater_row_id'] ) : ( 'row_' . ( $index + 1 ) );
			$sanitized_row['acf_repeater_row_index'] = isset( $row['acf_repeater_row_index'] ) ? (int) $row['acf_repeater_row_index'] : $index;

			$sanitized[] = $sanitized_row;
		}

		return $sanitized;
	}

	/**
	 * Sanitize flexible content value.
	 *
	 * @param array $value Flexible content layouts.
	 * @return array Sanitized layouts.
	 */
	private function sanitize_flexible_content( array $value ): array {
		$sanitized = array();

		foreach ( $value as $layout ) {
			if ( ! is_array( $layout ) ) {
				continue;
			}

			$sanitized_layout = array();
			foreach ( $layout as $key => $val ) {
				if ( $key === 'acf_fc_layout' ) {
					$sanitized_layout[ $key ] = sanitize_text_field( $val );
				} elseif ( is_array( $val ) ) {
					$sanitized_layout[ $key ] = $this->sanitize_value( $val );
				} else {
					$sanitized_layout[ $key ] = $this->sanitize_value( $val );
				}
			}

			$sanitized[] = $sanitized_layout;
		}

		return $sanitized;
	}

	/**
	 * Sanitize clone field value.
	 *
	 * @param array $value Clone field data.
	 * @return array Sanitized data.
	 */
	private function sanitize_clone( array $value ): array {
		return $this->sanitize_value( $value );
	}

	/**
	 * Sanitize repeater row for saving.
	 *
	 * @param array $row Row data.
	 * @param array $field Field configuration.
	 * @return array Sanitized row.
	 */
	public function sanitize_row( array $row, array $field ): array {
		$sub_fields = $field['sub_fields'] ?? array();
		$sanitized  = array();

		foreach ( $sub_fields as $sub_field ) {
			$name = $sub_field['name'] ?? '';
			if ( $name && isset( $row[ $name ] ) ) {
				$sanitized[ $name ] = $this->sanitize_by_type( $row[ $name ], $sub_field );
			}
		}

		// Preserve metadata.
		if ( isset( $row['acf_repeater_row_id'] ) ) {
			$sanitized['acf_repeater_row_id'] = sanitize_text_field( $row['acf_repeater_row_id'] );
		}

		return $sanitized;
	}

	/**
	 * Prepare value for database storage.
	 *
	 * @param array $value Field value.
	 * @param array $field Field configuration.
	 * @return array
	 */
	public function prepare_for_database( array $value, array $field ): array {
		// Run validation first.
		$validator = new Validator();
		$validator->validate_repeater( $value, $field );

		// Sanitize.
		return $this->sanitize_repeater( $value, $field );
	}

	/**
	 * Prepare value for display.
	 *
	 * @param array $value Field value.
	 * @param array $field Field configuration.
	 * @return array
	 */
	public function prepare_for_display( array $value, array $field ): array {
		$sub_fields = $field['sub_fields'] ?? array();
		$prepared   = array();

		foreach ( $value as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$prepared_row = array(
				'index' => $index,
				'id'    => $row['acf_repeater_row_id'] ?? uniqid( 'row_' ),
				'data'  => array(),
			);

			foreach ( $sub_fields as $sub_field ) {
				$name = $sub_field['name'] ?? '';
				if ( $name && isset( $row[ $name ] ) ) {
					$prepared_row['data'][ $name ] = $this->format_for_display( $row[ $name ], $sub_field );
				}
			}

			$prepared[] = $prepared_row;
		}

		return $prepared;
	}

	/**
	 * Format value for display.
	 *
	 * @param mixed $value Field value.
	 * @param array $field Field configuration.
	 * @return mixed
	 */
	private function format_for_display( mixed $value, array $field ): mixed {
		$type = $field['type'] ?? 'text';

		switch ( $type ) {
			case 'wysiwyg':
				return is_string( $value ) ? wp_kses_post( $value ) : $value;

			case 'image':
			case 'file':
				if ( is_numeric( $value ) ) {
					$attachment = get_post( (int) $value );
					if ( $attachment ) {
						return array(
							'id'    => (int) $value,
							'url'   => wp_get_attachment_url( $value ),
							'alt'   => get_post_meta( $value, '_wp_attachment_image_alt', true ),
							'title' => $attachment->post_title,
						);
					}
				}
				return $value;

			case 'gallery':
				if ( is_array( $value ) ) {
					return array_map(
						function ( $id ) {
							$attachment = get_post( (int) $id );
							if ( $attachment ) {
									return array(
										'id'    => (int) $id,
										'url'   => wp_get_attachment_url( $id ),
										'alt'   => get_post_meta( $id, '_wp_attachment_image_alt', true ),
										'title' => $attachment->post_title,
									);
							}
							return $id;
						},
						$value
					);
				}
				return $value;

			case 'date_picker':
			case 'time_picker':
			case 'datetime_picker':
				return is_string( $value ) ? $value : '';

			default:
				return $value;
		}
	}

	/**
	 * Sanitize field settings array.
	 *
	 * @param array $settings Raw settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_field_settings( array $settings ): array {
		$allowed_keys = array(
			'min_rows',
			'max_rows',
			'button_label',
			'layout',
			'collapsed',
			'sortable',
			'duplicate',
			'delete_confirm',
			'default_rows',
			'sub_fields',
		);

		$sanitized = array();

		foreach ( $allowed_keys as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$value = $settings[ $key ];

				switch ( $key ) {
					case 'min_rows':
					case 'max_rows':
						$sanitized[ $key ] = max( 0, (int) $value );
						break;

					case 'button_label':
					case 'collapsed':
						$sanitized[ $key ] = sanitize_text_field( $value );
						break;

					case 'layout':
						$sanitized[ $key ] = in_array( $value, array( 'table', 'block' ), true ) ? $value : 'table';
						break;

					case 'sortable':
					case 'duplicate':
					case 'delete_confirm':
						$sanitized[ $key ] = (bool) $value;
						break;

					case 'default_rows':
						if ( is_array( $value ) ) {
							$sanitized[ $key ] = array_map( array( $this, 'sanitize_value' ), $value );
						}
						break;

					case 'sub_fields':
						if ( is_array( $value ) ) {
							$sanitized[ $key ] = $this->sanitize_sub_fields( $value );
						}
						break;

					default:
						$sanitized[ $key ] = $this->sanitize_value( $value );
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize sub fields array.
	 *
	 * @param array $sub_fields Raw sub fields.
	 * @return array Sanitized sub fields.
	 */
	public function sanitize_sub_fields( array $sub_fields ): array {
		$sanitized = array();

		foreach ( $sub_fields as $index => $sub_field ) {
			if ( ! is_array( $sub_field ) ) {
				continue;
			}

			$clean_sub = array(
				'key'               => isset( $sub_field['key'] ) ? sanitize_text_field( $sub_field['key'] ) : 'field_' . uniqid(),
				'label'             => isset( $sub_field['label'] ) ? sanitize_text_field( $sub_field['label'] ) : '',
				'name'              => isset( $sub_field['name'] ) ? sanitize_key( $sub_field['name'] ) : '',
				'type'              => isset( $sub_field['type'] ) ? sanitize_text_field( $sub_field['type'] ) : 'text',
				'instructions'      => isset( $sub_field['instructions'] ) ? sanitize_textarea_field( $sub_field['instructions'] ) : '',
				'required'          => ! empty( $sub_field['required'] ),
				'conditional_logic' => isset( $sub_field['conditional_logic'] ) ? $this->sanitize_conditional_logic( $sub_field['conditional_logic'] ) : 0,
				'wrapper'           => isset( $sub_field['wrapper'] ) ? $this->sanitize_wrapper( $sub_field['wrapper'] ) : array(),
				'default_value'     => isset( $sub_field['default_value'] ) ? $this->sanitize_value( $sub_field['default_value'] ) : '',
				'parent'            => '',
			);

			// Type-specific settings.
			$type          = $clean_sub['type'];
			$type_settings = $this->get_type_settings_keys( $type );

			foreach ( $type_settings as $setting_key ) {
				if ( isset( $sub_field[ $setting_key ] ) ) {
					$clean_sub[ $setting_key ] = $this->sanitize_value( $sub_field[ $setting_key ] );
				}
			}

			// Handle nested sub fields (for repeater, flexible_content, clone).
			if ( in_array( $type, array( 'repeater', 'flexible_content', 'clone' ), true ) && isset( $sub_field['sub_fields'] ) && is_array( $sub_field['sub_fields'] ) ) {
				$clean_sub['sub_fields'] = $this->sanitize_sub_fields( $sub_field['sub_fields'] );
			}

			$sanitized[] = $clean_sub;
		}

		return $sanitized;
	}

	/**
	 * Get allowed settings keys for field type.
	 *
	 * @param string $type Field type.
	 * @return array<string>
	 */
	private function get_type_settings_keys( string $type ): array {
		$settings = array(
			'text'             => array( 'placeholder', 'prepend', 'append', 'maxlength', 'readonly', 'disabled' ),
			'textarea'         => array( 'placeholder', 'maxlength', 'rows', 'new_lines', 'readonly', 'disabled' ),
			'number'           => array( 'min', 'max', 'step', 'placeholder', 'prepend', 'append' ),
			'email'            => array( 'placeholder', 'prepend', 'append' ),
			'url'              => array( 'placeholder', 'prepend', 'append' ),
			'password'         => array( 'placeholder', 'prepend', 'append' ),
			'wysiwyg'          => array( 'tabs', 'toolbar', 'media_upload', 'delay' ),
			'select'           => array( 'choices', 'default_value', 'allow_null', 'multiple', 'ui', 'ajax', 'placeholder' ),
			'checkbox'         => array( 'choices', 'default_value', 'layout', 'toggle', 'return_format' ),
			'radio'            => array( 'choices', 'default_value', 'layout', 'return_format' ),
			'true_false'       => array( 'message', 'default_value', 'ui', 'ui_on_text', 'ui_off_text' ),
			'image'            => array( 'return_format', 'preview_size', 'library', 'min_width', 'min_height', 'min_size', 'max_width', 'max_height', 'max_size', 'mime_types' ),
			'file'             => array( 'return_format', 'library', 'min_size', 'max_size', 'mime_types' ),
			'gallery'          => array( 'min', 'max', 'min_width', 'min_height', 'min_size', 'max_width', 'max_height', 'max_size', 'mime_types', 'insert' ),
			'date_picker'      => array( 'display_format', 'return_format', 'first_day' ),
			'time_picker'      => array( 'display_format', 'return_format' ),
			'datetime_picker'  => array( 'display_format', 'return_format' ),
			'color_picker'     => array( 'default_value', 'palette' ),
			'link'             => array( 'return_format' ),
			'repeater'         => array( 'sub_fields', 'min_rows', 'max_rows', 'layout', 'button_label', 'collapsed', 'sortable', 'duplicate', 'delete_confirm' ),
			'flexible_content' => array( 'layouts', 'button_label', 'min', 'max' ),
			'clone'            => array( 'clone', 'display', 'layout', 'prefix_label', 'prefix_name' ),
		);

		return $settings[ $type ] ?? array();
	}

	/**
	 * Sanitize conditional logic.
	 *
	 * @param mixed $logic Conditional logic.
	 * @return mixed Sanitized logic.
	 */
	private function sanitize_conditional_logic( mixed $logic ): mixed {
		if ( is_array( $logic ) ) {
			return array_map(
				function ( $group ) {
					if ( is_array( $group ) ) {
							return array_map(
								function ( $rule ) {
									return array(
										'field'    => isset( $rule['field'] ) ? sanitize_text_field( $rule['field'] ) : '',
										'operator' => isset( $rule['operator'] ) ? sanitize_text_field( $rule['operator'] ) : '==',
										'value'    => isset( $rule['value'] ) ? $this->sanitize_value( $rule['value'] ) : '',
									);
								},
								$group
							);
					}
					return $group;
				},
				$logic
			);
		}
		return $logic;
	}

	/**
	 * Sanitize wrapper settings.
	 *
	 * @param array $wrapper Wrapper settings.
	 * @return array Sanitized wrapper.
	 */
	private function sanitize_wrapper( array $wrapper ): array {
		return array(
			'width' => isset( $wrapper['width'] ) ? sanitize_text_field( $wrapper['width'] ) : '',
			'class' => isset( $wrapper['class'] ) ? sanitize_html_class( $wrapper['class'] ) : '',
			'id'    => isset( $wrapper['id'] ) ? sanitize_html_class( $wrapper['id'] ) : '',
		);
	}
}
