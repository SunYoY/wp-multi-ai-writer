<?php
class WMAIW_Qwen implements WMAIW_AI_Provider {
    private $api_key;
    private $api_url = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function generate($prompt, $params = array()) {
        $body = array(
            'model' => 'qwen-turbo',
            'input' => array(
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                )
            ),
            'parameters' => array(
                'temperature' => (float) ($params['temperature'] ?? 0.7),
                'max_tokens'  => (int) ($params['max_tokens'] ?? 2000),
            ),
        );

        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => json_encode($body),
            'timeout' => get_option('wmaiw_settings')['timeout'] ?? 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['output']['text'])) {
            return $data['output']['text'];
        }
        return new WP_Error('api_error', $data['message'] ?? '未知错误');
    }

    public function is_available() {
        return !empty($this->api_key);
    }
}