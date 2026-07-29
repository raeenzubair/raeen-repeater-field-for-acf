<?php
/**
 * Sanitizer Tests for ACF Repeater.
 *
 * @package ACF_Repeater\Tests
 */

namespace ACF_Repeater\Tests;

use ACF_Repeater\Helpers\Sanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Class SanitizerTest
 */
class SanitizerTest extends TestCase {

	/**
	 * Sanitizer instance.
	 *
	 * @var Sanitizer
	 */
	private Sanitizer $sanitizer;

	/**
	 * Set up before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->sanitizer = new Sanitizer();
	}

	/**
	 * Test sanitize_field_data method.
	 */
	public function test_sanitize_field_data(): void {
		$data = array(
			'text_field'   => '  Hello World  ',
			'email_field'  => '  USER@EXAMPLE.COM  ',
			'number_field' => '42',
			'bool_field'   => true,
			'nested'       => array(
				'inner_text' => '  Inner  ',
			),
		);

		$sanitized = $this->sanitizer->sanitize_field_data( $data );

		$this->assertSame( 'Hello World', $sanitized['text_field'] );
		$this->assertSame( 'USER@EXAMPLE.COM', $sanitized['email_field'] );
		$this->assertSame( 42, $sanitized['number_field'] );
		$this->assertSame( true, $sanitized['bool_field'] );
		$this->assertSame( 'Inner', $sanitized['nested']['inner_text'] );
	}

	/**
	 * Test sanitize_value method with various types.
	 */
	public function test_sanitize_value(): void {
		// String.
		$this->assertSame( 'hello', $this->sanitizer->sanitize_value( '  hello  ' ) );

		// Integer.
		$this->assertSame( 42, $this->sanitizer->sanitize_value( 42 ) );

		// Float.
		$this->assertSame( 3.14, $this->sanitizer->sanitize_value( 3.14 ) );

		// Boolean.
		$this->assertSame( true, $this->sanitizer->sanitize_value( true ) );
		$this->assertSame( false, $this->sanitizer->sanitize_value( false ) );

		// Array.
		$this->assertSame( array( 'a', 'b' ), $this->sanitizer->sanitize_value( array( '  a  ', '  b  ' ) ) );

		// Null.
		$this->assertNull( $this->sanitizer->sanitize_value( null ) );
	}

	/**
	 * Test sanitize_by_type for text fields.
	 */
	public function test_sanitize_by_type_text(): void {
		$field = array( 'type' => 'text' );
		$this->assertSame( 'hello', $this->sanitizer->sanitize_by_type( '  hello  ', $field ) );

		$field = array( 'type' => 'textarea' );
		$this->assertSame( 'hello world', $this->sanitizer->sanitize_by_type( '  hello world  ', $field ) );

		$field = array( 'type' => 'email' );
		$this->assertSame( 'user@example.com', $this->sanitizer->sanitize_by_type( '  USER@EXAMPLE.COM  ', $field ) );

		$field = array( 'type' => 'url' );
		$this->assertSame( 'https://example.com', $this->sanitizer->sanitize_by_type( '  https://example.com  ', $field ) );
	}

	/**
	 * Test sanitize_by_type for number fields.
	 */
	public function test_sanitize_by_type_number(): void {
		$field = array( 'type' => 'number' );
		$this->assertSame( 42, $this->sanitizer->sanitize_by_type( '42', $field ) );
		$this->assertSame( 3.14, $this->sanitizer->sanitize_by_type( 3.14, $field ) );
		$this->assertSame( 0, $this->sanitizer->sanitize_by_type( 'invalid', $field ) );

		$field = array( 'type' => 'range' );
		$this->assertSame( 50, $this->sanitizer->sanitize_by_type( '50', $field ) );
	}

	/**
	 * Test sanitize_by_type for WYSIWYG.
	 */
	public function test_sanitize_by_type_wysiwyg(): void {
		$field  = array( 'type' => 'wysiwyg' );
		$input  = '<p>Hello <script>alert(1)</script> World</p>';
		$output = $this->sanitizer->sanitize_by_type( $input, $field );
		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( 'Hello', $output );
		$this->assertStringContainsString( 'World', $output );
	}

	/**
	 * Test sanitize_by_type for select/radio.
	 */
	public function test_sanitize_by_type_select_radio(): void {
		$field = array( 'type' => 'select' );
		$this->assertSame( 'option1', $this->sanitizer->sanitize_by_type( '  option1  ', $field ) );

		$field = array( 'type' => 'radio' );
		$this->assertSame( 'choice1', $this->sanitizer->sanitize_by_type( '  choice1  ', $field ) );
	}

	/**
	 * Test sanitize_by_type for checkbox/true_false.
	 */
	public function test_sanitize_by_type_checkbox(): void {
		$field = array( 'type' => 'checkbox' );
		$this->assertSame( array( 'choice1', 'choice2' ), $this->sanitizer->sanitize_by_type( array( '  choice1  ', '  choice2  ' ), $field ) );

		$field = array( 'type' => 'true_false' );
		$this->assertSame( '1', $this->sanitizer->sanitize_by_type( true, $field ) );
		$this->assertSame( '0', $this->sanitizer->sanitize_by_type( false, $field ) );
		$this->assertSame( '1', $this->sanitizer->sanitize_by_type( '1', $field ) );
		$this->assertSame( '0', $this->sanitizer->sanitize_by_type( '0', $field ) );
	}

	/**
	 * Test sanitize_by_type for image/file.
	 */
	public function test_sanitize_by_type_file(): void {
		$field = array( 'type' => 'image' );
		$this->assertSame( 123, $this->sanitizer->sanitize_by_type( '123', $field ) );
		$this->assertSame( 0, $this->sanitizer->sanitize_by_type( 'invalid', $field ) );

		$field = array( 'type' => 'file' );
		$this->assertSame( 456, $this->sanitizer->sanitize_by_type( 456, $field ) );
	}

	/**
	 * Test sanitize_by_type for gallery.
	 */
	public function test_sanitize_by_type_gallery(): void {
		$field = array( 'type' => 'gallery' );
		$this->assertSame( array( 1, 2, 3 ), $this->sanitizer->sanitize_by_type( array( '1', '2', '3' ), $field ) );
		$this->assertSame( array(), $this->sanitizer->sanitize_by_type( 'invalid', $field ) );
	}

	/**
	 * Test sanitize_by_type for date/time pickers.
	 */
	public function test_sanitize_by_type_datetime(): void {
		$field = array( 'type' => 'date_picker' );
		$this->assertSame( '2024-01-15', $this->sanitizer->sanitize_by_type( '  2024-01-15  ', $field ) );

		$field = array( 'type' => 'time_picker' );
		$this->assertSame( '14:30', $this->sanitizer->sanitize_by_type( '  14:30  ', $field ) );

		$field = array( 'type' => 'datetime_picker' );
		$this->assertSame( '2024-01-15 14:30:00', $this->sanitizer->sanitize_by_type( '  2024-01-15 14:30:00  ', $field ) );
	}

	/**
	 * Test sanitize_by_type for color picker.
	 */
	public function test_sanitize_by_type_color_picker(): void {
		$field = array( 'type' => 'color_picker' );
		$this->assertSame( '#ff0000', $this->sanitizer->sanitize_by_type( '  #ff0000  ', $field ) );
		$this->assertSame( '', $this->sanitizer->sanitize_by_type( 'invalid', $field ) );
	}

	/**
	 * Test sanitize_by_type for link.
	 */
	public function test_sanitize_by_type_link(): void {
		$field    = array( 'type' => 'link' );
		$input    = array(
			'url'    => '  https://example.com  ',
			'title'  => '  Link Title  ',
			'target' => '_blank',
			'rel'    => 'noopener',
			'class'  => 'my-class',
		);
		$expected = array(
			'url'    => 'https://example.com',
			'title'  => 'Link Title',
			'target' => '_blank',
			'rel'    => 'noopener',
			'class'  => 'my-class',
		);
		$this->assertSame( $expected, $this->sanitizer->sanitize_by_type( $input, $field ) );

		// Test with invalid target.
		$input['target']    = '_self';
		$expected['target'] = '';
		$this->assertSame( $expected, $this->sanitizer->sanitize_by_type( $input, $field ) );
	}

	/**
	 * Test sanitize_row method.
	 */
	public function test_sanitize_row(): void {
		$field = array(
			'sub_fields' => array(
				array(
					'name' => 'text_field',
					'type' => 'text',
				),
				array(
					'name' => 'email_field',
					'type' => 'email',
				),
				array(
					'name' => 'number_field',
					'type' => 'number',
				),
			),
		);

		$row = array(
			'text_field'          => '  Hello  ',
			'email_field'         => '  USER@EXAMPLE.COM  ',
			'number_field'        => '42',
			'acf_repeater_row_id' => 'row_123',
		);

		$sanitized = $this->sanitizer->sanitize_row( $row, $field );

		$this->assertSame( 'Hello', $sanitized['text_field'] );
		$this->assertSame( 'USER@EXAMPLE.COM', $sanitized['email_field'] );
		$this->assertSame( 42, $sanitized['number_field'] );
		$this->assertSame( 'row_123', $sanitized['acf_repeater_row_id'] );
	}

	/**
	 * Test sanitize_field_settings method.
	 */
	public function test_sanitize_field_settings(): void {
		$settings = array(
			'min_rows'        => '5',
			'max_rows'        => '10',
			'button_label'    => '  Add Item  ',
			'layout'          => 'block',
			'collapsed'       => '  field_name  ',
			'sortable'        => '1',
			'duplicate'       => '0',
			'delete_confirm'  => 'true',
			'default_rows'    => array( array( 'text' => '  default  ' ) ),
			'invalid_setting' => 'should be ignored',
		);

		$sanitized = $this->sanitizer->sanitize_field_settings( $settings );

		$this->assertSame( 5, $sanitized['min_rows'] );
		$this->assertSame( 10, $sanitized['max_rows'] );
		$this->assertSame( 'Add Item', $sanitized['button_label'] );
		$this->assertSame( 'block', $sanitized['layout'] );
		$this->assertSame( 'field_name', $sanitized['collapsed'] );
		$this->assertTrue( $sanitized['sortable'] );
		$this->assertFalse( $sanitized['duplicate'] );
		$this->assertTrue( $sanitized['delete_confirm'] );
		$this->assertSame( array( array( 'text' => 'default' ) ), $sanitized['default_rows'] );
		$this->assertArrayNotHasKey( 'invalid_setting', $sanitized );
	}

	/**
	 * Test sanitize_field_settings with invalid layout.
	 */
	public function test_sanitize_field_settings_invalid_layout(): void {
		$settings = array(
			'layout' => 'invalid_layout',
		);

		$sanitized = $this->sanitizer->sanitize_field_settings( $settings );
		$this->assertSame( 'table', $sanitized['layout'] );
	}

	/**
	 * Test sanitize_sub_fields method.
	 */
	public function test_sanitize_sub_fields(): void {
		$sub_fields = array(
			array(
				'key'               => 'field_123',
				'label'             => '  Text Field  ',
				'name'              => '  text-field  ',
				'type'              => 'text',
				'instructions'      => '  Enter text  ',
				'required'          => '1',
				'placeholder'       => '  Placeholder  ',
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_456',
							'operator' => '==',
							'value'    => '  test  ',
						),
					),
				),
				'wrapper'           => array(
					'width' => '50',
					'class' => 'my-class',
					'id'    => 'my-id',
				),
			),
		);

		$sanitized = $this->sanitizer->sanitize_sub_fields( $sub_fields );

		$this->assertCount( 1, $sanitized );
		$this->assertSame( 'field_123', $sanitized[0]['key'] );
		$this->assertSame( 'Text Field', $sanitized[0]['label'] );
		$this->assertSame( 'text-field', $sanitized[0]['name'] );
		$this->assertSame( 'text', $sanitized[0]['type'] );
		$this->assertSame( 'Enter text', $sanitized[0]['instructions'] );
		$this->assertTrue( $sanitized[0]['required'] );
		$this->assertSame( 'Placeholder', $sanitized[0]['placeholder'] );
		$this->assertSame( 'test', $sanitized[0]['conditional_logic'][0][0]['value'] );
		$this->assertSame( '50', $sanitized[0]['wrapper']['width'] );
		$this->assertSame( 'my-class', $sanitized[0]['wrapper']['class'] );
		$this->assertSame( 'my-id', $sanitized[0]['wrapper']['id'] );
	}

	/**
	 * Test prepare_for_database method.
	 */
	public function test_prepare_for_database(): void {
		$field = array(
			'min_rows'   => 1,
			'max_rows'   => 5,
			'sub_fields' => array(
				array(
					'name'     => 'text_field',
					'type'     => 'text',
					'required' => true,
				),
			),
		);

		$value = array(
			array( 'text_field' => '  Row 1  ' ),
			array( 'text_field' => '  Row 2  ' ),
		);

		$prepared = $this->sanitizer->prepare_for_database( $value, $field );

		$this->assertCount( 2, $prepared );
		$this->assertSame( 'Row 1', $prepared[0]['text_field'] );
		$this->assertSame( 'Row 2', $prepared[1]['text_field'] );
		$this->assertArrayHasKey( 'acf_repeater_row_id', $prepared[0] );
		$this->assertArrayHasKey( 'acf_repeater_row_index', $prepared[0] );
	}

	/**
	 * Test prepare_for_display method.
	 */
	public function test_prepare_for_display(): void {
		$field = array(
			'sub_fields' => array(
				array(
					'name' => 'text_field',
					'type' => 'text',
				),
				array(
					'name' => 'wysiwyg_field',
					'type' => 'wysiwyg',
				),
			),
		);

		$value = array(
			array(
				'text_field'          => 'Row 1',
				'wysiwyg_field'       => '<p>Content 1</p>',
				'acf_repeater_row_id' => 'row_1',
			),
			array(
				'text_field'          => 'Row 2',
				'wysiwyg_field'       => '<p>Content 2</p>',
				'acf_repeater_row_id' => 'row_2',
			),
		);

		$prepared = $this->sanitizer->prepare_for_display( $value, $field );

		$this->assertCount( 2, $prepared );
		$this->assertSame( 0, $prepared[0]['index'] );
		$this->assertSame( 'row_1', $prepared[0]['id'] );
		$this->assertSame( 'Row 1', $prepared[0]['data']['text_field'] );
		$this->assertSame( '<p>Content 1</p>', $prepared[0]['data']['wysiwyg_field'] );
	}
}
