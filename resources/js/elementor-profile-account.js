( function () {
	var activeWidget = null;

	document.addEventListener( 'click', function ( event ) {
		var toggle = event.target.closest( '.cp-profile-account-toggle' );

		if ( toggle ) {
			event.preventDefault();
			var widget = toggle.closest( '.cp-profile-account-widget' );
			var panel = widget.querySelector( '.cp-profile-account-panel' );
			var isExpanded = toggle.getAttribute( 'aria-expanded' ) === 'true';

			if ( activeWidget && activeWidget !== widget ) {
				closeWidget( activeWidget );
			}

			if ( isExpanded ) {
				closeWidget( widget );
			} else {
				openWidget( widget );
			}
			return;
		}

		var clickedInside = event.target.closest( '.cp-profile-account-widget' );
		if ( ! clickedInside && activeWidget ) {
			closeWidget( activeWidget );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' && activeWidget ) {
			closeWidget( activeWidget );
		}
	} );

	function openWidget( widget ) {
		var toggle = widget.querySelector( '.cp-profile-account-toggle' );
		var panel = widget.querySelector( '.cp-profile-account-panel' );
		if ( ! toggle || ! panel ) {
			return;
		}

		panel.classList.add( 'active' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		activeWidget = widget;
	}

	function closeWidget( widget ) {
		var toggle = widget.querySelector( '.cp-profile-account-toggle' );
		var panel = widget.querySelector( '.cp-profile-account-panel' );
		if ( ! toggle || ! panel ) {
			return;
		}

		panel.classList.remove( 'active' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		if ( activeWidget === widget ) {
			activeWidget = null;
		}
	}
} )();
