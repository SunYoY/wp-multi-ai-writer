<?php
/**
 * AJAX 请求处理器
 */
class WMAIW_AJAX_Handler {

    /**
     * 生成文章（供编辑器调用）
     */
    public function handle_generate() {
        check_ajax_referer('wmaiw_generate_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('无权限');
        }

        $topic = sanitize_text_field($_POST['topic'] ?? '');
        $model = sanitize_text_field($_POST['model'] ?? get_option('wmaiw_settings')['default_model']);
        $style = sanitize_text_field($_POST['style'] ?? 'professional');
        $length = intval($_POST['length'] ?? 1500);

        if (empty($topic)) {
            wp_send_json_error('请提供文章主题');
        }

        $api_manager = new WMAIW_API_Manager();
        $generator   = new WMAIW_Content_Generator($api_manager);
        $result      = $generator->generate_article($topic, $model, $style, $length);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * 测试 API Key 是否有效
     */
    public function handle_test_api() {
        check_ajax_referer('wmaiw_test_api_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('无权限');
        }

        $model = sanitize_text_field($_POST['model'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($model) || empty($api_key)) {
            wp_send_json_error('模型或 API Key 为空');
        }

        // 动态加载对应的 Provider 类
        $class_name = 'WMAIW_' . ucfirst($model);
        if (!class_exists($class_name)) {
            $file_path = WMAIW_PLUGIN_DIR . "api-providers/class-{$model}.php";
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                wp_send_json_error("模型 {$model} 的 Provider 文件不存在");
            }
        }

        if (!class_exists($class_name)) {
            wp_send_json_error("模型 {$model} 的 Provider 类不存在");
        }

        $provider = new $class_name($api_key);
        $test_prompt = '请回复“API测试成功”四个字，不要有其他内容。';
        $result = $provider->generate($test_prompt, array('max_tokens' => 20));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            if (strpos($result, 'API测试成功') !== false || strpos($result, '测试成功') !== false) {
                wp_send_json_success('API 密钥有效！');
            } else {
                wp_send_json_success('API 响应正常，但返回内容不符预期，请手动检查。');
            }
        }
    }
}