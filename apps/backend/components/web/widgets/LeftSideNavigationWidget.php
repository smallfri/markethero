<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * LeftSideNavigationWidget
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright 2013-2016 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */

class LeftSideNavigationWidget extends CWidget
{
    public function run()
    {
        $sections   = array();
        $hooks      = Yii::app()->hooks;
        $controller = $this->controller;
        $route      = $controller->route;
        $priority   = 0;
        $request    = Yii::app()->request;
        $user       = Yii::app()->user->getModel();

        Yii::import('zii.widgets.CMenu');

        $supportForumUrl = Yii::app()->options->get('system.common.support_forum_url');
        if ($supportForumUrl === null) {
            $supportForumUrl = MW_SUPPORT_FORUM_URL;
        }

        $menuItems = array(
            'support_forum' => array(
                'name'        => Yii::t('app', 'Support forum'),
                'icon'        => 'glyphicon-question-sign',
                'active'      => '',
                'route'       => $supportForumUrl,
                'linkOptions' => array('target' => '_blank'),
            ),
            'dashboard' => array(
                'name'      => Yii::t('app', 'Dashboard'),
                'icon'      => 'glyphicon-dashboard',
                'active'    => 'dashboard',
                'route'     => array('dashboard/index'),
            ),
            'articles' => array(
                'name'      => Yii::t('app', 'Articles'),
                'icon'      => 'glyphicon-book',
                'active'    => 'article',
                'route'     => null,
                'items'     => array(
                    array('url' => array('articles/index'), 'label' => Yii::t('app', 'View all articles'), 'active' => strpos($route, 'articles/index') === 0),
                    array('url' => array('article_categories/index'), 'label' => Yii::t('app', 'View all categories'), 'active' => strpos($route, 'article_categories') === 0),
                ),
            ),
            'users' => array(
                'name'      => Yii::t('app', 'Users'),
                'icon'      => 'glyphicon-user',
                'active'    => array('users', 'user_groups'),
                'route'     => null,
                'items'     => array(
                    array('url' => array('users/index'), 'label' => Yii::t('app', 'Users'), 'active' => strpos($route, 'users') === 0),
                    array('url' => array('user_groups/index'), 'label' => Yii::t('app', 'Groups'), 'active' => strpos($route, 'user_groups') === 0),
                ),
            ),
            'monetization' => array(
                'name'      => Yii::t('app', 'Monetization'),
                'icon'      => 'glyphicon-credit-card',
                'active'    => array('payment_gateway', 'price_plans', 'orders', 'promo_codes', 'currencies', 'taxes'),
                'route'     => null,
                'items'     => array(
                    array('url' => array('payment_gateways/index'), 'label' => Yii::t('app', 'Payment gateways'), 'active' => strpos($route, 'payment_gateway') === 0),
                    array('url' => array('price_plans/index'), 'label' => Yii::t('app', 'Price plans'), 'active' => strpos($route, 'price_plans') === 0),
                    array('url' => array('orders/index'), 'label' => Yii::t('app', 'Orders'), 'active' => strpos($route, 'orders') === 0),
                    array('url' => array('promo_codes/index'), 'label' => Yii::t('app', 'Promo codes'), 'active' => strpos($route, 'promo_codes') === 0),
                    array('url' => array('currencies/index'), 'label' => Yii::t('app', 'Currencies'), 'active' => strpos($route, 'currencies') === 0),
                    array('url' => array('taxes/index'), 'label' => Yii::t('app', 'Taxes'), 'active' => strpos($route, 'taxes') === 0),
                ),
            ),
            'customers' => array(
                'name'      => Yii::t('app', 'Customers'),
                'icon'      => 'glyphicon-user',
                'active'    => array('customer', 'campaign'),
                'route'     => null,
                'items'     => array(
                    array('url' => array('customers/index'), 'label' => Yii::t('app', 'Customers'), 'active' => strpos($route, 'customers') === 0 && strpos($route, 'customers_mass_emails') === false),
                    array('url' => array('customer_groups/index'), 'label' => Yii::t('app', 'Groups'), 'active' => strpos($route, 'customer_groups') === 0),
                    array('url' => array('campaigns/index'), 'label' => Yii::t('app', 'Campaigns'), 'active' => strpos($route, 'campaigns') === 0),
                    array('url' => array('customers_mass_emails/index'), 'label' => Yii::t('app', 'Mass emails'), 'active' => strpos($route, 'customers_mass_emails') === 0),
                    array('url' => array('customer_messages/index'), 'label' => Yii::t('app', 'Messages'), 'active' => strpos($route, 'customer_messages') === 0),
                ),
            ),
            'servers'       => array(
                'name'      => Yii::t('app', 'Servers'),
                'icon'      => 'glyphicon-transfer',
                'active'    => array('delivery_servers', 'bounce_servers', 'feedback_loop_servers'),
                'route'     => null,
                'items'     => array(
                    array('url' => array('delivery_servers/index'), 'label' => Yii::t('app', 'Delivery servers'), 'active' => strpos($route, 'delivery_servers') === 0),
                    array('url' => array('bounce_servers/index'), 'label' => Yii::t('app', 'Bounce servers'), 'active' => strpos($route, 'bounce_servers') === 0),
                    array('url' => array('feedback_loop_servers/index'), 'label' => Yii::t('app', 'Feedback loop servers'), 'active' => strpos($route, 'feedback_loop_servers') === 0),
                ),
            ),
            'domains' => array(
                'name'      => Yii::t('app', 'Domains'),
                'icon'      => 'glyphicon-globe',
                'active'    => array('sending_domains', 'tracking_domains'),
                'route'     => null,
                'items'     => array(
                    array('url' => array('sending_domains/index'), 'label' => Yii::t('app', 'Sending domains'), 'active' => strpos($route, 'sending_domains') === 0),
                    array('url' => array('tracking_domains/index'), 'label' => Yii::t('app', 'Tracking domains'), 'active' => strpos($route, 'tracking_domains') === 0),
                ),
            ),
            'list-page-type' => array(
                'name'      => Yii::t('app', 'List page types'),
                'icon'      => 'glyphicon-list-alt',
                'active'    => 'list_page_type',
                'route'     => array('list_page_type/index'),
            ),
            'email-templates-gallery' => array(
                'name'      => Yii::t('app', 'Email templates gallery'),
                'icon'      => 'glyphicon-text-width',
                'active'    => 'email_templates_gallery',
                'route'     => array('email_templates_gallery/index'),
            ),
            'blacklist' => array(
                'name'      => Yii::t('app', 'Email blacklist'),
                'icon'      => 'glyphicon-ban-circle',
                'active'    => 'email_blacklist',
                'route'     => array('email_blacklist/index'),
            ),
            'extend' => array(
                'name'      => Yii::t('app', 'Extend'),
                'icon'      => 'glyphicon-plus-sign',
                'active'    => array('extensions', 'theme', 'languages', 'ext'),
                'route'     => null,
                'items'     => array(
                    array('url' => array('extensions/index'), 'label' => Yii::t('app', 'Extensions'), 'active' => strpos($route, 'ext') === 0),
                    array('url' => array('theme/index'), 'label' => Yii::t('app', 'Themes'), 'active' => strpos($route, 'theme') === 0),
                    array('url' => array('languages/index'), 'label' => Yii::t('app', 'Languages'), 'active' => strpos($route, 'languages') === 0),
                ),
            ),

            'locations' => array(
                'name'      => Yii::t('app', 'Locations'),
                'icon'      => 'glyphicon-globe',
                'active'    => array('ip_location_services', 'countries', 'zones'),
                'route'     => null,
                'items'     => array(
                    array('url' => array('ip_location_services/index'), 'label' => Yii::t('app', 'Ip location services'), 'active' => strpos($route, 'ip_location_services') === 0),
                    array('url' => array('countries/index'), 'label' => Yii::t('app', 'Countries'), 'active' => strpos($route, 'countries') === 0),
                    array('url' => array('zones/index'), 'label' => Yii::t('app', 'Zones'), 'active' => strpos($route, 'zones') === 0),
                ),
            ),
            'settings' => array(
                'name'      => Yii::t('app', 'Settings'),
                'icon'      => 'glyphicon-cog',
                'active'    => 'settings',
                'route'     => null,
                'items'     => array(
                    array('url' => array('settings/index'), 'label' => Yii::t('app', 'Common'), 'active' => strpos($route, 'settings/index') === 0),
                    array('url' => array('settings/system_urls'), 'label' => Yii::t('app', 'System urls'), 'active' => strpos($route, 'settings/system_urls') === 0),
                    array('url' => array('settings/import_export'), 'label' => Yii::t('app', 'Import/Export'), 'active' => strpos($route, 'settings/import_export') === 0),
                    array('url' => array('settings/email_templates'), 'label' => Yii::t('app', 'Email templates'), 'active' => strpos($route, 'settings/email_templates') === 0),
                    array('url' => array('settings/cron'), 'label' => Yii::t('app', 'Cron'), 'active' => strpos($route, 'settings/cron') === 0),
                    array('url' => array('settings/email_blacklist'), 'label' => Yii::t('app', 'Email blacklist'), 'active' => strpos($route, 'settings/email_blacklist') === 0),
                    array('url' => array('settings/campaign_attachments'), 'label' => Yii::t('app', 'Campaigns'), 'active' => strpos($route, 'settings/campaign_') === 0),
                    array('url' => array('settings/customer_common'), 'label' => Yii::t('app', 'Customers'), 'active' => strpos($route, 'settings/customer_') === 0),
                    array('url' => array('settings/api_ip_access'), 'label' => Yii::t('app', 'Api'), 'active' => strpos($route, 'settings/api_ip_access') === 0),
                    array('url' => array('settings/monetization'), 'label' => Yii::t('app', 'Monetization'), 'active' => strpos($route, 'settings/monetization') === 0),
                    array('url' => array('settings/customization'), 'label' => Yii::t('app', 'Customization'), 'active' => strpos($route, 'settings/customization') === 0),
                    array('url' => array('settings/cdn'), 'label' => Yii::t('app', 'CDN'), 'active' => strpos($route, 'settings/cdn') === 0),
                    array('url' => array('settings/redis_queue'), 'label' => Yii::t('app', 'Queue'), 'active' => strpos($route, 'settings/redis_queue') === 0),
                    array('url' => array('settings/license'), 'label' => Yii::t('app', 'License'), 'active' => strpos($route, 'settings/license') === 0),
                ),
            ),
            'misc' => array(
                'name'      => Yii::t('app', 'Miscellaneous'),
                'icon'      => 'glyphicon-bookmark',
                'active'    => array('misc', 'transactional_emails', 'company_types', 'campaign_abuse_reports'),
                'route'     => null,
                'items'     => array(
                    array('url' => array('misc/campaigns_delivery_logs'), 'label' => Yii::t('app', 'Campaigns delivery logs'), 'active' => strpos($route, 'misc/campaigns_delivery_logs') === 0),
                    array('url' => array('misc/campaigns_bounce_logs'), 'label' => Yii::t('app', 'Campaigns bounce logs'), 'active' => strpos($route, 'misc/campaigns_bounce_logs') === 0),
                    array('url' => array('campaign_abuse_reports/index'), 'label' => Yii::t('app', 'Campaign abuse reports'), 'active' => strpos($route, 'campaign_abuse_reports/index') === 0),
                    array('url' => array('transactional_emails/index'), 'label' => Yii::t('app', 'Transactional emails'), 'active' => strpos($route, 'transactional_emails') === 0),
                    array('url' => array('misc/delivery_servers_usage_logs'), 'label' => Yii::t('app', 'Delivery servers usage logs'), 'active' => strpos($route, 'misc/delivery_servers_usage_logs') === 0),
                    array('url' => array('company_types/index'), 'label' => Yii::t('app', 'Company types'), 'active' => strpos($route, 'company_types') === 0),
                    array('url' => array('misc/application_log'), 'label' => Yii::t('app', 'Application log'), 'active' => strpos($route, 'misc/application_log') === 0),
                    array('url' => array('misc/emergency_actions'), 'label' => Yii::t('app', 'Emergency actions'), 'active' => strpos($route, 'misc/emergency_actions') === 0),
                    array('url' => array('misc/guest_fail_attempts'), 'label' => Yii::t('app', 'Guest fail attempts'), 'active' => strpos($route, 'misc/guest_fail_attempts') === 0),
                    array('url' => array('misc/cron_jobs_list'), 'label' => Yii::t('app', 'Cron jobs list'), 'active' => strpos($route, 'misc/cron_jobs_list') === 0),
                    array('url' => array('misc/phpinfo'), 'label' => Yii::t('app', 'PHP info'), 'active' => strpos($route, 'misc/phpinfo') === 0),
                ),
            ),
        );

        if ($supportForumUrl == '') {
            unset($menuItems['support_forum']);
        }

        $menuItems = (array)Yii::app()->hooks->applyFilters('backend_left_navigation_menu_items', $menuItems);

        // since 1.3.5
        foreach ($menuItems as $key => $data) {
            if (!empty($data['route']) && !$user->hasRouteAccess($data['route'])) {
                unset($menuItems[$key]);
                continue;
            }
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    if (isset($item['url']) && !$user->hasRouteAccess($item['url'])) {
                        unset($menuItems[$key]['items'][$index], $data['items'][$index]);
                    }
                }
            }
            if (empty($data['route']) && empty($data['items'])) {
                unset($menuItems[$key]);
            }
        }

        $menu = new CMenu();
        $menu->htmlOptions          = array('class' => 'sidebar-menu');
        $menu->submenuHtmlOptions   = array('class' => 'treeview-menu');

        foreach ($menuItems as $key => $data) {
            $_route  = !empty($data['route']) ? $data['route'] : 'javascript:;';
            $active  = false;

            if (!empty($data['active']) && is_string($data['active']) && strpos($route, $data['active']) === 0) {
                $active = true;
            } elseif (!empty($data['active']) && is_array($data['active'])) {
                foreach ($data['active'] as $in) {
                    if (strpos($route, $in) === 0) {
                        $active = true;
                        break;
                    }
                }
            }

            $item = array(
                'url'         => $_route,
                'label'       => '<i class="glyphicon '.$data['icon'].'"></i> <span>'.$data['name'].'</span>' . (!empty($data['items']) ? '<i class="fa fa-angle-left pull-right"></i>' : ''),
                'active'      => $active,
                'linkOptions' => !empty($data['linkOptions']) && is_array($data['linkOptions']) ? $data['linkOptions'] : array(),
            );

            if (!empty($data['items'])) {
                foreach ($data['items'] as $index => $i) {
                    if (isset($i['label'])) {
                        $data['items'][$index]['label'] = '<i class="fa fa-angle-double-right"></i>' . $i['label'];
                    }
                }
                $item['items']       = $data['items'];
                $item['itemOptions'] = array('class' => 'treeview');
            }

            $menu->items[] = $item;
        }

        $menu->run();
    }
}
