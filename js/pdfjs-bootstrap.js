/**
 * Load PDF.js as an ES module and expose it for the classic viewer script.
 *
 * Filenames use .js (not .mjs) so typical Apache/Nginx/Valet hosts serve
 * text/javascript. Browsers reject ES modules served as application/octet-stream.
 */
import * as pdfjsLib from './pdfjs/pdf.min.js';

globalThis.pdfjsLib = pdfjsLib;

if ( typeof globalThis.epdf_gf_pdfjs_strings !== 'undefined' ) {
	pdfjsLib.GlobalWorkerOptions.workerSrc = globalThis.epdf_gf_pdfjs_strings.url_worker;
}

window.dispatchEvent( new CustomEvent( 'epdf_gf_pdfjs_worker_set' ) );
