jQuery(document).ready(function($) {
    // 初始化模态框
    $('#wmaiw-modal').dialog({
        autoOpen: false,
        modal: true,
        width: 550,
        closeOnEscape: true,
        open: function() {
            $('#wmaiw-generate-status').html('');
            $('#wmaiw-topic').val('');
            $('.spinner').hide();
        }
    });

    // 打开模态框
    $('#wmaiw-generate-btn').on('click', function() {
        $('#wmaiw-modal').dialog('open');
    });

    // 生成文章
    $('#wmaiw-do-generate').on('click', function() {
        var topic = $('#wmaiw-topic').val().trim();
        if (!topic) {
            alert('请输入文章主题');
            return;
        }
        var model = $('#wmaiw-model').val();
        var style = $('#wmaiw-style').val();
        var length = parseInt($('#wmaiw-length').val());

        var $btn = $(this);
        var $spinner = $('.spinner');
        var $status = $('#wmaiw-generate-status');

        $btn.prop('disabled', true);
        $spinner.show();
        $status.html('生成中，请稍候...');

        $.ajax({
            url: wmaiw_rest.root + 'wmaiw/v1/generate',
            method: 'POST',
            contentType: 'application/json',
            headers: {
                'X-WP-Nonce': wmaiw_rest.nonce
            },
            data: JSON.stringify({
                topic: topic,
                model: model,
                style: style,
                length: length
            }),
            timeout: 90000,
            success: function(resp) {
                console.log('生成成功', resp);
                if (resp.content) {
                    var inserted = false;
                    
                    // 优先使用 TinyMCE（经典编辑器可视化模式）
                    if (typeof window.tinyMCE !== 'undefined' && window.tinyMCE.get('content')) {
                        var editor = window.tinyMCE.get('content');
                        if (editor) {
                            editor.focus();
                            editor.selection.setContent(resp.content);
                            inserted = true;
                            console.log('通过 TinyMCE 插入内容');
                        }
                    }
                    
                    // 回退到 textarea（文本模式）
                    if (!inserted) {
                        var textarea = document.getElementById('content');
                        if (textarea) {
                            var start = textarea.selectionStart;
                            var end = textarea.selectionEnd;
                            var content = textarea.value;
                            var newContent = content.substring(0, start) + resp.content + content.substring(end);
                            textarea.value = newContent;
                            $(textarea).trigger('change');
                            inserted = true;
                            console.log('通过 textarea 插入内容');
                        }
                    }
                    
                    if (inserted) {
                        // 自动填充标题（如果标题字段为空）
                        if (resp.title && resp.title.trim() !== '') {
                            var titleField = document.getElementById('title');
                            if (titleField && !titleField.value) {
                                titleField.value = resp.title;
                                $(titleField).trigger('change');
                            }
                        }
                        $status.html('✅ 文章已生成并插入编辑器');
                    } else {
                        console.error('未找到编辑器元素');
                        $status.html('❌ 未找到编辑器，请刷新页面重试');
                    }
                } else {
                    $status.html('❌ 生成失败：返回内容为空');
                }
                setTimeout(function() {
                    $status.fadeOut();
                    $('#wmaiw-modal').dialog('close');
                }, 2000);
            },
            error: function(xhr, status, error) {
                console.error('生成失败', xhr);
                var errMsg = xhr.responseJSON?.message || error || '未知错误';
                $status.html('❌ 生成失败：' + errMsg);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.hide();
            }
        });
    });
});