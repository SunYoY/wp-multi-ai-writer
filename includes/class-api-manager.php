<?php
require_once WMAIW_PLUGIN_DIR . 'api-providers/interface-provider.php';
require_once WMAIW_PLUGIN_DIR . 'api-providers/class-deepseek.php';
require_once WMAIW_PLUGIN_DIR . 'api-providers/class-qwen.php';
require_once WMAIW_PLUGIN_DIR . 'api-providers/class-doubao.php';

class WMAIW_API_Manager {

    private $providers = array();

    public function __construct() {
        $settings = get_option('wmaiw_settings', array());
        if (!empty($settings['api_key_deepseek'])) {
            $this->providers['deepseek'] = new WMAIW_DeepSeek($settings['api_key_deepseek']);
        }
        if (!empty($settings['api_key_qwen'])) {
            $this->providers['qwen'] = new WMAIW_Qwen($settings['api_key_qwen']);
        }
        if (!empty($settings['api_key_doubao'])) {
            $this->providers['doubao'] = new WMAIW_Doubao($settings['api_key_doubao']);
        }
    }

    public function generate($model, $prompt, $params = array()) {
        if (!isset($this->providers[$model])) {
            return new WP_Error('model_not_available', "模型 $model 未配置 API Key");
        }
        $provider = $this->providers[$model];
        return $provider->generate($prompt, $params);
    }

    public function get_available_models() {
        return array_keys($this->providers);
    }
}