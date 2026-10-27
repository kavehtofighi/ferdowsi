<?php
/**
 * Login / Register with Mobile OTP only
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class Ferdowsi_OTP_Login {
    public static function init() {
        $o = ferdowsi_get_options();
        if ( empty( $o['enable_otp_login'] ) ) return;

        add_shortcode( 'ferdowsi_otp_login', array( __CLASS__, 'shortcode' ) );
        add_action( 'wp_ajax_nopriv_ferdowsi_otp_login_send', array( __CLASS__, 'ajax_send' ) );
        add_action( 'wp_ajax_nopriv_ferdowsi_otp_login_verify', array( __CLASS__, 'ajax_verify' ) );
        add_action( 'wp_ajax_ferdowsi_otp_login_send', array( __CLASS__, 'ajax_send' ) );
        add_action( 'wp_ajax_ferdowsi_otp_login_verify', array( __CLASS__, 'ajax_verify' ) );
        add_action( 'login_form_login', array( __CLASS__, 'maybe_redirect_wp_login' ) );
    }

    public static function maybe_redirect_wp_login() {
        $o = ferdowsi_get_options();
        if ( ! empty( $o['otp_login_replace_wp'] ) && ! is_user_logged_in() ) {
            // optional: could redirect to a page with shortcode
        }
    }

    public static function shortcode() {
        if ( is_user_logged_in() ) {
            return '<p>شما وارد شده‌اید. <a href="' . esc_url( wp_logout_url( home_url() ) ) . '">خروج</a></p>';
        }
        ob_start();
        ?>
        <div id="ferdowsi-otp-login" style="max-width:360px;margin:20px auto;padding:20px;border:1px solid #ddd;border-radius:8px;font-family:Vazirmatn,Tahoma;direction:rtl;text-align:right;">
            <h3 style="margin-top:0;">ورود / ثبت‌نام با موبایل</h3>
            <div id="fol-step1">
                <label>شماره موبایل</label>
                <input type="tel" id="fol-mobile" placeholder="0912xxxxxxx" style="width:100%;padding:10px;margin:8px 0;box-sizing:border-box;" />
                <button type="button" id="fol-send" style="width:100%;padding:10px;background:#2271b1;color:#fff;border:none;border-radius:4px;cursor:pointer;">دریافت کد تأیید</button>
            </div>
            <div id="fol-step2" style="display:none;">
                <label>کد پیامک‌شده</label>
                <input type="text" id="fol-code" maxlength="6" style="width:100%;padding:10px;margin:8px 0;box-sizing:border-box;text-align:center;" />
                <button type="button" id="fol-verify" style="width:100%;padding:10px;background:#00a32a;color:#fff;border:none;border-radius:4px;cursor:pointer;">ورود</button>
            </div>
            <div id="fol-msg" style="margin-top:10px;font-size:13px;"></div>
        </div>
        <script>
        jQuery(function($){
            var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
            function msg(t,ok){ $('#fol-msg').css('color',ok?'green':'#d63638').text(t); }
            $('#fol-send').on('click', function(){
                var m = $('#fol-mobile').val().trim();
                if(!/^09[0-9]{9}$/.test(m)){ msg('شماره موبایل معتبر نیست'); return; }
                $(this).prop('disabled',true).text('در حال ارسال...');
                $.post(ajaxurl,{action:'ferdowsi_otp_login_send',mobile:m},function(r){
                    $('#fol-send').prop('disabled',false).text('دریافت کد تأیید');
                    if(r.success){ $('#fol-step1').hide(); $('#fol-step2').show(); msg(r.data||'کد ارسال شد',true); }
                    else msg(r.data||'خطا');
                });
            });
            $('#fol-verify').on('click', function(){
                var c = $('#fol-code').val().trim(), m = $('#fol-mobile').val().trim();
                $(this).prop('disabled',true);
                $.post(ajaxurl,{action:'ferdowsi_otp_login_verify',mobile:m,code:c},function(r){
                    if(r.success){ msg('ورود موفق...',true); location.href = r.data.redirect || '<?php echo esc_js( home_url() ); ?>'; }
                    else { msg(r.data||'کد نادرست'); $('#fol-verify').prop('disabled',false); }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public static function ajax_send() {
        $mobile = isset($_POST['mobile']) ? ferdowsi_to_english_digits( sanitize_text_field($_POST['mobile']) ) : '';
        if ( ! preg_match('/^09[0-9]{9}$/', $mobile) ) wp_send_json_error('شماره نامعتبر');
        $code = (string) wp_rand(100000,999999);
        set_transient( 'ferdowsi_otp_login_' . $mobile, $code, 5 * MINUTE_IN_SECONDS );
        // reuse mobile verify sender if available
        if ( class_exists('Ferdowsi_Mobile_Verify') ) {
            $r = Ferdowsi_Mobile_Verify::send_otp_sms( $mobile, $code );
            if ( is_wp_error($r) ) wp_send_json_error( $r->get_error_message() );
        } else {
            wp_send_json_error('سرویس پیامک در دسترس نیست');
        }
        wp_send_json_success('کد تأیید ارسال شد');
    }

    public static function ajax_verify() {
        $mobile = isset($_POST['mobile']) ? ferdowsi_to_english_digits( sanitize_text_field($_POST['mobile']) ) : '';
        $code   = isset($_POST['code']) ? ferdowsi_to_english_digits( sanitize_text_field($_POST['code']) ) : '';
        $stored = get_transient( 'ferdowsi_otp_login_' . $mobile );
        if ( ! $stored || $stored !== $code ) wp_send_json_error('کد نادرست یا منقضی شده');

        delete_transient( 'ferdowsi_otp_login_' . $mobile );

        // Find or create user by mobile
        $users = get_users( array(
            'meta_key'   => 'ferdowsi_mobile',
            'meta_value' => $mobile,
            'number'     => 1,
        ) );
        if ( $users ) {
            $user = $users[0];
        } else {
            $username = 'user_' . $mobile;
            $user_id = wp_create_user( $username, wp_generate_password(), $mobile . '@otp.local' );
            if ( is_wp_error($user_id) ) wp_send_json_error( $user_id->get_error_message() );
            update_user_meta( $user_id, 'ferdowsi_mobile', $mobile );
            update_user_meta( $user_id, 'ferdowsi_mobile_verified', 1 );
            $user = get_user_by( 'id', $user_id );
        }
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );
        wp_send_json_success( array( 'redirect' => home_url() ) );
    }
}
