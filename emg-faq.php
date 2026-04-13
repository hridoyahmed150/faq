<?php
/**
 * Plugin Name: EMG FAQ
 * Description: FAQ via shortcode with optional manual schema or auto FAQPage JSON-LD; inner shortcode HTML supported.
 * Version: 2.3.9
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
    /** "1" = split FAQ list into two columns on large screens (first half / second half). */
    const OPT_TWO_COLUMN_LAYOUT = 'emg_faq_two_column_layout';

    /** Frontend scope for all plugin CSS and custom CSS (user-facing alias: scoped FAQ output). */
    const SCOPE_ROOT_CLASS = 'emg-faq-root';

    const OPT_OPEN_FIRST = 'emg_faq_open_first_item';
    const OPT_MULTIPLE_OPEN = 'emg_faq_accordion_multiple_open';
    const OPT_ANIM_MS = 'emg_faq_accordion_anim_ms';
    const OPT_ICON_POSITION = 'emg_faq_icon_position';
    const OPT_ICON_SIZE = 'emg_faq_icon_size_px';
    const OPT_ICON_COLOR = 'emg_faq_icon_color';
    const OPT_ICON_COLOR_OPEN = 'emg_faq_icon_color_open';
    /** "1" = rounded ring border around icon (chevron box / plus-minus circle); "0" = icon only. */
    const OPT_ICON_RING_BORDER = 'emg_faq_icon_ring_border';
    const OPT_HEADING_WEIGHT = 'emg_faq_heading_font_weight';
    const OPT_HEADING_SIZE_T = 'emg_faq_heading_font_size_tablet';
    const OPT_HEADING_SIZE_M = 'emg_faq_heading_font_size_mobile';
    const OPT_CONTENT_LH = 'emg_faq_content_line_height';
    const OPT_CONTENT_FS_T = 'emg_faq_content_font_size_tablet';
    const OPT_CONTENT_FS_M = 'emg_faq_content_font_size_mobile';
    const OPT_ITEM_GAP = 'emg_faq_item_gap_px';
    const OPT_ITEM_BG = 'emg_faq_item_bg_color';
    const OPT_ITEM_BG_OPEN = 'emg_faq_item_bg_open_color';
    const OPT_Q_HOVER_BG = 'emg_faq_question_hover_bg';
    const OPT_CONTAINER_BG = 'emg_faq_container_bg';
    const OPT_CUSTOM_CSS = 'emg_faq_custom_css';
    /** Gap between columns in two-column layout (px). 0 = default 20px. */
    const OPT_COLUMN_GAP = 'emg_faq_column_gap_px';
    /** "1" = animated panel open/close; "0" = instant. */
    const OPT_SMOOTH_PANEL_ANIM = 'emg_faq_smooth_panel_animation';

    const OPT_CONTENT_LH_T = 'emg_faq_content_line_height_tablet';
    const OPT_CONTENT_LH_M = 'emg_faq_content_line_height_mobile';

    /** Container padding TRBL × desktop / tablet / mobile (px). */
    const OPT_PAD_C_TOP_D = 'emg_faq_pad_c_top_d';
    const OPT_PAD_C_TOP_T = 'emg_faq_pad_c_top_t';
    const OPT_PAD_C_TOP_M = 'emg_faq_pad_c_top_m';
    const OPT_PAD_C_RIGHT_D = 'emg_faq_pad_c_right_d';
    const OPT_PAD_C_RIGHT_T = 'emg_faq_pad_c_right_t';
    const OPT_PAD_C_RIGHT_M = 'emg_faq_pad_c_right_m';
    const OPT_PAD_C_BOTTOM_D = 'emg_faq_pad_c_bottom_d';
    const OPT_PAD_C_BOTTOM_T = 'emg_faq_pad_c_bottom_t';
    const OPT_PAD_C_BOTTOM_M = 'emg_faq_pad_c_bottom_m';
    const OPT_PAD_C_LEFT_D = 'emg_faq_pad_c_left_d';
    const OPT_PAD_C_LEFT_T = 'emg_faq_pad_c_left_t';
    const OPT_PAD_C_LEFT_M = 'emg_faq_pad_c_left_m';
    /** Question row (button) padding TRBL × breakpoint. */
    const OPT_PAD_Q_TOP_D = 'emg_faq_pad_q_top_d';
    const OPT_PAD_Q_TOP_T = 'emg_faq_pad_q_top_t';
    const OPT_PAD_Q_TOP_M = 'emg_faq_pad_q_top_m';
    const OPT_PAD_Q_RIGHT_D = 'emg_faq_pad_q_right_d';
    const OPT_PAD_Q_RIGHT_T = 'emg_faq_pad_q_right_t';
    const OPT_PAD_Q_RIGHT_M = 'emg_faq_pad_q_right_m';
    const OPT_PAD_Q_BOTTOM_D = 'emg_faq_pad_q_bottom_d';
    const OPT_PAD_Q_BOTTOM_T = 'emg_faq_pad_q_bottom_t';
    const OPT_PAD_Q_BOTTOM_M = 'emg_faq_pad_q_bottom_m';
    const OPT_PAD_Q_LEFT_D = 'emg_faq_pad_q_left_d';
    const OPT_PAD_Q_LEFT_T = 'emg_faq_pad_q_left_t';
    const OPT_PAD_Q_LEFT_M = 'emg_faq_pad_q_left_m';
    /** Answer panel padding TRBL × breakpoint. */
    const OPT_PAD_A_TOP_D = 'emg_faq_pad_a_top_d';
    const OPT_PAD_A_TOP_T = 'emg_faq_pad_a_top_t';
    const OPT_PAD_A_TOP_M = 'emg_faq_pad_a_top_m';
    const OPT_PAD_A_RIGHT_D = 'emg_faq_pad_a_right_d';
    const OPT_PAD_A_RIGHT_T = 'emg_faq_pad_a_right_t';
    const OPT_PAD_A_RIGHT_M = 'emg_faq_pad_a_right_m';
    const OPT_PAD_A_BOTTOM_D = 'emg_faq_pad_a_bottom_d';
    const OPT_PAD_A_BOTTOM_T = 'emg_faq_pad_a_bottom_t';
    const OPT_PAD_A_BOTTOM_M = 'emg_faq_pad_a_bottom_m';
    const OPT_PAD_A_LEFT_D = 'emg_faq_pad_a_left_d';
    const OPT_PAD_A_LEFT_T = 'emg_faq_pad_a_left_t';
    const OPT_PAD_A_LEFT_M = 'emg_faq_pad_a_left_m';

    const STYLE_HANDLE = 'emg-faq-frontend';

    private $schema_queue = array();

    private static $faq_assets_enqueued = false;

    private static $accordion_script_needed = false;

    public function __construct()
    {
        add_action('init', array($this, 'maybe_migrate_schema_output_option'), 0);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'maybe_enqueue_admin_preview_assets'));
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

        wp_register_style(self::STYLE_HANDLE, false, array(), '2.3.9');
        wp_enqueue_style(self::STYLE_HANDLE);
        $inline = $this->get_frontend_css();
        $custom = $this->get_frontend_custom_css();
        if ($custom !== '') {
            $inline .= "\n" . $custom;
        }
        wp_add_inline_style(self::STYLE_HANDLE, $inline);
    }

    private function get_frontend_css()
    {
        $icon_style = $this->sanitize_icon_style(get_option(self::OPT_ACCORDION_ICON_STYLE, 'plusminus'));
        $use_line_icon = ($icon_style === 'chevron');
        $icon_ring = get_option(self::OPT_ICON_RING_BORDER, '1') === '1';
        $smooth_panel = get_option(self::OPT_SMOOTH_PANEL_ANIM, '1') === '1';
        $question_font_size = (int) get_option(self::OPT_Q_FONT_SIZE, 16);
        if ($question_font_size < 8 || $question_font_size > 72) {
            $question_font_size = 16;
        }
        $question_color = $this->sanitize_css_color_flexible((string) get_option(self::OPT_Q_COLOR, '#111827'), '#111827');
        $answer_font_size = (int) get_option(self::OPT_A_FONT_SIZE, 16);
        if ($answer_font_size < 8 || $answer_font_size > 72) {
            $answer_font_size = 16;
        }
        $answer_color = $this->sanitize_css_color_flexible((string) get_option(self::OPT_A_COLOR, '#374151'), '#374151');
        $border_radius = (int) get_option(self::OPT_ITEM_BORDER_RADIUS, 8);
        if ($border_radius < 0 || $border_radius > 80) {
            $border_radius = 8;
        }

        $anim_ms = (int) get_option(self::OPT_ANIM_MS, 300);
        if ($anim_ms < 100) {
            $anim_ms = 100;
        }
        if ($anim_ms > 1500) {
            $anim_ms = 1500;
        }

        $css = '


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
	border: 0;
	border-radius: ' . $border_radius . 'px;
	margin-bottom: 10px;
	background: #fff;
	box-shadow: none;
	overflow: hidden;
	padding: 0;
}

.emg-faq-box .emg-faq-question {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	width: 100%;
	padding: 0;
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
	transition: background-color var(--emg-faq-anim-ms) ease;
}

.emg-faq-box .emg-faq-question:hover {
	background: #fafafa;
}
.emg-faq-box .emg-faq-question:focus {
	outline: none;
	box-shadow: none;
}

.emg-faq-box .emg-faq-question:focus-visible {
	outline: none;
	box-shadow: none;
}


.emg-faq-box .emg-faq-question-text {
	flex: 1;
	font-weight: 700;
	font-size: ' . $question_font_size . 'px;
	line-height: 145%;
	color: ' . $question_color . ';
}
' . ($use_line_icon ? '
.emg-faq-box .emg-faq-question.emg-faq-question-arrow {
	justify-content: flex-start;
}
.emg-faq-box .emg-faq-question.emg-faq-question-arrow .emg-faq-q-inline {
	flex: 1;
	min-width: 0;
	font-weight: 700;
	font-size: ' . $question_font_size . 'px;
	line-height: 145%;
	color: ' . $question_color . ';
	text-align: left;
}
.emg-faq-box .emg-faq-arrow-icon {
	display: block;
	height: 24px;
	width: 24px;
	min-width: 24px;
	margin-left: auto;
	position: relative;
	flex-shrink: 0;
	box-sizing: content-box;
	transition: transform 0.2s ease-in-out;
}
.emg-faq-box .emg-faq-arrow-icon::before,
.emg-faq-box .emg-faq-arrow-icon::after {
	content: "";
	height: 2px;
	position: absolute;
	top: 11px;
	width: 12px;
	background-color: currentColor;
	transition: transform 0.2s ease-in-out;
}
.emg-faq-box .emg-faq-arrow-icon::before {
		left: 2px;
	transform: rotate(45deg);
	transform-origin: 50% 50%;
}
.emg-faq-box .emg-faq-arrow-icon::after {
	right: 2px;
	transform: rotate(-45deg);
	transform-origin: 50% 50%;
}
.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-arrow-icon::before {
	left: 2px;
	transform: rotate(-45deg);
	transform-origin: 50% 50%;

}
.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-arrow-icon::after {
	right: 2px;
	transform: rotate(45deg);
	transform-origin: 50% 50%;
}
' : '
.emg-faq-box .emg-faq-question {
	--emg-faq-icon: 28px;
}
.emg-faq-box .emg-faq-question::before {
	content: "";
	position: absolute;
	left: calc(100% - 28px - var(--emg-faq-icon) / 2);
	top: 50%;
	right: auto;
	margin: 0;
	transform: translate(-50%, -50%);
	transition: background 200ms ease, border-color 200ms ease, transform 200ms ease;
	' . ($icon_ring ? 'width: var(--emg-faq-icon);
	height: var(--emg-faq-icon);
	border: max(1px, calc(var(--emg-faq-icon) * 0.071)) solid #000;
	border-radius: 50%;
	background: transparent;' : 'width: 0;
	height: 0;
	border: none;
	border-radius: 0;
	background: transparent;') . '
}

.emg-faq-box .emg-faq-question::after {
	content: "+";
	position: absolute;
	left: calc(100% - 28px - var(--emg-faq-icon) / 2);
	top: 50%;
	right: auto;
	margin: 0;
	transform: translate(-50%, -50%);
	color: #000;
	font-family: Arial, Helvetica, sans-serif;
	font-size: calc(var(--emg-faq-icon) * 0.52);
	line-height: 1;
	font-weight: 700;
	transition: opacity 200ms ease, transform 200ms ease, color 200ms ease;
}

.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-question::before {
	background: transparent;
}

.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-question::after {
	content: "−";
	opacity: 1;
	transform: translate(-50%, -50%);
	color: #000;
}
') . '

.emg-faq-box .emg-faq-answer {
	color: ' . $answer_color . ';
	line-height: 170%;
	font-size: ' . $answer_font_size . 'px;
	padding: 0;
	margin-top: 0;
    text-align: left;
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

.emg-faq-box .emg-faq-answer.emg-faq-panel {
	padding: 0;
	margin: 0;
}
.emg-faq-box .emg-faq-panel {
	overflow: hidden;
	max-height: 0;
	opacity: 0;
	transition: max-height var(--emg-faq-anim-ms) ease, opacity calc(var(--emg-faq-anim-ms) * 0.66) ease;
	will-change: max-height, opacity;
}

.emg-faq-box .emg-faq-acc-item.is-open .emg-faq-panel {
	opacity: 1;
}


.emg-faq-box.emg-faq-freeform .emg-faq-freeform-body {
	line-height: 1.6;
}

.emg-faq-wrapper.emg-faq-layout-two-col {
	max-width: 100%;
	width: 100%;
	margin: 0 auto;
	padding: 0;
	background: transparent;
}

.emg-faq-wrapper.emg-faq-layout-two-col .emg-faq-title.emg-faq-title-span {
	margin: 0 0 20px;
	font-size: 32px;
	line-height: 1.2;
	font-weight: 700;
}

.emg-faq-wrapper.emg-faq-layout-two-col .emg-faq-cols {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
	align-items: start;
	width: 100%;
}

.emg-faq-wrapper.emg-faq-layout-two-col .emg-faq-col-left,
.emg-faq-wrapper.emg-faq-layout-two-col .emg-faq-col-right {
	min-width: 0;
}

.emg-faq-wrapper.emg-faq-layout-two-col .emg-faq-col-left .emg-faq-box,
.emg-faq-wrapper.emg-faq-layout-two-col .emg-faq-col-right .emg-faq-box {
	margin: 0;
}

@media (max-width: 782px) {
	.emg-faq-wrapper.emg-faq-layout-two-col .emg-faq-cols {
		grid-template-columns: 1fr;
	}
}
';

        $out = $this->scope_faq_css($css, $anim_ms) . $this->get_frontend_style_extension_css();
        $root_sel = '.' . self::SCOPE_ROOT_CLASS;
        if (!$smooth_panel) {
            $out .= "\n{$root_sel} .emg-faq-panel{transition:none!important;will-change:auto;}\n";
            $out .= "{$root_sel} .emg-faq-question{transition:none!important;}\n";
        }
        return $out;
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
                function readCfg(root) {
                    var anim = parseInt(root.getAttribute('data-emg-faq-anim') || '300', 10);
                    if (isNaN(anim) || anim < 0) anim = 300;
                    return {
                        multiple: root.getAttribute('data-emg-faq-multiple') === '1',
                        animMs: anim
                    };
                }

                function clearPanelTimer(panel) {
                    if (!panel) return;
                    if (panel._emgTimer) {
                        window.clearTimeout(panel._emgTimer);
                        panel._emgTimer = null;
                    }
                }

                function onPanelTransitionEnd(panel, animMs, cb) {
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
                    var cap = Math.max(420, animMs + 80);
                    panel._emgTimer = window.setTimeout(finish, cap);
                }

                function animateFaqToggle(itemEl, forceOpen, cfg) {
                    var panel = itemEl.querySelector('.emg-faq-panel');
                    var trigger = itemEl.querySelector('.emg-faq-question');
                    if (!panel || !cfg) return;
                    if (itemEl.dataset.animating === '1') return;

                    var willOpen = typeof forceOpen === 'boolean' ? forceOpen : !itemEl.classList.contains('is-open');

                    if (willOpen && !cfg.multiple) {
                        var box = itemEl.closest('.emg-faq-box');
                        if (box) {
                            box.querySelectorAll('.emg-faq-acc-item.is-open').forEach(function (other) {
                                if (other !== itemEl) {
                                    animateFaqToggle(other, false, cfg);
                                }
                            });
                        }
                    }

                    itemEl.dataset.animating = '1';
                    clearPanelTimer(panel);

                    if (cfg.animMs <= 0) {
                        if (willOpen) {
                            itemEl.classList.add('is-open');
                            if (trigger) {
                                trigger.setAttribute('aria-expanded', 'true');
                            }
                            panel.setAttribute('aria-hidden', 'false');
                            panel.style.maxHeight = 'none';
                            panel.style.opacity = '1';
                            itemEl.dataset.animating = '0';
                        } else {
                            itemEl.classList.remove('is-open');
                            if (trigger) {
                                trigger.setAttribute('aria-expanded', 'false');
                            }
                            panel.style.maxHeight = '0px';
                            panel.style.opacity = '0';
                            panel.setAttribute('aria-hidden', 'true');
                            itemEl.dataset.animating = '0';
                        }
                        return;
                    }

                    if (willOpen) {
                        itemEl.classList.add('is-open');
                        if (trigger) {
                            trigger.setAttribute('aria-expanded', 'true');
                        }
                        panel.setAttribute('aria-hidden', 'false');
                        panel.style.maxHeight = '0px';
                        panel.style.opacity = '0';
                        window.requestAnimationFrame(function () {
                            window.requestAnimationFrame(function () {
                                panel.style.maxHeight = panel.scrollHeight + 'px';
                                panel.style.opacity = '1';
                                onPanelTransitionEnd(panel, cfg.animMs, function () {
                                    clearPanelTimer(panel);
                                    if (itemEl.classList.contains('is-open')) {
                                        panel.style.maxHeight = panel.scrollHeight + 'px';
                                    }
                                    itemEl.dataset.animating = '0';
                                });
                            });
                        });
                    } else {
                        itemEl.classList.remove('is-open');
                        if (trigger) {
                            trigger.setAttribute('aria-expanded', 'false');
                        }
                        if (panel.style.maxHeight === 'none' || panel.style.maxHeight === '') {
                            panel.style.maxHeight = panel.scrollHeight + 'px';
                        }
                        panel.style.opacity = '1';
                        window.requestAnimationFrame(function () {
                            panel.style.maxHeight = '0px';
                            panel.style.opacity = '0';
                        });
                        onPanelTransitionEnd(panel, cfg.animMs, function () {
                            clearPanelTimer(panel);
                            panel.setAttribute('aria-hidden', 'true');
                            itemEl.dataset.animating = '0';
                        });
                    }
                }

                document.querySelectorAll('.emg-faq-root').forEach(function (root) {
                    var cfg = readCfg(root);
                    root.querySelectorAll('.emg-faq-box').forEach(function (scope) {
                        scope.querySelectorAll('.emg-faq-item.emg-faq-acc-item').forEach(function (itemEl) {
                            var trigger = itemEl.querySelector('.emg-faq-question');
                            var panel = itemEl.querySelector('.emg-faq-panel');
                            if (!trigger || !panel) return;
                            if (itemEl.classList.contains('is-open')) {
                                panel.style.maxHeight = panel.scrollHeight + 'px';
                                panel.style.opacity = '1';
                                panel.style.transform = 'translateY(0)';
                                panel.setAttribute('aria-hidden', 'false');
                                trigger.setAttribute('aria-expanded', 'true');
                            } else {
                                panel.style.maxHeight = '0px';
                                panel.style.opacity = '0';
                                panel.style.transform = 'translateY(-4px)';
                                panel.setAttribute('aria-hidden', 'true');
                                trigger.setAttribute('aria-expanded', 'false');
                            }
                            trigger.addEventListener('click', function (e) {
                                e.preventDefault();
                                animateFaqToggle(itemEl, undefined, cfg);
                            });
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
        $this->register_emg_faq_setting(self::OPT_Q_COLOR, array($this, 'sanitize_opt_question_color'));
        $this->register_emg_faq_setting(self::OPT_A_FONT_SIZE, array($this, 'sanitize_font_size'));
        $this->register_emg_faq_setting(self::OPT_A_COLOR, array($this, 'sanitize_opt_answer_color'));
        $this->register_emg_faq_setting(self::OPT_ITEM_BORDER_WIDTH, array($this, 'sanitize_border_width'));
        $this->register_emg_faq_setting(self::OPT_ITEM_BORDER_COLOR, array($this, 'sanitize_opt_border_color_flex'));
        $this->register_emg_faq_setting(self::OPT_ITEM_BORDER_RADIUS, array($this, 'sanitize_border_radius'));
        $this->register_emg_faq_setting(self::OPT_ITEM_BORDER_SIDES, array($this, 'sanitize_border_sides'));
        $this->register_emg_faq_setting(self::OPT_TWO_COLUMN_LAYOUT, array($this, 'sanitize_manual_schema_flag'));
        $this->register_emg_faq_setting(self::OPT_OPEN_FIRST, array($this, 'sanitize_manual_schema_flag'));
        $this->register_emg_faq_setting(self::OPT_MULTIPLE_OPEN, array($this, 'sanitize_manual_schema_flag'));
        $this->register_emg_faq_setting(self::OPT_ANIM_MS, array($this, 'sanitize_anim_ms'));
        $this->register_emg_faq_setting(self::OPT_ICON_POSITION, array($this, 'sanitize_icon_position'));
        $this->register_emg_faq_setting(self::OPT_ICON_SIZE, array($this, 'sanitize_icon_size_px'));
        $this->register_emg_faq_setting(self::OPT_ICON_COLOR, array($this, 'sanitize_optional_css_color'));
        $this->register_emg_faq_setting(self::OPT_ICON_COLOR_OPEN, array($this, 'sanitize_optional_css_color'));
        $this->register_emg_faq_setting(self::OPT_ICON_RING_BORDER, array($this, 'sanitize_manual_schema_flag'));
        $this->register_emg_faq_setting(self::OPT_HEADING_WEIGHT, array($this, 'sanitize_heading_weight'));
        $this->register_emg_faq_setting(self::OPT_HEADING_SIZE_T, array($this, 'sanitize_font_size_or_zero'));
        $this->register_emg_faq_setting(self::OPT_HEADING_SIZE_M, array($this, 'sanitize_font_size_or_zero'));
        $this->register_emg_faq_setting(self::OPT_CONTENT_LH, array($this, 'sanitize_line_height_or_zero'));
        $this->register_emg_faq_setting(self::OPT_CONTENT_FS_T, array($this, 'sanitize_font_size_or_zero'));
        $this->register_emg_faq_setting(self::OPT_CONTENT_FS_M, array($this, 'sanitize_font_size_or_zero'));
        $this->register_emg_faq_setting(self::OPT_ITEM_GAP, array($this, 'sanitize_px_range'));
        $this->register_emg_faq_setting(self::OPT_ITEM_BG, array($this, 'sanitize_optional_css_color'));
        $this->register_emg_faq_setting(self::OPT_ITEM_BG_OPEN, array($this, 'sanitize_optional_css_color'));
        $this->register_emg_faq_setting(self::OPT_Q_HOVER_BG, array($this, 'sanitize_optional_css_color'));
        $this->register_emg_faq_setting(self::OPT_CONTAINER_BG, array($this, 'sanitize_optional_css_color'));
        $this->register_emg_faq_setting(self::OPT_CUSTOM_CSS, array($this, 'sanitize_custom_css'));
        $this->register_emg_faq_setting(self::OPT_COLUMN_GAP, array($this, 'sanitize_column_gap_px'));
        $this->register_emg_faq_setting(self::OPT_SMOOTH_PANEL_ANIM, array($this, 'sanitize_manual_schema_flag'));
        $this->register_emg_faq_setting(self::OPT_CONTENT_LH_T, array($this, 'sanitize_line_height_or_zero'));
        $this->register_emg_faq_setting(self::OPT_CONTENT_LH_M, array($this, 'sanitize_line_height_or_zero'));
        $pad_opts = array(
            self::OPT_PAD_C_TOP_D,
            self::OPT_PAD_C_TOP_T,
            self::OPT_PAD_C_TOP_M,
            self::OPT_PAD_C_RIGHT_D,
            self::OPT_PAD_C_RIGHT_T,
            self::OPT_PAD_C_RIGHT_M,
            self::OPT_PAD_C_BOTTOM_D,
            self::OPT_PAD_C_BOTTOM_T,
            self::OPT_PAD_C_BOTTOM_M,
            self::OPT_PAD_C_LEFT_D,
            self::OPT_PAD_C_LEFT_T,
            self::OPT_PAD_C_LEFT_M,
            self::OPT_PAD_Q_TOP_D,
            self::OPT_PAD_Q_TOP_T,
            self::OPT_PAD_Q_TOP_M,
            self::OPT_PAD_Q_RIGHT_D,
            self::OPT_PAD_Q_RIGHT_T,
            self::OPT_PAD_Q_RIGHT_M,
            self::OPT_PAD_Q_BOTTOM_D,
            self::OPT_PAD_Q_BOTTOM_T,
            self::OPT_PAD_Q_BOTTOM_M,
            self::OPT_PAD_Q_LEFT_D,
            self::OPT_PAD_Q_LEFT_T,
            self::OPT_PAD_Q_LEFT_M,
            self::OPT_PAD_A_TOP_D,
            self::OPT_PAD_A_TOP_T,
            self::OPT_PAD_A_TOP_M,
            self::OPT_PAD_A_RIGHT_D,
            self::OPT_PAD_A_RIGHT_T,
            self::OPT_PAD_A_RIGHT_M,
            self::OPT_PAD_A_BOTTOM_D,
            self::OPT_PAD_A_BOTTOM_T,
            self::OPT_PAD_A_BOTTOM_M,
            self::OPT_PAD_A_LEFT_D,
            self::OPT_PAD_A_LEFT_T,
            self::OPT_PAD_A_LEFT_M,
        );
        foreach ($pad_opts as $po) {
            $this->register_emg_faq_setting($po, array($this, 'sanitize_pad_px'));
        }
    }

    /**
     * Admin-only: WordPress color picker for Style tab fields.
     *
     * @param string $hook
     */
    public function maybe_enqueue_admin_preview_assets($hook)
    {
        if ($hook !== 'toplevel_page_emg-faq') {
            return;
        }
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_add_inline_script(
            'wp-color-picker',
            'jQuery(function($){$(".emg-faq-wp-color").each(function(){var $i=$(this);var v=$.trim($i.val()||"");if(/^rgba?\(/i.test(v)){return;}$i.wpColorPicker({change:function(e,ui){$i.val(ui.color.toString());}});});});',
            'after'
        );
    }

    public function sanitize_column_gap_px($value)
    {
        $n = (int) $value;
        if ($n < 0) {
            $n = 0;
        }
        if ($n > 80) {
            $n = 80;
        }
        return (string) $n;
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
        if ($value === 'arrow') {
            $value = 'chevron';
        }
        return in_array($value, array('plusminus', 'chevron'), true) ? $value : 'plusminus';
    }

    public function sanitize_icon_position($value)
    {
        $v = is_string($value) ? strtolower(trim($value)) : 'right';
        return in_array($v, array('left', 'right'), true) ? $v : 'right';
    }

    public function sanitize_icon_size_px($value)
    {
        $n = (int) $value;
        if ($n < 0) {
            $n = 0;
        }
        if ($n > 48) {
            $n = 48;
        }
        return (string) $n;
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

    /**
     * @param string $v
     * @return string|null Normalized color or null if invalid
     */
    private function parse_css_color_token($v)
    {
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $v)) {
            return strtolower($v);
        }
        if (preg_match('/^rgba\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9.]+)\s*\)$/', $v, $m)) {
            $r = min(255, max(0, (int) $m[1]));
            $g = min(255, max(0, (int) $m[2]));
            $b = min(255, max(0, (int) $m[3]));
            $a = min(1.0, max(0.0, (float) $m[4]));
            return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $a . ')';
        }
        if (preg_match('/^rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)$/', $v, $m)) {
            $r = min(255, max(0, (int) $m[1]));
            $g = min(255, max(0, (int) $m[2]));
            $b = min(255, max(0, (int) $m[3]));
            return 'rgb(' . $r . ',' . $g . ',' . $b . ')';
        }
        $hex = sanitize_hex_color($v);
        return $hex ? strtolower($hex) : null;
    }

    /**
     * Allow #hex, #rrggbbaa, rgb(), rgba() for safe CSS color values.
     *
     * @param mixed  $value
     * @param string $default
     * @return string
     */
    public function sanitize_css_color_flexible($value, $default)
    {
        $parsed = $this->parse_css_color_token((string) $value);
        return $parsed !== null ? $parsed : $default;
    }

    public function sanitize_opt_question_color($value)
    {
        return $this->sanitize_css_color_flexible($value, '#111827');
    }

    public function sanitize_opt_answer_color($value)
    {
        return $this->sanitize_css_color_flexible($value, '#374151');
    }

    public function sanitize_opt_border_color_flex($value)
    {
        return $this->sanitize_css_color_flexible($value, '#dddddd');
    }

    /**
     * @param mixed $value
     * @return string empty or valid CSS color
     */
    public function sanitize_optional_css_color($value)
    {
        $parsed = $this->parse_css_color_token((string) $value);
        return $parsed !== null ? $parsed : '';
    }

    /**
     * Optional color from DB for inline CSS rules (empty = skip rule).
     *
     * @param mixed $raw
     * @return string
     */
    private function optional_color_css($raw)
    {
        $t = $this->parse_css_color_token(trim((string) $raw));
        return $t !== null ? $t : '';
    }

    /**
     * @param string $stored
     * @return string
     */
    private function admin_color_input_classes($stored)
    {
        $t = trim((string) $stored);
        if ($t === '') {
            return 'emg-faq-wp-color widefat';
        }
        if ($this->parse_css_color_token($t) !== null && !preg_match('/^rgba?\(/i', $t)) {
            return 'emg-faq-wp-color widefat';
        }
        return 'widefat';
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

    public function sanitize_anim_ms($value)
    {
        $n = (int) $value;
        if ($n < 100) {
            $n = 100;
        }
        if ($n > 1500) {
            $n = 1500;
        }
        return (string) $n;
    }

    public function sanitize_optional_color($value)
    {
        if (!is_string($value)) {
            return '';
        }
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $color = sanitize_hex_color($value);
        return $color ? $color : '';
    }

    public function sanitize_heading_weight($value)
    {
        $n = (int) $value;
        $allowed = array(100, 200, 300, 400, 500, 600, 700, 800, 900);
        return in_array($n, $allowed, true) ? (string) $n : '0';
    }

    public function sanitize_font_size_or_zero($value)
    {
        $n = (int) $value;
        if ($n === 0) {
            return '0';
        }
        if ($n < 8) {
            $n = 8;
        }
        if ($n > 72) {
            $n = 72;
        }
        return (string) $n;
    }

    public function sanitize_line_height_or_zero($value)
    {
        $f = round((float) $value, 2);
        if ($f <= 0) {
            return '0';
        }
        if ($f < 1) {
            $f = 1;
        }
        if ($f > 3) {
            $f = 3;
        }
        return (string) $f;
    }

    public function sanitize_px_range($value)
    {
        $n = (int) $value;
        if ($n < 0) {
            $n = 0;
        }
        if ($n > 64) {
            $n = 64;
        }
        return (string) $n;
    }

    public function sanitize_pad_px($value)
    {
        $n = (int) $value;
        if ($n < 0) {
            $n = 0;
        }
        if ($n > 120) {
            $n = 120;
        }
        return (string) $n;
    }

    public function sanitize_custom_css($value)
    {
        if (!is_string($value)) {
            return '';
        }
        $css = str_replace("\0", '', $value);
        if (strlen($css) > 100000) {
            $css = substr($css, 0, 100000);
        }
        $lower = strtolower($css);
        $blocked = array(
            '@import',
            '@namespace',
            'expression(',
            'javascript:',
            '-moz-binding',
            'behavior:',
            '<script',
            '</script',
            'url(javascript',
        );
        foreach ($blocked as $token) {
            if (strpos($lower, $token) !== false) {
                add_settings_error(
                    'emg_faq_settings',
                    'emg_faq_unsafe_css',
                    'Custom CSS was not saved because it contained disallowed patterns (for example @import or script-related syntax).',
                    'error'
                );
                return '';
            }
        }
        return $css;
    }

    /**
     * Prefix plugin selectors so styles never leak outside .emg-faq-root.
     *
     * @param string $css
     * @param int    $anim_ms
     * @return string
     */
    private function scope_faq_css($css, $anim_ms)
    {
        $root = '.' . self::SCOPE_ROOT_CLASS;
        $pairs = array(
            '.emg-faq-wrapper' => $root . ' .emg-faq-wrapper',
            '.emg-faq-box' => $root . ' .emg-faq-box',
        );
        $out = (string) $css;
        foreach ($pairs as $from => $to) {
            if (strpos($out, $from) === false) {
                continue;
            }
            $out = str_replace($from, $to, $out);
        }
        $anim_ms = (int) $anim_ms;
        if ($anim_ms < 100) {
            $anim_ms = 100;
        }
        if ($anim_ms > 1500) {
            $anim_ms = 1500;
        }
        return $root . " {\n\tbox-sizing: border-box;\n\t--emg-faq-anim-ms: {$anim_ms}ms;\n}\n" . $out;
    }

    /**
     * @param array<int,string> $lines
     * @param string            $selector Full selector(s) including .emg-faq-root prefix where needed.
     */
    private function append_trbl_padding_css(array &$lines, $selector, $td, $rd, $bd, $ld, $tt, $rt, $bt, $lt, $tm, $rm, $bm, $lm)
    {
        $lines[] = $selector . ' { padding: ' . $td . 'px ' . $rd . 'px ' . $bd . 'px ' . $ld . 'px; }';
        if ($tt + $rt + $bt + $lt > 0) {
            $lines[] = '@media (max-width: 1024px) { ' . $selector . ' { padding: ' . $tt . 'px ' . $rt . 'px ' . $bt . 'px ' . $lt . 'px; } }';
        }
        if ($tm + $rm + $bm + $lm > 0) {
            $lines[] = '@media (max-width: 782px) { ' . $selector . ' { padding: ' . $tm . 'px ' . $rm . 'px ' . $bm . 'px ' . $lm . 'px; } }';
        }
    }

    /**
     * When border width was never saved: accordion defaults to 1px, plain to 0. Saved value applies to both modes.
     *
     * @return array{accordion:int,plain:int}
     */
    private function resolve_effective_item_border_widths()
    {
        $saved = get_option(self::OPT_ITEM_BORDER_WIDTH, false);
        if ($saved === false || $saved === null) {
            return array('accordion' => 1, 'plain' => 0);
        }
        $w = max(0, min(20, (int) $saved));

        return array('accordion' => $w, 'plain' => $w);
    }

    /**
     * @return array{top:string,right:string,bottom:string,left:string}
     */
    private function build_item_border_edge_css($width_px, $border_color, array $border_sides)
    {
        $has_top = in_array('top', $border_sides, true);
        $has_right = in_array('right', $border_sides, true);
        $has_bottom = in_array('bottom', $border_sides, true);
        $has_left = in_array('left', $border_sides, true);
        $w = max(0, min(20, (int) $width_px));
        $c = $border_color;

        return array(
            'top' => ($w > 0 && $has_top) ? $w . 'px solid ' . $c : '0',
            'right' => ($w > 0 && $has_right) ? $w . 'px solid ' . $c : '0',
            'bottom' => ($w > 0 && $has_bottom) ? $w . 'px solid ' . $c : '0',
            'left' => ($w > 0 && $has_left) ? $w . 'px solid ' . $c : '0',
        );
    }

    /**
     * Padding option: explicit saved value wins; otherwise use default for accordion vs plain output.
     *
     * @param string $opt
     * @param int    $default_accordion
     * @param int    $default_plain
     */
    private function pad_px_for_mode($opt, $default_accordion, $default_plain, $is_plain)
    {
        $v = get_option($opt, null);
        if ($v !== null && $v !== false && $v !== '') {
            return max(0, min(120, (int) $v));
        }

        return max(0, min(120, (int) ($is_plain ? $default_plain : $default_accordion)));
    }

    /**
     * Extra rules from Style tab (padding always emitted; sensible defaults when options are unset).
     *
     * @return string
     */
    private function get_frontend_style_extension_css()
    {
        $root = '.' . self::SCOPE_ROOT_CLASS;

        $c_bg = $this->optional_color_css(get_option(self::OPT_CONTAINER_BG, ''));

        $gap = (int) get_option(self::OPT_ITEM_GAP, 0);

        $item_bg = $this->optional_color_css(get_option(self::OPT_ITEM_BG, ''));
        $item_open = $this->optional_color_css(get_option(self::OPT_ITEM_BG_OPEN, ''));
        $q_hover = $this->optional_color_css(get_option(self::OPT_Q_HOVER_BG, ''));

        $h_w = (string) get_option(self::OPT_HEADING_WEIGHT, '0');
        $h_t = (int) get_option(self::OPT_HEADING_SIZE_T, 0);
        $h_m = (int) get_option(self::OPT_HEADING_SIZE_M, 0);

        $lh = (string) get_option(self::OPT_CONTENT_LH, '0');
        $lh_t = (string) get_option(self::OPT_CONTENT_LH_T, '0');
        $lh_m = (string) get_option(self::OPT_CONTENT_LH_M, '0');
        $a_t = (int) get_option(self::OPT_CONTENT_FS_T, 0);
        $a_m = (int) get_option(self::OPT_CONTENT_FS_M, 0);

        $icon_pos = $this->sanitize_icon_position((string) get_option(self::OPT_ICON_POSITION, 'right'));
        $icon_sz = (int) get_option(self::OPT_ICON_SIZE, 0);
        $icon_ring = get_option(self::OPT_ICON_RING_BORDER, '1') === '1';
        $icon_c = $this->optional_color_css(get_option(self::OPT_ICON_COLOR, ''));
        $icon_co = $this->optional_color_css(get_option(self::OPT_ICON_COLOR_OPEN, ''));

        $lines = array();

        $bws = $this->resolve_effective_item_border_widths();
        $border_color = $this->sanitize_css_color_flexible((string) get_option(self::OPT_ITEM_BORDER_COLOR, '#dddddd'), '#dddddd');
        $border_sides = get_option(self::OPT_ITEM_BORDER_SIDES, array('top', 'right', 'bottom', 'left'));
        if (!is_array($border_sides) || empty($border_sides)) {
            $border_sides = array('top', 'right', 'bottom', 'left');
        }
        $edge_acc = $this->build_item_border_edge_css($bws['accordion'], $border_color, $border_sides);
        $edge_plain = $this->build_item_border_edge_css($bws['plain'], $border_color, $border_sides);
        $sel_item_acc = $root . '[data-emg-faq-mode="accordion"] .emg-faq-box .emg-faq-item';
        $sel_item_plain = $root . '[data-emg-faq-mode="plain"] .emg-faq-box .emg-faq-item';
        $lines[] = $sel_item_acc . ' { border-top: ' . $edge_acc['top'] . '; border-right: ' . $edge_acc['right'] . '; border-bottom: ' . $edge_acc['bottom'] . '; border-left: ' . $edge_acc['left'] . '; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04); }';
        if ($bws['plain'] > 0) {
            $lines[] = $sel_item_plain . ' { border-top: ' . $edge_plain['top'] . '; border-right: ' . $edge_plain['right'] . '; border-bottom: ' . $edge_plain['bottom'] . '; border-left: ' . $edge_plain['left'] . '; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04); }';
        }

        $col_gap = (int) get_option(self::OPT_COLUMN_GAP, 0);
        if ($col_gap > 0) {
            $lines[] = $root . ' .emg-faq-wrapper.emg-faq-layout-two-col .emg-faq-cols { gap: ' . $col_gap . 'px; }';
        }

        $c_sel = $root . ' .emg-faq-box, ' . $root . ' .emg-faq-wrapper.emg-faq-layout-two-col';
        if ($c_bg !== '') {
            $lines[] = $c_sel . ' { background-color: ' . $c_bg . '; }';
        }

        $this->append_trbl_padding_css(
            $lines,
            $c_sel,
            (int) get_option(self::OPT_PAD_C_TOP_D, 0),
            (int) get_option(self::OPT_PAD_C_RIGHT_D, 0),
            (int) get_option(self::OPT_PAD_C_BOTTOM_D, 0),
            (int) get_option(self::OPT_PAD_C_LEFT_D, 0),
            (int) get_option(self::OPT_PAD_C_TOP_T, 0),
            (int) get_option(self::OPT_PAD_C_RIGHT_T, 0),
            (int) get_option(self::OPT_PAD_C_BOTTOM_T, 0),
            (int) get_option(self::OPT_PAD_C_LEFT_T, 0),
            (int) get_option(self::OPT_PAD_C_TOP_M, 0),
            (int) get_option(self::OPT_PAD_C_RIGHT_M, 0),
            (int) get_option(self::OPT_PAD_C_BOTTOM_M, 0),
            (int) get_option(self::OPT_PAD_C_LEFT_M, 0)
        );

        $q_acc = $root . '[data-emg-faq-mode="accordion"] .emg-faq-box .emg-faq-question';
        $this->append_trbl_padding_css(
            $lines,
            $q_acc,
            $this->pad_px_for_mode(self::OPT_PAD_Q_TOP_D, 18, 18, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_RIGHT_D, 56, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_BOTTOM_D, 18, 18, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_LEFT_D, 20, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_TOP_T, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_RIGHT_T, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_BOTTOM_T, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_LEFT_T, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_TOP_M, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_RIGHT_M, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_BOTTOM_M, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_Q_LEFT_M, 0, 0, false)
        );

        $q_plain = $root . '[data-emg-faq-mode="plain"] .emg-faq-box .emg-faq-question-text';
        $this->append_trbl_padding_css(
            $lines,
            $q_plain,
            $this->pad_px_for_mode(self::OPT_PAD_Q_TOP_D, 18, 18, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_RIGHT_D, 56, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_BOTTOM_D, 18, 18, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_LEFT_D, 20, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_TOP_T, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_RIGHT_T, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_BOTTOM_T, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_LEFT_T, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_TOP_M, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_RIGHT_M, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_BOTTOM_M, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_Q_LEFT_M, 0, 0, true)
        );

        // Padding on .emg-faq-answer-inner: outer panel stays padding-free so closed state has no gap; inner clips inside max-height animation.
        $a_acc = $root . '[data-emg-faq-mode="accordion"] .emg-faq-box .emg-faq-acc-item > .emg-faq-answer.emg-faq-panel > .emg-faq-answer-inner';
        $this->append_trbl_padding_css(
            $lines,
            $a_acc,
            $this->pad_px_for_mode(self::OPT_PAD_A_TOP_D, 12, 12, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_RIGHT_D, 20, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_BOTTOM_D, 16, 16, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_LEFT_D, 20, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_TOP_T, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_RIGHT_T, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_BOTTOM_T, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_LEFT_T, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_TOP_M, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_RIGHT_M, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_BOTTOM_M, 0, 0, false),
            $this->pad_px_for_mode(self::OPT_PAD_A_LEFT_M, 0, 0, false)
        );

        $a_plain = $root . '[data-emg-faq-mode="plain"] .emg-faq-box .emg-faq-item:not(.emg-faq-acc-item) .emg-faq-answer';
        $this->append_trbl_padding_css(
            $lines,
            $a_plain,
            $this->pad_px_for_mode(self::OPT_PAD_A_TOP_D, 12, 12, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_RIGHT_D, 20, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_BOTTOM_D, 16, 16, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_LEFT_D, 20, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_TOP_T, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_RIGHT_T, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_BOTTOM_T, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_LEFT_T, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_TOP_M, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_RIGHT_M, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_BOTTOM_M, 0, 0, true),
            $this->pad_px_for_mode(self::OPT_PAD_A_LEFT_M, 0, 0, true)
        );

        if ($gap > 0) {
            $lines[] = $root . ' .emg-faq-box .emg-faq-item { margin-bottom: ' . $gap . 'px; }';
        }

        if ($item_bg !== '') {
            $lines[] = $root . ' .emg-faq-box .emg-faq-item { background: ' . $item_bg . '; }';
        }
        if ($item_open !== '') {
            $lines[] = $root . ' .emg-faq-box .emg-faq-acc-item.is-open { background: ' . $item_open . '; }';
        }

        if ($q_hover !== '') {
            $lines[] = $root . '[data-emg-faq-mode="accordion"] .emg-faq-box .emg-faq-question:hover { background: ' . $q_hover . '; }';
        }

        if ($h_w !== '0') {
            $lines[] = $root . ' .emg-faq-box .emg-faq-title, ' . $root . ' .emg-faq-wrapper .emg-faq-title.emg-faq-title-span { font-weight: ' . (int) $h_w . '; }';
            $lines[] = $root . ' .emg-faq-box .emg-faq-question-text, ' . $root . ' .emg-faq-box .emg-faq-question { font-weight: ' . (int) $h_w . '; }';
        }

        if ($h_t >= 8) {
            $lines[] = '@media (max-width: 1024px) { ' . $root . ' .emg-faq-box .emg-faq-title, ' . $root . ' .emg-faq-wrapper .emg-faq-title.emg-faq-title-span { font-size: ' . $h_t . 'px; } '
                . $root . ' .emg-faq-box .emg-faq-question-text, ' . $root . ' .emg-faq-box .emg-faq-question:not(.emg-faq-question-arrow), ' . $root . ' .emg-faq-box .emg-faq-question.emg-faq-question-arrow .emg-faq-q-inline { font-size: ' . $h_t . 'px; } }';
        }
        if ($h_m >= 8) {
            $lines[] = '@media (max-width: 782px) { ' . $root . ' .emg-faq-box .emg-faq-title, ' . $root . ' .emg-faq-wrapper .emg-faq-title.emg-faq-title-span { font-size: ' . $h_m . 'px; } '
                . $root . ' .emg-faq-box .emg-faq-question-text, ' . $root . ' .emg-faq-box .emg-faq-question:not(.emg-faq-question-arrow), ' . $root . ' .emg-faq-box .emg-faq-question.emg-faq-question-arrow .emg-faq-q-inline { font-size: ' . $h_m . 'px; } }';
        }

        if ($lh !== '0' && (float) $lh > 0) {
            $lines[] = $root . ' .emg-faq-box .emg-faq-answer { line-height: ' . (float) $lh . '; }';
        }
        if ($lh_t !== '0' && (float) $lh_t > 0) {
            $lines[] = '@media (max-width: 1024px) { ' . $root . ' .emg-faq-box .emg-faq-answer { line-height: ' . (float) $lh_t . '; } }';
        }
        if ($lh_m !== '0' && (float) $lh_m > 0) {
            $lines[] = '@media (max-width: 782px) { ' . $root . ' .emg-faq-box .emg-faq-answer { line-height: ' . (float) $lh_m . '; } }';
        }
        if ($a_t >= 8) {
            $lines[] = '@media (max-width: 1024px) { ' . $root . ' .emg-faq-box .emg-faq-answer { font-size: ' . $a_t . 'px; } }';
        }
        if ($a_m >= 8) {
            $lines[] = '@media (max-width: 782px) { ' . $root . ' .emg-faq-box .emg-faq-answer { font-size: ' . $a_m . 'px; } }';
        }

        $icon_acc = $root . '[data-emg-faq-mode="accordion"] .emg-faq-box';
        if ($icon_sz > 0) {
            $fs = max(16, min(48, $icon_sz));
            $lines[] = $icon_acc . ' .emg-faq-question { --emg-faq-icon: ' . $fs . 'px; }';
            $lines[] = $icon_acc . ' .emg-faq-arrow-icon { font-size: ' . $fs . 'px; }';
        }
        if ($icon_c !== '') {
            $lines[] = $icon_acc . ' .emg-faq-arrow-icon { color: ' . $icon_c . '; }';
            $lines[] = $icon_acc . ' .emg-faq-question::before { border-color: ' . $icon_c . '; }';
            $lines[] = $icon_acc . ' .emg-faq-question::after { color: ' . $icon_c . '; }';
        }
        if ($icon_co !== '') {
            $lines[] = $icon_acc . ' .emg-faq-acc-item.is-open .emg-faq-question::after { color: ' . $icon_co . '; }';
            $lines[] = $icon_acc . ' .emg-faq-acc-item.is-open .emg-faq-arrow-icon { color: ' . $icon_co . '; }';
        }

        if ($icon_pos === 'left') {
            $acc = $root . '[data-emg-faq-mode="accordion"] .emg-faq-box .emg-faq-question';
            $lines[] = $acc . '.emg-faq-question--icon-left { flex-direction: row; justify-content: flex-start; }';
            $lines[] = $acc . '.emg-faq-question--icon-left::before { left: calc(28px + var(--emg-faq-icon, 28px) / 2); right: auto; transform: translate(-50%, -50%); }';
            $lines[] = $acc . '.emg-faq-question--icon-left::after { left: calc(28px + var(--emg-faq-icon, 28px) / 2); right: auto; transform: translate(-50%, -50%); }';
            $lines[] = $acc . '.emg-faq-question--arrow.emg-faq-question--icon-left .emg-faq-arrow-icon { margin-left: 0; margin-right: 12px; order: -1; }';
            $lines[] = $acc . '.emg-faq-question--arrow.emg-faq-question--icon-left .emg-faq-q-inline { flex: 1; }';
        }

        return "\n" . implode("\n", $lines) . "\n";
    }

    /**
     * User custom CSS; must reference .emg-faq-root (see admin notice).
     *
     * @return string
     */
    private function get_frontend_custom_css()
    {
        $raw = (string) get_option(self::OPT_CUSTOM_CSS, '');
        if ($raw === '') {
            return '';
        }
        return "\n/* EMG FAQ custom */\n" . $raw . "\n";
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

                var twoColCb = document.getElementById('emg-faq-two-column');
                var colGapWrap = document.getElementById('emg-faq-column-gap-wrap');
                function syncColumnGapVisibility() {
                    if (!twoColCb || !colGapWrap) return;
                    colGapWrap.style.display = twoColCb.checked ? '' : 'none';
                }
                if (twoColCb && colGapWrap) {
                    twoColCb.addEventListener('change', syncColumnGapVisibility);
                    syncColumnGapVisibility();
                }

                document.querySelectorAll('.emg-faq-pad-bp-toggle').forEach(function (sel) {
                    sel.addEventListener('change', function () {
                        var wrap = sel.closest('.emg-faq-pad-section');
                        if (!wrap) return;
                        var bp = sel.value;
                        wrap.querySelectorAll('.emg-faq-pad-bp-panel').forEach(function (p) {
                            p.style.display = p.getAttribute('data-bp') === bp ? '' : 'none';
                        });
                    });
                });

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

                var tabLinks = document.querySelectorAll('.emg-faq-settings-tabs .nav-tab');
                var tabPanels = document.querySelectorAll('.emg-faq-tab-panel');
                function emgFaqShowTab(id) {
                    tabPanels.forEach(function (p) {
                        var on = p.id === id;
                        p.classList.toggle('is-active', on);
                        p.style.display = on ? 'block' : 'none';
                    });
                    tabLinks.forEach(function (a) {
                        var href = a.getAttribute('href') || '';
                        a.classList.toggle('nav-tab-active', href === '#' + id);
                    });
                    try {
                        localStorage.setItem('emg_faq_active_tab', id);
                    } catch (e) { }
                }
                tabLinks.forEach(function (a) {
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        var href = this.getAttribute('href') || '';
                        if (href.charAt(0) === '#') {
                            emgFaqShowTab(href.slice(1));
                        }
                    });
                });
                var startTab = 'emg-faq-tab-general';
                try {
                    var h = window.location.hash ? window.location.hash.slice(1) : '';
                    if (h && document.getElementById(h)) {
                        startTab = h;
                    } else {
                        var s = localStorage.getItem('emg_faq_active_tab');
                        if (s && document.getElementById(s)) {
                            startTab = s;
                        }
                    }
                } catch (e2) { }
                emgFaqShowTab(startTab);

                function emgFaqCopyToClipboard(str) {
                    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                        return navigator.clipboard.writeText(str);
                    }
                    return Promise.reject(new Error('no clipboard api'));
                }

                function emgFaqCopyFallback(str) {
                    var ta = document.createElement('textarea');
                    ta.value = str;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.top = '0';
                    ta.style.left = '0';
                    ta.style.width = '1px';
                    ta.style.height = '1px';
                    ta.style.padding = '0';
                    ta.style.border = 'none';
                    ta.style.outline = 'none';
                    ta.style.boxShadow = 'none';
                    ta.style.background = 'transparent';
                    document.body.appendChild(ta);
                    ta.focus();
                    ta.select();
                    try {
                        var range = document.createRange();
                        range.selectNodeContents(ta);
                        var sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(range);
                        ta.setSelectionRange(0, str.length);
                    } catch (e0) { }
                    var ok = false;
                    try {
                        ok = document.execCommand('copy');
                    } catch (e1) { }
                    document.body.removeChild(ta);
                    return ok;
                }

                document.querySelector('.emg-faq-admin-wrap').addEventListener('click', function (e) {
                    var btn = e.target.closest('.emg-faq-copy-btn');
                    if (!btn) return;
                    var text = btn.getAttribute('data-clipboard');
                    if (!text) return;
                    var label = btn.textContent;
                    var msgOk = <?php echo wp_json_encode(__('Copied!', 'emg-faq')); ?>;
                    var msgBad = <?php echo wp_json_encode(__('Copy failed', 'emg-faq')); ?>;
                    function done(ok) {
                        btn.textContent = ok ? msgOk : msgBad;
                        window.setTimeout(function () { btn.textContent = label; }, ok ? 1600 : 2200);
                    }
                    emgFaqCopyToClipboard(text).then(function () { done(true); }).catch(function () {
                        done(emgFaqCopyFallback(text));
                    });
                });
            })();
        </script>
        <?php
    }

    /**
     * @param string $g c | q | a
     * @param string $side top | right | bottom | left
     * @param string $bp d | t | m
     * @return string
     */
    private function pad_option_for($g, $side, $bp)
    {
        static $map = null;
        if ($map === null) {
            $map = array(
                'c_top_d' => self::OPT_PAD_C_TOP_D,
                'c_top_t' => self::OPT_PAD_C_TOP_T,
                'c_top_m' => self::OPT_PAD_C_TOP_M,
                'c_right_d' => self::OPT_PAD_C_RIGHT_D,
                'c_right_t' => self::OPT_PAD_C_RIGHT_T,
                'c_right_m' => self::OPT_PAD_C_RIGHT_M,
                'c_bottom_d' => self::OPT_PAD_C_BOTTOM_D,
                'c_bottom_t' => self::OPT_PAD_C_BOTTOM_T,
                'c_bottom_m' => self::OPT_PAD_C_BOTTOM_M,
                'c_left_d' => self::OPT_PAD_C_LEFT_D,
                'c_left_t' => self::OPT_PAD_C_LEFT_T,
                'c_left_m' => self::OPT_PAD_C_LEFT_M,
                'q_top_d' => self::OPT_PAD_Q_TOP_D,
                'q_top_t' => self::OPT_PAD_Q_TOP_T,
                'q_top_m' => self::OPT_PAD_Q_TOP_M,
                'q_right_d' => self::OPT_PAD_Q_RIGHT_D,
                'q_right_t' => self::OPT_PAD_Q_RIGHT_T,
                'q_right_m' => self::OPT_PAD_Q_RIGHT_M,
                'q_bottom_d' => self::OPT_PAD_Q_BOTTOM_D,
                'q_bottom_t' => self::OPT_PAD_Q_BOTTOM_T,
                'q_bottom_m' => self::OPT_PAD_Q_BOTTOM_M,
                'q_left_d' => self::OPT_PAD_Q_LEFT_D,
                'q_left_t' => self::OPT_PAD_Q_LEFT_T,
                'q_left_m' => self::OPT_PAD_Q_LEFT_M,
                'a_top_d' => self::OPT_PAD_A_TOP_D,
                'a_top_t' => self::OPT_PAD_A_TOP_T,
                'a_top_m' => self::OPT_PAD_A_TOP_M,
                'a_right_d' => self::OPT_PAD_A_RIGHT_D,
                'a_right_t' => self::OPT_PAD_A_RIGHT_T,
                'a_right_m' => self::OPT_PAD_A_RIGHT_M,
                'a_bottom_d' => self::OPT_PAD_A_BOTTOM_D,
                'a_bottom_t' => self::OPT_PAD_A_BOTTOM_T,
                'a_bottom_m' => self::OPT_PAD_A_BOTTOM_M,
                'a_left_d' => self::OPT_PAD_A_LEFT_D,
                'a_left_t' => self::OPT_PAD_A_LEFT_T,
                'a_left_m' => self::OPT_PAD_A_LEFT_M,
            );
        }
        $key = $g . '_' . $side . '_' . $bp;
        return isset($map[$key]) ? $map[$key] : self::OPT_PAD_C_TOP_D;
    }

    /**
     * Saved padding or recommended default when the option was never stored.
     *
     * @param string $g c | q | a
     * @param string $side top | right | bottom | left
     * @param string $bp d | t | m
     */
    private function get_admin_pad_field_value($g, $side, $bp)
    {
        static $defaults = null;
        if ($defaults === null) {
            $z = array('top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0');
            $defaults = array(
                'c' => array('d' => $z, 't' => $z, 'm' => $z),
                'q' => array(
                    'd' => array('top' => '18', 'right' => '56', 'bottom' => '18', 'left' => '20'),
                    't' => $z,
                    'm' => $z,
                ),
                'a' => array(
                    'd' => array('top' => '12', 'right' => '20', 'bottom' => '16', 'left' => '20'),
                    't' => $z,
                    'm' => $z,
                ),
            );
        }
        $opt = $this->pad_option_for($g, $side, $bp);
        $v = get_option($opt, null);
        if ($v !== null && $v !== false && $v !== '') {
            return (string) $v;
        }
        $is_plain_settings = ((string) get_option(self::OPT_DISPLAY_MODE, 'accordion') === 'plain');
        if ($is_plain_settings && ($g === 'q' || $g === 'a') && ($side === 'left' || $side === 'right')) {
            return '0';
        }
        $def = isset($defaults[$g][$bp][$side]) ? $defaults[$g][$bp][$side] : '0';

        return $def;
    }

    /**
     * @param string $g c | q | a
     */
    private function render_admin_trbl_padding_block($g, $heading)
    {
        $bps = array(
            'd' => __('Desktop', 'emg-faq'),
            't' => __('Tablet (≤1024px)', 'emg-faq'),
            'm' => __('Mobile (≤782px)', 'emg-faq'),
        );
        $sides = array(
            'top' => __('Top', 'emg-faq'),
            'right' => __('Right', 'emg-faq'),
            'bottom' => __('Bottom', 'emg-faq'),
            'left' => __('Left', 'emg-faq'),
        );
        $hints = array(
            'c' => __('Wraps the FAQ block and two-column wrapper.', 'emg-faq'),
            'q' => __('The question row (accordion button or plain heading).', 'emg-faq'),
            'a' => __('The answer area (accordion: inside the panel content box; plain: the answer block).', 'emg-faq'),
        );
        $uid = 'emg-faq-pad-bp-' . preg_replace('/[^a-z]/', '', $g);
        $g_class = preg_replace('/[^a-z]/', '', $g);
        echo '<div class="emg-faq-pad-section emg-faq-pad-section--' . esc_attr($g_class) . '">';
        echo '<div class="emg-faq-pad-head">' . esc_html($heading) . '</div>';
        if (isset($hints[$g])) {
            echo '<p class="description emg-faq-pad-hint">' . esc_html($hints[$g]) . '</p>';
        }
        echo '<div class="emg-faq-field-row emg-faq-pad-bp-row">';
        echo '<label for="' . esc_attr($uid) . '" class="emg-faq-field-label" style="margin-right:10px;">' . esc_html__('Breakpoint', 'emg-faq') . '</label>';
        echo '<select id="' . esc_attr($uid) . '" class="emg-faq-pad-bp-toggle" style="max-width:280px;">';
        foreach ($bps as $bp => $bp_label) {
            echo '<option value="' . esc_attr($bp) . '">' . esc_html($bp_label) . '</option>';
        }
        echo '</select></div>';

        foreach ($bps as $bp => $bp_label) {
            $vis = ($bp === 'd') ? '' : ' style="display:none;"';
            echo '<div class="emg-faq-pad-bp-panel" data-bp="' . esc_attr($bp) . '"' . $vis . '>';
            echo '<div class="emg-faq-trbl-grid" style="margin-top:6px;">';
            foreach ($sides as $side => $slabel) {
                $opt = $this->pad_option_for($g, $side, $bp);
                $val = $this->get_admin_pad_field_value($g, $side, $bp);
                echo '<label>' . esc_html($slabel);
                echo '<input type="number" class="widefat" min="0" max="120" name="' . esc_attr($opt) . '" value="' . esc_attr($val) . '" /></label>';
            }
            echo '</div>';
            if ($bp === 't' || $bp === 'm') {
                echo '<p class="description" style="margin:8px 0 0;">' . esc_html__('Use 0 on this breakpoint to keep using the desktop padding for that side.', 'emg-faq') . '</p>';
            }
            echo '</div>';
        }
        echo '</div>';
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
        $icon_style_ui = ($icon_style === 'arrow') ? 'chevron' : $icon_style;
        $auto_schema = (string) get_option(self::OPT_MANUAL_SCHEMA, '1');
        $schema_output_enabled = (string) get_option(self::OPT_SCHEMA_OUTPUT_ENABLED, '1');
        $question_font_size = (string) get_option(self::OPT_Q_FONT_SIZE, '16');
        $question_color = (string) get_option(self::OPT_Q_COLOR, '#111827');
        $answer_font_size = (string) get_option(self::OPT_A_FONT_SIZE, '16');
        $answer_color = (string) get_option(self::OPT_A_COLOR, '#374151');
        $saved_ibw = get_option(self::OPT_ITEM_BORDER_WIDTH, false);
        if ($saved_ibw === false || $saved_ibw === null) {
            $item_border_width = ($display_mode === 'plain') ? '0' : '1';
        } else {
            $item_border_width = (string) $saved_ibw;
        }
        $item_border_color = (string) get_option(self::OPT_ITEM_BORDER_COLOR, '#dddddd');
        $item_border_radius = (string) get_option(self::OPT_ITEM_BORDER_RADIUS, '8');
        $item_border_sides = get_option(self::OPT_ITEM_BORDER_SIDES, array('top', 'right', 'bottom', 'left'));
        if (!is_array($item_border_sides)) {
            $item_border_sides = array('top', 'right', 'bottom', 'left');
        }
        $two_column_layout = (string) get_option(self::OPT_TWO_COLUMN_LAYOUT, '0');
        $open_first = (string) get_option(self::OPT_OPEN_FIRST, '0');
        $multiple_open = (string) get_option(self::OPT_MULTIPLE_OPEN, '0');
        $anim_ms = (string) get_option(self::OPT_ANIM_MS, '300');
        $icon_position = (string) get_option(self::OPT_ICON_POSITION, 'right');
        $icon_size = (string) get_option(self::OPT_ICON_SIZE, '0');
        $icon_color = (string) get_option(self::OPT_ICON_COLOR, '');
        $icon_color_open = (string) get_option(self::OPT_ICON_COLOR_OPEN, '');
        $icon_ring_border = (string) get_option(self::OPT_ICON_RING_BORDER, '1');
        $heading_weight = (string) get_option(self::OPT_HEADING_WEIGHT, '0');
        $heading_size_t = (string) get_option(self::OPT_HEADING_SIZE_T, '0');
        $heading_size_m = (string) get_option(self::OPT_HEADING_SIZE_M, '0');
        $content_lh = (string) get_option(self::OPT_CONTENT_LH, '0');
        $content_fs_t = (string) get_option(self::OPT_CONTENT_FS_T, '0');
        $content_fs_m = (string) get_option(self::OPT_CONTENT_FS_M, '0');
        $item_gap = (string) get_option(self::OPT_ITEM_GAP, '0');
        $item_bg = (string) get_option(self::OPT_ITEM_BG, '');
        $item_bg_open = (string) get_option(self::OPT_ITEM_BG_OPEN, '');
        $q_hover_bg = (string) get_option(self::OPT_Q_HOVER_BG, '');
        $container_bg = (string) get_option(self::OPT_CONTAINER_BG, '');
        $content_lh_t = (string) get_option(self::OPT_CONTENT_LH_T, '0');
        $content_lh_m = (string) get_option(self::OPT_CONTENT_LH_M, '0');
        $custom_css = (string) get_option(self::OPT_CUSTOM_CSS, '');
        $column_gap = (string) get_option(self::OPT_COLUMN_GAP, '0');
        $smooth_panel = (string) get_option(self::OPT_SMOOTH_PANEL_ANIM, '1');
        ?>
        <div class="wrap emg-faq-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <style>
                .emg-faq-tab-panel {
                    display: none;
                    margin-top: 16px;
                    max-width: 920px;
                }

                .emg-faq-tab-panel.is-active {
                    display: block;
                }

                .emg-faq-style-section {
                    border: 1px solid #c3c4c7;
                    background: #fff;
                    padding: 0;
                    margin: 0 0 16px;
                    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                    border-radius: 2px;
                }

                .emg-faq-style-section>summary {
                    list-style: none;
                    cursor: pointer;
                    padding: 12px 16px;
                    margin: 0;
                    font-size: 14px;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    user-select: none;
                    background: #f6f7f7;
                }

                .emg-faq-style-section>summary::-webkit-details-marker {
                    display: none;
                }

                .emg-faq-style-section>summary::marker {
                    content: '';
                }

                .emg-faq-style-section>summary::before {
                    content: '\203A';
                    font-size: 18px;
                    line-height: 1;
                    font-weight: bold;
                    display: inline-block;
                    transition: transform .15s ease;
                }

                .emg-faq-style-section[open]>summary::before {
                    transform: rotate(90deg);
                }

                .emg-faq-style-section-inner {
                    padding: 18px 20px;
                    border-top: 1px solid #dcdcde;
                }

                .emg-faq-style-section-inner>p.description:first-child {
                    margin-top: 0;
                }

                .emg-faq-style-section-inner>p.description {
                    margin-bottom: 16px;
                }

                .emg-faq-field-row {
                    margin-bottom: 20px;
                }

                .emg-faq-field-row:last-child {
                    margin-bottom: 0;
                }

                .emg-faq-field-label {
                    display: block;
                    font-weight: 600;
                    margin: 0 0 8px;
                    font-size: 13px;
                }

                .emg-faq-field-note {
                    display: block;
                    margin: 8px 0 0;
                    font-size: 12px;
                    color: #646970;
                    line-height: 1.45;
                }

                .emg-faq-pad-section {
                    margin-bottom: 20px;
                    border: 1px solid #c3c4c7;
                    border-radius: 6px;
                    padding: 14px 16px 16px;
                    background: #fff;
                }

                .emg-faq-pad-section--c {
                    border-left: 4px solid #2271b1;
                }

                .emg-faq-pad-section--q {
                    border-left: 4px solid #1d8f5c;
                }

                .emg-faq-pad-section--a {
                    border-left: 4px solid #b7791f;
                }

                .emg-faq-pad-head {
                    font-size: 15px;
                    font-weight: 600;
                    margin: 0 0 8px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #dcdcde;
                    line-height: 1.35;
                    color: #1d2327;
                }

                .emg-faq-pad-hint {
                    margin: -4px 0 12px !important;
                    font-size: 12px;
                    color: #646970;
                }

                .emg-faq-tri-grid,
                .emg-faq-trbl-grid {
                    display: grid;
                    gap: 12px 16px;
                    align-items: end;
                }

                .emg-faq-tri-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }

                .emg-faq-trbl-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }

                .emg-faq-tri-grid label,
                .emg-faq-trbl-grid label {
                    display: block;
                    font-weight: 500;
                    font-size: 12px;
                    color: #1d2327;
                }

                .emg-faq-tri-grid input[type="number"],
                .emg-faq-trbl-grid input[type="number"],
                .emg-faq-field-row input.widefat,
                .emg-faq-field-row select {
                    margin-top: 6px;
                }

                .emg-faq-color-stack .wp-picker-container {
                    margin-top: 6px;
                }

                @media (max-width: 782px) {

                    .emg-faq-tri-grid,
                    .emg-faq-trbl-grid {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                    }
                }

                @media (max-width: 600px) {

                    .emg-faq-tri-grid,
                    .emg-faq-trbl-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <form method="post" action="options.php">
                <?php settings_fields('emg_faq_settings'); ?>
                <?php settings_errors('emg_faq_settings'); ?>

                <h2 class="nav-tab-wrapper emg-faq-settings-tabs wp-clearfix" style="margin-top:12px;padding-top:0;">
                    <a href="#emg-faq-tab-general" class="nav-tab nav-tab-active"><?php esc_html_e('General', 'emg-faq'); ?></a>
                    <a href="#emg-faq-tab-style" class="nav-tab"><?php esc_html_e('Style', 'emg-faq'); ?></a>
                    <a href="#emg-faq-tab-advanced" class="nav-tab"><?php esc_html_e('Advanced', 'emg-faq'); ?></a>
                    <a href="#emg-faq-tab-seo" class="nav-tab"><?php esc_html_e('SEO / Schema', 'emg-faq'); ?></a>
                    <a href="#emg-faq-tab-usage" class="nav-tab"><?php esc_html_e('Usage', 'emg-faq'); ?></a>
                </h2>

                <div id="emg-faq-tab-general" class="emg-faq-tab-panel is-active">
                    <h2><?php esc_html_e('FAQ Items', 'emg-faq'); ?></h2>
                    <p>Each FAQ has its own Question and Answer fields. HTML is allowed in both fields (like <code>h1-h6</code>,
                        <code>p</code>, <code>ul</code>, <code>li</code>).
                    </p>
                    <div id="emg-faq-items-list">
                        <?php if (empty($default_faq)): ?>
                            <div class="emg-faq-admin-item"
                                style="border:1px solid #dcdcde;padding:14px;margin-bottom:12px;background:#fff;">
                                <p style="margin:0 0 8px;">
                                    <label><strong>Question</strong></label><br />
                                    <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>[0][question]" rows="3"
                                        style="width:100%;"></textarea>
                                </p>
                                <p style="margin:0 0 8px;">
                                    <label><strong>Answer</strong></label><br />
                                    <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>[0][answer]" rows="5"
                                        style="width:100%;"></textarea>
                                </p>
                                <button type="button" class="button emg-faq-remove-item">Remove</button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($default_faq as $idx => $row): ?>
                                <?php
                                $q = isset($row['question']) ? (string) $row['question'] : '';
                                $a = isset($row['answer']) ? (string) $row['answer'] : '';
                                ?>
                                <div class="emg-faq-admin-item"
                                    style="border:1px solid #dcdcde;padding:14px;margin-bottom:12px;background:#fff;">
                                    <p style="margin:0 0 8px;">
                                        <label><strong>Question</strong></label><br />
                                        <textarea
                                            name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>[<?php echo esc_attr((string) $idx); ?>][question]"
                                            rows="3" style="width:100%;"><?php echo esc_textarea($q); ?></textarea>
                                    </p>
                                    <p style="margin:0 0 8px;">
                                        <label><strong>Answer</strong></label><br />
                                        <textarea
                                            name="<?php echo esc_attr(self::OPT_DEFAULT_FAQ); ?>[<?php echo esc_attr((string) $idx); ?>][answer]"
                                            rows="5" style="width:100%;"><?php echo esc_textarea($a); ?></textarea>
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

                    <h2 style="margin-top:24px;"><?php esc_html_e('FAQ Wrapper (Optional)', 'emg-faq'); ?></h2>
                    <p><?php esc_html_e('Use', 'emg-faq'); ?> <code>{{faq_items}}</code>, <code>{{faq_content}}</code>,
                        <?php esc_html_e('or', 'emg-faq'); ?> <code>{{faq}}</code>
                        <?php esc_html_e('where the FAQ block should appear.', 'emg-faq'); ?>
                    </p>
                    <textarea name="<?php echo esc_attr(self::OPT_WRAPPER_TEMPLATE); ?>" rows="8"
                        style="width:100%;"><?php echo esc_textarea($wrapper_template); ?></textarea>
                </div>

                <div id="emg-faq-tab-style" class="emg-faq-tab-panel">
                    <p class="description">
                        <?php esc_html_e('Optional fields: use 0 or empty to keep the plugin’s original default look.', 'emg-faq'); ?>
                    </p>

                    <details class="emg-faq-style-section" open>
                        <summary><?php esc_html_e('Layout', 'emg-faq'); ?></summary>
                        <div class="emg-faq-style-section-inner">
                            <p class="description">
                                <?php esc_html_e('One column by default; enable two columns for wide screens (stacks on tablet/mobile). Column heights stay independent (no equal-height stretch).', 'emg-faq'); ?>
                            </p>
                            <div class="emg-faq-field-row">
                                <span class="emg-faq-field-label"><?php esc_html_e('View mode', 'emg-faq'); ?></span>
                                <select id="emg-faq-display-mode" class="widefat" style="max-width:320px;"
                                    name="<?php echo esc_attr(self::OPT_DISPLAY_MODE); ?>">
                                    <option value="accordion" <?php selected($display_mode, 'accordion'); ?>>
                                        <?php esc_html_e('Accordion', 'emg-faq'); ?>
                                    </option>
                                    <option value="plain" <?php selected($display_mode, 'plain'); ?>>
                                        <?php esc_html_e('Plain (Q & A)', 'emg-faq'); ?>
                                    </option>
                                </select>
                            </div>
                            <div class="emg-faq-field-row">
                                <label>
                                    <input type="hidden" name="<?php echo esc_attr(self::OPT_TWO_COLUMN_LAYOUT); ?>"
                                        value="0" />
                                    <input type="checkbox" id="emg-faq-two-column"
                                        name="<?php echo esc_attr(self::OPT_TWO_COLUMN_LAYOUT); ?>" value="1" <?php checked($two_column_layout, '1'); ?> />
                                    <?php esc_html_e('Two-column layout (optional)', 'emg-faq'); ?>
                                </label>
                            </div>
                            <div id="emg-faq-column-gap-wrap" class="emg-faq-field-row"
                                style="<?php echo esc_attr($two_column_layout === '1' ? '' : 'display:none;'); ?>">
                                <span class="emg-faq-field-label"><?php esc_html_e('Column gap', 'emg-faq'); ?></span>
                                <input type="number" class="small-text" min="0" max="80"
                                    name="<?php echo esc_attr(self::OPT_COLUMN_GAP); ?>"
                                    value="<?php echo esc_attr($column_gap); ?>" placeholder="20" />
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Default: 20px between columns when left at 0.', 'emg-faq'); ?></span>
                            </div>
                            <div class="emg-faq-field-row">
                                <span class="emg-faq-field-label"><?php esc_html_e('Item gap', 'emg-faq'); ?></span>
                                <input type="number" class="small-text" min="0" max="64"
                                    name="<?php echo esc_attr(self::OPT_ITEM_GAP); ?>"
                                    value="<?php echo esc_attr($item_gap); ?>" placeholder="10" />
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Controls spacing between FAQ items (same as row gap). Default: 10px when left at 0.', 'emg-faq'); ?></span>
                            </div>
                        </div>
                    </details>

                    <details class="emg-faq-style-section">
                        <summary><?php esc_html_e('Typography', 'emg-faq'); ?></summary>
                        <div class="emg-faq-style-section-inner">
                            <div class="emg-faq-field-row">
                                <span class="emg-faq-field-label"><?php esc_html_e('Question font size', 'emg-faq'); ?></span>
                                <div class="emg-faq-tri-grid">
                                    <label><?php esc_html_e('Mobile (px)', 'emg-faq'); ?>
                                        <input type="number" class="widefat" min="0" max="72" placeholder="16"
                                            name="<?php echo esc_attr(self::OPT_HEADING_SIZE_M); ?>"
                                            value="<?php echo esc_attr($heading_size_m); ?>" />
                                    </label>
                                    <label><?php esc_html_e('Tablet (px)', 'emg-faq'); ?>
                                        <input type="number" class="widefat" min="0" max="72" placeholder="16"
                                            name="<?php echo esc_attr(self::OPT_HEADING_SIZE_T); ?>"
                                            value="<?php echo esc_attr($heading_size_t); ?>" />
                                    </label>
                                    <label><?php esc_html_e('Desktop (px)', 'emg-faq'); ?>
                                        <input type="number" class="widefat" min="8" max="72" placeholder="16"
                                            name="<?php echo esc_attr(self::OPT_Q_FONT_SIZE); ?>"
                                            value="<?php echo esc_attr($question_font_size); ?>" />
                                    </label>
                                </div>
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Default: 16px. Use 0 on tablet/mobile to keep desktop size at that breakpoint.', 'emg-faq'); ?></span>
                            </div>
                            <div class="emg-faq-field-row">
                                <span class="emg-faq-field-label"><?php esc_html_e('Answer font size', 'emg-faq'); ?></span>
                                <div class="emg-faq-tri-grid">
                                    <label><?php esc_html_e('Mobile (px)', 'emg-faq'); ?>
                                        <input type="number" class="widefat" min="0" max="72" placeholder="16"
                                            name="<?php echo esc_attr(self::OPT_CONTENT_FS_M); ?>"
                                            value="<?php echo esc_attr($content_fs_m); ?>" />
                                    </label>
                                    <label><?php esc_html_e('Tablet (px)', 'emg-faq'); ?>
                                        <input type="number" class="widefat" min="0" max="72" placeholder="16"
                                            name="<?php echo esc_attr(self::OPT_CONTENT_FS_T); ?>"
                                            value="<?php echo esc_attr($content_fs_t); ?>" />
                                    </label>
                                    <label><?php esc_html_e('Desktop (px)', 'emg-faq'); ?>
                                        <input type="number" class="widefat" min="8" max="72" placeholder="16"
                                            name="<?php echo esc_attr(self::OPT_A_FONT_SIZE); ?>"
                                            value="<?php echo esc_attr($answer_font_size); ?>" />
                                    </label>
                                </div>
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Default: 16px. Tablet/mobile 0 = no override at that breakpoint.', 'emg-faq'); ?></span>
                            </div>
                            <div class="emg-faq-field-row">
                                <span class="emg-faq-field-label"><?php esc_html_e('Answer line height', 'emg-faq'); ?></span>
                                <div class="emg-faq-tri-grid">
                                    <label><?php esc_html_e('Mobile', 'emg-faq'); ?>
                                        <input type="number" class="widefat" min="0" max="3" step="0.05" placeholder="1.7"
                                            name="<?php echo esc_attr(self::OPT_CONTENT_LH_M); ?>"
                                            value="<?php echo esc_attr($content_lh_m); ?>" />
                                    </label>
                                    <label><?php esc_html_e('Tablet', 'emg-faq'); ?>
                                        <input type="number" class="widefat" min="0" max="3" step="0.05" placeholder="1.7"
                                            name="<?php echo esc_attr(self::OPT_CONTENT_LH_T); ?>"
                                            value="<?php echo esc_attr($content_lh_t); ?>" />
                                    </label>
                                    <label><?php esc_html_e('Desktop', 'emg-faq'); ?>
                                        <input type="number" class="widefat" min="0" max="3" step="0.05" placeholder="1.7"
                                            name="<?php echo esc_attr(self::OPT_CONTENT_LH); ?>"
                                            value="<?php echo esc_attr($content_lh); ?>" />
                                    </label>
                                </div>
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Unitless multiplier (e.g. 1.7). Default ≈ 1.7. Use 0 to keep the built-in default.', 'emg-faq'); ?></span>
                            </div>
                            <div class="emg-faq-field-row">
                                <span
                                    class="emg-faq-field-label"><?php esc_html_e('Font weight (question / title)', 'emg-faq'); ?></span>
                                <select class="widefat" style="max-width:320px;"
                                    name="<?php echo esc_attr(self::OPT_HEADING_WEIGHT); ?>">
                                    <option value="0" <?php selected($heading_weight, '0'); ?>>
                                        <?php esc_html_e('Default (700)', 'emg-faq'); ?>
                                    </option>
                                    <?php foreach (array(100, 200, 300, 400, 500, 600, 700, 800, 900) as $w): ?>
                                        <option value="<?php echo esc_attr((string) $w); ?>" <?php selected($heading_weight, (string) $w); ?>><?php echo esc_html((string) $w); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Applies to section title and question text.', 'emg-faq'); ?></span>
                            </div>
                        </div>
                    </details>

                    <details class="emg-faq-style-section">
                        <summary><?php esc_html_e('Colors', 'emg-faq'); ?></summary>
                        <div class="emg-faq-style-section-inner">
                            <div class="emg-faq-field-row emg-faq-color-stack">
                                <span class="emg-faq-field-label"><?php esc_html_e('Question color', 'emg-faq'); ?></span>
                                <input type="text"
                                    class="<?php echo esc_attr($this->admin_color_input_classes($question_color)); ?>"
                                    name="<?php echo esc_attr(self::OPT_Q_COLOR); ?>"
                                    value="<?php echo esc_attr($question_color); ?>" placeholder="#111827" autocomplete="off" />
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('HEX, rgb(), or rgba(). WordPress color picker appears for hex; use the field for transparency.', 'emg-faq'); ?>
                                    <?php esc_html_e('Default:', 'emg-faq'); ?> #111827</span>
                            </div>
                            <div class="emg-faq-field-row emg-faq-color-stack">
                                <span class="emg-faq-field-label"><?php esc_html_e('Answer color', 'emg-faq'); ?></span>
                                <input type="text"
                                    class="<?php echo esc_attr($this->admin_color_input_classes($answer_color)); ?>"
                                    name="<?php echo esc_attr(self::OPT_A_COLOR); ?>"
                                    value="<?php echo esc_attr($answer_color); ?>" placeholder="#374151" autocomplete="off" />
                                <span class="emg-faq-field-note"><?php esc_html_e('Default:', 'emg-faq'); ?> #374151</span>
                            </div>
                            <div class="emg-faq-field-row emg-faq-color-stack">
                                <span class="emg-faq-field-label"><?php esc_html_e('Container background', 'emg-faq'); ?></span>
                                <input type="text"
                                    class="<?php echo esc_attr($this->admin_color_input_classes($container_bg)); ?>"
                                    name="<?php echo esc_attr(self::OPT_CONTAINER_BG); ?>"
                                    value="<?php echo esc_attr($container_bg); ?>" placeholder="#ffffff" autocomplete="off" />
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Optional. Empty = transparent default.', 'emg-faq'); ?></span>
                            </div>
                            <div class="emg-faq-field-row emg-faq-color-stack">
                                <span class="emg-faq-field-label"><?php esc_html_e('Item background', 'emg-faq'); ?></span>
                                <input type="text" class="<?php echo esc_attr($this->admin_color_input_classes($item_bg)); ?>"
                                    name="<?php echo esc_attr(self::OPT_ITEM_BG); ?>" value="<?php echo esc_attr($item_bg); ?>"
                                    placeholder="#ffffff" autocomplete="off" />
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Optional. Applies to each FAQ card.', 'emg-faq'); ?></span>
                            </div>
                            <div class="emg-faq-field-row emg-faq-color-stack">
                                <span
                                    class="emg-faq-field-label"><?php esc_html_e('Item background when open', 'emg-faq'); ?></span>
                                <input type="text"
                                    class="<?php echo esc_attr($this->admin_color_input_classes($item_bg_open)); ?>"
                                    name="<?php echo esc_attr(self::OPT_ITEM_BG_OPEN); ?>"
                                    value="<?php echo esc_attr($item_bg_open); ?>" placeholder="rgba(0,0,0,0.04)"
                                    autocomplete="off" />
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Optional. Highlights the expanded row.', 'emg-faq'); ?></span>
                            </div>
                            <div class="emg-faq-field-row emg-faq-color-stack">
                                <span
                                    class="emg-faq-field-label"><?php esc_html_e('Question hover background', 'emg-faq'); ?></span>
                                <input type="text"
                                    class="<?php echo esc_attr($this->admin_color_input_classes($q_hover_bg)); ?>"
                                    name="<?php echo esc_attr(self::OPT_Q_HOVER_BG); ?>"
                                    value="<?php echo esc_attr($q_hover_bg); ?>" placeholder="#fafafa" autocomplete="off" />
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Optional. Default hover is a light gray if unset.', 'emg-faq'); ?></span>
                            </div>
                        </div>
                    </details>

                    <details class="emg-faq-style-section">
                        <summary><?php esc_html_e('Padding', 'emg-faq'); ?></summary>
                        <div class="emg-faq-style-section-inner">
                            <p class="description">
                                <?php esc_html_e('Container, question row, and answer area. Pick Desktop / Tablet / Mobile from the dropdown, then set Top / Right / Bottom / Left (px). Accordion answer padding applies inside the sliding panel (no extra gap when closed; open/close animation unchanged). Plain Q&amp;A defaults to no left/right padding on question and answer until you set values.', 'emg-faq'); ?>
                            </p>
                            <?php
                            $this->render_admin_trbl_padding_block('c', __('Container padding', 'emg-faq'));
                            $this->render_admin_trbl_padding_block('q', __('Question padding', 'emg-faq'));
                            $this->render_admin_trbl_padding_block('a', __('Answer padding', 'emg-faq'));
                            ?>
                        </div>
                    </details>

                    <details class="emg-faq-style-section">
                        <summary><?php esc_html_e('Icon settings', 'emg-faq'); ?></summary>
                        <div class="emg-faq-style-section-inner">
                            <div id="emg-faq-icon-style-wrap"
                                style="<?php echo esc_attr(($display_mode === 'accordion' ? '' : 'display:none;')); ?>">
                                <div class="emg-faq-field-row">
                                    <span class="emg-faq-field-label"><?php esc_html_e('Icon type', 'emg-faq'); ?></span>
                                    <select id="emg-faq-icon-style" class="widefat" style="max-width:320px;"
                                        name="<?php echo esc_attr(self::OPT_ACCORDION_ICON_STYLE); ?>">
                                        <option value="plusminus" <?php selected($icon_style_ui, 'plusminus'); ?>>
                                            <?php esc_html_e('Plus / minus', 'emg-faq'); ?>
                                        </option>
                                        <option value="chevron" <?php selected($icon_style_ui, 'chevron'); ?>>
                                            <?php esc_html_e('Arrow / chevron', 'emg-faq'); ?>
                                        </option>
                                    </select>
                                </div>
                                <div class="emg-faq-field-row">
                                    <label>
                                        <input type="hidden" name="<?php echo esc_attr(self::OPT_ICON_RING_BORDER); ?>"
                                            value="0" />
                                        <input type="checkbox" name="<?php echo esc_attr(self::OPT_ICON_RING_BORDER); ?>"
                                            value="1" <?php checked($icon_ring_border, '1'); ?> />
                                        <?php esc_html_e('Show rounded border ring around icon', 'emg-faq'); ?>
                                    </label>
                                    <span
                                        class="emg-faq-field-note"><?php esc_html_e('Uncheck for icon only (no circle outline).', 'emg-faq'); ?></span>
                                </div>
                            </div>
                            <div class="emg-faq-field-row">
                                <span class="emg-faq-field-label"><?php esc_html_e('Icon position', 'emg-faq'); ?></span>
                                <select class="widefat" style="max-width:320px;"
                                    name="<?php echo esc_attr(self::OPT_ICON_POSITION); ?>">
                                    <option value="right" <?php selected($icon_position, 'right'); ?>>
                                        <?php esc_html_e('Right', 'emg-faq'); ?>
                                    </option>
                                    <option value="left" <?php selected($icon_position, 'left'); ?>>
                                        <?php esc_html_e('Left', 'emg-faq'); ?>
                                    </option>
                                </select>
                            </div>
                            <div class="emg-faq-field-row">
                                <span class="emg-faq-field-label"><?php esc_html_e('Icon size', 'emg-faq'); ?></span>
                                <input type="number" class="small-text" min="0" max="48"
                                    name="<?php echo esc_attr(self::OPT_ICON_SIZE); ?>"
                                    value="<?php echo esc_attr($icon_size); ?>" placeholder="28" />
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Pixels. 0 = plugin default (28px icon box).', 'emg-faq'); ?></span>
                            </div>
                            <div class="emg-faq-field-row emg-faq-color-stack">
                                <span class="emg-faq-field-label"><?php esc_html_e('Icon color', 'emg-faq'); ?></span>
                                <input type="text"
                                    class="<?php echo esc_attr($this->admin_color_input_classes($icon_color)); ?>"
                                    name="<?php echo esc_attr(self::OPT_ICON_COLOR); ?>"
                                    value="<?php echo esc_attr($icon_color); ?>" placeholder="#000000" autocomplete="off" />
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Optional. Controls stroke / plus-minus / chevron color.', 'emg-faq'); ?></span>
                            </div>
                            <div class="emg-faq-field-row emg-faq-color-stack">
                                <span class="emg-faq-field-label"><?php esc_html_e('Icon color when open', 'emg-faq'); ?></span>
                                <input type="text"
                                    class="<?php echo esc_attr($this->admin_color_input_classes($icon_color_open)); ?>"
                                    name="<?php echo esc_attr(self::OPT_ICON_COLOR_OPEN); ?>"
                                    value="<?php echo esc_attr($icon_color_open); ?>" placeholder="#ffffff"
                                    autocomplete="off" />
                                <span
                                    class="emg-faq-field-note"><?php esc_html_e('Optional. When empty, open state uses the same color as closed.', 'emg-faq'); ?></span>
                            </div>
                        </div>
                    </details>

                    <details class="emg-faq-style-section">
                        <summary><?php esc_html_e('Accordion style & motion', 'emg-faq'); ?></summary>
                        <div class="emg-faq-style-section-inner">
                            <p class="description">
                                <?php esc_html_e('Item borders: accordion output uses your width by default (1px if you never saved a value). Plain Q&amp;A output defaults to no border until you set a width above 0. Each FAQ block sets data-emg-faq-mode so both can appear on the same page with correct styling.', 'emg-faq'); ?>
                            </p>
                            <div class="emg-faq-field-row">
                                <span
                                    class="emg-faq-field-label"><?php esc_html_e('Item border width (px)', 'emg-faq'); ?></span>
                                <input type="number" class="small-text" min="0" max="20"
                                    name="<?php echo esc_attr(self::OPT_ITEM_BORDER_WIDTH); ?>"
                                    value="<?php echo esc_attr($item_border_width); ?>"
                                    placeholder="<?php echo esc_attr($display_mode === 'plain' ? '0' : '1'); ?>" />
                                <span
                                    class="emg-faq-field-note"><?php echo esc_html($display_mode === 'plain' ? __('Plain default: 0 (no border). Accordion blocks still use 1px until you save a different value.', 'emg-faq') : __('Accordion default: 1px. Set &gt; 0 to show borders on plain Q&amp;A blocks too.', 'emg-faq')); ?></span>
                            </div>
                            <div class="emg-faq-field-row">
                                <span
                                    class="emg-faq-field-label"><?php esc_html_e('Item border radius (px)', 'emg-faq'); ?></span>
                                <input type="number" class="small-text" min="0" max="80"
                                    name="<?php echo esc_attr(self::OPT_ITEM_BORDER_RADIUS); ?>"
                                    value="<?php echo esc_attr($item_border_radius); ?>" placeholder="8" />
                                <span class="emg-faq-field-note"><?php esc_html_e('Default: 8px.', 'emg-faq'); ?></span>
                            </div>
                            <div class="emg-faq-field-row emg-faq-color-stack">
                                <span class="emg-faq-field-label"><?php esc_html_e('Border color', 'emg-faq'); ?></span>
                                <input type="text"
                                    class="<?php echo esc_attr($this->admin_color_input_classes($item_border_color)); ?>"
                                    name="<?php echo esc_attr(self::OPT_ITEM_BORDER_COLOR); ?>"
                                    value="<?php echo esc_attr($item_border_color); ?>" placeholder="#dddddd"
                                    autocomplete="off" />
                                <span class="emg-faq-field-note"><?php esc_html_e('Default:', 'emg-faq'); ?> #dddddd</span>
                            </div>
                            <p><?php esc_html_e('Border sides:', 'emg-faq'); ?>
                                <label style="margin-left:8px;margin-right:10px;"><input type="checkbox"
                                        name="<?php echo esc_attr(self::OPT_ITEM_BORDER_SIDES); ?>[]" value="top" <?php checked(in_array('top', $item_border_sides, true)); ?> />
                                    <?php esc_html_e('Top', 'emg-faq'); ?></label>
                                <label style="margin-right:10px;"><input type="checkbox"
                                        name="<?php echo esc_attr(self::OPT_ITEM_BORDER_SIDES); ?>[]" value="right" <?php checked(in_array('right', $item_border_sides, true)); ?> />
                                    <?php esc_html_e('Right', 'emg-faq'); ?></label>
                                <label style="margin-right:10px;"><input type="checkbox"
                                        name="<?php echo esc_attr(self::OPT_ITEM_BORDER_SIDES); ?>[]" value="bottom" <?php checked(in_array('bottom', $item_border_sides, true)); ?> />
                                    <?php esc_html_e('Bottom', 'emg-faq'); ?></label>
                                <label style="margin-right:10px;"><input type="checkbox"
                                        name="<?php echo esc_attr(self::OPT_ITEM_BORDER_SIDES); ?>[]" value="left" <?php checked(in_array('left', $item_border_sides, true)); ?> />
                                    <?php esc_html_e('Left', 'emg-faq'); ?></label>
                            </p>
                            <p>
                                <label>
                                    <input type="hidden" name="<?php echo esc_attr(self::OPT_SMOOTH_PANEL_ANIM); ?>"
                                        value="0" />
                                    <input type="checkbox" name="<?php echo esc_attr(self::OPT_SMOOTH_PANEL_ANIM); ?>" value="1"
                                        <?php checked($smooth_panel, '1'); ?> />
                                    <?php esc_html_e('Smooth panel open/close animation', 'emg-faq'); ?>
                                </label>
                            </p>
                            <p>
                                <label><?php esc_html_e('Transition duration (ms)', 'emg-faq'); ?>
                                    <input type="number" min="100" max="1500" step="10"
                                        name="<?php echo esc_attr(self::OPT_ANIM_MS); ?>"
                                        value="<?php echo esc_attr($anim_ms); ?>" />
                                </label>
                            </p>
                        </div>
                    </details>

                    <details class="emg-faq-style-section">
                        <summary><?php esc_html_e('Custom CSS', 'emg-faq'); ?></summary>
                        <div class="emg-faq-style-section-inner">
                            <p class="description">
                                <?php
                                printf(
                                    esc_html__('Start selectors with %s. Disallowed: @import and script-like syntax.', 'emg-faq'),
                                    '<code>.' . esc_html(self::SCOPE_ROOT_CLASS) . '</code>'
                                );
                                ?>
                            </p>
                            <textarea name="<?php echo esc_attr(self::OPT_CUSTOM_CSS); ?>" rows="10"
                                style="width:100%;max-width:920px;font-family:monospace;"><?php echo esc_textarea($custom_css); ?></textarea>
                        </div>
                    </details>
                </div>

                <div id="emg-faq-tab-advanced" class="emg-faq-tab-panel">
                    <h2><?php esc_html_e('Accordion behavior', 'emg-faq'); ?></h2>
                    <p>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr(self::OPT_OPEN_FIRST); ?>" value="0" />
                            <input type="checkbox" name="<?php echo esc_attr(self::OPT_OPEN_FIRST); ?>" value="1" <?php checked($open_first, '1'); ?> />
                            <?php esc_html_e('Open first item by default', 'emg-faq'); ?>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr(self::OPT_MULTIPLE_OPEN); ?>" value="0" />
                            <input type="checkbox" name="<?php echo esc_attr(self::OPT_MULTIPLE_OPEN); ?>" value="1" <?php checked($multiple_open, '1'); ?> />
                            <?php esc_html_e('Allow multiple items open at once (within the same column)', 'emg-faq'); ?>
                        </label>
                    </p>
                    <p class="description">
                        <?php esc_html_e('Panel animation duration and smooth motion are set under Style → Accordion style & motion. JSON-LD is under SEO / Schema.', 'emg-faq'); ?>
                    </p>
                </div>

                <div id="emg-faq-tab-seo" class="emg-faq-tab-panel">
                    <h2><?php esc_html_e('Schema Settings', 'emg-faq'); ?></h2>
                    <p>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr(self::OPT_SCHEMA_OUTPUT_ENABLED); ?>" value="0" />
                            <input type="checkbox" name="<?php echo esc_attr(self::OPT_SCHEMA_OUTPUT_ENABLED); ?>" value="1"
                                <?php checked($schema_output_enabled, '1'); ?> />
                            <?php esc_html_e('Show schema output (JSON-LD)', 'emg-faq'); ?>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="hidden" name="<?php echo esc_attr(self::OPT_MANUAL_SCHEMA); ?>" value="0" />
                            <input type="checkbox" id="emg-faq-auto-schema-enabled"
                                name="<?php echo esc_attr(self::OPT_MANUAL_SCHEMA); ?>" value="1" <?php checked($auto_schema, '1'); ?> />
                            <?php esc_html_e('Auto generate schema from FAQ items', 'emg-faq'); ?>
                        </label>
                    </p>
                    <p class="description">
                        <strong><?php esc_html_e('Checked:', 'emg-faq'); ?></strong>
                        <?php esc_html_e('schema is auto-created from visible FAQ items.', 'emg-faq'); ?><br>
                        <strong><?php esc_html_e('Unchecked:', 'emg-faq'); ?></strong>
                        <?php esc_html_e('custom schema box appears below, and you can provide your own JSON-LD.', 'emg-faq'); ?><br>
                        <?php esc_html_e('If custom JSON is empty or invalid, plugin falls back to auto schema.', 'emg-faq'); ?>
                    </p>

                    <div id="emg-faq-schema-field-wrap"
                        style="<?php echo esc_attr($auto_schema === '1' ? 'display:none;' : ''); ?>">
                        <h3><?php esc_html_e('Custom Schema JSON (Optional)', 'emg-faq'); ?></h3>
                        <textarea name="<?php echo esc_attr(self::OPT_DEFAULT_SCHEMA); ?>" rows="14"
                            style="width:100%;"><?php echo esc_textarea($default_schema); ?></textarea>
                    </div>
                </div>

                <div id="emg-faq-tab-usage" class="emg-faq-tab-panel">
                    <details class="emg-faq-style-section">
                        <summary><?php esc_html_e('Overview', 'emg-faq'); ?></summary>
                        <div class="emg-faq-style-section-inner">
                            <p><?php esc_html_e('Place the shortcode on any post or page. Assets load only when the shortcode runs, so the rest of the site stays lean.', 'emg-faq'); ?>
                            </p>
                            <p><?php esc_html_e('Use the FAQ list from the General tab, or override it with inner content and selectors for a single block.', 'emg-faq'); ?>
                            </p>
                        </div>
                    </details>

                    <details class="emg-faq-style-section" open>
                        <summary><?php esc_html_e('Examples', 'emg-faq'); ?></summary>
                        <div class="emg-faq-style-section-inner">
                            <ol class="emg-faq-usage-list" style="margin-left:1.25em;">
                                <li style="margin-bottom:10px;">
                                    <strong><?php esc_html_e('Basic', 'emg-faq'); ?></strong><br />
                                    <code>[emg_faq]</code>
                                    <button type="button" class="button button-small emg-faq-copy-btn"
                                        data-clipboard="[emg_faq]"><?php esc_html_e('Copy', 'emg-faq'); ?></button>
                                </li>
                                <li style="margin-bottom:10px;">
                                    <strong><?php esc_html_e('With city placeholder', 'emg-faq'); ?></strong><br />
                                    <code>[emg_faq city="Dallas"]</code>
                                    <button type="button" class="button button-small emg-faq-copy-btn"
                                        data-clipboard='[emg_faq city="Dallas"]'><?php esc_html_e('Copy', 'emg-faq'); ?></button>
                                </li>
                                <li style="margin-bottom:10px;">
                                    <strong><?php esc_html_e('Mode', 'emg-faq'); ?></strong><br />
                                    <code>[emg_faq mode="plain"]</code>
                                    <button type="button" class="button button-small emg-faq-copy-btn"
                                        data-clipboard='[emg_faq mode="plain"]'><?php esc_html_e('Copy plain', 'emg-faq'); ?></button>
                                    &nbsp;
                                    <code>[emg_faq mode="accordion"]</code>
                                    <button type="button" class="button button-small emg-faq-copy-btn"
                                        data-clipboard='[emg_faq mode="accordion"]'><?php esc_html_e('Copy accordion', 'emg-faq'); ?></button>
                                </li>
                                <li>
                                    <strong><?php esc_html_e('Custom content (selectors)', 'emg-faq'); ?></strong><br />
                                    <code>[emg_faq city="Dallas" question_selector=".faq-q" answer_selector=".faq-a"]</code>
                                    <button type="button" class="button button-small emg-faq-copy-btn"
                                        data-clipboard='[emg_faq city="Dallas" question_selector=".faq-q" answer_selector=".faq-a"]'><?php esc_html_e('Copy', 'emg-faq'); ?></button>
                                </li>
                            </ol>
                        </div>
                    </details>

                    <details class="emg-faq-style-section">
                        <summary><?php esc_html_e('Custom content & schema', 'emg-faq'); ?></summary>
                        <div class="emg-faq-style-section-inner">
                            <ul style="margin-left:1.25em;">
                                <li><?php esc_html_e('When you use inner HTML with question/answer selectors (or tags), that block replaces the global FAQ list for that shortcode only.', 'emg-faq'); ?>
                                </li>
                                <li><?php esc_html_e('Schema (JSON-LD) is off for enclosing content unless you add generate_schema="yes" (or 1/true/on). Then the plugin can build FAQPage from the parsed Q&A, respecting your SEO / Schema settings.', 'emg-faq'); ?>
                                </li>
                                <li><?php esc_html_e('strip_tags="true" or the stripe flag (selector mode) forces plain text for display and schema; omit or set strip_tags="false" to keep allowed HTML in answers.', 'emg-faq'); ?>
                                </li>
                            </ul>
                        </div>
                    </details>

                </div>

                <?php submit_button(__('Save Settings', 'emg-faq')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * @param string $tag
     * @return string
     */
    private function sanitize_shortcode_html_tag($tag)
    {
        $tag = strtolower(preg_replace('/[^a-z]/', '', (string) $tag));
        $allowed = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span', 'strong', 'em', 'li', 'blockquote');
        return in_array($tag, $allowed, true) ? $tag : 'p';
    }

    /**
     * @param string $sel
     * @return string
     */
    private function sanitize_shortcode_selector($sel)
    {
        $sel = trim((string) $sel);
        if ($sel === '') {
            return '';
        }
        if (strlen($sel) > 220) {
            $sel = substr($sel, 0, 220);
        }
        if (preg_match('/[<>\'"\x00-\x08\x0b\x0c\x0e-\x1f\\\\]/', $sel)) {
            return '';
        }
        return $sel;
    }

    /**
     * @param string $classes
     * @return string
     */
    private function sanitize_shortcode_class_list($classes)
    {
        $classes = trim((string) $classes);
        if ($classes === '') {
            return '';
        }
        $out = array();
        foreach (preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY) as $p) {
            $c = sanitize_html_class($p);
            if ($c !== '') {
                $out[] = $c;
            }
            if (count($out) >= 24) {
                break;
            }
        }
        return implode(' ', array_unique($out));
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
                'list_separation' => '',
            ),
            $atts,
            'emg_faq'
        );

        $atts['question'] = $this->sanitize_shortcode_html_tag($atts['question']);
        $atts['answer'] = $this->sanitize_shortcode_html_tag($atts['answer']);
        $atts['question_selector'] = $this->sanitize_shortcode_selector($atts['question_selector']);
        $atts['answer_selector'] = $this->sanitize_shortcode_selector($atts['answer_selector']);
        $atts['class'] = $this->sanitize_shortcode_class_list($atts['class']);

        $atts['title'] = sanitize_text_field((string) $atts['title']);
        $list_separation_schema = $this->sanitize_list_separation_for_schema($atts['list_separation']);

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
            $schema_final = $schema_output_enabled ? $this->resolve_schema_for_output($items, $schema_raw, $auto_schema_on, $list_separation_schema) : '';
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

            $schema_final = $this->resolve_schema_for_output($items_from_tags, $schema_raw, $auto_schema_on, $list_separation_schema);
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
        return $this->wrap_faq_root($this->apply_wrapper_template(ob_get_clean()), 'freeform');
    }

    /**
     * Manual schema when enabled + valid; otherwise auto FAQPage from items.
     */
    private function resolve_schema_for_output($items, $schema_raw, $auto_schema_on, $list_separation = 'space')
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

        return $this->build_faqpage_schema_json($items, $list_separation);
    }

    /**
     * Shortcode list_separation → internal mode for schema flattening only (whitelist).
     *
     * @param mixed $value
     * @return string 'space'|'comma'|'dot'
     */
    private function sanitize_list_separation_for_schema($value)
    {
        $v = strtolower(trim((string) $value));
        if ($v === 'comma' || $v === ',') {
            return 'comma';
        }
        if ($v === 'dot' || $v === 'period' || $v === '.') {
            return 'dot';
        }

        return 'space';
    }

    private function build_faqpage_schema_json($items, $list_separation = 'space')
    {
        $list_separation = $this->sanitize_list_separation_for_schema($list_separation);
        $main_entity = array();
        foreach ($items as $item) {
            $q = isset($item['q']) ? $this->flatten_html_text((string) $item['q'], $list_separation) : '';
            $a = isset($item['a']) ? $this->flatten_html_text((string) $item['a'], $list_separation) : '';
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

    private function flatten_html_text($value, $list_separation = 'space')
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        $list_mode = $this->sanitize_list_separation_for_schema($list_separation);
        // Only between adjacent &lt;li&gt; — never after the last item (would produce e.g. "project., ").
        if ($list_mode === 'comma') {
            $value = preg_replace('/<\/li\s*>\s*<li\b[^>]*>/i', ', ', $value);
        } elseif ($list_mode === 'dot') {
            $value = preg_replace('/<\/li\s*>\s*<li\b[^>]*>/i', '. ', $value);
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

    /**
     * Outer scope wrapper for frontend CSS, custom CSS, and accordion config.
     *
     * @param string $inner_html
     * @return string
     */
    /**
     * @param string $inner_html
     * @param string $mode accordion | plain | freeform (controls data-emg-faq-mode for scoped CSS)
     */
    private function wrap_faq_root($inner_html, $mode = 'accordion')
    {
        $inner_html = (string) $inner_html;
        $mode = is_string($mode) ? strtolower(trim($mode)) : 'accordion';
        if (!in_array($mode, array('accordion', 'plain', 'freeform'), true)) {
            $mode = 'accordion';
        }
        $anim = (int) get_option(self::OPT_ANIM_MS, 300);
        if ($anim < 100) {
            $anim = 100;
        }
        if ($anim > 1500) {
            $anim = 1500;
        }
        $smooth = get_option(self::OPT_SMOOTH_PANEL_ANIM, '1') === '1';
        $anim_attr = $smooth ? $anim : 0;
        $attrs = array(
            'class' => self::SCOPE_ROOT_CLASS,
            'data-emg-faq-mode' => $mode,
            'data-emg-faq-multiple' => get_option(self::OPT_MULTIPLE_OPEN, '0') === '1' ? '1' : '0',
            'data-emg-faq-anim' => (string) $anim_attr,
        );
        $parts = array();
        foreach ($attrs as $k => $v) {
            $parts[] = sprintf('%s="%s"', esc_attr($k), esc_attr($v));
        }
        return '<div ' . implode(' ', $parts) . '>' . $inner_html . '</div>';
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param bool   $is_plain
     * @param bool   $open_first_in_list When true, first accordion row starts expanded.
     */
    private function render_faq_item_rows($items, $is_plain, $open_first_in_list = false)
    {
        $html = '';
        $icon_st = $this->sanitize_icon_style((string) get_option(self::OPT_ACCORDION_ICON_STYLE, 'plusminus'));
        $use_line_icon = !$is_plain && $icon_st === 'chevron';
        $icon_left = $this->sanitize_icon_position((string) get_option(self::OPT_ICON_POSITION, 'right')) === 'left';
        $idx = 0;

        foreach ($items as $item) {
            $q_esc = !empty($item['question_is_html']) ? wp_kses_post($item['q']) : esc_html($item['q']);
            $html_ans = !empty($item['answer_is_html']);
            $ans_body = $html_ans ? wp_kses_post($item['a']) : esc_html($item['a']);
            $start_open = $open_first_in_list && $idx === 0 && !$is_plain;
            $acc_class = 'emg-faq-item emg-faq-acc-item' . ($start_open ? ' is-open' : '');
            $aria_exp = $start_open ? 'true' : 'false';
            $panel_attrs = $start_open
                ? ' class="emg-faq-answer emg-faq-panel" aria-hidden="false"'
                : ' class="emg-faq-answer emg-faq-panel" aria-hidden="true"';

            if ($is_plain) {
                $html .= '<div class="emg-faq-item">';
                $html .= '<div class="emg-faq-question-text">' . $q_esc . '</div>';
                $html .= '<div class="emg-faq-answer">';
                $html .= $ans_body;
                $html .= '</div></div>';
            } elseif ($use_line_icon) {
                $btn = 'emg-faq-question emg-faq-question-arrow';
                if ($icon_left) {
                    $btn .= ' emg-faq-question--icon-left';
                }
                $html .= '<div class="' . esc_attr($acc_class) . '">';
                $html .= '<button type="button" class="' . esc_attr($btn) . '" aria-expanded="' . esc_attr($aria_exp) . '">';
                if ($icon_left) {
                    $html .= '<span class="emg-faq-arrow-icon" aria-hidden="true"></span>';
                    $html .= '<span class="emg-faq-q-inline">' . $q_esc . '</span>';
                } else {
                    $html .= '<span class="emg-faq-q-inline">' . $q_esc . '</span>';
                    $html .= '<span class="emg-faq-arrow-icon" aria-hidden="true"></span>';
                }
                $html .= '</button>';
                $html .= '<div' . $panel_attrs . '>';
                $html .= '<div class="emg-faq-answer-inner">' . $ans_body . '</div>';
                $html .= '</div></div>';
            } else {
                $btn = 'emg-faq-question';
                if ($icon_left) {
                    $btn .= ' emg-faq-question--icon-left';
                }
                $html .= '<div class="' . esc_attr($acc_class) . '">';
                $html .= '<button type="button" class="' . esc_attr($btn) . '" aria-expanded="' . esc_attr($aria_exp) . '">';
                $html .= $q_esc;
                $html .= '</button>';
                $html .= '<div' . $panel_attrs . '>';
                $html .= '<div class="emg-faq-answer-inner">' . $ans_body . '</div>';
                $html .= '</div></div>';
            }
            ++$idx;
        }

        return $html;
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

        $two_col_on = get_option(self::OPT_TWO_COLUMN_LAYOUT, '0') === '1';
        $count = count($items);
        $use_two_columns = $two_col_on && $count >= 2;
        $open_first = get_option(self::OPT_OPEN_FIRST, '0') === '1';

        ob_start();
        if ($use_two_columns) {
            $split = intdiv($count, 2);
            if ($split < 1) {
                $split = 1;
            }
            $left_items = array_slice($items, 0, $split);
            $right_items = array_slice($items, $split);
            ?>
            <div class="emg-faq-wrapper emg-faq-layout-two-col">
                <?php if ($title !== ''): ?>
                    <h2 class="emg-faq-title emg-faq-title-span"><?php echo esc_html($title); ?></h2>
                <?php endif; ?>
                <div class="emg-faq-cols">
                    <div class="emg-faq-col-left">
                        <div class="<?php echo esc_attr($wrapper_class); ?>">
                            <?php echo $this->render_faq_item_rows($left_items, $is_plain, $open_first); ?>
                        </div>
                    </div>
                    <div class="emg-faq-col-right">
                        <div class="<?php echo esc_attr($wrapper_class); ?>">
                            <?php echo $this->render_faq_item_rows($right_items, $is_plain, false); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="<?php echo esc_attr($wrapper_class); ?>">
                <?php if ($title !== ''): ?>
                    <h2 class="emg-faq-title"><?php echo esc_html($title); ?></h2>
                <?php endif; ?>
                <?php echo $this->render_faq_item_rows($items, $is_plain, $open_first); ?>
            </div>
            <?php
        }
        if (!$is_plain) {
            $this->request_accordion_script();
        }

        $this->queue_schema($schema_raw);

        return $this->wrap_faq_root($this->apply_wrapper_template(ob_get_clean()), $display_mode);
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
