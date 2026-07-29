/**
 * Tests for ACFRepeaterSortable class.
 *
 * @package ACF_Repeater\Tests
 */

import ACFRepeaterSortable from '@admin/repeater-sortable';
import ACFRepeaterField from '@admin/repeater-field';

describe( 'ACFRepeaterSortable', () => {
	let fieldElement;
	let fieldController;
	let sortable;

	beforeEach( () => {
		// Create a repeater field with rows.
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
						<tr class="acf-repeater-row" data-row-id="row_1" data-row-index="0">
							<td class="acf-repeater-col-handle"><button class="acf-repeater-drag-handle"></button></td>
							<td class="acf-repeater-field-cell"></td>
							<td class="acf-repeater-col-actions"></td>
						</tr>
						<tr class="acf-repeater-row" data-row-id="row_2" data-row-index="1">
							<td class="acf-repeater-col-handle"><button class="acf-repeater-drag-handle"></button></td>
							<td class="acf-repeater-field-cell"></td>
							<td class="acf-repeater-col-actions"></td>
						</tr>
						<tr class="acf-repeater-row" data-row-id="row_3" data-row-index="2">
							<td class="acf-repeater-col-handle"><button class="acf-repeater-drag-handle"></button></td>
							<td class="acf-repeater-field-cell"></td>
							<td class="acf-repeater-col-actions"></td>
						</tr>
					</tbody>
				</table>
				<div class="acf-repeater-footer">
					<button type="button" class="button button-primary acf-repeater-add-row">Add Row</button>
				</div>
			</div>
		`;

		fieldElement = document.querySelector( '.acf-repeater' );
		fieldController = new ACFRepeaterField( fieldElement );
		sortable = new ACFRepeaterSortable( fieldController );
		sortable.init();
	} );

	afterEach( () => {
		jest.clearAllMocks();
		sortable.destroy();
	} );

	test( 'initializes correctly', () => {
		expect( sortable.fieldController ).toBe( fieldController );
		expect( sortable.draggedRow ).toBeNull();
		expect( sortable.startIndex ).toBe( -1 );
	} );

	test( 'moveRow reorders rows array', () => {
		// Move row from index 0 to index 2.
		sortable.moveRow( 0, 2 );

		expect( fieldController.rows[0].dataset.rowId ).toBe( 'row_2' );
		expect( fieldController.rows[1].dataset.rowId ).toBe( 'row_3' );
		expect( fieldController.rows[2].dataset.rowId ).toBe( 'row_1' );
	} );

	test( 'moveRow handles moving to earlier index', () => {
		// Move row from index 2 to index 0.
		sortable.moveRow( 2, 0 );

		expect( fieldController.rows[0].dataset.rowId ).toBe( 'row_3' );
		expect( fieldController.rows[1].dataset.rowId ).toBe( 'row_1' );
		expect( fieldController.rows[2].dataset.rowId ).toBe( 'row_2' );
	} );

	test( 'moveRow does nothing when fromIndex equals toIndex', () => {
		const originalOrder = fieldController.rows.map( r => r.dataset.rowId );
		sortable.moveRow( 1, 1 );

		const newOrder = fieldController.rows.map( r => r.dataset.rowId );
		expect( newOrder ).toEqual( originalOrder );
	} );

	test( 'createPlaceholder creates placeholder element', () => {
		const row = fieldController.rows[0];
		sortable.createPlaceholder( row );

		expect( sortable.placeholder ).toBeTruthy();
		expect( sortable.placeholder.classList.contains( 'acf-repeater-sortable-placeholder' ) ).toBe( true );
		expect( sortable.placeholder.style.opacity ).toBe( '0.5' );
	} );

	test( 'removePlaceholder removes placeholder from DOM', () => {
		const row = fieldController.rows[0];
		sortable.createPlaceholder( row );
		const placeholder = sortable.placeholder;

		sortable.removePlaceholder();

		expect( placeholder.parentNode ).toBeNull();
		expect( sortable.placeholder ).toBeNull();
	} );

	test( 'reorderDOM updates row indices and input names', () => {
		// Reorder rows: move row 0 to position 2.
		sortable.moveRow( 0, 2 );
		sortable.reorderDOM();

		const container = fieldElement.querySelector( '.acf-repeater-rows' );
		const rows = container.querySelectorAll( '.acf-repeater-row' );

		expect( rows[0].dataset.rowIndex ).toBe( '0' );
		expect( rows[1].dataset.rowIndex ).toBe( '1' );
		expect( rows[2].dataset.rowIndex ).toBe( '2' );
		expect( rows[0].dataset.rowId ).toBe( 'row_2' );
		expect( rows[2].dataset.rowId ).toBe( 'row_1' );
	} );

	test( 'destroy cleans up event listeners', () => {
		const container = fieldElement.querySelector( '.acf-repeater-rows' );
		const rows = container.querySelectorAll( '.acf-repeater-row' );

		// Check draggable attribute was added.
		expect( rows[0].hasAttribute( 'draggable' ) ).toBe( true );

		sortable.destroy();

		// Check draggable attribute was removed.
		expect( rows[0].hasAttribute( 'draggable' ) ).toBe( false );
	} );
} );