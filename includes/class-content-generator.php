<?php
class WMAIW_Content_Generator {

    private $api_manager;

    public function __construct($api_manager) {
        $this->api_manager = $api_manager;
    }

    public function generate_article($topic, $model, $style, $length) {
        $prompt = $this->build_prompt($topic, $style, $length);
        $settings = get_option('wmaiw_settings', array());
        $params = array(
            'temperature' => isset($settings['temperature']) ? (float) $settings['temperature'] : 0.7,
            'max_tokens'  => isset($settings['max_tokens']) ? (int) $settings['max_tokens'] : 2000,
        );
        $content = $this->api_manager->generate($model, $prompt, $params);
        if (is_wp_error($content)) {
            return $content;
        }
        return $this->parse_response($content);
    }

    private function build_prompt($topic, $style, $length) {
        $style_map = array(
            'professional' => '专业、正式、客观',
            'casual'       => '轻松、口语化、亲切',
            'enthusiastic' => '热情、有感染力',
        );
        $style_text = isset($style_map[$style]) ? $style_map[$style] : $style_map['professional'];
        $length = max(100, (int) $length);
        
        // 重要：确保主题不为空
        if (empty($topic)) {
            $topic = 'WordPress 入门教程';
        }
        
        $prompt = "你是一位资深的 WordPress 中文博客作者，擅长 SEO 写作。请根据以下要求写一篇文章：

主题：{$topic}
文章风格：{$style_text}
文章长度：约 {$length} 字

请按以下格式输出：
1. 标题（使用 H1 标签，即 <h1>标题</h1>）
2. 正文（使用 H2/H3 标题分隔段落，适当使用列表）
3. 结尾总结
4. 推荐 3-5 个标签（以“标签：”开头，后跟逗号分隔的标签）

直接输出文章内容，不要额外说明。";
        
        return $prompt;
    }

    private function parse_response($raw_content) {
        // 调试日志
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WMAIW Raw AI Content: ' . substr($raw_content, 0, 500));
        }

        $title = '';
        $body = $raw_content;

        // 提取 H1 标题
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $raw_content, $matches)) {
            $title = trim($matches[1]);
            $body = str_replace($matches[0], '', $body);
        } else {
            // 尝试 Markdown 标题
            $lines = explode("\n", $raw_content);
            $first_line = trim($lines[0]);
            if (preg_match('/^#+\s+(.+)/', $first_line, $md_match)) {
                $title = trim($md_match[1]);
                array_shift($lines);
                $body = implode("\n", $lines);
            } else {
                // 如果第一行较短，作为标题
                if (mb_strlen($first_line) < 80 && !empty($first_line)) {
                    $title = $first_line;
                    array_shift($lines);
                    $body = implode("\n", $lines);
                }
            }
        }

        if (empty(trim($body))) {
            $body = $raw_content;
        }

        // 提取标签
        $tags = array();
        if (preg_match('/标签[：:]\s*(.+)/i', $raw_content, $tag_matches)) {
            $tags = array_map('trim', explode(',', $tag_matches[1]));
            $body = preg_replace('/标签[：:]\s*.+/i', '', $body);
        }

        $title = sanitize_text_field($title);
        $body = wp_kses_post($body);

        return array(
            'title'   => $title,
            'content' => $body,
            'excerpt' => wp_trim_words(strip_tags($body), 55),
            'tags'    => $tags,
        );
    }
}