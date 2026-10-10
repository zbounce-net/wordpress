jQuery(function($) {
    $('.zb-email-validator').each(function() {
        const $container = $(this), $input = $container.find('.zb-email-input'), $button = $container.find('.zb-validate-btn');
        const $status = $container.find('.zb-status-value'), $results = $container.find('.zb-validation-results');
        const strings = zbEmailValidator.strings;
        const statuses = 'zb-status-yes zb-status-no zb-status-warning zb-status-error zb-status-unknown';
        let pending = false;
        function badge(selector, flag, yes, no) {
            $container.find(selector).removeClass(statuses).text(flag === true ? yes : flag === false ? no : strings.unknown)
                .addClass(flag === true ? 'zb-status-yes' : flag === false ? 'zb-status-no' : 'zb-status-unknown');
        }
        function error(message) { $status.text(message).css('color', '#d93025').show(); }
        function submit(event) {
            if (event) event.preventDefault();
            if (pending || $input.prop('disabled')) return;
            if (!$input[0].checkValidity() || !$input.val().trim()) return error(strings.invalid_email);
            pending = true; $button.prop('disabled', true); $input.prop('disabled', true); $results.hide();
            $status.text(strings.checking).css('color', '').show();
            $.post(zbEmailValidator.ajax_url, {action:'zb_create_validation_task', security:zbEmailValidator.nonce, email:$input.val().trim(), use_cache:$container.find('.zb-use-cache').is(':checked') ? '1' : '0'}, null, 'json')
                .done(function(response) {
                    if (!response || !response.success || !response.data || !response.data.result) return error(response && response.data && response.data.message || strings.error);
                    const result = response.data.result;
                    if (response.data.balance_text) $('.zb-key-balance').text(response.data.balance_text);
                    $container.find('.zb-policy-status').text(response.data.status_label || strings.unknown);
                    $container.find('.zb-policy-decision').text(response.data.allowed === true ? strings.allowed : response.data.allowed === false ? strings.rejected : strings.unknown);
                    $container.find('.zb-result-source').text(response.data.from_cache ? strings.cached : strings.fresh);
                    $status.hide(); $results.show(); $container.find('.zb-result-email').text(result.email);
                    badge('.zb-validity-badge', result.valid, strings.valid, strings.invalid);
                    badge('.zb-exists-status', result.exists, strings.yes, strings.no);
                    badge('.zb-disposable-status', !result.disposable, strings.no, strings.yes);
                    $container.find('.zb-acceptall-status').text(result.accept_all ? strings.unreliable : strings.no)
                        .attr('title', result.accept_all ? strings.accepts_all : '').removeClass(statuses)
                        .addClass(result.accept_all ? 'zb-status-warning' : 'zb-status-yes');
                }).fail(function(response) { error(response.responseJSON && response.responseJSON.data && response.responseJSON.data.message || strings.error); })
                .always(function() { pending = false; $button.prop('disabled', false); $input.prop('disabled', false); });
        }
        $button.on('click', submit);
        $input.on('keydown', function(event) { if (event.key === 'Enter') submit(event); });
    });
});
