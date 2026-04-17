<?php
/**
 * Plugin Name: WordPressAI助手
 * Plugin URI:  https://3css.cn/
 * Description: 接入通义千问、豆包、DeepSeek 等 AI 模型，异步生成 SEO 友好文章。
 * Version:     3.0.0
 * Author:      YiRan
 * License:     GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

define('WMAIW_VERSION', '3.0.0');
define('WMAIW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WMAIW_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WMAIW_PLUGIN_DIR . 'includes/class-plugin.php';

function wmaiw_init() {
    new WMAIW_Plugin();
}
add_action('plugins_loaded', 'wmaiw_init');

// 激活钩子：创建数据库表
register_activation_hook(__FILE__, 'wmaiw_create_tables');
function wmaiw_create_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wmaiw_tasks';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        model varchar(50) NOT NULL,
        status varchar(20) DEFAULT 'pending',
        topic text NOT NULL,
        style varchar(20) DEFAULT 'professional',
        length int(11) DEFAULT 500,
        result_title text,
        result_content longtext,
        result_tags text,
        error_message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY status (status)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // 设置默认选项
    if (!get_option('wmaiw_settings')) {
        update_option('wmaiw_settings', array(
            'default_model' => 'deepseek',
            'timeout'       => 60,
            'max_tokens'    => 2000,
            'temperature'   => 0.7,
        ));
    }
}

// 清理过期任务（每天一次）
add_action('wmaiw_cleanup_tasks', 'wmaiw_delete_old_tasks');
function wmaiw_delete_old_tasks() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wmaiw_tasks';
    $wpdb->query("DELETE FROM $table_name WHERE status IN ('completed','failed') AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
}
if (!wp_next_scheduled('wmaiw_cleanup_tasks')) {
    wp_schedule_event(time(), 'daily', 'wmaiw_cleanup_tasks');
}
// 强制注册顶级菜单（避免类加载问题）
// 最终保险：如果菜单未被注册，则延迟强制注册
add_action('admin_menu', 'wmaiw_fallback_menu', 1000);
function wmaiw_fallback_menu() {
    global $menu;
    $exists = false;
    if (is_array($menu)) {
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === 'wmaiw-settings') {
                $exists = true;
                break;
            }
        }
    }
    if (!$exists && class_exists('WMAIW_Settings')) {
        $settings = new WMAIW_Settings();
        if (method_exists($settings, 'register_menu')) {
            $settings->register_menu();
        }
    }
}