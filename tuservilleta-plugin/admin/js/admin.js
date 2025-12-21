/**
 * TuServilleta Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Media Uploader
        var mediaUploader;

        $('.tuservilleta-upload-btn').on('click', function(e) {
            e.preventDefault();

            var button = $(this);
            var targetId = button.data('target');

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Seleccionar imagen',
                button: {
                    text: 'Usar esta imagen'
                },
                multiple: false
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#' + targetId).val(attachment.url);
                $('#preview_' + targetId).html('<img src="' + attachment.url + '" alt="">');
                button.siblings('.tuservilleta-remove-btn').show();
            });

            mediaUploader.open();
        });

        // Remove Image
        $('.tuservilleta-remove-btn').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var targetId = button.data('target');

            $('#' + targetId).val('');
            $('#preview_' + targetId).html('<span class="dashicons dashicons-format-image"></span>');
            button.hide();
        });

        // CSV Upload
        $('#tuservilleta_upload_csv').on('click', function(e) {
            e.preventDefault();

            var fileInput = $('#tuservilleta_csv_file')[0];
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Por favor, selecciona un archivo CSV');
                return;
            }

            var formData = new FormData();
            formData.append('action', 'tuservilleta_upload_csv');
            formData.append('nonce', tuservilleta_admin.upload_nonce);
            formData.append('csv_file', fileInput.files[0]);

            var button = $(this);
            button.prop('disabled', true).text('Importando...');

            $.ajax({
                url: tuservilleta_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    var resultDiv = $('#tuservilleta_upload_result');
                    if (response.success) {
                        resultDiv.removeClass('error').addClass('success').text(response.data).show();
                        // Update product count
                        location.reload();
                    } else {
                        resultDiv.removeClass('success').addClass('error').text(response.data).show();
                    }
                },
                error: function() {
                    $('#tuservilleta_upload_result')
                        .removeClass('success')
                        .addClass('error')
                        .text('Error al procesar la solicitud')
                        .show();
                },
                complete: function() {
                    button.prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Importar CSV');
                }
            });
        });
    });

})(jQuery);
