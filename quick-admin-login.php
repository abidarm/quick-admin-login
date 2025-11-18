<?php
/**
 * Plugin Name: Quick Admin Login
 * Plugin URI: https://github.com/abidarm/quick-admin-login
 * Description: Lists admin users on the login page for quick login without password. For local development and testing only.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Author: Mohamed Abidar
 * Author URI: https://abidar.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quick-admin-login
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Quick_Admin_Login {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Only load in local development
        if (!$this->is_local_environment()) {
            return;
        }
        
        // Hook into login page
        add_action('login_footer', array($this, 'display_admin_users'));
        
        // Handle auto-login
        add_action('init', array($this, 'handle_auto_login'));
        
        // Add CSS for styling
        add_action('login_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Check if we're in a local development environment
     */
    private function is_local_environment() {
        // Check for common local development indicators
        $is_local = (
            (defined('WP_DEBUG') && WP_DEBUG) ||
            in_array($_SERVER['HTTP_HOST'] ?? '', array('localhost', '127.0.0.1', 'local')) ||
            strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false ||
            strpos($_SERVER['HTTP_HOST'] ?? '', '.test') !== false ||
            (defined('WP_ENVIRONMENT_TYPE') && constant('WP_ENVIRONMENT_TYPE') === 'local')
        );
        
        return apply_filters('quick_admin_login_is_local', $is_local);
    }
    
    /**
     * Get admin users (max 3)
     */
    private function get_admin_users() {
        $args = array(
            'role' => 'administrator',
            'number' => 3,
            'orderby' => 'ID',
            'order' => 'ASC'
        );
        
        $users = get_users($args);
        return $users;
    }
    
    /**
     * Display admin users on login page
     */
    public function display_admin_users() {
        $users = $this->get_admin_users();
        
        if (empty($users)) {
            return;
        }
        
        ?>
        <div id="quick-admin-login-container">
            <div class="quick-admin-login-box">
                <div class="quick-admin-login-header">
                    <svg class="quick-admin-login-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <h3>Quick Login</h3>
                    <span class="quick-admin-login-badge">Dev Only</span>
                </div>
                <p class="quick-admin-login-note">Click on a user to login automatically</p>
                <ul class="quick-admin-login-list">
                    <?php foreach ($users as $user): ?>
                        <li>
                            <a href="<?php echo esc_url(add_query_arg('quick_login', $user->ID, wp_login_url())); ?>" 
                               class="quick-admin-login-link"
                               data-user-id="<?php echo esc_attr($user->ID); ?>">
                                <span class="quick-admin-login-avatar">
                                    <?php echo get_avatar($user->ID, 48); ?>
                                </span>
                                <span class="quick-admin-login-info">
                                    <strong><?php echo esc_html($user->display_name); ?></strong>
                                    <small><?php echo esc_html($user->user_email); ?></small>
                                </span>
                                <span class="quick-admin-login-arrow">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                                    </svg>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle auto-login when user clicks on admin user
     */
    public function handle_auto_login() {
        // Only process on login page
        if (!isset($_GET['quick_login']) || !$this->is_local_environment()) {
            return;
        }
        
        // Verify nonce for security
        $user_id = intval($_GET['quick_login']);
        
        if (!$user_id) {
            return;
        }
        
        // Verify user exists and is admin
        $user = get_userdata($user_id);
        if (!$user || !in_array('administrator', $user->roles)) {
            wp_die('Invalid user or insufficient permissions.');
        }
        
        // Clear any existing auth cookies
        wp_clear_auth_cookie();
        
        // Set auth cookie
        wp_set_current_user($user_id, $user->user_login);
        wp_set_auth_cookie($user_id, true);
        
        // Log the login
        do_action('wp_login', $user->user_login, $user);
        
        // Redirect to admin dashboard
        $redirect_to = admin_url();
        wp_safe_redirect($redirect_to);
        exit;
    }
    
    /**
     * Enqueue styles for the login page
     */
    public function enqueue_styles() {
        ?>
        <style type="text/css">
            #quick-admin-login-container {
                margin: 0 auto 10px;
                padding: 0 0 20px 0;
                clear: both;
                width: 100%;
                max-width: 320px;
            }
            
            .login .quick-admin-login-box {
                padding: 26px 24px;
                font-weight: 400;
                overflow: hidden;
                background: #fff;
                border: 1px solid #c3c4c7;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .04);

                margin: 0 auto;
            }
            
            .quick-admin-login-box:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.06);
            }
            
            .quick-admin-login-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 0 0 12px 0;
                padding: 0 0 16px 0;
                border-bottom: 1px solid #e5e5e5;
            }
            
            .quick-admin-login-icon {
                color: #2271b1;
                flex-shrink: 0;
            }
            
            .quick-admin-login-box h3 {
                margin: 0;
                padding: 0;
                font-size: 16px;
                font-weight: 600;
                color: #1d2327;
                flex-grow: 1;
                line-height: 1.4;
            }
            
            .quick-admin-login-badge {
                display: inline-block;
                padding: 4px 8px;
                background: #f0f6fc;
                color: #0969da;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-radius: 4px;
                line-height: 1;
            }
            
            .quick-admin-login-note {
                margin: 0 0 20px 0;
                color: #646970;
                font-size: 13px;
                line-height: 1.5;
                text-align: center;
            }
            
            .quick-admin-login-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            
            .quick-admin-login-list li {
                margin: 0 0 12px 0;
                padding: 0;
            }
            
            .quick-admin-login-list li:last-child {
                margin-bottom: 0;
            }
            
            .quick-admin-login-link {
                display: flex;
                align-items: center;
                padding: 14px 16px;
                background: #f6f8fa;
                border: 1px solid #d1d9e0;
                border-radius: 6px;
                text-decoration: none;
                color: #24292f;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            }
            
            .quick-admin-login-link::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(34, 113, 177, 0.1), transparent);
                transition: left 0.5s ease;
            }
            
            .quick-admin-login-link:hover {
                background: #ffffff;
                border-color: #2271b1;
                color: #0969da;
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(34, 113, 177, 0.15);
            }
            
            .quick-admin-login-link:hover::before {
                left: 100%;
            }
            
            .quick-admin-login-link:active {
                transform: translateY(0);
                box-shadow: 0 1px 4px rgba(34, 113, 177, 0.1);
            }
            
            .quick-admin-login-avatar {
                margin-right: 14px;
                flex-shrink: 0;
                position: relative;
            }
            
            .quick-admin-login-avatar img {
                border-radius: 50%;
                display: block;
                border: 2px solid #e5e5e5;
                transition: border-color 0.2s ease;
            }
            
            .quick-admin-login-link:hover .quick-admin-login-avatar img {
                border-color: #2271b1;
            }
            
            .quick-admin-login-info {
                display: flex;
                flex-direction: column;
                flex-grow: 1;
                min-width: 0;
            }
            
            .quick-admin-login-info strong {
                display: block;
                font-size: 15px;
                font-weight: 600;
                margin-bottom: 4px;
                color: #1d2327;
                transition: color 0.2s ease;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .quick-admin-login-link:hover .quick-admin-login-info strong {
                color: #0969da;
            }
            
            .quick-admin-login-info small {
                display: block;
                font-size: 12px;
                color: #656d76;
                transition: color 0.2s ease;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .quick-admin-login-link:hover .quick-admin-login-info small {
                color: #0969da;
            }
            
            .quick-admin-login-arrow {
                margin-left: 12px;
                flex-shrink: 0;
                color: #656d76;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .quick-admin-login-link:hover .quick-admin-login-arrow {
                color: #0969da;
                transform: translateX(4px);
            }
            
            /* Responsive adjustments */
            @media (max-width: 782px) {
                #quick-admin-login-container {
                    max-width: 100%;
                    padding: 0 20px;
                }
                
                .quick-admin-login-box {
                    padding: 20px;
                }
            }
        </style>
        <?php
    }
}

// Initialize the plugin
new Quick_Admin_Login();

