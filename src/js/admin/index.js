/**
 * ACF Repeater — Admin JavaScript Entry Point
 *
 * Initializes repeater field instances for:
 * 1. Fields already in the DOM on page load
 * 2. Fields added dynamically (Gutenberg, flexible content, nested repeaters)
 *    via ACF's 'append' action and a MutationObserver fallback.
 *
 * @package ACF_Repeater
 */

import '@css/admin/repeater.css';
import '@css/admin/field-group.css';

import ACFRepeaterField from './repeater-field';

// ─── Initialization ─────────────────────────────────────────────────────────

/**
 * Initialize any .acf-repeater elements that haven't been set up yet.
 *
 * The guard `el._acfRepeater` prevents double-initialization.
 */
function initRepeaters() {
	document.querySelectorAll( '.acf-repeater' ).forEach( ( el ) => {
		if ( ! el._acfRepeater ) {
			el._acfRepeater = new ACFRepeaterField( el );
		}
	} );
}

// Run on DOMContentLoaded.
document.addEventListener( 'DOMContentLoaded', initRepeaters );

// Fallback: run again on window load (Gutenberg may delay meta-box injection).
window.addEventListener( 'load', initRepeaters );

// ─── ACF Action Hooks ────────────────────────────────────────────────────────

if ( typeof acf !== 'undefined' ) {
	/**
	 * ACF fires 'append' when new content is added to the page
	 * (e.g. a new row inside a flexible content field, or a new
	 * post meta box injected by Gutenberg).
	 *
	 * $el is a jQuery object containing the newly added content.
	 */
	acf.addAction( 'append', ( $el ) => {
		$el.find( '.acf-repeater' ).each( function () {
			if ( ! this._acfRepeater ) {
				this._acfRepeater = new ACFRepeaterField( this );
			}
		} );
	} );

	/**
	 * ACF fires 'remove' before removing content from the page.
	 * Clean up the controller to avoid memory leaks.
	 */
	acf.addAction( 'remove', ( $el ) => {
		$el.find( '.acf-repeater' ).each( function () {
			if ( this._acfRepeater ) {
				this._acfRepeater.destroy();
				this._acfRepeater = null;
			}
		} );
	} );
}

// ─── MutationObserver Fallback ───────────────────────────────────────────────

/**
 * Watch for .acf-repeater elements added dynamically.
 * This handles cases where ACF's 'append' action is not fired,
 * such as Gutenberg's async meta box loading.
 */
const observer = new MutationObserver( ( mutations ) => {
	let needsInit = false;

	for ( const mutation of mutations ) {
		if ( mutation.type !== 'childList' || ! mutation.addedNodes.length ) {
			continue;
		}
		for ( const node of mutation.addedNodes ) {
			if ( node.nodeType !== 1 ) continue; // Only element nodes.

			if (
				( node.classList && node.classList.contains( 'acf-repeater' ) ) ||
				( node.querySelector && node.querySelector( '.acf-repeater' ) )
			) {
				needsInit = true;
				break;
			}
		}
		if ( needsInit ) break;
	}

	if ( needsInit ) {
		initRepeaters();
	}
} );

// Observe the body for subtree changes after DOM is ready.
document.addEventListener( 'DOMContentLoaded', () => {
	observer.observe( document.body, { childList: true, subtree: true } );
} );