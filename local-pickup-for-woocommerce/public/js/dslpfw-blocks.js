/**
 * Cart & Checkout Blocks UI for Local Pickup for WooCommerce.
 *
 * Uses Store API extensions + React portals into cart line items,
 * and ExperimentalOrderShippingPackages for checkout pickup UI.
 */
( function () {
	'use strict';

	var wpElement = window.wp && window.wp.element;
	var wpData = window.wp && window.wp.data;
	var blocksCheckout = window.wc && window.wc.blocksCheckout;
	var wcSettings = window.wc && window.wc.wcSettings;

	if ( ! wpElement || ! wpData || ! blocksCheckout || ! wcSettings ) {
		return;
	}

	var el = wpElement.createElement;
	var useState = wpElement.useState;
	var useEffect = wpElement.useEffect;
	var useRef = wpElement.useRef;
	var createPortal = window.wp.element.createPortal || ( window.ReactDOM && window.ReactDOM.createPortal );
	var extensionCartUpdate = blocksCheckout.extensionCartUpdate;

	var settings = {};
	try {
		settings = wcSettings.getSetting( 'dslpfw-local-pickup_data', {} ) || {};
	} catch ( e ) {
		settings = {};
	}

	var NAMESPACE = settings.namespace || 'dslpfw-local-pickup';
	var I18N = settings.i18n || {};
	var SHIPPING_METHOD_ID = settings.shippingMethodId || 'ds_local_pickup';

	function getItemExt( item ) {
		return ( item && item.extensions && item.extensions[ NAMESPACE ] ) || {};
	}

	function getCartExtFromSources( cart, extensions ) {
		if ( extensions && extensions[ NAMESPACE ] ) {
			return extensions[ NAMESPACE ];
		}
		if ( cart && cart.extensions && cart.extensions[ NAMESPACE ] ) {
			return cart.extensions[ NAMESPACE ];
		}
		return {};
	}

	function rateIsLocalPickup( rate ) {
		if ( ! rate ) {
			return false;
		}
		if ( rate.method_id === SHIPPING_METHOD_ID ) {
			return true;
		}
		if ( rate.rate_id === SHIPPING_METHOD_ID ) {
			return true;
		}
		return !!( rate.rate_id && String( rate.rate_id ).indexOf( SHIPPING_METHOD_ID ) === 0 );
	}

	function cartHasLocalPickupSelected( cartData ) {
		var rates = ( cartData && cartData.shippingRates ) || [];
		return rates.some( function ( ratePackage ) {
			return ( ratePackage.shipping_rates || [] ).some( function ( rate ) {
				return rate.selected && rateIsLocalPickup( rate );
			} );
		} );
	}

	function deriveLocationIdFromItems( items ) {
		var locationId = 0;
		( items || [] ).some( function ( item ) {
			var ext = getItemExt( item );
			if ( ext.enabled && ext.handling === 'pickup' && ext.pickupLocationId ) {
				locationId = parseInt( ext.pickupLocationId, 10 ) || 0;
				return locationId > 0;
			}
			return false;
		} );
		return locationId;
	}

	function updateExtension( data ) {
		if ( ! extensionCartUpdate ) {
			return Promise.resolve();
		}
		return extensionCartUpdate( {
			namespace: NAMESPACE,
			data: data,
		} );
	}

	function LocationSelect( props ) {
		var locations = props.locations || [];
		var value = props.value ? String( props.value ) : '';
		var disabled = !! props.disabled;
		var onChange = props.onChange;

		return el(
			'select',
			{
				className: 'dslpfw-blocks-location-select',
				value: value,
				disabled: disabled,
				onChange: function ( event ) {
					onChange( event.target.value );
				},
			},
			el( 'option', { value: '' }, I18N.selectLocation || 'Select a pickup location' ),
			locations.map( function ( location ) {
				return el(
					'option',
					{ key: String( location.id ), value: String( location.id ) },
					location.label || location.name
				);
			} )
		);
	}

	function CartItemPickupControls( props ) {
		var item = props.item;
		var ext = getItemExt( item );
		var busyState = useState( false );
		var busy = busyState[ 0 ];
		var setBusy = busyState[ 1 ];

		if ( ! ext.enabled || ! ext.perItemSelection ) {
			return null;
		}

		var isPickup = ext.handling === 'pickup' || ext.mustBePickedUp;

		function runUpdate( payload ) {
			setBusy( true );
			return updateExtension( payload ).finally( function () {
				setBusy( false );
			} );
		}

		function onLocationChange( locationId ) {
			runUpdate( {
				action: 'set_cart_item_handling',
				cart_item_key: item.key,
				handling: 'pickup',
				pickup_location_id: parseInt( locationId, 10 ) || 0,
			} );
		}

		function onToggle( event ) {
			event.preventDefault();
			if ( ! ext.showHandlingToggle ) {
				return;
			}
			var next = isPickup ? 'ship' : 'pickup';
			runUpdate( {
				action: 'set_cart_item_handling',
				cart_item_key: item.key,
				handling: next,
				pickup_location_id: next === 'pickup' ? ( ext.pickupLocationId || 0 ) : 0,
			} );
		}

		var toggleText = isPickup
			? ( I18N.setForShipping || 'This item is set for pickup. Click here to ship this item.' )
			: ( I18N.setForPickup || 'This item is set for shipping. Click here to pickup this item.' );
		var toggleLink = isPickup
			? ( I18N.clickToShip || 'Click here to ship this item.' )
			: ( I18N.clickToPickup || 'Click here to pickup this item.' );
		var togglePrefix = toggleText.replace( toggleLink, '' );

		return el(
			'div',
			{ className: 'dslpfw-blocks-cart-item' + ( busy ? ' is-busy' : '' ) },
			isPickup
				? el(
						'div',
						{ className: 'dslpfw-blocks-location-wrap' },
						el( LocationSelect, {
							locations: ext.locations || [],
							value: ext.pickupLocationId || '',
							disabled: busy,
							onChange: onLocationChange,
						} )
				  )
				: null,
			ext.showHandlingToggle
				? el(
						'p',
						{ className: 'dslpfw-blocks-handling-toggle' },
						togglePrefix,
						el(
							'a',
							{
								href: '#',
								onClick: onToggle,
							},
							toggleLink
						)
				  )
				: null
		);
	}

	function CartItemPortals() {
		var cartItems = wpData.useSelect( function ( select ) {
			var store = select( 'wc/store/cart' );
			return store && store.getCartData ? store.getCartData().items : [];
		}, [] );

		var mountsState = useState( {} );
		var mounts = mountsState[ 0 ];
		var setMounts = mountsState[ 1 ];

		useEffect(
			function () {
				function syncMounts() {
					var next = {};
					( cartItems || [] ).forEach( function ( item ) {
						var row = document.querySelector(
							'tr.wc-block-cart-items__row[data-cart-item-key="' + item.key + '"] .wc-block-cart-item__wrap, ' +
								'.wc-block-components-order-summary-item[data-cart-item-key="' + item.key + '"] .wc-block-components-order-summary-item__description'
						);
						// Cart block product cell wrap.
						if ( ! row ) {
							var cartRow = document.querySelector(
								'tr.wc-block-cart-items__row[data-cart-item-key="' + item.key + '"]'
							);
							if ( cartRow ) {
								row = cartRow.querySelector( '.wc-block-cart-item__wrap' );
							}
						}
						// Checkout order summary (no data-cart-item-key on summary in some versions).
						if ( ! row ) {
							var summaryItems = document.querySelectorAll(
								'.wc-block-components-order-summary-item__description'
							);
							summaryItems.forEach( function ( node, index ) {
								if ( cartItems[ index ] && cartItems[ index ].key === item.key ) {
									row = node;
								}
							} );
						}
						if ( row ) {
							var host = row.querySelector( '.dslpfw-blocks-cart-item-host' );
							if ( ! host ) {
								host = document.createElement( 'div' );
								host.className = 'dslpfw-blocks-cart-item-host';
								var productName = row.querySelector(
									'.wc-block-components-product-name, a.wc-block-components-product-name, h3'
								);
								if ( productName && productName.parentNode === row ) {
									productName.insertAdjacentElement( 'afterend', host );
								} else if ( productName ) {
									productName.parentNode.insertBefore( host, productName.nextSibling );
								} else {
									row.insertBefore( host, row.firstChild ? row.firstChild.nextSibling : null );
								}
							}
							next[ item.key ] = host;
						}
					} );
					setMounts( next );
				}

				syncMounts();
				var observer = new MutationObserver( function () {
					syncMounts();
				} );
				observer.observe( document.body, { childList: true, subtree: true } );
				return function () {
					observer.disconnect();
				};
			},
			[ cartItems ]
		);

		if ( ! createPortal ) {
			return null;
		}

		return el(
			wpElement.Fragment,
			null,
			( cartItems || [] ).map( function ( item ) {
				if ( ! mounts[ item.key ] ) {
					return null;
				}
				return createPortal(
					el( CartItemPickupControls, { key: item.key, item: item } ),
					mounts[ item.key ]
				);
			} )
		);
	}

	function fetchAppointmentCalendar( locationId ) {
		var body = new window.FormData();
		body.append( 'action', 'dslpfw_get_pickup_location_appointment_data' );
		body.append( 'security', settings.appointmentDataNonce || '' );
		body.append( 'location_id', String( locationId ) );

		return window
			.fetch( settings.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body,
			} )
			.then( function ( response ) {
				return response.json();
			} );
	}

	function fetchAppointmentTimes( locationId, pickupDate ) {
		var body = new window.FormData();
		body.append( 'action', 'dslpfw_blocks_get_appointment_times' );
		body.append( 'security', settings.appointmentTimesNonce || '' );
		body.append( 'location_id', String( locationId ) );
		body.append( 'pickup_date', pickupDate );

		return window
			.fetch( settings.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body,
			} )
			.then( function ( response ) {
				return response.json();
			} );
	}

	function PackagePickupPanel( props ) {
		var pkg = props.packageData;
		var cartExt = props.cartExt;
		var busyState = useState( false );
		var busy = busyState[ 0 ];
		var setBusy = busyState[ 1 ];
		var calendarState = useState( null );
		var calendar = calendarState[ 0 ];
		var setCalendar = calendarState[ 1 ];
		var timesState = useState( null );
		var timesData = timesState[ 0 ];
		var setTimesData = timesState[ 1 ];
		var dateInputRef = useRef( null );
		var datepickerId = 'dslpfw-blocks-datepicker-' + String( pkg.packageId );
		var datepickerAltId = datepickerId + '-alt';

		var appointmentsMode = pkg.appointmentsMode || cartExt.appointmentsMode || settings.appointmentsMode || 'disabled';
		var showAppointments = appointmentsMode !== 'disabled' && pkg.isPickup;
		var showLocationSelect = pkg.perOrderSelection && pkg.isPickup;
		var locationId = pkg.pickupLocationId || ( pkg.selectedLocation && pkg.selectedLocation.id ) || 0;

		function runUpdate( payload ) {
			setBusy( true );
			return updateExtension( payload ).finally( function () {
				setBusy( false );
			} );
		}

		useEffect(
			function () {
				if ( ! showAppointments || ! locationId ) {
					setCalendar( null );
					return;
				}
				fetchAppointmentCalendar( locationId ).then( function ( response ) {
					if ( response && response.success ) {
						setCalendar( response.data );
					}
				} );
			},
			[ locationId, showAppointments ]
		);

		useEffect(
			function () {
				if ( ! showAppointments || ! locationId || ! pkg.pickupDate ) {
					setTimesData( null );
					return;
				}
				fetchAppointmentTimes( locationId, pkg.pickupDate ).then( function ( response ) {
					if ( response && response.success ) {
						setTimesData( response.data );
					}
				} );
			},
			[ locationId, pkg.pickupDate, showAppointments ]
		);

		// jQuery UI datepicker with holiday / unavailable dates disabled (same as classic checkout).
		useEffect(
			function () {
				var $ = window.jQuery;
				if ( ! showAppointments || ! locationId || ! calendar || ! $ || ! $.fn || ! $.fn.datepicker ) {
					return undefined;
				}

				var $input = $( '#' + datepickerId );
				if ( ! $input.length ) {
					return undefined;
				}

				if ( $input.hasClass( 'hasDatepicker' ) ) {
					$input.datepicker( 'destroy' );
				}

				var unavailable = calendar.unavailable_dates || [];
				var minDate = new Date( 1e3 * calendar.calendar_start );
				var maxDate = new Date( 1e3 * calendar.calendar_end );
				minDate = new Date( minDate.getTime() + 60 * minDate.getTimezoneOffset() * 1e3 );
				maxDate = new Date( maxDate.getTime() + 60 * maxDate.getTimezoneOffset() * 1e3 );

				$input.datepicker( {
					minDate: minDate,
					maxDate: maxDate,
					altField: '#' + datepickerAltId,
					altFormat: 'yy-mm-dd',
					dateFormat: 'MM dd, yy',
					firstDay: typeof settings.startOfWeek !== 'undefined' ? settings.startOfWeek : 1,
					prevText: '',
					nextText: '',
					showOn: 'both',
					gotoCurrent: true,
					changeMonth: true,
					changeYear: true,
					beforeShow: function ( input, inst ) {
						$( inst.dpDiv )
							.addClass( 'dslpfw-appointment-datepicker' )
							.addClass( 'dslpfw-blocks-datepicker' )
							.addClass( 'dslpfw-appointment-datepicker-' + pkg.packageId );
					},
					beforeShowDay: function ( date ) {
						var formatted = $.datepicker.formatDate( 'yy-mm-dd', date );
						var isAvailable = unavailable.indexOf( formatted ) === -1;
						return [ isAvailable, isAvailable ? 'dslpfw_available' : 'dslpfw_unavailable' ];
					},
					onSelect: function () {
						var isoDate = $( '#' + datepickerAltId ).val() || '';
						runUpdate( {
							action: 'set_package_handling',
							package_id: pkg.packageId,
							pickup_location_id: locationId,
							pickup_date: isoDate,
							appointment_offset: '',
						} );
					},
				} );

				$( 'button.ui-datepicker-trigger' ).attr(
					'title',
					settings.datepickerTitle || I18N.pickupDate || 'Choose a pickup date'
				);

				if ( pkg.pickupDate ) {
					try {
						var selected = $.datepicker.parseDate( 'yy-mm-dd', pkg.pickupDate );
						$input.datepicker( 'setDate', selected );
					} catch ( err ) {
						// Keep empty if stored date is invalid.
					}
				} else if ( calendar.auto_select_default && calendar.default_date ) {
					try {
						var defaultDate = new Date( 1e3 * calendar.default_date );
						defaultDate = new Date( defaultDate.getTime() + 60 * defaultDate.getTimezoneOffset() * 1e3 );
						$input.datepicker( 'setDate', defaultDate );
						var autoIso = $.datepicker.formatDate( 'yy-mm-dd', defaultDate );
						runUpdate( {
							action: 'set_package_handling',
							package_id: pkg.packageId,
							pickup_location_id: locationId,
							pickup_date: autoIso,
							appointment_offset: '',
						} );
					} catch ( err2 ) {
						// Ignore auto-select failures.
					}
				}

				return function () {
					if ( $input.length && $input.hasClass( 'hasDatepicker' ) ) {
						$input.datepicker( 'destroy' );
					}
				};
			},
			[ calendar, locationId, showAppointments, pkg.packageId ]
		);

		function onLocationChange( nextLocationId ) {
			runUpdate( {
				action: 'set_package_handling',
				package_id: pkg.packageId,
				pickup_location_id: parseInt( nextLocationId, 10 ) || 0,
				pickup_date: pkg.pickupDate || '',
				appointment_offset: pkg.appointmentOffset || '',
			} );
		}

		function onTimeChange( event ) {
			runUpdate( {
				action: 'set_package_handling',
				package_id: pkg.packageId,
				pickup_location_id: locationId,
				pickup_date: pkg.pickupDate || '',
				appointment_offset: event.target.value,
			} );
		}

		function onClearDate( event ) {
			event.preventDefault();
			var $ = window.jQuery;
			if ( $ && $.fn && $.fn.datepicker ) {
				var $input = $( '#' + datepickerId );
				if ( $input.length && $input.hasClass( 'hasDatepicker' ) ) {
					$input.datepicker( 'setDate', null );
				}
				$input.val( '' );
				$( '#' + datepickerAltId ).val( '' );
			}
			runUpdate( {
				action: 'set_package_handling',
				package_id: pkg.packageId,
				pickup_location_id: locationId,
				pickup_date: '',
				appointment_offset: '',
			} );
		}

		if ( ! pkg.isPickup ) {
			return null;
		}

		var selected = pkg.selectedLocation;

		var scheduleLabel = '';
		if ( timesData ) {
			if ( timesData.anytime ) {
				scheduleLabel = ( I18N.openingHours || 'Opening hours for pickup on %s:' ).replace(
					'%s',
					timesData.dayLabel || ''
				);
			} else {
				scheduleLabel = ( I18N.availableTimes || 'Available pickup times on %1$s (all times in %2$s):' )
					.replace( '%1$s', timesData.dayLabel || '' )
					.replace( '%2$s', timesData.timezone || '' );
			}
		}

		return el(
			'div',
			{ className: 'dslpfw-blocks-package' + ( busy ? ' is-busy' : '' ) },
			showLocationSelect
				? el( LocationSelect, {
						locations: pkg.locations || [],
						value: locationId || '',
						disabled: busy,
						onChange: onLocationChange,
				  } )
				: null,
			selected
				? el(
						'div',
						{ className: 'dslpfw-blocks-selected-location' },
						! showLocationSelect ? el( 'strong', null, selected.name ) : null,
						selected.addressHtml
							? el( 'div', {
									className: 'dslpfw-blocks-location-address',
									dangerouslySetInnerHTML: { __html: selected.addressHtml },
							  } )
							: null,
						selected.description
							? el(
									'small',
									{ className: 'dslpfw-blocks-location-note' },
									el( 'strong', null, ( I18N.note || 'Note:' ) + ' ' ),
									el( 'span', {
										dangerouslySetInnerHTML: { __html: selected.description },
									} )
							  )
							: null
				  )
				: null,
			showLocationSelect && ! locationId
				? el( 'em', null, I18N.pleaseChooseLocation || 'Please choose a pickup location' )
				: null,
			showAppointments && locationId
				? el(
						'div',
						{ className: 'dslpfw-blocks-appointment pickup-location-calendar' },
						el(
							'label',
							{
								className: 'dslpfw-blocks-appointment-label',
								htmlFor: datepickerId,
							},
							( I18N.scheduleAppointment || 'Schedule a pickup appointment' ) +
								( appointmentsMode === 'required' ? ' *' : ' ' + ( I18N.optional || '(optional)' ) )
						),
						el(
							'div',
							{ className: 'dslpfw-blocks-date-field form-row', style: { position: 'relative' } },
							el( 'input', {
								type: 'text',
								readOnly: true,
								id: datepickerId,
								ref: dateInputRef,
								className: 'dslpfw-blocks-appointment-date dslpfw-pickup-location-appointment-date input-text',
								placeholder: I18N.pickupDate || 'Pickup Date',
								required: appointmentsMode === 'required',
								disabled: busy,
							} ),
							el( 'input', {
								type: 'hidden',
								id: datepickerAltId,
								className: 'dslpfw-pickup-location-appointment-date-alt',
								value: pkg.pickupDate || '',
								readOnly: true,
							} )
						),
						appointmentsMode !== 'required' && pkg.pickupDate
							? el(
									'a',
									{
										href: '#',
										className: 'dslpfw-blocks-clear-date',
										onClick: onClearDate,
									},
									I18N.clear || 'Clear'
							  )
							: null,
						pkg.pickupDate && timesData
							? el(
									'div',
									{ className: 'dslpfw-blocks-schedule' },
									el( 'small', null, scheduleLabel ),
									! timesData.anytime && timesData.times && timesData.times.length
										? el(
												'select',
												{
													className: 'dslpfw-blocks-appointment-time',
													value: pkg.appointmentOffset || ( timesData.times[ 0 ] && timesData.times[ 0 ].offset ) || '',
													disabled: busy,
													onChange: onTimeChange,
												},
												timesData.times.map( function ( time ) {
													return el(
														'option',
														{ key: time.offset, value: time.offset },
														time.label
													);
												} )
										  )
										: null,
									timesData.anytime && timesData.openingHours && timesData.openingHours.length
										? el(
												'ul',
												null,
												timesData.openingHours.map( function ( hour, index ) {
													return el( 'li', { key: String( index ) }, el( 'small', null, hour ) );
												} )
										  )
										: null
							  )
							: null
				  )
				: null
		);
	}

	function CheckoutPickupPanel() {
		var cartData = wpData.useSelect( function ( select ) {
			var store = select( 'wc/store/cart' );
			return store && store.getCartData ? store.getCartData() : {};
		}, [] );

		var cartExt = getCartExtFromSources( cartData, cartData.extensions );
		var itemsHavePickup = ( cartData.items || [] ).some( function ( item ) {
			var ext = getItemExt( item );
			return ext.enabled && ( ext.handling === 'pickup' || ext.mustBePickedUp );
		} );
		var pickupSelected = cartHasLocalPickupSelected( cartData ) || itemsHavePickup;
		var packages = ( cartExt.packages || [] ).slice();
		var derivedLocationId = deriveLocationIdFromItems( cartData.items );
		var derivedLocation = null;
		var fallbackPackageId = '0';

		if ( cartData.shippingRates && cartData.shippingRates.length ) {
			var pickupRatePackage = cartData.shippingRates.find( function ( ratePackage ) {
				return ( ratePackage.shipping_rates || [] ).some( function ( rate ) {
					return rate.selected && rateIsLocalPickup( rate );
				} );
			} );
			fallbackPackageId = String(
				( pickupRatePackage || cartData.shippingRates[ 0 ] ).package_id
			);
		}

		( cartData.items || [] ).some( function ( item ) {
			var ext = getItemExt( item );
			if ( ext.enabled && ext.handling === 'pickup' && ext.selectedLocation ) {
				derivedLocation = ext.selectedLocation;
				return true;
			}
			return false;
		} );

		// Ensure we always have a package panel when local pickup is selected,
		// even if Store API package extension is incomplete.
		if ( pickupSelected && ! packages.length ) {
			packages.push( {
				packageId: fallbackPackageId,
				isPickup: true,
				perOrderSelection: cartExt.pickupSelectionMode !== 'per-item',
				pickupLocationId: derivedLocationId,
				selectedLocation: derivedLocation,
				locations: [],
				pickupDate: '',
				appointmentOffset: '',
				appointmentsMode: cartExt.appointmentsMode || settings.appointmentsMode || 'disabled',
			} );
		}

		packages = packages.map( function ( pkg ) {
			var next = Object.assign( {}, pkg );
			if ( pickupSelected ) {
				next.isPickup = true;
			}
			if ( ! next.pickupLocationId && derivedLocationId ) {
				next.pickupLocationId = derivedLocationId;
			}
			if ( ! next.selectedLocation && derivedLocation ) {
				next.selectedLocation = derivedLocation;
			}
			if ( ! next.appointmentsMode ) {
				next.appointmentsMode = cartExt.appointmentsMode || settings.appointmentsMode || 'disabled';
			}
			return next;
		} );

		var visiblePackages = packages.filter( function ( pkg ) {
			return pkg.isPickup || pickupSelected;
		} );

		if ( ! visiblePackages.length ) {
			return null;
		}

		return el(
			'div',
			{ className: 'dslpfw-blocks-checkout' },
			visiblePackages.map( function ( pkg ) {
				return el( PackagePickupPanel, {
					key: String( pkg.packageId ),
					packageData: pkg,
					cartExt: cartExt,
				} );
			} )
		);
	}

	function CheckoutAppointmentPortals() {
		var mountsState = useState( null );
		var host = mountsState[ 0 ];
		var setHost = mountsState[ 1 ];

		useEffect(
			function () {
				function sync() {
					var shippingTotals = document.querySelector(
						'.wc-block-components-totals-shipping, .wp-block-woocommerce-checkout-order-summary-shipping-block'
					);
					var fees = document.querySelector(
						'.wc-block-components-totals-fees, .wp-block-woocommerce-checkout-order-summary-fee-block, .wc-block-components-totals-fees__fee'
					);
					var orderSummaryContent = document.querySelector(
						'.wc-block-components-order-summary, .wp-block-woocommerce-checkout-order-summary-block .wc-block-components-totals-wrapper'
					);
					var totalsWrapper = document.querySelector(
						'.wc-block-checkout__sidebar .wc-block-components-totals-wrapper, .wp-block-woocommerce-checkout-totals-block'
					);

					var existing = document.getElementById( 'dslpfw-blocks-appointment-host' );
					if ( ! existing ) {
						existing = document.createElement( 'div' );
						existing.id = 'dslpfw-blocks-appointment-host';
						existing.className = 'dslpfw-blocks-appointment-host';

						var anchor = fees || shippingTotals;
						if ( anchor && anchor.parentNode ) {
							anchor.parentNode.insertBefore( existing, anchor.nextSibling );
						} else if ( orderSummaryContent ) {
							orderSummaryContent.appendChild( existing );
						} else if ( totalsWrapper ) {
							totalsWrapper.appendChild( existing );
						} else {
							existing = null;
						}
					}

					setHost( existing );
				}

				sync();
				var observer = new MutationObserver( sync );
				observer.observe( document.body, { childList: true, subtree: true } );
				return function () {
					observer.disconnect();
				};
			},
			[]
		);

		if ( ! createPortal || ! host ) {
			return null;
		}

		return createPortal( el( CheckoutPickupPanel, null ), host );
	}

	function Boot() {
		var isCart = !! document.querySelector( '.wp-block-woocommerce-cart, .wc-block-cart' );
		var isCheckout = !! document.querySelector( '.wp-block-woocommerce-checkout, .wc-block-checkout' );
		var lastSignature = '';

		useEffect(
			function () {
				if ( ! wpData.subscribe ) {
					return undefined;
				}
				return wpData.subscribe( function () {
					var store = wpData.select( 'wc/store/cart' );
					if ( ! store || ! store.getCartData ) {
						return;
					}
					var rates = store.getCartData().shippingRates || [];
					var signature = rates
						.map( function ( ratePackage ) {
							var selected = ( ratePackage.shipping_rates || [] ).find( function ( rate ) {
								return rate.selected;
							} );
							return ratePackage.package_id + ':' + ( selected ? selected.rate_id : '' );
						} )
						.join( '|' );
					if ( ! signature || signature === lastSignature ) {
						return;
					}
					lastSignature = signature;
					rates.forEach( function ( ratePackage ) {
						var selected = ( ratePackage.shipping_rates || [] ).find( function ( rate ) {
							return rate.selected;
						} );
						if ( ! selected ) {
							return;
						}
						updateExtension( {
							action: 'set_package_items_handling',
							package_id: String( ratePackage.package_id ),
							handling: rateIsLocalPickup( selected ) ? 'pickup' : 'ship',
						} );
					} );
				} );
			},
			[]
		);

		if ( ! isCart && ! isCheckout ) {
			return null;
		}

		return el(
			wpElement.Fragment,
			null,
			el( CartItemPortals, null ),
			isCheckout ? el( CheckoutAppointmentPortals, null ) : null
		);
	}

	// Mount root portal runner.
	function mountRoot() {
		var rootId = 'dslpfw-blocks-root';
		var root = document.getElementById( rootId );
		if ( ! root ) {
			root = document.createElement( 'div' );
			root.id = rootId;
			document.body.appendChild( root );
		}
		if ( wpElement.createRoot ) {
			wpElement.createRoot( root ).render( el( Boot, null ) );
		} else if ( window.ReactDOM && window.ReactDOM.render ) {
			window.ReactDOM.render( el( Boot, null ), root );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', mountRoot );
	} else {
		mountRoot();
	}

	// Prefer the order-summary portal for classic-like placement under shipping/fees.
	// SlotFills can duplicate the same panel, so they are intentionally not registered here.
} )();
