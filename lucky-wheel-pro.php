<?php
/**
 * Plugin Name: گردونه شانس حرفه‌ای
 * Plugin URI: https://github.com/sahandse/lucky-wheel-pro
 * Description: گردونه شانس وردپرس و ووکامرس با مدیریت جایزه، احتمال برد، موجودی، کمپین و محدودیت خرید.
 * Version: 1.0.0
 * Author: Sahand Rezvan
 * Author URI: https://github.com/sahandse
 * Text Domain: lucky-wheel-pro
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

final class LWP_Plugin {
    const VERSION = '1.0.0';
    const OPTION  = 'lwp_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_shortcode('lucky_wheel_pro', [$this, 'shortcode']);
    }

    public function defaults() {
        return [
            'enabled' => 'yes',
            'campaign_title' => 'گردونه شانس',
            'min_order_amount' => 0,
            'require_mobile' => 'yes',
            'accent' => '#111827',
            'theme' => 'minimal',
            'start_date' => '',
            'end_date' => '',
            'prizes' => [
                ['title' => '۵٪ تخفیف', 'chance' => 40, 'stock' => 100],
                ['title' => '۱۰٪ تخفیف', 'chance' => 25, 'stock' => 50],
                ['title' => 'ارسال رایگان', 'chance' => 20, 'stock' => 30],
                ['title' => 'بدون جایزه', 'chance' => 15, 'stock' => 9999],
            ],
        ];
    }

    public function settings() {
        return wp_parse_args((array)get_option(self::OPTION, []), $this->defaults());
    }

    public function register_settings() {
        register_setting('lwp_group', self::OPTION, [$this, 'sanitize_settings']);
    }

    public function sanitize_settings($in) {
        $d = $this->defaults();
        $prizes = [];

        if (!empty($in['prizes']) && is_array($in['prizes'])) {
            foreach ($in['prizes'] as $prize) {
                $title = sanitize_text_field($prize['title'] ?? '');
                if ($title === '') continue;
                $prizes[] = [
                    'title' => $title,
                    'chance' => min(100, max(0, (float)($prize['chance'] ?? 0))),
                    'stock' => max(0, absint($prize['stock'] ?? 0)),
                ];
            }
        }

        return [
            'enabled' => !empty($in['enabled']) ? 'yes' : 'no',
            'campaign_title' => sanitize_text_field($in['campaign_title'] ?? $d['campaign_title']),
            'min_order_amount' => max(0, (float)($in['min_order_amount'] ?? 0)),
            'require_mobile' => !empty($in['require_mobile']) ? 'yes' : 'no',
            'accent' => sanitize_hex_color($in['accent'] ?? '') ?: $d['accent'],
            'theme' => in_array($in['theme'] ?? '', ['minimal','dark','glass'], true) ? $in['theme'] : $d['theme'],
            'start_date' => sanitize_text_field($in['start_date'] ?? ''),
            'end_date' => sanitize_text_field($in['end_date'] ?? ''),
            'prizes' => $prizes ?: $d['prizes'],
        ];
    }

    public function admin_menu() {
        add_menu_page(
            'گردونه شانس حرفه‌ای',
            'گردونه شانس',
            'manage_options',
            'lucky-wheel-pro',
            [$this, 'settings_page'],
            'dashicons-tickets-alt',
            60
        );
    }

    public function admin_assets($hook) {
        if (false === strpos($hook, 'lucky-wheel-pro')) return;
        wp_enqueue_style('lwp-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', [], self::VERSION);
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        $s = $this->settings();
        ?>
        <div class="wrap lwp-admin">
            <div class="lwp-hero">
                <div>
                    <h1>گردونه شانس حرفه‌ای</h1>
                    <p>مدیریت کمپین، جوایز، احتمال برد و محدودیت‌های شرکت در گردونه.</p>
                </div>
                <span>v<?php echo esc_html(self::VERSION); ?></span>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('lwp_group'); ?>
                <div class="lwp-grid">
                    <section class="lwp-card">
                        <h2>کمپین</h2>
                        <label class="lwp-switch"><span>فعال بودن</span><input type="checkbox" name="<?php echo self::OPTION; ?>[enabled]" value="1" <?php checked($s['enabled'],'yes'); ?>></label>
                        <label>عنوان کمپین
                            <input type="text" name="<?php echo self::OPTION; ?>[campaign_title]" value="<?php echo esc_attr($s['campaign_title']); ?>">
                        </label>
                        <label>شروع کمپین
                            <input type="date" name="<?php echo self::OPTION; ?>[start_date]" value="<?php echo esc_attr($s['start_date']); ?>">
                        </label>
                        <label>پایان کمپین
                            <input type="date" name="<?php echo self::OPTION; ?>[end_date]" value="<?php echo esc_attr($s['end_date']); ?>">
                        </label>
                        <label>حداقل مبلغ خرید
                            <input type="number" min="0" step="1000" name="<?php echo self::OPTION; ?>[min_order_amount]" value="<?php echo esc_attr($s['min_order_amount']); ?>">
                        </label>
                    </section>

                    <section class="lwp-card">
                        <h2>ظاهر و ورودی</h2>
                        <label class="lwp-switch"><span>دریافت شماره موبایل</span><input type="checkbox" name="<?php echo self::OPTION; ?>[require_mobile]" value="1" <?php checked($s['require_mobile'],'yes'); ?>></label>
                        <label>تم
                            <select name="<?php echo self::OPTION; ?>[theme]">
                                <option value="minimal" <?php selected($s['theme'],'minimal'); ?>>مینیمال</option>
                                <option value="dark" <?php selected($s['theme'],'dark'); ?>>تیره</option>
                                <option value="glass" <?php selected($s['theme'],'glass'); ?>>شیشه‌ای</option>
                            </select>
                        </label>
                        <label>رنگ اصلی
                            <input type="color" name="<?php echo self::OPTION; ?>[accent]" value="<?php echo esc_attr($s['accent']); ?>">
                        </label>
                    </section>

                    <section class="lwp-card lwp-wide">
                        <h2>جوایز</h2>
                        <div class="lwp-prizes">
                            <?php foreach ((array)$s['prizes'] as $i => $prize): ?>
                                <div class="lwp-prize-row">
                                    <input type="text" name="<?php echo self::OPTION; ?>[prizes][<?php echo esc_attr($i); ?>][title]" value="<?php echo esc_attr($prize['title']); ?>" placeholder="عنوان جایزه">
                                    <input type="number" min="0" max="100" step="0.01" name="<?php echo self::OPTION; ?>[prizes][<?php echo esc_attr($i); ?>][chance]" value="<?php echo esc_attr($prize['chance']); ?>" placeholder="احتمال">
                                    <input type="number" min="0" name="<?php echo self::OPTION; ?>[prizes][<?php echo esc_attr($i); ?>][stock]" value="<?php echo esc_attr($prize['stock']); ?>" placeholder="موجودی">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small>درصدها باید در مجموع منطقی تنظیم شوند. کنترل نهایی توزیع جایزه در نسخه کامل‌تر توسعه داده می‌شود.</small>
                    </section>

                    <section class="lwp-card">
                        <h2>شورت‌کد</h2>
                        <code>[lucky_wheel_pro]</code>
                    </section>

                    <section class="lwp-card">
                        <h2>وضعیت توسعه</h2>
                        <p>ساختار افزونه، تنظیمات کمپین و جوایز آماده است. ثبت اسپین، CSV، پیامک و Elementor در نسخه‌های بعدی همین Repo تکمیل می‌شود.</p>
                    </section>
                </div>
                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
        </div>
        <?php
    }

    private function campaign_active() {
        $s = $this->settings();
        if ('yes' !== $s['enabled']) return false;

        $today = wp_date('Y-m-d');
        if ($s['start_date'] && $today < $s['start_date']) return false;
        if ($s['end_date'] && $today > $s['end_date']) return false;

        return true;
    }

    public function shortcode() {
        if (!$this->campaign_active()) return '';

        $s = $this->settings();
        $classes = 'lwp-wheel lwp-theme-' . esc_attr($s['theme']);

        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" style="--lwp-accent:<?php echo esc_attr($s['accent']); ?>">
            <div class="lwp-wheel-card">
                <h3><?php echo esc_html($s['campaign_title']); ?></h3>

                <div class="lwp-wheel-circle">
                    <span>🎁</span>
                </div>

                <?php if ('yes' === $s['require_mobile']) : ?>
                    <input type="tel" inputmode="numeric" placeholder="شماره موبایل">
                <?php endif; ?>

                <button type="button">چرخاندن گردونه</button>
            </div>
        </div>

        <style>
            .lwp-wheel{direction:rtl;text-align:center}
            .lwp-wheel-card{max-width:420px;margin:auto;padding:22px;border:1px solid #e5e7eb;border-radius:20px;background:#fff}
            .lwp-wheel-circle{width:180px;height:180px;margin:20px auto;border-radius:50%;display:grid;place-items:center;border:12px solid var(--lwp-accent);font-size:44px;box-shadow:0 12px 40px rgba(0,0,0,.08)}
            .lwp-wheel input{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid #d1d5db;border-radius:12px;margin:8px 0}
            .lwp-wheel button{width:100%;padding:12px;border:0;border-radius:12px;background:var(--lwp-accent);color:#fff;cursor:pointer}
            .lwp-theme-dark .lwp-wheel-card{background:#111827;color:#fff;border-color:#1f2937}
            .lwp-theme-glass .lwp-wheel-card{background:rgba(255,255,255,.72);backdrop-filter:blur(14px)}
        </style>
        <?php
        return ob_get_clean();
    }
}

new LWP_Plugin();
