/**
 * Raeen Repeater Field for ACF — Frontend JavaScript
 *
 * Initializes repeater fields on frontend ACF forms (acf_form()).
 *
 * @package    Raeen_Repeater
 * @repository https://github.com/raeenzubair/repeater-field-for-acf
 * @license    GPL-2.0-or-later
 */

import '@css/public/index.css';

// Frontend initialization — reuse the same ACFRepeaterField class
// (it is global on admin too, and available to frontend via this bundle).
function initFrontendRepeaters() {
	if ( typeof window.ACFRepeaterField === 'undefined' ) return;
	document.querySelectorAll( '.repeater-field-for-acf' ).forEach( ( el ) => {
		if ( ! el._acfRepeater ) {
			el._acfRepeater = new window.ACFRepeaterField( el );
		}
	} );
}

document.addEventListener( 'DOMContentLoaded', initFrontendRepeaters );

if ( typeof acf !== 'undefined' ) {
	acf.addAction( 'ready', initFrontendRepeaters );
	acf.addAction( 'append', ( $el ) => {
		$el.find( '.repeater-field-for-acf' ).each( function () {
			if ( ! this._acfRepeater && window.ACFRepeaterField ) {
				this._acfRepeater = new window.ACFRepeaterField( this );
			}
		} );
	} );
}