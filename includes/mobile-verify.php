<?php
/**
 * Mandatory Mobile Verification via Payamak Panel (OTP SMS)
 * Based on docs from payamak-panel.com / melipayamak
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_Mobile_Verify {

    const META_MOBILE   = 'ferdowsi_mobile';
    const META_VERIFIED = 'ferdowsi_mobile_verified';
    const META_OTP      = 'ferdowsi_otp_code';
    const META_OTP_TIME = 'ferdowsi_otp_time';

    public static function init() {
        $o = ferdowsi_get_options();
        if ( empty( $o['enable_mobile_verify'] ) ) return;

        // Frontend banner
        add_action( 'wp_footer', array( __CLASS__, 'render_banner' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

        // AJAX
        add_action( 'wp_ajax_ferdowsi_send_otp', array( __CLASS__, 'ajax_send_otp' ) );
        add_action( 'wp_ajax_ferdowsi_verify_otp', array( __CLASS__, 'ajax_verify_otp' ) );

        // Lock mobile field in admin profile
        add_action( 'show_user_profile', array( __CLASS__, 'profile_fields' ) );
        add_action( 'edit_user_profile', array( __CLASS__, 'profile_fields' ) );
        add_action( 'personal_options_update', array( __CLASS__, 'save_profile_fields' ) );
        add_action( 'edit_user_profile_update', array( __CLASS__, 'save_profile_fields' ) );

        // Prevent changing verified mobile via user_meta update
        add_filter( 'update_user_metadata', array( __CLASS__, 'prevent_mobile_change' ), 10, 5 );

        // WooCommerce: make billing phone readonly if verified
        add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'lock_wc_phone' ), 20 );
        add_action( 'woocommerce_checkout_process', array( __CLASS__, 'wc_force_verified_phone' ) );
    }

    public static function is_verified( $user_id = null ) {
        if ( ! $user_id ) $user_id = get_current_user_id();
        if ( ! $user_id ) return false;
        return (bool) get_user_meta( $user_id, self::META_VERIFIED, true );
    }

    public static function get_mobile( $user_id = null ) {
        if ( ! $user_id ) $user_id = get_current_user_id();
        return get_user_meta( $user_id, self::META_MOBILE, true );
    }

    public static function enqueue_scripts() {
        if ( ! is_user_logged_in() || self::is_verified() ) return;
        wp_enqueue_script( 'jquery' );
    }

    public static function render_banner() {
        if ( ! is_user_logged_in() || self::is_verified() || is_admin() ) return;

        $o = ferdowsi_get_options();
        $msg = ! empty( $o['mobile_verify_message'] ) ? $o['mobile_verify_message'] : 'برای ادامه استفاده از سایت، لطفاً شماره موبایل خود را تأیید کنید.';
        $mobile = self::get_mobile();
        ?>
        <div id="ferdowsi-mobile-banner" style="position:fixed;top:0;left:0;right:0;z-index:99999;background:#d63638;color:#fff;padding:14px 20px;text-align:center;font-family:Vazirmatn,Tahoma,sans-serif;box-shadow:0 2px 8px rgba(0,0,0,.2);direction:rtl;">
            <div style="max-width:800px;margin:0 auto;">
                <p style="margin:0 0 10px;font-size:15px;"><?php echo esc_html( $msg ); ?></p>
                <div id="ferdowsi-otp-step1" style="<?php echo $mobile ? 'display:none;' : ''; ?>">
                    <input type="tel" id="ferdowsi-mobile-input" placeholder="0912xxxxxxx" value="<?php echo esc_attr( $mobile ); ?>" style="padding:8px 12px;border:none;border-radius:4px;width:160px;font-family:inherit;direction:ltr;text-align:left;" />
                    <button type="button" id="ferdowsi-send-otp" style="padding:8px 16px;background:#fff;color:#d63638;border:none;border-radius:4px;cursor:pointer;font-family:inherit;font-weight:bold;margin-right:8px;">ارسال کد تأیید</button>
                </div>
                <div id="ferdowsi-otp-step2" style="<?php echo $mobile ? '' : 'display:none;'; ?>">
                    <span>کد ارسال‌شده به <strong id="ferdowsi-shown-mobile"><?php echo esc_html( $mobile ); ?></strong> را وارد کنید:</span>
                    <input type="text" id="ferdowsi-otp-input" placeholder="کد ۶ رقمی" maxlength="6" style="padding:8px 12px;border:none;border-radius:4px;width:110px;font-family:inherit;direction:ltr;text-align:center;margin:0 8px;" />
                    <button type="button" id="ferdowsi-verify-otp" style="padding:8px 16px;background:#fff;color:#d63638;border:none;border-radius:4px;cursor:pointer;font-family:inherit;font-weight:bold;">تأیید</button>
                    <button type="button" id="ferdowsi-resend-otp" style="padding:8px 12px;background:transparent;color:#fff;border:1px solid #fff;border-radius:4px;cursor:pointer;font-family:inherit;margin-right:6px;">ارسال مجدد</button>
                </div>
                <div id="ferdowsi-otp-msg" style="margin-top:8px;font-size:13px;"></div>
            </div>
        </div>
        <style>body.admin-bar #ferdowsi-mobile-banner{top:32px;} @media(max-width:782px){body.admin-bar #ferdowsi-mobile-banner{top:46px;}}</style>
        <script>
        jQuery(function($){
            var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
            var nonce = '<?php echo wp_create_nonce( 'ferdowsi_otp' ); ?>';

            function showMsg(t, ok) {
                $('#ferdowsi-otp-msg').css('color', ok ? '#b8f5b8' : '#ffd6d6').text(t);
            }

            $('#ferdowsi-send-otp, #ferdowsi-resend-otp').on('click', function(){
                var mobile = $('#ferdowsi-mobile-input').val().trim() || $('#ferdowsi-shown-mobile').text().trim();
                if (!/^09[0-9]{9}$/.test(mobile)) {
                    showMsg('شماره موبایل معتبر نیست (باید با ۰۹ شروع شود و ۱۱ رقم باشد).', false);
                    return;
                }
                $(this).prop('disabled', true).text('در حال ارسال...');
                $.post(ajaxurl, { action: 'ferdowsi_send_otp', mobile: mobile, nonce: nonce }, function(res){
                    $('#ferdowsi-send-otp, #ferdowsi-resend-otp').prop('disabled', false).text(function(){ return $(this).attr('id')==='ferdowsi-resend-otp' ? 'ارسال مجدد' : 'ارسال کد تأیید'; });
                    if (res.success) {
                        $('#ferdowsi-otp-step1').hide();
                        $('#ferdowsi-otp-step2').show();
                        $('#ferdowsi-shown-mobile').text(mobile);
                        showMsg(res.data.message || 'کد تأیید ارسال شد.', true);
                    } else {
                        showMsg(res.data || 'خطا در ارسال پیامک.', false);
                    }
                }).fail(function(){ showMsg('خطای ارتباط با سرور.', false); $('#ferdowsi-send-otp, #ferdowsi-resend-otp').prop('disabled', false); });
            });

            $('#ferdowsi-verify-otp').on('click', function(){
                var code = $('#ferdowsi-otp-input').val().trim();
                if (code.length < 4) { showMsg('کد را کامل وارد کنید.', false); return; }
                $(this).prop('disabled', true).text('در حال بررسی...');
                $.post(ajaxurl, { action: 'ferdowsi_verify_otp', code: code, nonce: nonce }, function(res){
                    if (res.success) {
                        showMsg('شماره موبایل با موفقیت تأیید شد.', true);
                        setTimeout(function(){ $('#ferdowsi-mobile-banner').slideUp(300, function(){ $(this).remove(); }); }, 1500);
                    } else {
                        showMsg(res.data || 'کد نادرست است.', false);
                        $('#ferdowsi-verify-otp').prop('disabled', false).text('تأیید');
                    }
                }).fail(function(){ showMsg('خطای ارتباط.', false); $('#ferdowsi-verify-otp').prop('disabled', false).text('تأیید'); });
            });
        });
        </script>
        <?php
    }

    public static function ajax_send_otp() {
        check_ajax_referer( 'ferdowsi_otp', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error( 'ابتدا وارد شوید.' );

        $user_id = get_current_user_id();
        if ( self::is_verified( $user_id ) ) wp_send_json_error( 'قبلاً تأیید شده است.' );

        $mobile = isset( $_POST['mobile'] ) ? sanitize_text_field( $_POST['mobile'] ) : '';
        $mobile = ferdowsi_to_english_digits( $mobile );
        if ( ! preg_match( '/^09[0-9]{9}$/', $mobile ) ) {
            wp_send_json_error( 'شماره موبایل نامعتبر است.' );
        }

        // Rate limit: 60 seconds
        $last = (int) get_user_meta( $user_id, self::META_OTP_TIME, true );
        if ( $last && ( time() - $last ) < 60 ) {
            wp_send_json_error( 'لطفاً ۶۰ ثانیه صبر کنید و دوباره تلاش کنید.' );
        }

        $code = (string) wp_rand( 100000, 999999 );
        update_user_meta( $user_id, self::META_MOBILE, $mobile );
        update_user_meta( $user_id, self::META_OTP, $code );
        update_user_meta( $user_id, self::META_OTP_TIME, time() );

        $result = self::send_otp_sms( $mobile, $code );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        wp_send_json_success( array( 'message' => 'کد تأیید به شماره شما ارسال شد.' ) );
    }

    public static function ajax_verify_otp() {
        check_ajax_referer( 'ferdowsi_otp', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error( 'ابتدا وارد شوید.' );

        $user_id = get_current_user_id();
        $code = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '';
        $code = ferdowsi_to_english_digits( $code );

        $stored = get_user_meta( $user_id, self::META_OTP, true );
        $time   = (int) get_user_meta( $user_id, self::META_OTP_TIME, true );

        if ( ! $stored || ! $time || ( time() - $time ) > 300 ) { // 5 min expiry
            wp_send_json_error( 'کد منقضی شده است. دوباره درخواست دهید.' );
        }
        if ( $code !== $stored ) {
            wp_send_json_error( 'کد وارد شده نادرست است.' );
        }

        update_user_meta( $user_id, self::META_VERIFIED, 1 );
        delete_user_meta( $user_id, self::META_OTP );
        delete_user_meta( $user_id, self::META_OTP_TIME );

        wp_send_json_success();
    }

    /**
     * Send OTP using Payamak Panel REST API (SendOtp)
     * Docs: https://rest.payamak-panel.com/api/SendSMS/SendOtp
     */
    public static function send_otp_sms( $to, $code ) {
        $o = ferdowsi_get_options();
        $username = trim( $o['payamak_username'] ?? '' );
        $password = trim( $o['payamak_password'] ?? '' );
        $from     = trim( $o['payamak_from'] ?? '' );

        if ( empty( $username ) || empty( $password ) || empty( $from ) ) {
            return new WP_Error( 'config', 'تنظیمات پنل پیامک کامل نیست. نام کاربری، رمز و شماره فرستنده را وارد کنید.' );
        }

        $url = 'https://rest.payamak-panel.com/api/SendSMS/SendOtp';
        $body = array(
            'username' => $username,
            'password' => $password,
            'to'       => $to,
            'from'     => $from,
            'code'     => $code,
        );

        $response = wp_remote_post( $url, array(
            'timeout' => 20,
            'body'    => $body,
            'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'http', 'خطا در ارتباط با سرویس پیامک: ' . $response->get_error_message() );
        }

        $code_http = wp_remote_retrieve_response_code( $response );
        $body_res  = wp_remote_retrieve_body( $response );

        // Successful OTP usually returns a recId (number) or positive value
        // Error codes are documented as negative or specific strings
        if ( $code_http >= 200 && $code_http < 300 ) {
            $decoded = json_decode( $body_res, true );
            // Some responses are plain text/number
            if ( is_numeric( $body_res ) && (int)$body_res > 0 ) {
                return true;
            }
            if ( isset( $decoded['Value'] ) && is_numeric( $decoded['Value'] ) && (int)$decoded['Value'] > 0 ) {
                return true;
            }
            if ( isset( $decoded['RetStatus'] ) && (int)$decoded['RetStatus'] === 1 ) {
                return true;
            }
            // Fallback: if body contains a long number assume success
            if ( preg_match( '/\d{5,}/', $body_res ) ) {
                return true;
            }
        }

        return new WP_Error( 'api', 'ارسال پیامک ناموفق بود. پاسخ سرویس: ' . substr( strip_tags( $body_res ), 0, 150 ) );
    }

    // ---------- Profile lock ----------
    public static function profile_fields( $user ) {
        $mobile   = get_user_meta( $user->ID, self::META_MOBILE, true );
        $verified = get_user_meta( $user->ID, self::META_VERIFIED, true );
        ?>
        <h3>تأیید موبایل (فردوسی)</h3>
        <table class="form-table">
            <tr>
                <th><label>شماره موبایل</label></th>
                <td>
                    <input type="text" name="ferdowsi_mobile" value="<?php echo esc_attr( $mobile ); ?>" class="regular-text" <?php echo $verified ? 'readonly' : ''; ?> />
                    <?php if ( $verified ) : ?>
                        <p class="description" style="color:green;">✓ تأیید شده — قابل تغییر نیست.</p>
                    <?php else : ?>
                        <p class="description">هنوز تأیید نشده است.</p>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ( current_user_can( 'edit_users' ) && ! $verified ) : ?>
            <tr>
                <th>تأیید دستی</th>
                <td><label><input type="checkbox" name="ferdowsi_force_verify" value="1"> علامت‌گذاری به‌عنوان تأیید‌شده</label></td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    public static function save_profile_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        $verified = get_user_meta( $user_id, self::META_VERIFIED, true );
        if ( $verified ) {
            // Do not allow change
            return;
        }
        if ( isset( $_POST['ferdowsi_mobile'] ) ) {
            $m = ferdowsi_to_english_digits( sanitize_text_field( $_POST['ferdowsi_mobile'] ) );
            update_user_meta( $user_id, self::META_MOBILE, $m );
        }
        if ( ! empty( $_POST['ferdowsi_force_verify'] ) && current_user_can( 'edit_users' ) ) {
            update_user_meta( $user_id, self::META_VERIFIED, 1 );
        }
    }

    public static function prevent_mobile_change( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
        if ( $meta_key !== self::META_MOBILE ) return $check;
        if ( get_user_meta( $object_id, self::META_VERIFIED, true ) ) {
            return false; // block update
        }
        return $check;
    }

    public static function lock_wc_phone( $fields ) {
        if ( ! is_user_logged_in() || ! self::is_verified() ) return $fields;
        $mobile = self::get_mobile();
        if ( $mobile && isset( $fields['billing']['billing_phone'] ) ) {
            $fields['billing']['billing_phone']['custom_attributes'] = array( 'readonly' => 'readonly' );
            $fields['billing']['billing_phone']['default'] = $mobile;
        }
        return $fields;
    }

    public static function wc_force_verified_phone() {
        if ( ! is_user_logged_in() || ! self::is_verified() ) return;
        $mobile = self::get_mobile();
        if ( $mobile ) {
            $_POST['billing_phone'] = $mobile;
        }
    }
}
