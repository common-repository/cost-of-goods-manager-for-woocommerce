jQuery(document).ready(function ($) {

    var formatRepo = function (repo) {
        if (repo.loading)
            return repo.text;

        var markup = '';
        markup += '<div class="select2-result-repository clearfix>';
        markup += '<div class="select2-result-repository__meta>';
        markup += '<div class="select2-result-repository__title">' + repo.first_name + ' ' + repo.last_name + '</div>';
        markup += '</div>';
        markup += '</div>';

        return markup;
    };

    var formatRepoSelection = function (repo) {
        if (repo.hasOwnProperty('first_name') && repo.hasOwnProperty('last_name'))
            return repo.first_name + ' ' + repo.last_name;
        else
            return repo.text;
    };

    $('a.zcostofgoods_cog_order_button').on('click', function (e) {
        e.preventDefault();
        if (confirm("Are you sure you want to do this? This action can not be undone!")) {
            let url = $(this).attr('href');
            let action = $(this).attr("data-action");
            let loading_screen = $('#zcostofgoods_loading_screen');
            loading_screen.css('display', 'flex');
            loading_screen.css('align-items', 'center');
            $.post({
                url: url,
                data: {
                    'action': action,
                },
                success: function (response) {
                    window.location = response;
                }
            })
        }
    });

    $('.zcostofgoods_wc_notice button.notice-dismiss').click(function () {
        let ajax_url = $('.zcostofgoods_wc_notice').attr('data-link');

        $.post({
            url: ajax_url,
            data: {
                'action': 'zcostofgoods_wc_notice_dismissed'
            },
            success: function () {
            }
        })
    });

});