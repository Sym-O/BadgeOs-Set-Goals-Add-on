jQuery(function ($) {
    $('#badgeos-set-goals-send-emailing').click(function (e) {
        // we display the new buttons and the textarea  
        $('#badgeos-set-goals-send-emailing').hide();
        $('#goals_notify_cancel').show();
        $('#goals_notify_specified_users').show();
        $('#goals_list_users_to_notify').show();
        $('#goals_notify_all').show();
    });

    //default message in the textarea
    var defaultMessage = "Enter the list of emails you want to notify:";
    $("#goals_list_users_to_notify").focus(function () {
        if ($(this).val() == defaultMessage) {
            $(this).val("");
        }
    }).blur(function () {
        if ($(this).val() == "") {
            $(this).val(defaultMessage);
        }
    }).val(defaultMessage);

    //action for the cancel button
    $('#goals_notify_cancel').click(function (e) {
        $('#badgeos-set-goals-send-emailing').show();
        $('#goals_notify_cancel').hide();
        $('#goals_notify_specified_users').hide();
        $('#goals_list_users_to_notify').hide();
        $('#goals_notify_all').hide();
    });

    //action for the button 'send to users above'
    $('#goals_notify_specified_users').click(function (e) {
        var textarea = $('#goals_list_users_to_notify');
        //we parse the textarea, the delimeters are '\n' and ' '
        var res = textarea.val().split(new RegExp('[ \n]+', 'g'));
        if (res[res.length - 1] == "") {
            res.splice(res.length - 1, 1);
        }

        hide_fields();
        //we show the div which will contain the ajax response
        $('#goals_notify_output').show();
        send_emailing(e, res);
    });

    //action for the button 'send to all'
    $('#goals_notify_all').click(function (e) {
        hide_fields();
        $('#goals_notify_output').show();
        send_emailing(e, null);
    });

    function hide_fields() {
        $('#goals_notify_cancel').hide();
        $('#goals_notify_specified_users').hide();
        $('#goals_list_users_to_notify').hide();
        $('#goals_notify_all').hide();
    }

    //action for the buttons to see the emails sent and not sent
    function hide_or_show_span_brother() {
        var list_of_emails = $(this).parent().children('span');
        if (list_of_emails.is(":visible")) {
            list_of_emails.hide();
        } else {
            list_of_emails.show();
        }
    }

    //Add of the loading text and the loading picture, they are hidden by default
    var img = $('#goals_notify_loading_image').detach().show();
    $('#goals_notify_output').append('<div class=\'goals_notify_loading_text\'><br/></div>');
    $('.goals_notify_loading_text').append(img).append('<span class="load_text"></span>').hide();

    var offset = 25;//number of emails to send per batch

    function send_emailing(e, array_of_emails) {
        // Unbind event to avoid multiple emailing issue if click again
        $('#badgeos-set-goals-send-emailing').unbind();
        var to_send;
        if (array_of_emails === null) {
            to_send = {
                'action': 'send_emailing',
            };
        } else {
            to_send = {
                'action': 'send_emailing',
                'array_of_emails': array_of_emails,
            };
        }
        var index = 0;
        send_mails_recursive(to_send, index);
    }

    var count = 1;//current batch number
    function send_mails_recursive(to_send, current_index) {
        $('.goals_notify_loading_text > .load_text').text("sending emails ...");
        $('.goals_notify_loading_text').show();
        to_send.current_index = current_index;
        to_send.frequency = offset;
        $.ajax({
            url: badgeos_set_goals.ajax_url,
            data: to_send,
            dataType: 'json',
            success: function (response) {
                var load_elem = $('.goals_notify_loading_text').hide().detach();

                //we show the status of the ajax response and the sent or not sent emails
                $('#goals_notify_output').append('<br/><strong>email batch n°' + count + ' : ' + response.type + '</strong><br/>' +
                        '<div id=\'goals_sent_mails_' + count + '\'><br/> <input class=\'button\' ' +
                        'style=\'width: 29%;\' value=\'click to see successfully notified users\' />'
                        + '<span style="display:none;">' + response.successfully_sent_emails + '</span></div>');
                $('#goals_sent_mails_' + count + ' input').click(hide_or_show_span_brother);

                if (response.type != "success") {
                    $('#goals_notify_output').append('<div id=\'goals_not_sent_mails_' + count + '\'><br/>'+ 
                            ' <input class=\'button\' style=\'width: 32%;\' value=\'click to see unnotified users due to errors\' />'
                            + '<span style="display:none;">' + response.not_sent_emails + '</span></div>');

                    $('#goals_not_sent_mails_' + count + ' input').click(hide_or_show_span_brother);
                }
                $('#goals_notify_output').append(load_elem);
                count++;

                //we recursively continue sending ajax request while the server tells us to continue
                if (response._continue) {
                    $('.goals_notify_loading_text > .load_text').text("waiting 20s before sending emails ...");
                    $('.goals_notify_loading_text').show();
                    setTimeout(function () {
                        send_mails_recursive(to_send, response.next_index);
                    }, 20000);
                }
            },
            error: function (response) {
                //in case of unknown error
                $('.goals_notify_loading_text').hide();
                $('#goals_notify_output').append('<br/><strong>request n°' + count + ' : external error occured' + '</strong><br/>' +
                        response.output);
            }
        });

    }
});
