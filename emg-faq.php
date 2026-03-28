<?php
/**
 * Plugin Name: EMG FAQ
 * Description: FAQ via shortcode with optional manual schema or auto FAQPage JSON-LD; inner shortcode HTML supported.
 * Version: 1.1.2
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

        wp_register_style(self::STYLE_HANDLE, false, array(), '1.1.2');
        wp_enqueue_style(self::STYLE_HANDLE);
        wp_add_inline_style(self::STYLE_HANDLE, $this->get_frontend_css());
    }

    private function get_frontend_css()
    {
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
.emg-faq-box summary {
	cursor: pointer;
	font-weight: 700;
	list-style: none;
	position: relative;
	padding-right: 24px;
}
.emg-faq-box summary::-webkit-details-marker {
	display: none;
}
.emg-faq-box summary::after {
	content: "+";
	position: absolute;
	right: 0;
	top: 50%;
	transform: translateY(-50%);
	font-size: 20px;
	line-height: 1;
	transition: transform 0.25s ease, opacity 0.25s ease;
}
.emg-faq-box details[open] summary::after {
	content: "−";
	transform: translateY(-50%) scale(1.05);
}
.emg-faq-box .emg-faq-answer {
	margin-top: 8px;
	line-height: 1.6;
}
.emg-faq-box .emg-faq-panel {
	overflow: hidden;
	max-height: 0;
	opacity: 0;
	transform: translateY(-4px);
	transition: max-height 0.28s ease, opacity 0.22s ease, transform 0.22s ease;
	will-change: max-height, opacity, transform;
}
.emg-faq-box details[open] .emg-faq-panel {
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
            function animateFaqToggle(detailsEl, forceOpen) {
                var panel = detailsEl.querySelector('.emg-faq-panel');
                if (!panel) return;
                var willOpen = typeof forceOpen === 'boolean' ? forceOpen : !detailsEl.open;
                if (willOpen) {
                    detailsEl.open = true;
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
                        detailsEl.open = false;
                    }, 280);
                }
            }
            document.querySelectorAll('.emg-faq-box').forEach(function (scope) {
                scope.querySelectorAll('details.emg-faq-item').forEach(function (detailsEl) {
                    var summary = detailsEl.querySelector('summary');
                    var panel = detailsEl.querySelector('.emg-faq-panel');
                    if (!summary || !panel) return;
                    if (detailsEl.open) {
                        panel.style.maxHeight = panel.scrollHeight + 'px';
                        panel.style.opacity = '1';
                        panel.style.transform = 'translateY(0)';
                    } else {
                        panel.style.maxHeight = '0px';
                        panel.style.opacity = '0';
                        panel.style.transform = 'translateY(-4px)';
                    }
                    summary.addEventListener('click', function (e) {
                        e.preventDefault();
                        animateFaqToggle(detailsEl);
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

    public function admin_footer_schema_toggle_script()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'toplevel_page_emg-faq') {
            return;
        }
        ?>
        <script>
        (function () {
            var cb = document.getElementById('emg-faq-manual-schema-enabled');
            var wrap = document.getElementById('emg-faq-schema-field-wrap');
            if (!cb || !wrap) return;
            function sync() { wrap.style.display = cb.checked ? 'block' : 'none'; }
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
        $manual_schema = (string) get_option(self::OPT_MANUAL_SCHEMA, '0');
        ?>
        <div class="wrap">
            <h1>EMG FAQ</h1>
            <p>Global default FAQ when shortcode has <strong>no inner content</strong>. Format: one line per item → <code>Question || Answer</code></p>
            <p>Use <code>{{city}}</code> in defaults; shortcode <code>city="…"</code> replaces it in FAQ + schema.</p>
            <p><strong>Inner shortcode:</strong> Must close with <code>[/emg_faq]</code> (not <code>[emg_faq]</code>). Any HTML or plain text inside is printed; <code>city="…"</code> replaces <code>{{city}}</code>, <code>{city}</code>, <code>{City}</code>. Optional structure: <code>&lt;h3&gt;Q&lt;/h3&gt;&lt;p&gt;A&lt;/p&gt;</code> for FAQ list + auto schema.</p>
            <form method="post" action="options.php">
                <?php settings_fields('emg_faq_settings'); ?>
                <?php settings_errors('emg_faq_settings'); ?>
                <h2>Default FAQ (Global)</h2>
                <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>" rows="16"
                    style="width:100%;"><?php echo esc_textarea($default_faq); ?></textarea>

                <h2 style="margin-top:24px;">FAQ Schema</h2>
                <p>
                    <label>
                        <input type="hidden" name="<?php echo esc_attr(self::OPT_MANUAL_SCHEMA); ?>" value="0" />
                        <input type="checkbox" id="emg-faq-manual-schema-enabled" name="<?php echo esc_attr(self::OPT_MANUAL_SCHEMA); ?>"
                            value="1" <?php checked($manual_schema, '1'); ?> />
                        Use custom default FAQ schema (JSON-LD)
                    </label>
                </p>
                <p class="description">Unchecked: schema is <strong>auto-generated</strong> from the FAQ items shown on the page (no manual JSON field needed). Checked: the textarea below is saved and used when valid; if empty or invalid, auto schema is used.</p>

                <div id="emg-faq-schema-field-wrap" style="<?php echo $manual_schema === '1' ? '' : 'display:none;'; ?>">
                    <h3>Default FAQ Schema (Global JSON-LD)</h3>
                    <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_SCHEMA); ?>" rows="14"
                        style="width:100%;"><?php echo esc_textarea($default_schema); ?></textarea>
                </div>

                <h2 style="margin-top:24px;">FAQ Display Mode</h2>
                <select name="<?php echo esc_attr(self::OPT_DISPLAY_MODE); ?>">
                    <option value="accordion" <?php selected($display_mode, 'accordion'); ?>>Accordion</option>
                    <option value="plain" <?php selected($display_mode, 'plain'); ?>>Plain Question + Answer</option>
                </select>

                <?php submit_button('Save EMG FAQ Settings'); ?>
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

        $atts = shortcode_atts(
            array(
                'title' => '',
                'city' => '',
                'class' => '',
                'mode' => '',
            ),
            $atts,
            'emg_faq'
        );

        $global_display_mode = (string) get_option(self::OPT_DISPLAY_MODE, 'accordion');
        $display_mode = $atts['mode'] !== '' ? (string) $atts['mode'] : $global_display_mode;
        $city_name = !empty($atts['city']) ? sanitize_text_field((string) $atts['city']) : '';

        $inner = $content !== null ? trim((string) $content) : '';

        if ($inner === '') {
            $faq_raw = (string) get_option(self::OPT_DEFAULT_FAQ, '');
            if ($city_name !== '') {
                $faq_raw = $this->replace_city_placeholder($faq_raw, $city_name);
                $atts['title'] = $this->replace_city_placeholder($atts['title'], $city_name);
            }
            $items = $this->parse_faq_lines($faq_raw);
            $manual_schema_on = get_option(self::OPT_MANUAL_SCHEMA, '0') === '1';
            $schema_raw = $this->normalize_schema_json((string) get_option(self::OPT_DEFAULT_SCHEMA, ''));
            if ($city_name !== '') {
                $schema_raw = $this->replace_city_placeholder($schema_raw, $city_name);
            }
            $schema_final = $this->resolve_schema_for_output($items, $schema_raw, $manual_schema_on);
            return $this->render_faq_block($atts['title'], $items, $schema_final, $display_mode, $atts['class']);
        }

        $inner_after_shortcodes = do_shortcode($inner);
        $inner_safe = wp_kses_post($inner_after_shortcodes);
        $items = $this->parse_faq_inner_content($inner_safe);

        $manual_schema_on = get_option(self::OPT_MANUAL_SCHEMA, '0') === '1';
        $schema_raw = $this->normalize_schema_json((string) get_option(self::OPT_DEFAULT_SCHEMA, ''));
        if ($city_name !== '') {
            $schema_raw = $this->replace_city_placeholder($schema_raw, $city_name);
        }

        if (!empty($items)) {
            if ($city_name !== '') {
                foreach ($items as $i => $row) {
                    $items[$i]['q'] = $this->replace_city_placeholder($row['q'], $city_name);
                    $items[$i]['a'] = $this->replace_city_placeholder($row['a'], $city_name);
                }
                $atts['title'] = $this->replace_city_placeholder($atts['title'], $city_name);
            }
            $schema_final = $this->resolve_schema_for_output($items, $schema_raw, $manual_schema_on);
            return $this->render_faq_block($atts['title'], $items, $schema_final, $display_mode, $atts['class']);
        }

        $body = $this->replace_city_placeholder($inner_after_shortcodes, $city_name);
        $body = wp_kses_post($body);
        if (!preg_match('/<(p|div|h[1-6]|ul|ol|li|blockquote|table|figure)\b/i', $body)) {
            $body = wpautop($body);
        }
        if ($city_name !== '') {
            $atts['title'] = $this->replace_city_placeholder($atts['title'], $city_name);
        }
        $schema_final = $this->resolve_schema_for_freeform($schema_raw, $manual_schema_on);

        return $this->render_faq_freeform($atts['title'], $body, $schema_final, $atts['class']);
    }

    /**
     * Freeform inner content: no FAQ pairs → no auto FAQPage; optional manual JSON-LD only.
     */
    private function resolve_schema_for_freeform($schema_raw, $manual_schema_on)
    {
        if (!$manual_schema_on) {
            return '';
        }
        $schema_raw = trim((string) $schema_raw);
        if ($schema_raw !== '' && $this->is_valid_json($schema_raw)) {
            return $schema_raw;
        }
        return '';
    }

    private function render_faq_freeform($title, $body_html, $schema_raw, $extra_class = '')
    {
        $extra_class = is_string($extra_class) ? trim($extra_class) : '';
        $wrapper_class = 'emg-faq-box emg-faq-freeform';
        if ($extra_class !== '') {
            $wrapper_class .= ' ' . implode(' ', array_map('sanitize_html_class', preg_split('/\s+/', $extra_class)));
        }

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
    private function resolve_schema_for_output($items, $schema_raw, $manual_schema_on)
    {
        if (empty($items)) {
            return '';
        }

        if ($manual_schema_on) {
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
     * Parse [emg_faq]...[/emg_faq] inner HTML: <h3>Question</h3> followed by block until next <h3>.
     */
    private function parse_faq_inner_content($html)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return array();
        }

        $items = array();
        if (preg_match_all('/<h3\b[^>]*>(.*?)<\/h3>\s*(.*?)(?=<h3\b|\z)/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $q = trim(wp_strip_all_tags($m[1]));
                $a_block = trim($m[2]);
                if ($q === '' || $a_block === '') {
                    continue;
                }
                $items[] = array(
                    'q' => $q,
                    'a' => $a_block,
                    'answer_is_html' => true,
                );
            }
        }

        return $items;
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
                        <div><strong><?php echo $q_esc; ?></strong></div>
                        <div class="emg-faq-answer">
                            <?php echo $html_ans ? wp_kses_post($item['a']) : esc_html($item['a']); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <details class="emg-faq-item">
                        <summary><?php echo $q_esc; ?></summary>
                        <div class="emg-faq-answer emg-faq-panel">
                            <?php echo $html_ans ? wp_kses_post($item['a']) : esc_html($item['a']); ?>
                        </div>
                    </details>
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
