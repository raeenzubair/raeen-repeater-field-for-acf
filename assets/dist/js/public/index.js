/*!
 * Raeen Repeater Field for ACF - Frontend Scripts
 * Source: src/js/public/index.js
 * Repository: https://github.com/raeenzubair/repeater-field-for-acf
 * Author: Mohammad Zubair Ali
 * License: GPL-2.0-or-later
 * Build: npm run build
 */
/**
 * Raeen Repeater Field for ACF — Frontend JavaScript
 *
 * Initializes repeater fields on frontend ACF forms (acf_form()).
 *
 * @package    Raeen_Repeater
 * @repository https://github.com/raeenzubair/repeater-field-for-acf
 * @license    GPL-2.0-or-later
 */
function e(){void 0!==window.ACFRepeaterField&&document.querySelectorAll(".repeater-field-for-acf").forEach(e=>{e._acfRepeater||(e._acfRepeater=new window.ACFRepeaterField(e))})}document.addEventListener("DOMContentLoaded",e),"undefined"!=typeof acf&&(acf.addAction("ready",e),acf.addAction("append",e=>{e.find(".repeater-field-for-acf").each(function(){!this._acfRepeater&&window.ACFRepeaterField&&(this._acfRepeater=new window.ACFRepeaterField(this))})}));
//# sourceMappingURL=index.js.map
