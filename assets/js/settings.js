jQuery(document).ready(function($) {
    $('.wmaiw-test-api').on('click', function() {
        var $btn = $(this);
        var model = $btn.data('model');
        var $input = $btn.closest('.wmaiw-api-field').find('input[type="password"]');
        var apiKey = $input.val();
        var $resultSpan = $btn.siblings('.wmaiw-test-result');

        if (!apiKey) {
            $resultSpan.html('<span style="color:red;">❌ 请先填写 API Key</span>');
            return;
        }

        $btn.prop('disabled', true).text('测试中...');
        $resultSpan.html('');

        $.ajax({
            url: wmaiw_rest.root + 'wmaiw/v1/test',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-WP-Nonce': wmaiw_rest.nonce },
            data: JSON.stringify({ model: model, api_key: apiKey }),
            timeout: 30000,
            success: function(resp) {
                if (resp.success) {
                    $resultSpan.html('<span style="color:green;">✅ ' + resp.message + '</span>');
                } else {
                    $resultSpan.html('<span style="color:red;">❌ ' + (resp.message || '测试失败') + '</span>');
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.message || '网络错误';
                $resultSpan.html('<span style="color:red;">❌ ' + msg + '</span>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('测试 ' + model.charAt(0).toUpperCase() + model.slice(1));
            }
        });
    });
});