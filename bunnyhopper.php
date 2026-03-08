<?php
/**
 * Plugin Name: Easter Bunny Hopper
 * Description: An interactive, physics-based bunny that rewards users with WooCommerce discount codes.
 * Version: 2.0.0
 * Author: gokin.pro & kreatywnet.marketing
 * Author URI: https://kreatywnet.marketing
 * Text Domain: easter-bunny-hopper
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Hook for adding admin menus
add_action( 'admin_menu', 'bunnyhopper_add_admin_menu' );
add_action( 'admin_init', 'bunnyhopper_settings_init' );

// Plugin list branding
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bunnyhopper_add_action_links' );
add_filter( 'plugin_row_meta', 'bunnyhopper_add_row_meta', 10, 2 );

/**
 * Add "Settings" and "Support" links to the plugin list
 */
function bunnyhopper_add_action_links( $links ) {
    $settings_link = '<a href="options-general.php?page=bunnyhopper_settings">' . esc_html__( 'Settings', 'easter-bunny-hopper' ) . '</a>';
    $support_link  = '<a href="https://kreatywnet.marketing" target="_blank" style="font-weight:bold; color:#d63638;">' . esc_html__( 'Support (kreatywnet.marketing)', 'easter-bunny-hopper' ) . '</a>';
    array_unshift( $links, $settings_link );
    $links[] = $support_link;
    return $links;
}

/**
 * Add "Visit kreatywnet.marketing" to the plugin row meta
 */
function bunnyhopper_add_row_meta( $plugin_meta, $plugin_file ) {
    if ( plugin_basename( __FILE__ ) === $plugin_file ) {
        $new_meta = array(
            'kreatywnet' => '<a href="https://kreatywnet.marketing" target="_blank" aria-label="' . esc_attr__( 'Visit kreatywnet.marketing', 'easter-bunny-hopper' ) . '">' . esc_html__( 'Visit kreatywnet.marketing', 'easter-bunny-hopper' ) . '</a>'
        );
        $plugin_meta = array_merge( $plugin_meta, $new_meta );
    }
    return $plugin_meta;
}

/**
 * Add options page
 */
function bunnyhopper_add_admin_menu() {
    add_options_page(
        esc_html__( 'Easter Bunny Hopper Settings 🐇', 'easter-bunny-hopper' ),
        esc_html__( 'BunnyHopper 🐇', 'easter-bunny-hopper' ),
        'manage_options',
        'bunnyhopper_settings',
        'bunnyhopper_options_page_html'
    );
}

/**
 * Register settings, sections, and fields
 */
function bunnyhopper_settings_init() {
    register_setting( 
        'bunnyhopper_plugin_page', 
        'bunnyhopper_settings', 
        array(
            'sanitize_callback' => 'bunnyhopper_sanitize_settings'
        ) 
    );

    add_settings_section(
        'bunnyhopper_plugin_page_section',
        esc_html__( 'Bunny Configuration', 'easter-bunny-hopper' ),
        'bunnyhopper_settings_section_callback',
        'bunnyhopper_plugin_page'
    );

    add_settings_field(
        'bunnyhopper_coupons_field',
        esc_html__( 'Select WooCommerce Coupons', 'easter-bunny-hopper' ),
        'bunnyhopper_coupons_field_render',
        'bunnyhopper_plugin_page',
        'bunnyhopper_plugin_page_section'
    );

    add_settings_field(
        'bunnyhopper_greetings_field',
        esc_html__( 'Bunny Greetings (one per line)', 'easter-bunny-hopper' ),
        'bunnyhopper_greetings_field_render',
        'bunnyhopper_plugin_page',
        'bunnyhopper_plugin_page_section'
    );

    add_settings_field(
        'bunnyhopper_show_credit_field',
        esc_html__( 'Show Footer Credit', 'easter-bunny-hopper' ),
        'bunnyhopper_show_credit_field_render',
        'bunnyhopper_plugin_page',
        'bunnyhopper_plugin_page_section'
    );
}

/**
 * Sanitize input data
 */
function bunnyhopper_sanitize_settings( $input ) {
    $sanitized = array();
    
    if ( isset( $input['bunnyhopper_coupons_field'] ) && is_array( $input['bunnyhopper_coupons_field'] ) ) {
        $sanitized['bunnyhopper_coupons_field'] = array_map( 'sanitize_text_field', $input['bunnyhopper_coupons_field'] );
    } else {
        $sanitized['bunnyhopper_coupons_field'] = array();
    }

    if ( isset( $input['bunnyhopper_greetings_field'] ) ) {
        $sanitized['bunnyhopper_greetings_field'] = sanitize_textarea_field( $input['bunnyhopper_greetings_field'] );
    }

    $sanitized['bunnyhopper_show_credit_field'] = isset( $input['bunnyhopper_show_credit_field'] ) ? 1 : 0;

    return $sanitized;
}

/**
 * Render coupons field
 */
function bunnyhopper_coupons_field_render() {
    $options = get_option( 'bunnyhopper_settings' );
    $selected_coupons = isset( $options['bunnyhopper_coupons_field'] ) ? (array) $options['bunnyhopper_coupons_field'] : array();
    
    $coupons = array();
    if ( class_exists( 'WooCommerce' ) ) {
        $args = array(
            'posts_per_page'   => -1,
            'orderby'          => 'title',
            'order'            => 'asc',
            'post_type'        => 'shop_coupon',
            'post_status'      => 'publish',
        );
        $coupons = get_posts( $args );
    }

    if ( empty( $coupons ) ) {
        echo '<p style="color:red;">' . esc_html__( 'No active coupons found or WooCommerce is not active.', 'easter-bunny-hopper' ) . '</p>';
        return;
    }

    echo '<div style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #fff; border-radius: 4px;">';
    foreach ( $coupons as $coupon ) {
        $coupon_title = $coupon->post_title;
        $checked = in_array( $coupon_title, $selected_coupons, true ) ? 'checked="checked"' : '';
        echo "<label style='display:block; margin-bottom: 6px;'>";
        echo '<input type="checkbox" name="bunnyhopper_settings[bunnyhopper_coupons_field][]" value="' . esc_attr( $coupon_title ) . '" ' . $checked . ' /> ';
        echo esc_html( $coupon_title );
        echo "</label>";
    }
    echo '</div>';
    echo '<p class="description">' . esc_html__( 'Select one or more coupons. The bunny will randomly pick one.', 'easter-bunny-hopper' ) . '</p>';
}

/**
 * Render greetings field
 */
function bunnyhopper_greetings_field_render() {
    $options = get_option( 'bunnyhopper_settings' );
    
    $default_greetings = "Happy Easter! 🌿\nJoyful Spring! 🐣\nYou caught me! 🐰\nSpring energy! ✨\n*Petting sounds* 🥰\nClick me 3 times! 🎁\nLooking for gifts? 🎁\nHop hop! 🐇\nSmells like spring! 🌷";
    $greetings = isset( $options['bunnyhopper_greetings_field'] ) ? $options['bunnyhopper_greetings_field'] : $default_greetings;
    
    echo "<textarea name='bunnyhopper_settings[bunnyhopper_greetings_field]' rows='10' cols='50' style='width: 100%; max-width: 500px; padding: 10px;'>" . esc_textarea( $greetings ) . "</textarea>";
    echo '<p class="description">' . esc_html__( 'Enter greetings used by the bunny. Each sentence on a new line.', 'easter-bunny-hopper' ) . '</p>';
}

function bunnyhopper_settings_section_callback() {
    echo esc_html__( 'Manage the appearance and behavior of your interactive Easter Bunny Hopper.', 'easter-bunny-hopper' );
}

/**
 * Render show credit field
 */
function bunnyhopper_show_credit_field_render() {
    $options = get_option( 'bunnyhopper_settings' );
    $val = isset( $options['bunnyhopper_show_credit_field'] ) ? $options['bunnyhopper_show_credit_field'] : 0;
    ?>
    <input type="checkbox" name="bunnyhopper_settings[bunnyhopper_show_credit_field]" value="1" <?php checked( 1, $val ); ?> />
    <span class="description"><?php esc_html_e( 'Show "Created by gokin.pro" credit in the footer of your website. (Opt-in required by WP.org guidelines)', 'easter-bunny-hopper' ); ?></span>
    <?php
}

/**
 * Render admin options page
 */
function bunnyhopper_options_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <!-- Hero Welcome Section by gokin.pro -->
        <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 12px; padding: 40px; margin-bottom: 30px; display: flex; align-items: center; gap: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div style="flex: 0 0 250px;">
                <style>
                    .bh-hero-svg { width: 100%; height: auto; filter: drop-shadow(0px 10px 15px rgba(0,0,0,0.1)); }
                    .trajectory-line { stroke-dasharray: 15 15; animation: move-dash 1.5s linear infinite; }
                    @keyframes move-dash { from { stroke-dashoffset: 30; } to { stroke-dashoffset: 0; } }
                    .animated-bunny { animation: float-bunny 3s cubic-bezier(0.45, 0.05, 0.55, 0.95) infinite; transform-origin: center; }
                    @keyframes float-bunny { 0%, 100% { transform: translateY(0px) rotate(0deg); } 40% { transform: translateY(-15px) rotate(5deg); } 70% { transform: translateY(8px) rotate(-2deg); } }
                    .blinking-eyes { transform-origin: 250px 235px; animation: blink 5s infinite; }
                    @keyframes blink { 0%, 94%, 100% { transform: scaleY(1); } 97% { transform: scaleY(0.1); } }
                    .physics-ear-l { transform-origin: 210px 200px; animation: ear-react-l 3s infinite; }
                    @keyframes ear-react-l { 0%, 100% { transform: rotate(0deg); } 40% { transform: rotate(-10deg); } 70% { transform: rotate(8deg); } }
                    .physics-ear-r { transform-origin: 290px 200px; animation: ear-react-r 3s infinite; }
                    @keyframes ear-react-r { 0%, 100% { transform: rotate(0deg); } 40% { transform: rotate(10deg); } 70% { transform: rotate(-8deg); } }
                    .nose-twitch { transform-origin: 250px 280px; animation: twitch 2.5s infinite; }
                    @keyframes twitch { 0%, 80%, 100% { transform: translateY(0); } 85%, 95% { transform: translateY(-1px); } 90% { transform: translateY(1px); } }
                    .breathing { transform-origin: 250px 250px; animation: breathe 1.5s ease-in-out infinite alternate; }
                    @keyframes breathe { 0% { transform: scale(1); } 100% { transform: scale(1.01, 0.99); } }
                </style>
                <svg viewBox="0 0 500 500" class="bh-hero-svg" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="phys-grad" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#3498db" /><stop offset="100%" stop-color="#2980b9" /></linearGradient>
                        <radialGradient id="bunny-fur" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#ffffff"/><stop offset="75%" stop-color="#f2f2f2"/><stop offset="100%" stop-color="#d6d6d6"/></radialGradient>
                        <radialGradient id="inner-ear" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#ffb3c6"/><stop offset="100%" stop-color="#ff99b3"/></radialGradient>
                        <radialGradient id="blush-grad" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#ffb3c6" stop-opacity="0.8"/><stop offset="100%" stop-color="#ffb3c6" stop-opacity="0"/></radialGradient>
                    </defs>
                    <circle cx="250" cy="250" r="230" fill="url(#phys-grad)" />
                    <path class="trajectory-line" d="M 50 400 Q 150 50 250 250 T 450 350" fill="none" stroke="#ffffff" stroke-width="6" stroke-linecap="round" opacity="0.6"/>
                    <g class="animated-bunny">
                        <g transform="translate(250, 240) rotate(20) translate(-250, -250)">
                            <g class="physics-ear-l">
                                <path d="M 225 200 C 180 70, 110 30, 95 85 C 105 150, 160 200, 190 215 Z" fill="url(#bunny-fur)" />
                                <path d="M 215 190 C 175 85, 125 55, 110 90 C 120 140, 165 185, 185 200 Z" fill="url(#inner-ear)" />
                            </g>
                            <g class="physics-ear-r">
                                <path d="M 275 200 C 320 70, 390 30, 405 85 C 395 150, 340 200, 310 215 Z" fill="url(#bunny-fur)" />
                                <path d="M 285 190 C 325 85, 375 55, 390 90 C 380 140, 335 185, 315 200 Z" fill="url(#inner-ear)" />
                            </g>
                            <g class="breathing">
                                <ellipse cx="250" cy="235" rx="70" ry="75" fill="url(#bunny-fur)" />
                                <ellipse cx="195" cy="260" rx="60" ry="50" fill="url(#bunny-fur)" />
                                <ellipse cx="305" cy="260" rx="60" ry="50" fill="url(#bunny-fur)" />
                                <ellipse cx="250" cy="210" rx="55" ry="60" fill="url(#bunny-fur)" />
                                <ellipse cx="250" cy="290" rx="45" ry="30" fill="#ffffff" />
                                <circle cx="190" cy="270" r="20" fill="url(#blush-grad)" />
                                <circle cx="310" cy="270" r="20" fill="url(#blush-grad)" />
                                <g class="blinking-eyes">
                                    <circle cx="210" cy="235" r="18" fill="#1a0d00"/><circle cx="202" cy="228" r="6" fill="#ffffff"/><circle cx="215" cy="240" r="3" fill="#ffffff"/>
                                    <circle cx="290" cy="235" r="18" fill="#1a0d00"/><circle cx="282" cy="228" r="6" fill="#ffffff"/><circle cx="295" cy="240" r="3" fill="#ffffff"/>
                                </g>
                            </g>
                            <g class="nose-twitch">
                                <path d="M 242 275 Q 250 270 258 275 Q 260 282 250 286 Q 240 282 242 275 Z" fill="#ff99b3" />
                                <path d="M 250 286 Q 250 295 240 298 M 250 286 Q 250 295 260 298" fill="none" stroke="#b3b3b3" stroke-width="2" stroke-linecap="round" />
                                <g stroke="#ffffff" stroke-width="2.5" fill="none" stroke-linecap="round" opacity="0.8">
                                    <path d="M 210 285 Q 160 275 125 285" /><path d="M 210 293 Q 160 295 120 305" />
                                    <path d="M 290 285 Q 340 275 375 285" /><path d="M 290 293 Q 340 295 380 305" />
                                </g>
                            </g>
                        </g>
                    </g>
                    <text x="250" y="420" font-family="Arial, Helvetica, sans-serif" font-size="52" font-weight="900" fill="#ffffff" text-anchor="middle" letter-spacing="1">BunnyHopper</text>
                    <text x="250" y="450" font-family="Arial, Helvetica, sans-serif" font-size="20" font-weight="bold" fill="#f39c12" text-anchor="middle" letter-spacing="4">EASTER BUNNY</text>
                </svg>
            </div>
            <div>
                <h1 style="margin: 0 0 10px 0; color: #1e293b; font-size: 28px; font-weight: 800;"><?php esc_html_e( 'Welcome to Easter Bunny Hopper 🐇', 'easter-bunny-hopper' ); ?></h1>
                <p style="font-size: 16px; line-height: 1.6; color: #64748b; margin-bottom: 20px; max-width: 500px;">
                    <?php esc_html_e( 'Thank you for choosing Easter Bunny Hopper! This interactive plugin was created by gokin.pro & kreatywnet.marketing to help you engage customers and boost sales through play. Configure your options below to start the fun!', 'easter-bunny-hopper' ); ?>
                </p>
                <div style="display: flex; gap: 10px;">
                    <a href="https://kreatywnet.marketing" target="_blank" class="button button-primary" style="background: #10b981; border-color: #059669;"><?php esc_html_e( 'Visit kreatywnet.marketing', 'easter-bunny-hopper' ); ?></a>
                    <a href="https://kreatywnet.marketing" target="_blank" class="button"><?php esc_html_e( 'Get Support', 'easter-bunny-hopper' ); ?></a>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <h2 style="margin: 0;"><?php esc_html_e( 'Configure Bunny Persistence 🐇', 'easter-bunny-hopper' ); ?></h2>
            <span style="background: #10b981; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase;">v2.0.0</span>
        </div>

        <form action="options.php" method="post" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; max-width: 800px;">
            <?php
            settings_fields( 'bunnyhopper_plugin_page' );
            do_settings_sections( 'bunnyhopper_plugin_page' );
            submit_button( esc_html__( 'Save Settings', 'easter-bunny-hopper' ) );
            ?>
        </form>

        <p style="margin-top: 30px; font-size: 13px; color: #64748b;">
            Easter Bunny Hopper Plugin – <?php esc_html_e( 'Created by', 'easter-bunny-hopper' ); ?> 
            <a href="https://kreatywnet.marketing" target="_blank" style="color: #10b981; text-decoration: none; font-weight: 600;">gokin.pro & kreatywnet.marketing</a>
        </p>
    </div>
    <?php
}

// Hook for enqueuing scripts
add_action( 'wp_enqueue_scripts', 'bunnyhopper_enqueue_frontend_scripts' );

/**
 * Enqueue scripts and pass data
 */
function bunnyhopper_enqueue_frontend_scripts() {
    if ( is_admin() ) {
        return;
    }

    $options = get_option( 'bunnyhopper_settings' );
    $coupon_codes = isset( $options['bunnyhopper_coupons_field'] ) ? (array) $options['bunnyhopper_coupons_field'] : array();

    $default_greetings = "Happy Easter! 🌿\nJoyful Spring! 🐣\nYou caught me! 🐰\nSpring energy! ✨\n*Petting sounds* 🥰\nClick me 3 times! 🎁\nLooking for gifts? 🎁\nHop hop! 🐇\nSmells like spring! 🌷";
    $greetings_raw = isset( $options['bunnyhopper_greetings_field'] ) && trim( $options['bunnyhopper_greetings_field'] ) !== '' ? $options['bunnyhopper_greetings_field'] : $default_greetings;
    
    $greetings_array = array_filter( array_map( 'trim', explode( "\n", $greetings_raw ) ) );
    if ( empty( $greetings_array ) ) {
        $greetings_array = explode( "\n", $default_greetings );
    }

    // Register Babel locally
    wp_enqueue_script( 'bunnyhopper-babel', plugins_url( 'assets/js/babel.min.js', __FILE__ ), array(), '7.24.0', true );

    // Enqueue WP's built-in React (wp-element)
    wp_enqueue_script( 'bunnyhopper-app', plugins_url( 'assets/js/bunnyhopper-app.js', __FILE__ ), array( 'wp-element' ), '2.0.0', true );

    // Add Babel transpilation type to the script tag
    add_filter( 'script_loader_tag', 'bunnyhopper_add_babel_type', 10, 3 );

    // Pass data to the script
    wp_localize_script( 'bunnyhopper-app', 'BUNNYHOPPER_PROMO_CODES', array_map( 'esc_js', $coupon_codes ) );
    wp_localize_script( 'bunnyhopper-app', 'BUNNYHOPPER_GREETINGS', array_map( 'esc_js', array_values( $greetings_array ) ) );
}

/**
 * Add type="text/babel" to the app script
 */
function bunnyhopper_add_babel_type( $tag, $handle, $src ) {
    if ( 'bunnyhopper-app' === $handle ) {
        return str_replace( ' src', ' type="text/babel" src', $tag );
    }
    return $tag;
}

// Ensure the root container and credit link are rendered in the footer
add_action( 'wp_footer', function() {
    echo '<div id="bunnyhopper-root"></div>';
    
    $options = get_option( 'bunnyhopper_settings' );
    $show_credit = isset( $options['bunnyhopper_show_credit_field'] ) ? (bool)$options['bunnyhopper_show_credit_field'] : false;

    if ( $show_credit ) {
        echo '<div style="position: fixed; bottom: 10px; right: 20px; font-family: sans-serif; font-size: 11px; color: #94a3b8; z-index: 999998; pointer-events: auto; opacity: 0.8;">';
        echo 'Easter Bunny Hopper – <a href="https://kreatywnet.marketing" target="_blank" rel="noopener noreferrer" style="color: #94a3b8; text-decoration: none; font-weight: 600;">Created by gokin.pro & kreatywnet.marketing</a>';
        echo '</div>';
    }
}, 100 );
