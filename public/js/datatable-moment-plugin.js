/*
 * This plug-in provides the ability to sort columns that have date/time data
 * in them. This can be particularly useful when working with dates and times
 * which are represented in a format that sorting doesn't handle by default,
 * for example AM/PM time notations.
 *
 * It is particularly useful when used in conjunction with the Moment.js library
 * which provides a huge number of date / time formatting options. Moment.js is
 * not a dependency for this plug-in, but it is recommended.
 *
 * @name Ultimate date / time sorting
 * @summary Sort date and time in any format
 * @author [Allan Jardine](http://www.sprymedia.co.uk)
 *
 * @example
 * $.fn.dataTable.moment( 'HH:mm MMM D, YY' );
 * $.fn.dataTable.moment( 'dddd, MMMM Do, YYYY' );
 *
 * $('#example').DataTable();
 */

(function (factory) {
	if (typeof define === 'function' && define.amd) {
		define(['jquery', 'moment', 'datatables.net'], factory);
	} else {
		factory(jQuery, moment);
	}
}(function ($, moment) {

$.fn.dataTable.moment = function ( format, locale ) {
	var types = $.fn.dataTable.ext.type;

	// Add type detection
	types.detect.unshift( function ( d ) {
		// Strip HTML5 tags (e.g. <time>) from data
		if (d && d.replace) {
			d = d.replace(/<.*?>/g, '');
		}

		// Null and empty values are acceptable
		if (d === '' || d === null) {
			return 'moment-'+format;
		}

		return moment( d, format, locale, true ).isValid() ?
			'moment-' + format :
			null;
	} );

	// Add sorting method - use an integer for the sorting
	types.order[ 'moment-' + format + '-pre' ] = function ( d ) {
		if (d && d.replace) {
			d = d.replace(/<.*?>/g, '');
		}
		
		return d === '' || d === null ?
			-Infinity :
			parseInt( moment( d, format, locale, true ).format( 'x' ), 10 );
	};
};

}));