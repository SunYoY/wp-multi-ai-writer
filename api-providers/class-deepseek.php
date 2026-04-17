<?php
class WMAIW_DeepSeek implements WMAIW_AI_Provider {
    private $api_key;
    private $api_url = 'https://api.deepseek.com/v1/chat/completions';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function generate($prompt, $params = array()) {
        // 强制转换 prompt 为字符串
        if (is_array($prompt)) {
            $prompt = implode("\n", $prompt);
        }
        $prompt = (string) $prompt;
        if (trim($prompt) === '') {
            $prompt = '请写一篇关于 WordPress 的简短介绍。';
        }

        $body = array(
            'model'      => 'deepseek-chat',
            'messages'   => array(
                array('role' => 'user', 'content' => $prompt)
            ),
            'temperature' => (float) ($params['temperature'] ?? 0.7),
            'max_tokens'  => (int) ($params['max_tokens'] ?? 2000),
        );

        // 调试日志
        error_log('DeepSeek Request: ' . json_encode($body));

        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode($body),
            'timeout' => get_option('wmaiw_settings')['timeout'] ?? 30,
        ));

        if (is_wp_error($response)) {
            error_log('DeepSeek WP_Error: ' . $response->get_error_message());
            return $response;
        }

        $response_body = wp_remote_retrieve_body($response);
        error_log('DeepSeek Response: ' . $response_body);

        $data = json_decode($response_body, true);
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        $error_msg = $data['error']['message'] ?? '未知错误';
        error_log('DeepSeek API Error: ' . $error_msg);
        return new WP_Error('api_error', $error_msg);
    }

    public function is_available() {
        return !empty($this->api_key);
    }
}