(function( $ ) {
	'use strict';

	/**
	 * All of the code for admin-facing Import Export JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 */

    $(document).ready(function(){
        //Toggle button activate import-export type
        toggleIEType();
        $('.dslpfw-ie-type').change(function () {
            toggleIEType(this);
        });

        // Export AJAX Call
        $('#dslpfw_export_settings').click(function(){
            var action = $('input[name="dslpfw_export_action"]').val();
            var security = $('input[name="dslpfw_export_action_nonce"]').val();
            var type = $('#dslpfw_export_type').prop('checked') ? 'csv' : 'json';
            var $this = $(this);
            $('.dslpfw-import-export-section').block({
                message: null,
                overlayCSS: {
                    background: 'rgb(255, 255, 255)',
                    opacity: 0.6,
                },
            });
            if( action && security ){
                $this.attr('disabled','disabled');
                $.ajax({
                    type: 'POST',
                    url: dslpfw_import_export_vars.ajaxurl,
                    data: {
                        'action': action,
                        'security': security,
                        'export_type': type
                    },
                    success: function( response ){
                        $('.dslpfw-import-export-section').unblock();
                        var div_wrap = $('<div></div>').addClass('notice');
                        var p_text = $('<p></p>').text(response.data.message);
                        if( response.success ){
                            div_wrap.addClass('notice-success');
                        } else {
                            div_wrap.addClass('notice-error');
                        }
                        div_wrap.append(p_text);
                        $(div_wrap).insertAfter($('.wp-header-end'));

                        //download link generation
                        if( response.data.download_path ){
                            var link = document.createElement('a');
                            link.href = response.data.download_path;
                            link.download = '';
                            document.body.appendChild(link);
                            link.click();
                        }
                        setTimeout(function(){
                            div_wrap.remove();
                            $this.attr('disabled', null);
                            link.remove();
                        }, 2000);
                    }
                });
            }
        });

        $('.dslpfw-import-file .label').text($('.dslpfw-import-file #import_file').data('placeholder')).css('opacity', '0.3');
        $('input[type="file"]').change(function (e) {
            const chosen_file = e.target.files[0].name;
            $('.dslpfw-import-file .label').text(chosen_file).css('opacity', '1');
        });

        // Import AJAX Call
        $('#dslpfw_import_setting').click(function(){
            
            var action = $('input[name="dslpfw_import_action"]').val();
            var security = $('input[name="dslpfw_import_action_nonce"]').val();
            var type = $('#dslpfw_import_type').prop('checked') ? 'csv' : 'json';

            // Check if a file has been selected
            var fileInput = $('input[name="import_file"]')[0];
            if (fileInput.files.length === 0) {
                $('.dslpfw-import-export-section').unblock();
                var div_wrap = $('<div></div>').addClass('notice');
                var p_text = $('<p></p>').text(dslpfw_import_export_vars.file_upload_msg.replace('{ext}', type.toUpperCase()));
                div_wrap.addClass('notice-error');
                div_wrap.append(p_text);
                $(div_wrap).insertAfter($('.wp-header-end'));
                return false;
            }

            var $this = $(this);
            $('.dslpfw-import-export-section').block({
                message: null,
                overlayCSS: {
                    background: 'rgb(255, 255, 255)',
                    opacity: 0.6,
                }
            });
            if( action && security ){
                $this.attr('disabled','disabled');
                var fd = new FormData();
                fd.append('import_file', $('input[name="import_file"]')[0].files[0]);  
                fd.append('action', action);
                fd.append('security', security);
                fd.append('import_type', type);
                $.ajax({
                    type: 'POST',
                    url: dslpfw_import_export_vars.ajaxurl,
                    data: fd,
                    contentType: false,
                    processData: false,
                    success: function( response ){
                        $('.dslpfw-import-export-section').unblock();
                        var div_wrap = $('<div></div>').addClass('notice');
                        var p_text = $('<p></p>').text(response.data.message);
                        if(response.success){
                            div_wrap.addClass('notice-success');
                            $('.dslpfw-import-file .label').text($('.dslpfw-import-file #import_file').data('placeholder')).css('opacity', '0.3');
                        } else {
                            div_wrap.addClass('notice-error');
                            $this.attr('disabled', null);
                        }
                        jQuery('input[name="import_file"]').val('');
                        div_wrap.append(p_text);
                        $(div_wrap).insertAfter($('.wp-header-end'));
                        setTimeout( function(){
                            div_wrap.remove();
                            $this.attr('disabled', null);
                        }, 3000 );
                    }
                });
            }
        });
	});

    function toggleIEType(e){
        if( 'undefined' === typeof e ){
            $('.dslpfw_toggle_container').each(function(){
                $(this).find('.dslpfw-type').removeClass('active');        
                $(this).find('.dslpfw-json-type').addClass('active');
            });
        } else {
            let parent_node = $(e).parent().parent();
            parent_node.find('.dslpfw-type').removeClass('active');
            if( $(e).prop('checked') ){
                //CSV
                parent_node.find('.dslpfw-csv-type').addClass('active');
            } else {
                //JSON
                parent_node.find('.dslpfw-json-type').addClass('active');
            }
        }
    }
})( jQuery );