/**
 * Raeen Repeater Field for ACF Sub-fields - Sub-field initialization and management
 *
 * @package    Raeen_Repeater
 * @repository https://github.com/raeenzubair/repeater-field-for-acf
 * @license    GPL-2.0-or-later
 */

class ACFRepeaterSubFields {
	constructor( fieldController ) {
		this.fieldController = fieldController;
		this.initializedFields = new WeakMap();
	}

	/**
	 * Initialize sub-fields for a row.
	 *
	 * @param {HTMLElement} rowElement Row element.
	 */
	initRowSubFields( rowElement ) {
		// ACF handles most field initialization via its own events.
		// We just need to ensure proper naming and event binding.

		const rowIndex = parseInt( rowElement.dataset.rowIndex, 10 );
		const prefix = `${this.fieldController.fieldName}[${rowIndex}]`;

		// Find all ACF fields in this row.
		rowElement.querySelectorAll( '.acf-field' ).forEach( fieldWrapper => {
			const fieldKey = fieldWrapper.dataset.key;
			if ( ! fieldKey || this.initializedFields.has( fieldWrapper ) ) {
				return;
			}

			// Update input names for this row index.
			this.updateFieldNames( fieldWrapper, prefix );

			// Initialize field-specific behavior.
			this.initFieldType( fieldWrapper );

			this.initializedFields.set( fieldWrapper, true );
		} );
	}

	/**
	 * Update input names for a field wrapper.
	 *
	 * @param {HTMLElement} fieldWrapper Field wrapper element.
	 * @param {string} prefix Prefix for input names.
	 */
	updateFieldNames( fieldWrapper, prefix ) {
		const inputs = fieldWrapper.querySelectorAll( 'input, select, textarea' );
		inputs.forEach( input => {
			if ( ! input.name ) return;

			// Check if name already has the correct prefix.
			if ( input.name.startsWith( prefix ) ) {
				return;
			}

			// Extract the sub-field name from the original name.
			const originalPrefix = this.fieldController.fieldName + '[';
			if ( input.name.startsWith( originalPrefix ) ) {
				const match = input.name.match( new RegExp( `${this.fieldController.fieldName}\\[\\d+\\]\\[(.+)\\]$` ) );
				if ( match ) {
					input.name = `${prefix}[${match[1]}]`;
				}
			}
		} );
	}

	/**
	 * Initialize field type specific behavior.
	 *
	 * @param {HTMLElement} fieldWrapper Field wrapper element.
	 */
	initFieldType( fieldWrapper ) {
		const fieldType = fieldWrapper.dataset.type;
		if ( ! fieldType ) return;

		switch ( fieldType ) {
			case 'image':
			case 'file':
				this.initFileField( fieldWrapper );
				break;

			case 'gallery':
				this.initGalleryField( fieldWrapper );
				break;

			case 'wysiwyg':
				this.initWysiwygField( fieldWrapper );
				break;

			case 'date_picker':
			case 'time_picker':
			case 'datetime_picker':
				this.initDateTimeField( fieldWrapper, fieldType );
				break;

			case 'color_picker':
				this.initColorPickerField( fieldWrapper );
				break;

			case 'select':
				this.initSelectField( fieldWrapper );
				break;

			case 'repeater':
				this.initNestedRepeater( fieldWrapper );
				break;
		}
	}

	/**
	 * Initialize file/image field.
	 */
	initFileField( fieldWrapper ) {
		const selectBtn = fieldWrapper.querySelector( '.repeater-field-for-acf-file-select' );
		const removeBtn = fieldWrapper.querySelector( '.repeater-field-for-acf-file-remove' );
		const hiddenInput = fieldWrapper.querySelector( '.repeater-field-for-acf-file-input' );
		const preview = fieldWrapper.querySelector( '.repeater-field-for-acf-file-preview' );

		if ( selectBtn ) {
			selectBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				this.openMediaLibrary( fieldWrapper, selectBtn.dataset.type || 'image' );
			} );
		}

		if ( removeBtn && hiddenInput ) {
			removeBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				hiddenInput.value = '';
				if ( preview ) preview.innerHTML = '';
				removeBtn.style.display = 'none';
				selectBtn.textContent = this.fieldController.i18n.select || 'Select';
				// Trigger change for ACF.
				hiddenInput.dispatchEvent( new Event( 'change' ) );
			} );
		}
	}

	/**
	 * Open WordPress media library.
	 */
	openMediaLibrary( fieldWrapper, type ) {
		if ( typeof wp === 'undefined' || ! wp.media ) {
			console.warn( 'WordPress media library not available' );
			return;
		}

		const hiddenInput = fieldWrapper.querySelector( '.repeater-field-for-acf-file-input' );
		const preview = fieldWrapper.querySelector( '.repeater-field-for-acf-file-preview' );
		const selectBtn = fieldWrapper.querySelector( '.repeater-field-for-acf-file-select' );
		const removeBtn = fieldWrapper.querySelector( '.repeater-field-for-acf-file-remove' );

		const frame = wp.media({
			title: type === 'image' ? 'Select Image' : 'Select File',
			library: { type: type === 'image' ? 'image' : '' },
			multiple: false,
		});

		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();

			if ( hiddenInput ) {
				hiddenInput.value = attachment.id;
				hiddenInput.dispatchEvent( new Event( 'change' ) );
			}

			if ( preview ) {
				if ( type === 'image' && attachment.sizes ) {
					preview.innerHTML = `<img src="${attachment.sizes.thumbnail?.url || attachment.url}" alt="" style="max-width:150px;height:auto;" />`;
				} else {
					preview.innerHTML = `<a href="${attachment.url}" target="_blank">${attachment.title}</a>`;
				}
			}

			if ( selectBtn ) {
				selectBtn.textContent = type === 'image' ? 'Change Image' : 'Change File';
			}

			if ( removeBtn ) {
				removeBtn.style.display = 'inline-block';
			}
		});

		frame.open();
	}

	/**
	 * Initialize gallery field.
	 */
	initGalleryField( fieldWrapper ) {
		// Gallery fields use ACF's built-in gallery handling.
		// We just need to ensure proper naming.
	}

	/**
	 * Initialize WYSIWYG field.
	 */
	initWysiwygField( fieldWrapper ) {
		// ACF handles WYSIWYG initialization via its own system.
		// TinyMCE is initialized by ACF on append.
	}

	/**
	 * Initialize date/time picker field.
	 */
	initDateTimeField( fieldWrapper, type ) {
		const input = fieldWrapper.querySelector( 'input' );
		if ( ! input ) return;

		// ACF handles date/time picker initialization.
		// We just ensure the input has the right classes.
		input.classList.add( `acf-${type}` );

		const format = fieldWrapper.dataset[ `${type.replace( '_', '-' ) }Format` ];
		if ( format ) {
			input.dataset[ `${type.replace( '_', '-' ) }Format` ] = format;
		}
	}

	/**
	 * Initialize color picker field.
	 */
	initColorPickerField( fieldWrapper ) {
		const input = fieldWrapper.querySelector( 'input' );
		if ( ! input ) return;

		input.classList.add( 'acf-color-picker' );
		// ACF initializes Iris color picker.
	}

	/**
	 * Initialize select field (Select2).
	 */
	initSelectField( fieldWrapper ) {
		const select = fieldWrapper.querySelector( 'select' );
		if ( ! select ) return;

		// ACF initializes Select2.
		if ( select.classList.contains( 'acf-select' ) ) {
			return;
		}

		select.classList.add( 'acf-select' );
	}

	/**
	 * Initialize nested repeater.
	 */
	initNestedRepeater( fieldWrapper ) {
		const nestedRepeater = fieldWrapper.querySelector( '.repeater-field-for-acf' );
		if ( nestedRepeater && ! nestedRepeater.acfRepeaterField ) {
			nestedRepeater.acfRepeaterField = new ACFRepeaterField( nestedRepeater );
		}
	}

	/**
	 * Cleanup sub-fields for a row being removed.
	 *
	 * @param {HTMLElement} rowElement Row element.
	 */
	cleanupRowSubFields( rowElement ) {
		rowElement.querySelectorAll( '.acf-field' ).forEach( fieldWrapper => {
			const fieldType = fieldWrapper.dataset.type;

			// Cleanup WYSIWYG editors.
			if ( fieldType === 'wysiwyg' ) {
				const editorId = fieldWrapper.querySelector( 'textarea' )?.id;
				if ( editorId && typeof tinyMCE !== 'undefined' ) {
					const editor = tinyMCE.get( editorId );
					if ( editor ) {
						editor.remove();
					}
				}
			}

			// Cleanup Select2.
			if ( fieldType === 'select' ) {
				const select = fieldWrapper.querySelector( 'select' );
				if ( select && select.select2 ) {
					select.select2( 'destroy' );
				}
			}

			// Cleanup color picker.
			if ( fieldType === 'color_picker' ) {
				const input = fieldWrapper.querySelector( 'input' );
				if ( input && input.iris ) {
					input.iris( 'destroy' );
				}
			}

			// Cleanup nested repeaters.
			if ( fieldType === 'repeater' ) {
				const nestedRepeater = fieldWrapper.querySelector( '.repeater-field-for-acf' );
				if ( nestedRepeater && nestedRepeater.acfRepeaterField ) {
					nestedRepeater.acfRepeaterField.destroy();
				}
			}

			this.initializedFields.delete( fieldWrapper );
		} );
	}
}

export default ACFRepeaterSubFields;