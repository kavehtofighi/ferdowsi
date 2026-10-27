<?php
/**
 * Admin Settings Page - Full
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_filter( 'plugin_action_links_' . FERDOWSI_PLUGIN_BASENAME, array( __CLASS__, 'action_links' ) );
    }

    public static function add_menu() {
        add_options_page(
            'تنظیمات فردوسی',
            'فردوسی',
            'manage_options',
            'ferdowsi-settings',
            array( __CLASS__, 'render_settings_page' )
        );
    }

    public static function register_settings() {
        register_setting( 'ferdowsi_settings_group', 'ferdowsi_options', array( __CLASS__, 'sanitize_options' ) );
    }

    public static function sanitize_options( $input ) {
        $out = array();
        $checks = array(
            'enable_jalali_dates', 'enable_persian_font', 'enable_persian_digits',
            'enable_char_conversion', 'enable_currency', 'week_starts_saturday',
            'frontend_font', 'show_gregorian_beside', 'enable_woocommerce',
            'enable_shamsi_permalinks', 'enable_acf', 'enable_gutenberg_font',
            'enable_calendar_widget', 'enable_dashboard_widget', 'enable_email_dates',
            'enable_admin_numbers', 'enable_search_improve', 'enable_dark_mode',
            'enable_export_import', 'enable_mobile_verify', 'enable_zarinpal', 'enable_bitpay', 'enable_order_sms', 'enable_otp_login', 'otp_login_replace_wp', 'enable_invoice', 'enable_unique_mobile', 'enable_iran_shipping', 'enable_dual_currency', 'enable_order_export', 'enable_jalali_reports'
        );
        foreach ( $checks as $key ) {
            $out[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
        }
        $out['currency_unit'] = ( isset( $input['currency_unit'] ) && 'rial' === $input['currency_unit'] ) ? 'rial' : 'toman';
        $out['date_format']   = sanitize_text_field( $input['date_format'] ?? 'j F Y' );
        $out['payamak_username'] = sanitize_text_field( $input['payamak_username'] ?? '' );
        $out['payamak_password'] = sanitize_text_field( $input['payamak_password'] ?? '' );
        $out['payamak_from']     = sanitize_text_field( $input['payamak_from'] ?? '' );
        $out['mobile_verify_message'] = sanitize_textarea_field( $input['mobile_verify_message'] ?? '' );
        $out['sms_tpl_processing'] = sanitize_textarea_field( $input['sms_tpl_processing'] ?? '' );
        $out['sms_tpl_completed'] = sanitize_textarea_field( $input['sms_tpl_completed'] ?? '' );
        $out['sms_tpl_cancelled'] = sanitize_textarea_field( $input['sms_tpl_cancelled'] ?? '' );
        return $out;
    }

    public static function action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'options-general.php?page=ferdowsi-settings' ) . '">تنظیمات</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $o = ferdowsi_get_options();
        settings_errors( 'ferdowsi' );
        ?>
        <div class="wrap" style="font-family: Vazirmatn, Tahoma, sans-serif; max-width: 900px;">
            <h1>تنظیمات افزونه فردوسی <small style="font-size:14px;color:#666;">نسخه <?php echo esc_html( FERDOWSI_VERSION ); ?></small></h1>
            <p>بومی‌سازی کامل وردپرس برای فارسی‌زبانان. هر بخش را می‌توانید جداگانه کنترل کنید.</p>

            <form method="post" action="options.php">
                <?php settings_fields( 'ferdowsi_settings_group' ); ?>

                <h2 class="title">هسته اصلی</h2>
                <table class="form-table">
                    <?php self::row_checkbox( $o, 'enable_jalali_dates', 'تاریخ شمسی', 'نمایش تاریخ شمسی در تمام بخش‌های وردپرس' ); ?>
                    <?php self::row_checkbox( $o, 'enable_persian_font', 'فونت فارسی ادمین', 'فونت وزیرمتن محلی در پیشخوان و ورود' ); ?>
                    <?php self::row_checkbox( $o, 'frontend_font', 'فونت در فرانت‌اند', 'اعمال فونت وزیرمتن در ظاهر سایت' ); ?>
                    <?php self::row_checkbox( $o, 'enable_persian_digits', 'ارقام فارسی', 'تبدیل اعداد به ۰۱۲۳۴۵۶۷۸۹' ); ?>
                    <?php self::row_checkbox( $o, 'enable_char_conversion', 'تبدیل حروف عربی', 'تبدیل ي و ك به ی و ک' ); ?>
                    <?php self::row_checkbox( $o, 'week_starts_saturday', 'شروع هفته از شنبه', '' ); ?>
                    <?php self::row_checkbox( $o, 'show_gregorian_beside', 'نمایش میلادی کنار شمسی', 'تاریخ میلادی در پرانتز' ); ?>
                </table>

                <h2 class="title">ووکامرس و فروشگاه</h2>
                <table class="form-table">
                    <?php self::row_checkbox( $o, 'enable_woocommerce', 'یکپارچگی ووکامرس', 'تاریخ شمسی سفارش‌ها، ایمیل‌ها، اعتبارسنجی موبایل و کد ملی' ); ?>
                    <?php self::row_checkbox( $o, 'enable_currency', 'واحد پول', 'پشتیبانی تومان / ریال' ); ?>
                    <tr>
                        <th>واحد پیش‌فرض</th>
                        <td>
                            <label><input type="radio" name="ferdowsi_options[currency_unit]" value="toman" <?php checked( $o['currency_unit'], 'toman' ); ?>> تومان</label>
                            &nbsp;&nbsp;
                            <label><input type="radio" name="ferdowsi_options[currency_unit]" value="rial" <?php checked( $o['currency_unit'], 'rial' ); ?>> ریال</label>
                        </td>
                    </tr>
                </table>

                <h2 class="title">ویژگی‌های پیشرفته</h2>
                <table class="form-table">
                    <?php self::row_checkbox( $o, 'enable_shamsi_permalinks', 'پیوند یکتای شمسی', 'ساختار /سال/ماه/روز/ (پس از فعال‌سازی پیوندهای یکتا را ذخیره مجدد کنید)' ); ?>
                    <?php self::row_checkbox( $o, 'enable_acf', 'پشتیبانی ACF', 'نمایش تاریخ شمسی در فیلدهای تاریخ Advanced Custom Fields' ); ?>
                    <?php self::row_checkbox( $o, 'enable_gutenberg_font', 'فونت در گوتنبرگ', 'اعمال فونت وزیرمتن در ویرایشگر بلوک' ); ?>
                    <?php self::row_checkbox( $o, 'enable_email_dates', 'تاریخ شمسی در ایمیل‌ها', 'تبدیل تاریخ‌ها در ایمیل‌های وردپرس' ); ?>
                    <?php self::row_checkbox( $o, 'enable_dashboard_widget', 'ویجت داشبورد', 'تقویم شمسی در پیشخوان' ); ?>
                    <?php self::row_checkbox( $o, 'enable_admin_numbers', 'ارقام فارسی در ادمین', 'تبدیل اعداد قابل مشاهده در جداول ادمین' ); ?>
                    <?php self::row_checkbox( $o, 'enable_search_improve', 'بهبود جستجو', 'نرمال‌سازی حروف عربی/فارسی در جستجو' ); ?>
                    <?php self::row_checkbox( $o, 'enable_dark_mode', 'پشتیبانی حالت تاریک', 'استایل‌های سازگار با دارک مود' ); ?>
                </table>

                
                <h2 class="title">تأیید اجباری موبایل (پیامک OTP)</h2>
                <p class="description">با استفاده از وب‌سرویس <a href="https://docs.payamak-panel.com" target="_blank">payamak-panel.com</a> (ملی پیامک / فراپیامک و ...). پس از تأیید، بنر مخفی می‌شود و شماره موبایل قفل می‌گردد.</p>
                <table class="form-table">
                    <?php self::row_checkbox( $o, 'enable_mobile_verify', 'فعال‌سازی تأیید موبایل', 'نمایش بنر اجباری برای کاربران لاگین‌نشده تأیید‌نشده' ); ?>
                    <tr>
                        <th>نام کاربری پنل</th>
                        <td><input type="text" name="ferdowsi_options[payamak_username]" value="<?php echo esc_attr( $o['payamak_username'] ?? '' ); ?>" class="regular-text" dir="ltr" /></td>
                    </tr>
                    <tr>
                        <th>رمز عبور / API Key</th>
                        <td><input type="password" name="ferdowsi_options[payamak_password]" value="<?php echo esc_attr( $o['payamak_password'] ?? '' ); ?>" class="regular-text" dir="ltr" autocomplete="new-password" /></td>
                    </tr>
                    <tr>
                        <th>شماره فرستنده (from)</th>
                        <td><input type="text" name="ferdowsi_options[payamak_from]" value="<?php echo esc_attr( $o['payamak_from'] ?? '' ); ?>" class="regular-text" dir="ltr" placeholder="5000xxxx" /></td>
                    </tr>
                    <tr>
                        <th>متن پیام بنر</th>
                        <td><textarea name="ferdowsi_options[mobile_verify_message]" rows="2" class="large-text"><?php echo esc_textarea( $o['mobile_verify_message'] ?? '' ); ?></textarea></td>
                    </tr>
                </table>

                
                <h2 class="title">درگاه‌های پرداخت</h2>
                <p class="description">پس از فعال‌سازی، از ووکامرس → پیکربندی → پرداخت‌ها، تنظیمات هر درگاه (مرچنت‌کد / API Key) را تکمیل کنید.</p>
                <table class="form-table">
                    <?php self::row_checkbox( $o, 'enable_zarinpal', 'زرین‌پال', 'فعال‌سازی درگاه زرین‌پال برای ووکامرس' ); ?>
                    <?php self::row_checkbox( $o, 'enable_bitpay', 'بیت‌پی', 'فعال‌سازی درگاه بیت‌پی (bitpay.ir) برای ووکامرس' ); ?>
                </table>

                
                <h2 class="title">پیامک سفارش و ورود OTP</h2>
                <table class="form-table">
                    <?php self::row_checkbox( $o, 'enable_order_sms', 'پیامک وضعیت سفارش', 'ارسال پیامک هنگام تغییر وضعیت سفارش (نیاز به تنظیمات پنل پیامک)' ); ?>
                    <tr><th>متن پیام «در حال انجام»</th><td><textarea name="ferdowsi_options[sms_tpl_processing]" rows="2" class="large-text"><?php echo esc_textarea( $o['sms_tpl_processing'] ?? '' ); ?></textarea><p class="description">متغیرها: {order_id} {total} {name} {status}</p></td></tr>
                    <tr><th>متن پیام «تکمیل‌شده»</th><td><textarea name="ferdowsi_options[sms_tpl_completed]" rows="2" class="large-text"><?php echo esc_textarea( $o['sms_tpl_completed'] ?? '' ); ?></textarea></td></tr>
                    <?php self::row_checkbox( $o, 'enable_otp_login', 'ورود/ثبت‌نام با OTP موبایل', 'شورت‌کد: [ferdowsi_otp_login]' ); ?>
                </table>

                <h2 class="title">فاکتور، ارسال، گزارش و سایر</h2>
                <table class="form-table">
                    <?php self::row_checkbox( $o, 'enable_invoice', 'فاکتور فارسی', 'دکمه فاکتور قابل چاپ در حساب کاربری و ادمین' ); ?>
                    <?php self::row_checkbox( $o, 'enable_unique_mobile', 'یکتایی موبایل', 'جلوگیری از ثبت شماره موبایل تکراری' ); ?>
                    <?php self::row_checkbox( $o, 'enable_iran_shipping', 'روش ارسال ایرانی', 'نرخ ثابت پست/تیپاکس قابل تنظیم در مناطق ارسال' ); ?>
                    <?php self::row_checkbox( $o, 'enable_dual_currency', 'نمایش دو واحدی', 'نمایش همزمان تومان و ریال کنار قیمت' ); ?>
                    <?php self::row_checkbox( $o, 'enable_order_export', 'خروجی اکسل شمسی', 'دکمه خروجی CSV سفارش‌ها با تاریخ شمسی' ); ?>
                    <?php self::row_checkbox( $o, 'enable_jalali_reports', 'گزارش فروش شمسی', 'ویجت داشبورد آخرین سفارش‌ها با تاریخ شمسی' ); ?>
                </table>

                <?php submit_button( 'ذخیره تنظیمات' ); ?>
            </form>

            <hr>
            <h2>ابزارها</h2>
            <p>
                <a href="<?php echo wp_nonce_url( admin_url( 'options-general.php?page=ferdowsi-settings&ferdowsi_export=1' ), 'ferdowsi_export' ); ?>" class="button">خروجی تنظیمات (JSON)</a>
            </p>
            <form method="post" enctype="multipart/form-data" style="margin-top:15px;">
                <?php wp_nonce_field( 'ferdowsi_import' ); ?>
                <input type="file" name="ferdowsi_import_file" accept=".json">
                <button type="submit" name="ferdowsi_import" class="button">وارد کردن تنظیمات</button>
            </form>

            <hr>
            <h2>راهنمای توابع و شورت‌کدها</h2>
            <ul style="list-style:disc;padding-right:20px;line-height:1.9;">
                <li><code>ferdowsi_jdate( $timestamp, 'j F Y' )</code> — تاریخ شمسی</li>
                <li><code>ferdowsi_format_price( 150000 )</code> — فرمت قیمت</li>
                <li><code>[ferdowsi_date format="j F Y"]</code></li>
                <li><code>[ferdowsi_calendar]</code></li>
                <li><code>[ferdowsi_price amount="250000"]</code></li>
                <li><code>[ferdowsi_convert from="gregorian" year="2026" month="10" day="27"]</code></li>
            </ul>

            <p style="color:#666;margin-top:30px;">افزونه فردوسی — ساخته‌شده برای جامعه فارسی‌زبان وردپرس</p>
        </div>
        <?php
    }

    private static function row_checkbox( $o, $key, $label, $desc ) {
        ?>
        <tr>
            <th scope="row"><?php echo esc_html( $label ); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="ferdowsi_options[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $o[ $key ] ), true ); ?> />
                    <?php echo esc_html( $desc ); ?>
                </label>
            </td>
        </tr>
        <?php
    }
}
