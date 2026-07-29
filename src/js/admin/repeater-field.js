/**
 * ACF Repeater Field — Core field controller.
 *
 * Manages row add/remove/duplicate/sort operations for the repeater field.
 * Works with ACF Free's form submission format: input names are generated
 * by ACF's own acf_render_field() using prefix + _name, resulting in:
 *   acf[{field_name}][{row_index}][{sub_field_name}]
 *
 * @package ACF_Repeater
 */

class ACFRepeaterField {
	constructor( element ) {
		this.element           = element;
		this.fieldKey          = element.dataset.fieldKey || '';
		this.fieldName         = element.dataset.fieldName || '';
		this.layout            = element.dataset.layout || 'table';
		this.collapsedFieldKey = element.dataset.collapsedField || '';
		this.maxRows           = parseInt( element.dataset.maxRows || '0', 10 );
		this.minRows           = parseInt( element.dataset.minRows || '0', 10 );
		this.sortable          = element.dataset.sortable !== 'false';
		this.duplicate         = element.dataset.duplicate !== 'false';
		this.deleteConfirm     = element.dataset.deleteConfirm !== 'false';
		this.nonce             = ( typeof acfRepeater !== 'undefined' && acfRepeater.nonce ) || '';
		this.ajaxUrl           = ( typeof acfRepeater !== 'undefined' && acfRepeater.ajax_url ) || '';
		this.i18n              = ( typeof acfRepeater !== 'undefined' && acfRepeater.i18n ) || {};

		this._rows            = [];
		this.sortableInstance = null;

		this.init();
	}

	init() {
		this.cacheRows();
		this.bindEvents();
		this.initSortable();
		this.updateRowNumbers();
		this.updateAllCollapsedTitles();
	}

	// ─── Event Binding ────────────────────────────────────────────────────────

	bindEvents() {
		// Use event delegation on the container so it covers dynamically added rows.
		this.element.addEventListener( 'click', ( e ) => {
			// Add Row button.
			const addBtn = e.target.closest( '.repeater-field-for-acf-add-row-btn, .acf-add-row-btn, [data-name="add-row"], .acf-button.blue' );
			if ( addBtn && this.element.contains( addBtn ) ) {
				e.preventDefault();
				// Only handle add-row buttons inside THIS repeater, not nested ones.
				if ( this.isDirectChild( addBtn ) ) {
					this.addRow();
				}
				return;
			}

			// Remove Row button.
			const removeBtn = e.target.closest( '.acf-remove-row, .acf-icon.-minus' );
			if ( removeBtn ) {
				const row = removeBtn.closest( '.acf-row' );
				if ( row && this.ownsRow( row ) ) {
					e.preventDefault();
					this.removeRow( row );
				}
				return;
			}

			// Duplicate Row button.
			const dupBtn = e.target.closest( '.acf-duplicate-row, .acf-icon.-duplicate' );
			if ( dupBtn ) {
				const row = dupBtn.closest( '.acf-row' );
				if ( row && this.ownsRow( row ) ) {
					e.preventDefault();
					this.duplicateRow( row );
				}
				return;
			}

			// Collapse/expand row toggle button.
			const collapseBtn = e.target.closest( '.acf-icon.-collapse, [data-event="collapse-row"], .acf-row-handle.order' );
			if ( collapseBtn ) {
				// Don't collapse when clicking on drag handle directly if reordering
				if ( e.target.closest( '.acf-sortable-handle' ) ) return;

				const row = collapseBtn.closest( '.acf-row' );
				if ( row && this.ownsRow( row ) ) {
					e.preventDefault();
					this.toggleRow( row );
				}
				return;
			}
		} );

		// Update collapsed title live on input change.
		this.element.addEventListener( 'input', ( e ) => {
			const row = e.target.closest( '.acf-row' );
			if ( row && this.ownsRow( row ) ) {
				this.updateCollapsedTitle( row );
			}
		} );
	}

	/**
	 * Check if an element is a direct child of this repeater
	 * (not inside a nested repeater).
	 */
	isDirectChild( el ) {
		// Walk up and check for the first .repeater-field-for-acf ancestor.
		let node = el.parentElement;
		while ( node ) {
			if ( node === this.element ) return true;
			if ( node.classList && node.classList.contains( 'repeater-field-for-acf' ) ) return false;
			node = node.parentElement;
		}
		return false;
	}

	/**
	 * Check if a row belongs to this repeater (not a nested one).
	 */
	ownsRow( row ) {
		// The row's direct .repeater-field-for-acf ancestor must be this.element.
		let node = row.parentElement;
		while ( node ) {
			if ( node === this.element ) return true;
			if ( node.classList && node.classList.contains( 'repeater-field-for-acf' ) ) return false;
			node = node.parentElement;
		}
		return false;
	}

	// ─── Row Management ───────────────────────────────────────────────────────

	/**
	 * Cache references to live (non-clone) rows.
	 */
	cacheRows() {
		// All layouts now use .repeater-field-for-acf-rows > .acf-row.
		this._rows = Array.from(
			this.element.querySelectorAll( ':scope > .repeater-field-for-acf-rows > .acf-row' )
		).filter( ( r ) => ! r.classList.contains( 'acf-clone' ) );
	}

	get rowCount() {
		return this._rows.length;
	}

	/**
	 * Get the hidden clone/template row.
	 */
	getCloneRow() {
		return this.element.querySelector( '.acf-row.acf-clone' );
	}

	/**
	 * Add a new row by cloning the template row.
	 */
	addRow() {
		if ( this.maxRows > 0 && this.rowCount >= this.maxRows ) {
			const msg = this.i18n.max_rows
				? this.i18n.max_rows.replace( '%d', this.maxRows )
				: `Maximum ${this.maxRows} rows allowed.`;
			alert( msg );
			return;
		}

		const clone = this.getCloneRow();
		if ( ! clone ) {
			console.error( 'ACF Repeater: clone row not found for field', this.fieldName );
			return;
		}

		const newIndex = this.rowCount;
		const newRow   = clone.cloneNode( true );

		newRow.classList.remove( 'acf-clone' );
		newRow.removeAttribute( 'style' );
		newRow.dataset.id = newIndex;

		// Replace all occurrences of 'acfcloneindex' with the actual index.
		this.replaceIndex( newRow, 'acfcloneindex', newIndex );

		// Insert before the clone row.
		clone.parentNode.insertBefore( newRow, clone );

		this.cacheRows();
		this.updateRowNumbers();

		// Destroy any TinyMCE instances that leaked into the clone, then let
		// ACF reinitialize all fields (WYSIWYG, Select2, datepickers, etc.).
		this.prepareRowForInit( newRow );
		if ( typeof acf !== 'undefined' ) {
			acf.doAction( 'append', jQuery( newRow ) );
		}
	}

	/**
	 * Remove a row with optional confirmation.
	 *
	 * @param {HTMLElement} row
	 */
	removeRow( row ) {
		if ( this.minRows > 0 && this.rowCount <= this.minRows ) {
			const msg = this.i18n.min_rows
				? this.i18n.min_rows.replace( '%d', this.minRows )
				: `Minimum ${this.minRows} rows required.`;
			alert( msg );
			return;
		}

		if ( this.deleteConfirm ) {
			const msg = this.i18n.confirm_delete || 'Are you sure you want to delete this row?';
			if ( ! confirm( msg ) ) return;
		}

		// Tell ACF to destroy all field instances inside the removed row
		// (media pickers, Select2, date pickers, TinyMCE, etc.).
		if ( typeof acf !== 'undefined' ) {
			acf.doAction( 'remove', jQuery( row ) );
		}

		// Destroy TinyMCE explicitly for the row being removed.
		this.destroyWysiwyg( row );

		row.remove();

		// reIndexRows renames every remaining row's textarea IDs.
		// Before it does, save all editor content so it survives the rename.
		this.syncAllWysiwyg();

		this.cacheRows();
		this.reIndexRows();
		this.updateRowNumbers();

		// After reIndexRows the textarea IDs changed. Destroy the stale
		// TinyMCE bindings and let ACF reinitialize editors on each row.
		if ( typeof acf !== 'undefined' ) {
			this._rows.forEach( ( r ) => {
				this.prepareRowForInit( r );
				acf.doAction( 'append', jQuery( r ) );
			} );
		}
	}

	/**
	 * Duplicate a row.
	 *
	 * @param {HTMLElement} sourceRow
	 */
	duplicateRow( sourceRow ) {
		if ( this.maxRows > 0 && this.rowCount >= this.maxRows ) {
			const msg = this.i18n.max_rows
				? this.i18n.max_rows.replace( '%d', this.maxRows )
				: `Maximum ${this.maxRows} rows allowed.`;
			alert( msg );
			return;
		}

		// Step 1: save TinyMCE content → textarea BEFORE cloning.
		this.syncWysiwygToTextarea( sourceRow );

		const clone    = this.getCloneRow();
		const oldIndex = parseInt( sourceRow.dataset.id, 10 );
		// Always append at the end. This means newIndex == current rowCount,
		// which is beyond every existing row's index, so reIndexRows()
		// won't touch any existing row — preserving their textarea IDs and
		// the TinyMCE instances attached to those IDs.
		const newIndex = this.rowCount;
		const newRow   = sourceRow.cloneNode( true );

		newRow.dataset.id = newIndex;
		this.replaceIndex( newRow, oldIndex, newIndex );

		// Step 2: clean TinyMCE residue from the clone NOW, while its IDs
		// (based on newIndex) don't exist in tinymce.editors yet.
		// If we did this AFTER reIndexRows, the clone's IDs might collide
		// with a freshly-renamed existing row and destroy its editor.
		this.prepareRowForInit( newRow );

		// Step 3: insert at the end (before the hidden .acf-clone template).
		// Inserting in the middle would require renumbering existing rows,
		// which breaks TinyMCE bindings on those rows.
		if ( clone ) {
			clone.parentNode.insertBefore( newRow, clone );
		} else {
			this.element.querySelector( '.repeater-field-for-acf-rows' ).appendChild( newRow );
		}

		this.cacheRows();
		// No reIndexRows() needed — we appended at newIndex == rowCount,
		// so every row is already at the correct sequential position.
		this.updateRowNumbers();

		if ( typeof acf !== 'undefined' ) {
			acf.doAction( 'append', jQuery( newRow ) );
		}
	}

	/**
	 * Sync TinyMCE editor content back to its underlying textarea.
	 *
	 * TinyMCE stores the live content in an <iframe>, not in the <textarea>.
	 * We must call triggerSave() on each instance before cloning so the
	 * textarea has the current HTML value.
	 *
	 * @param {HTMLElement} row
	 */
	syncWysiwygToTextarea( row ) {
		if ( typeof tinymce === 'undefined' ) return;

		// Find all editor textareas inside this row.
		row.querySelectorAll( 'textarea.wp-editor-area' ).forEach( ( textarea ) => {
			const editor = tinymce.get( textarea.id );
			if ( editor ) {
				editor.save(); // copies iframe content → textarea.value
			}
		} );
	}

	/**
	 * Save ALL TinyMCE editors in every live row of this repeater.
	 *
	 * Call this before any operation that renames textarea IDs (reIndexRows)
	 * so that textarea.value holds the current content before IDs change.
	 */
	syncAllWysiwyg() {
		if ( typeof tinymce === 'undefined' ) return;
		this._rows.forEach( ( r ) => this.syncWysiwygToTextarea( r ) );
	}

	/**
	 * Fully destroy TinyMCE instances attached to a row's editors.
	 *
	 * Use before row.remove() to prevent zombie TinyMCE instances that
	 * keep references to detached DOM nodes.
	 *
	 * @param {HTMLElement} row
	 */
	destroyWysiwyg( row ) {
		if ( typeof tinymce === 'undefined' ) return;
		row.querySelectorAll( 'textarea.wp-editor-area' ).forEach( ( textarea ) => {
			const editor = tinymce.get( textarea.id );
			if ( editor ) {
				editor.save();   // persist content first
				editor.remove(); // then destroy the instance
			}
		} );
	}

	/**
	 * Prepare a freshly-cloned row for ACF field initialization.
	 *
	 * TinyMCE replaces <textarea> elements with iframes and wrapper divs.
	 * When we clone a row, we get copies of those iframes. We must:
	 *  1. Destroy the TinyMCE instances attached to the cloned IDs.
	 *  2. Remove the TinyMCE-injected DOM so ACF can build fresh editors.
	 *  3. Restore the <textarea> to a clean, visible state.
	 *
	 * @param {HTMLElement} row  The newly cloned/inserted row.
	 */
	prepareRowForInit( row ) {
		if ( typeof tinymce === 'undefined' ) return;

		// Find every editor wrapper in this row.
		row.querySelectorAll( '.wp-editor-wrap' ).forEach( ( wrap ) => {
			const textarea = wrap.querySelector( 'textarea.wp-editor-area' );
			if ( ! textarea ) return;

			// 1. Destroy the TinyMCE instance registered under the cloned id.
			const editor = tinymce.get( textarea.id );
			if ( editor ) {
				editor.remove(); // removes instance, does NOT remove the textarea
			}

			// 2. Remove every TinyMCE-injected element that isn't the textarea.
			//    TinyMCE adds:
			//      - .mce-container  (the full editor chrome)
			//      - .wp-media-buttons bar may be re-added by ACF init
			wrap.querySelectorAll( '.mce-container, .mce-tinymce, .mce-panel, [id$="_ifr"]' ).forEach( ( el ) => el.remove() );

			// 3. Show the textarea (TinyMCE hides it when active).
			textarea.style.display = '';
			textarea.removeAttribute( 'aria-hidden' );

			// 4. Remove the "tmce-active" / "html-active" class so ACF
			//    knows the editor is in a clean uninitialized state.
			wrap.classList.remove( 'tmce-active', 'html-active', 'has-html-toolbar' );
		} );
	}

	/**
	 * Toggle a row collapsed/expanded state.
	 *
	 * @param {HTMLElement} row
	 */
	toggleRow( row ) {
		const isCollapsed = row.classList.toggle( '-collapsed' );
		row.classList.toggle( 'acf-row-collapsed', isCollapsed );

		const icon = row.querySelector( '.acf-icon.-collapse' );
		if ( icon ) {
			icon.setAttribute( 'aria-expanded', ! isCollapsed );
		}

		if ( isCollapsed ) {
			this.updateCollapsedTitle( row );
		}
	}

	/**
	 * Update the collapsed title text for a row based on the configured collapsed sub-field.
	 *
	 * @param {HTMLElement} row
	 */
	updateCollapsedTitle( row ) {
		const titleEl = row.querySelector( '.acf-row-compact-title' );
		if ( ! titleEl ) return;

		let val = '';
		if ( this.collapsedFieldKey ) {
			// Find sub-field by key or name.
			const subFieldEl = row.querySelector(
				`.acf-field[data-key="${this.collapsedFieldKey}"], .acf-field[data-name="${this.collapsedFieldKey}"]`
			);
			if ( subFieldEl ) {
				const input = subFieldEl.querySelector( 'input, select, textarea' );
				if ( input ) {
					val = input.value || '';
				}
			}
		}

		// Fallback: if no collapsed title value, use first text input in row.
		if ( ! val ) {
			const firstInput = row.querySelector( '.acf-fields > .acf-field input[type="text"]' );
			if ( firstInput ) {
				val = firstInput.value || '';
			}
		}

		titleEl.textContent = val ? String( val ) : '';
	}

	/**
	 * Update collapsed titles for all rows.
	 */
	updateAllCollapsedTitles() {
		this._rows.forEach( ( row ) => this.updateCollapsedTitle( row ) );
	}

	// ─── Index Management ─────────────────────────────────────────────────────

	/**
	 * Replace all occurrences of an old row index with a new one.
	 *
	 * This updates:
	 * - The data-id attribute on the row itself
	 * - All input name/id attributes (ACF uses: acf[field][{index}][sub])
	 * - All label for attributes
	 *
	 * @param {HTMLElement}  el       Element to update (usually a row).
	 * @param {string|number} oldIdx  Index to replace ('acfcloneindex' or integer).
	 * @param {number}        newIdx  New integer index.
	 */
	replaceIndex( el, oldIdx, newIdx ) {
		const oldStr    = String( oldIdx );
		const newStr    = String( newIdx );
		// Matches [acfcloneindex] or [0] etc. in input names.
		const bracketRe = new RegExp( '\\[' + this.escapeRe( oldStr ) + '\\]', 'g' );
		// Matches bare acfcloneindex or plain number in data-id / other attrs.
		const bareRe    = new RegExp( '(?<![\\w])' + this.escapeRe( oldStr ) + '(?![\\w])', 'g' );

		// Update all descendant elements with name, id, for attributes.
		el.querySelectorAll( '[name], [id], [for], [data-key]' ).forEach( ( node ) => {
			if ( node.name )    node.name    = node.name.replace( bracketRe, `[${newStr}]` );
			if ( node.id )      node.id      = node.id.replace( bracketRe, `[${newStr}]` ).replace( bareRe, newStr );
			if ( node.htmlFor ) node.htmlFor = node.htmlFor.replace( bracketRe, `[${newStr}]` ).replace( bareRe, newStr );
		} );

		// Update the row's own data-id.
		if ( el.dataset ) {
			el.dataset.id = newStr;
		}
	}

	/**
	 * After removing/reordering rows, re-index all rows to be sequential.
	 */
	reIndexRows() {
		this._rows.forEach( ( row, idx ) => {
			const currentIdx = parseInt( row.dataset.id, 10 );
			if ( currentIdx !== idx ) {
				this.replaceIndex( row, currentIdx, idx );
			}
		} );
	}

	/**
	 * Update the visible row order numbers.
	 */
	updateRowNumbers() {
		this._rows.forEach( ( row, idx ) => {
			const numberEl = row.querySelector( '.acf-row-number' );
			if ( numberEl ) {
				numberEl.textContent = idx + 1;
			}
		} );
	}

	// ─── Sortable (jQuery UI) ─────────────────────────────────────────────────

	initSortable() {
		if ( ! this.sortable || typeof jQuery === 'undefined' || ! jQuery.fn || ! jQuery.fn.sortable ) {
			return;
		}

		// All layouts use .repeater-field-for-acf-rows as the sortable container.
		const $container = jQuery( this.element ).find( '> .repeater-field-for-acf-rows' );
		if ( ! $container.length ) return;

		this.sortableInstance = $container.sortable( {
			items:              '> .acf-row:not(.acf-clone)',
			handle:             '.acf-sortable-handle',
			axis:               'y',
			cursor:             'grab',
			placeholder:        'acf-sortable-placeholder',
			forcePlaceholderSize: true,
			start: ( e, ui ) => {
				ui.placeholder.height( ui.item.outerHeight() );
				ui.item.addClass( 'acf-row-dragging' );
			},
			stop: ( e, ui ) => {
				ui.item.removeClass( 'acf-row-dragging' );
			},
			update: () => {
				this.cacheRows();
				this.reIndexRows();
				this.updateRowNumbers();
			},
		} );
	}

	// ─── Utilities ────────────────────────────────────────────────────────────

	/**
	 * Escape a string for use in a RegExp.
	 *
	 * @param {string} str
	 * @returns {string}
	 */
	escapeRe( str ) {
		return str.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	}

	/**
	 * Destroy the field controller (clean up sortable etc.).
	 */
	destroy() {
		if ( this.sortableInstance ) {
			try {
				this.sortableInstance.sortable( 'destroy' );
			} catch ( e ) {
				// Already destroyed.
			}
		}
	}
}

// Export to window for index.js.
window.ACFRepeaterField = ACFRepeaterField;
export default ACFRepeaterField;
