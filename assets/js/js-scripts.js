jQuery(document).ready(function ($) {

    /*preview email*/
    $('.preview-emails-html-overlay').on('click', function () {
        $('.preview-emails-html-container').addClass('preview-html-hidden');
    });
    $('.wce-preview-emails-button').on('click', function () {
        $(this).html('Please wait...');
		let _post_id = $(".postid").val();
        $.ajax({
            url: WC_CART.adminUrl,
            type: 'GET',
            dataType: 'JSON',
            data: {
                action: 'wce_preview_emails',
				        post_id: _post_id,
                subject: $('#SCR_reminder_subject').val(),
                heading: $('#wcce_email_from').val(),
                btnUrl: $('#wce_btn_settings').find('.wce_btn_link').val(),
                btnText: $('#wce_btn_settings').find('.wce_btn_text').val(),
                btnColor: $("input[name='wce_btn_background']").val(),
                CpBgColor: $("input[name='wce_cart_background']").val(),
                CpTextColor: $("input[name='wce_text_color']").val(),
                content: tinymce.activeEditor.getContent(),

            },
            success: function (response) {
                $('.wce-preview-emails-button').html('Preview emails');
                if (response) {
                    $('.preview-emails-html').html(response.html);
                    $('.preview-emails-html-container').removeClass('preview-html-hidden');
                }
            },
            error: function (err) {
                $('.wce-preview-emails-button').html('Preview emails');
            }
        })
    });
    // Change Status
    $('.wce_enable_status').on('change', function () {
        let _checked = 'on';
        let _post_id = $(this).data('post_id');
        let self = $(this);
        self.closest('tr').addClass('loading').css('cursor', 'not-allowed');
        if (!$(this).is(":checked")) {
            _checked = '';
        }
        $.ajax({
            url: WC_CART.adminUrl,
            type: 'POST',
            data: {
                action: 'wce_change_status_via_ajax',
                post_id: _post_id,
                checked: _checked,
            },
            success: function (res) {
                self.closest('tr').removeClass('loading').css('cursor', 'inherit');
                console.log('Success')
            },
        })
    });

    $('#wcce_btn').on('click', function () {

        let _checked = 'on';
        let _post_id = $(".postid").val();
        let self = $(this);
        let email_from = $(".wcce_email_from").val();
        console.log(_post_id)

        $.ajax({
            url: WC_CART.adminUrl,
            type: 'POST',
            data: {
                action: 'wccs_save_emailfrom_via_ajax',
                post_id: _post_id,
                wcce_email_from: email_from,
            },
            success: function (res) {
                self.closest('tr').removeClass('loading').css('cursor', 'inherit');
				            alert(res.data);

            },
        })
    });

  $('#btn_wcce_testemail').on('click', function () {
      console.log("click")

        let _post_id = $(".postid").val();
        let self = $(this);
        let testemail = $(".wcce_testemail").val();

        console.log(_post_id)
        console.log(testemail)
        console.log(WC_CART.adminUrl)
        $.ajax({
            url: WC_CART.adminUrl,
            type: 'POST',
            data: {
                action: 'wccs_send_test_email_via_ajax',
                post_id: _post_id,
                wcce_testemail: testemail
            },
            success: function (res) {
                    self.closest('tr').removeClass('loading').css('cursor', 'inherit');
				            alert(res.data);
                    console.log('Success',res)
            },
        })
    });



    // Change Status
    $('.wccs_enable_status').on('change', function () {
        let _checked = 'on';
        let _post_id = $(this).data('post_id');
        let self = $(this);
        self.closest('tr').addClass('loading').css('cursor', 'not-allowed');
        if (!$(this).is(":checked")) {
            _checked = '';
        }
        $.ajax({
            url: WC_CART.adminUrl,
            type: 'POST',
            data: {
                action: 'wccs_change_status_via_ajax',
                post_id: _post_id,
                checked: _checked,
            },
            success: function (res) {
                self.closest('tr').removeClass('loading').css('cursor', 'inherit');
                console.log('Success')
            },
        })
    });
    // Change Status Send Mail
    $('.wce_send_enable_status').on('change', function () {
        let _checked = 'on';
        let self = $(this);
        self.closest('tr').addClass('loading').css('cursor', 'not-allowed');
        let _post_id = $(this).data('post_id');
        if (!$(this).is(":checked")) {
            _checked = '';
        }
        $.ajax({
            url: WC_CART.adminUrl,
            type: 'POST',
            data: {
                action: 'wce_change_schedule_send_status_via_ajax',
                post_id: _post_id,
                checked: _checked,
            },
            success: function (res) {
                self.closest('tr').removeClass('loading').css('cursor', 'inherit');
                console.log('Success')
            },
        })
    });

    // Resend Email
    $('.wce-resend-email').on('click', function (e) {
        e.preventDefault();
        let self = $(this);
        let _key = $(this).data('key');
        self.closest('tr').addClass('loading').css('cursor', 'not-allowed');
        self.closest('tr').find('td.column-status').text('Sending');
        $.ajax({
            url: WC_CART.adminUrl,
            type: 'POST',
            data: {
                action: 'wce_resend_email_via_ajax',
                key: _key,
                security: WC_CART.security
            },
            success: function (res) {
                if (res.success) {
                    self.closest('tr').removeClass('loading').css('cursor', 'inherit');
                    self.closest('tr').find('td.column-status').text('Sent');
                    self.text('Resend');
                }
            },
        })

    });

    // Syn Schedule
    $('.wce-syn-scheduled').on('click',function (e) {
        $.ajax({
            url: WC_CART.adminUrl,
            type: 'POST',
            data: {
                action: 'wce_syn_scheduled',
            },
            success: function (response) {
                console.log("ok")
                console.log(response)
            },
            error: function (err) {
                console.log(err)
            }
        })



    });
});
