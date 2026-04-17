<?php
class WMAIW_Plugin {

    public function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once WMAIW_PLUGIN_DIR . 'includes/class-settings.php';
        require_once WMAIW_PLUGIN_DIR . 'includes/class-api-manager.php';
        require_once WMAIW_PLUGIN_DIR . 'includes/class-content-generator.php';
        require_once WMAIW_PLUGIN_DIR . 'includes/class-editor-integration.php';
    }

    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 999);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        new WMAIW_Editor_Integration();
    }

    public function add_admin_menu() {
        if (!class_exists('WMAIW_Settings')) {
            require_once WMAIW_PLUGIN_DIR . 'includes/class-settings.php';
        }
        if (class_exists('WMAIW_Settings')) {
            new WMAIW_Settings();
        }
    }

    public function enqueue_scripts($hook) {
        return;
    }

    public function register_rest_routes() {
        // 同步生成文章
        register_rest_route('wmaiw/v1', '/generate', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_sync_generate'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ));

        // 测试 API Key（用于设置页面）
        register_rest_route('wmaiw/v1', '/test', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_test_api'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));
    }

    public function handle_sync_generate($request) {
        $params = $request->get_json_params();
        $topic  = sanitize_text_field($params['topic'] ?? '');
        $model  = sanitize_text_field($params['model'] ?? get_option('wmaiw_settings')['default_model']);
        $style  = sanitize_text_field($params['style'] ?? 'professional');
        $length = intval($params['length'] ?? 500);

        if (empty($topic)) {
            return new WP_Error('no_topic', '请提供文章主题', array('status' => 400));
        }

        $api_manager = new WMAIW_API_Manager();
        $generator   = new WMAIW_Content_Generator($api_manager);
        $result      = $generator->generate_article($topic, $model, $style, $length);

        if (is_wp_error($result)) {
            return $result;
        }
        return rest_ensure_response($result);
    }

    public function handle_test_api($request) {
        $params = $request->get_json_params();
        $model   = sanitize_text_field($params['model'] ?? '');
        $api_key = sanitize_text_field($params['api_key'] ?? '');

        if (empty($model) || empty($api_key)) {
            return new WP_Error('missing_params', '模型或 API Key 为空', array('status' => 400));
        }

        $class_name = 'WMAIW_' . ucfirst($model);
        if (!class_exists($class_name)) {
            $file_path = WMAIW_PLUGIN_DIR . "api-providers/class-{$model}.php";
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                return new WP_Error('class_not_found', "模型 {$model} 的 Provider 文件不存在", array('status' => 500));
            }
        }

        if (!class_exists($class_name)) {
            return new WP_Error('class_not_found', "模型 {$model} 的 Provider 类不存在", array('status' => 500));
        }

        $provider = new $class_name($api_key);
        $test_prompt = '请回复“API测试成功”四个字，不要有其他内容。';
        $result = $provider->generate($test_prompt, array('max_tokens' => 20));

        if (is_wp_error($result)) {
            return $result;
        } else {
            if (strpos($result, 'API测试成功') !== false || strpos($result, '测试成功') !== false) {
                return array('success' => true, 'message' => 'API 密钥有效！');
            } else {
                return array('success' => true, 'message' => 'API 响应正常，但返回内容不符预期，请手动检查。');
            }
        }
    }
}