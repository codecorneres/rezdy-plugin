<?php

namespace CC_RezdyAPI\Admin;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Admin\Screen\Settings;
use CC_RezdyAPI\Admin\Screen\ThemePicker;

class Admin
{
    private $appContext;

    public function __construct(App $appContext)
    {

        $this->appContext = $appContext;

        if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
            // menu
            add_action('admin_menu', [$this, 'pages']);

            // headers
            add_action('admin_menu', [$this, 'init']);

            // update settings
            $_POST && add_action('admin_menu', [$this, 'maybeUpdate']);

            // scripts
            add_action('admin_enqueue_scripts', [$this, 'scripts']);

            // css compat
            add_action('admin_head', function () {
            });

            // notices
            add_action('admin_notices', function () {
                if ((float) get_site_option($this->appContext::DB_VERSION_OPTION) !== $this->appContext::DB_VERSION) {
                    echo '<div class="notice error"><p>', __('Please upgrade your database for <a href="plugins.php?s=cc-rezdy-api">CC Rezdy API</a> plugin by deativating then activating the plugin.', 'cc-rezdy-api'), '</p></div>', PHP_EOL;
                }
            });
        }

        if (is_admin()) {
            // plugins meta link shortcut
            $plugin_base = plugin_basename($this->appContext->getPluginFile());
            add_filter("plugin_action_links_{$plugin_base}", [$this, 'connectionsLinkShortcut']);
        }


        return $this;
    }


    public function pages()
    {

        add_menu_page(
            __('CC Rezdy API', 'cc-rezdy-api'),
            __('CC Rezdy API', 'cc-rezdy-api'),
            'manage_options',
            'cc-rezdy-api-settings',
            [$this->getScreenObject(Settings::class), 'render'],
        );

        // Add submenu page
        // add_submenu_page(
        //     'cc-rezdy-api-settings',
        //     __('Theme Picker', 'cc-rezdy-theme-picker'),
        //     __('Theme Picker', 'cc-rezdy-theme-picker'),
        //     'manage_options',
        //     'cc-rezdy-api-theme-picker',
        //     [$this->getScreenObject(ThemePicker::class), 'render'],
        // );
    }

    private function callPageScreenMethod(string $method)
    {
        switch ($_REQUEST['page'] ?? null) {

            case 'cc-rezdy-api-settings':
                return call_user_func([$this->getScreenObject(Settings::class), $method]);
                // case 'cc-rezdy-api-theme-picker':
                //     return call_user_func([$this->getScreenObject(ThemePicker::class), $method]);
        }
    }

    public function init()
    {
        return $this->callPageScreenMethod('init');
    }

    public function maybeUpdate()
    {
        return $this->callPageScreenMethod('update');
    }

    public function scripts()
    {
        return $this->callPageScreenMethod('scripts');
    }

    public function getScreenObject(string $class)
    {
        return $this->screenContext[$class] ?? ($this->screenContext[$class] = new $class($this->appContext));
    }

    public function connectionsLinkShortcut($links)
    {
        return array_merge([
            'settings' => '<a href="admin.php?page=cc-rezdy-api-settings">' . __('Manage', 'cc-rezdy-api') . '</a>'
        ], $links);
    }
}
