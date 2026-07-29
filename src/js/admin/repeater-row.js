/**
 * ACF Repeater Row - Row-level functionality
 *
 * @package ACF_Repeater
 */

class ACFRepeaterRow {
	constructor( rowElement, fieldController ) {
		this.element = rowElement;
		this.fieldController = fieldController;
		this.rowIndex = parseInt( rowElement.dataset.rowIndex, 10 );
		this.rowId = rowElement.dataset.rowId;
		this.isCollapsed = false;

		this.init();
	}

	init() {
		this.bindEvents();
		this.initSubFields();
	}

	bindEvents() {
		// Input change tracking.
		this.element.addEventListener( 'change', ( e ) => this.onInputChange( e ) );
		this.element.addEventListener( 'input', ( e ) => this.onInputChange( e ) );

		// Focus/blur for validation.
		this.element.addEventListener( 'focusin', ( e ) => this.onFocusIn( e ) );
		this.element.addEventListener( 'focusout', ( e ) => this.onFocusOut( e ) );
	}

	initSubFields() {
		// Initialize sub-fields within this row.
		// This is handled by ACF's append action.
	}

	onInputChange( event ) {
		const input = event.target;
		const name = input.name;

		if ( ! name || ! name.startsWith( this.fieldController.fieldName ) ) {
			return;
		}

		// Debounce save.
		clearTimeout( this.saveTimeout );
		this.saveTimeout = setTimeout( () => {
			this.saveRowData();
		}, 500 );
	}

	async saveRowData() {
		const rowData = this.getRowData();

		try {
			const response = await this.fieldController.ajaxRequest( 'acf_repeater_save_row', {
				field_key: this.fieldController.fieldKey,
				post_id: this.fieldController.getPostId(),
				row_index: this.rowIndex,
				row_data: rowData,
			} );

			if ( ! response.success ) {
				console.warn( 'Failed to save row data:', response.message );
			}
		} catch ( error ) {
			console.error( 'Error saving row data:', error );
		}
	}

	getRowData() {
		const data = {};
		const prefix = `${this.fieldController.fieldName}[${this.rowIndex}][`;

		this.element.querySelectorAll( 'input, select, textarea' ).forEach( input => {
			if ( input.name && input.name.startsWith( prefix ) ) {
				// Extract sub field name.
				const match = input.name.match( new RegExp( `${this.fieldController.fieldName}\\[${this.rowIndex}\\]\\[(.+)\\]$` ) );
				if ( match ) {
					const subFieldName = match[1];

					if ( input.type === 'checkbox' ) {
						if ( ! data[ subFieldName ] ) {
							data[ subFieldName ] = [];
						}
						if ( input.checked ) {
							data[ subFieldName ].push( input.value );
						}
					} else if ( input.type === 'radio' ) {
						if ( input.checked ) {
							data[ subFieldName ] = input.value;
						}
					} else {
						data[ subFieldName ] = input.value;
					}
				}
			}
		} );

		return data;
	}

	onFocusIn( event ) {
		this.element.classList.add( 'repeater-field-for-acf-row-focused' );
	}

	onFocusOut( event ) {
		// Check if focus moved outside this row.
		setTimeout( () => {
			if ( ! this.element.contains( document.activeElement ) ) {
				this.element.classList.remove( 'repeater-field-for-acf-row-focused' );
			}
		}, 0 );
	}

	setCollapsed( collapsed ) {
		this.isCollapsed = collapsed;
		this.element.classList.toggle( 'repeater-field-for-acf-row-collapsed', collapsed );

		const toggleBtn = this.element.querySelector( '.repeater-field-for-acf-row-toggle, .repeater-field-for-acf-block-toggle' );
		if ( toggleBtn ) {
			toggleBtn.setAttribute( 'aria-expanded', ! collapsed );
			const icon = toggleBtn.querySelector( '.dashicons' );
			if ( icon ) {
				icon.className = collapsed ? 'dashicons dashicons-arrow-right-alt2' : 'dashicons dashicons-arrow-down-alt2';
			}
		}

		if ( this.fieldController.layout === 'block' ) {
			const content = this.element.querySelector( '.repeater-field-for-acf-block-content' );
			if ( content ) {
				content.style.display = collapsed ? 'none' : '';
			}
		}
	}

	destroy() {
		clearTimeout( this.saveTimeout );
		this.element.removeEventListener( 'change', this.boundChangeHandler );
		this.element.removeEventListener( 'input', this.boundInputHandler );
		this.element.removeEventListener( 'focusin', this.boundFocusInHandler );
		this.element.removeEventListener( 'focusout', this.boundFocusOutHandler );
	}
}

export default ACFRepeaterRow;