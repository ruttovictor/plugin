<?php
/*
Plugin Name: Quotable
Description: Fetch and display daily quotes from Quotable API.
Version: 1.1
Author: Victor Rutto
*/

// Register settings and add menu page
function qod_register_settings()
{
    add_option('qod_quote_category', 'inspirational');
    add_option('qod_quote_frequency', 'daily');
    add_option('qod_display_style', 'default');

    register_setting('qod_options_group', 'qod_quote_category');
    register_setting('qod_options_group', 'qod_quote_frequency');
    register_setting('qod_options_group', 'qod_display_style');
}
add_action('admin_init', 'qod_register_settings');

function qod_create_menu()
{
    add_menu_page(
        'Quotable Settings',
        'Quotable',
        'manage_options',
        'qod-settings',
        'qod_settings_page'
    );
}
add_action('admin_menu', 'qod_create_menu');

function qod_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Quotable Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('qod_options_group'); ?>
            <?php do_settings_sections('qod-settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Quote Category</th>
                    <td>
                        <select name="qod_quote_category">
                            <option value="inspirational" <?php selected(get_option('qod_quote_category'), 'inspirational'); ?>>Inspirational</option>
                            <option value="funny" <?php selected(get_option('qod_quote_category'), 'funny'); ?>>Funny
                            </option>
                            <option value="life" <?php selected(get_option('qod_quote_category'), 'life'); ?>>Life</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Quote Frequency</th>
                    <td>
                        <select name="qod_quote_frequency">
                            <option value="daily" <?php selected(get_option('qod_quote_frequency'), 'daily'); ?>>Daily
                            </option>
                            <option value="hourly" <?php selected(get_option('qod_quote_frequency'), 'hourly'); ?>>Hourly
                            </option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Display Style</th>
                    <td>
                        <select name="qod_display_style">
                            <option value="default" <?php selected(get_option('qod_display_style'), 'default'); ?>>Default
                            </option>
                            <option value="modern" <?php selected(get_option('qod_display_style'), 'modern'); ?>>Modern
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Enqueue custom CSS for the plugin
function qod_enqueue_styles()
{
    wp_enqueue_style('qod-style', plugins_url('/css/qod-style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'qod_enqueue_styles');

// Fetch quote from API
function qod_fetch_quote()
{
    $category = get_option('qod_quote_category', 'inspirational');
    $response = wp_remote_get("https://api.quotable.io/quotes/random?tags={$category}");

    if (is_wp_error($response)) {
        return 'No quote found.';
    }

    $body = wp_remote_retrieve_body($response);
    $quote = json_decode($body);

    if (isset($quote[0])) {
        $quote = $quote[0];
        return sprintf('<blockquote><p>%s</p><footer>— %s</footer></blockquote>', esc_html($quote->content), esc_html($quote->author));
    }

    return 'No quote found.';
}

// Display shortcode
function qod_display_quote()
{
    return qod_fetch_quote();
}
add_shortcode('quote_of_the_day', 'qod_display_quote');

// Schedule quote updates
function qod_schedule_updates()
{
    if (!wp_next_scheduled('qod_daily_event')) {
        wp_schedule_event(time(), 'daily', 'qod_daily_event');
    }
}
add_action('wp', 'qod_schedule_updates');


function qod_custom_schedule($schedules)
{
    $schedules['hourly'] = array(
        'interval' => 3600,
        'display' => __('Once Hourly')
    );
    return $schedules;
}
add_filter('cron_schedules', 'qod_custom_schedule');
