(function(wp) {
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { PanelBody, TextareaControl, SelectControl, Button, Spinner, Notice } = wp.components;
    const { useState } = wp.element;
    const { dispatch } = wp.data;
    const { __ } = wp.i18n;

    const AIModal = () => {
        const [topic, setTopic] = useState('');
        const [model, setModel] = useState('deepseek');
        const [style, setStyle] = useState('professional');
        const [length, setLength] = useState(1500);
        const [loading, setLoading] = useState(false);
        const [error, setError] = useState('');

        const handleGenerate = async () => {
            if (!topic.trim()) {
                setError('请输入文章主题');
                return;
            }
            setLoading(true);
            setError('');

            try {
                const response = await fetch(wmaiw_rest.root + 'wmaiw/v1/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wmaiw_rest.nonce
                    },
                    body: JSON.stringify({ topic, model, style, length })
                });
                const data = await response.json();
                if (response.ok) {
                    // 插入内容到编辑器
                    const blocks = wp.blocks.parse(data.content);
                    dispatch('core/editor').insertBlocks(blocks);
                    // 设置标题
                    if (data.title && !wp.data.select('core/editor').getEditedPostAttribute('title')) {
                        dispatch('core/editor').editPost({ title: data.title });
                    }
                    if (data.tags && data.tags.length) {
                        console.log('建议标签:', data.tags);
                    }
                } else {
                    setError(data.message || '生成失败');
                }
            } catch (err) {
                setError('网络错误，请重试');
            } finally {
                setLoading(false);
            }
        };

        return (
            <>
                <PluginSidebarMoreMenuItem target="ai-writer-sidebar">
                    AI 文章生成器
                </PluginSidebarMoreMenuItem>
                <PluginSidebar name="ai-writer-sidebar" title="AI 文章生成器">
                    <PanelBody>
                        {error && <Notice status="error" onRemove={() => setError('')}>{error}</Notice>}
                        <TextareaControl
                            label="文章主题"
                            value={topic}
                            onChange={setTopic}
                            rows={3}
                            placeholder="例如：WordPress 性能优化的 10 个技巧"
                        />
                        <SelectControl
                            label="选择模型"
                            value={model}
                            options={[
                                { label: 'DeepSeek', value: 'deepseek' },
                                { label: '通义千问', value: 'qwen' },
                                { label: '豆包', value: 'doubao' }
                            ]}
                            onChange={setModel}
                        />
                        <SelectControl
                            label="文章风格"
                            value={style}
                            options={[
                                { label: '专业正式', value: 'professional' },
                                { label: '轻松口语', value: 'casual' },
                                { label: '热情感染', value: 'enthusiastic' }
                            ]}
                            onChange={setStyle}
                        />
                        <SelectControl
                            label="文章长度"
                            value={length}
                            options={[
                                { label: '短篇 (500字)', value: 500 },
                                { label: '中篇 (1000字)', value: 1000 },
                                { label: '长篇 (1500字)', value: 1500 },
                                { label: '超长 (2000字)', value: 2000 }
                            ]}
                            onChange={setLength}
                        />
                        <Button isPrimary onClick={handleGenerate} disabled={loading}>
                            {loading ? <Spinner /> : '生成并插入'}
                        </Button>
                    </PanelBody>
                </PluginSidebar>
            </>
        );
    };

    registerPlugin('wmaiw-ai-writer', { render: AIModal });
})(window.wp);