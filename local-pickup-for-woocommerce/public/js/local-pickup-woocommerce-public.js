(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
    $(function() {
        //pickup location dropdown init & change event ( "o" function in reference plugin )
        function dslpfw_get_pickup_location_option_attr( data, attr ) {
            if ( data.element ) {
                return $( data.element ).attr( attr ) || '';
            }

            return '';
        }

        function dslpfw_pickup_location_option_matches_search( data, term ) {
            var name = ( data.name || dslpfw_get_pickup_location_option_attr( data, 'data-name' ) ).toLowerCase();
            var address = ( data.address || dslpfw_get_pickup_location_option_attr( data, 'data-address' ) ).toLowerCase();
            var postcode = ( data.postcode || dslpfw_get_pickup_location_option_attr( data, 'data-postcode' ) ).toLowerCase();
            var city = dslpfw_get_pickup_location_option_attr( data, 'data-city' ).toLowerCase();
            var text = ( data.text || '' ).toLowerCase();

            if ( data.element && ! text ) {
                text = $( data.element ).text().toLowerCase();
            }

            return (
                name.indexOf( term ) > -1 ||
                address.indexOf( term ) > -1 ||
                postcode.indexOf( term ) > -1 ||
                city.indexOf( term ) > -1 ||
                text.indexOf( term ) > -1
            );
        }

        function dslpfw_initiate_select2() {
            var selct2_obj = $('.pickup-location-list');
            var selectFn = $.fn.selectWoo || $.fn.select2;

            // Fixes for #105002 ticket issue
            if ( selct2_obj.length === 0 || ! selectFn ) { return; }

            selct2_obj.each(function() {
                var $select = $(this);

                if ( $select.hasClass( 'select2-hidden-accessible' ) ) {
                    selectFn.call( $select, 'destroy' );
                }
            });

            var nearbyEnabled = !! dslpfw_front_vars.nearby_pickup_locations_enabled;
            var select2Options = {
                width: 'resolve',
                templateResult: function (state) {
                    if (!state.id) {
                        return state.text;
                    }
                    var location_name = state.name ? state.name : dslpfw_get_pickup_location_option_attr( state, 'data-name' ),
                        location_address = state.address ? state.address : dslpfw_get_pickup_location_option_attr( state, 'data-address' ),
                        location_postcode = state.postcode ? state.postcode : dslpfw_get_pickup_location_option_attr( state, 'data-postcode' );
                    var $state = $(
                        '<span>' + location_name + '</span><br>' + 
                        '<small>' + location_address + ' - <em>' + location_postcode + '</em></small>'
                    );
                    return $state ? $state : state.text;
                },
                matcher: function(params, data) {
                    // Placeholder option should always be available to Select2 internals.
                    if ( ! data.id ) {
                        return data;
                    }

                    var term = $.trim( params.term || params.query || '' ).toLowerCase();
                    var isSearching = term.length > 0;

                    // When searching, include all eligible locations regardless of nearby flag.
                    if ( isSearching ) {
                        return dslpfw_pickup_location_option_matches_search( data, term ) ? data : null;
                    }

                    // When not searching, only show nearby locations by default.
                    if ( nearbyEnabled ) {
                        var isNearby = dslpfw_get_pickup_location_option_attr( data, 'data-nearby' );

                        if ( '1' !== isNearby ) {
                            return null;
                        }
                    }

                    return data;
                }
            };

            if ( nearbyEnabled ) {
                select2Options.minimumResultsForSearch = 0;
            }

            selectFn.call( selct2_obj, select2Options );

            selct2_obj.off('change.ds-local-pickup').on( 'change.ds-local-pickup', function () {
                var $selectedOption = $(this).find('option:selected');

                if ( $selectedOption.length && $selectedOption.val() && '1' !== $selectedOption.attr( 'data-nearby' ) ) {
                    $selectedOption.attr( 'data-nearby', '1' );
                }
                var object_type = $(this).data('pickup-object-type');
                var object_id = $(this).data('pickup-object-id');
                var current_val = $(this).val();
                var data;
                if( 'cart-item' === object_type ){
                    data = { 
                        action: 'dslpfw_set_cart_item_handling', 
                        security: dslpfw_front_vars.dslpfw_set_cart_item_handling_nonce, 
                        cart_item_key: object_id, 
                        pickup_data: { 
                            handling: 'pickup',
                            pickup_location_id: current_val 
                        } 
                    };
                }
                if( 'package' === object_type ){
                    data = { 
                        action: 'dslpfw_set_package_handling', 
                        security: dslpfw_front_vars.dslpfw_set_package_handling_nonce, 
                        package_id: object_id,  
                        pickup_location_id: current_val 
                    };
                } 
                $('.woocommerce-cart-form, .cart_totals, .woocommerce-checkout-review-order').block({
                    message: null,
                    overlayCSS: {
                        background: 'rgb(255, 255, 255)',
                        opacity: 0.6,
                    },
                });
                $.ajax({
                    type: 'POST',
                    url: dslpfw_front_vars.ajaxurl,
                    data: data,
                    success: function( responce ){
                        $('.woocommerce-cart-form, .cart_totals, .woocommerce-checkout-review-order').unblock();
                        if( responce.success ) {
                            if( dslpfw_front_vars.is_cart ) {
                                $(document).trigger('wc_update_cart');
                            }
                            if( dslpfw_front_vars.is_checkout ) {
                                $(document.body).trigger('update_checkout');
                            }
                        }
                    }
                });
            });
        }
        dslpfw_initiate_select2();

        //Toggle pickup and shipping handling for cart items ( "c" function in reference plugin )
        function dslpfw_toggle_pickup_shipping_handling() {
            $('a.dslpfw-local-pickup-enable, a.dslpfw-local-pickup-disable').on('click', function (e) {
                e.preventDefault();
                var handling;
                var toggle_parent = $(this).closest('.dslpfw-pickup-location-field-toggle');
                if( $(this).hasClass('dslpfw-local-pickup-enable') ) {
                    handling = 'pickup'; 
                    toggle_parent.find('a.dslpfw-local-pickup-enable').parent().hide();
                    toggle_parent.find('a.dslpfw-local-pickup-disable').parent().show();
                    toggle_parent.find('> div').show();
                } else {
                    handling = 'ship';
                    toggle_parent.find('a.dslpfw-local-pickup-enable').parent().show();
                    toggle_parent.find('a.dslpfw-local-pickup-disable').parent().hide();
                    toggle_parent.find('> div').hide();
                }
                var pickup_location_id = $(this).closest('td').find('.pickup-location-list').val();

                var data = {
                    action: 'dslpfw_set_cart_item_handling',
                    security: dslpfw_front_vars.dslpfw_set_cart_item_handling_nonce,
                    cart_item_key: toggle_parent.data('pickup-object-id'),
                    pickup_data: { 
                        handling: handling, 
                        pickup_location_id: pickup_location_id 
                    },
                };
                $('.woocommerce-cart-form').block({
                    message: null,
                    overlayCSS: {
                        background: 'rgb(255, 255, 255)',
                        opacity: 0.6,
                    },
                });
                $.ajax({
                    type: 'POST',
                    url: dslpfw_front_vars.ajaxurl,
                    data: data,
                    success: function( responce ){
                        if( 'per-order' === dslpfw_front_vars.pickup_selection_mode) {
                            $('.pickup-location-list').trigger('change');
                        }
                        $('.woocommerce-cart-form').unblock();
                        if( responce.success ) {
                            if( dslpfw_front_vars.is_cart ) {
                                $(document).trigger('wc_update_cart');
                            }
                            if( dslpfw_front_vars.is_checkout ) {
                                $(document.body).trigger('update_checkout');
                            }
                        }
                    }
                });
            });
        }
        dslpfw_toggle_pickup_shipping_handling();

        //On change shipping methos session data modify ( "i" function in reference plugin )
        $('input[name^="shipping_method"][type="radio"]').off('change.ds-local-pickup').on('change.ds-local-pickup', function () {
            var shipping_type = $(this).is(':checked') && 'ds_local_pickup' === $(this).val() ? 'pickup' : 'ship';
            var package_id = $(this).attr('data-index');
            $.ajax({
                type: 'POST',
                url: dslpfw_front_vars.ajaxurl,
                data: {
                    action: 'dslpfw_set_package_items_handling', 
                    security: dslpfw_front_vars.dslpfw_set_package_items_handling_nonce, 
                    package_id: package_id,  
                    handling: shipping_type
                },
                success: function( responce ){
                    if( responce.success ) {
                        if( dslpfw_front_vars.is_cart ) {
                            $(document.body).on('updated_shipping_method', function () {
                                return $(document).trigger('wc_update_cart');
                            });
                        }
                        if( dslpfw_front_vars.is_checkout ) {
                            h(document.body).one('updated_checkout', function () {
                                return h(document.body).trigger('update_checkout');
                            });
                        }
                    }
                }
            });
        });

        if(dslpfw_front_vars.is_cart) {
            remove_shipping_calc_and_details();
        }
        if(dslpfw_front_vars.is_checkout){
            dslpfw_show_hide_shipping_fields_checkout();
        }
        
        $('#order_review').find('> p.woocommerce-shipping-contents').remove(),
        $(document.body).on('updated_cart_totals', function () {
            remove_shipping_calc_and_details();
            dslpfw_toggle_pickup_shipping_handling();
            dslpfw_initiate_select2();
        });

        $(document.body).on('updated_checkout', function () {
            dslpfw_show_hide_shipping_fields_checkout();
            dslpfw_toggle_pickup_shipping_handling();
            dslpfw_initiate_select2();

            if( $('.dslpfw-pickup-location-appointment') ) {
                $('.dslpfw-pickup-location-appointment').each(function(){
                    var $this = $(this);
                    var pickup_location = $this.find('input.dslpfw-pickup-location-appointment-date').data('location-id');
                    $.ajax({
                        type: 'POST',
                        url: dslpfw_front_vars.ajaxurl,
                        data: {
                            action: 'dslpfw_get_pickup_location_appointment_data', 
                            security: dslpfw_front_vars.dslpfw_get_pickup_location_appointment_data_nonce, 
                            location_id: pickup_location
                        },
                        success: function( responce ){
                            if( responce.success ) {
                                update_datepicker($this, responce.data);
                            }                            
                        }
                    });
                });
            }
        });
    });

    function update_datepicker( $this, $appointment_data) {
        var datepicker_field = $this.find('input.dslpfw-pickup-location-appointment-date');
        var datepicker_field_val = $this.find('input.dslpfw-pickup-location-appointment-date-alt');
        var clear_datepicker = $this.find('[id^=dslpfw-date-clear-]');
        var datepicker_val = datepicker_field.val();
        var location_id = datepicker_field.data('location-id');
        var package_id = datepicker_field.data('package-id');
        var date_from_datepicker = ( '' !== datepicker_val ) ? new Date(datepicker_val) : ($appointment_data.default_date && '' !== $appointment_data.default_date ? new Date(1e3 * $appointment_data.default_date) : null );
        var disabled_dates = $appointment_data.unavailable_dates ? $.map($appointment_data.unavailable_dates, function (date) {
            return date;
        }) : [];
        
        // datepicker_field.attr('value', '');
        // datepicker_field.trigger('change');
        // datepicker_field.removeClass('hasDatepicker');
        // datepicker_field.datepicker('destroy');

        var min_date = new Date(1e3 * $appointment_data.calendar_start);
        var max_date = new Date(1e3 * $appointment_data.calendar_end);
        min_date = new Date(min_date.getTime() + 60 * min_date.getTimezoneOffset() * 1e3);
        max_date = new Date(max_date.getTime() + 60 * max_date.getTimezoneOffset() * 1e3);
        date_from_datepicker = new Date(date_from_datepicker.getTime() + 60 * date_from_datepicker.getTimezoneOffset() * 1e3);

        datepicker_field.datepicker({
            minDate: min_date,
            maxDate: max_date,
            altField: '#' + datepicker_field_val.attr('id'),
            altFormat: 'yy-mm-dd',
            dateFormat: 'MM dd, yy',
            defaultDate: date_from_datepicker || null,
            firstDay: dslpfw_front_vars.start_of_week,
            prevText: '',
            nextText: '',
            showOn: 'both',
            gotoCurrent: true,
            changeMonth: true,
            changeYear: true,
            beforeShow: function (e, t) {
                return $(t.dpDiv).addClass('dslpfw-appointment-datepicker').addClass('dslpfw-appointment-datepicker-' + package_id);
            },
            beforeShowDay: function (date) {
                date = $.datepicker.formatDate('yy-mm-dd', date);
                return [-1 === disabled_dates.indexOf(date), 'dslpfw_available'];
            },
            onSelect: show_timerange,
        })
        .one('init', function () {
            return $('button.ui-datepicker-trigger').attr('title', dslpfw_front_vars.datepicker_title);
        })
        .trigger('init');
        
        $this.find('select.dslpfw-pickup-location-appointment-offset').select2().on('change', function (e) {
            return e.preventDefault(), e.stopPropagation(), show_timerange($.datepicker.formatDate('yy-mm-dd', datepicker_field.datepicker('getDate')), datepicker_field);
        });

        var selected_date = datepicker_field.val() && datepicker_field.val().match(/^\d{4}-\d{2}-\d{2}$/) ? $.datepicker.parseDate('yy-mm-dd', datepicker_field.val()) : null;
        if( selected_date ){
            datepicker_field.datepicker('setDate', selected_date);
            $('#ui-datepicker-div').hide();
        } else {
            if( $appointment_data.auto_select_default && $appointment_data.default_date && '' !== $appointment_data.default_date ){
                var default_date = $.datepicker.parseDate('yy-mm-dd', $appointment_data.default_date);
                datepicker_field.datepicker('setDate', default_date);
                $('#ui-datepicker-div').hide();
                show_timerange( default_date, datepicker_field );
            }
        }

        clear_datepicker.on( 'click', function(e){
            e.preventDefault();
            $('.woocommerce-checkout-review-order').block({
                message: null,
                overlayCSS: {
                    background: 'rgb(255, 255, 255)',
                    opacity: 0.6,
                },
            });
            datepicker_field.datepicker('setDate', null);
            datepicker_field.attr('value', '');
            $.ajax({
                type: 'POST',
                url: dslpfw_front_vars.ajaxurl,
                data: {
                    action: 'dslpfw_set_package_handling', 
                    security: dslpfw_front_vars.dslpfw_set_package_handling_nonce, 
                    pickup_date: '', 
                    package_id: package_id, 
                    pickup_location_id: location_id,
                },
                success: function( responce ){
                    $('.woocommerce-checkout-review-order').unblock();
                    if( responce.success ) {
                        $this.find('.dslpfw-pickup-location-schedule').empty();
                        $(document.body).trigger('update_checkout');
                    }                            
                }
            });
        });
    }

    function show_timerange( datetext, ele ) {
        ele = ele.input ? ele.input : ele;
        var location_id = ele.data('location-id');
        var show_schedule_ele = ele.parent().parent();
        var package_id = ele.data('package-id');
        var pickup_date = $('#dslpfw-pickup-date-' + package_id).val();
        var pickup_offset = $('#dslpfw-pickup-appointment-offset-' + package_id);
        var pickup_offset_val = pickup_offset.val();
        if( datetext && pickup_date && new Date(pickup_date) ) {
            ele.attr( 'value', datetext );
            $('.woocommerce-checkout-review-order').block({
                message: null,
                overlayCSS: {
                    background: 'rgb(255, 255, 255)',
                    opacity: 0.6,
                },
            });
            $.ajax({
                type: 'POST',
                url: dslpfw_front_vars.ajaxurl,
                data: {
                    action: 'dslpfw_set_package_handling', 
                    security: dslpfw_front_vars.dslpfw_set_package_handling_nonce, 
                    pickup_date: pickup_date, 
                    package_id: package_id, 
                    pickup_location_id: location_id, 
                    appointment_offset: pickup_offset_val
                },
                success: function( responce ){
                    if( responce.success ) {
                        if( pickup_offset.length === 0 ) {
                            $.ajax({
                                type: 'POST',
                                url: dslpfw_front_vars.ajaxurl,
                                data: {
                                    action: 'dslpfw_get_pickup_location_opening_hours_list', 
                                    security: dslpfw_front_vars.dslpfw_get_pickup_location_opening_hours_list_nonce, 
                                    location: location_id, 
                                    package_id: package_id, 
                                    pickup_date: pickup_date
                                },
                                success: function( responce ){
                                    if (responce && responce.success) {
                                        var append_html = show_schedule_ele.find('.dslpfw-pickup-location-schedule');
                                        append_html.empty();
                                        append_html.append(responce.data);
                                        $(document.body).trigger('update_checkout');
                                    }
                                    $('.woocommerce-checkout-review-order').unblock();              
                                }
                            });
                        } else {
                            $(document.body).trigger('update_checkout');
                            $('.woocommerce-checkout-review-order').unblock();
                        }
                    } else {
                        $('.woocommerce-checkout-review-order').unblock();
                    }                        
                }
            });
        }
    }

    /** hide shipping calc and address for shipping details ("u" function in reference plugin) */
    function remove_shipping_calc_and_details(){
        $('.woocommerce-shipping-totals').each(function () {
            var checkValue = $(this).find('input.shipping_method:checked').val();
            var hiddenValue = $(this).find('input:hidden.shipping_method').val();
            if (checkValue === dslpfw_front_vars.shipping_method_id || hiddenValue === dslpfw_front_vars.shipping_method_id) {
                $(this).find('p.woocommerce-shipping-destination, .woocommerce-shipping-calculator').hide();
            }
        });
    }
    /** Hide shipping method checkbox from checkout page for pickup only ("p" function in reference plugin) */
    function dslpfw_show_hide_shipping_fields_checkout(){
        if( ! dslpfw_front_vars.dslpfw_display_shipping_address_fields ){
            var pickup_selected;
            if( 'per-item' === dslpfw_front_vars.pickup_selection_mode) {
                pickup_selected = parseInt($('#dslpfw-packages-to-pickup').val());
            } else {
                pickup_selected = $('.woocommerce-shipping-methods').find('input[value=ds_local_pickup]:checked').length;
        
                // If no radio button is found, get the value from the hidden input for local pick up
                if ( pickup_selected === 0 ) {
                    pickup_selected = $('input[type="hidden"][name^="shipping_method"][value="ds_local_pickup"]').length;
                }
            }

            if( pickup_selected > 0 ) {
                $('#shiptobilling, #ship-to-different-address').hide(),
                $('#shiptobilling, #ship-to-different-address').parent().find('h3').hide(),
                $('#ship-to-different-address input').prop('checked', !1),
                $('.shipping_address').hide();
            } else {
                $('#shiptobilling, #ship-to-different-address').show(),
                $('#shiptobilling, #ship-to-different-address').parent().find('h3').show(),
                $('#ship-to-different-address input').is(':checked') ? $('.shipping_address').show() : $('.shipping_address').hide();
            }
        }
    }

})( jQuery );
