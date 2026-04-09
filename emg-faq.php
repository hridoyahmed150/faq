<?php
/**
 * Plugin Name: EMG FAQ
 * Description: FAQ via shortcode with optional manual schema or auto FAQPage JSON-LD; inner shortcode HTML supported.
 * Version: 1.2.2
 * Author: Hridoy Ahmed
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMG_FAQ_Plugin
{
    const OPT_DEFAULT_FAQ = 'emg_faq_default_faq_items';
    const OPT_WRAPPER_TEMPLATE = 'emg_faq_wrapper_template';
    const OPT_DEFAULT_SCHEMA = 'emg_faq_default_schema_json';
    const OPT_DISPLAY_MODE = 'emg_faq_display_mode';
    const OPT_ACCORDION_ICON_STYLE = 'emg_faq_accordion_icon_style';
    const OPT_MANUAL_SCHEMA = 'emg_faq_manual_schema_enabled';
    /** When saved as "1", JSON-LD schema output is on (label: "Show schema output"). */
    const OPT_SCHEMA_OUTPUT_ENABLED = 'emg_faq_schema_output_enabled';
    /** @internal Legacy option key (semantically confusing); migrated to OPT_SCHEMA_OUTPUT_ENABLED. */
    const OPT_LEGACY_SCHEMA_OUTPUT_KEY = 'emg_faq_disable_schema_output';

    /** Max bytes of inner shortcode HTML for tag/selector FAQ parsing (ReDoS / CPU guard). */
    const FAQ_INNER_PARSE_MAX_BYTES = 524288;
    const OPT_Q_FONT_SIZE = 'emg_faq_question_font_size';
    const OPT_Q_COLOR = 'emg_faq_question_color';
    const OPT_A_FONT_SIZE = 'emg_faq_answer_font_size';
    const OPT_A_COLOR = 'emg_faq_answer_color';
    const OPT_ITEM_BORDER_WIDTH = 'emg_faq_item_border_width';
    const OPT_ITEM_BORDER_COLOR = 'emg_faq_item_border_color';
    const OPT_ITEM_BORDER_RADIUS = 'emg_faq_item_border_radius';
    const OPT_ITEM_BORDER_SIDES = 'emg_faq_item_border_sides';

    const STYLE_HANDLE = 'emg-faq-frontend';

    private $schema_queue = array();

    private static $faq_assets_enqueued = false;

    private static $accordion_script_needed = false;

    public function __construct()
    {
        add_action('init', array($this, 'maybe_migrate_schema_output_option'), 0);
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
     * One-time copy from legacy option key (emg_faq_disable_schema_output) to emg_faq_schema_output_enabled.
     */
    public function maybe_migrate_schema_output_option()
    {
        if (get_option(self::OPT_SCHEMA_OUTPUT_ENABLED, false) !== false) {
            return;
        }
        $legacy = get_option(self::OPT_LEGACY_SCHEMA_OUTPUT_KEY, false);
        if ($legacy === false) {
            return;
        }
        $on = ($legacy === '1' || $legacy === 1 || $legacy === true);
        update_option(self::OPT_SCHEMA_OUTPUT_ENABLED, $on ? '1' : '0');
        delete_option(self::OPT_LEGACY_SCHEMA_OUTPUT_KEY);
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

        wp_register_style(self::STYLE_HANDLE, false, array(), '1.2.2');
        wp_enqueue_style(self::STYLE_HANDLE);
        wp_add_inline_style(self::STYLE_HANDLE, $this->get_frontend_css());
    }

    private function get_frontend_css()
    {
        $icon_style = $this->sanitize_icon_style(get_option(self::OPT_ACCORDION_ICON_STYLE, 'plusminus'));
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
        $border_width = (int) get_option(self::OPT_ITEM_BORDER_WIDTH, 1);
        if ($border_width < 0 || $border_width > 20) {
            $border_width = 1;
        }
        $border_radius = (int) get_option(self::OPT_ITEM_BORDER_RADIUS, 8);
        if ($border_radius < 0 || $border_radius > 80) {
            $border_radius = 8;
        }
        $border_color = sanitize_hex_color((string) get_option(self::OPT_ITEM_BORDER_COLOR, '#dddddd'));
        if (empty($border_color)) {
            $border_color = '#dddddd';
        }
        $border_sides = get_option(self::OPT_ITEM_BORDER_SIDES, array('top', 'right', 'bottom', 'left'));
        if (!is_array($border_sides) || empty($border_sides)) {
            $border_sides = array('top', 'right', 'bottom', 'left');
        }
        $has_top = in_array('top', $border_sides, true);
        $has_right = in_array('right', $border_sides, true);
        $has_bottom = in_array('bottom', $border_sides, true);
        $has_left = in_array('left', $border_sides, true);
        $border_top = $has_top ? $border_width . 'px solid ' . $border_color : '0';
        $border_right = $has_right ? $border_width . 'px solid ' . $border_color : '0';
        $border_bottom = $has_bottom ? $border_width . 'px solid ' . $border_color : '0';
        $border_left = $has_left ? $border_width . 'px solid ' . $border_color : '0';

        return '


.emg-faq-box {
	max-width: 100%;
	width: 100%;
	margin: 0 auto;
	padding: 0;
	background: transparent;
}

.emg-faq-box .emg-faq-title {
	margin: 0 0 20px;
	font-size: 32px;
	line-height: 1.2;
	font-weight: 700;
}
.emg-faq-box .emg-faq-item {
	border-top: ' . $border_top . ';
	border-right: ' . $border_right . ';
	border-bottom: ' . $border_bottom . ';
	border-left: ' . $border_left . ';
	border-radius: ' . $border_radius . 'px;
	margin-bottom: 10px;
	background: #fff;
	box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
	overflow: hidden;
	padding: 0;
}

.emg-faq-box .emg-faq-question {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	width: 100%;
	padding: 18px 56px 18px 20px;
	cursor: pointer;
	min-height: 52px;
	-webkit-tap-highlight-color: transparent;
	background: transparent;
	border: 0;
	text-align: left;
	position: relative;
	font-weight: 700;
	font-size: ' . $question_font_size . 'px;
	line-height: 145%;
	color: ' . $question_color . ';
}

.emg-faq-box .emg-faq-question:hover {
	background: #fafafa;
}


.emg-faq-box .emg-faq-question-text {
	flex: 1;
	font-weight: 700;
	font-size: ' . $question_font_size . 'px;
	line-height: 145%;
	color: ' . $question_color . ';
}

.emg-faq-box .emg-faq-question::before {
	content: "";
	position: absolute;
	top: 50%;
	right: 28px;
	transform: translate(50%, -50%);
	transition: background 200ms ease, border-color 200ms ease, transform 200ms ease;
}

.emg-faq-box .emg-faq-question::after {
	content: "";
	position: absolute;
	top: 50%;
	right: 28px;
	transition: opacity 200ms ease, transform 200ms ease, color 200ms ease;
}
' . ($icon_style === 'arrow' ? '
.emg-faq-box .emg-faq-question::before {
	width: 28px;
	height: 28px;
	border: 2px solid #000;
	border-radius: 50%;
	background: #fff;
}

.emg-faq-box .emg-faq-question::after {
	width: 9px;
	height: 9px;
	border-right: 2px solid #000;
	border-bottom: 2px solid #000;
	transform: translate(40%, -60%) rotate(45deg);
}

.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-question::before {
	background: #000;
}

.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-question::after {
	border-right-color: #fff;
	border-bottom-color: #fff;
	transform: translate(40%, -40%) rotate(-135deg);
}
' : '
.emg-faq-box .emg-faq-question::before {
	width: 28px;
	height: 28px;
	border: 2px solid #000;
	border-radius: 50%;
	background: #fff;
}

.emg-faq-box .emg-faq-question::after {
	content: "+";
	transform: translate(50%, -50%);
	color: #000;
	font-size: 18px;
	line-height: 1;
	font-weight: 700;
}

.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-question::before {
	background: #000;
}

.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-question::after {
	content: "−";
	opacity: 1;
	transform: translate(50%, -52%);
	color: #fff;
}
') . '

.emg-faq-box .emg-faq-answer {
	color: ' . $answer_color . ';
	line-height: 170%;
	font-size: ' . $answer_font_size . 'px;
	padding: 0 20px;
	margin-top: 0;
}

.emg-faq-box .emg-faq-answer p:first-child {
	margin-top: 0;
}

.emg-faq-box .emg-faq-answer p:last-child {
	margin-bottom: 0;
}

.emg-faq-box .emg-faq-answer ul {
	margin: 10px 0 0;
	padding-left: 20px;
}

.emg-faq-box .emg-faq-panel {
	overflow: hidden;
	max-height: 0;
	opacity: 0;
	transition: max-height 0.3s ease, opacity 0.2s ease;
	will-change: max-height, opacity;
}

.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-panel {
	opacity: 1;
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
                function clearPanelTimer(panel) {
                    if (!panel) return;
                    if (panel._emgTimer) {
                        window.clearTimeout(panel._emgTimer);
                        panel._emgTimer = null;
                    }
                }

                function onPanelTransitionEnd(panel, cb) {
                    if (!panel) return;
                    var done = false;
                    var finish = function () {
                        if (done) return;
                        done = true;
                        panel.removeEventListener('transitionend', handle);
                        cb();
                    };
                    var handle = function (ev) {
                        if (ev.target !== panel || ev.propertyName !== 'max-height') return;
                        finish();
                    };
                    panel.addEventListener('transitionend', handle);
                    panel._emgTimer = window.setTimeout(finish, 420);
                }

                function animateFaqToggle(itemEl, forceOpen) {
                    var panel = itemEl.querySelector('.emg-faq-panel');
                    var trigger = itemEl.querySelector('.emg-faq-question');
                    if (!panel) return;
                    if (itemEl.dataset.animating === '1') return;

                    var willOpen = typeof forceOpen === 'boolean' ? forceOpen : !itemEl.classList.contains('is-open');
                    itemEl.dataset.animating = '1';
                    clearPanelTimer(panel);

                    if (willOpen) {
                        itemEl.classList.add('is-open');
                        if (trigger) {
                            trigger.setAttribute('aria-expanded', 'true');
                        }
                        panel.hidden = false;
                        panel.style.maxHeight = '0px';
                        panel.style.opacity = '0';
                        window.requestAnimationFrame(function () {
                            window.requestAnimationFrame(function () {
                                panel.style.maxHeight = panel.scrollHeight + 'px';
                                panel.style.opacity = '1';
                                onPanelTransitionEnd(panel, function () {
                                    clearPanelTimer(panel);
                                    if (itemEl.classList.contains('is-open')) {
                                        // Keep explicit height to avoid close jank from none->0 transition.
                                        panel.style.maxHeight = panel.scrollHeight + 'px';
                                    }
                                    itemEl.dataset.animating = '0';
                                });
                            });
                        });
                    } else {
                        if (panel.style.maxHeight === 'none' || panel.style.maxHeight === '') {
                            panel.style.maxHeight = panel.scrollHeight + 'px';
                        }
                        panel.style.opacity = '1';
                        window.requestAnimationFrame(function () {
                            panel.style.maxHeight = '0px';
                            panel.style.opacity = '0';
                        });
                        onPanelTransitionEnd(panel, function () {
                            clearPanelTimer(panel);
                            itemEl.classList.remove('is-open');
                            if (trigger) {
                                trigger.setAttribute('aria-expanded', 'false');
                            }
                            panel.hidden = true;
                            itemEl.dataset.animating = '0';
                        });
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
        $this->register_emg_faq_setting(self::OPT_DEFAULT_FAQ, array($this, 'sanitize_faq_items'));
        $this->register_emg_faq_setting(self::OPT_WRAPPER_TEMPLATE, array($this, 'sanitize_wrapper_template'));
        $this->register_emg_faq_setting(self::OPT_DEFAULT_SCHEMA, array($this, 'sanitize_schema_text'));
        $this->register_emg_faq_setting(self::OPT_DISPLAY_MODE, array($this, 'sanitize_display_mode'));
        $this->register_emg_faq_setting(self::OPT_ACCORDION_ICON_STYLE, array($this, 'sanitize_icon_style'));
        $this->register_emg_faq_setting(self::OPT_MANUAL_SCHEMA, array($this, 'sanitize_manual_schema_flag'));
        $this->register_emg_faq_setting(self::OPT_SCHEMA_OUTPUT_ENABLED, array($this, 'sanitize_manual_schema_flag'));
        $this->register_emg_faq_setting(self::OPT_Q_FONT_SIZE, array($this, 'sanitize_font_size'));
        $this->register_emg_faq_setting(self::OPT_Q_COLOR, array($this, 'sanitize_color'));
        $this->register_emg_faq_setting(self::OPT_A_FONT_SIZE, array($this, 'sanitize_font_size'));
        $this->register_emg_faq_setting(self::OPT_A_COLOR, array($this, 'sanitize_color'));
        $this->register_emg_faq_setting(self::OPT_ITEM_BORDER_WIDTH, array($this, 'sanitize_border_width'));
        $this->register_emg_faq_setting(self::OPT_ITEM_BORDER_COLOR, array($this, 'sanitize_border_color'));
        $this->register_emg_faq_setting(self::OPT_ITEM_BORDER_RADIUS, array($this, 'sanitize_border_radius'));
        $this->register_emg_faq_setting(self::OPT_ITEM_BORDER_SIDES, array($this, 'sanitize_border_sides'));
    }

    /**
     * @param string   $option
     * @param callable $sanitize_callback
     */
    private function register_emg_faq_setting($option, $sanitize_callback)
    {
        register_setting(
            'emg_faq_settings',
            $option,
            array(
                'sanitize_callback' => $sanitize_callback,
                'show_in_rest' => false,
            )
        );
    }

    public function sanitize_text($value)
    {
        return is_string($value) ? trim($value) : '';
    }

    public function sanitize_faq_items($value)
    {
        if (!is_array($value)) {
            return array();
        }

        $out = array();
        foreach ($value as $row) {
            if (!is_array($row)) {
                continue;
            }

            $q_raw = isset($row['question']) ? (string) $row['question'] : '';
            $a_raw = isset($row['answer']) ? (string) $row['answer'] : '';
            $q = trim(wp_kses_post($q_raw));
            $a = trim(wp_kses_post($a_raw));
            if ($q === '' || $a === '') {
                continue;
            }

            $out[] = array(
                'question' => $q,
                'answer' => $a,
            );
        }

        return $out;
    }

    public function sanitize_wrapper_template($value)
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return '';
        }

        return wp_kses_post($value);
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

    public function sanitize_icon_style($value)
    {
        $value = is_string($value) ? strtolower(trim($value)) : 'plusminus';
        return in_array($value, array('plusminus', 'arrow'), true) ? $value : 'plusminus';
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

    public function sanitize_border_width($value)
    {
        $size = (int) $value;
        if ($size < 0) {
            $size = 0;
        }
        if ($size > 20) {
            $size = 20;
        }
        return (string) $size;
    }

    public function sanitize_border_radius($value)
    {
        $size = (int) $value;
        if ($size < 0) {
            $size = 0;
        }
        if ($size > 80) {
            $size = 80;
        }
        return (string) $size;
    }

    public function sanitize_border_color($value)
    {
        $color = sanitize_hex_color((string) $value);
        return $color ? $color : '#dddddd';
    }

    public function sanitize_border_sides($value)
    {
        $allowed = array('top', 'right', 'bottom', 'left');
        if (!is_array($value)) {
            return $allowed;
        }

        $sides = array();
        foreach ($value as $side) {
            $side = strtolower(trim((string) $side));
            if (in_array($side, $allowed, true)) {
                $sides[] = $side;
            }
        }
        $sides = array_values(array_unique($sides));
        if (empty($sides)) {
            return $allowed;
        }
        return $sides;
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
                function syncSchema() { wrap.style.display = cb.checked ? 'none' : 'block'; }
                cb.addEventListener('change', syncSchema);
                syncSchema();

                var modeSelect = document.getElementById('emg-faq-display-mode');
                var iconWrap = document.getElementById('emg-faq-icon-style-wrap');
                function syncIconStyleVisibility() {
                    if (!modeSelect || !iconWrap) return;
                    iconWrap.style.display = modeSelect.value === 'accordion' ? 'block' : 'none';
                }
                if (modeSelect && iconWrap) {
                    modeSelect.addEventListener('change', syncIconStyleVisibility);
                    syncIconStyleVisibility();
                }

                var list = document.getElementById('emg-faq-items-list');
                var addBtn = document.getElementById('emg-faq-add-item');
                var tpl = document.getElementById('emg-faq-item-template');
                if (!list || !addBtn || !tpl) return;

                var nextIndex = 0;
                list.querySelectorAll('textarea[name]').forEach(function (el) {
                    var m = el.name.match(/\[(\d+)\]\[(question|answer)\]$/);
                    if (!m) return;
                    var idx = parseInt(m[1], 10);
                    if (!isNaN(idx) && idx >= nextIndex) {
                        nextIndex = idx + 1;
                    }
                });

                list.addEventListener('click', function (e) {
                    var btn = e.target.closest('.emg-faq-remove-item');
                    if (!btn) return;
                    var row = btn.closest('.emg-faq-admin-item');
                    if (row) row.remove();
                });

                addBtn.addEventListener('click', function () {
                    var html = tpl.innerHTML.replace(/__INDEX__/g, String(nextIndex));
                    nextIndex += 1;
                    var wrapEl = document.createElement('div');
                    wrapEl.innerHTML = html;
                    while (wrapEl.firstChild) {
                        list.appendChild(wrapEl.firstChild);
                    }
                });
            })();
        </script>
        <?php
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $default_faq = get_option(self::OPT_DEFAULT_FAQ, array());
        if (!is_array($default_faq)) {
            $default_faq = array();
        }
        $wrapper_template = (string) get_option(self::OPT_WRAPPER_TEMPLATE, '');
        $default_schema = (string) get_option(self::OPT_DEFAULT_SCHEMA, '');
        $display_mode = (string) get_option(self::OPT_DISPLAY_MODE, 'accordion');
        $icon_style = (string) get_option(self::OPT_ACCORDION_ICON_STYLE, 'plusminus');
        $auto_schema = (string) get_option(self::OPT_MANUAL_SCHEMA, '1');
        $schema_output_enabled = (string) get_option(self::OPT_SCHEMA_OUTPUT_ENABLED, '1');
        $question_font_size = (string) get_option(self::OPT_Q_FONT_SIZE, '16');
        $question_color = (string) get_option(self::OPT_Q_COLOR, '#111827');
        $answer_font_size = (string) get_option(self::OPT_A_FONT_SIZE, '16');
        $answer_color = (string) get_option(self::OPT_A_COLOR, '#374151');
        $item_border_width = (string) get_option(self::OPT_ITEM_BORDER_WIDTH, '1');
        $item_border_color = (string) get_option(self::OPT_ITEM_BORDER_COLOR, '#dddddd');
        $item_border_radius = (string) get_option(self::OPT_ITEM_BORDER_RADIUS, '8');
        $item_border_sides = get_option(self::OPT_ITEM_BORDER_SIDES, array('top', 'right', 'bottom', 'left'));
        if (!is_array($item_border_sides)) {
            $item_border_sides = array('top', 'right', 'bottom', 'left');
        }
        ?>
        <div class="wrap">
            <h1>EMG FAQ</h1>

            <h2 style="margin-top:20px;">Shortcode Examples</h2>
            <p><h3>1) FAQ list:</h3><code>[emg_faq class="faq-box"]</code>
            <div><h3>2) FAQ list with city replacement:</h3>
                <p>In <strong>FAQ Items</strong>, set question and answer with <code>{{city}}</code> placeholder.</p>
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
                <p>Optional (selector mode only): <code>strip_tags="true"</code> or bare <code>stripe</code> strips HTML from parsed Q/A (plain text display and schema). Default keeps safe HTML (<code>p</code>, <code>ul</code>, <code>li</code>, <code>strong</code>, etc.). Use <code>strip_tags="false"</code> when both attributes appear.</p>
            </div>


            <form method="post" action="options.php">
                <?php settings_fields('emg_faq_settings'); ?>
                <?php settings_errors('emg_faq_settings'); ?>
                <h2>FAQ Items</h2>
                <p>Each FAQ has its own Question and Answer fields. HTML is allowed in both fields (like <code>h1-h6</code>, <code>p</code>, <code>ul</code>, <code>li</code>).</p>
                <div id="emg-faq-items-list">
                    <?php if (empty($default_faq)): ?>
                        <div class="emg-faq-admin-item" style="border:1px solid #dcdcde;padding:14px;margin-bottom:12px;background:#fff;">
                            <p style="margin:0 0 8px;">
                                <label><strong>Question</strong></label><br />
                                <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>[0][question]" rows="3" style="width:100%;"></textarea>
                            </p>
                            <p style="margin:0 0 8px;">
                                <label><strong>Answer</strong></label><br />
                                <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>[0][answer]" rows="5" style="width:100%;"></textarea>
                            </p>
                            <button type="button" class="button emg-faq-remove-item">Remove</button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($default_faq as $idx => $row): ?>
                            <?php
                            $q = isset($row['question']) ? (string) $row['question'] : '';
                            $a = isset($row['answer']) ? (string) $row['answer'] : '';
                            ?>
                            <div class="emg-faq-admin-item" style="border:1px solid #dcdcde;padding:14px;margin-bottom:12px;background:#fff;">
                                <p style="margin:0 0 8px;">
                                    <label><strong>Question</strong></label><br />
                                    <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>[<?php echo esc_attr((string) $idx); ?>][question]" rows="3" style="width:100%;"><?php echo esc_textarea($q); ?></textarea>
                                </p>
                                <p style="margin:0 0 8px;">
                                    <label><strong>Answer</strong></label><br />
                                    <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>[<?php echo esc_attr((string) $idx); ?>][answer]" rows="5" style="width:100%;"><?php echo esc_textarea($a); ?></textarea>
                                </p>
                                <button type="button" class="button emg-faq-remove-item">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p><button type="button" id="emg-faq-add-item" class="button button-secondary">+ Add FAQ</button></p>
                <script type="text/template" id="emg-faq-item-template">
                    <div class="emg-faq-admin-item" style="border:1px solid #dcdcde;padding:14px;margin-bottom:12px;background:#fff;">
                        <p style="margin:0 0 8px;">
                            <label><strong>Question</strong></label><br />
                            <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>[__INDEX__][question]" rows="3" style="width:100%;"></textarea>
                        </p>
                        <p style="margin:0 0 8px;">
                            <label><strong>Answer</strong></label><br />
                            <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>[__INDEX__][answer]" rows="5" style="width:100%;"></textarea>
                        </p>
                        <button type="button" class="button emg-faq-remove-item">Remove</button>
                    </div>
                </script>

                <h2 style="margin-top:24px;">FAQ Wrapper Template (Optional)</h2>
                <p>Use <code>{{faq_items}}</code> where FAQ items should be injected.</p>
                <textarea name="<?php echo esc_attr(self::OPT_WRAPPER_TEMPLATE); ?>" rows="8"
                    style="width:100%;"><?php echo esc_textarea($wrapper_template); ?></textarea>

                <h2 style="margin-top:24px;">Schema Settings</h2>
                <p>
                    <label>
                        <input type="hidden" name="<?php echo esc_attr(self::OPT_SCHEMA_OUTPUT_ENABLED); ?>" value="0" />
                        <input type="checkbox"
                            name="<?php echo esc_attr(self::OPT_SCHEMA_OUTPUT_ENABLED); ?>" value="1" <?php checked($schema_output_enabled, '1'); ?> />
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

                <div id="emg-faq-schema-field-wrap" style="<?php echo esc_attr($auto_schema === '1' ? 'display:none;' : ''); ?>">
                    <h3>Custom Schema JSON (Optional)</h3>
                    <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_SCHEMA); ?>" rows="14"
                        style="width:100%;"><?php echo esc_textarea($default_schema); ?></textarea>
                </div>

                <h2 style="margin-top:24px;">FAQ View Style</h2>
                <select id="emg-faq-display-mode" name="<?php echo esc_attr(self::OPT_DISPLAY_MODE); ?>">
                    <option value="accordion" <?php selected($display_mode, 'accordion'); ?>>Accordion</option>
                    <option value="plain" <?php selected($display_mode, 'plain'); ?>>Plain (Question and Answer)</option>
                </select>
                <div id="emg-faq-icon-style-wrap" style="<?php echo esc_attr(($display_mode === 'accordion' ? '' : 'display:none;') . ' margin-top:12px;'); ?>">
                    <label for="emg-faq-icon-style"><strong>Accordion icon style:</strong></label><br />
                    <select id="emg-faq-icon-style" name="<?php echo esc_attr(self::OPT_ACCORDION_ICON_STYLE); ?>">
                        <option value="plusminus" <?php selected($icon_style, 'plusminus'); ?>>Plus / Minus</option>
                        <option value="arrow" <?php selected($icon_style, 'arrow'); ?>>Arrow</option>
                    </select>
                </div>

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
                <h2 style="margin-top:24px;">FAQ Item Border Style</h2>
                <p>
                    <label>
                        Border width (px):
                        <input type="number" min="0" max="20" step="1"
                            name="<?php echo esc_attr(self::OPT_ITEM_BORDER_WIDTH); ?>"
                            value="<?php echo esc_attr($item_border_width); ?>" />
                    </label>
                </p>
                <p>
                    <label>
                        Border color:
                        <input type="color"
                            name="<?php echo esc_attr(self::OPT_ITEM_BORDER_COLOR); ?>"
                            value="<?php echo esc_attr($item_border_color); ?>" />
                    </label>
                </p>
                <p>
                    <label>
                        Border radius (px):
                        <input type="number" min="0" max="80" step="1"
                            name="<?php echo esc_attr(self::OPT_ITEM_BORDER_RADIUS); ?>"
                            value="<?php echo esc_attr($item_border_radius); ?>" />
                    </label>
                </p>
                <p>
                    Border sides:
                    <label style="margin-right:12px;">
                        <input type="checkbox" name="<?php echo esc_attr(self::OPT_ITEM_BORDER_SIDES); ?>[]" value="top" <?php checked(in_array('top', $item_border_sides, true)); ?> />
                        Top
                    </label>
                    <label style="margin-right:12px;">
                        <input type="checkbox" name="<?php echo esc_attr(self::OPT_ITEM_BORDER_SIDES); ?>[]" value="right" <?php checked(in_array('right', $item_border_sides, true)); ?> />
                        Right
                    </label>
                    <label style="margin-right:12px;">
                        <input type="checkbox" name="<?php echo esc_attr(self::OPT_ITEM_BORDER_SIDES); ?>[]" value="bottom" <?php checked(in_array('bottom', $item_border_sides, true)); ?> />
                        Bottom
                    </label>
                    <label style="margin-right:12px;">
                        <input type="checkbox" name="<?php echo esc_attr(self::OPT_ITEM_BORDER_SIDES); ?>[]" value="left" <?php checked(in_array('left', $item_border_sides, true)); ?> />
                        Left
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
        $schema_output_enabled = get_option(self::OPT_SCHEMA_OUTPUT_ENABLED, '1') === '1';

        $raw_atts = is_array($atts) ? $atts : array();
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
                'strip_tags' => '',
                'stripe' => '',
            ),
            $atts,
            'emg_faq'
        );

        $atts['title'] = sanitize_text_field((string) $atts['title']);

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
            $items = $this->get_default_faq_items();
            if ($city_name !== '') {
                $atts['title'] = $this->replace_city_placeholder($atts['title'], $city_name);
                foreach ($items as $i => $row) {
                    $items[$i]['q'] = $this->replace_city_placeholder($row['q'], $city_name);
                    $items[$i]['a'] = $this->replace_city_placeholder($row['a'], $city_name);
                }
            }
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
            $selector_strip_plain = $this->enclosing_shortcode_wants_strip_tags($raw_atts);
            $items_from_tags = $this->parse_faq_inner_content_by_selectors(
                $body,
                $atts['question_selector'],
                $atts['answer_selector'],
                $selector_strip_plain
            );
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
        <div class="<?php echo esc_attr($wrapper_class); ?>">
            <?php if ($title !== ''): ?>
                <h2 class="emg-faq-title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <div class="emg-faq-freeform-body"><?php echo $body_html; ?></div>
        </div>
        <?php
        $this->queue_schema($schema_raw);
        return $this->apply_wrapper_template(ob_get_clean());
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
            $q = isset($item['q']) ? $this->flatten_html_text((string) $item['q']) : '';
            $a = isset($item['a']) ? $this->flatten_html_text((string) $item['a']) : '';
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

        return $this->faq_json_encode_for_ld($data);
    }

    /**
     * JSON-LD inside a script tag: never pass only JSON_UNESCAPED_* to wp_json_encode — that skips
     * JSON_HEX_* and allows breaking out of &lt;script type="application/ld+json"&gt; via &lt;/script&gt; in strings.
     */
    private function faq_json_encode_for_ld($data)
    {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        return wp_json_encode($data, $flags, 512);
    }

    private function flatten_html_text($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        // Keep visible separation when HTML blocks/lists are flattened.
        $value = preg_replace('/<\/?(p|div|li|ul|ol|br|h[1-6])\b[^>]*>/i', ' ', $value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = wp_strip_all_tags($value, true);
        $value = preg_replace('/\s+/u', ' ', (string) $value);

        return trim((string) $value);
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
        if (strlen($html) > self::FAQ_INNER_PARSE_MAX_BYTES) {
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

    /**
     * Enclosing shortcode only: strip HTML from selector-parsed Q/A (plain text + esc_html output).
     * Prefers {@see strip_tags}; {@see stripe} is a supported alias (also works as a bare flag).
     */
    private function enclosing_shortcode_wants_strip_tags(array $raw_atts)
    {
        if (array_key_exists('strip_tags', $raw_atts)) {
            $v = $raw_atts['strip_tags'];
            if ($v === '' || $v === null) {
                return true;
            }

            return $this->shortcode_string_is_truthy($v);
        }
        if (array_key_exists('stripe', $raw_atts)) {
            $v = $raw_atts['stripe'];
            if ($v === '' || $v === null) {
                return true;
            }

            return $this->shortcode_string_is_truthy($v);
        }

        return false;
    }

    /**
     * @param mixed $value
     */
    private function shortcode_string_is_truthy($value)
    {
        $v = strtolower(trim((string) $value));
        if (in_array($v, array('0', 'false', 'no', 'off'), true)) {
            return false;
        }
        if (in_array($v, array('1', 'yes', 'true', 'on'), true)) {
            return true;
        }

        return false;
    }

    private function parse_faq_inner_content_by_selectors($html, $question_selector, $answer_selector, $strip_tags = false)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return array();
        }
        if (strlen($html) > self::FAQ_INNER_PARSE_MAX_BYTES) {
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
                $q_raw = (string) $m[2];
                $question = $strip_tags ? trim(wp_strip_all_tags($q_raw)) : trim(wp_kses_post($q_raw));
                if (trim(wp_strip_all_tags($question, true)) === '') {
                    continue;
                }
                $block = (string) $m[3];
                $answer = '';
                if (preg_match('/<([a-z0-9]+)\b[^>]*' . $aPattern . '[^>]*>(.*?)<\/\1>/is', $block, $am)) {
                    $a_raw = (string) $am[2];
                    $answer = $strip_tags ? trim(wp_strip_all_tags($a_raw)) : trim(wp_kses_post($a_raw));
                } else {
                    $answer = $strip_tags ? trim(wp_strip_all_tags($block)) : trim(wp_kses_post($block));
                }
                if (trim(wp_strip_all_tags($answer, true)) === '') {
                    continue;
                }
                if ($strip_tags) {
                    $items[] = array('q' => $question, 'a' => $answer);
                } else {
                    $items[] = array(
                        'q' => $question,
                        'a' => $answer,
                        'question_is_html' => true,
                        'answer_is_html' => true,
                    );
                }
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

    /**
     * Decode then re-encode with hex-escaped &lt; &gt; &amp; quotes so strings cannot close the JSON-LD script tag.
     */
    private function ld_json_string_for_script($json_string)
    {
        $json_string = trim((string) $json_string);
        if ($json_string === '') {
            return '';
        }
        $decoded = json_decode($json_string, true, 512);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }

        return $this->faq_json_encode_for_ld($decoded);
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

    private function get_default_faq_items()
    {
        $raw = get_option(self::OPT_DEFAULT_FAQ, array());
        $items = array();

        if (is_array($raw)) {
            foreach ($raw as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $q = isset($row['question']) ? trim(wp_kses_post((string) $row['question'])) : '';
                $a = isset($row['answer']) ? trim(wp_kses_post((string) $row['answer'])) : '';
                if ($q === '' || $a === '') {
                    continue;
                }
                $items[] = array(
                    'q' => $q,
                    'a' => $a,
                    'answer_is_html' => true,
                    'question_is_html' => true,
                );
            }
            return $items;
        }

        // Legacy fallback if stored value is still plain text list.
        return $this->parse_faq_lines((string) $raw);
    }

    private function apply_wrapper_template($faq_html)
    {
        $faq_html = (string) $faq_html;
        $template = trim((string) get_option(self::OPT_WRAPPER_TEMPLATE, ''));
        if ($template !== '') {
            $template = wp_kses_post($template);
        }
        if ($template === '') {
            return $faq_html;
        }

        $tokenized = str_replace(
            array('{{faq_items}}', '{{faq_content}}', '{{faq}}'),
            $faq_html,
            $template
        );
        if ($tokenized !== $template) {
            return $tokenized;
        }

        return $template . $faq_html;
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
        <div class="<?php echo esc_attr($wrapper_class); ?>">
            <?php if ($title !== ''): ?>
                <h2 class="emg-faq-title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
                <?php
                $q_esc = !empty($item['question_is_html']) ? wp_kses_post($item['q']) : esc_html($item['q']);
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
        </div>
        <?php
        if (!$is_plain) {
            $this->request_accordion_script();
        }

        $this->queue_schema($schema_raw);

        return $this->apply_wrapper_template(ob_get_clean());
    }

    private function queue_schema($schema_raw)
    {
        $safe = $this->ld_json_string_for_script($schema_raw);
        if ($safe === '') {
            return;
        }

        $key = md5($safe);
        $this->schema_queue[$key] = $safe;
    }

    public function output_queued_schema()
    {
        if (empty($this->schema_queue)) {
            return;
        }

        foreach ($this->schema_queue as $safe_ld_json) {
            echo '<script type="application/ld+json">' . $safe_ld_json . '</script>' . "\n";
        }
    }
}

new EMG_FAQ_Plugin();
