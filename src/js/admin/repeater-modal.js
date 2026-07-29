/**
 * ACF Repeater Modal - Confirmation dialogs
 *
 * @package ACF_Repeater
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
		this.overlay.className = 'acf-repeater-modal-overlay';

		// Create modal.
		this.modal = document.createElement( 'div' );
		this.modal.className = 'acf-repeater-modal';
		this.modal.setAttribute( 'role', 'dialog' );
		this.modal.setAttribute( 'aria-modal', 'true' );
		this.modal.setAttribute( 'aria-labelledby', 'acf-repeater-modal-title' );
		this.modal.innerHTML = `
			<div class="acf-repeater-modal-content">
				<div class="acf-repeater-modal-header">
					<h3 id="acf-repeater-modal-title" class="acf-repeater-modal-title"></h3>
					<button type="button" class="acf-repeater-modal-close" aria-label="${this.escapeHtml( acfRepeater?.i18n?.close || 'Close' )}">
						<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
					</button>
				</div>
				<div class="acf-repeater-modal-body"></div>
				<div class="acf-repeater-modal-footer">
					<button type="button" class="button acf-repeater-modal-cancel"></button>
					<button type="button" class="button button-primary acf-repeater-modal-confirm"></button>
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
		this.modal.querySelector( '.acf-repeater-modal-close' ).addEventListener( 'click', () => this.close( false ) );
		this.modal.querySelector( '.acf-repeater-modal-cancel' ).addEventListener( 'click', () => this.close( false ) );
		this.modal.querySelector( '.acf-repeater-modal-confirm' ).addEventListener( 'click', () => this.close( true ) );

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

			const titleEl = this.modal.querySelector( '.acf-repeater-modal-title' );
			const bodyEl = this.modal.querySelector( '.acf-repeater-modal-body' );
			const confirmBtn = this.modal.querySelector( '.acf-repeater-modal-confirm' );
			const cancelBtn = this.modal.querySelector( '.acf-repeater-modal-cancel' );

			titleEl.textContent = options.title || 'Confirm';
			bodyEl.innerHTML = `<p>${options.message || ''}</p>`;
			confirmBtn.textContent = options.confirmText || 'Confirm';
			cancelBtn.textContent = options.cancelText || 'Cancel';

			if ( options.danger ) {
				confirmBtn.classList.add( 'acf-repeater-modal-danger' );
			} else {
				confirmBtn.classList.remove( 'acf-repeater-modal-danger' );
			}

			this.open();
		} );
	}

	open() {
		this.overlay.classList.add( 'acf-repeater-modal-overlay-visible' );
		this.modal.classList.add( 'acf-repeater-modal-open' );
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
		this.modal.classList.remove( 'acf-repeater-modal-open' );
		this.overlay.classList.remove( 'acf-repeater-modal-overlay-visible' );
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