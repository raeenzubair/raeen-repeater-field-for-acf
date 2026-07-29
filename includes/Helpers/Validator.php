<?php
/**
 * Validator class for ACF Repeater.
 *
 * @package ACF_Repeater\Helpers
 */

namespace ACF_Repeater\Helpers;

/**
 * Class Validator
 *
 * Handles validation of repeater field data.
 */
class Validator {

	/**
	 * Validation errors.
	 *
	 * @var array<string, array<string>>
	 */
	private array $errors = array();

	/**
	 * Validate repeater field data.
	 *
	 * @param array $value Field value (rows).
	 * @param array $field Field configuration.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_repeater( array $value, array $field ): bool {
		$this->errors = array();

		// Validate min rows.
		$min_rows = (int) ( $field['min_rows'] ?? 0 );
		if ( $min_rows > 0 && count( $value ) < $min_rows ) {
			$this->add_error(
				'min_rows',
				sprintf(
				/* translators: %d: Minimum rows */
					__( 'Minimum %d rows required.', 'acf-repeater' ),
					$min_rows
				)
			);
		}

		// Validate max rows.
		$max_rows = (int) ( $field['max_rows'] ?? 0 );
		if ( $max_rows > 0 && count( $value ) > $max_rows ) {
			$this->add_error(
				'max_rows',
				sprintf(
				/* translators: %d: Maximum rows */
					__( 'Maximum %d rows allowed.', 'acf-repeater' ),
					$max_rows
				)
			);
		}

		// Validate each row.
		$sub_fields = $field['sub_fields'] ?? array();
		foreach ( $value as $row_index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			foreach ( $sub_fields as $sub_field ) {
				$this->validate_sub_field( $row, $sub_field, $row_index );
			}
		}

		return empty( $this->errors );
	}

	/**
	 * Validate a single sub field.
	 *
	 * @param array $row Row data.
	 * @param array $sub_field Sub field configuration.
	 * @param int   $row_index Row index.
	 * @return void
	 */
	private function validate_sub_field( array $row, array $sub_field, int $row_index ): void {
		$name     = $sub_field['name'] ?? '';
		$required = ! empty( $sub_field['required'] );

		if ( ! $name ) {
			return;
		}

		$value     = $row[ $name ] ?? null;
		$has_value = $this->has_value( $value );

		// Required validation.
		if ( $required && ! $has_value ) {
			$this->add_error(
				"row_{$row_index}_{$name}",
				sprintf(
				/* translators: %s: Field label */
					__( '%s is required.', 'acf-repeater' ),
					$sub_field['label'] ?? $name
				)
			);
			return;
		}

		// Skip further validation if no value and not required.
		if ( ! $has_value ) {
			return;
		}

		// Type-specific validation.
		$type = $sub_field['type'] ?? 'text';
		$this->validate_by_type( $value, $type, $sub_field, $row_index, $name );
	}

	/**
	 * Validate value by field type.
	 *
	 * @param mixed  $value Field value.
	 * @param string $type Field type.
	 * @param array  $sub_field Sub field configuration.
	 * @param int    $row_index Row index.
	 * @param string $name Field name.
	 * @return void
	 */
	private function validate_by_type( mixed $value, string $type, array $sub_field, int $row_index, string $name ): void {
		switch ( $type ) {
			case 'email':
				if ( is_string( $value ) && ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
					$this->add_error( "row_{$row_index}_{$name}", __( 'Invalid email address.', 'acf-repeater' ) );
				}
				break;

			case 'url':
				if ( is_string( $value ) && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
					$this->add_error( "row_{$row_index}_{$name}", __( 'Invalid URL.', 'acf-repeater' ) );
				}
				break;

			case 'number':
			case 'range':
				if ( ! is_numeric( $value ) ) {
					$this->add_error( "row_{$row_index}_{$name}", __( 'Must be a number.', 'acf-repeater' ) );
				} else {
					$num_value = (float) $value;
					if ( isset( $sub_field['min'] ) && $num_value < (float) $sub_field['min'] ) {
						$this->add_error(
							"row_{$row_index}_{$name}",
							sprintf(
							/* translators: %s: Minimum value */
								__( 'Minimum value is %s.', 'acf-repeater' ),
								$sub_field['min']
							)
						);
					}
					if ( isset( $sub_field['max'] ) && $num_value > (float) $sub_field['max'] ) {
						$this->add_error(
							"row_{$row_index}_{$name}",
							sprintf(
							/* translators: %s: Maximum value */
								__( 'Maximum value is %s.', 'acf-repeater' ),
								$sub_field['max']
							)
						);
					}
					if ( isset( $sub_field['step'] ) && $sub_field['step'] > 0 ) {
						$step = (float) $sub_field['step'];
						$min  = isset( $sub_field['min'] ) ? (float) $sub_field['min'] : 0;
						if ( fmod( $num_value - $min, $step ) !== 0.0 ) {
							$this->add_error(
								"row_{$row_index}_{$name}",
								sprintf(
								/* translators: %s: Step value */
									__( 'Value must be a multiple of %s.', 'acf-repeater' ),
									$sub_field['step']
								)
							);
						}
					}
				}
				break;

			case 'select':
			case 'radio':
				$choices = $sub_field['choices'] ?? array();
				if ( ! empty( $choices ) ) {
					$values = is_array( $value ) ? $value : array( $value );
					foreach ( $values as $val ) {
						if ( ! array_key_exists( $val, $choices ) ) {
							$this->add_error( "row_{$row_index}_{$name}", __( 'Invalid choice.', 'acf-repeater' ) );
							break;
						}
					}
				}
				break;

			case 'checkbox':
				$choices = $sub_field['choices'] ?? array();
				if ( ! empty( $choices ) && is_array( $value ) ) {
					foreach ( $value as $val ) {
						if ( ! array_key_exists( $val, $choices ) ) {
							$this->add_error( "row_{$row_index}_{$name}", __( 'Invalid choice.', 'acf-repeater' ) );
							break;
						}
					}
				}
				break;

			case 'date_picker':
				if ( is_string( $value ) && ! $this->is_valid_date( $value, $sub_field['date_format'] ?? 'Y-m-d' ) ) {
					$this->add_error( "row_{$row_index}_{$name}", __( 'Invalid date format.', 'acf-repeater' ) );
				}
				break;

			case 'time_picker':
				if ( is_string( $value ) && ! $this->is_valid_time( $value, $sub_field['time_format'] ?? 'H:i' ) ) {
					$this->add_error( "row_{$row_index}_{$name}", __( 'Invalid time format.', 'acf-repeater' ) );
				}
				break;

			case 'datetime_picker':
				if ( is_string( $value ) && ! $this->is_valid_datetime( $value ) ) {
					$this->add_error( "row_{$row_index}_{$name}", __( 'Invalid date/time format.', 'acf-repeater' ) );
				}
				break;

			case 'color_picker':
				if ( is_string( $value ) && ! preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ) {
					$this->add_error( "row_{$row_index}_{$name}", __( 'Invalid color format.', 'acf-repeater' ) );
				}
				break;

			case 'wysiwyg':
				// WYSIWYG validation - could check for allowed HTML, length, etc.
				break;

			case 'repeater':
				// Nested repeater validation.
				if ( is_array( $value ) ) {
					$nested_validator = new self();
					if ( ! $nested_validator->validate_repeater( $value, $sub_field ) ) {
						$nested_errors = $nested_validator->get_errors();
						foreach ( $nested_errors as $error_key => $error_messages ) {
							foreach ( $error_messages as $msg ) {
								$this->add_error( "row_{$row_index}_{$name}_{$error_key}", $msg );
							}
						}
					}
				}
				break;

			case 'image':
			case 'file':
				// Validate attachment ID exists.
				if ( is_numeric( $value ) && ! get_post( (int) $value ) ) {
					$this->add_error( "row_{$row_index}_{$name}", __( 'Invalid attachment.', 'acf-repeater' ) );
				}
				break;

			case 'gallery':
				if ( is_array( $value ) ) {
					foreach ( $value as $attachment_id ) {
						if ( is_numeric( $attachment_id ) && ! get_post( (int) $attachment_id ) ) {
							$this->add_error( "row_{$row_index}_{$name}", __( 'One or more invalid attachments.', 'acf-repeater' ) );
							break;
						}
					}
				}
				break;

			case 'link':
				if ( is_array( $value ) && ! empty( $value['url'] ) && ! filter_var( $value['url'], FILTER_VALIDATE_URL ) ) {
					$this->add_error( "row_{$row_index}_{$name}", __( 'Invalid link URL.', 'acf-repeater' ) );
				}
				break;

			default:
				// Custom validation via filter.
				$custom_errors = apply_filters( "acf_repeater_validate_field_{$type}", array(), $value, $sub_field, $row_index );
				if ( is_array( $custom_errors ) ) {
					foreach ( $custom_errors as $msg ) {
						$this->add_error( "row_{$row_index}_{$name}", $msg );
					}
				}
		}
	}

	/**
	 * Check if value has content.
	 *
	 * @param mixed $value Value to check.
	 * @return bool
	 */
	private function has_value( mixed $value ): bool {
		if ( $value === null || $value === '' ) {
			return false;
		}

		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		if ( is_string( $value ) ) {
			return trim( $value ) !== '';
		}

		return true;
	}

	/**
	 * Validate date format.
	 *
	 * @param string $date Date string.
	 * @param string $format Expected format.
	 * @return bool
	 */
	private function is_valid_date( string $date, string $format ): bool {
		$date_obj = \DateTime::createFromFormat( $format, $date );
		return $date_obj !== false && $date_obj->format( $format ) === $date;
	}

	/**
	 * Validate time format.
	 *
	 * @param string $time Time string.
	 * @param string $format Expected format.
	 * @return bool
	 */
	private function is_valid_time( string $time, string $format ): bool {
		$time_obj = \DateTime::createFromFormat( $format, $time );
		return $time_obj !== false && $time_obj->format( $format ) === $time;
	}

	/**
	 * Validate datetime format.
	 *
	 * @param string $datetime Datetime string.
	 * @return bool
	 */
	private function is_valid_datetime( string $datetime ): bool {
		$formats = array( 'Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i:sP', 'Y-m-d H:i' );
		foreach ( $formats as $format ) {
			$dt = \DateTime::createFromFormat( $format, $datetime );
			if ( $dt !== false && $dt->format( $format ) === $datetime ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add validation error.
	 *
	 * @param string $key Error key.
	 * @param string $message Error message.
	 * @return void
	 */
	private function add_error( string $key, string $message ): void {
		if ( ! isset( $this->errors[ $key ] ) ) {
			$this->errors[ $key ] = array();
		}
		$this->errors[ $key ][] = $message;
	}

	/**
	 * Get validation errors.
	 *
	 * @return array<string, array<string>>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Get first error message.
	 *
	 * @return string|null
	 */
	public function get_first_error(): ?string {
		foreach ( $this->errors as $messages ) {
			if ( ! empty( $messages ) ) {
				return $messages[0];
			}
		}
		return null;
	}

	/**
	 * Check if has errors.
	 *
	 * @return bool
	 */
	public function has_errors(): bool {
		return ! empty( $this->errors );
	}

	/**
	 * Get errors for a specific row.
	 *
	 * @param int $row_index Row index.
	 * @return array<string, array<string>>
	 */
	public function get_row_errors( int $row_index ): array {
		$prefix     = "row_{$row_index}_";
		$row_errors = array();

		foreach ( $this->errors as $key => $messages ) {
			if ( str_starts_with( $key, $prefix ) ) {
				$row_errors[ $key ] = $messages;
			}
		}

		return $row_errors;
	}

	/**
	 * Get errors for a specific field in a row.
	 *
	 * @param int    $row_index Row index.
	 * @param string $field_name Field name.
	 * @return array<string>
	 */
	public function get_field_errors( int $row_index, string $field_name ): array {
		$key = "row_{$row_index}_{$field_name}";
		return $this->errors[ $key ] ?? array();
	}
}
