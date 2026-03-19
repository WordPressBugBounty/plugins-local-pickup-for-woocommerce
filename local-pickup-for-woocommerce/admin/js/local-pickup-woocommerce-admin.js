(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
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

    $(document).ready(function() { 
        
        /** tiptip js implementation */
        $( '.woocommerce-help-tip' ).tipTip( {
            'attribute': 'data-tip',
            'fadeIn': 50,
            'fadeOut': 50,
            'delay': 200,
            'keepAlive': true
        } );

        /** select2 js implementation */
        if ($('.dslpfw-display-country').length) {
            $('.dslpfw-display-country').select2({
                placeholder: 'Select Countries',
                allowClear: true,
            });
        }

        //Toggle button activate import-export type
        toggleType();
        $('.dslpfw-toggle-slider').change(function () {
            toggleType();
        });
        $('.dslpfw-type').click(function(){ 
            $(this).parent().find('.dslpfw-toggle-slider').trigger('click'); 
        });

        // Active sidebar menu for all plugin page
        $( 'a[href="admin.php?page=dslpfw-local-pickup-settings"]' ).parents().addClass( 'current wp-has-current-submenu' );
        $( 'a[href="admin.php?page=dslpfw-local-pickup-settings"]' ).addClass( 'current' );

        sectionToggle( 'dslpfw-choose-locations', 'per-order' );
        $('.dslpfw-choose-locations').change(function() {
            sectionToggle( 'dslpfw-choose-locations', 'per-order' );
        });

        var show_sections = [ 'enabled', 'required' ];
        var choose_location = $('.dslpfw-pickup-mode').val();
        if( jQuery.inArray( choose_location, show_sections ) !== -1 ) {
            $('.show-appointment-fields').show();
        } else {
            $('.show-appointment-fields').hide();
        }
        $('.dslpfw-pickup-mode').change(function() {
            var choose_location = $(this).val();

            if( choose_location.includes('_disabled') ){
                $(this).find(':selected').prop('selected', false);
                $('body').addClass('dslpfw-modal-visible');
                
                if( $.inArray( choose_location, show_sections ) !== -1 ) {
                    $('.show-appointment-fields').show();
                } else {
                    $('.show-appointment-fields').hide();
                }
            } else {
                if( $.inArray( choose_location, show_sections ) !== -1 ) {
                    $('.show-appointment-fields').show();
                } else {
                    $('.show-appointment-fields').hide();
                }
            }
        });

        toggle_appointment_fields( 'dslpfw_pickup_location_pickup_hours' );
        toggle_appointment_fields( 'dslpfw_pickup_location_holiday_dates' );
        toggle_appointment_fields( 'dslpfw_pickup_location_lead_time' );
        toggle_appointment_fields( 'dslpfw_pickup_location_deadline' );
        toggle_appointment_fields( 'dslpfw_pickup_location_fee_adjustment' );
        toggle_appointment_fields( 'dslpfw_pickup_location_products' );
        toggle_appointment_fields( 'dslpfw_pickup_location_categories' );
        
        /** 
         * Plugin Setup Wizard Script START 
         */
        // Hide & show wizard steps based on the url params 
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('require_license')) {
            $('.ds-plugin-setup-wizard-main .tab-panel').hide();
            $( '.ds-plugin-setup-wizard-main #step5' ).show();
        } else {
            $( '.ds-plugin-setup-wizard-main #step1' ).show();
        }
            
        // Plugin setup wizard steps script
        $(document).on('click', '.ds-plugin-setup-wizard-main .tab-panel .btn-primary:not(.ds-wizard-complete)', function () {
            var curruntStep = jQuery(this).closest('.tab-panel').attr('id');
            var nextStep = 'step' + ( parseInt( curruntStep.slice(4,5) ) + 1 ); // Masteringjs.io

            if( 'step5' !== curruntStep ) {

                //Youtube videos stop on next step
                $('iframe[src*="https://www.youtube.com/embed/"]').each(function(){
                    $(this).attr('src', $(this).attr('src'));
                    return false;
                });

                $( '#' + curruntStep ).hide();
                $( '#' + nextStep ).show();   
            }
        });

        // Get allow for marketing or not
        if ( $( '.ds-plugin-setup-wizard-main .ds_count_me_in' ).is( ':checked' ) ) {
            $('#fs_marketing_optin input[name="allow-marketing"][value="true"]').prop('checked', true);
        } else {
            $('#fs_marketing_optin input[name="allow-marketing"][value="false"]').prop('checked', true);
        }

        // Get allow for marketing or not on change	    
        $(document).on( 'change', '.ds-plugin-setup-wizard-main .ds_count_me_in', function() {
            if ( this.checked ) {
                $('#fs_marketing_optin input[name="allow-marketing"][value="true"]').prop('checked', true);
            } else {
                $('#fs_marketing_optin input[name="allow-marketing"][value="false"]').prop('checked', true);
            }
        });

        // Complete setup wizard
        $(document).on( 'click', '.ds-plugin-setup-wizard-main .tab-panel .ds-wizard-complete', function() {
            if ( $( '.ds-plugin-setup-wizard-main .ds_count_me_in' ).is( ':checked' ) ) {
                $( '.fs-actions button'  ).trigger('click');
            } else {
                $('.fs-actions #skip_activation')[0].click();
            }
        });

        // Send setup wizard data on Ajax callback
        $(document).on( 'click', '.ds-plugin-setup-wizard-main .fs-actions button', function() {
            var wizardData = {
                'action': 'dslpfw_plugin_setup_wizard_submit',
                'survey_list': $('.ds-plugin-setup-wizard-main .ds-wizard-where-hear-select').val(),
                'nonce': dslpfw_vars.setup_wizard_ajax_nonce
            };

            $.ajax({
                url: dslpfw_vars.ajaxurl,
                data: wizardData,
                success: function ( success ) {
                    console.log(success);
                }
            });
        });
        /** 
         * Plugin Setup Wizard Script End 
         */

        $('.status_switch').change(function(){
            var get_val = $(this).is(':checked');
            var post_id = $(this).data('dslpfwid');
            if( post_id > 0 && '' !== post_id ) {
                $.ajax({
                    type: 'POST',
                    url: dslpfw_vars.ajaxurl,
                    data: {
                        'action'    : 'dslpfw_change_status_from_list',
                        'dslpfw_id' : post_id,
                        'dslpfw_status': get_val,
                        'security'  : dslpfw_vars.status_change_listing_ajax_nonce
                    },
                    beforeSend: function(){
                        $('.dslpfw-section-left .wp-list-table').block({
                            message: null,
                            overlayCSS: {
                                background: 'rgb(255, 255, 255)',
                                opacity: 0.6,
                            },
                        });
                    },
                    success: function(){
                        $('.dslpfw-section-left .wp-list-table').unblock();
                    }
                });
            }
        });

        /** Time range slider start
         * Note: all things are in minute
        */
        $('.time-range-wrap').find('.slider-range').slider({
            range: true,
            min: 0,
            max: 1440,
            step: 15,
            values: [540, 1020],
            slide: function (e, ui) {
                updateTimeLabels($(this), ui.values);
            }
        });

        // Show exist slider
        $('.time-range-wrap').find('.sliders-step').each(function(){
            var defaultValues = [];
            var defaultStartValues = $(this).find('.pickup_hours_start').val();
            defaultStartValues = Math.floor(defaultStartValues / 60);

            var defaultEndValues = $(this).find('.pickup_hours_end').val();
            defaultEndValues = Math.floor(defaultEndValues / 60);

            defaultValues[0] = parseInt(defaultStartValues);
            defaultValues[1] = parseInt(defaultEndValues);
            updateTimeLabels($(this), defaultValues);
            $(this).find('.slider-range').slider('values', defaultValues);
        });

        // Function to update time labels
        function updateTimeLabels($this, values) {
            var hours1 = Math.floor(values[0] / 60);
            var minutes1 = values[0] - (hours1 * 60);
            var seconds1 = values[0] * 60;
            var time1 = formatTime(hours1, minutes1);
            
            var hours2 = Math.floor(values[1] / 60);
            var minutes2 = values[1] - (hours2 * 60);
            var seconds2 = values[1] * 60;
            var time2 = formatTime(hours2, minutes2);

            // Update time labels
            $this.closest('.time-range').find('.pickup-start-time').html(time1);
            $this.closest('.time-range').find('.pickup-end-time').html(time2);

            // Update time in hidden field for store
            $this.closest('.sliders-step').find('.pickup_hours_start').val(seconds1);
            $this.closest('.sliders-step').find('.pickup_hours_end').val(seconds2);
        }
        
        function formatTime(hours, minutes) {
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // Handle midnight (0 hours)
            minutes = minutes < 10 ? '0' + minutes : minutes;
            return hours + ':' + minutes + ' ' + ampm;
        }

        // If there is not time range then add new time range when that day status is enabled
        $('[name^="dslpfw_default_pickup_hours"], [name^="dslpfw_pickup_location_pickup_hours"]').change(function(){
            var $this = $(this);
            var value = $this.is(':checked');
            
            if (value) {
                var time_range_length = $this.closest('.dslpfw-time-range-group').find('.time-range-wrap').find('.time-range').length;
                if( time_range_length <= 0 ){
                    $this.closest('.dslpfw-time-range-group').find('.add-pickup-time-range').trigger('click');
                }
            }
        });

        //Add new time range slider
        $(document).on( 'click', '.add-pickup-time-range', function(e){
            e.preventDefault();

            var day_id = $(this).data('day-id');

            // Make close of time range
            var clone_ele = $('.time-range-clone .time-range').clone();
            var clone_start_name = clone_ele.find('.pickup_hours_start');
            var clone_end_name = clone_ele.find('.pickup_hours_end');
            clone_start_name.attr('name', clone_start_name.attr('name').replace('{day}', day_id));
            clone_end_name.attr('name', clone_end_name.attr('name').replace('{day}', day_id));

            // Append time range
            clone_ele.appendTo($(this).closest('.dslpfw-time-range-group').find('.time-range-wrap'));

            // Initialise time range
            clone_ele.find('.slider-range').slider({
                range: true,
                min: 0,
                max: 1440,
                step: 15,
                values: [540, 1020],
                slide: function (e, ui) {
                    updateTimeLabels($(this), ui.values);
                }
            });

            // Print default value
            var defaultValues = clone_ele.find('.slider-range').slider('values');
            updateTimeLabels(clone_ele.find('.slider-range'), defaultValues);

            var toggle_group = $(this).closest('.dslpfw-time-range-group').find('.dslpfw-toggle-group');
            if( ! $(this).closest('.dslpfw-time-range-group').find('.time-range-wrap').is(':visible') ){
                toggle_group.trigger('click');
                toggle_group.text('Collapse');
                toggle_group.removeClass('dslpfw-toggle-group-hide').addClass('dslpfw-toggle-group-show');
            }
        });

        //Remove selected time range slider
        $(document).on( 'click', '.delete-pickup-time-range', function(e){
            e.preventDefault();
            var parent = $(this).closest('.dslpfw-time-range-group');
            $(this).closest('.time-range').remove();
            if(parent.find('.time-range').length <= 0 ){
                parent.find('.time-range-wrap').hide();
                parent.find('.dslpfw-toggle-group').removeClass('dslpfw-toggle-group-show').addClass('dslpfw-toggle-group-hide');
            }
        });
        /** Time range slider end */

        if( $('.dslpfw-time-range-group').length > 0 ) {
            $('.dslpfw-time-range-group').each(function(){
                if($(this).find('.time-range-wrap').is(':visible')){
                    $('.dslpfw-toggle-group').text('Collapse');
                } else {
                    $('.dslpfw-toggle-group').text('Expand');
                }
            });
        }
        
        $('.dslpfw-toggle-group').click(function(e) {
            e.preventDefault();
            $(this).closest('.dslpfw-time-range-group').find('.time-range-wrap').toggle();
            if($(this).closest('.dslpfw-time-range-group').find('.time-range-wrap').is(':visible')){
                $(this).text('Collapse');
            } else {
                $(this).text('Expand');
            }
        });

        $('.datepicker').datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'yy-mm-dd',
            beforeShowDay: function(date) {
                var isSelected = false;
                var dates = $(this).siblings('.dslpfw-selected-dates-wrap').children().map(function() {
                    return $(this).find('.dslpfw-date-remove').data('date');
                }).get();
                $('#dslpfw_holiday_dates').val(dates.join('|'));    
                if( dates.length > 0 ){
                    dates = dates.map(function(x) {
                        return $.datepicker.parseDate('yy-mm-dd', x.trim()).getTime();
                    });
                    isSelected = dates.indexOf(date.getTime()) === -1 ? false : true;
                }
                
                return [true, isSelected ? 'dslpfw-selected' : ''];
            },
            onSelect: function(dateText) {
                
                var selected = [];
                
                // Retrieve array of data-date values
                var dates = $(this).siblings('.dslpfw-selected-dates-wrap').children().map(function() {
                    return $(this).find('.dslpfw-date-remove').data('date');
                }).get();
                
                // Convert them in timestamp format
                dates = dates.map(function(x){
                    return $.datepicker.parseDate('yy-mm-dd', x.trim()).getTime();
                });
                
                // Convert selected date in timestamp format
                var selectedD = $.datepicker.parseDate('yy-mm-dd',dateText).getTime();
                
                //This will add selected data 
                $.each(dates, function(index, value) {
                    if (value !== selectedD) {
                        selected.push(value);
                    }
                });
                
                // This will remove already selected date
                if (dates.indexOf(selectedD) === -1) {
                    selected.push(selectedD);
                }

                // Convert timestamp to date format
                selected = selected.map(function(x){ 
                    return $.datepicker.formatDate('yy-mm-dd', new Date(x));
                });

                // Prepare tag forshow selected dates
                var date_list = $(this).siblings('span.dslpfw-selected-dates-wrap');
                if( date_list.find('span').length > 0 ){
                    date_list.find('span').remove();
                }
                $('#dslpfw_holiday_dates').val(selected.join('|'));
                selected.forEach(function(myDate) {
                    var selected_dates_wrap = $('<span>', {
                        'class': 'dslpfw-selected-dates',
                    });
                    var date_span = $('<span>', {
                        'class': 'dslpfw-date',
                        'text': $.datepicker.formatDate($.datepicker._defaults.dateFormat, new Date(myDate))
                    });
                    var date_remove_span = $('<span>', {
                        'data-date': myDate,
                        'class': 'dslpfw-date-remove',
                        html: '<i class="dashicons dashicons-dismiss"></i>'
                    });
                    selected_dates_wrap.append(date_span, date_remove_span);
                    date_list.append(selected_dates_wrap);
                });
            }
        });

        // Manually trigger onSelect event when button is clicked
        $(document).on('click', '.dslpfw-selected-dates-wrap span.dslpfw-date-remove', function() {
            var specificDate = $(this).data('date'); 

            // Get the onSelect event handler function
            var onSelectHandler = $('.datepicker').datepicker('option', 'onSelect');
            
            // Call the onSelect event handler function directly with the specific date
            onSelectHandler.call($('.datepicker')[0], specificDate, { selected: true });

            //Refresh datepicker after every remove 
            $('.datepicker').datepicker('refresh');

        });

        /**
         * SelectWoo dropdown for product selection
         */
        $('.ds-product-search').filter(':not(.enhanced)').each(function() {
            var ds_select2 = $(this);
            ds_select2.selectWoo({
                placeholder: ds_select2.data( 'placeholder' ),
                allowClear: ds_select2.data( 'allow_clear' ) ? true : false,
                minimumInputLength: ds_select2.data( 'minimum_input_length' ) ? ds_select2.data( 'minimum_input_length' ) : '3',
                ajax: {
                    url: dslpfw_vars.ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    cache: true,
                    data: function(params) {
                        return {
                            search          : params.term,
                            action          : ds_select2.data( 'action' ) || 'dslpfw_json_search_products',
                            display_pid     : ds_select2.data( 'display_id' ) ? true : false,
                            security        : dslpfw_vars.dslpfw_product_search_nonce,
                            posts_per_page  : dslpfw_vars.select2_per_product_ajax,
                            offset          : params.page || 1,
                        };
                    },
                    processResults: function( data ) {
                        var terms = [];
                        if ( data ) {
                            $.each( data, function( id, text ) {
                                terms.push( { id: id, text: text } );
                            });
                        }
                        var pagination = terms.length > 0 && terms.length >= dslpfw_vars.select2_per_product_ajax ? true : false;
                        return {
                            results: terms,
                            pagination: {
                                more : pagination
                            } 
                        };
                    }
                }
            });
        });

        /** Upgrade Dashboard Script START */
        // Dashboard features popup script
        $(document).on('click', '.dotstore-upgrade-dashboard .premium-key-fetures .premium-feature-popup', function (event) {
            let $trigger = $('.feature-explanation-popup, .feature-explanation-popup *');
            if(!$trigger.is(event.target) && $trigger.has(event.target).length === 0){
                $('.feature-explanation-popup-main').not($(this).find('.feature-explanation-popup-main')).hide();
                $(this).parents('li').find('.feature-explanation-popup-main').show();
                $('body').addClass('feature-explanation-popup-visible');
            }
        });
        $(document).on('click', '.dotstore-upgrade-dashboard .popup-close-btn', function () {
            $(this).parents('.feature-explanation-popup-main').hide();
            $('body').removeClass('feature-explanation-popup-visible');
        });
        /** Upgrade Dashboard Script End */

        // Script for Beacon configuration
        var helpBeaconCookie = getCookie( 'dslpfw-help-beacon-hide' );
        if ( ! helpBeaconCookie ) {
            Beacon('init', 'afe1c188-3c3b-4c5f-9dbd-87329301c920');
            Beacon('config', {
                display: {
                    style: 'icon',
                    iconImage: 'message',
                    zIndex: '99999'
                }
            });

            // Add plugin articles IDs to display in beacon
            Beacon('suggest', ['659d2f373afe0a1c1e4d13bd', '66322371c3d8e87cfb53c098', '66322b970d8e3e7142b0ed81', '6632267f0d8e3e7142b0ed68', '663224444c3ddc1d4e7a2752']);

            // Add custom close icon form beacon
            setTimeout(function() {
                if ( $( '.hsds-beacon .BeaconFabButtonFrame' ).length > 0 ) {
                    let newElement = document.createElement('span');
                    newElement.classList.add('dashicons', 'dashicons-no-alt', 'dots-beacon-close');
                    let container = document.getElementsByClassName('BeaconFabButtonFrame');
                    container[0].appendChild( newElement );
                }
            }, 3000);

            // Hide beacon
            $(document).on('click', '.dots-beacon-close', function(){
                Beacon('destroy');
                setCookie( 'dslpfw-help-beacon-hide' , 'true', 24 * 60 );
            });
        }

        /** Script for Freemius upgrade popup */
        $(document).on('click', '#dotsstoremain .dslpfw-pro-label, .dslpfw-upgrade-to-unlock .form-table', function(){
            $('body').addClass('dslpfw-modal-visible');
        });

        $(document).on('click', '#dotsstoremain .modal-close-btn', function(){
            $('body').removeClass('dslpfw-modal-visible');
        });
        $(document).on('click', '.dots-header .dots-upgrade-btn, .dotstore-upgrade-dashboard .upgrade-now', function(e){
            e.preventDefault();
            upgradeToProFreemius( '' );
        });
        $(document).on('click', '.upgrade-to-pro-modal-main .upgrade-now', function(e){
            e.preventDefault();
            $('body').removeClass('dslpfw-modal-visible');
            let couponCode = $('.upgrade-to-pro-discount-code').val();
            upgradeToProFreemius( couponCode );
        });
    });

    // Set cookies
    function setCookie(name, value, minutes) {
        var expires = '';
        if (minutes) {
            var date = new Date();
            date.setTime(date.getTime() + (minutes * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '') + expires + '; path=/';
    }

    // Get cookies
    function getCookie(name) {
        let nameEQ = name + '=';
        let ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        return null;
    }

    /** Script for Freemius upgrade popup */
    function upgradeToProFreemius( couponCode ) {
        let handler;
        handler = FS.Checkout.configure({
            plugin_id: '14688',
            plan_id: '24499',
            public_key:'pk_9edf804dccd14eabfd00ff503acaf',
            image: 'https://www.thedotstore.com/wp-content/uploads/sites/1417/2024/04/Local-Pickup-Banner-Main-Banner.png',
            coupon: couponCode,
        });
        handler.open({
            name: 'Local Pickup for WooCommerce',
            subtitle: 'You’re a step closer to our Pro features',
            licenses: jQuery('input[name="licence"]:checked').val(),
            purchaseCompleted: function( response ) {
                console.log (response);
            },
            success: function (response) {
                console.log (response);
            }
        });
    }

    /**
     * Provide which element action need to show/hide which element as result 
     * Note: maintain prefix show-* with resultElement to work on HTML and pass here without it
     * 
     * @param {class/id name only} actionElement 
     * @param {class/id name only} resultElement 
     */
    function sectionToggle( actionElement, resultElement ) {
        var choose_location = $('.'+actionElement).val();
        if( resultElement === choose_location ) {
            $('.show-'+resultElement).show();
        } else {
            $('.show-'+resultElement).hide();
        }
    }

    /**
     * Provide which element action need to show/hide which element as result
     * This is only for appointment section 
     * 
     * @param {name only} field_name 
     */
    function toggle_appointment_fields( field_name ) {
        var field_status = $('#'+field_name+'_status:checked').is(':checked');
        if( field_status ) {
            $('.'+field_name+'_wrap').show();
        } else {
            $('.'+field_name+'_wrap').hide();
        }
        $('#'+field_name+'_status').change(function() {
            var field_status = $(this).is(':checked');
            if( field_status ) {
                $('.'+field_name+'_wrap').show();
            } else {
                $('.'+field_name+'_wrap').hide();
            }
        });
    }

    function toggleType(){
        $('.dslpfw-toggle-slider').each(function(){
            let parent_node = $(this).parent().parent();
            parent_node.find('.dslpfw-type').removeClass('active');
            let span = $('<span/>').addClass('dslpfw-input-group-text').text('%');
            if( $(this).prop('checked') ){
                //Right select
                parent_node.find('.dslpfw-right-type').addClass('active');
                parent_node.parent().find('.dslpfw-input-group').append(span);
            } else {
                //Left select
                parent_node.find('.dslpfw-left-type').addClass('active');
                parent_node.parent().find('.dslpfw-input-group').find('span').remove();
            }
        });
    }
    
})( jQuery );
