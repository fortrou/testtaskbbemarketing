jQuery(document).ready(function($) {
    $(document).on('click', '.edit-stylists.edit', function(e) {
        e.preventDefault();
        $(this).removeClass('edit').addClass('cancel').text('Cancel editing');

        $('.stylist-data tbody tr').each(function() {
            $(this).find('input').val($(this).find('.value-item').text().trim());
            $(this).find('.value-item').hide();
            $('button.save-stylist').show();
            $(this).find('.value-input').show().attr('readonly', false);
        });
    });

    $(document).on('click', '.edit-stylists.cancel', function(e) {
        e.preventDefault();
        $(this).removeClass('cancel').addClass('edit').text('Edit stylist');

        $('.stylist-data tbody tr').each(function() {
            $(this).find('.value-item').show();
            $('button.save-stylist').hide();
            $(this).find('.value-input').hide().attr('readonly', true);
        });
    });

    $(document).on('click', 'button.save-stylist', function(e) {
        e.preventDefault();
        var stylistData = {};
        $('.stylist-data tbody tr').each(function() {
            var input = $(this).find('.value-input');
            var name = input.attr('name');
            var value = input.val().trim();
            stylistData[name] = value;
        });

        $.ajax({
            url: moda_ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'moda_save_stylist_details',
                stylist_id: $('input[name="stylist_id"]').val(),
                stylist_data: stylistData,
                security: moda_ajax_object.nonce,
                _ajax_nonce: moda_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Stylist details saved successfully.');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An unexpected error occurred.');
            }
        });
    });


    var searchTimer = null;

    $(document).on('input', 'input[name = search_celebrity]', function() {
        var query = $(this).val().trim();
        if (searchTimer) {
            clearTimeout(searchTimer);
        }
        searchTimer = setTimeout(function() {
            $.ajax({
                url: moda_ajax_object.ajax_url,
                method: 'POST',
                data: {
                    action: 'moda_search_celebrities',
                    stylist_id: $('input[name="stylist_id"]').val(),
                    search: query,
                    _ajax_nonce: moda_ajax_object.nonce
                },
                success: function(response) {
                    if (!response || !response.success) {
                        return;
                    }
                    var select = $('select[name="celebrity_id"]');
                    if (!select.length) {
                        return;
                    }
                    var options = '<option value="">Select celebrity</option>';
                    $.each(response.data, function(_, celeb) {
                        options += '<option value="' + celeb.id + '">' + celeb.full_name + '</option>';
                    });
                    select.html(options);
                }
            });
        }, 300);
    });
});
