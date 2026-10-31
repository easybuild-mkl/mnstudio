<?php
/*
 * Plugin Name: MNStudio Pro
 * Description: MNStudio Pro adds custom functionalities and provides enhanced new features to the site
 * Author: BlackSwan
 * Author URI: https://blackswandev.com/
 * Plugin URI: https://amirhp.com/landing
 * Version: 6.4.2
 * Tested up to: 6.0
 * Requires PHP: 7.0
 * Text Domain: mnstudio
 * Domain Path: /languages
 * Copyright: (c) BlackSwanDev.Com, All rights reserved.
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/12/29 14:31:57
*/
namespace PigmentDev;
defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>BlackSwan MNStudio Pro :: Developed by Amirhp-com (<a href='https://amirhp.com/'>https://amirhp.com/</a>)</small>");
if (!class_exists("MNStudio_Pro")) {
  class MNStudio_Pro {
    protected $td = "mnstudio";
    protected $db_slug = "mnstudio";
    protected $title = "MNStudio Pro";
    protected $setting_key = "mnstudio__general_settings";
    protected $version = "6.4.2";
    protected $db_version = "5.0.0";
    protected $diet_id;
    protected $pregnant_id;
    protected $assets_url;
    protected $diet_label;
    protected $pregnant_label;
    protected $secure_link_secret;
    protected $discount_percentage;
    protected $discount_category;
    protected $secure_video_base;
    protected $remove_wp_lazy_load = true;
    protected $add_font_to_wc_email = true;
    protected $use_currency_switch = true;
    protected $notify_after_purchase = true;
    protected $dollar_currency = true;
    protected $disable_gutenberg = false;
    protected $different_course_checkout = true;
    protected $media_size_columns = true;
    protected $custom_thank_you_message = true;
    protected $remove_recurring_totals = true;
    protected $learn_dash_continue = true;
    protected $sms_form_for_outofstock = true;
    protected $second_sms_text = "pcode:urxhk1t1sg2x5ct" . PHP_EOL . "b_first_name:{b_first_name}" . PHP_EOL . "order:{order_id}";
    protected $db_table;
    protected $db_table_sl;
    protected $debug;
    protected $gettext_replace;
    protected $str_replace;
    protected $paypal_gateway_slug; /* ppcp | ppcp-gateway */
    protected $course_shipping_slug = "course";
    protected $fix_wc_backend_wrecked = true;
    protected $telegram_fab = false;
    protected $telegram_url;
    protected $telegram_title;
    public $text_online_term_level = "ترم آنلاین";
    public $text_beginner = "مبتدی";
    public function __construct() {
      global $wpdb;
      $this->assets_url          = plugins_url("/assets/", __FILE__);
      $this->db_table            = $wpdb->prefix . "mnstudio_cron";
      $this->db_table_sl         = $wpdb->prefix . "mnstudio_stock_log";
      $this->gettext_replace     = $this->read("gettext_replace", "");
      $this->str_replace         = $this->read("str_replace", "");
      $this->diet_id             = $this->read("diet_id", "91271920278");
      $this->pregnant_id         = $this->read("pregnant_id", "91271927335");
      $this->diet_label          = $this->read("diet_label", "دریافت رژیم غذایی یکماهه");
      $this->pregnant_label      = $this->read("pregnant_label", "ویزیت آنلاین با ماما 15 دقیقه");
      $this->secure_link_secret  = $this->read("secure_link_secret", "310daa96727bbc07952f4193ef430484d2");
      $this->secure_video_base   = $this->read("secure_video_base", "https://dl.minanaseh.com/secure/");
      $this->telegram_url        = $this->read("telegram_url", "https://t.me/MN_Studiio");
      $this->telegram_title      = $this->read("telegram_title", "Telegram Support");
      $this->paypal_gateway_slug = $this->read("paypal_gateway_slug", "bacs");
      $this->discount_percentage = $this->read("discount", "");
      $this->discount_category   = array_map("trim", (array) explode(",", $this->read("discount_cat", "")));
      $this->telegram_fab        = !empty($this->telegram_url);

      load_plugin_textdomain("mnstudio", false, dirname(plugin_basename(__FILE__)) . "/languages/");
      $this->debug = "yes" == $this->read("debug", "no");

      add_filter("woocommerce_locate_template", [ $this, "woocommerce_locate_template" ], 99999, 3 );
      add_filter("woocommerce_locate_template", [ $this, "woocommerce_locate_template" ], -10030, 3 );
	    add_filter("woocommerce_add_to_cart_fragments", function($arg){
        ob_start();
        $template = plugin_dir_path(__FILE__).'include/mini-cart.php';
        if (file_exists($template)) {
          echo "<!-- mnstudio_pro v5 :: plugin:mini-cart -->";
          include $template;
        }else{
          echo "<!-- mnstudio_pro v5 :: woocommerce:mini-cart.php -->";
          wc_get_template('cart/mini-cart.php');
        }
        // woocommerce_mini_cart();
        $mini_cart = ob_get_clean();
        $arg['div.widget_shopping_cart_content'] = '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>';
        return $arg;
      }, 300000 );

      // Handle the 'switch_currency' query argument to toggle between different currencies (e.g., USD and IRR).
      // This functionality allows users to switch their preferred currency for transactions.
      if (isset($_GET["switch_currency"]) || isset($_GET["cs"])) {
        $this->template_redirect_dollar();
      }
      // add switch_currency to wp query args in order to read it in the template_redirect
      add_filter("query_vars", function($vars) {
        $vars[] = "cs";
        $vars[] = "switch_currency";
        return $vars;
      });

      // $this->include("class-sticky-add2cart");

      add_action("woocommerce_before_shop_loop_item", function(){
        remove_action("woodmart_add_loop_btn", "woocommerce_template_loop_add_to_cart", 10);
        remove_all_actions("woodmart_add_loop_btn");
      });

      // change shipping method price if all items in cart are from mat category
      add_action("woocommerce_package_rates", array($this, "custom_mat_shipping_rate"), 20, 2);
      add_filter("woocommerce_update_order_review_fragments", function($fragments){
        $has_mat = $this->has_mat_in_cart();
        if ($has_mat) {
          $script = <<<HTML
            <style>
              .woocommerce #shipping_method_0_flat_rate6+label .woocommerce-Price-amount.amount bdi:before,
              .woocommerce #shipping_method_0_flat_rate1+label .woocommerce-Price-amount.amount bdi:before,
              .woocommerce-checkout #shipping_method_0_flat_rate6+label .woocommerce-Price-amount.amount bdi:before,
              .woocommerce-checkout #shipping_method_0_flat_rate1+label .woocommerce-Price-amount.amount bdi:before
            </style>
          HTML;
          if (isset($fragments[".woocommerce-checkout-review-order-table"])) { $fragments[".woocommerce-checkout-review-order-table"] .= $script; }
        }
        return $fragments;
      });
      remove_action( 'woocommerce_thankyou', "WC_Subscriptions_Order::subscription_thank_you");
      remove_action( 'woocommerce_thankyou', ["WC_Subscriptions_Order", "subscription_thank_you"]);
	    // add_filter('woocommerce_subscriptions_thank_you_message', $thank_you_message, $order_id ),

      add_action('wp_before_admin_bar_render', function () {
        global $wp_admin_bar;
        if (!is_admin_bar_showing() || !is_object($wp_admin_bar)) { return; }
        $all_nodes = $wp_admin_bar->get_nodes();
        $new_nodes = [];
        foreach ($all_nodes as $id => $node) {
          if (!in_array($id, [
              "elementor_notes",
              "wp-mail-smtp-menu",
              "wp-logo",
              "customize",
              "duplicate-post",
            ])) { $new_nodes[$id] = $node; }
        }
        foreach (array_keys($all_nodes) as $id) {
          $wp_admin_bar->remove_node($id);
        }
        foreach ($new_nodes as $id => $node) {
          if ($id == "rank-math") {
            $wp_admin_bar->add_node([
              'id'       => $id,
              'title'    => str_replace("Rank Math SEO", "SEO", $node->title),
              'href'     => $node->href,
              'meta'     => $node->meta,
            ]);
          }elseif ("xts_dashboard" == $id) {
            $wp_admin_bar->add_node([
              'id'       => $id,
              'title'    => "WD",
              'href'     => $node->href,
              'meta'     => $node->meta,
            ]);
          }
          else{
            $wp_admin_bar->add_node($node);
          }
        }
      }, 99999);

      add_action("wp_head", array($this, "add_fav_icon"), -1);
      add_action("admin_head", array($this, "add_fav_icon"), -1);

      add_action("init", function(){
        $this->title = __("MNStudio Pro", $this->td);

        // if product shipping class is $this->course_shipping_slug and $this->using_dollar() then set product not available for purchase and disable add to cart button
        add_filter('woocommerce_is_purchasable', function($is_purchasable, $product) {
          $shipping_class_id = $product->get_shipping_class();
          if ($this->different_course_checkout && $this->using_dollar() && $shipping_class_id && (string) $shipping_class_id != (string) $this->course_shipping_slug) {
            return false;
          }
          return $is_purchasable;
        }, 99999, 2);

      }, 1);

      // bulk discount 30% for offline courses - yalda 1404
      add_action('__woocommerce_cart_calculate_fees__', function ($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;
        $discount_amount = 0.3; // 30% discount
        $wc_bulk_purchase_fee_label = "تخفیف ویژه یلدا دوره‌های آفلاین";
        $total_discount = 0;
        $offline_count = 0;
        foreach ($cart->get_cart() as $cart_item) {
          $product = $cart_item['data'];
          $product_id = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();
          $field_type = function_exists('get_field') ? get_field("type", $product_id) : "";
          $is_offline = ($field_type === "آفلاین" || stripos($product->get_title(), "آفلاین") !== false);
          if ($is_offline) {
            $offline_count += $cart_item['quantity'];
          }
        }

        // Only apply discount if 2 or more offline products found
        if ($offline_count >= 2) {
          foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();
            $field_type = function_exists('get_field') ? get_field("type", $product_id) : "";
            $is_offline = ($field_type === "آفلاین" || stripos($product->get_title(), "آفلاین") !== false);
            if ($is_offline) {
              $line_price = $product->get_price() * $cart_item['quantity'];
              $discount = $line_price * $discount_amount;
              $total_discount += $discount;
            }
          }
          if ($total_discount > 0) {
            $cart->add_fee($wc_bulk_purchase_fee_label, -$total_discount);
          }
        }
      }, 20);

      add_action('acf/admin_head', function() {
        wp_enqueue_style('select2-css', "{$this->assets_url}css/select2.min.css", array(), '4.1.0-rc.0');
        wp_enqueue_script('select2-js', "{$this->assets_url}js/select2.min.js", 'jquery', '4.1.0-rc.0');
      }, 99999);

      if ("yes" == $this->read("currency_based_cache", "no")) {
        // https://github.com/wp-media/wp-rocket-helpers/tree/master/cache/wp-rocket-cache-dynamic-cookie/
        require_once plugin_dir_path(__FILE__) . "include/wp-rocket-cache-dynamic-cookie.php";
        require_once plugin_dir_path(__FILE__) . "include/wp-rocket-htaccess-custom-rules-no-store-html.php";
      }

      /* fix variable product price are not shown crossed-out */
      add_filter("woocommerce_get_price_html", function($price, $product){
        if ($product->is_type('variable') || $product->is_type('variable-subscription') && method_exists($product, 'get_variation_prices')) {
          $price_list = $this->using_dollar() === true ? $this->get_variation_prices(false, $product) : $product->get_variation_prices(true);
          $regular_prices = $this->using_dollar() === true ? $this->get_variation_regular_price("min", false, $product) : $product->get_variation_regular_price("min");
          $sale_prices = $this->using_dollar() === true ? $this->get_variation_sale_price("min", false, $product) : $product->get_variation_sale_price("min");
          if ($regular_prices != $sale_prices) return wc_format_sale_price($regular_prices, $sale_prices);
        };
        return $price;
      }, 9999, 2);

      if ($this->discount_percentage && !empty($this->discount_percentage) && floatval($this->discount_percentage) > 0) {
        // add global discount to all products and apply on sale-price based on regular price
        add_filter("woocommerce_product_get_price", array($this, "apply_global_discount"), 999, 2);
        add_filter("woocommerce_product_get_sale_price", array($this, "apply_global_discount"), 999, 2);
        add_filter("woocommerce_product_variation_get_price", array($this, "apply_global_discount"), 999, 2);
        add_filter("woocommerce_product_variation_get_sale_price", array($this, "apply_global_discount"), 999, 2);
        add_filter("woocommerce_get_price_html", function($price, $product){
          $product_id = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();
          if (!has_term((array) $this->discount_category, 'product_cat', $product_id)) return $price;
          $raw_price = $this->apply_global_discount($product->get_price("edit"), $product, true);
          if ( '' === $product->get_price() ) { $price = apply_filters( 'woocommerce_empty_price_html', '', $product ); }
          else { $price = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), $raw_price ) . $product->get_price_suffix(); }
          return $price;
        }, 1, 2);
        add_filter("woocommerce_cart_item_price", array($this, "display_cart_item_discounted_price"), 999, 3);
        add_action("woocommerce_before_calculate_totals", array($this, "update_cart_items_price"), 999, 1);
      }

      // change user avatar url for comments section, hook to pre_get_avatar to use profile_image meta instead of gravatar if exists

      // remove learndash request to its server for update
      remove_action("init", "nss_plugin_updater_activate_learndash_propanel");
      remove_action("woocommerce_scheduled_subscription_payment", ["WC_Subscriptions_Manager", "prepare_renewal"], 1, 1);
      remove_action("woocommerce_order_details_after_order_table", ["WC_Subscriptions_Order", "add_subscriptions_to_view_order_templates"], 10);
      remove_action("woocommerce_email_after_order_table", ["WC_Subscriptions_Order", "add_sub_info_email"], 15);
      // remove_action("woocommerce_subscription_details_after_subscription_table", ["WC_Subscriptions_Order","get_related_orders_template"]);

      // when an order includes a LearnDash course, it wont be automatically marked as Complete.
      add_filter("learndash_woocommerce_auto_complete_order", "__return_false");

      require_once plugin_dir_path(__FILE__) . "include/class-cron.php";
      require_once plugin_dir_path(__FILE__) . "include/class-stock-log.php";
      require_once plugin_dir_path(__FILE__) . "include/class-jdate.php";

      add_action("admin_init", array($this, "check_database"));

      add_action("admin_menu", function(){
        add_menu_page(__("MNStudio Pro - Configuration",$this->td), __("MNStudio Pro",$this->td), "manage_options", "mnstudio", array($this, "setting_wrapper"), "dashicons-welcome-learn-more", 4);
        add_submenu_page("mnstudio", __("MNStudio Pro - CronJob List",$this->td), __("New CronJob",$this->td), "manage_options", "mnstudio_cronjob", function(){do_action("mnstudio_cronjob_page");});
        add_submenu_page("mnstudio", __("MNStudio Pro - Stock History",$this->td), __("Stock History",$this->td), "manage_options", "mnstudio_stock_log", function(){do_action("mnstudio_stock_log_page");});
      });

      add_action("init", array($this, "learndash_loaded"));
      add_action("init", array($this, "init_plugin"));
      add_action("init", array($this, "expire_past_due_subscriptions"));
      add_action("init", array($this, "create_category_comment_post_type"));
      add_action("pre_get_posts", array($this, "exclude_learndash_post_types_from_search"));
      add_action("created_product_cat", array($this, "save_category_comment_dropdown"));
      add_action("edited_product_cat", array($this, "save_category_comment_dropdown"));
      add_action("product_cat_edit_form_fields", array($this, "add_category_comment_field"));
      add_action("template_redirect", function ($template) {
        if (is_singular("category_comment")) {
          $term_id = get_post_meta(get_the_ID(), "_category_id", true);
          $archive_url = get_term_link((int) $term_id, "product_cat");
          if (!is_wp_error($archive_url)) {
            wp_redirect($archive_url);
            exit;
          }
        }
        return $template;
      });

      add_action("wp", array($this, "stop_schedule_and_remove_events"));
      add_action("init", array($this, "process_scheduled_actions_from_db"));
      add_filter("cron_schedules", array($this, "add_custom_intervals"));
      add_action("mnstudio_email_sms_queue_process", array($this, "process_email_sms_queue"));

      // hide other shipping methods if blackfriday free-shipping coupon is used
      // add_filter("woocommerce_package_rates", array($this, "hide_shipping_when_free_is_available"), 100 );

      // free shipping for all orders at Start of 8 Azar till End of 9 Azar
      add_filter("woocommerce_shipping_free_shipping_is_available", array($this, "free_shipping_for_date_to_date"), 10, 2);

      // put out-of-stock products to end of list
      add_filter("posts_clauses", array($this, "put_out_of_stock_products_last"), 999999, 2);

      add_action("woocommerce_add_to_cart", array($this, "add_diet_program_to_cart"), 30, 0);
      add_action("woocommerce_before_calculate_totals", [$this, 'check_cart_for_restricted_product'], 99999);
      add_filter("woocommerce_get_cart_item_from_session", function ($cart_item, $values, $key) {
        if (isset($values['mn_price'])) {
          $cart_item['price'] = $values['mn_price'];
          $cart_item['total'] = $values['mn_price'];
          $cart_item['subtotal'] = $values['mn_price'];
        }
        return $cart_item;
      }, 10, 3);

    }
    public function has_mat_in_cart(){
      $booster_pid = 91271939236; // booster product id, cost: 230_000
      $has_mat = false;
      // Check if all items in the cart belong to category "yoga-mat"
      foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item["data"];
        $product_id = $product->get_id();
        if ($product->is_type("variation") || ($product->get_parent_id() && $product->get_parent_id() > 0)) $product_id = $product->get_parent_id();
        // If any product is in "yoga-mat" category, stop
        if (has_term("yoga-mat", "product_cat", $product_id)) { $has_mat = true; break; }
        // if product is booster product, set $has_mat to true
        if ((string) $booster_pid === (string) $product_id) { $has_mat = true; break; }
      }
      return $has_mat;
    }
    public function custom_mat_shipping_rate($rates, $package) {
      $shipping_ids = array(1, 6); // send via Post shipping method instance IDs here
      $new_shipping_cost = 230_000; // New shipping cost for "yoga-mat" category items
      $has_mat = $this->has_mat_in_cart();
      // If all items are from "yoga-mat" category or found booster product, set shipping price
      if ($has_mat) {
        foreach ($rates as $rate_id => $rate) {
          $instance_id = isset($rate->instance_id) ? $rate->instance_id : "";
          if (in_array($instance_id, $shipping_ids)) {
            $rates[$rate_id]->cost = $new_shipping_cost;
            if (!empty($rates[$rate_id]->taxes) && is_array($rates[$rate_id]->taxes)) {
              foreach ($rates[$rate_id]->taxes as $key => $tax) {
                $rates[$rate_id]->taxes[$key] = 0;
              }
            }
          }
        }

        add_filter("woocommerce_update_order_review_fragments", function($fragments) use ($has_mat) {
          if ($has_mat) {
            $script = <<<HTML
              <style>
                /* .woocommerce-checkout #shipping_method_0_flat_rate6+label .woocommerce-Price-amount.amount bdi:before,
                .woocommerce-checkout #shipping_method_0_flat_rate1+label .woocommerce-Price-amount.amount bdi:before{display: none;} */
                .woocommerce #shipping_method_0_flat_rate6+label .woocommerce-Price-amount.amount bdi:before,
                .woocommerce #shipping_method_0_flat_rate1+label .woocommerce-Price-amount.amount bdi:before,
                .woocommerce-checkout #shipping_method_0_flat_rate6+label .woocommerce-Price-amount.amount bdi:before,
                .woocommerce-checkout #shipping_method_0_flat_rate1+label .woocommerce-Price-amount.amount bdi:before
              </style>
            HTML;
            if (isset($fragments[".woocommerce-checkout-review-order-table"])) {
              $fragments[".woocommerce-checkout-review-order-table"] .= $script;
            }
          }
          return $fragments;
        });

      }
      return $rates;
    }
    public function woocommerce_locate_template( $template, $template_name, $_template_path ) {
      switch ($template_name) {
        case 'cart_mini_cart':
        case 'cart/mini-cart.php':
          $template = get_stylesheet_directory() . '/woocommerce/cart/mini-cart.php';
          if ( file_exists( $template ) ) return $template;
        break;
        default:
          return $template;
        break;
      }
      return $template;
    }
    // Include a PHP file from the plugin directory
    public function include($name="", $folder="include"){
      $path = plugin_dir_path(__FILE__) . "{$folder}/{$name}.php";
      if (file_exists($path)) { require_once $path; }
    }
    public function is_new_ui_template($product_id){
      /*
        = New UI Offline ==
        temp-180
        temp-ashtanga
        temp-barreausol
        temp-meditation
        temp-pregnancy
        template-offline
        == New UI Online ==
        temp-online
        == New UI Private ==
        temp-private
        */

      return
      $this->is_new_ui_offline($product_id) ||
      $this->is_new_ui_online($product_id) ||
      $this->is_new_ui_private($product_id);
    }
    public function is_new_ui_offline($product_id){
      return has_term("template-offline", "product_tag", $product_id) ||
             has_term("temp-180", "product_tag", $product_id) ||
             has_term("temp-ashtanga", "product_tag", $product_id) ||
             has_term("temp-barreausol", "product_tag", $product_id) ||
             has_term("temp-meditation", "product_tag", $product_id) ||
             has_term("temp-pregnancy", "product_tag", $product_id);
    }
    public function is_new_ui_online($product_id){
      return has_term("template-online", "product_tag", $product_id) ||
             has_term("temp-online", "product_tag", $product_id);
    }
    public function is_new_ui_private($product_id){
      return has_term("template-private", "product_tag", $product_id) ||
             has_term("temp-private", "product_tag", $product_id);
    }
    /**
    * Update cart item prices with global discount.
    *
    * @param WC_Cart $cart
    * @return void
    */
    public function update_cart_items_price($cart) {
      if (is_admin() && !defined('DOING_AJAX')) return;
      foreach ($cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();
        if (!has_term((array) $this->discount_category, 'product_cat', $product_id)) continue;
        $regular_price = $product->get_regular_price("edit");
        if (!$regular_price) continue;
        $discounted_price = $regular_price * (1 - $this->discount_percentage / 100);
        $cart_item['data']->set_price($discounted_price);
      }
    }
    /**
    * Display discounted price in cart.
    *
    * @param string $price
    * @param array $cart_item
    * @param string $cart_item_key
    * @return string
    */
    public function display_cart_item_discounted_price($price, $cart_item, $cart_item_key) {
      $product = $cart_item['data'];
      $product_id = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();
      if (!has_term((array) $this->discount_category, 'product_cat', $product_id)) return $price;
      $regular_price = $product->get_regular_price("edit");
      $discounted_price = $regular_price * (1 - $this->discount_percentage / 100);
      // Show original price crossed out and discounted price
      return '<del>' . wc_price($regular_price) . '</del><ins>' . wc_price($discounted_price) . '</ins>';
    }
    /**
    * Apply global discount to product price.
    *
    * @param float $price
    * @param \WC_Product $product
    * @return float
    */
    public function apply_global_discount($price, $product, $suppress=false) {
      $product_id = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();
      if (!has_term((array) $this->discount_category, 'product_cat', $product_id)) return $price;
      $regular_price = $suppress ? (floatval($product->get_regular_price())>0?$product->get_regular_price():$product->get_price()) : $product->get_regular_price();
      if (!$regular_price) return $price;
      $discounted_price = $regular_price * (1 - $this->discount_percentage / 100);
      return $discounted_price;
    }
    public function put_out_of_stock_products_last($posts_clauses, $query) {
      global $wpdb;
      // Skip if it’s admin, not a product query, or not relevant
      if (is_ajax() && $query->get('post_type') && 'product' == $query->get('post_type') || (is_woocommerce() && ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ))) {
        $posts_clauses['join'] .= " INNER JOIN $wpdb->postmeta istockstatus ON ($wpdb->posts.ID = istockstatus.post_id) ";
        $posts_clauses['orderby'] = " istockstatus.meta_value ASC, " . $posts_clauses['orderby'];
        $posts_clauses['where'] = " AND istockstatus.meta_key = '_stock_status' AND istockstatus.meta_value <> '' " . $posts_clauses['where'];
      }
      return $posts_clauses;
    }
    public function hide_shipping_when_free_is_available( $rates ) {
      // Check if "blackfriday" coupon is applied
      if ( in_array( 'blackfriday', WC()->cart->get_applied_coupons(), true ) ) {
        $free = array();
        foreach ( $rates as $rate_id => $rate ) {
          if ( 'free_shipping' === $rate->method_id ) {
            $free[ $rate_id ] = $rate;
            break;
          }
        }
        return ! empty( $free ) ? $free : $rates;
      }
      // Return original rates if condition is not met
      return $rates;
    }
    public function learndash_loaded(){
      $user_id = get_current_user_id();
      if (!$user_id || !is_user_logged_in()) return;
      if (!function_exists("wcs_get_subscriptions_for_order")) return;
      $orders = wc_get_orders(array(
        "customer_id" => $user_id,
        "status" => ["processing","completed"],
        // "return" => "ids",
        "limit" => -1,
      ));
      if ($orders && !empty($orders)) {
        foreach ($orders as $order_id) {
          $subs = (array) wcs_get_subscriptions_for_order($order_id);
          foreach ($subs as $subscription) {
            // $item = new \WC_Subscription($item);
            if ($subscription->has_status(["expired", "cancelled"])) {
              // if (class_exists("WC_Subscriptions_Manager")) {
                // \WC_Subscriptions_Manager::clear_users_subscriptions_from_order($order_id);
              // }
              if (class_exists("Learndash_WooCommerce")) {
                \Learndash_WooCommerce::remove_subscription_course_access($subscription);
              }
            }
          }
        }
      }
    }
    public function free_shipping_for_date_to_date($is_available, $package){
      // Set timezone to WordPress's configured timezone
      date_default_timezone_set(wp_timezone_string());

      // Get the current server time
      $current_time = strtotime(date('Y-m-d H:i:s'));

      // Define the start and end timestamps for free shipping
      $start_time = strtotime('2024-11-28 00:00:00'); // Start of 8 Azar
      $end_time = strtotime('2024-11-29 23:59:59');   // End of 9 Azar

      // Check if the current time is within the free shipping window
      if ($current_time >= $start_time && $current_time <= $end_time) {
        add_filter("woocommerce_package_rates", function( $rates ) { $free = array();
          foreach ( $rates as $rate_id => $rate ) { if ( 'free_shipping' === $rate->method_id ) { $free[ $rate_id ] = $rate; break; } }
          return !empty( $free ) ? $free : $rates;
        }, 100 );
        return true; // Enable free shipping
      }

      return $is_available; // Default behavior
    }
    public function process_scheduled_actions_from_db() {
      if ((float) $this->read("sms_queue_interval", 2) > 0) {
        if (!wp_next_scheduled("mnstudio_email_sms_queue_process")) {
          wp_schedule_event(time(), "mnstudio_interval", "mnstudio_email_sms_queue_process");
        }
      }else{
        wp_clear_scheduled_hook("mnstudio_email_sms_queue_process");
      }
    }
    public function add_custom_intervals($schedules) {
      $custom_interval = (float) $this->read("sms_queue_interval", 2);
      $schedules["mnstudio_interval"] = array(
        "interval" => $custom_interval * MINUTE_IN_SECONDS,
        "display"  => "MNStudio Queue Interval",
      );
      return $schedules;
    }
    public function stop_schedule_and_remove_events() {
      if ((float) $this->read("sms_queue_interval", 2) <= 0 ) {
        wp_clear_scheduled_hook("mnstudio_email_sms_queue_process");
      }
    }
    public function add_fav_icon(){
      $fav = $this->read("favicon");
      if (!empty(trim($fav))) {
        echo '<!-- fav icon added by mnstudio-core plugin; developed by pigment.dev --><link rel="icon" href="' . esc_url($fav) . '" type="image/x-icon" />';
      }
    }
    public function check_database(){
      $cur_version = $this->read("mnstudio_db_version", null);
      // Check if it's the first install or if an update is required
      if (is_null($cur_version) || version_compare($cur_version, $this->db_version, "lt")) {
        $this->create_database(true); // Create or update the database
        $this->set("mnstudio_db_version", $this->db_version); // Update the stored version
      }
    }
    public function create_database($force = false) {
      global $wpdb;
      if (!function_exists('dbDelta')) {
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
      }
      $charset_collate = $wpdb->get_charset_collate();
      if ($wpdb->get_var("SHOW TABLES LIKE '" . $this->db_table . "'") != $this->db_table || false !== $force) {
        dbDelta("CREATE TABLE `$this->db_table` (
          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `date_sent` VARCHAR(20),
          `scheduled` VARCHAR(20),
          `mobile` VARCHAR(20),
          `order_id` BIGINT(20),
          `product` BIGINT(20),
          `refer` VARCHAR(500),
          `message_type` VARCHAR(50) DEFAULT 'PENDING',
          `type` VARCHAR(20) DEFAULT 'none',
          `idx` VARCHAR(2) DEFAULT '0',
          `sms` TEXT,
          `body` TEXT,
          `subject` VARCHAR(150),
          `details` TEXT,
          `result` TEXT,
          PRIMARY KEY (`id`) ) $charset_collate;");
      }
      if ($wpdb->get_var("SHOW TABLES LIKE '" . $this->db_table_sl . "'") != $this->db_table_sl || false !== $force) {
        dbDelta("CREATE TABLE `$this->db_table_sl` (
          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `order_id` BIGINT(20),
          `product_id` BIGINT(20),
          `user_id` BIGINT(20),
          `refer` VARCHAR(500),
          `details` TEXT,
          `result` TEXT,
          `extras` TEXT,
          PRIMARY KEY (`id`) ) $charset_collate;");
      }
    }
    public function send_sms($numbers, $message) {
      $response = false;
      $username = trim($this->read("username_api"));
      $password = trim($this->read("password_secret"));
      $from     = trim($this->read("sender_number"));
      $domain   = trim($this->read("server_url"));
      if (empty($username) || empty($password) || empty($from)) {
        return new \WP_Error("empty-user-pass", _x("SMS Provider credentials is not filled completely.", "sms-error", $this->td));
      }
      $massage = trim(strip_tags($message));
      if (is_array($numbers)) $to = $numbers;
      else $to = explode(',', $numbers);
      $massage = str_replace('pcode:', 'patterncode:', strip_tags(trim($massage)));
      if (substr($massage, 0, 11) === "patterncode") {
        $massage = str_replace("\r\n", ";", $massage);
        $massage = str_replace("\n", ";", $massage);
        $splitted = explode(';', $massage);
        $patterncodeArray = explode(':', $splitted[0]);
        $patterncode = trim($patterncodeArray[1]);
        unset($splitted[0]);
        $input_data = array();
        foreach ($splitted as $parm) {
          $splitted_parm = explode(':', $parm, 2);
          $input_data[$splitted_parm[0]] = trim($splitted_parm[1]);
        }
        foreach ($to as $toNum) {
          $url = "/patterns/pattern?username=" . $username . "&password=" . urlencode($password) . "&from=$from&to=" . json_encode(array($toNum)) . "&input_data=" . urlencode(json_encode($input_data)) . "&pattern_code=" . $patterncode;
          $result = $this->cUrl($url, array(), 'GET');
        }
        $result_array = json_decode($result);
        if (is_array($result_array) && $result_array[0] != 'sent') {
          $res_code = $result_array[0];
          $res_data = $result_array[1];
          return $this->getErrors($res_code, $result);
        }
        $response = true;
        return $response;
      } else {
        $url = "/services.jspd";
        $params = array('uname' => $username, 'pass' => $password, 'from' => $from, 'message' => $massage, 'to' => json_encode($to), 'op' => 'send');
        $result = $this->cUrl($url, $params, 'POST');
        $result2 = json_decode($result);
        $res_code = $result2[0];
        $res_data = $result2[1];
        if ($res_code == '0') {
          $response = true;
        } else {
          $response = $this->getErrors($res_code, $result);
        }
        return $response;
      }
    }
    private function cUrl($url, $params = array(), $method = 'POST') {
      $domain = trim($this->read("server_url"));
      $handler = curl_init($domain . $url);
      curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, 20);
      curl_setopt($handler, CURLOPT_TIMEOUT, 30);
      curl_setopt($handler, CURLOPT_CUSTOMREQUEST, $method);
      if ($method == 'POST') curl_setopt($handler, CURLOPT_POSTFIELDS, $params);
      curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, 0);
      $result = curl_exec($handler);
      if (curl_errno($handler)) {
        $result = curl_error($handler);
        return json_encode(array('-1', $result));
      }
      return $result;
    }
    private function getErrors($error="", $json="") {
      $errorCodes = array(
        '-1'   => 'ارتباط با سامانه پیامک انجام نشد.',
        '0'    => 'عملیات با موفقیت انجام شده است.',
        '1'    => 'متن پیام خالی می باشد.',
        '2'    => 'کاربر محدود گردیده است.',
        '3'    => 'خط به شما تعلق ندارد.',
        '4'    => 'گیرندگان خالی است.',
        '5'    => 'اعتبار کافی نیست.',
        '7'    => 'خط مورد نظر برای ارسال انبوه مناسب نمی باشد.',
        '9'    => 'خط مورد نظر در این ساعت امکان ارسال ندارد. برای ارسال پیامک در ۲۴ ساعت شبانه روز از وب سرویس پترن استفاده نمایید',
        '98'   => 'حداکثر تعداد گیرنده رعایت نشده است.',
        '99'   => 'اپراتور خط ارسالی قطع می باشد.',
        '21'   => 'پسوند فایل صوتی نامعتبر است.',
        '22'   => 'سایز فایل صوتی نامعتبر است.',
        '23'   => 'تعداد تالش در پیام صوتی نامعتبر است.',
        '100'  => 'شماره مخاطب دفترچه تلفن نامعتبر می باشد.',
        '101'  => 'شماره مخاطب در دفترچه تلفن وجود دارد.',
        '102'  => 'شماره مخاطب با موفقیت در دفترچه تلفن ذخیره گردید.',
        '111'  => 'حداکثر تعداد گیرنده برای ارسال پیام صوتی رعایت نشده است.',
        '131'  => 'تعداد تالش در پیام صوتی باید یکبار باشد.',
        '132'  => 'آدرس فایل صوتی وارد نگردیده است.',
        '301'  => 'از حرف ویژه در نام کاربری استفاده گردیده است.',
        '302'  => 'قیمت گذاری انجام نگردیده است.',
        '303'  => 'نام کاربری وارد نگردیده است.',
        '304'  => 'نام کاربری قبال انتخاب گردیده است.',
        '305'  => 'نام کاربری وارد نگردیده است.',
        '306'  => 'کد ملی وارد نگردیده است.',
        '307'  => 'کد ملی به خطا وارد شده است.',
        '308'  => 'شماره شناسنامه نا معتبر است.',
        '309'  => 'شماره شناسنامه وارد نگردیده است.',
        '310'  => 'ایمیل کاربر وارد نگردیده است.',
        '311'  => 'شماره تلفن وارد نگردیده است.',
        '312'  => 'تلفن به درستی وارد نگردیده است.',
        '313'  => 'آدرس شما وارد نگردیده است.',
        '314'  => 'شماره موبایل را وارد نکرده اید.',
        '315'  => 'شماره موبایل به نادرستی وارد گردیده است.',
        '316'  => 'سطح دسترسی به نادرستی وارد گردیده است.',
        '317'  => 'کلمه عبور وارد نگردیده است.',
        '455'  => 'ارسال در آینده برای کد بالک ارسالی لغو شد.',
        '456'  => 'کد بالک ارسالی نامعتبر است.',
        '458'  => 'کد تیکت نامعتبر است.',
        '964'  => 'شما دسترسی نمایندگی ندارید.',
        '962'  => 'نام کاربری یا کلمه عبور نادرست می باشد.',
        '963'  => 'دسترسی نامعتبر می باشد.',
        '971'  => 'پترن ارسالی نامعتبر است.',
        '970'  => 'پارامتر های ارسالی برای پترن نامعتبر است.',
        '972'  => 'دریافت کننده برای ارسال پترن نامعتبر می باشد.',
        '992'  => 'ارسال پیام از ساعت 8 تا 23 می باشد. برای ارسال پیامک در ۲۴ ساعت شبانه روز از وب سرویس پترن استفاده نمایید',
        '993'  => 'دفترچه تلفن باید یک آرایه باشد',
        '994'  => 'لطفا تصویری از کارت بانکی خود را از منو مدارک ارسال کنید',
        '995'  => 'جهت ارسال با خطوط اشتراکی سامانه، لطفا شماره کارت بانکی خود را به دلیل تکمیل فرایند احراز هویت از بخش ارسال مدارک ثبت نمایید.',
        '996'  => 'پترن فعال نیست.',
        '997'  => 'شما اجازه ارسال از این پترن را ندارید.',
        '998'  => 'کارت ملی یا کارت بانکی شما تایید نشده است.',
        '1001' => 'فرمت نام کاربری درست نمی باشد)حداقل ۵ کاراکتر، فقط حروف و اعداد(.',
        '1002' => 'گذرواژه خیلی ساده می باشد)حداقل ۸ کاراکتر بوده و نام کاربری، ایمیل و شماره موبایل در آن وجود نداشته باشد(.',
        '1003' => 'مشکل در ثبت، با پشتیبانی تماس بگیرید.',
        '1004' => 'مشکل در ثبت، با پشتیبانی تماس بگیرید.',
        '1005' => 'مشکل در ثبت، با پشتیبانی تماس بگیرید.',
        '1006' => 'تاریخ ارسال پیام برای گذشته می باشد، لطفا تاریخ ارسال پیام را به درستی وارد نمایید.',
        );
      return (isset($errorCodes[$error])) ? $errorCodes[$error] : 'اشکال تعریف نشده با کد :' . var_export($error, true) . var_export($json, true);
    }
    public function create_notif_entry_from_order($order_id=0, $force=false) {
      $order = wc_get_order($order_id);
      if (!$order) return false;
      if ("yes" == $order->get_meta("_schedule_on_thankyou", true) && !$force) return true;
      if (!$order->has_status(['completed','processing']) && !$force) return false;
      if ($order->has_status(array('pending', 'on-hold', 'cancelled'))) return false;
      global $wpdb;
      $time = strtotime(current_time('mysql'));
      foreach ($order->get_items() as $item_id => $item) {
        $target_id = $item->get_variation_id() && $item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id();
        $delay_interval = DAY_IN_SECONDS;
        if ($this->diet_id == $target_id) $delay_interval = MINUTE_IN_SECONDS;
        if ($this->pregnant_id == $target_id) $delay_interval = MINUTE_IN_SECONDS;
        if ($target_id && !empty($target_id) && wc_get_product($target_id)) {
          $name = $item->get_name();
          $enabled = get_post_meta($target_id, "_scheduled_notif", true);
          if ("yes" === $enabled) {

            $note = array();
            for ($i=1; $i <= 2; $i++) {
              $notif_day = get_post_meta($target_id, "_scheduled_notif{$i}_day", true);
              if (!empty($notif_day) && intval($notif_day) > 0) {

                // remove_all_filters("wp_date");
                // remove_all_filters("date_i18n");

                $date = $time + ($delay_interval * intval($notif_day));
                $sms = get_post_meta($target_id, "_scheduled_notif{$i}_sms", true);
                $body = get_post_meta($target_id, "_scheduled_notif{$i}_email", true);
                $subject = get_post_meta($target_id, "_scheduled_notif{$i}_email_subject", true);
                $user_name = is_user_logged_in() ? get_current_user_id() : ($order->get_user_id() ? $order->get_user_id() : "USER_SYSTEM_BOT");
                $isAdded = $wpdb->insert($this->db_table, array(
                  "date_sent"    => "",
                  "scheduled"    => date("Y-m-d H:i:s", $date),
                  "mobile"       => $order->get_billing_phone(),
                  "order_id"     => $order_id,
                  "product"      => $target_id,
                  "refer"        => "scheduled notif #{$i}",
                  "idx"          => $i,
                  "message_type" => "PENDING",
                  "result"       => "",
                  "type"         => "auto_detect_type",
                  "sms"          => $sms,
                  "body"         => $body,
                  "subject"      => $subject,
                  "details"      => "<strong>.:: MNStudio Pro - v.$this->version ::.</strong>" .
                                  "<br>User: " . $this->display_user($user_name, true, true, true) . " / Order: <a href='".admin_url("admin.php?page=wc-orders&action=edit&id=".$order_id)."' target='_blank'>#$order_id</a>".
                                  "<br>triggered from fn ~> " . __METHOD__ .
                                  "<br>Request added at: " . date_i18n("Y/m/d H:i:s", current_time("timestamp")) .
                                  "<br>~~ /. end ./ ~~",
                ));
                $dbStatus = $isAdded && !is_null($isAdded) && false !== $isAdded ? "SUCCESS, dB_idx: #{$wpdb->insert_id}" : "ERR: <pre style='text-align: left; direction: ltr;'>". var_export($wpdb->last_error,1) ."</pre>";
                $emoji = $isAdded && !is_null($isAdded) && false !== $isAdded ? "✅" : "❌";
                $note[] = "<div style='text-align: left; direction: ltr;'>{$emoji} Notif#{$i} Scheduled.<br>$name <small>(#$target_id)</small><br>Res: {$dbStatus}</div>";
              }
            }
            if (!empty($note)) {
              $order->add_order_note("<div style='text-align: left; direction: ltr;'><strong>MNStudio Pro</strong><br></div>" . implode("<br>&nbsp;", $note), false, false);
            }

          }
        }
      }
      $order->update_meta_data("_schedule_on_thankyou", "yes");
      $order->save();
      return $order->get_edit_order_url();
    }
    public function process_email_sms_queue($silent=true, $id=false){
      global $wpdb;
      $num_process = (int) $this->read("sms_queue_items", 4);
      $list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->db_table WHERE (`message_type` = 'PENDING' OR `message_type` = 'FAILED') AND `scheduled` <= %s ORDER BY `id` DESC LIMIT %d", current_time("mysql"), $num_process ) );
      if ($id) {
        $list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->db_table WHERE `id` = %d ORDER BY `id` DESC LIMIT 1", $id ) );
      }
      if (!$silent) echo "<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>SQL: ".$wpdb->last_query."<br>Err: ".var_export($wpdb->last_error,1)."<br>Rows: ". var_export($list ? count($list) : 0,1) ."</pre>";
      foreach ($list as $entry) {
        if (!$silent) echo "<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>". print_r($entry,1) ."</pre><br><br>";
        $order_id = $entry->order_id;
        $order = wc_get_order($order_id);
        if (!$order){
          $wpdb->update($this->db_table, array("message_type" => "SKIPPED",
            "date_sent" => current_time("mysql"),
            "details" => $entry->details = $entry->details . "<hr><pre style='text-align: left; margin: 0; direction: ltr; padding: 3px; overflow: auto;'>request sent at : ".date_i18n("Y/m/d H:i:s", current_time("timestamp"))."\nprocess queue res: " . var_export("wc_get_order err",1) ."</pre>",
            "result" => "wc_get_order err",
          ), array("id"=>$entry->id));
          continue;
        }
        if ($order->has_status(array('pending', 'on-hold', 'cancelled'))){
          $order->add_order_note("<div style='text-align: left; direction: ltr;'><strong>MNStudio Pro</strong> - cronjob status: ".("invalid_status " . $order->get_status("edit"))."</div>", false, false);
          $wpdb->update($this->db_table, array("message_type" => "SKIPPED",
            "date_sent" => current_time("mysql"),
            "details" => $entry->details = $entry->details . "<hr><pre style='text-align: left; margin: 0; direction: ltr; padding: 3px; overflow: auto;'>request sent at : ".date_i18n("Y/m/d H:i:s", current_time("timestamp"))."\nprocess queue res: " . var_export("invalid_status " . $order->get_status("edit"),1) ."</pre>",
            "result" => "invalid_status " . $order->get_status("edit"),
          ), array("id"=>$entry->id) );
          continue;
        }

        $already_sent_email = "yes" == $order->get_meta("_sent_{$entry->idx}_email_{$entry->product}", true);
        $already_sent_sms =  "yes" == $order->get_meta("_sent_{$entry->idx}_sms_{$entry->product}", true);


        if ($already_sent_email && $already_sent_sms){
          $order->add_order_note("<div style='text-align: left; direction: ltr;'><strong>MNStudio Pro</strong> - cronjob status: ".("already sent")."</div>", false, false);
          $wpdb->update($this->db_table, array("message_type" => "DUPLICATE",
            "date_sent" => current_time("mysql"),
            "details" => $entry->details = $entry->details . "<hr><pre style='text-align: left; margin: 0; direction: ltr; padding: 3px; overflow: auto;'>request sent at : ".date_i18n("Y/m/d H:i:s", current_time("timestamp"))."\nprocess queue res: " . var_export("already sent",1) ."</pre>",
            "result" => "already sent",
          ), array("id"=>$entry->id) );
          continue;
        }

        if (empty($entry->body) && empty($entry->subject) && empty($entry->sms)){
          $order->add_order_note("<div style='text-align: left; direction: ltr;'><strong>MNStudio Pro</strong> - cronjob status: ".("empty cronjob")."</div>", false, false);
          $wpdb->update($this->db_table, array("message_type" => "SKIPPED",
            "date_sent" => current_time("mysql"),
            "details" => $entry->details = $entry->details . "<hr><pre style='text-align: left; margin: 0; direction: ltr; padding: 3px; overflow: auto;'>request sent at : ".date_i18n("Y/m/d H:i:s", current_time("timestamp"))."\nsend_wc_email res: " . var_export("empty cronjob",1) ."</pre>",
            "result" => "empty cronjob",
          ), array("id"=>$entry->id) );
          continue;
        }

        $res_email = "email: status unknown.";
        if (!empty($entry->body) && !empty($entry->subject) && !$already_sent_email) {
          $this->send_wc_email($entry->subject, $entry->body, $order_id);
          $order->update_meta_data("_sent_{$entry->idx}_email_{$entry->product}", "yes");
          $res_email = "wc custom email sent to " . $order->get_billing_email();
          $order->add_order_note("<div style='text-align: left; direction: ltr;'><strong>MNStudio Pro</strong> - cronjob mail: ".($res_email)."</div>", false, false);
          $wpdb->update($this->db_table, array("message_type" => "SUCCESS",
            "date_sent" => current_time("mysql"),
            "details" => $entry->details = $entry->details . "<hr><pre style='text-align: left; margin: 0; direction: ltr; padding: 3px; overflow: auto;'>request sent at : ".date_i18n("Y/m/d H:i:s", current_time("timestamp"))."\nsend_wc_email res: " . var_export($res_email,1) ."</pre>",
            "result" => var_export("success",1)), array("id"=>$entry->id)
          );
        }

        if (!empty($entry->sms) && !$already_sent_sms && !empty($order->get_billing_phone())) {
          $mobile = $order->get_billing_phone();
          $new_status = $order->get_status();
          $message = $entry->sms;
          if (function_exists("PWSMS")) {
            $message = PWSMS()->replace_short_codes($entry->sms, $new_status, $order);
            // $response = PWSMS()->send_sms(array("post_id" => $order_id, "message" => $message, "mobile" => $mobile));
          }
          elseif (function_exists("PWooSMS")) {
            $message = PWooSMS()->ReplaceShortCodes($entry->sms, $new_status, $order);
            // $response = PWooSMS()->SendSMS(array("post_id" => $order_id, "message" => $message, "mobile" => $mobile));
          }
          $sent = $this->send_sms($mobile, $message);
          if ($sent && !is_wp_error($sent)) {
            $wpdb->update($this->db_table, array("message_type" => "SUCCESS",
              "date_sent" => current_time("mysql"),
              "details" => $entry->details = $entry->details . "<hr><pre style='text-align: left; margin: 0; direction: ltr; padding: 3px; overflow: auto;'>request sent at : ".date_i18n("Y/m/d H:i:s", current_time("timestamp"))."\nsend_sms res: send sms to $mobile: " . var_export($sent,1) . "<br>{$message}" ."</pre>",
              "result" => var_export("success",1)), array("id"=>$entry->id)
            );
            $order->add_order_note("<div style='text-align: left; direction: ltr;'><strong>MNStudio Pro</strong> - cronjob sms: success, res: ".var_export($sent,1)."</div>", false, false);
            $order->update_meta_data("_sent_{$entry->idx}_sms_{$entry->product}", "yes");
          }
          else {
            $wpdb->update($this->db_table, array("message_type" => "FAILED",
              "date_sent" => current_time("mysql"),
              "details" => $entry->details = $entry->details . "<hr><pre style='text-align: left; margin: 0; direction: ltr; padding: 3px; overflow: auto;'>request sent at : ".date_i18n("Y/m/d H:i:s", current_time("timestamp"))."\nsend_sms res: send sms to $mobile: " . (is_wp_error($sent) ? print_r($sent->get_error_message(),1) : var_export($sent,1)) ."<br>{$message}</pre>",
              "result" => (is_wp_error($sent) ? print_r($sent->get_error_message(),1) : var_export($sent,1)) ), array("id"=>$entry->id)
            );
            $order->add_order_note("<div style='text-align: left; direction: ltr;'><strong>MNStudio Pro</strong> - cronjob sms: error, details at #".$wpdb->insert_id."</div>", false, false);
          }
        }

        $order->save();
      }
      if (!$silent) echo "<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>finished at: ". date_i18n("Y/m/d H:i:s", current_time("timestamp")) ."</pre>";
    }
    public function send_wc_email($subject, $body, $order_id){
      add_filter("woocommerce_new_order_email_allows_resend", "__return_true");
      add_filter("woocommerce_email_subject_customer_invoice", function ($orig, $object, $that) use ($subject) { return $subject; }, 10, 3);
      add_filter("woocommerce_email_heading_customer_invoice", function ($orig, $object, $that) use ($subject) { return $subject; }, 10, 3);
      add_filter("woocommerce_email_heading_customer_invoice_paid", function ($orig, $object, $that) use ($subject) { return $subject; }, 10, 3);
      add_filter("woocommerce_email_subject_customer_invoice_paid", function ($orig, $object, $that) use ($subject) { return $subject; }, 10, 3);
      add_action("woocommerce_email_header", function ($heading, $email) use ($body) { if ($email->id == "customer_invoice") { echo do_shortcode($body) . "<section style='display:none;'>"; } }, 97125, 2);
      add_action("woocommerce_email_footer", function ($email) { if ($email->id == "customer_invoice") { echo "</section>"; } }, 97125);
      $mailer = WC()->mailer()->get_emails();
      $mailer['WC_Email_Customer_Invoice']->heading = $subject;
      $mailer['WC_Email_Customer_Invoice']->subject = $subject;
      $mailer['WC_Email_Customer_Invoice']->trigger($order_id);
      remove_all_actions("woocommerce_email_header", 97125);
      remove_all_actions("woocommerce_email_footer", 97125);
    }
    public function check_cart_for_restricted_product($cart) {
      if (is_admin() && !defined('DOING_AJAX')) { return; }

      $restricted_product_count = 0; $total_items = 0;
      foreach ($cart->get_cart() as $cart_item) {
        $total_items++;
        $product_id = $cart_item['product_id'];
        if ($product_id == $this->diet_id) $restricted_product_count++;
      }
      if ($restricted_product_count == 1 && $total_items == 1) {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
          if ($cart_item['product_id'] == $this->diet_id) $cart->remove_cart_item($cart_item_key);
        }
      }

      // $restricted_product_count = 0; $total_items = 0;
      // foreach ($cart->get_cart() as $cart_item) {
      //   $product_id = $cart_item['product_id'];
      //   $total_items++;
      //   if ($product_id == $this->pregnant_id) $restricted_product_count++;
      // }
      // if ($restricted_product_count == 1 && $total_items == 1) {
      //   foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
      //     if ($cart_item['product_id'] == $this->pregnant_id) $cart->remove_cart_item($cart_item_key);
      //   }
      // }

      foreach ( $cart->get_cart() as $item ) {
        if( array_key_exists("mn_price", $item)) {
          $item["data"]->set_price($item["mn_price"]);
        }
      }

    }
    public function add_include_diet_program_checkbox() {
      // Check if product is not in "shop" category
      if (!has_term('shop', 'product_cat') && !in_array(get_the_ID(), [$this->diet_id])) {
      // if (!has_term('shop', 'product_cat') && !in_array(get_the_ID(), [$this->diet_id, $this->pregnant_id])) {
        $price = ""; $diet = wc_get_product($this->diet_id);
        if ($diet) { $price = ($diet->get_price_html()); }
        $price2 = ""; $diet2 = wc_get_product($this->pregnant_id);
        if ($diet2) { $price2 = ($diet2->get_price_html()); }
        ?>
        <style>

          .woocommerce-variation-add-to-cart {
            flex-wrap: wrap !important;
          }

          label.include_diet_program_label {
            display: inline-block;
            margin: 0;
          }

          input.include_diet_program {
            height: 20px;
            width: 20px;
          }

          img.diet-top-overly {
            height: 45px;
            margin-right: 1rem;
          }

          .diet-addition {
            background: #b4f5bf;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
            line-height: 1.5;
          }

          @media only screen and (max-width: 600px) {

            .open-diet-info,
            .include_diet_program {
              flex: 0 0 auto;
            }

            .diet-addition {
              font-size: 13px;
              line-height: 1.5;
            }
          }

          .open-diet-info {
            display: block;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
            background: #f9f4ed;
            width: -webkit-max-content;
            width: -moz-max-content;
            width: max-content;
            padding: 0.3rem 1rem;
            border-radius: 5px;
            border: 2px dashed #e2871c;
            color: #e2871c !important;
            height: 36px;
          }
          .woocommerce-variation-add-to-cart { flex-wrap: wrap !important; }
          label.include_diet_program_label { display: inline-block; margin: 0; }
          input.include_diet_program { height: 1rem; height: 20px; width: 20px; }
          img.diet-top-overly{ height: 45px; margin-right: 1rem; }
          .diet-addition {
            background: #b4f5bf;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
            line-height: 1.5;
          }
          @media only screen and (max-width: 600px) {
            .open-diet-info, .include_diet_program{
              flex: 0 0 auto;
            }
            .diet-addition{
              font-size: 13px;
              line-height: 1.5;
            }
          }
          .open-diet-info {
            display: block;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
            background: #f9f4ed;
            width: max-content;
            padding: 0.3rem 1rem;
            border-radius: 5px;
            border: 2px dashed #e2871c;
            color: #e2871c !important;
            height: 36px;
          }
        </style>
        <?php
        //
        if (false && (has_term('pregnancy-yoga', 'product_cat') || "91271929183" == get_the_ID()) ){
          ?>
          <div class="mammography-program-wrapper">
            <img class="diet-top-overly" src="<?=$this->assets_url;?>pregnant.svg" />
            <div class="diet-addition">
              <input type="checkbox" class="include_diet_program" id="<?="mammography_checkbox_".get_the_ID();?>" name="include_mammography_program" value="<?=get_the_ID();?>" />
              <label class="include_diet_program_label" for="<?="mammography_checkbox_".get_the_ID();?>"><?=$this->pregnant_label;?> <span class="diet-price">(<?=($price2);?>)</span></label>
            </div>
            <div class="extra-diet-info">
              <a href="#open-mammography-popup" class="open-diet-info" title="برای اطلاعات بیشتر کلیک کنید" ><img class="diet-info-icon" src="<?=$this->assets_url;?>info.svg?<?=time()?>" width="20px" /> راهنمای ویزیت آنلاین با ماما</a>
            </div>
          </div>
          <?php
        }
        // if (!has_term('pregnancy-yoga', 'product_cat')) {
          ?>
          <div class="diet-program-wrapper">
            <img class="diet-top-overly" src="<?=$this->assets_url;?>diet.svg" />
            <div class="diet-addition">
              <input type="checkbox" class="include_diet_program" id="<?="diet_checkbox_".get_the_ID();?>" name="include_diet_program" value="<?=get_the_ID();?>" />
              <label class="include_diet_program_label" for="<?="diet_checkbox_".get_the_ID();?>"><?=$this->diet_label;?> <span class="diet-price">(<?=($price);?>)</span></label>
            </div>
            <div class="extra-diet-info">
              <a href="#open-diet-popup" class="open-diet-info" title="برای اطلاعات بیشتر کلیک کنید" ><img class="diet-info-icon" src="<?=$this->assets_url;?>info.svg?<?=time()?>" width="20px" /> راهنمای دریافت رژیم غذایی</a>
            </div>
          </div>
          <?php
        // }
      }
    }
    public function before_add_to_cart_button_newUI($only_out_of_stock=false) {
      // Check if product is not in "shop" category
      $product = wc_get_product(get_the_ID());
      // Read the CSS file and add as inline style
      $css_file = $this->assets_url . "css/add-to-cart.css";
      $css_path = plugin_dir_path(__FILE__) . "assets/css/add-to-cart.css";
      if (file_exists($css_path)) {
        $css_content = file_get_contents($css_path);
        wp_register_style("mn-add-to-cart", false);
        wp_enqueue_style("mn-add-to-cart");
        wp_add_inline_style("mn-add-to-cart", $css_content);
      } else {
        wp_enqueue_style("mn-add-to-cart", $css_file, [], time());
      }
      wp_enqueue_script("mn-add-to-cart", "{$this->assets_url}js/add-to-cart.js", ["jquery"], time());
      ?>
      <style> .wd-close-side.wd-close-side-opened { z-index: 100001; } .wd-side-hidden.wd-opened { z-index: 100002; } </style>
      <?php
      if (!$product->is_in_stock() && has_term("shop", "product_cat", $product->get_id())){
        // do not print bottom bar if physical and not-in stock
      }
      else{
        ?>
        <div class="sticky-bottom-bar-add2cart" style="display:none;">
          <div class="sticky-bar-inner" style="display:flex;align-items:center;justify-content:space-between;width:100%;">
            <button class="sticky-bar-add-to-cart-btn button alt"></button>
            <span class="sticky-bar-price"></span>
          </div>
        </div>
        <?php
      }


      if ($only_out_of_stock) return ;
      if (!has_term('shop', 'product_cat') && !in_array(get_the_ID(), [$this->diet_id])) {
        // if (!has_term('shop', 'product_cat') && !in_array(get_the_ID(), [$this->diet_id, $this->pregnant_id])) {
        $price = ""; $diet = wc_get_product($this->diet_id);
        if ($diet) { $price = $diet->get_price_html(); }
        $price3 = ""; $diet2 = wc_get_product($this->pregnant_id);
        if ($diet2) { $price3 = $diet2->get_price_html(); }
        // if (!has_term('pregnancy-yoga', 'product_cat')) {
        ?>
        <?php if ($this->is_new_ui_online(get_the_ID())) { ?>
          <style>
            .woocommerce-variation.single_variation { margin: -8px 0; }
          </style>
        <?php } ?>
        <?php if ($this->is_new_ui_private(get_the_ID())) { ?>
          <style>
            .woocommerce-variation.single_variation { margin: -8px 0; }
          </style>
        <?php } ?>
        <div class="diet-program-wrapper pid-<?=get_the_ID();?>">
          <?php

          // جدول مربی کلاس‌ها
          if (function_exists('get_field')) { $weekly_plan = get_field('weekly_plan', get_the_ID());
            if ($weekly_plan && is_array($weekly_plan)) {
              echo '<table class="acf-weekly-plan" style="width:100%;border-collapse:collapse;margin-bottom:1rem;">';
              echo '<thead><tr><th colspan=2 style="padding:8px;border:1px solid #ccc;">مربی کلاس‌ها</th></tr></thead>';
              echo '<tbody>';
              foreach ($weekly_plan as $row) {
                $week_day = isset($row['week_day']) ? esc_html($row['week_day']) : '';
                $instructor = isset($row['instructor']) ? esc_html($row['instructor']) : '';
                echo '<tr>';
                echo '<td style="padding:8px;border:1px solid #ccc;">' . $week_day . '</td>';
                echo '<th style="padding:8px;border:1px solid #ccc;">' . $instructor . '</th>';
                echo '</tr>';
              }
              echo '</tbody></table>';
          }}
          ?>

          <!-- کلاس انلاین -->
          <?php if ($this->is_new_ui_online(get_the_ID())) { ?>
            <!-- جدول راهنمای کلاس  -->
            <div class="extra-diet-info">
              <a href="#open-class-guide-popup" class="open-diet-info" title="برای اطلاعات بیشتر کلیک کنید" >
                <svg style="vertical-align: middle;" xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.5C17.514 2.5 22 6.986 22 12.5C22 18.014 17.514 22.5 12 22.5C6.486 22.5 2 18.014 2 12.5C2 6.986 6.486 2.5 12 2.5ZM12 4C7.313 4 3.5 7.813 3.5 12.5C3.5 17.187 7.313 21 12 21C16.687 21 20.5 17.187 20.5 12.5C20.5 7.813 16.687 4 12 4ZM12.0039 15.2959C12.5569 15.2959 13.0039 15.7429 13.0039 16.2959C13.0039 16.8489 12.5569 17.2959 12.0039 17.2959C11.4509 17.2959 10.9989 16.8489 10.9989 16.2959C10.9989 15.7429 11.4419 15.2959 11.9939 15.2959H12.0039ZM11.9941 7.954C12.4081 7.954 12.7441 8.29 12.7441 8.704V13.123C12.7441 13.537 12.4081 13.873 11.9941 13.873C11.5801 13.873 11.2441 13.537 11.2441 13.123V8.704C11.2441 8.29 11.5801 7.954 11.9941 7.954Z" fill="#94577C"></path></svg>
                جدول راهنمای کلاسها
              </a>
            </div>
            <!-- تیک مبتدی هستم -->
            <div class="diet-addition">
              <label class="opt-label include_diet_program_label" for="<?="beginner_user_".get_the_ID();?>">
                <input type="checkbox" id="<?="beginner_user_".get_the_ID();?>" class="opt-input beiginner_checkbox" name="beginner_term" value="yes" />
                مبتدی هستم
              </label>
            </div>
          <?php } ?>

          <!-- تیک ویزیت با ماما -->
          <?php if (false && (has_term('pregnancy-yoga', 'product_cat') || "91271929183" == get_the_ID()) ){ ?>
              <div class="diet-addition">
                <label class="opt-label include_diet_program_label" for="<?="mammography_checkbox_".get_the_ID();?>">
                  <input type="checkbox" class="opt-input include_diet_program" id="<?="mammography_checkbox_".get_the_ID();?>" name="include_mammography_program" value="<?=get_the_ID();?>" />
                  <?=$this->pregnant_label;?> <span class="diet-price">(<?=($price3);?>)</span>
                </label>
              </div>
              <div class="extra-diet-info">
                <a href="#open-mammography-popup" class="open-diet-info" title="برای اطلاعات بیشتر کلیک کنید" >
                  <svg style="vertical-align: middle;" xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.5C17.514 2.5 22 6.986 22 12.5C22 18.014 17.514 22.5 12 22.5C6.486 22.5 2 18.014 2 12.5C2 6.986 6.486 2.5 12 2.5ZM12 4C7.313 4 3.5 7.813 3.5 12.5C3.5 17.187 7.313 21 12 21C16.687 21 20.5 17.187 20.5 12.5C20.5 7.813 16.687 4 12 4ZM12.0039 15.2959C12.5569 15.2959 13.0039 15.7429 13.0039 16.2959C13.0039 16.8489 12.5569 17.2959 12.0039 17.2959C11.4509 17.2959 10.9989 16.8489 10.9989 16.2959C10.9989 15.7429 11.4419 15.2959 11.9939 15.2959H12.0039ZM11.9941 7.954C12.4081 7.954 12.7441 8.29 12.7441 8.704V13.123C12.7441 13.537 12.4081 13.873 11.9941 13.873C11.5801 13.873 11.2441 13.537 11.2441 13.123V8.704C11.2441 8.29 11.5801 7.954 11.9941 7.954Z" fill="#94577C"></path></svg>
                  راهنمای ویزیت آنلاین با ماما
                </a>
              </div>
          <?php } ?>

          <?php if ("subscription" === $product->get_type()) { echo '<div class="price-single '.$product->get_type().'">'.$product->get_price_html().'</div>'; } ?>

          <!-- تیک دریافت رژیم -->
          <div class="diet-addition">
            <label class="opt-label include_diet_program_label" for="<?="diet_checkbox_".get_the_ID();?>">
              <input type="checkbox" class="opt-input include_diet_program" id="<?="diet_checkbox_".get_the_ID();?>" name="include_diet_program" value="<?=get_the_ID();?>" />
              <?=$this->diet_label;?> <span class="diet-price">(<?=($price);?>)</span>
            </label>
          </div>

          <!-- راهنمای دریافت رژیم -->
          <div class="extra-diet-info">
            <a href="#open-diet-popup" class="open-diet-info" title="برای اطلاعات بیشتر کلیک کنید" >
              <svg style="vertical-align: middle;" xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.5C17.514 2.5 22 6.986 22 12.5C22 18.014 17.514 22.5 12 22.5C6.486 22.5 2 18.014 2 12.5C2 6.986 6.486 2.5 12 2.5ZM12 4C7.313 4 3.5 7.813 3.5 12.5C3.5 17.187 7.313 21 12 21C16.687 21 20.5 17.187 20.5 12.5C20.5 7.813 16.687 4 12 4ZM12.0039 15.2959C12.5569 15.2959 13.0039 15.7429 13.0039 16.2959C13.0039 16.8489 12.5569 17.2959 12.0039 17.2959C11.4509 17.2959 10.9989 16.8489 10.9989 16.2959C10.9989 15.7429 11.4419 15.2959 11.9939 15.2959H12.0039ZM11.9941 7.954C12.4081 7.954 12.7441 8.29 12.7441 8.704V13.123C12.7441 13.537 12.4081 13.873 11.9941 13.873C11.5801 13.873 11.2441 13.537 11.2441 13.123V8.704C11.2441 8.29 11.5801 7.954 11.9941 7.954Z" fill="#94577C"></path></svg>
              راهنمای دریافت رژیم غذایی
            </a>
          </div>

        </div>
        <div class="elementor-element elementor-last-divider elementor-widget-divider" data-id="896bdc8" style="max-width: 100%;--divider-border-style: solid;--divider-color: var( --e-global-color-320c5e0 );--divider-border-width: 1px;--kit-widget-spacing: 0px;margin-block-end: 0;">
          <div class="elementor-divider" style="padding-block-start: 16px;padding-block-end: 16px;"><span class="elementor-divider-separator" style="flex: 1 1 100%;"></span></div>
        </div>
        <?php
        if ($this->is_new_ui_offline(get_the_ID()) && ($product->is_type("simple") || has_term('coaching', 'product_tag'))) {
          ?>
          <div class="price-single"><?=$product->get_price_html();?></div>
          <?php
        }
      }
    }
    public function add_diet_program_to_cart() {
      // Check if checkbox is checked
      if (isset($_POST['include_diet_program']) && !empty($_POST['include_diet_program'])) {
        // Get product ID of the specified product
        $cart_contents = WC()->cart->get_cart();
        $product_in_cart = false;
        foreach ($cart_contents as $key => $item) {
          if ($item['product_id'] == $this->diet_id) {
            $product_in_cart = true;
            break;
          }
        }
        // If product is not in the cart, add it
        if (!$product_in_cart) {
          // Add product to cart with quantity 1
          WC()->cart->add_to_cart($this->diet_id, 1);
          // WC()->cart->add_to_cart($this->diet_id, 1, 0, array(), array(
          //   'mn_price' => $product->get_price("edit"),
          //   'line_total' => $product->get_price("edit"),
          //   'line_subtotal' => $product->get_price("edit"),
          // ));
        }
      }

      if (isset($_POST['include_mammography_program']) && !empty($_POST['include_mammography_program'])) {
        // Get product ID of the specified product
        $cart_contents = WC()->cart->get_cart();
        $product_in_cart = false;
        foreach ($cart_contents as $key => $item) {
          if ($item['product_id'] == $this->pregnant_id) {
            $product_in_cart = true;
            break;
          }
        }
        // If product is not in the cart, add it
        if (!$product_in_cart) {
          // Add product to cart with quantity 1
          WC()->cart->add_to_cart($this->pregnant_id, 1);
        }
      }
    }
    public function second_sms_check() {
      // date_default_timezone_set("Asia/Tehran");
      // $date = new \DateTime("2024-03-10 07:00");
      // $now = new \DateTime();
      // if($date <span $now /* || current_user_can("administrator") */) {
      add_action("woocommerce_order_status_processing", array($this, "send_second_sms"), 15);
      // }
    }
    public function admin_styles(){
      $screen = get_current_screen();
			$screen_id = $screen ? $screen->id : '';
      if (in_array( $screen_id, wc_get_screen_ids() ) ) {
				wp_enqueue_style("woocommerce_admin_styles");
        wp_add_inline_style("woocommerce_admin_styles", '@media screen and (max-width: 782px) {
          .post-type-shop_order .wp-list-table tr:not(.is-expanded) td.column-order_number a.order-view, .woocommerce_page_wc-orders .wc-orders-list-table.wp-list-table tr:not(.is-expanded) td.column-order_number a.order-view{ width: calc(100% - 2rem) !important; }
          .order_number.column-order_number.has-row-actions.column-primary { display: block; }
          /*.post-type-shop_order .wp-list-table td.column-order_number .order-preview,
          .woocommerce_page_wc-orders .wc-orders-list-table.wp-list-table td.column-order_number .order-preview { display: none; }*/
          .post-type-shop_order .wp-list-table tr:not(.is-expanded) td.column-order_date, .woocommerce_page_wc-orders .wc-orders-list-table.wp-list-table tr:not(.is-expanded) td.column-order_date { width: 16ch !important; }
        }');
			}
      ?>
      <style>:lang(en) .woocommerce-admin-page #postbox-container-2 { clear: left !important; }</style>
      <?php
    }
    public function render_column($column_id, $order_id){
      if ($column_id == "order_number") {
        echo '<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>';
      }
    }
    public function expire_past_due_subscriptions() {
      if (!function_exists("wcs_get_subscriptions")) return ;
      $subscriptions = wcs_get_subscriptions(array(
        "subscriptions_per_page" => -1,
        "customer_id" => get_current_user_id(),
        "subscription_status" => "active",
      ));
      if ($subscriptions && !empty($subscriptions)) {
        foreach ($subscriptions as $subscription) {
          $end_time = $subscription->get_time('end');
          if ($end_time && $end_time < time()) {
            $subscription->update_status('expired');
            $subscription->save();
          }
        }
      }
      if (current_user_can("manage_options") && is_admin() && isset($_GET["check_expired_subscriptions"]) && !empty($_GET["check_expired_subscriptions"])) {
        $check_expired_subscriptions = sanitize_text_field(trim($_GET["check_expired_subscriptions"]));
        $subscriptions = wcs_get_subscriptions(array(
          "subscriptions_per_page" => -1,
          "subscription_status" => "active",
        ));
        if ($subscriptions && !empty($subscriptions)) {
          $i = 0;
          foreach ($subscriptions as $subscription) {
            $end_time = $subscription->get_time('end');
            if ($end_time && $end_time < time()) {
              $i++;
              echo "<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>{$i} -- ". print_r([
                "order" => "<a href='".esc_attr($subscription->get_edit_order_url())."'>#{$subscription->get_order_number()}</a>",
                "customer" => $this->display_user($subscription->get_user_id(), 1, 1, 1),
                "expiry" => $end_time ? wp_date("Y/m/d H:i:s", $end_time) . " (" . date_i18n("Y/m/d H:i:s", $end_time) . ") " : "NOT SET!",
                "has_expired" => $end_time && $end_time < time() ? "❌ EXPIRED" : "✅ NOT YET" ,
              ],1) ."</pre>";
              $subscription->update_status('expired');
              $subscription->save();
            }

          }
        }
        if (empty($subscriptions) || $i == 0) {
          ?>
          <center dir="rtl">
            <br>
            <h2>همه چیز مرتب و میزونه 😍🎉</h2>
            سفارش منقضی شده ای که اکسپایر نشده باشه یافت نشد
            <br>
          </center>
          <?php
        }
        exit;
      }
    }
    public function init_plugin() {


      if (current_user_can("manage_options") && is_admin() && isset($_GET["WC_Email"]) && !empty($_GET["WC_Email"])) {
        $WC_Email = sanitize_text_field(trim($_GET["WC_Email"]));
        // wc_email_customer_processing_order
        $mailer = WC()->mailer()->get_emails();
        $mailer['WC_Email_Customer_Processing_Order']->trigger($WC_Email);
        die("sent email to {$WC_Email}");
      }

      add_filter("date_i18n", array($this, "jdate"), 10, 4);
      add_action("woocommerce_email_before_order_table", function($order, $sent_to_admin, $plain_text, $email){ add_filter("date_i18n", array($this, "jdate"), 10, 4); }, 10, 4);
      add_action("woocommerce_admin_order_data_after_payment_info", function($order){ remove_filter("date_i18n", array($this, "jdate"), 10); });
      add_action("woocommerce_admin_order_data_after_order_details", function($order){ add_filter("date_i18n", array($this, "jdate"), 10, 4); });

      add_filter("woocommerce_order_number", function( $id, $order ) { return str_replace("912719", "", $id); }, 10, 2);

      if (current_user_can("manage_options") && is_admin() && isset($_GET["run_cron"]) && !empty($_GET["run_cron"])) {
        $run_cron = sanitize_text_field(trim($_GET["run_cron"]));
        $this->process_email_sms_queue(false, "yes" != $run_cron && is_numeric($run_cron) ? $run_cron : false);
        wp_die("", "MNStudio Pro - Run CronJob");
      }

      if (is_blog_admin() && current_user_can("manage_options") && isset($_GET["setVirtual"]) && !empty($_GET["setVirtual"])) {
        $this->set_virtual_products_with_shipping_class_course();
        die("all done.");
      }

      if (current_user_can("manage_options") && is_admin() && isset($_GET["send_test_sms"]) && !empty($_GET["send_test_sms"])) {
        $order_id = sanitize_text_field(trim($_GET["send_test_sms"]));
        if ($order_id && !empty($order_id) && "ORDER_ID" != $order_id) {
          $response = "function PWooSMS/PWSMS does not exists.";
          if (function_exists("PWooSMS") || function_exists("PWSMS")) {
            $version = defined("PWSMS_VERSION") ? PWSMS_VERSION : "0.00";
            $sms = "Test sms for order {order_id} - PWSMSv.{$version} / مبلغ سفارش: {price} / وضعیت سفارش: {status}";
            $order = wc_get_order($order_id);
            if ($order) {
              $mobile = $order->get_billing_phone();
              $new_status = $order->get_status();
              if (function_exists("PWSMS")) {
                $message = PWSMS()->replace_short_codes($sms, $new_status, $order);
                $response = PWSMS()->send_sms(array("post_id" => $order_id, "message" => $message, "mobile" => $mobile));
              }
              elseif (function_exists("PWooSMS")) {
                $message = PWooSMS()->ReplaceShortCodes($sms, $new_status, $order);
                $response = PWooSMS()->SendSMS(array("post_id" => $order_id, "message" => $message, "mobile" => $mobile));
              }
            }
            else{
              $response = "could not initiate order.";
            }
          }
          wp_die( date_i18n("Y/m/d H:i:s", current_time("timestamp")) . " / SMS RESULT: " . var_export($response, 1), "MNStudio Test SMS");
        }
        else{
          wp_die("Enter Order ID to retrieve mobile number on send_test_sms url parameter.", "MNStudio Test SMS");
        }
      }

      if (current_user_can("manage_options") && is_admin() && isset($_GET["send_ippanel_test_sms"]) && !empty($_GET["send_ippanel_test_sms"])) {
        $sms_test_number = sanitize_text_field(trim($_GET["send_ippanel_test_sms"]));
        if ($sms_test_number && !empty($sms_test_number) && "MOBILE" != $sms_test_number) {
          $mobile  = $sms_test_number;
          $text = "A test Payment SMS from ".home_url()." and MNStudio Pugin v.{$this->version}, sent to {$mobile}, initiated by User: " . wp_strip_all_tags($this->display_user(FALSE, false, 1), true) . " at " . date_i18n("Y/m/d H:i:s", current_time("timestamp"));
          $res = $this->send_sms($mobile, $text);
          if (is_wp_error($res)) {
            $res = $res->get_error_message();
          }
          wp_die("SMS Res: " . var_export($res,1) . "<hr>$text", "MNStudio Test SMS");
        }else{
          wp_die("Enter mobile number on send_ippanel_test_sms url parameter.", "MNStudio Test SMS");
        }
      }

      if (current_user_can("manage_options") && is_admin() && isset($_GET["re_schedule_order"]) && !empty($_GET["re_schedule_order"])) {
        $order_id = sanitize_text_field(trim($_GET["re_schedule_order"]));
        if ($order_id && !empty($order_id) && "ORDER_ID" != $order_id) {
          $res = $this->create_notif_entry_from_order($order_id, true);
          wp_die("Done, res: " . $res, "MNStudio Re-Schedule");
        }else{
          wp_die("Enter Order ID as re_schedule_order url parameter.", "MNStudio Re-Schedule");
        }
      }

      if (current_user_can("manage_options") && is_blog_admin() && isset($_GET["get_order_by_pid"]) && !empty($_GET["get_order_by_pid"])) {
        $get_order_by_pid = sanitize_text_field(trim($_GET["get_order_by_pid"]));
        if ($get_order_by_pid && !empty($get_order_by_pid) && "PRODUCT_ID" != $get_order_by_pid) {
          $res = $this->get_orders_ids_by_product_id($get_order_by_pid, array_keys(wc_get_order_statuses()));
          wp_die("<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>". print_r($res,1) ."</pre>", "MNStudio Find Order");
        }else{
          wp_die("Enter Order ID as re_schedule_order url parameter.", "MNStudio Find Order");
        }
      }


      add_filter("wp_schema_pro_schema_video_object", function ($schema, $data, $post) {
        if (isset($schema['hasPart'])) { unset($schema['hasPart']); } return $schema;
      }, 999999, 3);

      add_action("woocommerce_before_add_to_cart_button", array($this, "add_custom_field_to_product"));
      add_filter("woocommerce_add_to_cart_validation", array($this, "wc_add_to_cart_validation"), 10, 4);
      add_filter("woocommerce_add_cart_item_data", array($this, "add_custom_field_data_to_cart"), 10, 2);
      add_filter("woocommerce_add_to_cart_validation", function($passed, $product_id, $quantity, $variation_id = 0) {
        $product = wc_get_product($variation_id ?: $product_id);
        $selected_session = isset($_POST['attribute_pa_sessions']) ? sanitize_text_field($_POST['attribute_pa_sessions']) : '';
        $beginner_checked = isset($_POST['beginner_term']) && $_POST['beginner_term'] === 'yes';

        if ($selected_session === "4day" && $beginner_checked && $this->is_new_ui_online($product_id)) {
          wc_add_notice("این کلاس برای کاربران مبتدی در دسترس نیست.", "error");
          return false;
        }

        return $passed;
      }, 10, 4);

      add_filter("woocommerce_add_cart_item_data", function ($cart_item_data, $product_id) {
        if (isset($_POST['beginner_term']) && $_POST['beginner_term'] === 'yes') {
          $cart_item_data['beginner_term'] = 'yes';
        }
        return $cart_item_data;
      }, 10, 2);
      add_filter("woocommerce_get_item_data", function ($item_data, $cart_item) {
        if (isset($cart_item['beginner_term']) && $cart_item['beginner_term'] === 'yes') {
          $item_data[] = array(
            'key'   => $this->text_online_term_level,
            'value' => $this->text_beginner,
          );
        }
        return $item_data;
      }, 10, 2);
      add_action("woocommerce_checkout_create_order_line_item", function ($item, $cart_item_key, $values, $order) {
        if (isset($values['beginner_term']) && $values['beginner_term'] === 'yes') {
          $item->add_meta_data($this->text_online_term_level, $this->text_beginner);
        }
      }, 10, 4);
      add_action("woocommerce_checkout_create_order_line_item", array($this, "add_custom_field_data_to_order"), 10, 4);
      add_filter("woocommerce_checkout_create_order", array($this, "wc_checkout_create_order"), 10, 2);
      add_filter("woocommerce_get_item_data", array($this, "wc_cart_get_item_data"), 10, 2);
      add_filter("woocommerce_hidden_order_itemmeta", array($this, "woocommerce_hidden_order_itemmeta"), 10, 2);
      add_filter("woocommerce_order_item_visible", array($this, "hide_bundle_items"), 10, 2);
      add_action("woocommerce_email_order_meta", array($this, "bundle_add_order_notes_to_admin_email"), 10, 3);

      add_filter("woocommerce_product_data_tabs", array($this, "wc_bundled_product_settings_tabs"));
      add_action("woocommerce_product_data_panels", array($this, "wc_bundled_product_panels"));
      add_action("woocommerce_process_product_meta", array($this, "wc_bundled_save_fields"));

      add_shortcode("get-comment", array($this, "get_comment"), 10, 2);
      add_shortcode("show-comment", array($this, "show_comment"), 10, 2);
      add_shortcode("mn_video", array($this, "shortcode_wrapper_mn_video"), 10, 2);

      add_shortcode("required_tools", array($this, "shortcode_wrapper_required_tools"), 10, 2);
		  add_filter('acf/load_field/name=required_tools', array($this, 'load_product_and_taxonomies_into_acf'));

      add_filter("comment_post_redirect", array($this, "redirect_after_comment"));

      remove_all_actions("woodmart_woocommerce_main_loop");
      add_action("woodmart_woocommerce_main_loop", array($this, "woodmart_woocommerce_main_loop"), 9999);

      // Change Limit on Number of Variations for Dynamic Variable Product Dropdowns
      // https://woocommerce.com/document/change-limit-on-number-of-variations-for-dynamic-variable-product-dropdowns/
      add_filter("woocommerce_ajax_variation_threshold", function ($qty, $product) { return 100; }, 9999, 2);

      // add_action("woocommerce_product_query", array($this, "hide_products_subcategory"), 9999 );

      add_action("template_redirect", array($this, "template_redirect_dollar"), 1);
      add_action("admin_init", array($this, "admin_init"), 1);
      add_filter("password_change_email", array($this, "custom_admin_password_change_email"), 10, 3);
      add_action("wp_enqueue_scripts", array($this, "add_extra_class_front"));
      add_action("admin_enqueue_scripts", array($this, "add_extra_class_admin"));
      add_filter("woocommerce_format_postcode", array($this, "digits_to_english"), 10, 2);
      add_filter("woocommerce_checkout_posted_data", array($this, "force_checkout_digits_to_english"));

      add_shortcode("current_currency", array($this, "show_current_currency"), 10, 2);
      add_shortcode("other_currency", array($this, "show_other_currency"), 10, 2);
      add_shortcode("if_irr", array($this, "show_if_currency_irr"), 10, 2);
      add_shortcode("if_usd", array($this, "show_if_currency_usd"), 10, 2);
      add_shortcode("user_ip", array($this, "get_real_IP_address"));
      add_shortcode("secure_video", array($this, "secured_video_shortcode"));
      add_filter("gettext_woocommerce", array($this, "gettext_translate_td_woocommerce"), 10, 3);
      add_filter("woocommerce_get_stock_html", array($this, "woocommerce_get_stock_html"), 8888, 2);

      // Code Generated using help of ChatGPT AI at 2023/08/28 16:57:48
      add_action("init", array($this, "enable_comments_on_pages"));
      add_filter("get_comment_link", function ($link, $comment, $args, $page) { return home_url(); }, 9999, 4);

      // change h3 to p
      if ($this->read("fix_h3_title_to_p") == "yes") {
        remove_action("woocommerce_shop_loop_item_title", "woocommerce_template_loop_product_title", 10);
        add_action("woocommerce_shop_loop_item_title", array($this, "woocommerce_template_loop_product_title"), 10);
      }
      add_action("comment_form_defaults", function ($defaults) { $defaults['title_reply_before'] = '<p id="reply-title" class="comment-reply-title">'; $defaults['title_reply_after']  = '</p>'; return $defaults; }, 10);


      if ($this->remove_wp_lazy_load) {
        add_filter('wp_lazy_loading_enabled', '__return_false');
        add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment = null) {
          $attr['loading'] = 'eager';
          return $attr;
        });
      }
      if ($this->add_font_to_wc_email) {
        add_filter("woocommerce_mail_content", array($this, "woocommerce_mail_content_callback_IRANSansWeb"), 9999);
      }
      if ($this->disable_gutenberg) {
        add_filter("use_block_editor_for_post", "__return_false", 10);
        add_filter("use_block_editor_for_post_type", "__return_false", 10);
      }
      if ($this->different_course_checkout) {
        // add_filter("woocommerce_enable_order_notes_field"      , "__return_false", 9999 );
        add_filter("woocommerce_customer_get_billing_country", function () {
          return "IR";
        });
        add_filter("woocommerce_customer_get_shipping_country", function () {
          return "IR";
        });
        add_filter("woocommerce_ship_to_different_address_checked", "__return_false");
        add_filter("woocommerce_checkout_fields", array($this, "filter_checkout_fields"), 10);
        add_filter("woocommerce_checkout_posted_data", array($this, "force_ship_to_different_address"));
        add_filter("woocommerce_cart_needs_shipping", array($this, "cart_needs_shipping"), 10, 1);
        add_filter("woocommerce_cart_needs_shipping_address", array($this, "cart_needs_shipping"), 10, 1);
        add_filter("woocommerce_order_needs_shipping_address", array($this, "cart_needs_shipping"), 10, 1);
        add_filter("woocommerce_package_rates", array($this, "hide_shipping_condition_based"), 999999, 2);
        add_filter("woocommerce_billing_fields", array($this, "remove_country_billing_fields"));
        add_filter("woocommerce_available_payment_gateways", array($this, "filter_available_payment_gateways"));
      }
      if ($this->sms_form_for_outofstock) {
        add_action("woocommerce_single_product_summary", array($this, "out_of_stock_show_sms_notify_form"), 20);
        add_action("woocommerce_variable_product_summary", array($this, "out_of_stock_show_sms_notify_form"), 20);
      }
      if ($this->media_size_columns) {
        add_action("admin_enqueue_scripts", array($this, "enqueue_admin_script"));
        add_filter("manage_upload_columns", array($this, "add_column_file_size"));
        add_action("manage_media_custom_column", array($this, "column_file_size"), 10, 2);
      }
      if ($this->custom_thank_you_message) {
        add_shortcode("custom-thankyou", array($this, "get_order_custom_thankyou_html"), 10, 2);
        add_filter("pwoosms_shortcodes_list", array($this, "pwoosms_shortcodes_list"));
        add_filter("pwoosms_order_sms_body_after_replace", array($this, "sms_body_after_replace"), 10, 5);
        add_action("woocommerce_process_product_meta", array($this, "save_product_custom_tab"));
        add_action("woocommerce_order_details_before_order_table", array($this, "show_bought_course_details"), 10, 1);
        add_action("woocommerce_order_details_before_order_table", array($this, "show_paypal_custom_text"), -1001, 1);
        add_action("woocommerce_product_options_general_product_data", array($this, "printout_options_content"));
        add_action("woocommerce_email_order_details", array($this, "woocommerce_email_add_custom_thankyou"), 5, 4);
      }
      if ($this->remove_recurring_totals) {
        add_filter("woocommerce_cart_calculate_fees", array($this, "add_recurring_postage_fees"), 10, 1);
      }

      if (!is_blog_admin() || !is_admin()) {
        add_filter("woocommerce_get_price_html", array($this, "hide_out_of_stock_price"), 10, 2);
        add_filter("woocommerce_is_purchasable", array($this, "wc_bundle_is_purchasable"), 10, 2);
        add_filter("woocommerce_product_is_in_stock", array($this, "wc_bundle_is_purchasable"), 10, 2);
      }

      add_filter("woocommerce_email_enabled_customer_on_hold_order", "__return_false");

      if ($this->dollar_currency && (!is_admin() || defined('DOING_AJAX') && function_exists("is_ajax") && is_ajax())) {
        add_filter("woocommerce_email_enabled_customer_on_hold_order", "__return_true");
        add_filter("woocommerce_currency", array($this, "woocommerce_currency"));
        add_filter("woocommerce_currency_symbol", array($this, "woocommerce_currency_symbol"));
        add_filter("wc_get_price_decimals", array($this, "wc_get_price_decimals"));
        add_filter("woocommerce_price_format", array($this, "woocommerce_price_format"));
        add_filter("woocommerce_get_price_html", array($this, "woocommerce_variable_price_html"), 2, 2);
        add_filter("woocommerce_is_purchasable", array($this, "woocommerce_is_purchasable"), 1, 2);
        add_action("woocommerce_cart_item_price", array($this, "modify_cart_item_unit_price"), 10, 3);
        add_action("woocommerce_cart_item_subtotal", array($this, "modify_cart_item_unit_price"), 10, 3);
        add_action("woocommerce_before_calculate_totals", array($this, "woocommerce_before_calculate_totals"));
        add_action("woocommerce_checkout_create_order_line_item", array($this, "checkout_create_order_line_item"), 10, 4);
        add_filter("woocommerce_coupon_get_amount", array($this, "woocommerce_coupon_get_amount"), 88, 2);
      }
      if ($this->dollar_currency) {
        add_action("woocommerce_before_checkout_form", array($this, "notice_for_usd_payment_not_allowed"));
        add_action("woocommerce_coupon_options", array($this, "woocommerce_coupon_add_dollar_price"), 10, 2);
        add_action("woocommerce_update_coupon", array($this, "woocommerce_coupon_save_dollar_price"), 10, 1);
        add_action("woocommerce_process_product_meta", array($this, "save_dollar_price_for_simple"));
        add_action("woocommerce_product_options_general_product_data", array($this, "printout_dollar_price_for_simple"));
        add_action("woocommerce_variation_options", array($this, "printout_dollar_price_for_variation"), 90, 3);
        add_action("woocommerce_save_product_variation", array($this, "save_dollar_price_for_variation"), 10, 2);
        add_action("woocommerce_variation_header", array($this, "display_dollar_price_on_variation_header"));
        add_action("woocommerce_review_order_before_payment", array($this, "refresh_checkout_on_payment_methods_change"));
      }

      add_action("woocommerce_review_order_before_payment", array($this, "refresh_checkout_on_province_change"));

      if (current_user_can("manage_options") && is_blog_admin() && isset($_GET["enrol_pid_to_ld"]) && !empty($_GET["enrol_pid_to_ld"])) {
        define('WP_DEBUG', true);
        define('WP_DEBUG_LOG', true);
        define('WP_DEBUG_DISPLAY', true);
        @ini_set('display_errors', 1);
        error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
        echo "<title>Pigment.Dev</title><style> table { width: calc(100% - 2rem); margin: 1rem; text-align: center; border-collapse: collapse; } table th{ padding: 0.7rem; border: 1px solid #bbb; background: #dcd; } table td{ padding: 0.35rem; border: 1px solid #ccc; }div,p,tr{ box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px, rgba(0, 0, 0, 0.3) 0px 1px 3px -1px; padding: 1rem; margin: 2rem 1rem; background: #fff; } body { background: #eee; }</style>";

        // https://minanaseh.com/wp-admin/?enrol_pid_to_ld=1&course_id=9127186461&product_id=9127190916&remove=1

        $course_id  = false;  //15271;
        $product_id = false;  //12100;
        $remove     = false;

        if (isset($_GET["course_id"]) && !empty($_GET["course_id"])) {
          $course_id = sanitize_text_field(trim($_GET["course_id"]));
        }
        if (isset($_GET["product_id"]) && !empty($_GET["product_id"])) {
          $product_id = sanitize_text_field(trim($_GET["product_id"]));
        }
        if (isset($_GET["remove"]) && !empty($_GET["remove"])) {
          $remove = true;
        }

        if (!get_post($course_id)) {
          die("<p>course_id invalid</p>");
        }
        if (!get_post($product_id)) {
          die("<p>product_id invalid</p>");
        }

        $course_title = get_the_title($course_id);
        $product_title = get_the_title($product_id);
        echo "<div><h1>" . ($remove ? "<span style='color: red;'>Removing Access </span>from" : "<span style='color: green;'>Granting Access </span>to") . " Users</h1>Product: <a href='" . admin_url("post.php?post=$product_id&action=edit") . "'>#$product_id</a> $product_title <br>Course: <a href='" . admin_url("post.php?post=$course_id&action=edit") . "'>#$course_id</a> \"$course_title\"</div>";
        $statuses   = array("wc-completed",);
        $orders     = $this->get_orders_ids_by_product_id($product_id, $statuses);
        $i = 0;
        $total = count($orders);
        echo "<table><thead>
        <tr><th>#</th>
        <th>WC.Order</th>
        <th>Customer</th>
        <th>LD.Course</th>
        <th>Prev.Status</th>
        <th>Current.Status</th>
        <th>Result</th></tr>
        </thead><tbody>";
        echo "<tr><td colspan='7' style='text-align: center;'>" . date_i18n("Y/m/d H:i:s", current_time("timestamp")) . " -- start of processing /.</td></td>";
        foreach ($orders as $order_id) {
          $i++;
          $order = wc_get_order($order_id);
          if (!$order) continue;
          $user_id = $order->get_customer_id();
          if (!$user_id || $user_id < 1) {
            continue;
          }
          $enrolled_courses = learndash_user_get_enrolled_courses($user_id);
          $joined = in_array($course_id, $enrolled_courses);
          echo "<tr><td>$i / $total</td>";
          echo "<td><a href='" . admin_url("post.php?post=$order_id&action=edit") . "'>#$order_id</a></td>";
          echo "<td>" . $this->display_user($user_id, 1, 1) . "</td>";
          echo "<td><a href='" . admin_url("post.php?post={$course_id}&action=edit&currentTab=sfwd-courses-settings") . "'>" . get_the_title($course_id) . "</a></td>";
          echo ($joined ? "<td style='color: green;'>Yes</td>" : "<td style='color: red;'>No</td>");
          $success = ld_update_course_access($user_id, $course_id, $remove);
          $enrolled_courses = learndash_user_get_enrolled_courses($user_id);
          echo in_array($course_id, $enrolled_courses) ? "<td style='color: green;'>ACCESS GRANTED</td>" : "<td style='color:red;'>ACCESS REMOVED</td>";
          echo ($success ? "<td style='color: green;'>course access updated successfully</td>" : "<td style='color:red;'>failed to update course access</td>");
        }
        echo "<tr><td colspan='7' style='text-align: center;'>" . date_i18n("Y/m/d H:i:s", current_time("timestamp")) . " -- end of processing /.</td></td>";
        echo "</tbody></table>";
        exit;
      }

      add_action("woocommerce_before_thankyou", array($this, "remove_usd_currency"));

      // zarinpal log system
      add_action("woocommerce_before_thankyou", function ($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
          $this->create_notif_entry_from_order($order_id);
        }
        if ($order && $this->debug) $order->add_order_note("<b>GatewayLOG:</b> User is at ThankYou page, Order Status: " . $order->get_status(), false, false);
      });
      add_action("WC_ZPal_Return_from_Gateway_No_Order_ID", function ($order_id, $dep, $Fault) {
        $order = wc_get_order($order_id);
        if ($order && $this->debug) $order->add_order_note("<b>GatewayLOG:</b> Return_from_Gateway_No_Order_ID -- order_id: $order_id -- fault: $Fault", false, false);
      }, 1, 3);
      add_action("woocommerce_receipt_WC_ZPal", function ($order_id) {
        $order = wc_get_order($order_id);
        if ($order && $this->debug) $order->add_order_note("<b>GatewayLOG:</b> Before Redirecting to PaymentURL", false, false);
      });
      add_action("WC_ZPal_Gateway_Payment", function ($order_id, $Description, $Mobile) {
        $order = wc_get_order($order_id);
        if ($order && $this->debug) $order->add_order_note("<b>GatewayLOG:</b> Before Requesting Bank URL", false, false);
      }, 1, 3);
      add_action("WC_ZPal_Send_to_Gateway_Failed", function ($order_id, $Fault, $debug) {
        $order = wc_get_order($order_id);
        if ($order && $this->debug) $order->add_order_note("<b>GatewayLOG:</b> Send to bank Failed: $Fault<hr><div dir='ltr' style='direction: ltr;'>" . var_export($debug, 1), false, false);
      }, 1, 3);
      add_action("WC_ZPal_Return_from_Gateway_Success", function ($order_id, $Transaction_ID) {
        $order = wc_get_order($order_id);
        if ($order && $this->debug) $order->add_order_note("<b>GatewayLOG:</b> Verify Purchase Success, Transaction_ID: $Transaction_ID", false, false);
      }, 1, 2);
      add_action("WC_ZPal_Return_from_Gateway_ReSuccess", function ($order_id, $Transaction_ID) {
        $order = wc_get_order($order_id);
        if ($order && $this->debug) $order->add_order_note("<b>GatewayLOG:</b> Return from bank Success, Transaction_ID: $Transaction_ID", false, false);
      }, 1, 2);


      add_action("woocommerce_order_details_before_order_table", array($this, "remove_usd_currency"));
      add_action("woocommerce_order_formatted_line_subtotal", function ($subtotal, $item, $that) {
        $this->remove_usd_currency();
        return $subtotal;
      }, 10, 3);



      add_filter("body_class", function ($classes) {
        if (function_exists('is_product') && is_product()) {
          global $post;
          $product = !is_a($post, "\WC_Product") ? wc_get_product($post) : $post;
          if ($product) {
            $product_type = $product->get_type();
            $classes[] = 'product-type-' . $product_type;
            $course = $product->is_type("variable-subscription") || $product->is_type("subscription");
            $classes[] = $course ? "product-is-course" : "product-is-physical";

            if ($this->is_new_ui_offline($product->get_id())) {
              $classes[] = "new_ui_offline product-course-offline";
            }
            if ($this->is_new_ui_online($product->get_id())) {
              $classes[] = "new_ui_online product-course-online";
            }
            if ($this->is_new_ui_private($product->get_id())) {
              $classes[] = "new_ui_private product-course-private";
            }

          }
        }
        $classes[] = is_user_logged_in() ? "cur-user-loggedin" : "cur-user-guest";
        $classes[] = $this->using_dollar() ? "wc-currency-usd" : "wc-currency-irt";
        $classes[] = "cur-country-" . (isset($_SERVER['GEOIP_COUNTRY_CODE']) ? $_SERVER['GEOIP_COUNTRY_CODE'] : "ukwn");
        $classes[] = $this->is_from_iran() ? "is-from-iran" : "not-from-iran";
        return $classes;
      });

      add_filter("woocommerce_get_formatted_order_total", function ($formatted_total, $that, $tax_display, $display_refunded) {
        $this->remove_usd_currency();
        $formatted_total = wc_price($that->get_total(), array('currency' => $that->get_currency()));
        $order_total     = $that->get_total();
        $total_refunded  = $that->get_total_refunded();
        $tax_string      = '';
        // Tax for inclusive prices.
        if (wc_tax_enabled() && 'incl' === $tax_display) {
          $tax_string_array = array();
          $tax_totals       = $that->get_tax_totals();
          if ('itemized' === get_option('woocommerce_tax_total_display')) {
            foreach ($tax_totals as $code => $tax) {
              $tax_amount         = ($total_refunded && $display_refunded) ? wc_price(\WC_Tax::round($tax->amount - $that->get_total_tax_refunded_by_rate_id($tax->rate_id)), array('currency' => $that->get_currency())) : $tax->formatted_amount;
              $tax_string_array[] = sprintf('%s %s', $tax_amount, $tax->label);
            }
          } elseif (!empty($tax_totals)) {
            $tax_amount         = ($total_refunded && $display_refunded) ? $that->get_total_tax() - $that->get_total_tax_refunded() : $that->get_total_tax();
            $tax_string_array[] = sprintf('%s %s', wc_price($tax_amount, array('currency' => $that->get_currency())), WC()->countries->tax_or_vat());
          }
          if (!empty($tax_string_array)) {
            /* translators: %s: taxes */
            $tax_string = ' <small class="includes_tax">' . sprintf(__('(includes %s)', 'woocommerce'), implode(', ', $tax_string_array)) . '</small>';
          }
        }
        if ($total_refunded && $display_refunded) {
          $formatted_total = '<del aria-hidden="true">' . wp_strip_all_tags($formatted_total) . '</del> <ins>' . wc_price($order_total - $total_refunded, array('currency' => $that->get_currency())) . $tax_string . '</ins>';
        } else {
          $formatted_total .= $tax_string;
        }
        return $formatted_total;
      }, 10, 4);

      add_filter("woocommerce_subscriptions_thank_you_message", "__return_empty_string", 10);
      remove_action("register_new_user", "wp_send_new_user_notifications");
      remove_action("edit_user_created_user", "wp_send_new_user_notifications", 10, 2);

      if ($this->notify_after_purchase) {
        add_action("woocommerce_process_product_meta", array($this, "save_scheduled_notification_for_simple"));
        add_action("woocommerce_save_product_variation", array($this, "save_scheduled_notification_for_variation"), 10, 2);
        add_action("woocommerce_product_options_general_product_data", array($this, "printout_scheduled_notification_for_simple"));
        add_action("woocommerce_variation_options", array($this, "printout_scheduled_notification_for_variation"), 10, 3);
        add_action("woocommerce_order_status_changed", array($this, "create_notif_entry_from_order"), 10, 1);
        add_action("woocommerce_order_status_processing", array($this, "create_notif_entry_from_order"), 10, 1);
        add_action("woocommerce_order_status_completed", array($this, "create_notif_entry_from_order"), 10, 1);
        add_action("woocommerce_new_order", array($this, "create_notif_entry_from_order"), 99999, 1);
      }
      if ($this->telegram_fab) {
        add_action("wp_head", array($this, "add_telegram_fab"));
      }
      if ($this->learn_dash_continue) {
        add_action("template_redirect", array($this, "template_redirect_ld"));
        add_action("wp_enqueue_scripts", array($this, "add_learn_dash_continue"));
      }

      add_action("manage_shop_order_posts_custom_column", array( $this, "render_column" ), 10, 2 );
      add_action("manage_woocommerce_page_wc-orders_custom_column", array( $this, "render_column" ), 10, 2 );
			add_action("admin_enqueue_scripts", array($this, "admin_styles"));

      // replace bundle product add to cart
      add_action("woocommerce_simple_add_to_cart", array($this, "wc_bundled_add_to_cart"));
      add_action("woocommerce_before_add_to_cart_form", function(){
        global $product; if (!$product) return;
        if ($this->different_course_checkout && $this->using_dollar() && (string) $product->get_shipping_class() != (string) $this->course_shipping_slug) echo "<style>form.cart { display: none; }</style>";
        if (!$product->is_purchasable() || !$product->is_in_stock()) echo "<style>form.cart { display: none; }</style>";
      });


      // Show out-of-stock UI for all product types (simple, variable, etc.)
      add_action('woocommerce_before_single_product', function() { global $product; if ($product && !$product->is_in_stock()) { $this->before_add_to_cart_button_newUI(true); } }, 99999);

      add_action("woocommerce_before_single_product", function(){
        global $product;
        if (!$product) $product = wc_get_product(get_the_ID());
        if ($this->using_dollar() && $this->different_course_checkout && $product && $product->get_shipping_class() != $this->course_shipping_slug) {
          $btn = "&nbsp;<a href='" . esc_url(add_query_arg("cs", "IRT")) . "' class='button alt'>" . __("تغییر واحد پولی به تومان", $this->td) . "</a>";
          wc_print_notice("این محصول تنها در ایران و با واحد پولی تومان قابل خریداری است. $btn", 'error');
        }
      });

      add_action("woocommerce_before_add_to_cart_button", function(){
        if ($this->is_new_ui_template(get_the_ID())) {
          $this->before_add_to_cart_button_newUI();
        }else{
          $this->add_include_diet_program_checkbox();
        }
      });


      add_shortcode('wc_price', function ($atts) {
        $atts = shortcode_atts(['id' => 0,], $atts);
        $product_id = intval($atts['id']);
        if (!$product_id) { return ''; }
        if (!class_exists('WC_Product')) { return ''; }
        $product = wc_get_product($product_id);
        if (!$product) { return ''; }
        // if product is variable, get the min price
        if ($product->is_type('variable') || $product->is_type('variable-subscription') && method_exists($product, 'get_variation_prices')) {
          $prices = $this->using_dollar() === true ? $this->get_variation_prices(false, $product) : $product->get_variation_prices(true);
          $min_price = current($prices['price']);
          return wc_price($min_price);
        }
        return $product->get_price_html();
      });

    }
    public function shortcode_wrapper_required_tools($atts=array(), $content=""){
      $atts = extract(shortcode_atts(array( "default" => "values", ),$atts));
      if (!function_exists("get_field")) return;
      ob_start();
      $tools = get_field("required_tools", get_the_ID());
      if ($tools && is_array($tools) && count($tools) > 0) {
        echo "<div class='required-tools-list' style='--gap: 16px;'>";
        ?>
        <style>
        .required-tools-list {
          display: flex;
          flex-wrap: wrap;
          gap: var(--gap);
        }

        .tools-card {
          width: calc(34% - var(--gap));
          background: #fff;
          border-radius: 6px;
          box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
          padding: 8px;
          text-align: center;
          min-width: 150px;
        }
        </style>
        <?php
        foreach ($tools as $tool) {
          $is_cat = strpos($tool, 'cat_') === 0;
          $id = (int) str_replace(array('cat_', 'prod_'), '', $tool);

          if ($is_cat) {
            $term = get_term($id, 'product_cat');
            if (!$term || is_wp_error($term)) continue;

            $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
            $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
            $products = wc_get_products(array(
              'status' => 'publish',
              'limit' => -1,
              'category' => array($term->slug),
              'return' => 'ids',
            ));
            $min_price = null;
            foreach ($products as $pid) {
              $p = wc_get_product($pid);
              if ($p && $p->is_purchasable() && $p->get_price() !== '') {
                $price = floatval($p->get_price());
                if ($min_price === null || $price < $min_price) {
                  $min_price = $price;
                }
              }
            }
            $price_html = $min_price !== null ? wc_price($min_price) : '';
            echo "<div class='tools-card'>";
            echo "<a href='" . esc_url(get_term_link($term)) . "' style='text-decoration: none; color: inherit;'>";
            echo "<img src='" . esc_url($image_url) . "' style='width: 100%; aspect-ratio: 1 / 1; object-fit: cover; border-radius: 4px;'>";
            echo "<div style='font-weight: bold; margin-top: 8px;'>" . esc_html($term->name) . "</div>";
            if ($price_html) echo "<div style='color: #888; font-size: 12px; margin-top: 4px;'>شروع از {$price_html}</div>";
            echo "</a></div>";
          } else {
            $product = wc_get_product($id);
            if (!$product) continue;
            if (!$product->is_purchasable()) continue;
            if (!$product->is_in_stock() && !$product->backorders_allowed()) continue;
            $image_url = wp_get_attachment_url($product->get_image_id()) ?: wc_placeholder_img_src();
            $price_html = $product->get_price_html();
            echo "<div class='tools-card'>";
            echo "<a href='" . esc_url(get_permalink($product->get_id())) . "' style='text-decoration: none; color: inherit;'>";
            echo "<img src='" . esc_url($image_url) . "' style='width: 100%; aspect-ratio: 1 / 1; object-fit: cover; border-radius: 4px;'>";
            echo "<div style='font-weight: bold; margin-top: 8px;'>" . esc_html($product->get_name()) . "</div>";
            if ($price_html) echo "<div style='color: #888; margin-top: 4px;'>{$price_html}</div>";
            echo "</a></div>";
          }
        }

        echo "</div>";
      }
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return $htmloutput;
    }
    public function load_product_and_taxonomies_into_acf($field){
      $categories = get_terms(array( 'taxonomy' => 'product_cat', 'hide_empty' => false, ));
      $products = wc_get_products(array( 'status' => 'publish', 'limit'  => -1, 'return' => 'ids', ));
      $field['choices'] = [];
      // Add category choices with prefix "cat_"
      foreach ($categories as $cat) {
        $field['choices']["cat_" . $cat->term_id] = "📂 " . $cat->name;
      }
      // Add product choices with prefix "prod_"
      foreach ($products as $pid) {
        $field['choices']["prod_" . $pid] = "📦 " . get_the_title($pid);
      }
      return $field;
    }
    public function jdate($date, $format, $timestamp, $gmt){
      return pu_jdate($format, $timestamp, "", "local");
    }
    private function update_footer_info() {
      add_filter("update_footer", function () {
        return sprintf(_x("%s — Version %s", "footer-copyright", $this->td), $this->title, $this->version);
      }, 999999999);
    }
    public function setting_wrapper(){
      $this->update_footer_info();
      wp_enqueue_script("wp-color-picker"); wp_enqueue_style('wp-color-picker');
      wp_enqueue_style("my_font_awesome", "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css", array(), current_time( 'timestamp' ));
      wp_enqueue_script($this->db_slug, "{$this->assets_url}js/jquery.repeater.min.js", ["jquery"], "1.2.1");
      wp_enqueue_style("{$this->db_slug}", "{$this->assets_url}css/backend.css");
      is_rtl() and wp_add_inline_style($this->db_slug, "#wpbody-content { font-family: bodyfont, roboto, Tahoma; }");
      ?>
      <div class="wrap">
        <h1 class='had'><span class='fas fa-cogs'></span>&nbsp;<strong style="font-weight: 700;"><?=__("MNStudio Configuration", $this->td);?></strong></h1>
        <?php
        if (isset($_REQUEST["settings-updated"]) && $_REQUEST["settings-updated"] == "true") {
          echo '<div id="message" class="updated notice is-dismissible"><p>' . _x("Settings saved successfully.", "setting-general", $this->td) . "</p></div>";
        }
        ?>
        <form method="post" action="options.php">
          <?php settings_fields("{$this->db_slug}__general"); ?>
          <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
            <a href="#" data-tab="tab_general" class="nav-tab nav-tab-active"><?= __("General", $this->td); ?></a>
            <a href="#" data-tab="tab_translate" class="nav-tab"><?= __("Translate", $this->td); ?></a>
            <a href="#" data-tab="tab_str_replace" class="nav-tab"><?= __("String Replace", $this->td); ?></a>
            <a href="#" data-tab="tab_modules" class="nav-tab"><?= __("Modules", $this->td); ?></a>
            <a href="<?=admin_url("admin.php?page=mnstudio_cronjob");?>" class="nav-tab"><?= __("CronJob", $this->td); ?> &nbsp;<i class="fas fa-fw fa-sm fa-solid fa-external-link"></i></a>
            <a href="<?=admin_url("admin.php?page=mnstudio_stock_log");?>" class="nav-tab"><?= __("Stock History", $this->td); ?> &nbsp;<i class="fas fa-fw fa-sm fa-solid fa-external-link"></i></a>
          </nav>

          <div class="tab-content tab-active" data-tab="tab_general">
            <br>
            <table class="form-table wp-list-table widefat striped table-view-list posts">
              <thead>
                <?php
                echo "<tr class='sync_site_tr border-top'>
                <th><h2 style='display: inline-block;'>" . __("General Configuration", $this->td) . "</h2></th>
                <td>:: <a target='_blank' href='".admin_url("?send_test_sms=ORDER_ID")."'>Send Test SMS using PW_SMS</a></td>
                </tr>";
                ?>
              </thead>
              <tbody>
                <?php
                  $this->print_setting_checkbox(["slug" => "debug", "caption" => __("Active Debug Mode", $this->td)]);
                  $this->print_setting_checkbox(["slug" => "currency_based_cache", "caption" => __("Currency-based Cache", $this->td)]);
                  $this->print_setting_input(["slug" => "favicon", "caption" => __("Favicon", $this->td),]);
                  $this->print_setting_input(["slug" => "diet_label", "caption" => __("Diet Addon Label", $this->td),]);
                  $this->print_setting_input(["slug" => "diet_id", "caption" => __("Diet Product ID", $this->td), "desc" => (!empty($this->diet_id) ? get_the_title($this->diet_id) : ""),]);
                  $this->print_setting_input(["slug" => "pregnant_label", "caption" => __("Pregnancy Addon Label", $this->td),]);
                  $this->print_setting_input(["slug" => "pregnant_id", "caption" => __("Pregnancy Product ID", $this->td), "desc" => (!empty($this->pregnant_id) ? get_the_title($this->pregnant_id) : ""),]);
                  $this->print_setting_input(["slug" => "secure_video_base", "caption" => __("Secure-link Base", $this->td),]);
                  $this->print_setting_input(["slug" => "secure_link_secret", "caption" => __("Secure-link Secret", $this->td),]);
                  $this->print_setting_input(["slug" => "telegram_url", "caption" => __("Telegram URL", $this->td),]);
                  $this->print_setting_input(["slug" => "telegram_title", "caption" => __("Telegram Title", $this->td),]);
                  $this->print_setting_input(["slug" => "paypal_gateway_slug", "caption" => __("Paypal WC-Gateway", $this->td), "desc" => "bacs | ppcp | ppcp-gateway",]);
                  $this->print_setting_input(["slug" => "rest_pass_subject", "caption" => __("Rest-pass Email Subject", $this->td), "desc" => __("Leave empty to use default",$this->td)]);
                  $this->print_setting_textarea(["slug" => "rest_pass_body", "caption" => __("Rest-pass Email Body", $this->td), "desc" => __("Leave empty to use default, use following macros: ",$this->td) . implode(" | ", [
                    "###USERNAME###",
                    "###ADMIN_EMAIL###",
                    "###EMAIL###",
                    "###SITENAME###",
                    "###SITEURL###",
                    "###USER_EMAIL###",
                    "###DISPLAY_NAME###",
                    "###FIRST_NAME###",
                    "###LAST_NAME###",
                  ]),]);
                  $this->print_setting_input([ "type" => "number", "slug" => "sms_queue_interval", "caption" => __("SMS Queue Interval", $this->td), "desc" => __("Set Queue Interval in Minutes to check unsent Email/SMS from list and send x-items per interval",$this->td),]);
                  $this->print_setting_input([ "type" => "number", "slug" => "sms_queue_items", "caption" => __("SMS Queue Items per Interval", $this->td), "desc" => __("How many unsent Email/SMS should be sent in single Interval cycle.<br>It is recomonded to send less than 6 items per minutes",$this->td),]);
                  $this->print_setting_input([ "type" => "number", "slug" => "discount", "caption" => __("Global Discount", $this->td), "desc"    => __("Add a globally discount applied on all products (within category below), leave empty to disable.", $this->td),]);
                  $this->print_setting_input([ "slug" => "discount_cat", "caption" => __("Global Discount Category", $this->td), "desc" => __("Enter category slug to apply global discount on it. Use comma to separate multiple items.",$this->td),]);
                ?>
              </tbody>
            </table>
            <br>
            <table class="form-table wp-list-table widefat striped table-view-list posts">
              <thead>
                <?php
                echo "<tr class='sync_site_tr border-top'>
                <th><h2 style='display: inline-block;'>" . __("IPPanel Configuration", $this->td) . "</h2></th>
                <td>:: <a target='_blank' href='".admin_url("?send_ippanel_test_sms=MOBILE")."'>Send Test SMS using Built-in IPPanel ServiceWorker</a></td>
                </tr>";
                ?>
              </thead>
              <tbody>
                <?php
                  $this->print_setting_input([
                    "slug" => "server_url",
                    "caption" => __("Server", $this->td),
                    "desc" => "ippanel.com / sms.farazsms.com",
                    "extra_html" => "dir=ltr lang=en_US",
                  ]);
                  $this->print_setting_input([
                    "slug" => "username_api",
                    "caption" => __("Username", $this->td),
                    "extra_html" => "dir=ltr lang=en_US",
                  ]);
                  $this->print_setting_input([
                    "slug" => "password_secret",
                    "caption" => __("Password", $this->td),
                    "extra_html" => "dir=ltr lang=en_US",
                  ]);
                  $this->print_setting_input([
                    "slug" => "sender_number",
                    "caption" => __("Sender number", $this->td),
                    "desc" => "3000505",
                    "extra_html" => "dir=ltr lang=en_US",
                  ]);

                ?>
              </tbody>
            </table>
          </div>

          <div class="tab-content" data-tab="tab_modules">
            <style> .form-table th { padding: 20px 10px 20px 10px; } .form-table th {width: auto;}</style>
            <br>
            <table class="form-table wp-list-table widefat striped table-view-list posts">
              <thead>
                <?php
                echo "<tr class='sync_site_tr border-top'><th colspan=2>
                <h2 style='display: inline-block;'>" . __("Modules Configuration", $this->td) . "</h2></th></tr>";
                ?>
              </thead>
              <tbody>
                <tr><th>Sub. Expiry</th><td><a target="_blank" href="<?=admin_url("?check_expired_subscriptions=1");?>">Check Expired Subscriptions</a></td></tr>
                <tr><th>Set Virtual</th><td><a target="_blank" href="<?=admin_url("?setVirtual=yes");?>">Set All Products with 'Course' Shipping Slug as Virtual</a></td></tr>
                <tr><th>Re-Schedule Order</th><td><a target="_blank" href="<?=admin_url("?re_schedule_order=ORDER_ID");?>">Re-Schedule Order Notifications</a></td></tr>
                <tr><th>Get Product Orders</th><td><a target="_blank" href="<?=admin_url("?get_order_by_pid=PRODUCT_ID");?>">Find ALl Orders with Specific Product</a></td></tr>
                <tr><th>LearnDash Enroll Pid</th><td><a target="_blank" href="<?=admin_url("?enrol_pid_to_ld=1&course_id=COURSE_ID&product_id=PRODUCT_ID&remove=1");?>">Find ALl Orders with Specific Product and Enroll Customer to Attached Course</a></td></tr>
                <tr><th>LearnDash UnEnroll Pid</th><td><a target="_blank" href="<?=admin_url("?enrol_pid_to_ld=1&course_id=COURSE_ID&product_id=PRODUCT_ID&remove=1");?>">Find ALl Orders with Specific Product and UnEnroll Customer to Attached Course</a></td></tr>
                <tr><th>Custom ThankYou Message per Product</th><td  style="direction: ltr;"><code>[custom-thankyou order_id=6464]</code> (remove 'order_id' to automatically get from current order)</td></tr>
                <tr><th>Currency Switch</th><td  style="direction: ltr;"><code>/?currency_switch</code> or <code>/?cs</code><br><code>[current_currency]</code> / <code>[other_currency]</code><br><code>[if_irr]Content for IRR currency[/if_irr]</code><br><code>[if_usd]Content for USD currency[/if_usd]</code></td></tr>
                <tr><th>Added Extra Classes</th><td><code>hide-for-guest</code> & <code>hide-for-loggedin</code></td></tr>
                <tr><th>Continue LearnDash Course</th><td>(<code>/?continue_learning</code>)</td></tr>
                <tr><th>nGinx Secure Link and Video</th><td style="direction: ltr;"><code>[secure_video file='video.mp4']</code></td></tr>
                <tr><th>Show Current Detected IP</th><td style="direction: ltr;"><code>[user_ip]</code></td></tr>
                <tr><th>Required Host Cron Job Command</th><td style="direction: ltr;">FRT: <code>define('DISABLE_WP_CRON', true);</code><br>NXT: <code>*\/2 * * * * wget --delete-after <?=home_url("/wp-cron.php?doing_wp_cron");?> >/dev/null 2>&1</code><br>OR: <code>/usr/local/bin/php /home/public_html/wp-cron.php?doing_wp_cron >/dev/null 2>&1</code></td></tr>
                <tr><th>Notify Email/SMS After Purchase</th><td>(Simple/Variable/Subscription products)</td></tr>
                <tr><th>Different Checkout Fields for Courses</th><td>YES</td></tr>
                <tr><th>Dollar-Currency Addition</th><td>YES</td></tr>
                <tr><th>Media Size Columns</th><td>YES</td></tr>
                <tr><th>Telegram FAB</th><td>YES</td></tr>
                <tr><th>Fix WooCommerce Shipping Admin-page Corrupted</th><td>YES</td></tr>
                <tr><th>Outofstock SMS Notify form</th><td>YES</td></tr>
                <tr><th>Remove Recurring totals</th><td>YES</td></tr>
                <tr><th>Add Font to WC Email</th><td>YES</td></tr>
                <tr><th>Remove WP Lazy Load</th><td>YES</td></tr>
                <tr><th>Remove Prefixed Order Number (912719)</th><td>YES</td></tr>
                <tr><th>Host-LSCache</th><td>
<pre style='text-align: left; direction: ltr; border:1px solid #eee; overflow: auto;'>
RewriteCond %{REQUEST_URI} !(\/wp-admin\/|\/xmlrpc.php|\/wp-(app|cron|login|register|mail)\.php|wp-.*\.php) [NC]
RewriteCond %{HTTP_COOKIE} !(comment_author|wp-postpass|wordpress_\[a-f0-9\]\+|wordpress_logged_in) [NC]
RewriteCond %{REQUEST_URI} !\.(jpg|gif|jpeg|png|css|ico|js|swf|zip|rar|mp3|exe|ttf|woff|tff) [NC]
RewriteRule ^$ - [L,E=Cache-Control:max-age=240]

RewriteCond %{REQUEST_URI} !(\/wp-admin\/|\/xmlrpc.php|\/wp-(app|cron|login|register|mail)\.php|wp-.*\.php) [NC]
RewriteCond %{HTTP_COOKIE} !(comment_author|wp-postpass|wordpress_\[a-f0-9\]\+|wordpress_logged_in) [NC]
RewriteCond %{REQUEST_URI} !\.(jpg|gif|jpeg|png|css|ico|js|swf|zip|rar|mp3|exe|ttf|woff|tff) [NC]
RewriteRule ^/$ - [L,E=Cache-Control:max-age=240]

RewriteCond %{REQUEST_URI} !(\/wp-admin\/|\/xmlrpc.php|\/wp-(app|cron|login|register|mail)\.php|wp-.*\.php) [NC]
RewriteCond %{HTTP_COOKIE} !(comment_author|wp-postpass|wordpress_\[a-f0-9\]\+|wordpress_logged_in) [NC]
RewriteCond %{REQUEST_URI} !\.(jpg|gif|jpeg|png|css|ico|js|swf|zip|rar|mp3|exe|ttf|woff|tff) [NC]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !(order|checkout|cart)
RewriteRule . /index.php [L,E=Cache-Control:max-age=240]
</pre>
                </td></tr>
                <tr><th>Currency-based RocketCache</th><td>
                <pre style='text-align: left; direction: ltr; border:1px solid #eee; overflow: auto;'>
&lt;IfModule mod_headers.c&gt;
Header append Vary: Accept-Encoding
&lt;/IfModule&gt;

# Set no-cache, no-store, must-revalidate headers to HTML files
&lt;FilesMatch &quot;\.(html|html_gzip|php)$&quot;&gt;
&lt;IfModule mod_headers.c&gt;
Header set Cache-Control &quot;no-cache, no-store, must-revalidate&quot;
&lt;/IfModule&gt;
&lt;/FilesMatch&gt;
</pre>
                </td></tr>
                <tr><th>CPanel PHP via htaccess</th><td>
                <pre style='text-align: left; direction: ltr; border:1px solid #eee; overflow: auto;'>
&lt;IfModule php7_module&gt;
   php_flag display_errors Off
   php_value max_execution_time 120
   php_value max_input_time 900
   php_value max_input_vars 10000
   php_value memory_limit 20480M
   php_value post_max_size 20480M
   php_value session.gc_maxlifetime 1440
   php_value session.save_path &quot;/var/cpanel/php/sessions/ea-php74&quot;
   php_value upload_max_filesize 512M
   php_flag zlib.output_compression On
&lt;/IfModule&gt;
&lt;IfModule lsapi_module&gt;
   php_flag display_errors Off
   php_value max_execution_time 120
   php_value max_input_time 900
   php_value max_input_vars 10000
   php_value memory_limit 20480M
   php_value post_max_size 20480M
   php_value session.gc_maxlifetime 1440
   php_value session.save_path &quot;/var/cpanel/php/sessions/ea-php74&quot;
   php_value upload_max_filesize 512M
   php_flag zlib.output_compression On
&lt;/IfModule&gt;
</pre>
                </td></tr>
            </tbody>
            </table>
          </div>

          <div class="tab-content" data-tab="tab_translate">
            <br>
            <div class="desc repeater translation-panel">
              <table class="wp-list-table widefat striped table-view-list posts">
                <thead>
                  <tr>
                    <th class="th-original"><?=__("Original", $this->td);?></th>
                    <th class="th-translate"><?=__("Translate", $this->td);?></th>
                    <th class="th-text-domain"><?=__("TextDomain", $this->td);?></th>
                    <th class="th-options"><?=__("Options", $this->td);?></th>
                    <th class="th-action" style="width: 100px;"><?=__("Action", $this->td);?></th>
                  </tr>
                </thead>
                <tbody data-repeater-list="gettext">
                  <tr data-repeater-item style="display:none;">
                    <td class="th-original"><span class="dashicons dashicons-menu-alt move-handle"></span>&nbsp;<input type="text" data-slug="original" name="original" value="" placeholder="<?=__("Original text ...", $this->td);?>"/></td>
                    <td class="th-translate"><input type="text" data-slug="translate" name="translate" placeholder="<?=__("Translate to ...", $this->td);?>" /></td>
                    <td class="th-text-domain"><input type="text" data-slug="text_domain" name="text_domain" placeholder="<?=__("Text Domain (Optional)", $this->td);?>" /></td>
                    <td class="th-options">
                      <label><input type="checkbox" value="yes" data-slug="use_replace" name="use_replace"/>&nbsp;<?=__("Partial Replace?", $this->td);?></label>&nbsp;&nbsp;
                      <!-- <label><input type="checkbox" value="yes" data-slug="use_regex" name="use_regex"/>&nbsp;<?=__("Use RegEx?", $this->td);?></label>&nbsp;&nbsp; -->
                      <label><input type="checkbox" value="yes" data-slug="two_sided" name="two_sided"/>&nbsp;<?=__("Translated Origin?", $this->td);?></label>
                    </td>
                    <td class="th-action">
                      <a class="button button-secondary" data-repeater-delete><span style="margin: 4px 0;" class="dashicons dashicons-trash"></span><?=__("Delete Row", $this->td);?></a>
                    </td>
                  </tr>
                </tbody>
              </table>
              <br>
              <a data-repeater-create class="button button-secondary button-hero"><span style="margin: 12px 8px;" class="dashicons dashicons-insert"></span><?=__("Add New Row", $this->td);?></a>&nbsp;&nbsp;
            </div>
            <br><br>
            <table class="form-table wp-list-table widefat striped table-view-list posts">
              <thead>
                <?php
                  echo "<tr class='sync_site_tr border-top'><th colspan=2><strong style='display: inline-block;'>" . __("Migrate Translation", $this->td) . "</strong></th></tr>";
                ?>
              </thead>
              <tbody>
                <?php
                  $this->print_setting_textarea(["slug" => "gettext_replace", "caption" => __("Translation Data", $this->td), "style" => "width: 100%; direction: ltr; min-height: 300px; font-family: monospace; font-size: 0.8rem;",]);
                ?>
              </tbody>
            </table>
          </div>

          <div class="tab-content" data-tab="tab_str_replace">
            <br>
            <div class="desc repeater str_replace-panel">
              <table class="wp-list-table widefat striped table-view-list posts">
                <thead>
                  <tr>
                    <th class="th-original"><?=__("Original", $this->td);?></th>
                    <th class="th-translate"><?=__("Replace", $this->td);?></th>
                    <th class="th-options"><?=__("Options", $this->td);?></th>
                    <th class="th-action" style="width: 100px;"><?=__("Action", $this->td);?></th>
                  </tr>
                </thead>
                <tbody data-repeater-list="gettext">
                  <tr data-repeater-item style="display:none;">
                    <td class="th-original"><span class="dashicons dashicons-menu-alt move-handle"></span>&nbsp;<input type="text" data-slug="original" name="original" value="" placeholder="<?=__("Original text ...", $this->td);?>"/></td>
                    <td class="th-translate"><input type="text" data-slug="translate" name="translate" placeholder="<?=__("Translate to ...", $this->td);?>" /></td>
                    <td class="th-options">
                      <label><input type="checkbox" value="yes" data-slug="buffer" name="buffer" />&nbsp;<?=__("Green: Replace in Output Buffer | Red: Replace in Content Only", $this->td);?></label>&nbsp;&nbsp;
                      <label><input type="checkbox" value="yes" data-slug="active" name="active" />&nbsp;<?=__("Active", $this->td);?></label>&nbsp;&nbsp;
                    </td>
                    <td class="th-action">
                      <a class="button button-secondary" data-repeater-delete><span style="margin: 4px 0;" class="dashicons dashicons-trash"></span><?=__("Delete Row", $this->td);?></a>
                    </td>
                  </tr>
                </tbody>
              </table>
              <br>
              <a data-repeater-create class="button button-secondary button-hero"><span style="margin: 12px 8px;" class="dashicons dashicons-insert"></span><?=__("Add New Row", $this->td);?></a>&nbsp;&nbsp;
            </div>
            <br><br>
            <table class="form-table wp-list-table widefat striped table-view-list posts">
              <thead>
                <?php
                  echo "<tr class='sync_site_tr border-top'><th colspan=2><strong style='display: inline-block;'>" . __("Migrate String Replace", $this->td) . "</strong></th></tr>";
                ?>
              </thead>
              <tbody>
                <?php
                  $this->print_setting_textarea(["slug" => "str_replace", "caption" => __("String Replace Data", $this->td), "style" => "width: 100%; direction: ltr; min-height: 300px; font-family: monospace; font-size: 0.8rem;",]);
                ?>
              </tbody>
            </table>
          </div>

          <script>
            (function($) {
              $(document).ready(function() {

                var _pepro_ajax_request = null;
                var $success_color = "rgba(21, 139, 2, 0.8)";
                var $error_color   = "rgba(139, 2, 2, 0.8)";
                var $info_color    = "rgba(2, 133, 139, 0.8)";
                if (!$("toast").length) {$(document.body).append($("<toast>"));}

                $("input.wpColorPicker").wpColorPicker();

                $(document).on("click tap", "a.nav-tab[data-tab]", function(e) {
                  e.preventDefault();
                  var me = $(this);
                  $(".nav-tab.nav-tab-active").removeClass("nav-tab-active");
                  me.addClass("nav-tab-active");
                  $(".tab-content.tab-active").removeClass("tab-active");
                  $(`.tab-content[data-tab=${me.data("tab")}]`).addClass("tab-active");
                  window.location.hash = me.data("tab");
                  localStorage.setItem("mnstudio-core", me.data("tab"));
                });
                function reload_last_active_tab() {
                  if (window.location.hash && "" !== window.location.hash) {
                    $(".nav-tab[data-tab="+window.location.hash.replace("#", "")+"]").trigger("click");
                  }else {
                    last = localStorage.getItem("mnstudio-core");
                    if (last && "" != last) {
                      $(".nav-tab[data-tab="+last.replace("#", "")+"]").trigger("click");
                    }
                  }
                }
                reload_last_active_tab();
                setTimeout(reload_last_active_tab, 100);
                setTimeout(reload_last_active_tab, 500);
                setTimeout(reload_last_active_tab, 1000);

                // initiate repeater
                var $repeater = $(".repeater.translation-panel").repeater({ hide: function (deleteElement) { $(this).remove(); build_translation_data(".repeater.translation-panel", "#gettext_replace"); }, });
                var $str_replace = $(".repeater.str_replace-panel").repeater({ hide: function (deleteElement) { $(this).remove(); build_translation_data(".repeater.str_replace-panel", "#str_replace"); }, });

                // load repeater prev-data
                var json = $("#gettext_replace").val();
                try { var obj = JSON.parse(json); var list = new Array(); if (obj.gettext) { $.each(obj.gettext, function (i,x) { list.push(x); }); $repeater.setList(list); }
                } catch (e) { console.info("could not load translations repeater data"); }

                // load repeater prev-data
                var json = $("#str_replace").val();
                try { var obj = JSON.parse(json); var list = new Array(); if (obj.gettext) { $.each(obj.gettext, function (i,x) { list.push(x); }); $str_replace.setList(list); }
                } catch (e) { console.info("could not load str_replace repeater data"); }


                // update repeater data
                $(document).on("change keyup", ".repeater.translation-panel input", function(e){ e.preventDefault(); build_translation_data(".repeater.translation-panel", "#gettext_replace"); });
                $(document).on("change keyup", ".repeater.str_replace-panel input", function(e){ e.preventDefault(); build_translation_data(".repeater.str_replace-panel", "#str_replace"); });

                // Sortable ui for translation panel
                if ($('.repeater.translation-panel table.wp-list-table tbody tr').length > 2){
                  $('.repeater.translation-panel table.wp-list-table').sortable({ items: 'tr', cursor: 'move', axis: 'y', scrollSensitivity: 40, update: function (event, ui) { build_translation_data(".repeater.translation-panel", "#gettext_replace"); }, /* handle: 'td.wc-shipping-zone-method-sort', */ });
                }
                if ($('.repeater.str_replace-panel table.wp-list-table tbody tr').length > 2){
                  $('.repeater.str_replace-panel table.wp-list-table').sortable({ items: 'tr', cursor: 'move', axis: 'y', scrollSensitivity: 40, update: function (event, ui) { build_translation_data(".repeater.str_replace-panel", "#str_replace"); }, /* handle: 'td.wc-shipping-zone-method-sort', */ });
                }

                // make json data
                function build_translation_data(container=".repeater.translation-panel", data_inp="#gettext_replace"){
                  console.log(`build_translation_data ${container} ~> ${data_inp}`);
                  try {
                    var gettext = {"gettext":[]};
                    $(`${container} table tr[data-repeater-item]`).each(function(i, x) {
                      var item = {};
                      $(this).find("[data-slug]").each(function (indexInArray, valueOfElement) {
                        let el = $(valueOfElement);
                        slug = el.attr("data-slug");
                        switch (el.attr("type")) {
                          case "checkbox":
                            val = el.prop("checked") ? "yes" : false;
                            break;
                          case "checkbox":
                            val = el.prop("checked") ? "yes" : false;
                            break;
                          default:
                            val = el.val();
                        }
                        item[slug] = val;
                      });
                      gettext["gettext"][i] = item;
                    });
                    var jsonData = JSON.stringify(gettext);
                    $(data_inp).val(jsonData).trigger("change");
                  } catch (e) { }
                }

                function show_toast(data = "Sample Toast!", bg="", delay = 3000) {
                  if (!$("toast").length) {$(document.body).append($("<toast>"));}else{$("toast").removeClass("active");}
                  setTimeout(function () {
                    $("toast").css("--toast-bg", bg).html(data).stop().addClass("active").delay(delay).queue(function () {
                      $(this).removeClass("active").dequeue().off("click tap");
                    }).on("click tap", function (e) {e.preventDefault(); $(this).stop().removeClass("active");});
                  }, 200);
                }
              });
            })(jQuery);
          </script>

          <div class="submit_wrapper">
            <button id="submit" class="button button-primary button-hero"><span style="margin: 12px 8px;" class="dashicons dashicons-yes-alt"></span>&nbsp;<?= __("Save setting", $this->td); ?></button>
          </div>
        </form>
      </div>
      <?php
    }
    /**
      * Get All orders IDs for a given product ID.
      *
      * @param  integer  $product_id (required)
      * @param  array    $order_status (optional) Default is 'wc-completed'
      *
      * @return array
      */
    public function get_orders_ids_by_product_id($product_id, $order_status = array('wc-completed')) {
      global $wpdb;
      $results = $wpdb->get_col("
          SELECT order_items.order_id
          FROM {$wpdb->prefix}woocommerce_order_items as order_items
          LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
          LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
          WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ( '" . implode("','", $order_status) . "' )
          AND order_items.order_item_type = 'line_item'
          AND order_item_meta.meta_key = '_product_id'
          AND order_item_meta.meta_value = '$product_id'
      ");
      return $results;
    }
    public function display_user($uid=0, $link=false, $id=true, $raw_else=false){
      $uid = false === $uid ? get_current_user_id() : $uid;
      $user_info = get_userdata($uid);
      if ($user_info) {
        if ($link) {
          return sprintf(
            "<a target='_blank' href='%s' title='%s'>%s</a>%s",
            admin_url("user-edit.php?user_id=$uid"),
            "Username: $user_info->user_login".PHP_EOL."Date Reg.: $user_info->user_registered".PHP_EOL,
            (trim("$user_info->first_name $user_info->last_name") == "" ? $user_info->user_login : "$user_info->first_name $user_info->last_name"),
            ($id ? "&nbsp;&nbsp;<sup title='ID $uid'>ID: $uid</sup>" : "")
          );
        }else{
          return "$user_info->first_name $user_info->last_name";
        }
      }
      else {
        return $raw_else ? $uid : sprintf(__("ID #%s [deleted-user]", $this->td), $uid);
      }
    }
    public function send_second_sms($order_id, $second_sms_text = null) {
      if (!$order_id) return;
      $order = wc_get_order($order_id);
      if (!is_a($order, "\WC_Order")) return;
      if ($order->get_meta("_second_sms_sent", true) === "yes") return true;
      if (is_null($second_sms_text)) $second_sms_text = $this->second_sms_text;
      if (function_exists("PWooSMS")) {
        $send_sms = false;
        foreach ($order->get_items() as $item_id => $item) {
          $product = $item->get_product();
          if ($product && $this->course_shipping_slug == $product->get_shipping_class()) {
            $send_sms = true;
            break;
          }
        }
        if (!$send_sms) return false;
        $mobile = $order->get_billing_phone();
        $new_status = $order->get_status();

        if (function_exists("PWSMS")) {
          $message = PWSMS()->replace_short_codes($second_sms_text, $new_status, $order);
          $response = PWSMS()->send_sms(array("post_id" => $order_id, "message" => $message, "mobile" => $mobile));
        }
        elseif (function_exists("PWooSMS")) {
          $message = PWooSMS()->ReplaceShortCodes($second_sms_text, $new_status, $order);
          $response = PWooSMS()->SendSMS(array("post_id" => $order_id, "message" => $message, "mobile" => $mobile));
        }

        $order->update_meta_data("_second_sms_sent", "yes");
        $order->add_order_note("<b>PigmentDev -- Send 2nd SMS: </b>" . date("Y/m/d H:i:s", current_time("timestamp")) . "<br>SMS RESULT: <pre style='text-align: left; direction: ltr; border:1px solid #1e45cf; padding: 1rem; color: #1e45cf;'>" . var_export([
          "pattern"  => $second_sms_text,
          "message"  => $message,
          "mobile"   => $mobile,
          "response" => $response
        ], 1) . "</pre>", false);
        $order->save();
        return $response;
      }
    }
    public function bundle_add_order_notes_to_admin_email($order, $sent_to_admin = true, $plain_text = false) {

      // You want to send those only to the Admin
      if (!$sent_to_admin) return;

      // You can also check which email you are sending, by checking the order status
      // if ( 'completed' != $order->get_status() ) return;

      $bundle_items = array();
      foreach ($order->get_items() as $item) {
        $_selected_var_id = $item->get_meta('_selected_var_id', true);
        $_saved_virtuals  = $item->get_meta('_saved_virtuals', true);
        if (!$_selected_var_id || empty($_selected_var_id)) continue;
        $bundle_virtuals = (array) explode(",", $_saved_virtuals);
        $bundle_items[] = get_the_title($_selected_var_id);
        foreach ($bundle_virtuals as $pid) $bundle_items[] = get_the_title($pid);
      }
      if (!empty($bundle_items)) {
        echo "<h2>محصولات باندل شده</h2><p>";
        echo implode("<br>", $bundle_items);
        echo "</p><br>";
      }
    }
    public function hide_bundle_items($return, $item) {
      $meta = wc_get_order_item_meta($item->get_id(), "_bundle_hide", true);
      if (!empty($meta) && $meta > 0) return false; //hide from table
      return $return;
    }
    public function hide_out_of_stock_price($price, $product) {
      if ($this->different_course_checkout && $this->using_dollar() && (string) $product->get_shipping_class() != (string) $this->course_shipping_slug) return "";
      if (!$product->is_in_stock()) $price = 'ناموجود';
      return $price;
    }
    public function wc_bundled_add_to_cart() {
      global $product;
      if (!$product) return;
      $bundled = "yes" === get_post_meta($product->get_id(), "_is_bundle", true);
      if (!$bundled) return;
      $bundle_physical = get_post_meta($product->get_id(), "bundle_physical", true);
      (array) $bundle_virtuals = explode("\n", get_post_meta($product->get_id(), "bundle_virtuals", true));
      $product_new = wc_get_product($bundle_physical);
      if (!$product_new) return;
      if ($product_new->get_type() == "variable" && $product_new->is_in_stock()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('wc-add-to-cart-variation');
        $get_variations       = count($product_new->get_children()) <= apply_filters('woocommerce_ajax_variation_threshold', 100, $product_new);
        $available_variations = $get_variations ? $product_new->get_available_variations() : false;
        $attributes           = $product_new->get_variation_attributes();
        $selected_attributes  = $product_new->get_default_attributes();
        $attribute_keys       = array_keys($attributes);
        $variations_json      = wp_json_encode($available_variations);
        $variations_attr      = function_exists('wc_esc_json') ? wc_esc_json($variations_json) : _wp_specialchars($variations_json, ENT_QUOTES, 'UTF-8', true);
        $stock_status         = $product_new->is_in_stock() ? "in_stock" : "out_of_stock";
        do_action('woocommerce_before_add_to_cart_form'); ?>
        <form class="variations_form physical_product_dummy_form <?= $stock_status; ?>" method="post" style="margin: 0;"
        data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
          <?php if (empty($available_variations) && false !== $available_variations) : ?>
            <p class="stock out-of-stock"><?php echo esc_html(apply_filters('woocommerce_out_of_stock_message', __('This product is currently out of stock and unavailable.', 'woocommerce'))); ?></p>
            <style> .single_add_to_cart_button.button.alt { display: none; } </style>
          <?php else : ?>
            <table class="variations" cellspacing="0" role="presentation" style="margin: 0;padding: 0;">
              <tbody>
                <?php foreach ($attributes as $attribute_name => $options) : ?>
                  <tr>
                    <th class="label"><label for="<?php echo esc_attr(sanitize_title($attribute_name)); ?>"><?php echo wc_attribute_label($attribute_name); // WPCS: XSS ok.
                                                                                                            ?></label></th>
                    <td class="value">
                      <?php
                      wc_dropdown_variation_attribute_options(
                        array(
                          'options'   => $options,
                          'attribute' => $attribute_name,
                          'product'   => $product_new,
                        )
                      );
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php
            do_action('woocommerce_after_variations_table');
            ?>
            <div class="single_variation_wrap">
              <?php
              do_action('woocommerce_before_single_variation');
              do_action('woocommerce_single_variation');
              do_action('woocommerce_after_single_variation');
              ?>
            </div>
          <?php endif; ?>
          <?php
          do_action('woocommerce_after_variations_form');
          ?>
          <style>
            .physical_product_dummy_form .woocommerce-variation-add-to-cart,
            .physical_product_dummy_form+.stock {
              display: none !important;
            }

            .product-is-physical form.variations_form p.stock.out-of-stock {
              padding: 0.32rem;
            }

            .variations_form table td,
            .variations_form table th {
              border: none;
            }

            .physical_product_dummy_form.out_of_stock .woocommerce-variation-add-to-cart {
              display: block !important;
            }

            .physical_product_dummy_form.out_of_stock .woocommerce-variation.single_variation {
              display: block !important;
            }

            .elementor-widget-container>.product-labels.labels-rectangular {
              display: none !important;
            }
          </style>
          <script>
            (function($) {
              $(document).ready(function() {
                setTimeout(function() {
                  $("form.cart .single_add_to_cart_button").addClass('disabled wc-variation-is-unavailable')
                }, 100);
                $(document).on("change input", ".physical_product_dummy_form input.variation_id", function(e) {
                  e.preventDefault();
                  var me = $(this);
                  if ($("[data-product_variations]").length) {
                    let variations = $("[data-product_variations]").data("product_variations");
                    $.each(variations, function(i, x) {
                      if (x.variation_id == me.val()) {
                        if (x.is_in_stock !== true) {
                          $("form.cart .single_add_to_cart_button").addClass('disabled wc-variation-is-unavailable')
                        } else {
                          $("form.cart .single_add_to_cart_button").removeClass('disabled wc-variation-is-unavailable')
                        }
                      }
                    });
                  }
                  $("input#_selected_var_id").val(me.val()).trigger("change");
                  if (me.val() == "") {
                    $("form.cart .single_add_to_cart_button").addClass('disabled wc-variation-is-unavailable')
                  }
                });
              });
            })(jQuery);
          </script>
        </form>
      <?php
      }
    }
    public function wc_add_to_cart_validation($passed, $product_id, $quantity, $variation_id = null) {
      $bundled = "yes" === get_post_meta($product_id, "_is_bundle", true);
      if (!$bundled) return $passed;
      if (empty($_POST['_selected_var_id'])) {
        $passed = false;
        wc_add_notice("شما باید تمامی گزینه ها را انتخاب کنید", 'error');
      }
      $bundle_physical = get_post_meta($product_id, "bundle_physical", true);
      $selected_var = sanitize_text_field($_POST['_selected_var_id']);
      $physical = wc_get_product($bundle_physical);
      if (!in_array($selected_var, $physical->get_children()) && $bundle_physical != $selected_var) {
        $passed = false;
        wc_add_notice("داده ی غیرمجاز وارد شده است", 'error');
      }
      return $passed;
    }
    public function add_custom_field_to_product() {
      global $product;
      if (!$product) return;
      $bundled = "yes" === get_post_meta($product->get_id(), "_is_bundle", true);
      if (!$bundled) return;
      woocommerce_form_field('_selected_var_id', array("type" => "hidden"), "");
    }
    public function add_custom_field_data_to_cart($cart_item_data, $product_id) {
      if (isset($_POST['_selected_var_id'])) {
        $cart_item_data['_selected_var_id'] = sanitize_text_field($_POST['_selected_var_id']);
      }
      return $cart_item_data;
    }
    public function add_custom_field_data_to_order($item, $cart_item_key, $values, $cart) {
      // Check if the custom field value is set
      if (isset($values['_selected_var_id'])) {
        $product_id = $values['product_id'];
        $bundled = "yes" === get_post_meta($product_id, "_is_bundle", true);
        if (!$bundled) return;
        $bundle_physical = get_post_meta($product_id, "bundle_physical", true);
        (array) $bundle_virtuals = explode("\n", get_post_meta($product_id, "bundle_virtuals", true));
        $item->update_meta_data('_saved_physical', $bundle_physical);
        $item->update_meta_data('_selected_var_id', sanitize_text_field($values['_selected_var_id']));
        $item->update_meta_data('_saved_virtuals', implode(",", $bundle_virtuals));
      }
    }
    public function wc_bundle_is_purchasable($return, $product) {
      $bundled = "yes" === get_post_meta($product->get_id(), "_is_bundle", true);
      if (!$bundled) return $return;
      $bundle_physical = get_post_meta($product->get_id(), "bundle_physical", true);
      $bundle = wc_get_product($bundle_physical);
      if (!$bundle_physical || !$bundle) return $return;
      return $bundle->is_in_stock();
      return $return;
    }
    public function wc_checkout_create_order(\WC_Order $order, $data) {
      // Loop through order
      foreach ($order->get_items() as $item) {
        $_saved_physical  = $item->get_meta('_saved_physical', true);
        $_selected_var_id = $item->get_meta('_selected_var_id', true);
        $_saved_virtuals  = $item->get_meta('_saved_virtuals', true);
        if (!$_selected_var_id || empty($_selected_var_id)) continue;
        $virtuals = (array) explode(",", $_saved_virtuals);
        foreach ($virtuals as $i => $virtual_id) {
          $item_id = $order->add_product(@wc_get_product(intval($virtual_id)), 1, ['subtotal' => 0, 'total' => 0, 'quantity' => 1,]);
          wc_add_order_item_meta($item_id, "_bundle_hide", $virtual_id);
        }
        $item_id = $order->add_product(@wc_get_product($_selected_var_id), 1, ['subtotal' => 0, 'total' => 0, 'quantity' => 1,]);
        wc_add_order_item_meta($item_id, "_bundle_hide", $_selected_var_id);
      }
      $order->save();
      return $order;
    }
    public function woocommerce_hidden_order_itemmeta($items) {
      $items[] = '_saved_physical';
      $items[] = '_selected_var_id';
      $items[] = '_saved_virtuals';
      $items[] = '_bundle_hide';
      return $items;
    }
    public function wc_cart_get_item_data($item_data, $cart_item_data) {
      if (isset($cart_item_data['_selected_var_id'])) {
        $product = wc_get_product($cart_item_data['_selected_var_id']);
        // $product = $cart_item_data["data"];
        $product_id = $cart_item_data["product_id"];
        (array) $bundle_virtuals = explode("\n", get_post_meta($product_id, "bundle_virtuals", true));
        $bundle_items = array($product->get_name());
        foreach ($bundle_virtuals as $pid) {
          $bundle_items[] = get_the_title($pid);
        }
        $item_data["_wc_bundle"] = array(
          "key"   => "باندل",
          "value" => implode(" ، ", $bundle_items),
        );
      }
      return $item_data;
    }
    public function wc_bundled_product_settings_tabs($tabs) {
      //unset( $tabs[ 'inventory' ] );
      $tabs['wc_bundled_product'] = array(
        'label'    => 'محصول باندل',
        'target'   => 'wc_bundled_product_data',
        'class'    => array("pigment", "show_if_simple"),
        // 'class'    => array('show_if_virtual'),
        'priority' => 21,
      );
      return $tabs;
    }
    public function wc_bundled_product_panels() {
      global $post;
      $product_id = $post->ID;
      echo '<div id="wc_bundled_product_data" class="panel woocommerce_options_panel hidden"><style>#woocommerce-coupon-data ul.wc-tabs li.wc_bundled_product_options a::before, #woocommerce-product-data ul.wc-tabs li.wc_bundled_product_options a::before, .woocommerce ul.wc-tabs li.wc_bundled_product_options a::before { content: "\f454"; }</style>';
      echo "<p>یک محصول فیزیکی که کاربر میتواند وریشن های آن را انتخاب کند و چندین محصول مجازی دیگر را بصورت یکجا به کاربر بفروشید.<br>
      توجه کنید اگر محصول مجازی شما از نوع متغیر است باید شناسه متغیر را وارد کنید.</p>";

      // echo "<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>". print_r(get_post_meta($product_id, "_is_bundle", true),1) ."</pre>";

      woocommerce_wp_checkbox(array(
        "id"          => "_is_bundle",
        "label"       => "محصول باندل است؟",
        "desc_tip"    => true,
        "description" => "اگر محصول باندل است این گزینه را تیک بزنید",
      ));

      woocommerce_wp_text_input(array(
        "id"          => "bundle_physical",
        "label"       => "محصول فیزیکی",
        "desc_tip"    => true,
        "style"       => "direction: ltr;text-align: left;",
        "placeholder" => "آیدی محصول فیزیکی را وارد کنید",
        "description" => "آیدی محصول فیزیکی، اگر محصول متغیر است نیازی به وارد کردن شناسه متغیر نیست، تنها شناسه محصول کافیست",
      ));

      woocommerce_wp_textarea_input(array(
        "id"          => "bundle_virtuals",
        "label"       => "محصولات مجازی",
        "desc_tip"    => true,
        "style"       => "height: 100px;direction: ltr;text-align: left;",
        "placeholder" => "آیدی محصولات مجازی را در هر خط یک مورد وارد کنید",
        "description" => "آیدی محصولات مجازی را در هر خط یک مورد وارد کنید. اگر محصول متغیر است باید شناسه متغیر را وارد کنید",
      ));

      echo "</div>";
    }
    public function wc_bundled_save_fields($id) {
      update_post_meta($id, "bundle_physical", sanitize_text_field(isset($_POST["bundle_physical"])?$_POST["bundle_physical"]:""));
      update_post_meta($id, "bundle_virtuals", sanitize_textarea_field(isset($_POST["bundle_virtuals"])?$_POST["bundle_virtuals"]:""));
      update_post_meta($id, "_is_bundle", sanitize_text_field(isset($_POST["_is_bundle"])?$_POST["_is_bundle"]:""));
    }
    public function hide_products_subcategory($q) {
      if (!is_product_category()) return;
      $parent_id = get_queried_object_id();
      $q = false;
      return $q;
    }
    public function woodmart_woocommerce_main_loop($fragments = false) {
      $shop_cat_id = 79;
      $term = get_queried_object();
      // if (current_user_can("administrator")) echo "<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>". print_r($term,1) ."</pre>";
      if (is_product_category() && $term && $term->term_id != $shop_cat_id && ($term->parent != $shop_cat_id || $term->parent = 0)) return;
      if (function_exists("woodmart_woocommerce_main_loop")) woodmart_woocommerce_main_loop($fragments);
    }
    public function add_category_comment_field($tag) {
      $current_category_comment = get_term_meta($tag->term_id, 'category_comment_post_id', true);
      echo "<tr class='form-field category_comment amirhp-com'>";
      echo '<th scope="row" valign="top"><label for="category_comment">Category Comment</label></th>';
      echo "<td><input type='text' name='category_comment' id='category_comment' value='$current_category_comment' /></td>";
      echo '</tr>';
    }
    public function save_category_comment_dropdown($term_id) {
      $term = get_term($term_id, 'product_cat');
      if (is_wp_error($term) || empty($term)) return;
      $post_args = array(
        "post_title"  => "دسته بندی — {$term->name}",
        "post_type"   => "category_comment",
        "post_status" => "publish",
      );
      $existing_post_id = get_term_meta($term_id, 'category_comment_post_id', true);
      if ($existing_post_id && get_post($existing_post_id)) {
        $post_args['ID'] = $existing_post_id;
        update_term_meta($term_id, 'category_comment_post_id', $existing_post_id);
        wp_update_post($post_args);
        update_post_meta($existing_post_id, '_category_id', $term_id);
      } else {
        $new_post_id = wp_insert_post($post_args);
        if (!is_wp_error($new_post_id)) {
          update_term_meta($term_id, 'category_comment_post_id', $new_post_id);
          update_post_meta($new_post_id, '_category_id', $term_id);
        }
      }
    }
    public function create_category_comment_post_type() {
      $labels = array(
        "name" => "Category Comments",
        "singular_name" => "Category Comment",
      );
      $args = array(
        "labels"              => $labels,
        "public"              => true,
        "show_ui"             => true,
        "exclude_from_search" => true,
        "publicly_queryable"  => true,
        "show_in_menu"        => false,
        "show_in_nav_menus"   => false,
        "show_in_admin_bar"   => false,
        "show_in_rest"        => false,
        "supports"            => array("title", "comments"),
      );
      register_post_type('category_comment', $args);
    }
    public function redirect_after_comment($location) {
      $uid = (int) get_current_user_id() . $this->get_real_IP_address();
      delete_transient("added_comment_{$uid}");
      set_transient("added_comment_{$uid}", "yes", 10);
      // return add_query_arg(["added"=>"true"], wp_get_referer());
      return wp_get_referer();
    }
    public function shortcode_wrapper_mn_video($atts=array(), $content=""){
      $atts = extract(shortcode_atts(array("default" => "values",),$atts));
      ob_start();
      global $post;
      // Try to get the current object (post, page, taxonomy, etc.)
      $object = null;
      if (is_singular()) {
        $object = $post;
      } elseif (is_tax() || is_category() || is_tag()) {
        $object = get_queried_object();
      }
      // Get the ID for ACF functions
      $object_id = 0;
      if ($object instanceof \WP_Post) {
        $object_id = $object->ID;
      } elseif (isset($object->term_id)) {
        $object_id = "{$object->taxonomy}_{$object->term_id}";
      }
      // Fetch ACF fields
      $mnv_show_video   = function_exists('get_field') ? get_field('mnv_show_video', $object_id) : '';
      $mnv_select_video = function_exists('get_field') ? get_field('mnv_select_video', $object_id) : '';
      $mnv_select_video_url = function_exists('get_field') ? get_field('mnv_select_video_url', $object_id) : '';
      $mnv_cover_image  = function_exists('get_field') ? get_field('mnv_cover_image', $object_id) : '';
      $mnv_select_video = is_numeric($mnv_select_video) ? wp_get_attachment_url($mnv_select_video) : $mnv_select_video;
      $mnv_cover_image  = is_numeric($mnv_cover_image) ? wp_get_attachment_url($mnv_cover_image) : $mnv_cover_image;
      if ($mnv_select_video_url && !empty($mnv_select_video_url)) {
        $mnv_select_video = $mnv_select_video_url;
      }
      ?>
      <?php if ($mnv_show_video && ($mnv_select_video || $mnv_cover_image)): ?>
        <div class="shortcode_wrapper">
          <div class="mn-video-block">
            <?php if ( $mnv_select_video ) : ?>
              <video
                src="<?= esc_url($mnv_select_video); ?>"
                poster="<?= esc_url($mnv_cover_image); ?>"
                playsinline
                webkit-playsinline
                style="max-width:100%;height:auto;"
              <?php if ( false ) : ?>
                controls="false"
                autoplay="false"
                preload="none"
                onplay="this.controls=false"
                onloadedmetadata="this.controls=false"
                controlslist="nodownload noplaybackrate nofullscreen"
              <?php else: ?>
                controls="true"
                preload="none"
                autostart="false"
                onplay="this.controls=true"
                onloadedmetadata="this.controls=true"
                controlslist="nodownload noplaybackrate nofullscreen"
              <?php endif; ?>
                controlslist="nodownload noplaybackrate nofullscreen"
                tabindex="-1">
                مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
              </video>
            <?php endif; ?>
            <?php if ( !$mnv_select_video && $mnv_cover_image ) : ?>
              <img src="<?= esc_url($mnv_cover_image); ?>" alt="Cover Image" style="max-width:100%;height:auto;" />
            <?php endif; ?>

            <style>
              .mn-video-custom-play-btn {
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                background: #684b6826;
                border: none;
                border-radius: 40px;
                padding: 12px;
                font-size: 18px;
                cursor: pointer;
                z-index: 10;
                display: flex;
                align-items: center;
                gap: 10px;
                backdrop-filter: blur(25px);
                width: auto;
                color: #470A30;
                transition: all .3s ease-in-out;
                flex-wrap: nowrap;
                min-width: 230px;
                justify-content: center;
              }
              .mn-video-custom-play-btn svg {
                width: 48px;
                height: 48px;
                min-width: 48px;
                min-height: 48px;
              }
              .mn-video-block video{
                object-fit: cover;
              }
              .mn-video-block {
                position: relative;
                display: inline-block;
                width: 100%;
                max-width: 100%;
              }
              .btmnv {
                font-size: 18px;
                font-weight: bold;
                line-height: 25px;
                font-family: "iransansbold", Sans-serif;
              }
              .mn-video-duration {
                font-size: 0.9rem;
                display: block;
                line-height: 1.5rem;
                font-weight: 400;
              }
              .mn-video-custom-play-btn:hover {
                background: #0000002e;
                color: #470A30 !important;
              }
            </style>
            <button type="button" class="mn-video-custom-play-btn" id="mnv-play-btn-<?= esc_attr($object_id); ?>">
              <svg width="41" height="41" viewBox="0 0 41 41" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M20.5 0C31.804 0 41 9.196 41 20.5C41 31.804 31.804 41 20.5 41C9.196 41 0 31.804 0 20.5C0 9.196 9.196 0 20.5 0ZM20.5 3C10.85 3 3 10.85 3 20.5C3 30.15 10.85 38 20.5 38C30.15 38 38 30.15 38 20.5C38 10.85 30.15 3 20.5 3ZM19.6616 13.5228C20.7016 13.9568 27.9996 17.6948 27.9996 20.4908C27.9996 23.1108 21.4316 26.7748 19.7536 27.4748C19.0996 27.7468 18.3536 28.0028 17.6556 28.0028C17.1156 28.0028 16.6036 27.8488 16.1856 27.4328C15.8636 27.1148 15.0036 26.2648 14.9996 20.6668C14.9976 14.7228 15.8916 13.8388 16.1856 13.5448C17.2316 12.5088 18.9356 13.2188 19.6616 13.5228ZM18.2676 16.1728C17.9136 18.1748 17.9076 22.9568 18.2616 24.8568C20.0176 24.2368 23.8216 21.7368 24.8456 20.4988C23.8256 19.2968 20.0696 16.8488 18.2676 16.1728Z" fill="#612449" />
              </svg>
              <span class="title-mnv">
                <span class="btmnv">مشاهده ویدیو</span>
                <span class="mn-video-duration" id="mnv-duration-<?= esc_attr($object_id); ?>"></span>
              </span>
            </button>
            <script>
              (function ($) {
                $(document).ready(function () {
                  var btn = document.getElementById('mnv-play-btn-<?= esc_attr($object_id); ?>');
                  var video = btn && btn.closest('.mn-video-block').querySelector('video');
                  if (btn && video) {
                    btn.addEventListener('click', function(e){video.play();});
                    video.addEventListener('play', function(){btn.style.display = 'none';});
                    video.addEventListener('pause', function(){btn.style.display = '';});
                    if (!video.paused && !video.ended) {btn.style.display = 'none';}
                    // Play/pause on video click/tap
                    video.addEventListener('click', function(e) { if (video.paused) { video.play(); } else { video.pause(); } });
                    video.addEventListener('touchend', function(e) { if (video.paused) { video.pause(); } else { video.play(); } });
                  }

                  var video = document.querySelector('#mnv-play-btn-<?= esc_attr($object_id); ?>')?.closest('.mn-video-block')?.querySelector('video');
                  var durationSpan = document.getElementById('mnv-duration-<?= esc_attr($object_id); ?>');
                  if (video && durationSpan) {
                    function formatDuration(seconds) {
                      seconds = Math.floor(seconds);
                      var min = Math.floor(seconds / 60);
                      var sec = seconds % 60;
                      var out = '';
                      if (min > 0) out += min + ' دقیقه ';
                      out += sec + ' ثانیه';
                      return out;
                    }
                    function updateDuration() {
                      if (video.duration && !isNaN(video.duration)) {
                        durationSpan.textContent = formatDuration(video.duration);
                      }
                    }
                    video.addEventListener('loadedmetadata', updateDuration);
                    // If already loaded
                    if (video.readyState >= 1) updateDuration();
                  }
                });
              })(jQuery);
            </script>
          </div>
        </div>
      <?php endif; ?>
      <?php
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return $htmloutput;
    }
    public function show_comment($atts = array(), $content = "") {
      $atts = extract(shortcode_atts(array("id" => "", "title" => "0",), $atts));
      if (empty($id)) $id = get_term_meta(get_queried_object()->term_id, "category_comment_post_id", true);
      $post_title = get_the_title($id);
      ob_start();
      $comments = get_comments(["post_id" => $id, "status" => "approve",]);
      if ($comments) {
      ?>
        <h3><?= $title == "0" ? "نظرات دیگر دانشجویان در مورد <strong>{$post_title}</strong>" : $title; ?></h3>
        <style>
          .comment-form-url {
            display: none;
          }

          #reviews .avatar {
            float: right;
          }

          #reviews .comment {
            clear: both;
          }

          #reviews .avatar img {
            margin: 0 0 0 0.5rem;
            border-radius: 5px;
          }

          #reviews .meta {
            font-weight: bold;
          }

          #reviews li.review {
            margin-bottom: 1rem;
            clear: both;
            min-height: calc(60px + 2rem);
            padding: 1rem;
            box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
            background: #fff;
            border-radius: 5px;
          }

          #reviews li.comment {
            padding: 1rem;
            list-style: none;
            border-top: 1px solid var(--brdcolor-gray-300);
          }

          #reviews li.comment ul li:last-of-type {
            padding-bottom: 0;
            margin-bottom: 0;
          }

          #reviews li.comment ul li {
            margin-right: 1rem;
          }

          @media only screen and (max-width: 720px) {
            #reviews .comment-meta.commentmetadata {
              text-align: end !important;
              margin: -0.5rem 0 1rem 0 !important;
            }
          }

          #reviews .says,
          #reviews .reply,
          #reviews .comment-edit-link,
          #reviews .comment-reply-link {
            display: none;
          }

          #reviews .comment-meta.commentmetadata {
            display: block;
            text-align: left;
            margin: -1.5rem 0 1rem 0;
            font-size: small;
            pointer-events: none;
          }

          #reviews .comment-author.vcard {
            display: flex;
            align-items: center;
            gap: 0;
          }

          ul#reviews {
            padding: 0 !important;
          }

          #reviews .comment-author.vcard .fn {
            margin-right: 0.6rem;
            font-style: initial;
          }

          .comment-author.vcard img {
            border-radius: 100%;
          }
        </style>
        <ul id="reviews">
          <?php wp_list_comments([
            "avatar_size" => 60,
          ], $comments); ?>
        </ul>
      <?php
      }
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return $htmloutput;
    }
    public function get_comment($atts = array(), $content = "") {
      $atts = extract(shortcode_atts(array("id" => "", "title" => "0", "added" => "0",), $atts));
      $uid = (int) get_current_user_id() . $this->get_real_IP_address();
      $id = get_term_meta(get_queried_object()->term_id, "category_comment_post_id", true);
      ob_start();
      ?>
      <style>
        .submitted-comment-awaiting-approval {
          margin-bottom: 2rem;
          padding: 1rem;
          background: #dfffdf;
          border-right: 5px solid green;
          box-shadow: #32325d00 0px 6px 12px -2px, #00000063 0px 3px 3px -3px;
        }
      </style>
      <?php
      if (!empty($id) && get_post($id)) echo "<h3>" . ($title == "0" ? "نظر شما" : $title) . "</h3>";
      if (isset($_GET["added"]) && !empty($_GET["added"]) || "yes" === get_transient("added_comment_{$uid}")) {
        delete_transient("added_comment_{$uid}");
        echo "<div class='submitted-comment-awaiting-approval'>" . ($added == "0" ? "با تشکر، نظر شما ثبت شد." : $added) . "</div>";
        $htmloutput = ob_get_contents();
        ob_end_clean();
        return $htmloutput;
      }
      if (!empty($id) && get_post($id)) comment_form(null, $id);
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return $htmloutput;
    }
    public function set_virtual_products_with_shipping_class_course() {
      // Get all products with the shipping class "course"
      $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'tax_query' => array(
          array(
            'taxonomy' => 'product_shipping_class',
            'field' => 'slug',
            'terms' => 'course', // Change this to your shipping class name
          ),
        ),
      );

      $products = new \WP_Query($args);
      $total_products = $products->found_posts;
      $processed_products = 0;
      $total_var = 0;
      while ($products->have_posts()) {
        $products->the_post();
        $product_id = get_the_ID();
        $product_name = get_the_title();

        // Update progress
        $processed_products++;
        $progress_percent = round(($processed_products / $total_products) * 100, 2);
        $product = wc_get_product($product_id);

        // Display progress
        $isVirtual = $product->is_virtual() ? "YES" : "NO";
        echo "Processing Product $processed_products of $total_products ($progress_percent%) - <a href='" . get_edit_post_link($product_id) . "'>$product_name</a><br>";
        // Check if the product is a variable product
        if ($product->is_type("variable")) {
          // Update all variations to be virtual
          $variations = $product->get_children();
          if (!empty($variations)) {
            $tv = count($variations);
            $i = 0;
            foreach ($variations as $variation_id) {
              $i++;
              $total_var++;
              $isVirtual = wc_get_product($variation_id)->is_virtual() ? "YES" : "NO";
              echo "<small>Processing Variation #$variation_id ~ $i of $tv -- Is Virtual: $isVirtual</small><br>";
              update_post_meta($variation_id, '_virtual', 'yes');
            }
          }
        } else {
          $total_var++;
          // Update the product to be virtual
          update_post_meta($product_id, '_virtual', 'yes');
        }

        // Clear product cache
        wc_delete_product_transients($product_id);
      }

      echo "<p><strong>Total Product & Variations Processed: $total_var;</strong></p>";
      // Reset the post data
      wp_reset_postdata();
    }
    public function exclude_learndash_post_types_from_search($query) {
      $excluded_post_types = array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz');
      if (!is_admin() && $query->is_main_query() && $query->is_search()) {
        $query->set('post_type', array('post', 'page')); // Add any additional post types to this array
      }
    }
    public function enable_comments_on_pages() {
      // Enable comments on all pages
      foreach (get_post_types() as $post_type) {
        if (in_array($post_type, ['page'])) {
          // Enable comments and trackbacks
          $args = array(
            'ID' => 0,
            'comment_status' => 'open',
            'ping_status' => 'open'
          );
          wp_update_post($args);
        }
      }
    }
    public function gettext_translate_td_woocommerce($translation, $text, $domain) {
      if ('Thanks for your order. It’s on-hold until we confirm that payment has been received.' == $text) return "";
      if ('Sorry, no products matched your selection. Please choose a different combination.' == $text) return "در این ساعت کلاسی تشکیل نمی‌شود، لطفا ساعات دیگر را امتحان کنید";
      if ('Please select some product options before adding this product to your cart.' == $text) return "این محصول شامل چند گزینه برای انتخاب است، لطفا یکی از موارد را انتخاب کنید.";
      return $translation;
    }
    public function woocommerce_template_loop_product_title() {
      echo '<p class="wd-entities-title ' . esc_attr(apply_filters('woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title')) . '"><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    public function woocommerce_get_stock_html($html, $product) {
      // $html = empty($html) ? "نامعلوم" : $html;
      $availability = $product->get_availability();
      $parent = $product->get_parent_id() ? wc_get_product($product->get_parent_id()) : $product;
      if ((!$parent->is_in_stock() || !$product->is_in_stock()) && ($parent->is_type("variable-subscription") || $parent->is_type("subscription") || $product->is_type("variable-subscription") || $product->is_type("subscription"))) {
        $html = '';
        $availability['availability'] = "تکمیل ظرفیت";
        ob_start();
        wc_get_template('single-product/stock.php', array('product' => $product, 'class' => $availability['class'], 'availability' => $availability['availability'],));
        $html = ob_get_clean();
      }
      if ((!$parent->is_in_stock() || !$product->is_in_stock()) && ($parent->is_type("variable") || $parent->is_type("simple"))) {
        $html = '';
        $availability['availability'] = "اتمام موجودی";
        ob_start();
        wc_get_template('single-product/stock.php', array('product' => $product, 'class' => $availability['class'], 'availability' => $availability['availability'],));
        $html = ob_get_clean();
      }
      if (empty($html)) {
        ob_start();
        if (!$parent->is_in_stock() || !$product->is_in_stock()) {
          $availability['availability'] = "تکمیل ظرفیت";
        }
        wc_get_template('single-product/stock.php', array('product' => $product, 'class' => $availability['class'], 'availability' => $availability['availability'],));
        $html = ob_get_clean();
      }
      return $html;
    }
    public function remove_usd_currency() {
      // if ($this->using_dollar()) {
      remove_filter("woocommerce_currency", array($this, "woocommerce_currency"));
      remove_filter("woocommerce_currency_symbol", array($this, "woocommerce_currency_symbol"));
      remove_filter("wc_get_price_decimals", array($this, "wc_get_price_decimals"));
      remove_filter("woocommerce_price_format", array($this, "woocommerce_price_format"));
      remove_filter("woocommerce_get_price_html", array($this, "woocommerce_variable_price_html"), 2, 2);
      remove_filter("woocommerce_is_purchasable", array($this, "woocommerce_is_purchasable"), 1, 2);
      remove_action("woocommerce_cart_item_price", array($this, "modify_cart_item_unit_price"), 10, 3);
      remove_action("woocommerce_cart_item_subtotal", array($this, "modify_cart_item_unit_price"), 10, 3);
      remove_action("woocommerce_before_calculate_totals", array($this, "woocommerce_before_calculate_totals"));
      remove_action("woocommerce_checkout_create_order_line_item", array($this, "checkout_create_order_line_item"), 10, 4);
      remove_filter("woocommerce_coupon_get_amount", array($this, "woocommerce_coupon_get_amount"), 88, 2);
      // }
    }
    public function save_scheduled_notification_for_simple($post_id ) {
      $product = wc_get_product($post_id );
      $product->update_meta_data("_scheduled_notif"               , (isset($_POST["_scheduled_notif_simple"]        ) ? $_POST["_scheduled_notif_simple"]         : ""));
      $product->update_meta_data("_scheduled_notif1_day"          , (isset($_POST["_scheduled_notif1_day"]          ) ? $_POST["_scheduled_notif1_day"]           : ""));
      $product->update_meta_data("_scheduled_notif1_email"        , (isset($_POST["_scheduled_notif1_email"]        ) ? $_POST["_scheduled_notif1_email"]         : ""));
      $product->update_meta_data("_scheduled_notif1_email_subject", (isset($_POST["_scheduled_notif1_email_subject"]) ? $_POST["_scheduled_notif1_email_subject"] : ""));
      $product->update_meta_data("_scheduled_notif1_sms"          , (isset($_POST["_scheduled_notif1_sms"]          ) ? $_POST["_scheduled_notif1_sms"]           : ""));
      $product->update_meta_data("_scheduled_notif2_day"          , (isset($_POST["_scheduled_notif2_day"]          ) ? $_POST["_scheduled_notif2_day"]           : ""));
      $product->update_meta_data("_scheduled_notif2_email"        , (isset($_POST["_scheduled_notif2_email"]        ) ? $_POST["_scheduled_notif2_email"]         : ""));
      $product->update_meta_data("_scheduled_notif2_email_subject", (isset($_POST["_scheduled_notif2_email_subject"]) ? $_POST["_scheduled_notif2_email_subject"] : ""));
      $product->update_meta_data("_scheduled_notif2_sms"          , (isset($_POST["_scheduled_notif2_sms"]          ) ? $_POST["_scheduled_notif2_sms"]           : ""));

      $product->save();
    }
    public function save_scheduled_notification_for_variation($variation_id, $i) {
      update_post_meta($variation_id, "_scheduled_notif", (isset($_POST["_scheduled_notif"][$i])        ? $_POST["_scheduled_notif"][$i]        : ""));
      update_post_meta($variation_id, "_scheduled_notif1_day", (isset($_POST["_scheduled_notif1_day"][$i])   ? $_POST["_scheduled_notif1_day"][$i]   : ""));
      update_post_meta($variation_id, "_scheduled_notif1_email", (isset($_POST["_scheduled_notif1_email"][$i]) ? $_POST["_scheduled_notif1_email"][$i] : ""));
      update_post_meta($variation_id, "_scheduled_notif1_email_subject", (isset($_POST["_scheduled_notif1_email_subject"][$i]) ? $_POST["_scheduled_notif1_email_subject"][$i] : ""));
      update_post_meta($variation_id, "_scheduled_notif1_sms", (isset($_POST["_scheduled_notif1_sms"][$i])   ? $_POST["_scheduled_notif1_sms"][$i]   : ""));
      update_post_meta($variation_id, "_scheduled_notif2_day", (isset($_POST["_scheduled_notif2_day"][$i])   ? $_POST["_scheduled_notif2_day"][$i]   : ""));
      update_post_meta($variation_id, "_scheduled_notif2_email", (isset($_POST["_scheduled_notif2_email"][$i]) ? $_POST["_scheduled_notif2_email"][$i] : ""));
      update_post_meta($variation_id, "_scheduled_notif2_email_subject", (isset($_POST["_scheduled_notif2_email_subject"][$i]) ? $_POST["_scheduled_notif2_email_subject"][$i] : ""));
      update_post_meta($variation_id, "_scheduled_notif2_sms", (isset($_POST["_scheduled_notif2_sms"][$i])   ? $_POST["_scheduled_notif2_sms"][$i]   : ""));
    }
    public function printout_scheduled_notification_for_simple() {
      wp_register_script("_blackswan_scheduled_notifications", "", ["jquery"], current_time("timestamp"));
      wp_add_inline_script("_blackswan_scheduled_notifications", '
        (function($) {
          $(function() {
            setTimeout(function () { $("._blackswan_scheduled_notifications [data-toggle-div]").trigger("change"); }, 150);
            setTimeout(function () { $("._blackswan_scheduled_notifications [data-toggle-div]").trigger("change"); }, 500);
            setTimeout(function () { $("._blackswan_scheduled_notifications [data-toggle-div]").trigger("change"); }, 1000);


            console.log("for variations loaded");

            $(document).on("woocommerce_variations_loaded woocommerce_variations_added woocommerce_variations_saved", "#woocommerce-product-data", function(e){
              setTimeout(function () { $("._blackswan_scheduled_notifications.variable-opts [data-toggle-div]").trigger("change"); }, 150);
            });

            $(document).on("change", "._blackswan_scheduled_notifications [data-toggle-div]", function(e){
              e.preventDefault();
              var me = $(this);
              if (me.prop("checked")) {
                $(me.data("toggle-div")).slideDown();
              }else{
                $(me.data("toggle-div")).slideUp();
              }
            });

          });
        })(jQuery);
      ');
      $id = get_the_ID();
      wp_enqueue_script("_blackswan_scheduled_notifications");
      ?>
      <style>
        .options_group._blackswan_scheduled_notifications.variable-opts > p.form-field { margin: 0 !important; }
        ._blackswan_scheduled_notifications.show_if_simple.show_if_external .form-field._scheduled_notif_field [for="_scheduled_notif"] { width: fit-content; padding-inline-end: 1rem; }
        .options_group._blackswan_scheduled_notifications.variable-opts.options [data-toggle-div] { margin: 5px !important; margin-inline-end: 1rem !important; }
        .options_group._blackswan_scheduled_notifications > div { background: #ffffd5; }
        .options_group._blackswan_scheduled_notifications.variable-opts.options { background: #ffffd5; padding: 0.5rem; display: inline-block; width: 100%;}
        .variable-opts+.scheduled_notifications_wrapper { background: #ffffd5; padding: 0.5rem; display: flex; width: 100%;}
        .variable-opts+.scheduled_notifications_wrapper>div { width: 100%; }
        @media only screen and (max-width: 850px) { .variable-opts+.scheduled_notifications_wrapper { flex-direction: column; } }
        ._blackswan_scheduled_notifications.variable-opts p.form-field._scheduled_notif_field { line-height: 0.5; }
        ._blackswan_scheduled_notifications textarea { height: 10rem; }
      </style>
      <div class="options_group _blackswan_scheduled_notifications show_if_simple show_if_external hidden">
        <?php
        $uniqid = uniqid("scheduled_");
        woocommerce_wp_checkbox(array(
          "label"             => '<span style="margin: 4px;" class="dashicons dashicons-format-status"></span>&nbsp;' . __("Scheduled Notifications", $this->td),
          "wrapper_class"     => "form-row form-row-full",
          "id"                => "_scheduled_notif_simple",
          "custom_attributes" => ["data-toggle-div" => ".{$uniqid}"],
          "description"       => __("The setting will be applied to Orders placed from now afterward", $this->td),
          "desc_tip"          => true,
          "value"             => $this->read_string($id, "_scheduled_notif"),
        ));
        echo "<div class='scheduled_notifications_wrapper options_group $uniqid'>";
        /* FIRST NOTIFICATION WRAPPER */
        woocommerce_wp_text_input(array(
          "label"             => '<span style="margin: 4px;" class="dashicons dashicons-calendar-alt"></span>&nbsp;' . __("Notification 1 Date", $this->td),
          "id"                => "_scheduled_notif1_day",
          "type"              => "number",
          "custom_attributes" => ["min" => 0, "dir" => "ltr", "lang" => "en_US", "step" => "1"],
          "description"       => __("Date notification will be sent, days after the order placed. Use 0 to Disable.", $this->td),
          "desc_tip"          => true,
          "value"             => $this->read_string($id, "_scheduled_notif1_day"),
        ));
        woocommerce_wp_text_input(array(
          "label" => '<span style="margin: 4px;" class="dashicons dashicons-email-alt"></span>&nbsp;' . __("Email Subject", $this->td),
          "id"    => "_scheduled_notif1_email_subject",
          "value" => $this->read_string($id, "_scheduled_notif1_email_subject"),
        ));
        woocommerce_wp_textarea_input(array(
          "label"       => '<span style="margin: 4px;" class="dashicons dashicons-email-alt"></span>&nbsp;' . __("Notification Email", $this->td),
          "id"          => "_scheduled_notif1_email",
          "description" => __("Empty content to disable this kind of notification. Shortcodes are supported.", $this->td),
          "desc_tip"    => true,
          "value"       => $this->read_string($id, "_scheduled_notif1_email"),
        ));
        woocommerce_wp_textarea_input(array(
          "label"       => '<span style="margin: 4px;" class="dashicons dashicons-testimonial"></span>&nbsp;' . __("Notification SMS", $this->td),
          "id"          => "_scheduled_notif1_sms",
          "description" => __("Empty content to disable this kind of notification. SMS Macros are supported.", $this->td),
          "desc_tip"    => true,
          "value"       => $this->read_string($id, "_scheduled_notif1_sms"),
        ));
        echo "<hr>";
        /* SECOND NOTIFICATION WRAPPER */
        woocommerce_wp_text_input(array(
          "label"             => '<span style="margin: 4px;" class="dashicons dashicons-calendar-alt"></span>&nbsp;' . __("Notification 2 Date", $this->td),
          "id"                => "_scheduled_notif2_day",
          "type"              => "number",
          "custom_attributes" => ["min" => 0, "dir" => "ltr", "lang" => "en_US", "step" => "1"],
          "description"       => __("Date notification will be sent, days after the order placed. Use 0 to Disable.", $this->td),
          "desc_tip"          => true,
          "value"             => $this->read_string($id, "_scheduled_notif2_day"),
        ));
        woocommerce_wp_text_input(array(
          "label" => '<span style="margin: 4px;" class="dashicons dashicons-email-alt"></span>&nbsp;' . __("Email Subject", $this->td),
          "id"    => "_scheduled_notif2_email_subject",
          "value" => $this->read_string($id, "_scheduled_notif2_email_subject"),
        ));
        woocommerce_wp_textarea_input(array(
          "label"       => '<span style="margin: 4px;" class="dashicons dashicons-email-alt"></span>&nbsp;' . __("Notification Email", $this->td),
          "id"          => "_scheduled_notif2_email",
          "value"       => $this->read_string($id, "_scheduled_notif2_email"),
          "description" => __("Empty content to disable this kind of notification. Shortcodes are supported.", $this->td),
          "desc_tip"    => true,
        ));
        woocommerce_wp_textarea_input(array(
          "label"       => '<span style="margin: 4px;" class="dashicons dashicons-testimonial"></span>&nbsp;' . __("Notification SMS", $this->td),
          "id"          => "_scheduled_notif2_sms",
          "description" => __("Empty content to disable this kind of notification. SMS Macros are supported.", $this->td),
          "desc_tip"    => true,
          "value"       => $this->read_string($id, "_scheduled_notif2_sms"),
        ));
        echo "</div>";
        ?>
      </div>
      <?php
    }
    public function printout_scheduled_notification_for_variation($loop, $variation_data, $variation) {
      ?>
      <div class="options_group _blackswan_scheduled_notifications variable-opts options">
        <?php
        $uniqid = uniqid("scheduled_");
        woocommerce_wp_checkbox(array(
          "label"             => '<span class="dashicons dashicons-format-status"></span>&nbsp;' . __("Scheduled Notifications", $this->td),
          "id"                => "_scheduled_notif_{$loop}",
          "name"              => "_scheduled_notif[{$loop}]",
          "value"             => $this->read_string($variation->ID, "_scheduled_notif"),
          "custom_attributes" => ["data-toggle-div" => ".{$uniqid}"],
          "description"       => __("The setting will be applied to Orders placed from now afterward", $this->td),
          "desc_tip"          => true,
        ));
        ?>
      </div>
      <?php
      echo "<div class='scheduled_notifications_wrapper $uniqid'>";
      echo '<div class="form-field">';
      /* FIRST NOTIFICATION WRAPPER */
      woocommerce_wp_text_input(array(
        "label"             => '<span style="margin: 4px;" class="dashicons dashicons-calendar-alt"></span>&nbsp;' . __("Notification 1 Date", $this->td),
        "id"                => "_scheduled_notif1_day_{$loop}",
        "name"              => "_scheduled_notif1_day[{$loop}]",
        "value"             => $this->read_string($variation->ID, "_scheduled_notif1_day"),
        "type"              => "number",
        "custom_attributes" => ["min" => 0, "dir" => "ltr", "lang" => "en_US", "step" => "1"],
        "description"       => __("Date notification will be sent, days after the order placed. Use 0 to Disable.", $this->td),
        "desc_tip"          => true,
      ));
      woocommerce_wp_text_input(array(
        "label" => '<span style="margin: 4px;" class="dashicons dashicons-email-alt"></span>&nbsp;' . __("Email Subject", $this->td),
        "id"    => "_scheduled_notif1_email_subject_{$loop}",
        "name"  => "_scheduled_notif1_email_subject[{$loop}]",
        "value" => $this->read_string($variation->ID, "_scheduled_notif1_email_subject"),
      ));
      woocommerce_wp_textarea_input(array(
        "label"       => '<span style="margin: 4px;" class="dashicons dashicons-email-alt"></span>&nbsp;' . __("Notification Email", $this->td),
        "id"          => "_scheduled_notif1_email_{$loop}",
        "name"        => "_scheduled_notif1_email[{$loop}]",
        "value"       => $this->read_string($variation->ID, "_scheduled_notif1_email"),
        "description" => __("Empty content to disable this kind of notification. Shortcodes are supported.", $this->td),
        "desc_tip"    => true,
      ));
      woocommerce_wp_textarea_input(array(
        "label"       => '<span style="margin: 4px;" class="dashicons dashicons-testimonial"></span>&nbsp;' . __("Notification SMS", $this->td),
        "id"          => "_scheduled_notif1_sms_{$loop}",
        "name"        => "_scheduled_notif1_sms[{$loop}]",
        "value"       => $this->read_string($variation->ID, "_scheduled_notif1_sms"),
        "description" => __("Empty content to disable this kind of notification. SMS Macros are supported.", $this->td),
        "desc_tip"    => true,
      ));
      echo '</div>';
      echo '<div class="form-field">';
      /* SECOND NOTIFICATION WRAPPER */
      woocommerce_wp_text_input(array(
        "label"             => '<span style="margin: 4px;" class="dashicons dashicons-calendar-alt"></span>&nbsp;' . __("Notification 2 Date", $this->td),
        "id"                => "_scheduled_notif2_day_{$loop}",
        "name"              => "_scheduled_notif2_day[{$loop}]",
        "value"             => $this->read_string($variation->ID, "_scheduled_notif2_day"),
        "type"              => "number",
        "custom_attributes" => ["min" => 0, "dir" => "ltr", "lang" => "en_US", "step" => "1"],
        "description"       => __("Date notification will be sent, days after the order placed. Use 0 to Disable.", $this->td),
        "desc_tip"          => true,
      ));
      woocommerce_wp_text_input(array(
        "label" => '<span style="margin: 4px;" class="dashicons dashicons-email-alt"></span>&nbsp;' . __("Email Subject", $this->td),
        "id"    => "_scheduled_notif2_email_subject_{$loop}",
        "name"  => "_scheduled_notif2_email_subject[{$loop}]",
        "value" => $this->read_string($variation->ID, "_scheduled_notif2_email_subject"),
      ));
      woocommerce_wp_textarea_input(array(
        "label"       => '<span style="margin: 4px;" class="dashicons dashicons-email-alt"></span>&nbsp;' . __("Notification Email", $this->td),
        "id"          => "_scheduled_notif2_email_{$loop}",
        "name"        => "_scheduled_notif2_email[{$loop}]",
        "value"       => $this->read_string($variation->ID, "_scheduled_notif2_email"),
        "description" => __("Empty content to disable this kind of notification. Shortcodes are supported.", $this->td),
        "desc_tip"    => true,
      ));
      woocommerce_wp_textarea_input(array(
        "label"       => '<span style="margin: 4px;" class="dashicons dashicons-testimonial"></span>&nbsp;' . __("Notification SMS", $this->td),
        "id"          => "_scheduled_notif2_sms_{$loop}",
        "name"        => "_scheduled_notif2_sms[{$loop}]",
        "value"       => $this->read_string($variation->ID, "_scheduled_notif2_sms"),
        "description" => __("Empty content to disable this kind of notification. SMS Macros are supported.", $this->td),
        "desc_tip"    => true,
      ));
      echo "</div>";
      echo "</div>";
    }
    public function read_string($id, $slug){
      $value = get_post_meta($id, $slug, true);
      return (string) (is_array($value) ? "" : $value );
    }
    public function secured_video_shortcode($atts) {
      $atts = extract(shortcode_atts(array(
        'file'     => '',
        'height'   => '360',
        'width'    => '640',
        'poster'   => '',
        'btn'      => '',
        'hd'       => '',
        'loop'     => '',
        'muted'    => '',
        'return'   => '',
        'autoplay' => '',
        'preload'  => 'metadata',
        'class'    => 'wp-video-shortcode'
      ), $atts));
      $ip       = $this->get_real_IP_address();
      $href     = $this->make_secure_link("{$this->secure_video_base}{$file}");
      $img      = $this->make_secure_link("{$this->secure_video_base}" . str_replace("mp4", "jpg", $file));
      $muted    = !empty($muted)   ? "muted"    : "";
      $autoplay = !empty($autoplay) ? "autoplay" : "";
      $loop     = !empty($loop)    ? "loop"     : "";
      $poster   = !empty($poster)  ? 'poster="' . $poster . '"' : 'poster="' . $img . '"';
      if ("url" == $return) return $href;
      return "<style>body.learndash-cpt video.secured_video {max-width: 650px !important;}body.learndash-cpt.learndash-embed-responsive .mejs-container.mejs-video, body.learndash-cpt.learndash-embed-responsive .mejs-overlay.mejs-video.load, body.learndash-cpt.learndash-embed-responsive .wp-video, body.learndash-cpt.learndash-embed-responsive video.wp-video-shortcode { height: auto !important; width: auto !important; margin: auto; display: block; }</style>
      <video $loop $autoplay $muted height='$height' width='$width' controls='true' controlslist='nodownload' $poster oncontextmenu='return false;' data-ip='$ip' class='secured_video $class'>
        <source src=\"$href\" type=\"video/mp4\">
        Sorry, your browser doesn't support embedded videos, but don't worry, you can <a href='$href' download>download it</a> and watch it with your favorite video player!
      </video>";
    }
    public function make_secure_link($url = "", $expire_hour = 24) {
      $secret = $this->secure_link_secret;
      $parsedUrl  = parse_url($url);
      $path       = $parsedUrl["path"];
      $expires    = time() + ($expire_hour * 60 * 60);
      $ip         = $this->get_real_IP_address();
      $md5        = md5("{$expires}{$path}{$ip} {$secret}", true);
      $md5        = base64_encode($md5);
      $md5        = strtr($md5, '+/', '-_');
      $md5        = str_replace('=', '', $md5);
      return $url . '?md5=' . $md5 . '&expires=' . $expires;
    }
    public function notice_for_usd_payment_not_allowed() {
      $has_course = false;
      $has_goods = false;
      foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if ($this->course_shipping_slug == $cart_item['data']->get_shipping_class()) {
          $has_course = true;
        }
        if ($this->course_shipping_slug != $cart_item['data']->get_shipping_class()) {
          $has_goods = true;
        }
      }
      if ($has_course && $has_goods && $this->using_dollar()) {
        wc_add_notice("خرید دلاری فقط برای کلاس‌ها امکان پذیر است. لطفا چنانچه محصول فیزیکی در سبد خرید خود دارید آن را حذف کنید تا امکان پرداخت برایتان فعال شود.", "error");
      }
    }
    public function using_dollar() {
      if (defined("USINGUSD") && USINGUSD === true) {
        return true;
      }
      if (defined("USINGIRR") && USINGIRR === true) {
        return true;
      }
      if (false === $this->use_currency_switch) {
        return false;
      }
      // if (isset(WC()->session) && (string) $this->paypal_gateway_slug === (string) WC()->session->get("chosen_payment_method")) { return true; }
      return isset($_COOKIE["using_dollar"]) && "yes" === (string) $_COOKIE["using_dollar"];
    }
    public function show_current_currency($atts = array(), $content = "") {
      return $this->using_dollar() ? get_woocommerce_currencies()["USD"] : get_woocommerce_currencies()[get_woocommerce_currency()];
    }
    public function show_other_currency($atts = array(), $content = "") {
      return !$this->using_dollar() ? get_woocommerce_currencies()["USD"] : get_woocommerce_currencies()[get_woocommerce_currency()];
    }
    public function show_if_currency_irr($atts = array(), $content = "") {
      return !$this->using_dollar() ? do_shortcode($content) : "";
    }
    public function show_if_currency_usd($atts = array(), $content = "") {
      return $this->using_dollar() ? do_shortcode($content) : "";
    }
    public function woocommerce_mail_content_callback_IRANSansWeb($mail_content) {
      $url = plugins_url("/assets/", __FILE__);
      ob_start();
      ?>
      <style>
        @font-face {
          font-family: IRANSans;
          font-style: normal;
          font-weight: normal;
          src: url('<?= $url; ?>IRANSansWeb.eot');
          src: url('<?= $url; ?>IRANSansWeb.eot?#iefix') format('embedded-opentype'),
            url('<?= $url; ?>IRANSansWeb.woff2') format('woff2'),
            url('<?= $url; ?>IRANSansWeb.woff') format('woff'),
            url('<?= $url; ?>IRANSansWeb.ttf') format('truetype');
        }

        @font-face {
          font-family: IRANSans;
          font-style: normal;
          font-weight: bold;
          src: url('<?= $url; ?>IRANSansWeb_Bold.eot');
          src: url('<?= $url; ?>IRANSansWeb_Bold.eot?#iefix') format('embedded-opentype'),
            url('<?= $url; ?>IRANSansWeb_Bold.woff2') format('woff2'),
            url('<?= $url; ?>IRANSansWeb_Bold.woff') format('woff'),
            url('<?= $url; ?>IRANSansWeb_Bold.ttf') format('truetype');
        }

        body {
          font-size: 14px;
        }

        body,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        input,
        textarea,
        button,
        a,
        span,
        div,
        table,
        tr,
        th,
        td,
        i,
        strong {
          font-family: IRANSans, IranYekan, Tahoma, sans-serif !important;
          unicode-bidi: plaintext;
          font-weight: 400;
          direction: rtl;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
          font-weight: bold;
        }
      </style>
      <?php
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return $mail_content . $htmloutput;
    }
    public function add_recurring_postage_fees($cart) {
      if (!empty($cart->recurring_cart_key)) {
        remove_action("woocommerce_cart_totals_after_order_total", array("WC_Subscriptions_Cart", "display_recurring_totals"), 10);
        remove_action("woocommerce_review_order_after_order_total", array("WC_Subscriptions_Cart", "display_recurring_totals"), 10);
      }
    }
    public function woocommerce_email_add_custom_thankyou($order, $sent_to_admin, $plain_text, $email) {
      if (in_array($email->id, ["customer_completed_order", "customer_invoice"])) {
        echo $this->get_order_custom_thankyou_html(["order_id" => $order->get_id(), "email" => "no"]);
      }
    }
    public function out_of_stock_show_sms_notify_form() {
      global $product;
      if (!$product->is_in_stock()) {
        echo do_shortcode("<div class='outofstock-notify'>[woo_ps_sms]</div>");
      }
      // remove_all_actions(current_action());
    }
    public function admin_init() {
      if (function_exists("WC") && isset(WC()->session)) {
        if (is_checkout()) {
          if ((string) $this->paypal_gateway_slug === (string) WC()->session->get("chosen_payment_method")) {
            setcookie("using_dollar", "yes", time() + DAY_IN_SECONDS * 7, "/");
          } else {
            setcookie("using_dollar", "no", time() + DAY_IN_SECONDS * 7, "/");
          }
        }
      }

      $defaults = [
        "prev_version"         => $this->version,
        "cur_version"          => $this->version,
        "mnstudio_db_version"  => $this->db_version,
        "debug"                => "no",
        "discount"             => "",
        "discount_cat"         => "",
        "favicon"              => home_url("/favicon.png"),
        "sms_queue_items"      => "4",
        "sms_queue_interval"   => "2",
        "gettext_replace"      => "",
        "str_replace"          => "",
        "diet_id"              => "91271920278",
        "pregnant_id"          => "91271927335",
        "diet_label"           => "دریافت رژیم غذایی یکماهه",
        "pregnant_label"       => "ویزیت آنلاین با ماما 15 دقیقه",
        "secure_link_secret"   => "310daa96727bbc07952f4193ef430484d2",
        "secure_video_base"    => "https://dl.minanaseh.com/secure/",
        "telegram_url"         => "https://t.me/MN_Studiio",
        "telegram_title"       => "Telegram Support",
        "paypal_gateway_slug"  => "bacs",
        "username_api"         => "",
        "password_secret"      => "",
        "sender_number"        => "",
        "server_url"           => "",
        "v2_setting"           => "",
        "fix_h3_title_to_p"    => "yes",
        "rest_pass_subject"    => "",
        "rest_pass_body"       => "",
        "currency_based_cache" => "no",
      ];
      add_option($this->setting_key, $defaults, "", "no");
      register_setting("mnstudio__general", $this->setting_key);

      if ($this->read("v2_setting") != $this->version) {
        foreach ($defaults as $slug => $val) delete_option("{$this->db_slug}__{$slug}");
        $this->set("v2_setting", $this->version);
      }
    }
    public function custom_admin_password_change_email($pass_change_email, $user, $userdata) {
      if (!empty($this->read("rest_pass_subject"))) {
        $pass_change_email["subject"] = $this->read("rest_pass_subject");
      }
      if (!empty($this->read("rest_pass_body"))) {
        $pass_change_email["message"] = do_shortcode($this->read("rest_pass_body"));
        $pass_change_email["message"] = str_replace(
          [ "###USER_EMAIL###", "###DISPLAY_NAME###", "###FIRST_NAME###", "###LAST_NAME###" ],
          [ $user["user_email"], $user["display_name"], $user["first_name"], $user["last_name"] ],
          $pass_change_email["message"]
        );
      }
      return $pass_change_email;
    }
    public function read($slug="", $default=NULL){
      $opt = get_option($this->setting_key, $default);
      return isset($opt[$slug]) ? $opt[$slug] : $default;
    }
    public function set($slug="", $value=""){
      $options = (array) get_option($this->setting_key);
      $options[$slug] = $value;
      update_option($this->setting_key, $options, "no");
    }
    public function template_redirect_dollar() {
      global $post;
      if (false === $this->use_currency_switch) {
        setcookie("using_dollar", "no", time() + DAY_IN_SECONDS * 7, "/");
      }
      if (isset($_GET["switch_currency"]) || isset($_GET["cs"])) {
        $currency = (isset($_GET["cs"]) && !empty($_GET["cs"])) ? $_GET["cs"] : ((isset($_GET["switch_currency"]) && !empty($_GET["switch_currency"])) ? $_GET["switch_currency"] : false);
        if ("IRT" == $currency) {
          $_COOKIE["using_dollar"] = "no";
          setcookie("using_dollar", "no", time() + DAY_IN_SECONDS * 7, "/");
        } elseif ("USD" == $currency) {
          $_COOKIE["using_dollar"] = "yes";
          setcookie("using_dollar", "yes", time() + DAY_IN_SECONDS * 7, "/");
        } else {
          if (isset($_COOKIE["using_dollar"]) && "yes" === (string) $_COOKIE["using_dollar"]) {
            $_COOKIE["using_dollar"] = "no";
            setcookie("using_dollar", "no", time() + DAY_IN_SECONDS * 7, "/");
          } else {
            $_COOKIE["using_dollar"] = "yes";
            setcookie("using_dollar", "yes", time() + DAY_IN_SECONDS * 7, "/");
          }
        }
        wp_redirect(remove_query_arg(["cs", "switch_currency"]));
        exit;
      }
      /* if ( function_exists("WC") && isset(WC()->session)) {
        if (is_checkout()) {
          if ((string) $this->paypal_gateway_slug === (string) WC()->session->get("chosen_payment_method")) {
            setcookie("using_dollar", "yes", time()+DAY_IN_SECONDS*7, "/");
          }else{
            setcookie("using_dollar", "no", time()+DAY_IN_SECONDS*7, "/");
          }
        }
        WC()->session->set("chosen_payment_method", NULL);
      } */
      if (!is_checkout() && (false === $this->use_currency_switch || false === $this->using_dollar())) {
        remove_filter("woocommerce_currency", array($this, "woocommerce_currency"));
        remove_filter("woocommerce_currency_symbol", array($this, "woocommerce_currency_symbol"));
        remove_filter("wc_get_price_decimals", array($this, "wc_get_price_decimals"));
        remove_filter("woocommerce_price_format", array($this, "woocommerce_price_format"));
        remove_filter("woocommerce_get_price_html", array($this, "woocommerce_variable_price_html"), 2, 2);
        remove_filter("woocommerce_is_purchasable", array($this, "woocommerce_is_purchasable"), 1, 2);
        remove_action("woocommerce_cart_item_price", array($this, "modify_cart_item_unit_price"), 10, 3);
        remove_action("woocommerce_cart_item_subtotal", array($this, "modify_cart_item_unit_price"), 10, 3);
        remove_action("woocommerce_before_calculate_totals", array($this, "woocommerce_before_calculate_totals"));
        remove_action("woocommerce_checkout_create_order_line_item", array($this, "checkout_create_order_line_item"), 10, 4);
        remove_filter("woocommerce_coupon_get_amount", array($this, "woocommerce_coupon_get_amount"), 88, 2);
      }
    }
    public function digits_to_english($postcode, $country) {
      $num_pairs = array("۰" => "0", "۱" => "1", "۲" => "2", "۳" => "3", "۴" => "4", "۵" => "5", "۶" => "6", "۷" => "7", "۸" => "8", "۹" => "9");
      return "IR" == $country ? strtr($postcode, $num_pairs) : $postcode;
    }
    public function woocommerce_currency($value) {
      if ($this->using_dollar() !== true) return $value;
      return "USD";
    }
    public function woocommerce_currency_symbol($value) {
      if ($this->using_dollar() !== true) return $value;
      return "$";
    }
    public function wc_get_price_decimals($value) {
      if ($this->using_dollar() !== true) return $value;
      return 2;
    }
    public function woocommerce_price_format($value) {
      if ($this->using_dollar() !== true) return $value;
      return '%1$s%2$s';
    }
    public function refresh_checkout_on_payment_methods_change() {
      if (is_checkout() && !is_wc_endpoint_url()) {
        wp_register_script('blackswan-wc-payment-checkout', '');
        wp_enqueue_script('blackswan-wc-payment-checkout');
        wp_add_inline_script('blackswan-wc-payment-checkout', '(function($){
          $("form.checkout").on("change",\'input[name^="payment_method"]\', function() {$("body").trigger("update_checkout");});
        })(jQuery);');
      }
    }
    public function refresh_checkout_on_province_change() {
      if (is_checkout()) {
        wp_register_script('blackswan-wc-payment-checkout-2', '');
        wp_enqueue_script('blackswan-wc-payment-checkout-2');
        wp_add_inline_script('blackswan-wc-payment-checkout-2', '(function($){
          $("form.checkout").on("change",\'[name^="billing_state"]\', function() {$("body").trigger("update_checkout");});
        })(jQuery);');
      }
    }
    public function get_currency($default = "IRR") {
      return (string) isset($_COOKIE["using_dollar"]) ? $_COOKIE["using_dollar"] : $default;
    }
    public function set_currency($currency = "IRR") {
      setcookie("using_dollar", $currency, DAY_IN_SECONDS, "/", home_url(), true);
    }
    public function filter_available_payment_gateways($gateways) {
      if (is_admin()) {
        return $gateways;
      }
      $gateways_original = $gateways;
      if (!isset(WC()->cart)) {
        return $gateways;
      }
      $has_only_course = true;
      foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if ($this->course_shipping_slug != $cart_item['data']->get_shipping_class()) {
          $has_only_course = false;
          break;
        }
      }
      if (!$has_only_course) {
        if (isset($gateways[$this->paypal_gateway_slug])) {
          unset($gateways[$this->paypal_gateway_slug]);
          add_filter("woocommerce_no_available_payment_methods_message", function () {
            return "خرید دلاری فقط برای کلاس‌ها امکان پذیر است. لطفا محصولات فیزیکی در سبد خرید خود را حذف کنید.";
          }, 10);
        }
      }
      if ($this->using_dollar()) {
        foreach ($gateways as $id => $data) {
          if ($id != $this->paypal_gateway_slug && $id != "WC_Payment4") {
            unset($gateways[$id]);
          }
        }
      } else {
        if (isset($gateways[$this->paypal_gateway_slug])) {
          unset($gateways[$this->paypal_gateway_slug]);
        }
      }
      return $gateways;
    }
    public function woocommerce_is_purchasable($is, $that) {
      if ($this->using_dollar() !== true) return $is;

      if ($that->get_shipping_class() != $this->course_shipping_slug) {
        return false;
      }

      if ($that->is_type("subscription")) {
        return $is;
      }
      if ($that->is_type("variable-subscription")) {
        return $is;
      }
      $price_regular = get_post_meta($that->get_id(), "_dollar_price_regular", true);
      $price_sale    = get_post_meta($that->get_id(), "_dollar_price_sale", true);
      if ("" === (string) $price_regular) {
        return false;
      }
      if ("" === (string) $price_regular && "" !== (string) $price_sale) {
        return false;
      }
      if ((float) $price_regular < (float) $price_sale) {
        return false;
      }
      return $is;
    }
    public function woocommerce_variable_price_html($price, $that) {
      if ($this->using_dollar() !== true) return $price;
      if ($this->different_course_checkout && $this->using_dollar() && (string) $that->get_shipping_class() != (string) $this->course_shipping_slug) return "";
      if ($that->is_type("variable") || $that->is_type("variable-subscription")) {
        $prices = $this->get_variation_prices(true, $that);
        if (empty($prices['price'])) {
          $price = apply_filters('woocommerce_variable_empty_price_html', '', $that);
        }
        else{
          $min_price     = current($prices['price']);
          $max_price     = end($prices['price']);
          $min_reg_price = current($prices['regular_price']);
          $max_reg_price = end($prices['regular_price']);
          if ($min_price !== $max_price) {
            $price = wc_format_price_range($min_price, $max_price);
          } elseif ($that->is_on_sale() && $min_reg_price === $max_reg_price) {
            $price = wc_format_sale_price(wc_price($max_reg_price), wc_price($min_price));
          } else {
            $price = wc_price($min_price);
          }
          return $price;
        }
      } elseif ("product" === $that->post_type || "product_variation" === $that->post_type || "variable-subscription" === $that->post_type) {
        $variation_id  = is_numeric($that) ? $that : $that->get_id();
        $price_regular = get_post_meta($variation_id, "_dollar_price_regular", true);
        $price_sale    = get_post_meta($variation_id, "_dollar_price_sale", true);
        if ((float)$price_sale > 0) {
          return wc_format_sale_price($price_regular, $price_sale);
        }
        return wc_price($price_regular);
      }
      return $price;
    }
    public function modify_cart_item_unit_price($product_subtotal, $cart_item, $cart_item_key) {
      if ($this->using_dollar() !== true) return $product_subtotal;
      if ($cart_item["line_subtotal"] > 0) {
        $variation_id  = isset($cart_item["variation_id"]) && $cart_item["variation_id"] > 0 ? $cart_item["variation_id"] : $cart_item["product_id"];
        $price_regular = get_post_meta($variation_id, "_dollar_price_regular", true);
        $price_sale    = get_post_meta($variation_id, "_dollar_price_sale", true);
        $new_price     = (float) ((string) $price_sale !== "" ? $price_sale : $price_regular);
        if (current_action() == "woocommerce_cart_item_subtotal") {
          $new_price *= $cart_item["quantity"];
        }
        return wc_price($new_price);
      }
      return $product_subtotal;
    }
    public function woocommerce_before_calculate_totals($cart_object) {
      if ($this->using_dollar() !== true) return $cart_object;
      if (is_admin() && !defined('DOING_AJAX')) return;
      foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product  = $cart_item['data'];
        $variation_id  = isset($cart_item["variation_id"]) && $cart_item["variation_id"] > 0 ? $cart_item["variation_id"] : $cart_item["product_id"];
        $price_regular = get_post_meta($variation_id, "_dollar_price_regular", true);
        $price_sale    = get_post_meta($variation_id, "_dollar_price_sale", true);
        $new_price     = (float) ($price_sale !== "" ? $price_sale : $price_regular);
        $product->set_price($new_price);
      }
    }
    public function checkout_create_order_line_item($item, $cart_item_key, $cart_item, $order) {
      if ($this->using_dollar() !== true) return;
      $variation_id  = isset($cart_item["variation_id"]) && $cart_item["variation_id"] > 0 ? $cart_item["variation_id"] : $cart_item["product_id"];
      $price_regular = get_post_meta($variation_id, "_dollar_price_regular", true);
      $price_sale    = get_post_meta($variation_id, "_dollar_price_sale", true);
      $new_price     = (float) ((string) $price_sale !== "" ? $price_sale : $price_regular);
      $item->update_meta_data("_currency", 'USD');
      $item->update_meta_data("_dollar_price", $new_price);
    }
    public function template_redirect_ld() {
      if (isset($_GET["continue_learning"]) && is_user_logged_in()) {
        $post = get_user_meta(get_current_user_id(), "_last_ld_lesson", true);
        $url  = get_permalink($post);
        wp_redirect($url);
        exit;
      }
    }
    public function add_learn_dash_continue() {
      global $post;
      if (isset($post->post_type) && "sfwd-lessons" === $post->post_type && is_user_logged_in()) {
        update_user_meta(get_current_user_id(), "_last_ld_lesson", $post->ID);
      }
    }
    public function add_extra_class_front() {
      $data = "/* extra advanced style -- by amirhp.com */";
      wp_enqueue_script('wc-cart-fragments');
      if (is_user_logged_in()) {
        $data .= ".hide-for-loggedin{display: none !important;}";
      } else {
        $data .= ".hide-for-loggedout, .hide-for-guest{display: none !important;}";
      }


      if (function_exists("get_field") && get_field("term_selector")) {
        $data .= "a.elementor-button.elementor-button-link[href='".get_field("term_selector")."']{
          background-color: var(--btn-accented-bgcolor) !important; color: #fff !important;
        }";
      }

      wp_register_style("mnstudio-advanced-css", "");
      wp_add_inline_style("mnstudio-advanced-css", $data);
      wp_enqueue_style("mnstudio-advanced-css");


      wp_register_script("mnstudio-advanced-js", "", ["jquery"], current_time("timestamp"));
      wp_add_inline_script("mnstudio-advanced-js", '
      (function($) {
        $(function() {
          setTimeout(function () {

            $(".woocommerce-form-login-toggle .pepro-open-login").off();
            $(".woocommerce-form-login-toggle .pepro-open-login").off("click");
            $(".woocommerce-form-login-toggle .pepro-open-login").off("tap");
            $(".login.woocommerce-form.woodmart-login-popup [name=redirect_to]").val(window.location.href);
            $(document).on("click tap", ".woocommerce-form-login-toggle .pepro-open-login", function(e){
              e.preventDefault();
              var me = $(this);
              // $(".peprodev-ultimate-profile-solution.peprodev-smart-btn.logged-out:visible").click();
              $(".login.woocommerce-form.woodmart-login-popup.hidden-form").toggle();
            });
          }, 500);
        });
      })(jQuery);
      ');
      wp_enqueue_script("mnstudio-advanced-js");
    }
    public function add_extra_class_admin($hook_suffix) {

      if ($this->str_starts_with($hook_suffix, "woocommerce_page_") && $this->fix_wc_backend_wrecked) {
        wp_enqueue_script("wc-blockUI", plugins_url("woocommerce/assets/js/jquery-blockui/jquery.blockUI.min.js"), array("jquery"), time());
        wp_enqueue_script("wc-tiptip", plugins_url("woocommerce/assets/js/jquery-tiptip/jquery.tipTip.min.js"), array("jquery"), time());
        wp_enqueue_script("wc-enhanced", plugins_url("woocommerce/assets/js/admin/wc-enhanced-select.min.js"), array("jquery"), time());
        wp_enqueue_script("wc-woocommerce_admin", plugins_url("woocommerce/assets/js/admin/woocommerce_admin.min.js"), array("jquery"), time());
        wp_enqueue_style("wc-admin-rtl", plugins_url("woocommerce/assets/css/admin-rtl.css"), array(), time(), "all");
      }

      $data = "/* extra advanced style for admin -- by amirhp.com */";
      if ("plugins.php" == $hook_suffix) {
        $data .= "[data-plugin*='blackswan-edits.php'] .plugin-description { direction: ltr; text-align: left; font-family: Calibri; }";
      }
      if (isset($_GET["page"]) && $_GET["page"] == "persian-woocommerce-sms-pro") {
        $data .= "
        .wrap.woocommerce.persian_woocommerce_sms { width: calc(100% - 3rem); margin: 0 !important; }
        .right-side-pwoo, .wrap.woocommerce.persian_woocommerce_sms+.right-side-pwoo { display: none !important; }
        ";
      }
      if ("tools_page_crontrol_admin_manage_page" == $hook_suffix) {
        $data .= ".crontrol_args.column-crontrol_args,.crontrol_next.column-crontrol_next { direction: ltr; font-family: Consolas; line-height: 1; vertical-align: middle; }";
      }
      if (current_user_can("administrator")) echo "<div style='display: none;' class='hook_suffix amirhp.com'>$hook_suffix</div>";
      wp_register_style("mnstudio-advanced-css", "");
      wp_add_inline_style("mnstudio-advanced-css", $data);
      wp_enqueue_style("mnstudio-advanced-css");
    }
    public function add_telegram_fab() {
      $data = "/* telegram floating button style -- by amirhp.com */";
      $data .= ".amirhp-com-telegram-fab { position: fixed; right: 30px; bottom: 30px; cursor: pointer; font-family: Arial, Helvetica, sans-serif; z-index: 999999999; text-align: center; line-height: 56px; font-size: 0; }";
      $data .= ".amirhp-com-telegram-fab a { width: 56px; height: 56px; border-radius: 50%; -webkit-border-radius: 50%; -moz-border-radius: 50%; box-shadow: 0px 6px 8px 2px rgba(0, 0, 0, 0.14); -webkit-box-shadow: 0px 6px 8px 2px rgba(0, 0, 0, 0.14); -moz-box-shadow: 0px 6px 8px 2px rgba(0, 0, 0, 0.14); display: block; background: #2aa7e8 url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100' width='48px'%3E%3Cpath d='M15.5,45.4C37,36,51.3,29.9,58.5,26.9c20.5-8.5,24.7-10,27.5-10c0.6,0,2,0.1,2.9,0.9c0.7,0.6,1,1.4,1,2c0.1,0.6,0.2,1.9,0.1,2.9c-1.1,11.6-5.9,39.9-8.3,53c-1,5.5-3.1,7.4-5,7.6c-4.3,0.4-7.5-2.8-11.7-5.5c-6.5-4.2-10.1-6.9-16.4-11c-7.3-4.8-2.6-7.4,1.6-11.7C51.1,53.7,70,36.5,70.4,35c0-0.2,0.1-0.9-0.3-1.3c-0.4-0.4-1.1-0.3-1.5-0.1c-0.7,0.1-11,7-31.1,20.5c-2.9,2-5.6,3-8,3c-2.6-0.1-7.7-1.5-11.5-2.7c-4.6-1.5-8.3-2.3-8-4.8C10.2,48.1,12,46.8,15.5,45.4z' fill='%23fff'/%3E%3C/svg%3E\") no-repeat 5px/40px; }";
      $data .= "@media only screen and (max-width: 500px) {.amirhp-com-telegram-fab {right: 20px !important;bottom: 50px !important;}}";
      wp_register_style("amirhp-com-telegram-fab", "");
      wp_add_inline_style("amirhp-com-telegram-fab", $data);
      wp_enqueue_style("amirhp-com-telegram-fab");
      ?>
      <div class="amirhp-com-telegram-fab"><a title="<?= $this->telegram_title; ?>" href="<?= $this->telegram_url; ?>" target="_blank"></a></div>
      <?php
    }
    public function pwoosms_shortcodes_list($shortcodes) {
      return "<strong>پیامک شخصی سازی شده دوره ها</strong><br>" .
        "<code>{order_custom_sms}</code> = توضیحات وارد شده<br>" . PHP_EOL . $shortcodes;
    }
    public function sms_body_after_replace($content, $order_id, $order, $all_product_ids, $vendor_product_ids) {
      if (!$order || !wc_get_order($order_id)) return $content;
      $purchased_list = array();
      foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
        if ($product_id && !empty($product_id) && get_post($product_id)) {
          $_custom_sms_content = get_post_meta($product_id, "_custom_sms_content", true);
          if ($_custom_sms_content && !empty($_custom_sms_content)) {
            $purchased_list[] = do_shortcode($_custom_sms_content);
          }
        }
      }
      foreach ($order->get_items() as $item_id => $item) {
        $_selected_var_id = $item->get_meta('_selected_var_id', true);
        $_saved_virtuals  = $item->get_meta('_saved_virtuals', true);
        if (!$_selected_var_id || empty($_selected_var_id)) continue;
        $_custom_sms_content = get_post_meta($product_id, "_custom_sms_content", true);
        if ($_custom_sms_content && !empty($_custom_sms_content)) {
          $purchased_list = [do_shortcode($_custom_sms_content)];
        }
      }
      if (!empty($purchased_list)) {
        $new_string = implode(PHP_EOL, $purchased_list);
        $content = str_replace("{order_custom_sms}", $new_string, $content);
      } else {
        $content = str_replace("{order_custom_sms}", "", $content);
      }
      return $content;
    }
    public function save_dollar_price_for_simple($post_id) {
      $product = wc_get_product($post_id);
      $product->update_meta_data("_dollar_price_regular", (isset($_POST["_dollar_price_regular"]) ? $_POST["_dollar_price_regular"] : ""));
      $product->update_meta_data("_dollar_price_sale", (isset($_POST["_dollar_price_sale"]) ? $_POST["_dollar_price_sale"] : ""));
      $product->save();
    }
    public function save_dollar_price_for_variation($variation_id, $i) {
      update_post_meta($variation_id, "_dollar_price_regular", (isset($_POST["_dollar_price_regular"][$i]) ? $_POST["_dollar_price_regular"][$i] : ""));
      update_post_meta($variation_id, "_dollar_price_sale", (isset($_POST["_dollar_price_sale"][$i]) ? $_POST["_dollar_price_sale"][$i] : ""));
    }
    public function printout_dollar_price_for_simple() {
      ?>
      <div class="options_group _blackswan_custom_tab show_if_simple show_if_external hidden">
        <?php
        $id = get_the_ID();
        woocommerce_wp_text_input(array(
          "label"             => __("Dollar Regular Price", $this->td),
          "id"                => "_dollar_price_regular",
          "value"             => $this->read_string($id, "_dollar_price_regular"),
          "type"              => "number",
          "custom_attributes" => ["min" => 0, "dir" => "ltr", "lang" => "en_US", "step" => "0.01"],
          "desc_tip"          => false,
        ));
        woocommerce_wp_text_input(array(
          "label"             => __("Dollar Sale Price", $this->td),
          "id"                => "_dollar_price_sale",
          "value"             => $this->read_string($id, "_dollar_price_sale"),
          "type"              => "number",
          "custom_attributes" => ["min" => 0, "dir" => "ltr", "lang" => "en_US", "step" => "0.01"],
          "desc_tip"          => false,
        ));
        ?>
      </div>
      <?php
    }
    public function printout_dollar_price_for_variation($loop, $variation_data, $variation) {
      ?>
      <div class="amirhpCom-dollar-price form-row form-row-full options">
        <div class="form-field form-row form-row-first">
          <?php
          woocommerce_wp_text_input(array(
          "label"             => __("Dollar Regular Price", $this->td),
          "id"                => "_dollar_price_regular",
          "name"              => "_dollar_price_regular[{$loop}]",
          "value"             => $this->read_string($variation->ID, "_dollar_price_regular"),
          "type"              => "number",
          "custom_attributes" => ["min" => 0, "dir" => "ltr", "lang" => "en_US", "step" => "0.01"],
          "desc_tip"          => false,
          ));
          ?>
        </div>
        <div class="form-field form-row form-row-last">
          <?php
          woocommerce_wp_text_input(array(
          "label"             => __("Dollar Sale Price", $this->td),
          "id"                => "_dollar_price_sale",
          "name"              => "_dollar_price_sale[{$loop}]",
          "value"             => $this->read_string($variation->ID, "_dollar_price_sale"),
          "type"              => "number",
          "custom_attributes" => ["min" => 0, "dir" => "ltr", "lang" => "en_US", "step" => "0.01"],
          "desc_tip"          => false,
          ));
          ?>
        </div>
      </div>
      <?php
    }
    public function woocommerce_coupon_add_dollar_price($coupon_id, $coupon) {
      woocommerce_wp_text_input(array(
        "label"    => __("Dollar Discount Price", $this->td),
        "id"       => "_dollar_price",
        "type"     => "number",
        "custom_attributes" => ["min" => 0, "dir" => "ltr", "lang" => "en_US", "step" => "0.01"],
        "desc_tip" => false,
      ));
    }
    public function woocommerce_coupon_save_dollar_price($id) {
      update_post_meta($id, "_dollar_price", isset($_REQUEST['_dollar_price']) ? $_REQUEST['_dollar_price'] : "0");
    }
    public function woocommerce_coupon_get_amount($value, $coupon) {
      if ($this->using_dollar() !== true) return $value;
      if ($coupon->is_type('fixed_cart')) {
        return (float) get_post_meta($coupon->get_id(), "_dollar_price", true);
      }
      return $value;
    }
    public function display_dollar_price_on_variation_header($variation) {
      $variation_id  = is_numeric($variation) ? $variation : $variation->ID;
      $price_regular = get_post_meta($variation_id, "_dollar_price_regular", true);
      $price_sale    = get_post_meta($variation_id, "_dollar_price_sale", true);
      $price         = wc_format_sale_price($price_regular, $price_sale);
      echo '<span style="font-size: smaller;float: inline-end;line-height: 3;margin-inline-end: 1rem;">' . $price . '</span>';
    }
    public function printout_options_content() {
      ?>
      <div class="options_group _blackswan_custom_tab show_if_simple show_if_variable show_if_external hidden">
        <?php
        woocommerce_wp_textarea_input(array(
          "label"             => "توضیحات صفحه تشکر خرید",
          "id"                => "_checkout_html",
          "type"              => "text",
          "desc_tip"          => false,
        ));
        woocommerce_wp_textarea_input(array(
          "label"             => "متن پیامک شخصی سازی شده",
          "id"                => "_custom_sms_content",
          "type"              => "text",
          "desc_tip"          => false,
        ));
        ?>
      </div>
      <?php
    }
    public function save_product_custom_tab($post_id) {
      $product = wc_get_product($post_id);
      $product->update_meta_data('_checkout_html', (isset($_POST['_checkout_html']) && !empty($_POST['_checkout_html']) ? wp_unslash($_POST['_checkout_html']) : ""));
      $product->update_meta_data('_custom_sms_content', (isset($_POST['_custom_sms_content']) && !empty($_POST['_custom_sms_content']) ? wp_unslash($_POST['_custom_sms_content']) : ""));
      $product->save();
    }
    public function get_order_custom_thankyou_html($atts = array(), $content = "") {
      global $post, $wp;
      $order = false;
      $atts = extract(shortcode_atts(array("order_id" => "", "email" => "yes",), $atts));
      ob_start();
      if (empty($order_id) && isset($wp->query_vars['order-received'])) {
        $order_id = absint($wp->query_vars['order-received']);
      }
      if (empty($order_id)) $order_id = $post->ID;
      if (!empty($order_id)) {
        $order = wc_get_order($order_id);
        if (!$order) return;
      }
      if (!$order) return;


      if ($this->paypal_gateway_slug == $order->get_payment_method() && $email == "yes") {
        echo do_shortcode("[html_block id='9127179053']");
      }

      $purchased_list = array();
      $found_bundled = false;
      // if (!$order->has_status('completed') || !$order->has_status('processing')) return;
      foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
        $bundled = "yes" === get_post_meta($product_id, "_is_bundle", true);
        if ($bundled) {
          $found_bundled = $product_id;
        }
      }
      foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
        if ($product_id && !empty($product_id) && get_post($product_id)) {
          $_checkout_html = get_post_meta($product_id, "_checkout_html", true);
          if ($_checkout_html && !empty($_checkout_html)) {
            if (false != $found_bundled) {
              if ($product_id == $found_bundled) {
                $purchased_list[] = "<div class='custom-checkout-message course-id-$product_id'>" . do_shortcode(nl2br($_checkout_html)) . "</div>";
              }
            } else {
              $purchased_list[] = "<div class='custom-checkout-message course-id-$product_id'>" . do_shortcode(nl2br($_checkout_html)) . "</div>";
            }
          }
        }
      }
      if (!empty($purchased_list)) {
      ?>
        <h2 class="thankyou-course-details__title">توضیحات:</h2>
        <div class="woocommerce-table--order-details wc-thankyou-course-details">
          <?= implode(PHP_EOL, $purchased_list); ?>
        </div>
      <?php
      }
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return $htmloutput;
    }
    public function show_paypal_custom_text($order) {
      if ($this->paypal_gateway_slug == $order->get_payment_method()) {
      ?>
        <div class="paypal-payment-info">
          <p><strong>به باشگاه آنلاین MN استودیو خوش آمدید.</strong></p>
          <p>ثبت نام شما تا ساعاتی دیگر نهایی می‌شود.</p>
          <p>لطفاً فیش خود را برای ما آپلود کنید. همکاران ما در بازه‌ی زمانی ۹ الی ۲۰ به وقت ایران، فیش را بررسی میکنند و دسترسی شما به ویدئوهای آموزشی، بعد از تایید فیش از طریق ایمیل به شما اطلاع رسانی می‌شود.</p>
        </div>
        <?php
      }
    }
    public function show_bought_course_details($order) {
      echo $this->get_order_custom_thankyou_html(["order_id" => $order->get_id()]);
    }
    public function force_ship_to_different_address($posted_data) {
      $posted_data['ship_to_different_address'] = false;
      return $posted_data;
    }
    public function force_checkout_digits_to_english($posted_data) {
      $posted_data_filter = array(
        "billing_phone",
        "billing_postcode",
        "billing_address_1",
        "billing_address_2",
        "shipping_phone",
        "shipping_postcode",
        "shipping_address_1",
        "shipping_address_2",
      );
      foreach ($posted_data_filter as $key) {
        if (isset($posted_data[$key])) {
          $posted_data[$key] = $this->digits_to_english($posted_data[$key], "IR");
        }
      }
      return $posted_data;
    }
    public function is_from_iran() {
      return isset($_SERVER['GEOIP_COUNTRY_CODE']) && "IR" == strtoupper($_SERVER['GEOIP_COUNTRY_CODE']) ? true : false;
    }
    public function remove_country_billing_fields($fields) {
      $has_course = false;
      $has_goods = false;
      foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if ($this->course_shipping_slug == $cart_item['data']->get_shipping_class()) {
          $has_course = true;
        }
        if ($this->course_shipping_slug != $cart_item['data']->get_shipping_class()) {
          $has_goods = true;
        }
      }
      if ($has_course && !$has_goods) {
        $fields['billing_city']['required'] = false;
        $fields['billing_country']['required'] = false;
        $fields['billing_state']['required'] = false;
        $fields['billing_address_1']['required'] = false;
        $fields['billing_address_2']['required'] = false;
        $fields['billing_postcode']['required'] = false;
      }
      return $fields;
    }
    public function filter_checkout_fields($fields) {
      $has_course = false;
      $has_goods = false;
      foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if ($this->course_shipping_slug == $cart_item['data']->get_shipping_class()) $has_course = true;
        if ($this->course_shipping_slug != $cart_item['data']->get_shipping_class()) $has_goods = true;
      }
      if ($has_course && !$has_goods) {
        unset($fields['billing']['billing_city']);
        unset($fields['billing']['billing_country']);
        unset($fields['billing']['billing_state']);
        unset($fields['billing']['billing_address_1']);
        unset($fields['billing']['billing_address_2']);
        unset($fields['billing']['billing_postcode']);
        unset($fields['billing']['billing_company']);
        unset($fields['shipping']['billing_city']);
        unset($fields['shipping']['billing_country']);
        unset($fields['shipping']['billing_state']);
        unset($fields['shipping']['billing_address_1']);
        unset($fields['shipping']['billing_address_2']);
        unset($fields['shipping']['billing_postcode']);
        unset($fields['shipping']['billing_company']);
        unset($fields['shipping']);
        add_filter("woocommerce_cart_needs_shipping", "__return_false");
        add_filter("woocommerce_cart_needs_shipping_address", "__return_false");
        add_filter("woocommerce_order_needs_shipping_address", "__return_false");
        add_filter("woocommerce_enable_order_notes_field", "__return_false");
        add_filter("woocommerce_default_address_fields", array($this, "remove_default_address_fields"));
      }
      if ($this->using_dollar() && $has_goods) {
        add_filter("woocommerce_cart_needs_shipping", "__return_false");
        add_filter("woocommerce_cart_needs_shipping_address", "__return_false");
        add_filter("woocommerce_order_needs_shipping_address", "__return_false");
      }
      return $fields;
    }
    public function cart_needs_shipping($value) {
      $has_course = false;
      $has_goods = false;
      $cart = WC()->cart;
      if (!$cart) return $value;
      foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if ($this->course_shipping_slug == $cart_item['data']->get_shipping_class()) $has_course = true;
        if ($this->course_shipping_slug != $cart_item['data']->get_shipping_class()) $has_goods = true;
      }
      if ($has_course && !$has_goods) return false;
      if ($this->using_dollar() && $has_goods) return false;

      $cart_total = $cart->get_cart_contents_total();
      if ($cart_total == 0) {
        $applied_coupons = $cart->get_applied_coupons();
        foreach ($applied_coupons as $coupon_code) {
          $coupon = new \WC_Coupon($coupon_code);
          if ($coupon && $coupon->get_free_shipping()) return false;
        }
      }
      return $value;
    }
    public function hide_shipping_condition_based($rates, $package) {
      $has_course = false;
      $has_goods = false;
      $array_neutral_shipping = [17, 18];
      $found_bundle = false;
      $found_bundle_only = true;

      foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

        if (isset($cart_item['_selected_var_id']) && !empty($cart_item['_selected_var_id'])) {
          $found_bundle = true;
        } else {
          $found_bundle_only = false;
        }

        if ($this->course_shipping_slug == $cart_item['data']->get_shipping_class()) {
          $has_course = true;
        }
        if ($this->course_shipping_slug != $cart_item['data']->get_shipping_class()) {
          $has_goods = true;
        }
      }

      if ($has_course && !$has_goods) {
        foreach ($rates as $rate_id => $rate_val) {
          if (!in_array((int) $rate_val->get_instance_id(), $array_neutral_shipping)) {
            unset($rates[$rate_id]);
          }
        }
      } else {
        foreach ($rates as $rate_id => $rate_val) {
          if (in_array((int) $rate_val->get_instance_id(), $array_neutral_shipping)) {
            unset($rates[$rate_id]);
          }
        }
      }
      if ($has_goods && $this->using_dollar()) {
        // return [];
      }

      if ($found_bundle && $found_bundle_only) {
        // ارسال با پست = 6,1
        foreach ($rates as $rate_id => $rate_val) {
          if (in_array((int) $rate_val->get_instance_id(), [6, 1])) {
            $rates[$rate_id]->cost = 0;
            $rates[$rate_id]->label .= " : <strong>رایگان</strong>";
          }
        }
      }

      // $cart = WC()->cart;
      // $cart_total = $cart->get_cart_contents_total();
      // if ($cart_total == 0) {
      //   $applied_coupons = $cart->get_applied_coupons();
      //   foreach ($applied_coupons as $coupon_code) {
      //     $coupon = new \WC_Coupon($coupon_code);
      //     if ($coupon && $coupon->get_free_shipping()) return false;
      //   }
      // }

      return $rates;
    }
    public function remove_default_address_fields($fields) {
      unset($fields['country']);
      unset($fields['state']);
      unset($fields['postcode']);
      unset($fields['address_1']);
      unset($fields['address_2']);
      unset($fields['city']);
      return $fields;
    }
    public function add_column_file_size($columns) {
      $columns['filesize'] = 'File Size';
      return $columns;
    }
    public function column_file_size($column_name, $media_item) {
      $filesize = filesize(get_attached_file($media_item));
      echo "<input type='hidden' value='$filesize' class='sort_size' />" . size_format($filesize, 2);
    }
    public function enqueue_admin_script($hook) {
      if ('upload.php' != $hook) return;
      wp_register_script('blackswan-dummy-js-header', '');
      wp_enqueue_script('blackswan-dummy-js-header');
      wp_add_inline_script('blackswan-dummy-js-header', '(function($) {$(function() {$("#the-list tr").sort((a, b) => {return (parseInt($(b).find("input.sort_size").val())) > parseInt($(a).find("input.sort_size").val()) ? 1 : -1; }).appendTo("#the-list");});})(jQuery);');
    }
    public static function str_starts_with(string $haystack, string $needle): bool {
      return 0 === strncmp($haystack, $needle, \strlen($needle));
    }
    public static function str_ends_with(string $haystack, string $needle): bool {
      return '' === $needle || ('' !== $haystack && 0 === substr_compare($haystack, $needle, -\strlen($needle)));
    }
    public function read_price_data(&$product, $for_display=false) {
      $prices_array = array(
        'price'         => array(),
        'regular_price' => array(),
        'sale_price'    => array(),
      );
      $variation_ids = $product->get_available_variations("objects");
      foreach ($variation_ids as $variation) {
        $variation_id = $variation->get_id();
        $regular_price = get_post_meta($variation_id, "_dollar_price_regular", true);
        $sale_price    = get_post_meta($variation_id, "_dollar_price_sale", true);
        $price         = (float) ((string) $sale_price !== "" ? $sale_price : $regular_price);
        // Skip empty prices.
        if ('' === $price) {
          continue;
        }
        // If sale price does not equal price, the product is not yet on sale.
        if ($sale_price === $regular_price || $sale_price !== $price) {
          $sale_price = $regular_price;
        }
        $prices_array['price'][$variation_id]         = wc_format_decimal($price, wc_get_price_decimals());
        $prices_array['regular_price'][$variation_id] = wc_format_decimal($regular_price, wc_get_price_decimals());
        $prices_array['sale_price'][$variation_id]    = wc_format_decimal($sale_price, wc_get_price_decimals());
      }
      return $prices_array;
    }
    protected function sort_variation_prices($prices) {
      asort($prices);
      return $prices;
    }
    public function get_variation_prices($for_display=false, $that=[]) {
      $prices = $this->read_price_data($that, $for_display);
      foreach ($prices as $price_key => $variation_prices) {
        $prices[$price_key] = $this->sort_variation_prices($variation_prices);
      }
      return $prices;
    }
    public function get_variation_regular_price( $min_or_max = 'min', $for_display = false, $that ) {
      $prices = $this->get_variation_prices( $for_display, $that );
      $price  = 'min' === $min_or_max ? current( $prices['regular_price'] ) : end( $prices['regular_price'] );
      return apply_filters( 'woocommerce_get_variation_regular_price', $price, $this, $min_or_max, $for_display );
    }
    public function get_variation_sale_price( $min_or_max = 'min', $for_display = false, $that ) {
      $prices = $this->get_variation_prices( $for_display, $that );
      $price  = 'min' === $min_or_max ? current( $prices['sale_price'] ) : end( $prices['sale_price'] );
      return apply_filters( 'woocommerce_get_variation_sale_price', $price, $this, $min_or_max, $for_display );
    }
    public function get_real_IP_address() {
      if (!empty($_SERVER["GEOIP_ADDR"])) {
        $ip = $_SERVER["GEOIP_ADDR"];
      } elseif (!empty($_SERVER["HTTP_X_REAL_IP"])) {
        $ip = $_SERVER["HTTP_X_REAL_IP"];
      } elseif (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
      } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
      } else {
        $ip = $_SERVER["REMOTE_ADDR"];
      }
      return $ip;
    }
    public function print_setting_input($data) {
      extract(wp_parse_args($data, array(
        "slug"        => "",
        "caption"     => "",
        "type"        => "text",
        "desc"        => "",
        "extra_html"  => "",
        "extra_class" => "",
      )));
      echo "<tr class='type-" . esc_attr($type) . " $extra_class " . sanitize_title($slug) . "'>
              <th scope='row'><label for='$slug'>$caption</label></th>
              <td><input
                      name='{$this->setting_key}[$slug]'
                      id='" . esc_attr($slug) . "'
                      type='" . esc_attr($type) . "'
                      placeholder='" . esc_attr($caption) . "'
                      title='" . esc_attr(sprintf(_x("Enter %s", "setting-page", $this->td), $caption)) . "'
                      value='" . esc_attr($this->read($slug)) . "'
                      class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " />
              <p class='description'>" . ($desc) . "</p></td></tr>";
    }
    public function print_setting_checkbox($data) {
      extract(wp_parse_args($data, array(
        "slug"        => "",
        "caption"     => "",
        "desc"        => "",
        "value"       => "yes",
        "extra_html"  => "",
        "extra_class" => "",
      )));
      echo "<tr class='type-checkbox $extra_class " . sanitize_title($slug) . "'>
              <th scope='row'><label for='$slug'>$caption</label></th>
              <td><input
                      name='{$this->setting_key}[$slug]'
                      id='" . esc_attr($slug) . "'
                      type='checkbox'
                      title='" . esc_attr(sprintf(_x("Enter %s", "setting-page", $this->td), $caption)) . "'
                      value='" . esc_attr($value) . "'
                      " . checked($value, $this->read($slug), false) . "
                      class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " />
              <p class='description'>" . ($desc) . "</p></td></tr>";
    }
    public function print_setting_select($data) {
      extract(wp_parse_args($data, array(
        "slug"        => "",
        "caption"     => "",
        "options"     => array(),
        "desc"        => "",
        "extra_html"  => "",
        "extra_class" => "",
      )));
      echo "<tr class='type-select $extra_class " . sanitize_title($slug) . "'>
              <th scope='row'><label for='$slug'>$caption</label></th>
              <td><select
                      name='{$this->setting_key}[$slug]'
                      id='" . esc_attr($slug) . "'
                      title='" . esc_attr(sprintf(_x("Choose %s", "setting-page", $this->td), $caption)) . "'
                      class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . ">";
      foreach ($options as $key => $value) {
        if ($key == "EMPTY") { $key = ""; }
        echo "<option value='" . esc_attr($key) . "' " . selected($this->read($slug), $key, false) . ">" . esc_html($value) . "</option>";
      }
      echo "</select><p class='description'>" . ($desc) . "</p></td></tr>";
    }
    public function print_setting_textarea($data) {
      extract(wp_parse_args($data, array(
        "slug"        => "",
        "caption"     => "",
        "style"       => "",
        "desc"        => "",
        "rows"        => "5",
        "extra_html"  => "",
        "extra_class" => "",
      )));
      echo "<tr class='type-textarea $extra_class" . sanitize_title($slug) . "'>
              <th scope='row'><label for='$slug'>$caption</label></th>
              <td><textarea
                      name='{$this->setting_key}[$slug]'
                      id='" . esc_attr($slug) . "'
                      placeholder='" . esc_attr($caption) . "'
                      title='" . esc_attr(sprintf(_x("Enter %s", "setting-page", $this->td), $caption)) . "'
                      rows='" . esc_attr($rows) . "'
                      style='" . esc_attr($style) . "'
                      class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " >" . ($this->read($slug)) . "</textarea>
              <p class='description'>" . ($desc) . "</p></td></tr>";
    }
    public function print_setting_editor($data) {
      extract(wp_parse_args($data, array(
        "slug"        => "",
        "caption"     => "",
        "options"     => array(),
        "desc"        => "",
        "extra_class" => "",
      )));
      $editor_settings = array_merge($options, array(
        'editor_height' => 150,    // (number) Editor height in pixels
        'media_buttons' => false,  // (bool) Whether to show the Add Media/other media buttons.
        'teeny'         => false,  // (bool) Whether to output the minimal editor config. Examples include Press This and the Comment editor. Default false.
        'tinymce'       => true,   // (bool|array) Whether to load TinyMCE. Can be used to pass settings directly to TinyMCE using an array. Default true.
        'quicktags'     => false,  // (bool|array) Whether to load Quicktags. Can be used to pass settings directly to Quicktags using an array. Default true.
        'editor_class'  => "",     // (string) Extra classes to add to the editor textarea element. Default empty.
        'textarea_name' => "{$this->setting_key}[$slug]",
      ));

      $editor_id = strtolower(str_replace(array('-', '_', ' ', '*'), '', $slug));
      echo "<tr class='type-editor $extra_class " . sanitize_title($slug) . "'>
      <th scope='row'><label for='$slug'>$caption</label></th><td>";
      wp_editor($this->read($slug), $editor_id, $editor_settings);

      echo "<p class='description'>" . ($desc) . "</p></td></tr>";
    }
  }
  add_action("plugins_loaded", function () {
    global $MNStudio_Pro;
    $MNStudio_Pro = new MNStudio_Pro;
  });
  add_action("doing_it_wrong_run", function($function_name, $message, $version){
    add_filter('doing_it_wrong_trigger_error', '__return_false', 9999, 3);
  }, 9999, 3);
}
/*##################################################
Lead Developer: [amirhp-com](https://amirhp.com/)
##################################################*/