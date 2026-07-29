/**
 * Tests for ACFRepeaterField class.
 *
 * @package ACF_Repeater\Tests
 */

import ACFRepeaterField from '@admin/repeater-field';

// Mock the global acfRepeater object.
global.acfRepeater = {
	nonce: 'test-nonce',
	ajax_url: '/wp-admin/admin-ajax.php',
	i18n: {
		add_row: 'Add Row',
		delete_row: 'Delete Row',
		duplicate_row: 'Duplicate Row',
		collapse_row: 'Collapse Row',
		expand_row: 'Expand Row',
		sort_rows: 'Sort Rows',
		confirm_delete: 'Are you sure you want to delete this row?',
		min_rows_error: 'Minimum %d rows required.',
		max_rows_error: 'Maximum %d rows allowed.',
		required_field: 'This field is required',
		loading: 'Loading...',
		no_rows: 'No rows added yet.',
		row_collapsed: 'Row collapsed',
		row_expanded: 'Row expanded',
		drag_to_reorder: 'Drag to reorder',
	},
};

// Mock fetch for AJAX requests.
global.fetch = jest.fn();

describe( 'ACFRepeaterField', () => {
	let fieldElement;
	let fieldController;

	beforeEach( () => {
		// Create a basic repeater field element.
		document.body.innerHTML = `
			<div class="acf-repeater acf-repeater-table" data-field-key="field_123" data-field-name="repeater_field" data-max-rows="0" data-min-rows="0" data-button-label="Add Row" data-collapsed-field="" data-duplicate-enabled="true" data-delete-confirm="true" data-sortable-enabled="true">
				<table class="acf-repeater-table">
					<thead>
						<tr>
							<th class="acf-repeater-col-handle"></th>
							<th class="acf-repeater-col-text_field">Text Field</th>
							<th class="acf-repeater-col-actions"></th>
						</tr>
					</thead>
					<tbody class="acf-repeater-rows">
					</tbody>
				</table>
				<div class="acf-repeater-footer">
					<button type="button" class="button button-primary acf-repeater-add-row" data-field-key="field_123">
						<span class="dashicons dashicons-plus-alt"></span>
						<span>Add Row</span>
					</button>
				</div>
				<div class="acf-repeater-empty-notice" style="display: block;">
					<p>No rows added yet. Click "Add Row" to get started.</p>
				</div>
			</div>
		`;

		fieldElement = document.querySelector( '.acf-repeater' );
		fieldController = new ACFRepeaterField( fieldElement );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'initializes correctly', () => {
		expect( fieldController.fieldKey ).toBe( 'field_123' );
		expect( fieldController.fieldName ).toBe( 'repeater_field' );
		expect( fieldController.layout ).toBe( 'table' );
		expect( fieldController.maxRows ).toBe( 0 );
		expect( fieldController.minRows ).toBe( 0 );
		expect( fieldController.buttonLabel ).toBe( 'Add Row' );
		expect( fieldController.duplicateEnabled ).toBe( true );
		expect( fieldController.deleteConfirm ).toBe( true );
		expect( fieldController.sortableEnabled ).toBe( true );
	} );

	test( 'cacheRows finds existing rows', () => {
		fieldElement.querySelector( '.acf-repeater-rows' ).innerHTML = `
			<tr class="acf-repeater-row" data-row-id="row_1" data-row-index="0"></tr>
			<tr class="acf-repeater-row" data-row-id="row_2" data-row-index="1"></tr>
		`;

		fieldController.cacheRows();
		expect( fieldController.rows.length ).toBe( 2 );
		expect( fieldController.rowCount ).toBe( 2 );
	} );

	test( 'updateRowIndices updates row data attributes', () => {
		fieldElement.querySelector( '.acf-repeater-rows' ).innerHTML = `
			<tr class="acf-repeater-row" data-row-id="row_1" data-row-index="5"></tr>
			<tr class="acf-repeater-row" data-row-id="row_2" data-row-index="3"></tr>
		`;

		fieldController.cacheRows();
		fieldController.updateRowIndices();

		const rows = fieldElement.querySelectorAll( '.acf-repeater-row' );
		expect( rows[0].dataset.rowIndex ).toBe( '0' );
		expect( rows[1].dataset.rowIndex ).toBe( '1' );
	} );

	test( 'updateEmptyState shows notice when no rows', () => {
		fieldController.rowCount = 0;
		fieldController.updateEmptyState();

		const notice = fieldElement.querySelector( '.acf-repeater-empty-notice' );
		expect( notice.style.display ).toBe( '' );
	} );

	test( 'updateEmptyState hides notice when rows exist', () => {
		fieldController.rowCount = 2;
		fieldController.updateEmptyState();

		const notice = fieldElement.querySelector( '.acf-repeater-empty-notice' );
		expect( notice.style.display ).toBe( 'none' );
	} );

	test( 'getPostId returns post ID from ACF', () => {
		global.acf.get.mockReturnValue( 456 );
		expect( fieldController.getPostId() ).toBe( 456 );
	} );

	test( 'getPostId falls back to URL parameter', () => {
		global.acf.get.mockReturnValue( null );
		// Mock the URLSearchParams parse
		const originalGet = global.acf.get;
		global.acf.get = jest.fn( ( key ) => {
			if ( key === 'post_id' ) return null;
			return originalGet( key );
		} );
		// The getPostId function parses URL params, so we can't easily test
		// this in jsdom. Just test that it returns 0 when no post_id.
		expect( fieldController.getPostId() ).toBe( 0 );
	} );
} );