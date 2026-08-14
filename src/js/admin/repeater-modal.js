/**
 * Raeen Repeater Field for ACF Modal - Confirmation dialogs
 *
 * @package    Raeen_Repeater
 * @repository https://github.com/raeenzubair/repeater-field-for-acf
 * @license    GPL-2.0-or-later
 */

class ACFRepeaterModal {
	constructor() {
		this.modal = null;
		this.overlay = null;
		this.onConfirm = null;
		this.onCancel = null;
		this.firstFocusable = null;
		this.lastFocusable = null;

		this.createElements();
	}

	createElements() {
		// Create overlay.
		this.overlay = document.createElement( 'div' );
		this.overlay.className = 'repeater-field-for-acf-modal-overlay';

		// Create modal.
		this.modal = document.createElement( 'div' );
		this.modal.className = 'repeater-field-for-acf-modal';
		this.modal.setAttribute( 'role', 'dialog' );
		this.modal.setAttribute( 'aria-modal', 'true' );
		this.modal.setAttribute( 'aria-labelledby', 'repeater-field-for-acf-modal-title' );
		this.modal.innerHTML = `
			<div class="repeater-field-for-acf-modal-content">
				<div class="repeater-field-for-acf-modal-header">
					<h3 id="repeater-field-for-acf-modal-title" class="repeater-field-for-acf-modal-title"></h3>
					<button type="button" class="repeater-field-for-acf-modal-close" aria-label="${this.escapeHtml( acfRepeater?.i18n?.close || 'Close' )}">
						<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
					</button>
				</div>
				<div class="repeater-field-for-acf-modal-body"></div>
				<div class="repeater-field-for-acf-modal-footer">
					<button type="button" class="button repeater-field-for-acf-modal-cancel"></button>
					<button type="button" class="button button-primary repeater-field-for-acf-modal-confirm"></button>
				</div>
			</div>
		`;

		// Append to body.
		this.overlay.appendChild( this.modal );
		document.body.appendChild( this.overlay );

		// Bind events.
		this.bindEvents();
	}

	bindEvents() {
		this.modal.querySelector( '.repeater-field-for-acf-modal-close' ).addEventListener( 'click', () => this.close( false ) );
		this.modal.querySelector( '.repeater-field-for-acf-modal-cancel' ).addEventListener( 'click', () => this.close( false ) );
		this.modal.querySelector( '.repeater-field-for-acf-modal-confirm' ).addEventListener( 'click', () => this.close( true ) );

		// Close on overlay click.
		this.overlay.addEventListener( 'click', ( e ) => {
			if ( e.target === this.overlay ) {
				this.close( false );
			}
		} );

		// Escape key.
		this.keydownHandler = ( e ) => {
			if ( e.key === 'Escape' ) {
				this.close( false );
			} else if ( e.key === 'Tab' ) {
				this.trapFocus( e );
			}
		};
	}

	/**
	 * Show confirmation modal.
	 *
	 * @param {Object} options
	 * @param {string} options.title
	 * @param {string} options.message
	 * @param {string} options.confirmText
	 * @param {string} options.cancelText
	 * @param {boolean} options.danger
	 * @returns {Promise<boolean>}
	 */
	confirm( options ) {
		return new Promise( ( resolve ) => {
			this.onConfirm = () => resolve( true );
			this.onCancel = () => resolve( false );

			const titleEl = this.modal.querySelector( '.repeater-field-for-acf-modal-title' );
			const bodyEl = this.modal.querySelector( '.repeater-field-for-acf-modal-body' );
			const confirmBtn = this.modal.querySelector( '.repeater-field-for-acf-modal-confirm' );
			const cancelBtn = this.modal.querySelector( '.repeater-field-for-acf-modal-cancel' );

			titleEl.textContent = options.title || 'Confirm';
			bodyEl.innerHTML = `<p>${options.message || ''}</p>`;
			confirmBtn.textContent = options.confirmText || 'Confirm';
			cancelBtn.textContent = options.cancelText || 'Cancel';

			if ( options.danger ) {
				confirmBtn.classList.add( 'repeater-field-for-acf-modal-danger' );
			} else {
				confirmBtn.classList.remove( 'repeater-field-for-acf-modal-danger' );
			}

			this.open();
		} );
	}

	open() {
		this.overlay.classList.add( 'repeater-field-for-acf-modal-overlay-visible' );
		this.modal.classList.add( 'repeater-field-for-acf-modal-open' );
		document.body.style.overflow = 'hidden';

		// Focus management.
		const focusableElements = this.modal.querySelectorAll(
			'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
		);
		this.firstFocusable = focusableElements[0];
		this.lastFocusable = focusableElements[ focusableElements.length - 1 ];

		this.firstFocusable?.focus();

		// Add keydown listener.
		document.addEventListener( 'keydown', this.keydownHandler );
	}

	close( confirmed = false ) {
		this.modal.classList.remove( 'repeater-field-for-acf-modal-open' );
		this.overlay.classList.remove( 'repeater-field-for-acf-modal-overlay-visible' );
		document.body.style.overflow = '';

		document.removeEventListener( 'keydown', this.keydownHandler );

		if ( confirmed && this.onConfirm ) {
			this.onConfirm();
		} else if ( !confirmed && this.onCancel ) {
			this.onCancel();
		}

		this.onConfirm = null;
		this.onCancel = null;
	}

	trapFocus( event ) {
		if ( event.key !== 'Tab' ) return;

		if ( event.shiftKey ) {
			if ( document.activeElement === this.firstFocusable ) {
				event.preventDefault();
				this.lastFocusable?.focus();
			}
		} else {
			if ( document.activeElement === this.lastFocusable ) {
				event.preventDefault();
				this.firstFocusable?.focus();
			}
		}
	}

	escapeHtml( text ) {
		const div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}

	destroy() {
		if ( this.modal ) this.modal.remove();
		if ( this.overlay ) this.overlay.remove();
		document.removeEventListener( 'keydown', this.keydownHandler );
	}
}

// Create singleton instance.
const acfRepeaterModal = new ACFRepeaterModal();

// Export for global use.
window.acfRepeaterModal = acfRepeaterModal;

export default acfRepeaterModal;