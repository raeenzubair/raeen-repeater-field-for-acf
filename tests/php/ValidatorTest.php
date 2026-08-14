<?php
/**
 * Validator Tests for Raeen Repeater Field for ACF.
 *
 * @package Raeen_Repeater\Tests
 */

namespace Raeen_Repeater\Tests;

use Raeen_Repeater\Helpers\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Class ValidatorTest
 */
class ValidatorTest extends TestCase {

	/**
	 * Validator instance.
	 *
	 * @var Validator
	 */
	private Validator $validator;

	/**
	 * Set up before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->validator = new Validator();
	}

	/**
	 * Test minimum rows validation.
	 */
	public function test_min_rows_validation(): void {
		$field = array(
			'min_rows'   => 2,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name' => 'text_field',
					'type' => 'text',
				),
			),
		);

		// Less than min rows.
		$value = array( array( 'text_field' => 'Row 1' ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'min_rows', $errors );

		// Exact min rows.
		$value = array(
			array( 'text_field' => 'Row 1' ),
			array( 'text_field' => 'Row 2' ),
		);
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );

		// More than min rows.
		$value = array(
			array( 'text_field' => 'Row 1' ),
			array( 'text_field' => 'Row 2' ),
			array( 'text_field' => 'Row 3' ),
		);
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test maximum rows validation.
	 */
	public function test_max_rows_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 2,
			'sub_fields' => array(
				array(
					'name' => 'text_field',
					'type' => 'text',
				),
			),
		);

		// More than max rows.
		$value = array(
			array( 'text_field' => 'Row 1' ),
			array( 'text_field' => 'Row 2' ),
			array( 'text_field' => 'Row 3' ),
		);
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'max_rows', $errors );

		// Exact max rows.
		$value = array(
			array( 'text_field' => 'Row 1' ),
			array( 'text_field' => 'Row 2' ),
		);
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );

		// Less than max rows.
		$value = array( array( 'text_field' => 'Row 1' ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test required sub field validation.
	 */
	public function test_required_sub_field_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'required_field',
					'type'     => 'text',
					'required' => true,
				),
				array(
					'name'     => 'optional_field',
					'type'     => 'text',
					'required' => false,
				),
			),
		);

		// Missing required field.
		$value = array( array( 'optional_field' => 'Present' ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_required_field', $errors );
		$this->assertArrayNotHasKey( 'row_0_optional_field', $errors );

		// Required field present.
		$value = array(
			array(
				'required_field' => 'Present',
				'optional_field' => '',
			),
		);
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );

		// Empty string for required field should fail.
		$value = array(
			array(
				'required_field' => '',
				'optional_field' => '',
			),
		);
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test email validation.
	 */
	public function test_email_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'email_field',
					'type'     => 'email',
					'required' => true,
				),
			),
		);

		$value = array( array( 'email_field' => 'invalid-email' ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_email_field', $errors );

		$value = array( array( 'email_field' => 'valid@example.com' ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test URL validation.
	 */
	public function test_url_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'url_field',
					'type'     => 'url',
					'required' => true,
				),
			),
		);

		$value = array( array( 'url_field' => 'not-a-url' ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_url_field', $errors );

		$value = array( array( 'url_field' => 'https://example.com' ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test number validation with min/max/step.
	 */
	public function test_number_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name' => 'number_field',
					'type' => 'number',
					'min'  => 0,
					'max'  => 100,
					'step' => 5,
				),
			),
		);

		// Non-numeric.
		$value = array( array( 'number_field' => 'not-a-number' ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );

		// Below min.
		$value = array( array( 'number_field' => -1 ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );

		// Above max.
		$value = array( array( 'number_field' => 101 ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );

		// Invalid step.
		$value = array( array( 'number_field' => 7 ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );

		// Valid values.
		$value = array( array( 'number_field' => 0 ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );

		$value = array( array( 'number_field' => 10 ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );

		$value = array( array( 'number_field' => 100 ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test select/radio validation with choices.
	 */
	public function test_select_radio_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'select_field',
					'type'     => 'select',
					'required' => true,
					'choices'  => array(
						'option1' => 'Option 1',
						'option2' => 'Option 2',
					),
				),
				array(
					'name'     => 'radio_field',
					'type'     => 'radio',
					'required' => true,
					'choices'  => array(
						'choice1' => 'Choice 1',
						'choice2' => 'Choice 2',
					),
				),
			),
		);

		// Invalid select choice.
		$value = array(
			array(
				'select_field' => 'invalid',
				'radio_field'  => 'choice1',
			),
		);
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_select_field', $errors );

		// Invalid radio choice.
		$value = array(
			array(
				'select_field' => 'option1',
				'radio_field'  => 'invalid',
			),
		);
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_radio_field', $errors );

		// Valid choices.
		$value = array(
			array(
				'select_field' => 'option1',
				'radio_field'  => 'choice2',
			),
		);
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test checkbox validation with choices.
	 */
	public function test_checkbox_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'checkbox_field',
					'type'     => 'checkbox',
					'required' => true,
					'choices'  => array(
						'choice1' => 'Choice 1',
						'choice2' => 'Choice 2',
					),
				),
			),
		);

		// Invalid checkbox choice.
		$value = array( array( 'checkbox_field' => array( 'invalid' ) ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_checkbox_field', $errors );

		// Valid choices.
		$value = array( array( 'checkbox_field' => array( 'choice1', 'choice2' ) ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );

		// Single valid choice.
		$value = array( array( 'checkbox_field' => array( 'choice1' ) ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test date picker validation.
	 */
	public function test_date_picker_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'        => 'date_field',
					'type'        => 'date_picker',
					'required'    => true,
					'date_format' => 'Y-m-d',
				),
			),
		);

		$value = array( array( 'date_field' => 'invalid-date' ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_date_field', $errors );

		$value = array( array( 'date_field' => '2024-01-15' ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test time picker validation.
	 */
	public function test_time_picker_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'        => 'time_field',
					'type'        => 'time_picker',
					'required'    => true,
					'time_format' => 'H:i',
				),
			),
		);

		$value = array( array( 'time_field' => 'invalid-time' ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_time_field', $errors );

		$value = array( array( 'time_field' => '14:30' ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test color picker validation.
	 */
	public function test_color_picker_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'color_field',
					'type'     => 'color_picker',
					'required' => true,
				),
			),
		);

		$value = array( array( 'color_field' => 'not-a-color' ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_color_field', $errors );

		$value = array( array( 'color_field' => '#ff0000' ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );

		$value = array( array( 'color_field' => '#abcdef' ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test nested repeater validation.
	 */
	public function test_nested_repeater_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'       => 'nested_repeater',
					'type'       => 'repeater',
					'required'   => false,
					'min_rows'   => 1,
					'max_rows'   => 0,
					'sub_fields' => array(
						array(
							'name'     => 'nested_text',
							'type'     => 'text',
							'required' => true,
						),
					),
				),
			),
		);

		// Empty nested repeater should fail min_rows.
		$value = array( array( 'nested_repeater' => array() ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_nested_repeater_min_rows', $errors );

		// Valid nested repeater.
		$value = array( array( 'nested_repeater' => array( array( 'nested_text' => 'Nested Value' ) ) ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test link validation.
	 */
	public function test_link_validation(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'link_field',
					'type'     => 'link',
					'required' => true,
				),
			),
		);

		$value = array( array( 'link_field' => array( 'url' => 'not-a-url' ) ) );
		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$errors = $this->validator->get_errors();
		$this->assertArrayHasKey( 'row_0_link_field', $errors );

		$value = array( array( 'link_field' => array( 'url' => 'https://example.com' ) ) );
		$this->assertTrue( $this->validator->validate_repeater( $value, $field ) );
	}

	/**
	 * Test get_row_errors method.
	 */
	public function test_get_row_errors(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'text_field',
					'type'     => 'text',
					'required' => true,
				),
			),
		);

		$value = array(
			array( 'text_field' => '' ),
			array( 'text_field' => 'OK' ),
			array( 'text_field' => '' ),
		);

		$this->validator->validate_repeater( $value, $field );

		$row0_errors = $this->validator->get_row_errors( 0 );
		$this->assertArrayHasKey( 'row_0_text_field', $row0_errors );

		$row1_errors = $this->validator->get_row_errors( 1 );
		$this->assertEmpty( $row1_errors );

		$row2_errors = $this->validator->get_row_errors( 2 );
		$this->assertArrayHasKey( 'row_2_text_field', $row2_errors );
	}

	/**
	 * Test get_field_errors method.
	 */
	public function test_get_field_errors(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'text_field',
					'type'     => 'text',
					'required' => true,
				),
			),
		);

		$value = array( array( 'text_field' => '' ) );

		$this->validator->validate_repeater( $value, $field );

		$errors = $this->validator->get_field_errors( 0, 'text_field' );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'required', $errors[0] );
	}

	/**
	 * Test get_first_error method.
	 */
	public function test_get_first_error(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'text_field',
					'type'     => 'text',
					'required' => true,
				),
			),
		);

		$value = array( array( 'text_field' => '' ) );

		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );

		$first_error = $this->validator->get_first_error();
		$this->assertNotNull( $first_error );
		$this->assertStringContainsString( 'required', $first_error );

		// Test with no errors.
		$this->validator = new Validator();
		$this->assertTrue( $this->validator->validate_repeater( array( array( 'text_field' => 'OK' ) ), $field ) );
		$this->assertNull( $this->validator->get_first_error() );
	}

	/**
	 * Test has_errors method.
	 */
	public function test_has_errors(): void {
		$field = array(
			'min_rows'   => 0,
			'max_rows'   => 0,
			'sub_fields' => array(
				array(
					'name'     => 'text_field',
					'type'     => 'text',
					'required' => true,
				),
			),
		);

		$value = array( array( 'text_field' => '' ) );

		$this->assertFalse( $this->validator->validate_repeater( $value, $field ) );
		$this->assertTrue( $this->validator->has_errors() );

		$this->validator = new Validator();
		$this->assertTrue( $this->validator->validate_repeater( array( array( 'text_field' => 'OK' ) ), $field ) );
		$this->assertFalse( $this->validator->has_errors() );
	}
}
