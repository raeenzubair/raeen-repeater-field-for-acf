/**
 * ACF Repeater Sortable - Drag and drop row sorting
 *
 * @package ACF_Repeater
 */

class ACFRepeaterSortable {
	constructor( fieldController ) {
		this.fieldController = fieldController;
		this.sortableInstance = null;
		this.placeholder = null;
		this.draggedRow = null;
		this.startIndex = -1;
	}

	init() {
		if ( ! this.fieldController.sortableEnabled ) {
			return;
		}

		// Use native HTML5 Drag and Drop API (no jQuery UI dependency).
		this.bindDragEvents();
	}

	bindDragEvents() {
		const container = this.fieldController.layout === 'table'
			? this.fieldController.element.querySelector( '.repeater-field-for-acf-rows' )
			: this.fieldController.element.querySelector( '.repeater-field-for-acf-blocks' );

		if ( ! container ) return;

		// Make rows draggable.
		container.querySelectorAll( '.repeater-field-for-acf-row, .repeater-field-for-acf-block-row' ).forEach( row => {
			row.setAttribute( 'draggable', 'true' );
			row.addEventListener( 'dragstart', ( e ) => this.onDragStart( e, row ) );
			row.addEventListener( 'dragend', ( e ) => this.onDragEnd( e, row ) );
			row.addEventListener( 'dragover', ( e ) => this.onDragOver( e, row ) );
			row.addEventListener( 'dragleave', ( e ) => this.onDragLeave( e, row ) );
			row.addEventListener( 'drop', ( e ) => this.onDrop( e, row ) );
		} );

		// Handle for better UX.
		container.querySelectorAll( '.repeater-field-for-acf-drag-handle' ).forEach( handle => {
			handle.addEventListener( 'mousedown', ( e ) => {
				const row = handle.closest( '.repeater-field-for-acf-row, .repeater-field-for-acf-block-row' );
				if ( row ) {
					row.setAttribute( 'draggable', 'true' );
				}
			} );
		} );
	}

	onDragStart( event, row ) {
		this.draggedRow = row;
		this.startIndex = parseInt( row.dataset.rowIndex, 10 );
		row.classList.add( 'repeater-field-for-acf-row-dragging' );

		// Set drag data.
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData( 'text/plain', this.startIndex.toString() );

		// Create placeholder.
		this.createPlaceholder( row );
	}

	onDragEnd( event, row ) {
		row.classList.remove( 'repeater-field-for-acf-row-dragging' );
		this.removePlaceholder();
		this.draggedRow = null;
		this.startIndex = -1;
	}

	onDragOver( event, row ) {
		event.preventDefault();
		event.dataTransfer.dropEffect = 'move';

		if ( row === this.draggedRow ) return;

		// Show drop indicator.
		row.classList.add( 'repeater-field-for-acf-drop-target' );

		// Determine insert position (before or after).
		const rect = row.getBoundingClientRect();
		const midY = rect.top + rect.height / 2;

		if ( event.clientY < midY ) {
			row.classList.add( 'repeater-field-for-acf-drop-before' );
			row.classList.remove( 'repeater-field-for-acf-drop-after' );
		} else {
			row.classList.add( 'repeater-field-for-acf-drop-after' );
			row.classList.remove( 'repeater-field-for-acf-drop-before' );
		}
	}

	onDragLeave( event, row ) {
		// Only remove if actually leaving the row (not entering a child).
		if ( ! row.contains( event.relatedTarget ) ) {
			row.classList.remove( 'repeater-field-for-acf-drop-target', 'repeater-field-for-acf-drop-before', 'repeater-field-for-acf-drop-after' );
		}
	}

	onDrop( event, row ) {
		event.preventDefault();

		if ( row === this.draggedRow ) return;

		const targetIndex = parseInt( row.dataset.rowIndex, 10 );
		const isBefore = row.classList.contains( 'repeater-field-for-acf-drop-before' );
		const newIndex = isBefore ? targetIndex : targetIndex + 1;

		// Adjust for removed dragged row.
		let adjustedNewIndex = newIndex;
		if ( this.startIndex < adjustedNewIndex ) {
			adjustedNewIndex--;
		}

		this.moveRow( this.startIndex, adjustedNewIndex );

		// Cleanup.
		row.classList.remove( 'repeater-field-for-acf-drop-target', 'repeater-field-for-acf-drop-before', 'repeater-field-for-acf-drop-after' );
	}

	moveRow( fromIndex, toIndex ) {
		if ( fromIndex === toIndex ) return;

		const rows = this.fieldController.rows;
		const row = rows[ fromIndex ];

		// Remove from old position.
		rows.splice( fromIndex, 1 );

		// Insert at new position.
		rows.splice( toIndex, 0, row );

		// Re-render in DOM.
		this.reorderDOM();

		// Update indices.
		this.fieldController.updateRowIndices();

		// Save to server.
		this.fieldController.saveRowOrder();
	}

	reorderDOM() {
		const container = this.fieldController.layout === 'table'
			? this.fieldController.element.querySelector( '.repeater-field-for-acf-rows' )
			: this.fieldController.element.querySelector( '.repeater-field-for-acf-blocks' );

		if ( ! container ) return;

		// Detach all rows and re-append in order.
		this.fieldController.rows.forEach( ( row, index ) => {
			row.dataset.rowIndex = index;
			this.fieldController.updateInputNames( row, index );
			container.appendChild( row );
		} );
	}

	createPlaceholder( row ) {
		this.placeholder = row.cloneNode( true );
		this.placeholder.classList.add( 'repeater-field-for-acf-sortable-placeholder' );
		this.placeholder.style.opacity = '0.5';
		this.placeholder.style.pointerEvents = 'none';
		this.placeholder.removeAttribute( 'draggable' );

		// Clear inputs in placeholder.
		this.placeholder.querySelectorAll( 'input, select, textarea' ).forEach( input => {
			input.disabled = true;
			input.style.visibility = 'hidden';
		} );

		row.parentNode.insertBefore( this.placeholder, row.nextSibling );
	}

	removePlaceholder() {
		if ( this.placeholder && this.placeholder.parentNode ) {
			this.placeholder.parentNode.removeChild( this.placeholder );
		}
		this.placeholder = null;
	}

	destroy() {
		const container = this.fieldController.layout === 'table'
			? this.fieldController.element.querySelector( '.repeater-field-for-acf-rows' )
			: this.fieldController.element.querySelector( '.repeater-field-for-acf-blocks' );

		if ( container ) {
			container.querySelectorAll( '.repeater-field-for-acf-row, .repeater-field-for-acf-block-row' ).forEach( row => {
				row.removeAttribute( 'draggable' );
				row.removeEventListener( 'dragstart', this.boundDragStart );
				row.removeEventListener( 'dragend', this.boundDragEnd );
				row.removeEventListener( 'dragover', this.boundDragOver );
				row.removeEventListener( 'dragleave', this.boundDragLeave );
				row.removeEventListener( 'drop', this.boundDrop );
			} );
		}
	}
}

export default ACFRepeaterSortable;