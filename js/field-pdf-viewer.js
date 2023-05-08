// After field settings are loaded in the form editor.
gform.addAction( 'gform_post_load_field_settings', function( field, form ) {
	if ( 'pdf_viewer' !== field[0].type ) {
		return;
	}
	// Populate and update our initial scale property.
	var el = document.getElementById('field_initial_scale');
	if ( el ) {
		// Populate the setting value.
		el.value = rgar( field[0], 'initialScale' );
		// Update the setting when users change the value.
		[ 'input', 'propertychange' ].forEach(function(e){
			el.addEventListener(e,function() {
				SetFieldProperty('initialScale', this.value);
			},false);
		});
	}
}, 10, 2 );
