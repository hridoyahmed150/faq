<?php
/**
 * Plugin Name: EMG FAQ
 * Description: FAQ via shortcode with optional manual schema or auto FAQPage JSON-LD; inner shortcode HTML supported.
 * Version: 1.2.0
 * Author: Hridoy Ahmed
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMG_FAQ_Plugin
{
    const OPT_DEFAULT_FAQ = 'emg_faq_default_faq_items';
    const OPT_DEFAULT_SCHEMA = 'emg_faq_default_schema_json';
    const OPT_DISPLAY_MODE = 'emg_faq_display_mode';
    const OPT_MANUAL_SCHEMA = 'emg_faq_manual_schema_enabled';
    const OPT_DISABLE_SCHEMA = 'emg_faq_disable_schema_output';
    const OPT_Q_FONT_SIZE = 'emg_faq_question_font_size';
    const OPT_Q_COLOR = 'emg_faq_question_color';
    const OPT_A_FONT_SIZE = 'emg_faq_answer_font_size';
    const OPT_A_COLOR = 'emg_faq_answer_color';

    const STYLE_HANDLE = 'emg-faq-frontend';

    private $schema_queue = array();

    private static $faq_assets_enqueued = false;

    private static $accordion_script_needed = false;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_footer', array($this, 'admin_footer_schema_toggle_script'));
        add_action('wp_enqueue_scripts', array($this, 'maybe_pre_enqueue_faq_assets'), 20);
        add_action('wp_footer', array($this, 'output_accordion_script_once'), 5);
        add_action('wp_footer', array($this, 'output_queued_schema'), 1);
        add_shortcode('emg_faq', array($this, 'shortcode_emg_faq'));
        add_shortcode('emg-faq', array($this, 'shortcode_emg_faq'));
    }

    /**
     * Load FAQ CSS in &lt;head&gt; when the main post content likely contains the shortcode.
     */
    public function maybe_pre_enqueue_faq_assets()
    {
        if (is_admin()) {
            return;
        }
        if ($this->queried_post_has_emg_faq_shortcode()) {
            $this->ensure_faq_assets_enqueued();
        }
    }

    private function queried_post_has_emg_faq_shortcode()
    {
        if (!is_singular()) {
            return false;
        }
        $post = get_queried_object();
        if (!$post instanceof WP_Post || $post->post_content === '') {
            return false;
        }
        return has_shortcode($post->post_content, 'emg_faq') || has_shortcode($post->post_content, 'emg-faq');
    }

    private function ensure_faq_assets_enqueued()
    {
        if (self::$faq_assets_enqueued) {
            return;
        }
        self::$faq_assets_enqueued = true;

        wp_register_style(self::STYLE_HANDLE, false, array(), '1.2.0');
        wp_enqueue_style(self::STYLE_HANDLE);
        wp_add_inline_style(self::STYLE_HANDLE, $this->get_frontend_css());
    }

    private function get_frontend_css()
    {
        $question_font_size = (int) get_option(self::OPT_Q_FONT_SIZE, 16);
        if ($question_font_size < 8 || $question_font_size > 72) {
            $question_font_size = 16;
        }
        $question_color = sanitize_hex_color((string) get_option(self::OPT_Q_COLOR, '#111827'));
        if (empty($question_color)) {
            $question_color = '#111827';
        }
        $answer_font_size = (int) get_option(self::OPT_A_FONT_SIZE, 16);
        if ($answer_font_size < 8 || $answer_font_size > 72) {
            $answer_font_size = 16;
        }
        $answer_color = sanitize_hex_color((string) get_option(self::OPT_A_COLOR, '#374151'));
        if (empty($answer_color)) {
            $answer_color = '#374151';
        }

        return '
.emg-faq-box {
	margin: 28px 0;
	padding: 20px;
	background: #fff;
}
.emg-faq-box .emg-faq-title {
	margin: 0 0 14px;
	font-size: 28px;
	line-height: 1.2;
}
.emg-faq-box .emg-faq-item {
	border-top: 1px solid #edf1f5;
	padding: 12px 0;
}
.emg-faq-box .emg-faq-item:first-of-type {
	border-top: 0;
	padding-top: 0;
}
.emg-faq-box .emg-faq-question {
	width: 100%;
	background: transparent;
	border: 0;
	padding: 0 24px 0 0;
	text-align: left;
	cursor: pointer;
	font-weight: 700;
	position: relative;
	font-size: ' . $question_font_size . 'px;
	color: ' . $question_color . ';
}
.emg-faq-box .emg-faq-question-text {
	font-size: ' . $question_font_size . 'px;
	color: ' . $question_color . ';
	font-weight: 700;
}
.emg-faq-box .emg-faq-question::after {
	content: "+";
	position: absolute;
	right: 0;
	top: 50%;
	transform: translateY(-50%);
	font-size: 20px;
	line-height: 1;
	transition: transform 0.25s ease, opacity 0.25s ease;
}
.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-question::after {
	content: "−";
	transform: translateY(-50%) scale(1.05);
}
.emg-faq-box .emg-faq-answer {
	margin-top: 8px;
	line-height: 1.6;
	font-size: ' . $answer_font_size . 'px;
	color: ' . $answer_color . ';
}
.emg-faq-box .emg-faq-panel {
	overflow: hidden;
	max-height: 0;
	opacity: 0;
	transform: translateY(-4px);
	transition: max-height 0.28s ease, opacity 0.22s ease, transform 0.22s ease;
	will-change: max-height, opacity, transform;
}
.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-panel {
	opacity: 1;
	transform: translateY(0);
}
.emg-faq-box.emg-faq-freeform .emg-faq-freeform-body {
	line-height: 1.6;
}
';
    }

    private function request_accordion_script()
    {
        self::$accordion_script_needed = true;
    }

    public function output_accordion_script_once()
    {
        if (!self::$accordion_script_needed) {
            return;
        }
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;
        ?>
        <script id="emg-faq-accordion">
            (function () {
                function animateFaqToggle(itemEl, forceOpen) {
                    var panel = itemEl.querySelector('.emg-faq-panel');
                    var trigger = itemEl.querySelector('.emg-faq-question');
                    if (!panel) return;
                    var willOpen = typeof forceOpen === 'boolean' ? forceOpen : !itemEl.classList.contains('is-open');
                    if (willOpen) {
                        itemEl.classList.add('is-open');
                        if (trigger) {
                            trigger.setAttribute('aria-expanded', 'true');
                        }
                        panel.hidden = false;
                        panel.style.maxHeight = '0px';
                        panel.style.opacity = '0';
                        panel.style.transform = 'translateY(-4px)';
                        window.requestAnimationFrame(function () {
                            panel.style.maxHeight = panel.scrollHeight + 'px';
                            panel.style.opacity = '1';
                            panel.style.transform = 'translateY(0)';
                        });
                    } else {
                        panel.style.maxHeight = panel.scrollHeight + 'px';
                        panel.style.opacity = '1';
                        panel.style.transform = 'translateY(0)';
                        window.requestAnimationFrame(function () {
                            panel.style.maxHeight = '0px';
                            panel.style.opacity = '0';
                            panel.style.transform = 'translateY(-4px)';
                        });
                        window.setTimeout(function () {
                            itemEl.classList.remove('is-open');
                            if (trigger) {
                                trigger.setAttribute('aria-expanded', 'false');
                            }
                            panel.hidden = true;
                        }, 280);
                    }
                }
                document.querySelectorAll('.emg-faq-box').forEach(function (scope) {
                    scope.querySelectorAll('.emg-faq-item.emg-faq-acc-item').forEach(function (itemEl) {
                        var trigger = itemEl.querySelector('.emg-faq-question');
                        var panel = itemEl.querySelector('.emg-faq-panel');
                        if (!trigger || !panel) return;
                        if (itemEl.classList.contains('is-open')) {
                            panel.style.maxHeight = panel.scrollHeight + 'px';
                            panel.style.opacity = '1';
                            panel.style.transform = 'translateY(0)';
                            panel.hidden = false;
                            trigger.setAttribute('aria-expanded', 'true');
                        } else {
                            panel.style.maxHeight = '0px';
                            panel.style.opacity = '0';
                            panel.style.transform = 'translateY(-4px)';
                            panel.hidden = true;
                            trigger.setAttribute('aria-expanded', 'false');
                        }
                        trigger.addEventListener('click', function (e) {
                            e.preventDefault();
                            animateFaqToggle(itemEl);
                        });
                    });
                });
            })();
        </script>
        <?php
    }

    public function admin_menu()
    {
        add_menu_page(
            'EMG FAQ',
            'EMG FAQ',
            'manage_options',
            'emg-faq',
            array($this, 'render_settings_page'),
            'dashicons-editor-help',
            62
        );
    }

    public function register_settings()
    {
        register_setting('emg_faq_settings', self::OPT_DEFAULT_FAQ, array('sanitize_callback' => array($this, 'sanitize_text')));
        register_setting('emg_faq_settings', self::OPT_DEFAULT_SCHEMA, array('sanitize_callback' => array($this, 'sanitize_schema_text')));
        register_setting('emg_faq_settings', self::OPT_DISPLAY_MODE, array('sanitize_callback' => array($this, 'sanitize_display_mode')));
        register_setting('emg_faq_settings', self::OPT_MANUAL_SCHEMA, array('sanitize_callback' => array($this, 'sanitize_manual_schema_flag')));
        register_setting('emg_faq_settings', self::OPT_DISABLE_SCHEMA, array('sanitize_callback' => array($this, 'sanitize_manual_schema_flag')));
        register_setting('emg_faq_settings', self::OPT_Q_FONT_SIZE, array('sanitize_callback' => array($this, 'sanitize_font_size')));
        register_setting('emg_faq_settings', self::OPT_Q_COLOR, array('sanitize_callback' => array($this, 'sanitize_color')));
        register_setting('emg_faq_settings', self::OPT_A_FONT_SIZE, array('sanitize_callback' => array($this, 'sanitize_font_size')));
        register_setting('emg_faq_settings', self::OPT_A_COLOR, array('sanitize_callback' => array($this, 'sanitize_color')));
    }

    public function sanitize_text($value)
    {
        return is_string($value) ? trim($value) : '';
    }

    public function sanitize_manual_schema_flag($value)
    {
        return ($value === '1' || $value === 1 || $value === true) ? '1' : '0';
    }

    public function sanitize_schema_text($value)
    {
        $value = is_string($value) ? trim($value) : '';
        $value = $this->normalize_schema_json($value);
        if ($value === '') {
            return '';
        }

        if (!$this->is_valid_json($value)) {
            add_settings_error(
                'emg_faq_settings',
                'emg_faq_invalid_schema',
                'Default FAQ Schema is not valid JSON. Settings were saved but that schema will be skipped until fixed (auto schema may still apply).',
                'error'
            );
        }

        return $value;
    }

    private function normalize_schema_json($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }

        if (stripos($raw, '<script') !== false) {
            $raw = preg_replace('/^\s*<script[^>]*>/i', '', $raw);
            $raw = preg_replace('/<\/script>\s*$/i', '', $raw);
            $raw = trim((string) $raw);
        }

        return $raw;
    }

    public function sanitize_display_mode($value)
    {
        $value = is_string($value) ? strtolower(trim($value)) : 'accordion';
        return in_array($value, array('plain', 'accordion'), true) ? $value : 'accordion';
    }

    public function sanitize_font_size($value)
    {
        $size = (int) $value;
        if ($size < 8) {
            $size = 8;
        }
        if ($size > 72) {
            $size = 72;
        }
        return (string) $size;
    }

    public function sanitize_color($value)
    {
        $color = sanitize_hex_color((string) $value);
        return $color ? $color : '#111827';
    }

    public function admin_footer_schema_toggle_script()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'toplevel_page_emg-faq') {
            return;
        }
        ?>
        <script>
            (function () {
                var cb = document.getElementById('emg-faq-auto-schema-enabled');
                var wrap = document.getElementById('emg-faq-schema-field-wrap');
                if (!cb || !wrap) return;
                function sync() { wrap.style.display = cb.checked ? 'none' : 'block'; }
                cb.addEventListener('change', sync);
                sync();
            })();
        </script>
        <?php
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $default_faq = (string) get_option(self::OPT_DEFAULT_FAQ, '');
        $default_schema = (string) get_option(self::OPT_DEFAULT_SCHEMA, '');
        $display_mode = (string) get_option(self::OPT_DISPLAY_MODE, 'accordion');
        $auto_schema = (string) get_option(self::OPT_MANUAL_SCHEMA, '1');
        $schema_output_enabled = (string) get_option(self::OPT_DISABLE_SCHEMA, '1');
        $question_font_size = (string) get_option(self::OPT_Q_FONT_SIZE, '16');
        $question_color = (string) get_option(self::OPT_Q_COLOR, '#111827');
        $answer_font_size = (string) get_option(self::OPT_A_FONT_SIZE, '16');
        $answer_color = (string) get_option(self::OPT_A_COLOR, '#374151');
        ?>
        <div class="wrap">
            <h1>EMG FAQ</h1>

            <h2 style="margin-top:20px;">Shortcode Examples</h2>
            <p><h3>1) FAQ list:</h3><code>[emg_faq class="faq-box"]</code>
            <div><h3>2) FAQ list with city replacement:</h3>
                <p>In <strong>FAQ List</strong>, add this line:
                <code>What is portable storage in {{city}}? || Portable storage in {{city}} is a container service.</code></p>
                <p>Then use shortcode:</p>
                <code>[emg_faq city="Dallas"]</code>
                <p>Frontend FAQ output becomes:</p>
                <p><code>What is portable storage in Dallas?</code></p>
                <p><code>Portable storage in Dallas is a container service.</code></p>
                <p>Schema output will also use <code>Dallas</code> in the same places.</p>
            </div>
            <div><h3>3) Mode example (Plain vs Accordion):</h3>
                <p>Plain mode shortcode: <code>[emg_faq city="Dallas" mode="plain"]</code></p>
                <p>Accordion mode shortcode: <code>[emg_faq city="Dallas" mode="accordion"]</code></p>
                If <code>mode</code> is not set in shortcode, plugin uses the global <strong>FAQ View Style</strong> selected
                below.
            </div>
            <div><h3>4) Custom content inside shortcode (overrides FAQ list):</h3>
                <p>Selector-based example (recommended for question/answer parsing + schema):<br>
                <code>[emg_faq city="Dallas" question_selector=".faq-q" answer_selector=".faq-a" generate_schema="yes"]&lt;h2 class="faq-q"&gt;What is portable storage in {{city}}?&lt;/h2&gt;&lt;p class="faq-a"&gt;Portable storage in {{city}} helps you move at your own pace.&lt;/p&gt;[/emg_faq]</code></p>

                <p>Result: only this custom content is shown; global FAQ list is ignored for this block. City placeholders are replaced (e.g. <code>{{city}}</code> → <code>Dallas</code>).</p>
            </div>


            <form method="post" action="options.php">
                <?php settings_fields('emg_faq_settings'); ?>
                <?php settings_errors('emg_faq_settings'); ?>
                <h2>FAQ List</h2>
                <p>FAQ format: one line per item, like <code>Question || Answer</code>.</p>
                <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>" rows="16"
                    style="width:100%;"><?php echo esc_textarea($default_faq); ?></textarea>

                <h2 style="margin-top:24px;">Schema Settings</h2>
                <p>
                    <label>
                        <input type="hidden" name="<?php echo esc_attr(self::OPT_DISABLE_SCHEMA); ?>" value="0" />
                        <input type="checkbox"
                            name="<?php echo esc_attr(self::OPT_DISABLE_SCHEMA); ?>" value="1" <?php checked($schema_output_enabled, '1'); ?> />
                        Show schema output (JSON-LD)
                    </label>
                </p>
                <p>
                    <label>
                        <input type="hidden" name="<?php echo esc_attr(self::OPT_MANUAL_SCHEMA); ?>" value="0" />
                        <input type="checkbox" id="emg-faq-auto-schema-enabled"
                            name="<?php echo esc_attr(self::OPT_MANUAL_SCHEMA); ?>" value="1" <?php checked($auto_schema, '1'); ?> />
                        Auto generate schema from FAQ items
                    </label>
                </p>
                <p class="description">
                    <strong>Checked:</strong> schema is auto-created from visible FAQ items.<br>
                    <strong>Unchecked:</strong> custom schema box appears below, and you can provide your own JSON-LD.<br>
                    If custom JSON is empty or invalid, plugin falls back to auto schema.
                </p>

                <div id="emg-faq-schema-field-wrap" style="<?php echo $auto_schema === '1' ? 'display:none;' : ''; ?>">
                    <h3>Custom Schema JSON (Optional)</h3>
                    <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_SCHEMA); ?>" rows="14"
                        style="width:100%;"><?php echo esc_textarea($default_schema); ?></textarea>
                </div>

                <h2 style="margin-top:24px;">FAQ View Style</h2>
                <select name="<?php echo esc_attr(self::OPT_DISPLAY_MODE); ?>">
                    <option value="accordion" <?php selected($display_mode, 'accordion'); ?>>Accordion</option>
                    <option value="plain" <?php selected($display_mode, 'plain'); ?>>Plain (Question and Answer)</option>
                </select>

                <h2 style="margin-top:24px;">FAQ Text Style</h2>
                <p>
                    <label>
                        Question font size (px):
                        <input type="number" min="8" max="72" step="1"
                            name="<?php echo esc_attr(self::OPT_Q_FONT_SIZE); ?>"
                            value="<?php echo esc_attr($question_font_size); ?>" />
                    </label>
                </p>
                <p>
                    <label>
                        Question font color:
                        <input type="color"
                            name="<?php echo esc_attr(self::OPT_Q_COLOR); ?>"
                            value="<?php echo esc_attr($question_color); ?>" />
                    </label>
                </p>
                <p>
                    <label>
                        Answer font size (px):
                        <input type="number" min="8" max="72" step="1"
                            name="<?php echo esc_attr(self::OPT_A_FONT_SIZE); ?>"
                            value="<?php echo esc_attr($answer_font_size); ?>" />
                    </label>
                </p>
                <p>
                    <label>
                        Answer font color:
                        <input type="color"
                            name="<?php echo esc_attr(self::OPT_A_COLOR); ?>"
                            value="<?php echo esc_attr($answer_color); ?>" />
                    </label>
                </p>

                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * @param array  $atts
     * @param string $content Inner HTML between [emg_faq] and [/emg_faq]
     */
    public function shortcode_emg_faq($atts, $content = null)
    {
        if (!is_admin()) {
            $this->ensure_faq_assets_enqueued();
        }
        $schema_output_enabled = get_option(self::OPT_DISABLE_SCHEMA, '1') === '1';

        $atts = shortcode_atts(
            array(
                'title' => '',
                'city' => '',
                'class' => '',
                'mode' => '',
                'question' => 'h3',
                'answer' => 'p',
                'question_selector' => '',
                'answer_selector' => '',
                'generate_schema' => '',
            ),
            $atts,
            'emg_faq'
        );

        $global_display_mode = (string) get_option(self::OPT_DISPLAY_MODE, 'accordion');
        $display_mode = $atts['mode'] !== '' ? (string) $atts['mode'] : $global_display_mode;
        $city_name = !empty($atts['city']) ? sanitize_text_field((string) $atts['city']) : '';
        $has_selector_pair = !empty($atts['question_selector']) && !empty($atts['answer_selector']);
        if ($atts['mode'] === '' && $has_selector_pair) {
            // For selector-based enclosing content, default to plain when mode is omitted.
            $display_mode = 'plain';
        }

        $inner = $content !== null ? trim((string) $content) : '';

        if ($inner === '') {
            $faq_raw = (string) get_option(self::OPT_DEFAULT_FAQ, '');
            if ($city_name !== '') {
                $faq_raw = $this->replace_city_placeholder($faq_raw, $city_name);
                $atts['title'] = $this->replace_city_placeholder($atts['title'], $city_name);
            }
            $items = $this->parse_faq_lines($faq_raw);
            $auto_schema_on = get_option(self::OPT_MANUAL_SCHEMA, '1') === '1';
            $schema_raw = $this->normalize_schema_json((string) get_option(self::OPT_DEFAULT_SCHEMA, ''));
            if ($city_name !== '') {
                $schema_raw = $this->replace_city_placeholder($schema_raw, $city_name);
            }
            $schema_final = $schema_output_enabled ? $this->resolve_schema_for_output($items, $schema_raw, $auto_schema_on) : '';
            return $this->render_faq_block($atts['title'], $items, $schema_final, $display_mode, $atts['class']);
        }

        $inner_after_shortcodes = do_shortcode($inner);
        $body = $this->replace_city_placeholder($inner_after_shortcodes, $city_name);
        $body = trim((string) wp_kses_post($body));
        if (function_exists('shortcode_unautop')) {
            $body = shortcode_unautop($body);
        }
        $body = $this->remove_empty_paragraphs($body);
        if ($city_name !== '') {
            $atts['title'] = $this->replace_city_placeholder($atts['title'], $city_name);
        }

        // For enclosing shortcode content:
        // - default: print exactly what user writes
        // - accordion mode: if question/answer tags are provided and parseable, render as accordion items
        // - schema: OFF by default unless generate_schema is explicitly enabled
        $schema_final = '';
        $items_from_tags = array();
        if (!empty($atts['question_selector']) && !empty($atts['answer_selector'])) {
            $items_from_tags = $this->parse_faq_inner_content_by_selectors($body, $atts['question_selector'], $atts['answer_selector']);
        }
        if (empty($items_from_tags)) {
            $items_from_tags = $this->parse_faq_inner_content_by_tags($body, $atts['question'], $atts['answer']);
        }

        if (!empty($items_from_tags) && $city_name !== '') {
            foreach ($items_from_tags as $i => $row) {
                $items_from_tags[$i]['q'] = $this->replace_city_placeholder($row['q'], $city_name);
                $items_from_tags[$i]['a'] = $this->replace_city_placeholder($row['a'], $city_name);
            }
        }

        $wants_inner_schema = in_array(
            strtolower(trim((string) $atts['generate_schema'])),
            array('1', 'yes', 'true', 'on'),
            true
        );

        if ($schema_output_enabled && $wants_inner_schema) {
            $auto_schema_on = get_option(self::OPT_MANUAL_SCHEMA, '1') === '1';
            $schema_raw = $this->normalize_schema_json((string) get_option(self::OPT_DEFAULT_SCHEMA, ''));
            if ($city_name !== '') {
                $schema_raw = $this->replace_city_placeholder($schema_raw, $city_name);
            }

            $schema_final = $this->resolve_schema_for_output($items_from_tags, $schema_raw, $auto_schema_on);
        }

        // Enclosing + accordion mode can render parsed items from custom question/answer tags.
        if ($display_mode === 'accordion' && !empty($items_from_tags)) {
            return $this->render_faq_block($atts['title'], $items_from_tags, $schema_final, $display_mode, $atts['class']);
        }

        return $this->render_faq_freeform($atts['title'], $body, $schema_final, $atts['class']);
    }

    private function render_faq_freeform($title, $body_html, $schema_raw, $extra_class = '')
    {
        $extra_class = is_string($extra_class) ? trim($extra_class) : '';
        $wrapper_class = 'emg-faq-box emg-faq-freeform';
        if ($extra_class !== '') {
            $wrapper_class .= ' ' . implode(' ', array_map('sanitize_html_class', preg_split('/\s+/', $extra_class)));
        }
        $body_html = $this->remove_empty_paragraphs((string) $body_html);

        ob_start();
        ?>
        <section class="<?php echo esc_attr($wrapper_class); ?>">
            <?php if ($title !== ''): ?>
                <h2 class="emg-faq-title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <div class="emg-faq-freeform-body"><?php echo $body_html; ?></div>
        </section>
        <?php
        $this->queue_schema($schema_raw);
        return ob_get_clean();
    }

    /**
     * Manual schema when enabled + valid; otherwise auto FAQPage from items.
     */
    private function resolve_schema_for_output($items, $schema_raw, $auto_schema_on)
    {
        if (empty($items)) {
            return '';
        }

        if (!$auto_schema_on) {
            $schema_raw = trim((string) $schema_raw);
            if ($schema_raw !== '' && $this->is_valid_json($schema_raw)) {
                return $schema_raw;
            }
        }

        return $this->build_faqpage_schema_json($items);
    }

    private function build_faqpage_schema_json($items)
    {
        $main_entity = array();
        foreach ($items as $item) {
            $q = isset($item['q']) ? wp_strip_all_tags((string) $item['q']) : '';
            $a = isset($item['a']) ? wp_strip_all_tags((string) $item['a']) : '';
            if ($q === '' || $a === '') {
                continue;
            }
            $main_entity[] = array(
                '@type' => 'Question',
                'name' => $q,
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $a,
                ),
            );
        }

        if (empty($main_entity)) {
            return '';
        }

        $data = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $main_entity,
        );

        return wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Strip &lt;p&gt; blocks that are empty or whitespace/NBSP-only (common after wpautop around shortcodes).
     */
    private function remove_empty_paragraphs($html)
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }
        $cleaned = preg_replace_callback(
            '/<p\b[^>]*>(.*?)<\/p>/is',
            function ($m) {
                $inner = (string) $m[1];
                if (preg_match('/<[a-z][^>]*>/i', $inner)) {
                    return $m[0];
                }
                $inner = html_entity_decode($inner, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $inner = preg_replace('/<br\s*\/?>/i', '', $inner);
                $inner = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', '', $inner);
                $inner = trim(wp_strip_all_tags($inner, true));
                return $inner === '' ? '' : $m[0];
            },
            $html
        );

        return trim((string) $cleaned);
    }

    private function parse_faq_inner_content_by_tags($html, $question_tag = 'h3', $answer_tag = 'p')
    {
        $html = trim((string) $html);
        if ($html === '') {
            return array();
        }

        $qtag = strtolower(trim((string) $question_tag));
        $atag = strtolower(trim((string) $answer_tag));
        if (!preg_match('/^h[1-6]$/', $qtag)) {
            $qtag = 'h3';
        }
        if (!preg_match('/^(p|div|li|span|blockquote)$/', $atag)) {
            $atag = 'p';
        }

        $items = array();
        $pattern = '/<' . preg_quote($qtag, '/') . '\b[^>]*>(.*?)<\/' . preg_quote($qtag, '/') . '>\s*(.*?)(?=<' . preg_quote($qtag, '/') . '\b|\z)/is';
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $q = trim(wp_strip_all_tags($m[1]));
                if ($q === '') {
                    continue;
                }

                $a_block = trim((string) $m[2]);
                $a = '';
                if (preg_match('/<' . preg_quote($atag, '/') . '\b[^>]*>(.*?)<\/' . preg_quote($atag, '/') . '>/is', $a_block, $am)) {
                    $a = trim(wp_strip_all_tags($am[1]));
                } else {
                    $a = trim(wp_strip_all_tags($a_block));
                }

                if ($a === '') {
                    continue;
                }
                $items[] = array('q' => $q, 'a' => $a);
            }
        }

        return $items;
    }

    private function parse_faq_inner_content_by_selectors($html, $question_selector, $answer_selector)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return array();
        }

        $q = trim((string) $question_selector);
        $a = trim((string) $answer_selector);
        if ($q === '' || $a === '') {
            return array();
        }

        $qPattern = $this->selector_to_regex($q);
        $aPattern = $this->selector_to_regex($a);
        if ($qPattern === '' || $aPattern === '') {
            return array();
        }

        $items = array();
        $pattern = '/<([a-z0-9]+)\b[^>]*' . $qPattern . '[^>]*>(.*?)<\/\1>\s*(.*?)(?=<[a-z0-9]+\b[^>]*' . $qPattern . '[^>]*>|\z)/is';
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $question = trim(wp_strip_all_tags($m[2]));
                if ($question === '') {
                    continue;
                }
                $block = (string) $m[3];
                $answer = '';
                if (preg_match('/<([a-z0-9]+)\b[^>]*' . $aPattern . '[^>]*>(.*?)<\/\1>/is', $block, $am)) {
                    $answer = trim(wp_strip_all_tags($am[2]));
                } else {
                    $answer = trim(wp_strip_all_tags($block));
                }
                if ($answer === '') {
                    continue;
                }
                $items[] = array('q' => $question, 'a' => $answer);
            }
        }

        return $items;
    }

    private function selector_to_regex($selector)
    {
        $selector = trim((string) $selector);
        if ($selector === '') {
            return '';
        }

        // class selector: .faq-q
        if (strpos($selector, '.') === 0) {
            $name = preg_replace('/[^a-zA-Z0-9_-]/', '', substr($selector, 1));
            if ($name === '') {
                return '';
            }
            return 'class=["\'][^"\']*\b' . preg_quote($name, '/') . '\b[^"\']*["\']';
        }

        // id selector: #faq-q
        if (strpos($selector, '#') === 0) {
            $name = preg_replace('/[^a-zA-Z0-9_-]/', '', substr($selector, 1));
            if ($name === '') {
                return '';
            }
            return 'id=["\']' . preg_quote($name, '/') . '["\']';
        }

        return '';
    }

    private function replace_city_placeholder($text, $city)
    {
        $text = (string) $text;
        $city = (string) $city;
        if ($city === '') {
            return $text;
        }
        $text = str_ireplace('{{city}}', $city, $text);
        $text = str_replace('{City}', $city, $text);
        $text = str_ireplace('{city}', $city, $text);
        return $text;
    }

    private function is_valid_json($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return true;
        }
        json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function parse_faq_lines($raw)
    {
        $items = array();
        $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
        if (!is_array($lines)) {
            return $items;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '||') === false) {
                continue;
            }
            $parts = explode('||', $line, 2);
            $q = trim($parts[0]);
            $a = trim($parts[1]);
            if ($q === '' || $a === '') {
                continue;
            }
            $items[] = array('q' => $q, 'a' => $a, 'answer_is_html' => false);
        }

        return $items;
    }

    private function render_faq_block($title, $items, $schema_raw, $display_mode = 'accordion', $extra_class = '')
    {
        if (empty($items)) {
            return '';
        }

        $display_mode = $this->sanitize_display_mode($display_mode);
        $is_plain = $display_mode === 'plain';
        $extra_class = is_string($extra_class) ? trim($extra_class) : '';
        $wrapper_class = 'emg-faq-box';
        if ($extra_class !== '') {
            $wrapper_class .= ' ' . implode(' ', array_map('sanitize_html_class', preg_split('/\s+/', $extra_class)));
        }

        ob_start();
        ?>
        <section class="<?php echo esc_attr($wrapper_class); ?>">
            <?php if ($title !== ''): ?>
                <h2 class="emg-faq-title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
                <?php
                $q_esc = esc_html($item['q']);
                $html_ans = !empty($item['answer_is_html']);
                ?>
                <?php if ($is_plain): ?>
                    <div class="emg-faq-item">
                        <div class="emg-faq-question-text"><?php echo $q_esc; ?></div>
                        <div class="emg-faq-answer">
                            <?php echo $html_ans ? wp_kses_post($item['a']) : esc_html($item['a']); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="emg-faq-item emg-faq-acc-item">
                        <button type="button" class="emg-faq-question" aria-expanded="false">
                            <?php echo $q_esc; ?>
                        </button>
                        <div class="emg-faq-answer emg-faq-panel" hidden>
                            <?php echo $html_ans ? wp_kses_post($item['a']) : esc_html($item['a']); ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>
        <?php
        if (!$is_plain) {
            $this->request_accordion_script();
        }

        $this->queue_schema($schema_raw);

        return ob_get_clean();
    }

    private function queue_schema($schema_raw)
    {
        $schema_raw = trim((string) $schema_raw);
        if ($schema_raw === '' || !$this->is_valid_json($schema_raw)) {
            return;
        }

        $key = md5($schema_raw);
        $this->schema_queue[$key] = $schema_raw;
    }

    public function output_queued_schema()
    {
        if (empty($this->schema_queue)) {
            return;
        }

        foreach ($this->schema_queue as $schema_raw) {
            echo '<script type="application/ld+json">' . $schema_raw . '</script>' . "\n";
        }
    }
}

new EMG_FAQ_Plugin();
