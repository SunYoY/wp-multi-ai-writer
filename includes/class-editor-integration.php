<?php
class WMAIW_Editor_Integration {

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('edit_form_after_title', array($this, 'add_ai_button'));
        add_action('admin_footer', array($this, 'render_modal_html'));
    }

    public function enqueue_scripts($hook) {
        global $post;
        if (!in_array($hook, array('post.php', 'post-new.php'))) return;
        $use_classic = !use_block_editor_for_post($post);
        if (!$use_classic) return;
        wp_enqueue_script('wmaiw-classic', WMAIW_PLUGIN_URL . 'assets/js/classic-editor.js', array('jquery', 'jquery-ui-dialog'), WMAIW_VERSION, true);
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_localize_script('wmaiw-classic', 'wmaiw_rest', array(
            'root'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    public function add_ai_button() {
        global $post;
        if (!$post) return;
        $use_classic = !use_block_editor_for_post($post);
        if (!$use_classic) return;
        echo '<div style="margin-bottom: 15px;"><button type="button" id="wmaiw-generate-btn" class="button button-primary">🤖 AI 生成文章（异步）</button></div>';
    }

    public function render_modal_html() {
        global $post;
        if (!$post) return;
        $use_classic = !use_block_editor_for_post($post);
        if (!$use_classic) return;
        ?>
        <div id="wmaiw-modal" style="display:none;" title="AI 生成文章">
            <div style="padding: 10px;">
                <p><label>文章主题：</label><br/>
                <textarea id="wmaiw-topic" rows="3" style="width:100%;" placeholder="例如：WordPress 性能优化的10个技巧"></textarea></p>
                <p><label>选择模型：</label><br/>
                <select id="wmaiw-model" style="width:100%;">
                    <option value="deepseek">DeepSeek</option>
                    <option value="qwen">通义千问</option>
                    <option value="doubao">豆包</option>
                </select></p>
                <p><label>文章风格：</label><br/>
                <select id="wmaiw-style" style="width:100%;">
                    <option value="professional">专业正式</option>
                    <option value="casual">轻松口语</option>
                    <option value="enthusiastic">热情感染</option>
                </select></p>
                <p><label>文章长度：</label><br/>
                <select id="wmaiw-length" style="width:100%;">
                    <option value="100">短文 (100字)</option>
                    <option value="200">中长 (200字)</option>
                    <option value="500" selected>长文 (500字)</option>
                    <option value="1000">超长 (1000字)</option>
                </select></p>
                <div id="wmaiw-generate-status" style="color:green;"></div>
                <button id="wmaiw-do-generate" class="button button-primary">开始生成</button>
                <span class="spinner" style="float:none; margin-left:10px;"></span>
            </div>
        </div>
        <?php
    }
}