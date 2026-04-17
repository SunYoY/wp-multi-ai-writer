<?php
class WMAIW_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 999);
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_settings_assets'));
    }

    public function register_menu() {
        add_menu_page(
            'WP Multi AI Writer 设置',
            'AI Writer',
            'manage_options',
            'wmaiw-settings',
            array($this, 'render_page'),
            'dashicons-edit',
            30
        );
    }

    public function enqueue_settings_assets($hook) {
        if ($hook !== 'toplevel_page_wmaiw-settings') return;
        wp_enqueue_style('wmaiw-settings', WMAIW_PLUGIN_URL . 'assets/css/admin-style.css', array(), WMAIW_VERSION);
        wp_enqueue_script('wmaiw-settings', WMAIW_PLUGIN_URL . 'assets/js/settings.js', array('jquery'), WMAIW_VERSION, true);
        wp_localize_script('wmaiw-settings', 'wmaiw_rest', array(
            'root'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    public function init_settings() {
        register_setting('wmaiw_settings_group', 'wmaiw_settings');
        add_settings_section('wmaiw_general', '⚙️ 通用设置', null, 'wmaiw-settings');
        add_settings_field('default_model', '默认模型', array($this, 'field_default_model'), 'wmaiw-settings', 'wmaiw_general');
        add_settings_field('timeout', '请求超时（秒）', array($this, 'field_timeout'), 'wmaiw-settings', 'wmaiw_general');
        add_settings_field('max_tokens', '最大 Token 数', array($this, 'field_max_tokens'), 'wmaiw-settings', 'wmaiw_general');
        add_settings_field('temperature', '创意程度 (0-1)', array($this, 'field_temperature'), 'wmaiw-settings', 'wmaiw_general');
        add_settings_section('wmaiw_models', '🔑 模型 API 配置', null, 'wmaiw-settings');
        foreach (['deepseek', 'qwen', 'doubao'] as $model) {
            add_settings_field("api_key_{$model}", ucfirst($model) . ' API Key', function() use ($model) {
                $this->field_api_key_with_test($model);
            }, 'wmaiw-settings', 'wmaiw_models');
        }
    }

    public function field_default_model() {
        $options = get_option('wmaiw_settings');
        $value   = $options['default_model'] ?? 'deepseek';
        echo '<select name="wmaiw_settings[default_model]"><option value="deepseek" ' . selected($value, 'deepseek', false) . '>DeepSeek</option><option value="qwen" ' . selected($value, 'qwen', false) . '>通义千问</option><option value="doubao" ' . selected($value, 'doubao', false) . '>豆包</option></select>';
    }

    public function field_timeout() {
        $options = get_option('wmaiw_settings');
        $value   = $options['timeout'] ?? 30;
        echo "<input type='number' name='wmaiw_settings[timeout]' value='$value' min='5' max='120' class='small-text' /> 秒";
    }

    public function field_max_tokens() {
        $options = get_option('wmaiw_settings');
        $value   = $options['max_tokens'] ?? 2000;
        echo "<input type='number' name='wmaiw_settings[max_tokens]' value='$value' min='100' max='8000' class='small-text' />";
    }

    public function field_temperature() {
        $options = get_option('wmaiw_settings');
        $value   = $options['temperature'] ?? 0.7;
        echo "<input type='range' name='wmaiw_settings[temperature]' value='$value' min='0' max='1' step='0.01' />";
        echo "<span id='temperature_display' style='margin-left:10px;'>$value</span>";
        echo "<script>document.querySelector('[name=\"wmaiw_settings[temperature]\"]').addEventListener('input',function(){document.getElementById('temperature_display').innerText=this.value;})</script>";
    }

    public function field_api_key_with_test($model) {
        $options = get_option('wmaiw_settings');
        $key     = $options["api_key_{$model}"] ?? '';
        $model_name = ucfirst($model);
        ?>
        <div class="wmaiw-api-field">
            <input type="password" name="wmaiw_settings[api_key_<?php echo $model; ?>]" value="<?php echo esc_attr($key); ?>" class="regular-text" autocomplete="off" />
            <button type="button" class="button button-secondary wmaiw-test-api" data-model="<?php echo $model; ?>">测试 <?php echo $model_name; ?></button>
            <span class="wmaiw-test-result" data-model="<?php echo $model; ?>"></span>
        </div>
        <?php
    }

    public function render_page() {
        ?>
        <div class="wrap wmaiw-settings-wrap">
            <h1>🤖 WP Multi AI Writer 设置</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wmaiw_settings_group'); ?>
                <?php do_settings_sections('wmaiw-settings'); ?>
                <?php submit_button('保存所有设置', 'primary', 'submit', true); ?>
            </form>
            <div class="wmaiw-info">
                <h3>📌 获取 API Key 指引</h3>
                <ul><li><strong>DeepSeek</strong>：<a href="https://platform.deepseek.com/" target="_blank">https://platform.deepseek.com/</a></li></ul>
            </div>
        </div>
        <?php
    }
}