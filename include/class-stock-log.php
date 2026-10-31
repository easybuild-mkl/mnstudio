<?php
use PigmentDev\MNStudio_Pro;
defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>BlackSwan MNStudio Pro :: Developed by Amirhp-com (<a href='https://amirhp.com/'>https://amirhp.com/</a>)</small>");

if (!function_exists('str_contains')) { function str_contains($haystack, $needle) { return $needle !== '' && mb_strpos($haystack, $needle) !== false; } }
if (!function_exists('str_starts_with')) { function str_starts_with($val, $str) { if(mb_substr($val, 0, mb_strlen($str)) == $str) { return true; } else { return false; } } }
if (!function_exists('str_ends_with')) { function str_ends_with($val, $str) { if(mb_substr($val, -mb_strlen($str), mb_strlen($str)) == $str) { return true; } else { return false; } } }

class stock_log extends MNStudio_Pro {
  protected $db_table;
  protected $assets_url;
  protected $allowed_tables;
  public function __construct() {
    global $wpdb;
    $this->assets_url     = plugins_url("/assets/", dirname(__FILE__));
    $this->db_table       = $wpdb->prefix . "mnstudio_cron";
    $this->db_table_sl    = $wpdb->prefix . "mnstudio_stock_log";
    $this->allowed_tables = [$this->db_table_sl];
    add_action("wp_ajax_{$this->db_slug}_sl", array($this, "handel_ajax_req"));
    add_action("mnstudio_stock_log_page", array($this, "db_container"));

    add_action("woocommerce_product_before_set_stock", array($this, "log_product_manual_hook"), 10, 1);
    add_action("woocommerce_variation_before_set_stock", array($this, "log_product_manual_hook"), 10, 1);
    add_action("woocommerce_subscription_before_set_stock", array($this, "log_product_manual_hook"), 10, 1);
    add_action("woocommerce_variable-subscription_before_set_stock", array($this, "log_product_manual_hook"), 10, 1);
    add_action("woocommerce_variable_subscription_before_set_stock", array($this, "log_product_manual_hook"), 10, 1);
    add_action("woocommerce_reduce_order_stock", array($this, "log_order_stock_reduced_hook"), 10, 1);
    add_action("woocommerce_restore_order_stock", array($this, "log_order_stock_restored_hook"), 10, 1);
    add_action("woocommerce_product_bulk_and_quick_edit", array($this, "log_temp_product_stock"), 10, 2);
    add_action("woocommerce_product_bulk_edit_save", array($this, "log_bulk_edit_stock_change_hook"), 20, 2);
  }
  public function log_temp_product_stock($post_id, $post){
		$request_data = $this->request_data();
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return $post_id; }
		// Don't save revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'product' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) { return $post_id; }
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( ! isset( $request_data['woocommerce_quick_edit_nonce'] ) || ! wp_verify_nonce( $request_data['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ) ) { return $post_id; }
    $product = wc_get_product( $post );
    if ($product) { set_transient("_mn_stock_{$product->get_id()}", $product->get_stock_quantity(), 60); }
  }
  public function log_product_manual_hook($product){
    if (is_a($product, 'WC_Product')) {
      $product_id = $product->get_id();
      $old_stock = get_post_meta($product_id, "_stock", true);
      $new_stock = $product->get_stock_quantity();
      $manage_stock = $product->managing_stock();
      if ($manage_stock && $old_stock !== false && $new_stock != $old_stock) {
        $source = (wp_is_rest_endpoint() ? "stock updated via REST API" : "stock updated manually via edit product");
        if (isset($_SERVER["SCRIPT_URL"]) && "/wp-json/wc/v3/products/batch" == $_SERVER["SCRIPT_URL"] && str_contains($_SERVER["HTTP_REFERER"], "/wp-admin/admin.php?page=stock-manager")) {
          $source = "stock updated via 'Stock Manager' plugin";
        }
        if (isset($_SERVER["SCRIPT_URL"]) && "/wp-admin/admin-ajax.php" == $_SERVER["SCRIPT_URL"] && str_contains($_SERVER["HTTP_REFERER"], "/wp-admin/edit.php?post_type=product")) {
          $source = "stock updated manually via quick edit";
        }
        $this->log_stock_change(current_action(), [
          "product_id" => $product_id,
          "details" => [
            "old_stock" => intval($old_stock),
            "new_stock" => intval($new_stock),
          ],
          "result" => $source,
        ]);
      }
    }
  }
  public function log_order_stock_reduced_hook($order) {
    foreach ($order->get_items() as $item) {
      $product_id = $item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id();
      $quantity = $item->get_quantity();
      $product = wc_get_product($product_id);
      $this->log_stock_change(false, [
        "order_id" => $order->get_id(),
        "product_id" => $product_id,
        "details" => [
          "reduced_quantity" => intval($quantity),
          "remaining_stock" => intval($product->get_stock_quantity()),
        ],
        "result" => 'stock reduced by wc-order',
      ]);
    }
  }
  public function log_order_stock_restored_hook($order) {
    foreach ($order->get_items() as $item) {
      $product_id = $item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id();
      $quantity = $item->get_quantity();
      $product = wc_get_product($product_id);
      $this->log_stock_change(false, [
        "order_id" => $order->get_id(),
        "product_id" => $product_id,
        "details" => [
          "restored_quantity" => intval($quantity),
          "remaining_stock" => intval($product->get_stock_quantity()),
        ],
        "result" => 'stock restored by wc-order',
      ]);
    }
  }
  public function log_bulk_edit_stock_change_hook($product) {
    $product = wc_get_product($product);
    if (!$product) return;
    $product_id = $product->get_id();
    $manage_stock = $product->managing_stock();
    $old_stock = get_transient("_mn_stock_{$product_id}");
    $new_stock = $product->get_stock_quantity();
    // if ($manage_stock && $old_stock !== false && $new_stock != $old_stock) {
      $this->log_stock_change("woocommerce_bulk_edit_save", [
        'product_id' => $product_id,
        'details' => [
          'old_stock' => intval($old_stock),
          'new_stock' => intval($new_stock),
        ],
        'result' => 'stock updated via bulk edit',
      ]);
      delete_transient("_mn_stock_{$product_id}");
    // }
  }

  protected function request_data() { return $_REQUEST; }
  /**
    * Unified function to log stock changes.
    *
    * @param string $trigger The event or hook that triggered the change.
    * @param array $data Details about the stock change.
    */
  public function log_stock_change($trigger=false, $data = []) {
    global $wpdb;
    $defaults = [
      'order_id'   => 'null',
      'product_id' => 'null',
      'user_id'    => get_current_user_id(),
      'refer'      => $trigger ? $trigger : current_action(),
      'details'    => [],
      'result'     => '',
      'extras'     => wp_json_encode($_SERVER),
    ];
    $log_data = wp_parse_args($data, $defaults);
    $log_data['details'] = json_encode($log_data['details']);
    $wpdb->insert($this->db_table_sl, $log_data, "%s");
  }
  public function pretty_print($_2d_array, $color="#0060df"){ return substr(rtrim(str_replace(["\n",'Array(', "[", "]", " => "], ['','', " <span style='color: #ccc'><br></span> <span style='color: {$color}'>", "</span>", ": "], print_r($this->format_array($_2d_array), true)), ")"), 43); }
  public function format_array($a){return array_map(function($b){return is_array($b)?$this->format_array($b):(is_bool($b)?($b?'true':'false'):$b);}, (array) $a);}

  public function db_container(){
    ob_start();
    wp_enqueue_script("jquery");
    wp_enqueue_style("my_font_awesome", "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css", array(), current_time( 'timestamp' ));
    wp_enqueue_style("persian-datepicker", "{$this->assets_url}css/persian-datepicker.css", array(), current_time("timestamp"));
    wp_enqueue_script("persian-date", "{$this->assets_url}js/persian-date.min.js", array("jquery"), current_time("timestamp"));
    wp_enqueue_script("persian-datepicker", "{$this->assets_url}js/persian-datepicker.js", array("jquery", "persian-date"), current_time("timestamp"));
    wp_enqueue_style("mnstudio_datatable", "{$this->assets_url}assets-db/css/jquery.dataTables.min.css", array(), current_time("timestamp"));
    wp_enqueue_script("mnstudio_datatable", "{$this->assets_url}assets-db/js/jquery.dataTables.min.js", array("jquery"), "1.10.21", true);
    wp_enqueue_script("mnstudio_SrchHighlt", "{$this->assets_url}assets-db/js/dataTables.searchHighlight.min.js", array("jquery"), "1.0.1", true);
    wp_enqueue_script("mnstudio_highlight.js", "{$this->assets_url}assets-db/js/highlight.js", array("jquery"), "3.0.0", true);
    wp_enqueue_script("mnstudio-general-jq-confirm", "{$this->assets_url}assets-db/js/jquery-confirm.js", array("jquery"), current_time("timestamp"));
    wp_enqueue_style("mnstudio-general-jq-confirm", "{$this->assets_url}assets-db/css/jquery-confirm.css", array(), current_time("timestamp"));
    wp_enqueue_style("mnstudio-backend", "{$this->assets_url}assets-db/css/backend.css");
    wp_enqueue_style("mnstudio-backend-raw", "{$this->assets_url}css/backend.css");
    if (is_rtl()) wp_add_inline_style("mnstudio-backend", "#wpbody-content { font-family: bodyfont, roboto, Tahoma; }");
    $can_delete = get_current_user_id() == 1237;
    ?>
    <div class="wrap">
      <h1 class="had"><span class='fas fa-cogs'></span>&nbsp;<strong style="font-weight: 700;"><?=__("WooCommerce Stock History", $this->td);?></strong></h1><br>
      <?php
      echo "<div class='hitools'>
              <a class='dt-button' id='advanced_search' href='#advanced_search'><i class='fas fa-search'></i>" . _x("Advanced Search", "setting-general", $this->td) . "</a>
              <a class='dt-button' id='select_all' href='#select_all'><i class='fas fa-check'></i>" . _x("Select All", "setting-general", $this->td) . "</a>
              <a class='dt-button' id='force_db_create' href='#'><i class='fas fa-database'></i>" . _x("Fix Database Structure", "setting-general", $this->td) . "</a>
              ".($can_delete?"<a class='dt-button' id='delete_selected' href='#delete_selected'><i class='fas fa-trash'></i>" . _x("Delete Selected", "setting-general", $this->td) . "</a>":"")."
              ".($can_delete?"<a class='dt-button' id='empty_db_truncate' data-refresh='true' href='#'><i class='fas fa-trash-alt'></i>" . _x("Empty Database", "setting-general", $this->td) . "</a>":"")."
            </div>";
      $defaults = apply_filters("$this->td/datatables_defaults", array(
          "table" => $this->db_table_sl,
          "default_per_page" => 25,
          "order_by" => "date_created",
          "table_headers" => array(
            "_sharp_id"     => _x("Mark/ID", "db-column", $this->td),
            "date_created"  => _x("Date Created", "db-column", $this->td),
            "order_id"      => _x("Order", "db-column", $this->td),
            "product_id"    => _x("Product", "db-column", $this->td),
            "user_id"       => _x("User", "db-column", $this->td),
            "details"       => _x("Extras", "db-column", $this->td),
            "refer"         => _x("Referer Hook", "db-column", $this->td),
            "result"        => _x("Result", "db-column", $this->td),
            "_sharp_action" => _x("Action", "db-column", $this->td),
          ),
          "table_search" => array(
            "date_created" => _x("Date Created", "db-column", $this->td),
            "order_id"     => _x("Order", "db-column", $this->td),
            "product_id"   => _x("Product", "db-column", $this->td),
            "user_id"      => _x("User", "db-column", $this->td),
            "refer"        => _x("Referer Hook", "db-column", $this->td),
            "result"       => _x("Result", "db-column", $this->td),
            "details"      => _x("Extras", "db-column", $this->td),
            "extras"       => _x("Technical Info", "db-column", $this->td),
          ),
          "item_val_parsing" => function ($obj, $header_key, $item_value) {
            $header_name = $item_value;
            $item_value = property_exists($obj, $header_key) ? $obj->$header_key : __("Not Specified!", $this->td);
            // if (empty(trim($item_value))) { return ""; }
            switch ($header_key) {
              case '_sharp_id':
                return "<label><input name='selected_id[]' autocomplete='off' value='$obj->id' type='checkbox'/> $obj->id</label>";
                break;
              case '_sharp_action':
                $can_delete = get_current_user_id() == 1237;
                $data = json_decode($obj->extras);
                $data = "<pre style='text-align: left; direction: ltr; overflow: auto;font-size: small;'>". $this->pretty_print($data) ."</pre>";
                $action = '
                <div class="dt-buttons">
                  <a href="javascript:;" class="dt-button popup--entry" data-name="'._x("Technical Info", "db-column", $this->td).'" data-content="' . esc_attr($data) . '" title="' . __("View", $this->td) . '"><i class="fas fa-fw fa-eye" style="margin: 0;"></i></a>
                  '.($can_delete ? '<a href="javascript:;" class="dt-button delete--entry" data-id="' . $obj->id . '" title="' . __("Delete entry", $this->td) . '"><i style="margin: 0 !important;" class="fas fa-fw fa-trash-alt"></i></a>' : "").'
                </div>';
                // $action .= '<a href="javascript:;" class="button button-primary btn-just-icon edit--entry" data-id="' . $obj->id . '" title="' . __("Edit entry", $this->td) . '"><i class="fas fa-pencil"></i> ' . __("Edit entry", $this->td) . '</a>';
                return $action;
                break;
              case 'customer_id':
              case 'user_id':
                if (empty($item_value)) return "";
                return $this->display_user($item_value, true, true, false);
              break;
              case 'product_id':
              case 'product':
                if (empty($item_value)) return "";
                if (!is_numeric($item_value) || $item_value < 1) return $item_value;
                $product = wc_get_product($item_value);
                $title = "#{$item_value}";
                $title_hover = "#{$item_value}";
                $url = admin_url("post.php?post=$item_value&action=edit");
                if ($product) {
                  $url = get_edit_post_link($product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id());
                  $title = $product->get_name();
                  $title_hover = $product->get_formatted_name();
                  $title_hover .= PHP_EOL . "Type: " . $product->get_type();
                }
                return "<a href='$url' target='_blank' title='".esc_attr(strip_tags($title_hover))."'>{$title}<br>(#{$item_value})</a>";
              break;
              case 'cur_qty':
                $product = wc_get_product($obj->product_id);
                if ($product) {
                  return $product->get_stock_quantity();
                }
                return "NULL";
              break;
              case 'order':
              case 'order_id':
                if (empty($item_value)) return "NULL";
                if (!is_numeric($item_value) || $item_value < 1) return "NULL";
                $url = admin_url("post.php?post=$item_value&action=edit");
                $order = wc_get_order($item_value); $title = "#{$item_value}";
                if ($order) {
                  $title = "#" . $order->get_order_number() . "<br>(" . $order->get_formatted_billing_full_name() . ")<br>" . $order->get_formatted_order_total();
                  $url = $order->get_edit_order_url();
                  return "<a href='$url' target='_blank' title='".esc_attr(strip_tags($title))."'>$title</a>";
                }
                return $item_value;
              break;
              case 'date_created':
              case 'scheduled':
              case 'date_sent':
                return !empty($item_value) ? '<span style="direction: ltr;unicode-bidi: plaintext;">' . date_i18n("Y-m-d H:i:s", strtotime($item_value)) . "</span>" : "";
              break;
              case 'price':
                return "<input type='hidden' value='$item_value' class='count-total-price' />" . wc_price($item_value);
              break;
              case 'refer':
                $value = $item_value;
                $product = wc_get_product(isset($obj->product_id) ? $obj->product_id : 0);
                $res = str_replace("woocommerce_", "<span style='color:#0060df;' title='".esc_attr($value)."'>wc_</span>", esc_html($value));
                if ($product) { $res .= "<br>type: " . $product->get_type(); }
                return $res;
                break;
              case 'extras':
                $data = json_decode($item_value);
                // $data = "<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>". print_r($data,1) ."</pre>";
                $data = "<pre style='text-align: left; direction: ltr; overflow: auto;font-size: small;'>". $this->pretty_print($data) ."</pre>";
                return '<div class="dt-buttons"><a href="javascript:;" class="dt-button popup--entry" data-name="'.$header_name.'" data-content="' . esc_attr($data) . '" title="' . __("View", $this->td) . '">' . __("View", $this->td) . '</a></div>';
              break;
              case 'details':
              // case 'result':
                $res = (array) json_decode($item_value);
                $product = wc_get_product(isset($obj->product_id) ? $obj->product_id : 0);
                if ($product) { $res["current_stock"] = $product->get_stock_quantity(); }
                return '<div>' . $this->pretty_print($res, "#0060df" /* info */) . '</div>';
              break;
              default:
                return esc_html($obj->$header_key);
              break;
            }
            return esc_html(property_exists($obj, $header_key) ? $obj->$header_key : "");
          },
          "item_tr_class" => "__return_empty_string",
          "item_td_class" => "__return_empty_string",
          "item_tr_fn" => "__return_empty_string",
        )
      );
      echo $this->dataTable($defaults);
      ?>
      </div>
      <script>
        jQuery.noConflict();
        (function($) {
          $(function() {
            $(document).on("click tap", "#toggle_success", function(e){
              e.preventDefault();
              var me = $(this);
              $("tr.status-success").toggle();
            });
          });
        })(jQuery);
      </script>
      <style>
        tr.qty-up {background-color: #e3fde3 !important;}
        tr.qty-down {background-color:#fde3e3 !important;}
        tr.status-success {background-color: #e3fde3 !important;}
        tr.status-duplicate {background-color:#f7fde3 !important;}
        tr.status-failed {background-color:#fde3e3 !important;}
      </style>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    echo $html;
  }
  public function handel_ajax_req() {
    check_ajax_referer($this->db_slug, "nonce");
    if (wp_doing_ajax() && $_POST["action"] == "{$this->db_slug}_sl") {
      if ($_POST["wparam"] === "{$this->db_slug}_sl") {
        global $wpdb;
        switch ($_POST["lparam"]) {

          #region database-related fns
          # database recreate
          case "db_recreate":
            $this->create_database(true);
            wp_send_json_success(array("msg" => __("Database successfully re-created and got fixed.", $this->td)));
          break;
          # database truncate
          case "clear_db":
          case "db_empty":
            global $wpdb;
            if (!isset($_POST["table"])) wp_send_json_error(array("msg" => __("An unknown error occurred.", $this->td),));
            $table = sanitize_text_field($_POST["table"]);
            if (!in_array($table, $this->allowed_tables)) wp_send_json_error(array("msg" => __("An unknown error occurred.", $this->td),));
            $del = $wpdb->query("TRUNCATE TABLE `$table`");
            if (false !== $del) {
              wp_send_json_success(array("table" => $table, "msg" => __("Database cleared successfully.", $this->td)));
            } else {
              wp_send_json_error(array("table" => $table, "msg" => __("Error clearing database!", $this->td) . ("<br>" . (string) $wpdb->last_error), "er" => $wpdb->last_error,));
            }
          break;
          # database remove item
          case "delete_item":
            if (!isset($_POST["dparam"]) || empty($_POST["dparam"]) || !is_numeric($_POST["dparam"])) {
              wp_send_json_error(array("msg" => __("An unknown error occurred.", $this->td),));
            }

            if (!isset($_POST["table"])) wp_send_json_error(array("msg" => __("An unknown error occurred.", $this->td),));
            $table = sanitize_text_field($_POST["table"]);
            if (!in_array($table, $this->allowed_tables)) wp_send_json_error(array("msg" => __("An unknown error occurred.", $this->td),));

            $del4db = $wpdb->delete($table, array('ID' => sanitize_text_field($_POST["dparam"])), array('%d'));
            if (false !== $del4db) {
              wp_send_json_success(array("msg" => __("Item successfully deleted", $this->td),));
            } else {
              wp_send_json_error(array("msg" => __("Could not remove item from database.", $this->td),));
            }
          break;
          # database remove multiple items
          case "delete_item_array":

            if (!isset($_POST["table"])) wp_send_json_error(array("msg" => __("An unknown error occurred.", $this->td),));
            $table = sanitize_text_field($_POST["table"]);
            if (!in_array($table, $this->allowed_tables)) wp_send_json_error(array("msg" => __("An unknown error occurred.", $this->td),));

            (array) $ids = (isset($_POST["dparam"]) && !empty($_POST["dparam"]) ? sanitize_post($_POST["dparam"]) : array());
            $ids = implode(',', array_map('absint', $ids));
            $res = $wpdb->query("DELETE FROM $table WHERE ID IN($ids)");
            if ($res != false) {
              wp_send_json_success(array("msg" => __("Items removed successfully.", $this->td),));
            } else {
              wp_send_json_error(array("msg" => __("There was a problem with your request.", $this->td),));
            }
          break;
          #endregion
          default:
            wp_send_json_error(array("msg" => __("Incorrect Data Supplied.", $this->td)));
          break;

        }
      }
      wp_send_json_error(array("msg" => __("Incorrect Data Supplied.", $this->td)));
    }
  }
  public function localize_script_data_table($args = array()){
    $defaults = array(
      "td"                    => "{$this->td}_sl",
      "wparam"                => "{$this->db_slug}_sl",
      "ajax"                  => admin_url("admin-ajax.php"),
      "home"                  => home_url(),
      "nonce"                 => wp_create_nonce($this->db_slug),
      "table"                 => $this->db_table_sl,
      "currency"              => get_woocommerce_currency_symbol(),
      "href"                  => admin_url("admin.php?page=mnstudio_stock_log"),
      "title"                 => _x("Select image file", "translate-js", $this->td),
      "btntext"               => _x("Use this image", "translate-js", $this->td),
      "clear"                 => _x("Clear", "translate-js", $this->td),
      "currentlogo"           => _x("Current preview", "translate-js", $this->td),
      "selectbtn"             => _x("Select image", "translate-js", $this->td),
      "tr_submit"             => _x("Submit", "js-string", $this->td),
      "tr_today"              => _x("Today", "js-string", $this->td),
      "errorTxt"              => _x("Error", "translate-js", $this->td),
      "cancelTtl"             => _x("Canceled", "translate-js", $this->td),
      "confirmTxt"            => _x("Confirm", "translate-js", $this->td),
      "successTtl"            => _x("Success", "translate-js", $this->td),
      "submitTxt"             => _x("Submit", "translate-js", $this->td),
      "okTxt"                 => _x("Okay", "translate-js", $this->td),
      "txtYes"                => _x("Yes", "translate-js", $this->td),
      "txtNop"                => _x("No", "translate-js", $this->td),
      "cancelbTn"             => _x("Cancel", "translate-js", $this->td),
      "edit"                  => _x("Edit Entry", "translate-js", $this->td),
      "copied"                => _x("Successfully copied to clipboard!", "translate-js", $this->td),
      "nosel"                 => _x("No Selection!", "translate-js", $this->td),
      "sendTxt"               => _x("Send to all", "translate-js", $this->td),
      "closeTxt"              => _x("Close", "translate-js", $this->td),
      "reloadTxt"             => _x("Reload page", "translate-js", $this->td),
      "deleteConfirmTitle"    => _x("Delete Confirmation", "translate-js", $this->td),
      "UnknownErr"            => _x("Unfortunately we encountered a technical problem, for more information check console", "translate-js", $this->td),
      "deleteConfirmation"    => _x("Are you sure you want to delete items <u><strong>ID #%s</strong></u> ? <red>THIS CANNOT BE UNDONE.", "translate-js", $this->td),
      "clearDBConfirmation"   => _x("Are you sure you want to clear all data from database? <red>THIS CANNOT BE UNDONE.", "translate-js", $this->td),
      "clearResetSettiConfrm" => _x("Are you sure you want to clear all saved settings and revert to default? <red>THIS CANNOT BE UNDONE.", "translate-js", $this->td),
      "clearDBConfTitle"      => _x("Clear Database", "translate-js", $this->td),
      "clearResetSettings"    => _x("Reset Settings", "translate-js", $this->td),
      "fixDBConfTitle"        => _x("Fix & Re-Create Database", "translate-js", $this->td),
      "fixDBConfirmation"     => _x("Are you sure you want to re-create and fix database?", "translate-js", $this->td),
      "attach"                => _x("Select / Upload image", "translate-js", $this->td),

      "str1"    => sprintf(_x("Logs Exported from server: %s", "translate-js", $this->td), home_url()),
      "str4"    => "logs-exported-" . date_i18n("YmdHis", current_time("timestamp")),
      "str2"    => sprintf(_x("%s Export", "translate-js", $this->td), $this->title),
      "str3"    => sprintf(_x("Exported at %s %s", "translate-js", $this->td), date_i18n(get_option('date_format'), current_time("timestamp")), date_i18n(get_option('time_format'), current_time("timestamp"))),
      "str5"    => sprintf(_x("Exported via %s at %s %s", "translate-js", $this->td), $this->title, date_i18n(get_option('date_format'), current_time("timestamp")), date_i18n(get_option('time_format'), current_time("timestamp"))),
      "str55"   => $this->title,
      "filter"  => _x("Filter ...", "data-table", $this->td),

      "tbl1"    => _x("No data available in table", "data-table", $this->td),
      "tbl2"    => _x("Showing _START_ to _END_ of _TOTAL_ entries", "data-table", $this->td),
      "tbl3"    => _x("Showing 0 to 0 of 0 entries", "data-table", $this->td),
      "tbl4"    => _x("(filtered from _MAX_ total entries)", "data-table", $this->td),
      "tbl5"    => _x("Show _MENU_ entries", "data-table", $this->td),
      "tbl6"    => _x("Loading...", "data-table", $this->td),
      "tbl7"    => _x("Processing...", "data-table", $this->td),
      "tbl8"    => _x("Search:", "data-table", $this->td),
      "tbl9"    => _x("No matching records found", "data-table", $this->td),
      "tbl10"    => _x("First", "data-table", $this->td),
      "tbl11"    => _x("Last", "data-table", $this->td),
      "tbl12"    => _x("Next", "data-table", $this->td),
      "tbl13"    => _x("Previous", "data-table", $this->td),
      "tbl14"    => _x(": activate to sort column ascending", "data-table", $this->td),
      "tbl15"    => _x(": activate to sort column descending", "data-table", $this->td),
      "tbl16"    => _x("Copy to clipboard", "data-table", $this->td),
      "tbl17"    => _x("Print", "data-table", $this->td),
      "tbl177"   => _x("Column visibility", "data-table", $this->td),
      "tbl18"    => _x("Export CSV", "data-table", $this->td),
      "tbl19"    => _x("Export Excel", "data-table", $this->td),
      "tbl20"    => _x("Export PDF", "data-table", $this->td),

    );
    $arguments = wp_parse_args($args, $defaults);
    return $arguments;
  }
  public function dataTable(array $args){
    ob_start();
    $defaults = apply_filters( "$this->td/datatables_defaults", array(
        "table"                  => $this->db_table_sl,
        "default_per_page"       => 50,
        "order_by"               => "date_created",
        "current_page_url"       => admin_url("admin.php?page=mnstudio_stock_log"),
        "current_page_name"      => $_GET["page"],
        "current_section"        => "",
        "table_headers"          => array("_sharp_id" => __('ID', $this->td),),
        "table_search"           => array(),
        "table_id"               => "exported_data",
        "table_class"            => "exported_data table table-striped table-bordered table-shopping",
        "thead_class"            => "",
        "thead_class"            => "",
        "paginate_btn_class"     => "button button-secondary",
        "paginate_btn_cur_class" => "button button-secondary",
        "paginate_prev"          => __('Previous', $this->td),
        "paginate_next"          => __('Next', $this->td),
        "paginate_class"         => "pagination",
        "database_empty"         => "<h3 style='font-weight: bold;'>" . __("Database is empty!", $this->td) . "</h3><h4>" . __("It seems there's nothing to show. please check your log setting and use the app to generate some logs.", $this->td) . "</h4>",
        "item_val_parsing"       => function ($obj, $header_key, $item_value) { return esc_html($item_value); },
        "item_td_class"     => function ($obj, $header_key, $item_value) { return ""; },
        "item_tr_class"     => function ($obj) { return ""; },
        "item_tr_fn"        => function ($obj) { return ""; },
      )
    );
    $arguments = wp_parse_args($args, $defaults);
    $args = extract($arguments);
    global $wpdb, $wp;
    $post_per_page = isset($_GET['per_page']) ? abs((int) $_GET['per_page']) : $default_per_page;
    $page          = isset($_GET['num']) ? abs((int) $_GET['num']) : 1;
    $offset        = ($page * $post_per_page) - $post_per_page;
    $total         = $wpdb->get_var("SELECT COUNT(1) FROM $table AS combined_table");
    $query         = "SELECT * FROM $table WHERE 1=1 ";
    $query_extra   = [];
    $notice        = "";
    $srchresultcnt = "";
    $noticepre     = "<p style='color: #0071a1;'><strong>" . __("Active Filters:", $this->td) . '</strong>';
    $noticepost    = "</p>";
    $relat         = (isset($_GET["relation"]) && "AND" == $_GET["relation"]) ? true : false;
    $exac          = (isset($_GET["exact"]) && "yes" == $_GET["exact"]) ? true : false;
    foreach ($table_search as $header => $header_value) {
      if (isset($_GET[$header]) && !empty($_GET[$header])) {
        $value = sanitize_text_field(trim($_GET[$header]));
        if ("user_id" == $header) {
          $query_extra[] = $wpdb->prepare("`$header` = %s", "$value");
        }
        elseif ("start_date" == $header) {
          $query_extra[] = $wpdb->prepare("CAST(`date_created` AS DATE) >= CAST('%s' AS DATE)", $value);
        }
        elseif ("end_date" == $header) {
          $query_extra[] = $wpdb->prepare("CAST(`date_created` AS DATE) <= CAST('%s' AS DATE)", $value);
        } else {
          $valfilter = $exac ? $value : "%$value%";
          $query_extra[] = $wpdb->prepare("`$header` LIKE %s", $valfilter);
        }
        $url = esc_url(remove_query_arg($header, add_query_arg($wp->query_vars, home_url($wp->request))));
        $notice .= "<br><a href='$url'><i class='fas fa-times-circle'></i></a> $header_value: $value";
      }
    }
    $notice = !empty($notice) ? $noticepre . $notice . $noticepost : $notice;
    if (!empty($query_extra)) {
      $query .= " AND " . ($relat ? implode(" AND ", $query_extra) : implode(" OR ", $query_extra));
    }
    $query .= " ORDER BY `$order_by` DESC LIMIT {$offset}, {$post_per_page}";

    $res_obj = $wpdb->get_results($query);

    wp_enqueue_script("{$this->db_slug}-dataTables", "{$this->assets_url}/assets-db/js/backend-data-tables.js", array('jquery'), null, true);
    wp_localize_script("{$this->db_slug}-dataTables", "_dt_object", $this->localize_script_data_table([
      "table" => $table,
    ]));
    $this->print_table_style();
    is_rtl() and wp_add_inline_style("{$this->db_slug}", "#wpbody-content { font-family: bodyfont, roboto, Tahoma; }");

    $items_per_page_selceter = "<select id='itemsperpagedisplay' data-total='$total' name='per_page' class='select' style='width:320px !important; margin: 0 0 .5rem .5rem; float: right;' title='" . __("Items per page", $this->td) . "' >
    <option value='50' " . selected(25, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 25) . "</option>
    <option value='50' " . selected(50, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 50) . "</option>
    <option value='100' " . selected(100, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 100) . "</option>
    <option value='200' " . selected(200, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 200) . "</option>
    <option value='300' " . selected(300, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 300) . "</option>
    <option value='400' " . selected(500, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 400) . "</option>
    <option value='500' " . selected(500, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 500) . "</option>
    <option value='600' " . selected(500, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 600) . "</option>
    <option value='700' " . selected(500, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 700) . "</option>
    <option value='800' " . selected(500, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 800) . "</option>
    <option value='900' " . selected(500, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 900) . "</option>
    <option value='1000' " . selected(1000, $post_per_page, false) . ">" . sprintf(_x("Show %s items per page", "items_per_page", $this->td), 1000) . "</option>
    <option disabled>-----------------</option>
    <option value='$total' " . selected($total, $post_per_page, false) . ">" . ($total > 1 ? sprintf(__("Show all %s items at once", $this->td), $total) : __("Show the only found data", $this->td)) . "</option>
    </select>";

    if (!empty($notice)) {
      echo "<script>var has_result = true;</script>";
      $srchresultcnt = sprintf(_n(", Search result: %d", ", Search results: %d", $total, $this->td), $wpdb->num_rows);
    }
    echo "<div id=\"search_form\" class=\"search_form closed\">";
    $relat     = (isset($_GET["relation"]) && "AND" == $_GET["relation"]) ? true : false;
    $exac      = (isset($_GET["exact"]) && "yes" == $_GET["exact"]) ? true : false;
    $srch      = "";
    $filtertwo = "";
    echo "<p><strong>" . __("Advanced Search in Database", $this->td) . "</strong></p>";
    if (!empty($table_search)) {
      echo "<input type='hidden' name='page' value='" . (isset($_GET['page']) ? $_GET['page'] : "{$this->db_slug}_setting") . "' />";
      echo "<div class='form-filters'>";
      $filter_two = '
      <div class="form-row finalrow">
        <label><strong>' . __("Exact Match: ", $this->td) . '</strong><bdi>On&nbsp;<input type="checkbox" id="mode_exact" required name="exact" ' . checked($exac, true, false) . ' value="yes" />&nbsp;Off</bdi></label>
        <label><strong>' . __("Relation: ", $this->td) . '</strong><bdi>AND&nbsp;<input type="checkbox" id="mode_relation" required name="relation" ' . checked($relat, true, false) . ' value="AND" />&nbsp;OR</bdi></label>
      </div>
      <div class="form-row finalrow">
        <a href="#submit_form_search_queue" class="dt-button"><i class="fas fa-search"></i>'.__("Search now", $this->td).'</a>
        <a href="'.$current_page_url.'" class="dt-button"><i class="fas fa-eraser"></i>'.__("Clear search", $this->td).'</a>
      </div>';

      foreach ($table_search as $key => $value) {

        $cur_value = (isset($_GET[$key]) && !empty($_GET[$key]) ? esc_attr(sanitize_text_field(trim($_GET[$key]))) : "");
        echo "<div class=\"form-row search_$key \">
                <div class=\"form-label\">
                  <label for=\"search_$key\">$value</label>
                </div>
                <div class=\"form-input {$key}_wrapper\" data-ref='#{$key}' data-date='$cur_value' >";
                  if ($key == "user_id"){
                    //
                  }
                  if ($key == "start_date") {
                    echo "<input type=\"text\" id=\"start_date\" name='$key' class='datepicker start_date' value='$cur_value' />";
                  }
                  elseif ($key == "end_date") {
                    echo "<input type=\"text\" id=\"end_date\" name='$key' class='datepicker end_date' value='$cur_value' />";
                  }
                  else{
                    echo "<input type=\"text\" id=\"search_$key\" name='$key' value='$cur_value' />";
                  }
          echo "</div>
        </div>";
      }
      echo $filter_two;
      echo "</div></div>";
    }
    ?>
    <style>
      .form-filters {
        display: grid;
        gap: 0.5rem 1rem;
        grid-template-rows: auto !important;
        grid-template-columns: 1fr 1fr 1fr !important;
      }
      .form-row {
        position: relative;
        grid-column: auto !important;
      }
    </style>
    <div class="data-table-wrap">
      <form action="<?= $current_page_url; ?>" id='mainform'>
        <input type="hidden" name="page" value="<?= $current_page_name; ?>" />
        <input type="hidden" name="num" value="<?= $page; ?>" />
        <?php
        if (!empty($wpdb->num_rows)) {
          $header = array_unique($table_headers);
          echo "<p class='numeral-details'><b>" . sprintf(_n("Only %s Record indexed", "%s Records indexed", $total, $this->td), $total) . "$srchresultcnt</b>   {$items_per_page_selceter}</p>";
          do_action("$this->td/datatables_before_table", $arguments);
          echo "<table border='1' id='$table_id' class='$table_class'>";
          $headingsrow = "<tr>";
          foreach ($header as $key => $value) {
            $extraClass = "";
            if ($this->str_starts_with($key, "_") || in_array($key, apply_filters("$this->td/datatables_no_export_columns", array("_sharp_id", "_action", "details", "result")))) {$extraClass = "noExport";}
            $headingsrow .= "<th class='item_th_{$key} $extraClass'>{$value}</th>";
          }
          $headingsrow .= "</tr>";
          echo "<thead class='$thead_class'>$headingsrow</thead><tbody>";
          foreach ($res_obj as $obj) {
            $item_tr_classes = call_user_func($item_tr_class, $obj);
            $item_tr_fns = call_user_func($item_tr_fn, $obj);
            echo "<tr class='item_tr_{$obj->id} $item_tr_classes' $item_tr_fns>";
            foreach ($header as $header_id => $header_name) {
              $val = call_user_func($item_val_parsing, $obj, $header_id, $header_name);
              $item_td_classs = call_user_func($item_td_class, $obj, $header_id, $header_name);
              echo "<td class='item_td_{$header_id} $item_td_classs'>{$val}</td>";
            }
            $template_body = "<template id='item_tr_{$obj->id}'>";
            $template_body .= "<div class='log5'>" . highlight_string("<?php " . var_export($obj, 1), 1) . "</div><style>.log5{text-align: left; direction: ltr; padding: 1rem; display: block; max-height: 500px; overflow: auto;z-index: 77777777777 !important;position: relative;background: white;} .log5 code {background: transparent !important;}</style>";
            $template_body .= "</template>";
            echo "$template_body</tr>";
          }

          echo "</tbody><tfoot class='$thead_class'>$headingsrow</tfoot></table>";
          do_action("$this->td/datatables_after_table", $arguments);
          do_action("$this->td/datatables_before_paginate", $arguments);
          echo '<div class="' . $paginate_class . '" style="margin-top: 1.5rem;display: block;">';
          echo paginate_links(array(
            'base' => add_query_arg('num', '%#%'),
            'format' => '',
            'show_all' => false,
            'mid_size' => 2,
            'end_size' => 2,
            'prev_text' => "<span class='$paginate_btn_class'>" . $paginate_prev . "</span>",
            'next_text' => "<span class='$paginate_btn_class'>" . $paginate_next . "</span>",
            'total' => ceil( (empty($srchresultcnt) ? $total : $wpdb->num_rows) / $post_per_page),
            'current' => $page,
            'before_page_number' => "<span class='$paginate_btn_cur_class'>",
            'after_page_number' => "</span>",
            'type' => 'list'
          ));
          echo "</div>";
          do_action("$this->td/datatables_after_paginate", $arguments);
        } else {
          do_action("$this->td/datatables_before_empty_table", $arguments);
          echo $database_empty;
          do_action("$this->td/datatables_after_empty_table", $arguments);
        }
        ?>
      </form>
    </div>
    <?php
    $return_html_data = ob_get_contents();
    ob_end_clean();
    return apply_filters("$this->td/datatables_return", $return_html_data, $return_html_data, $arguments);
  }
  protected function print_table_style(){
    if (is_rtl()) {
      $fC = ":last-child";
      $lC = ":first-child";
      echo "<style>body, p, input:not([dir=ltr]), button, h1, h2, h3, h4, h5, h6, textarea:not([dir=ltr]), p, .ui-widget {direction: rtl !important;}</style>";
    } else {
      $fC = ":first-child";
      $lC = ":last-child";
      echo "<style>body, p, input:not([dir=rtl]), button, h1, h2, h3, h4, h5, h6, textarea:not([dir=rtl]), p, .ui-widget {direction: ltr !important;}</style>";
    }
    echo "<style>
    .sechme {
      cursor: alias;
      -webkit-touch-callout: none;
      -webkit-user-select: none;
      -khtml-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }
    #exported_data_filter input[type=search] {
      width: 20rem;
    }
    .fixedHeader-floating thead{
      position: relative;
      top: 2rem;
    }
    table{
      border: solid #ccc 1px;
      -moz-border-radius: 6px;
      -webkit-border-radius: 6px;
    }
    table tr:hover {
      background: #fbf8e9;
      -o-transition: all 0.1s ease-in-out;
      -webkit-transition: all 0.1s ease-in-out;
      -moz-transition: all 0.1s ease-in-out;
      -ms-transition: all 0.1s ease-in-out;
      transition: all 0.1s ease-in-out;
    }
    .loadingdelete{
      background: url('data:image/gif;base64,R0lGODlhEAALAPQAAP////8AAP7a2v7Q0P7q6v4GBv8AAP4uLv6Cgv5gYP66uv4iIv5KSv6Kiv5kZP6+vv4mJv4EBP5OTv7m5v7Y2P709P44OP7c3P7y8v62tv6goP7Kyv7u7gAAAAAAAAAAACH+GkNyZWF0ZWQgd2l0aCBhamF4bG9hZC5pbmZvACH5BAALAAAAIf8LTkVUU0NBUEUyLjADAQAAACwAAAAAEAALAAAFLSAgjmRpnqSgCuLKAq5AEIM4zDVw03ve27ifDgfkEYe04kDIDC5zrtYKRa2WQgAh+QQACwABACwAAAAAEAALAAAFJGBhGAVgnqhpHIeRvsDawqns0qeN5+y967tYLyicBYE7EYkYAgAh+QQACwACACwAAAAAEAALAAAFNiAgjothLOOIJAkiGgxjpGKiKMkbz7SN6zIawJcDwIK9W/HISxGBzdHTuBNOmcJVCyoUlk7CEAAh+QQACwADACwAAAAAEAALAAAFNSAgjqQIRRFUAo3jNGIkSdHqPI8Tz3V55zuaDacDyIQ+YrBH+hWPzJFzOQQaeavWi7oqnVIhACH5BAALAAQALAAAAAAQAAsAAAUyICCOZGme1rJY5kRRk7hI0mJSVUXJtF3iOl7tltsBZsNfUegjAY3I5sgFY55KqdX1GgIAIfkEAAsABQAsAAAAABAACwAABTcgII5kaZ4kcV2EqLJipmnZhWGXaOOitm2aXQ4g7P2Ct2ER4AMul00kj5g0Al8tADY2y6C+4FIIACH5BAALAAYALAAAAAAQAAsAAAUvICCOZGme5ERRk6iy7qpyHCVStA3gNa/7txxwlwv2isSacYUc+l4tADQGQ1mvpBAAIfkEAAsABwAsAAAAABAACwAABS8gII5kaZ7kRFGTqLLuqnIcJVK0DeA1r/u3HHCXC/aKxJpxhRz6Xi0ANAZDWa+kEAA7AAAAAAAAAAAAPGJyIC8+CjxiPldhcm5pbmc8L2I+OiAgbXlzcWxfcXVlcnkoKSBbPGEgaHJlZj0nZnVuY3Rpb24ubXlzcWwtcXVlcnknPmZ1bmN0aW9uLm15c3FsLXF1ZXJ5PC9hPl06IENhbid0IGNvbm5lY3QgdG8gbG9jYWwgTXlTUUwgc2VydmVyIHRocm91Z2ggc29ja2V0ICcvdmFyL3J1bi9teXNxbGQvbXlzcWxkLnNvY2snICgyKSBpbiA8Yj4vaG9tZS9hamF4bG9hZC93d3cvbGlicmFpcmllcy9jbGFzcy5teXNxbC5waHA8L2I+IG9uIGxpbmUgPGI+Njg8L2I+PGJyIC8+CjxiciAvPgo8Yj5XYXJuaW5nPC9iPjogIG15c3FsX3F1ZXJ5KCkgWzxhIGhyZWY9J2Z1bmN0aW9uLm15c3FsLXF1ZXJ5Jz5mdW5jdGlvbi5teXNxbC1xdWVyeTwvYT5dOiBBIGxpbmsgdG8gdGhlIHNlcnZlciBjb3VsZCBub3QgYmUgZXN0YWJsaXNoZWQgaW4gPGI+L2hvbWUvYWpheGxvYWQvd3d3L2xpYnJhaXJpZXMvY2xhc3MubXlzcWwucGhwPC9iPiBvbiBsaW5lIDxiPjY4PC9iPjxiciAvPgo8YnIgLz4KPGI+V2FybmluZzwvYj46ICBteXNxbF9xdWVyeSgpIFs8YSBocmVmPSdmdW5jdGlvbi5teXNxbC1xdWVyeSc+ZnVuY3Rpb24ubXlzcWwtcXVlcnk8L2E+XTogQ2FuJ3QgY29ubmVjdCB0byBsb2NhbCBNeVNRTCBzZXJ2ZXIgdGhyb3VnaCBzb2NrZXQgJy92YXIvcnVuL215c3FsZC9teXNxbGQuc29jaycgKDIpIGluIDxiPi9ob21lL2FqYXhsb2FkL3d3dy9saWJyYWlyaWVzL2NsYXNzLm15c3FsLnBocDwvYj4gb24gbGluZSA8Yj42ODwvYj48YnIgLz4KPGJyIC8+CjxiPldhcm5pbmc8L2I+OiAgbXlzcWxfcXVlcnkoKSBbPGEgaHJlZj0nZnVuY3Rpb24ubXlzcWwtcXVlcnknPmZ1bmN0aW9uLm15c3FsLXF1ZXJ5PC9hPl06IEEgbGluayB0byB0aGUgc2VydmVyIGNvdWxkIG5vdCBiZSBlc3RhYmxpc2hlZCBpbiA8Yj4vaG9tZS9hamF4bG9hZC93d3cvbGlicmFpcmllcy9jbGFzcy5teXNxbC5waHA8L2I+IG9uIGxpbmUgPGI+Njg8L2I+PGJyIC8+CjxiciAvPgo8Yj5XYXJuaW5nPC9iPjogIG15c3FsX3F1ZXJ5KCkgWzxhIGhyZWY9J2Z1bmN0aW9uLm15c3FsLXF1ZXJ5Jz5mdW5jdGlvbi5teXNxbC1xdWVyeTwvYT5dOiBDYW4ndCBjb25uZWN0IHRvIGxvY2FsIE15U1FMIHNlcnZlciB0aHJvdWdoIHNvY2tldCAnL3Zhci9ydW4vbXlzcWxkL215c3FsZC5zb2NrJyAoMikgaW4gPGI+L2hvbWUvYWpheGxvYWQvd3d3L2xpYnJhaXJpZXMvY2xhc3MubXlzcWwucGhwPC9iPiBvbiBsaW5lIDxiPjY4PC9iPjxiciAvPgo8YnIgLz4KPGI+V2FybmluZzwvYj46ICBteXNxbF9xdWVyeSgpIFs8YSBocmVmPSdmdW5jdGlvbi5teXNxbC1xdWVyeSc+ZnVuY3Rpb24ubXlzcWwtcXVlcnk8L2E+XTogQSBsaW5rIHRvIHRoZSBzZXJ2ZXIgY291bGQgbm90IGJlIGVzdGFibGlzaGVkIGluIDxiPi9ob21lL2FqYXhsb2FkL3d3dy9saWJyYWlyaWVzL2NsYXNzLm15c3FsLnBocDwvYj4gb24gbGluZSA8Yj42ODwvYj48YnIgLz4K') no-repeat 50% 50%;
      display: none;
      width: 100%;
      height: 35px;
    }
    table td, table th {
      border-left: 1px solid #ccc;
      border-top: 1px solid #ccc;
      padding: 10px;
      text-align: left;
    }
    table th {
      background-color: #dce9f9;
      background-image: -webkit-gradient(linear, left top, left bottom, from(#ebf3fc), to(#dce9f9));
      background-image: -webkit-linear-gradient(top, #ebf3fc, #dce9f9);
      background-image:    -moz-linear-gradient(top, #ebf3fc, #dce9f9);
      background-image:     -ms-linear-gradient(top, #ebf3fc, #dce9f9);
      background-image:      -o-linear-gradient(top, #ebf3fc, #dce9f9);
      background-image:         linear-gradient(top, #ebf3fc, #dce9f9);
      border-top: none;
      text-shadow: 0 1px 0 rgba(255,255,255,.5);
    }
    table th, table td {
      text-align: center;
    }
    .pagination>ul{
      margin: 0 !important;
      padding: 0 !important;
      cursor: default;
    }
    .pagination>ul>li{
      display: inline-block;
      padding: 4px;
      margin: 0 !important;
    }
    .pagination {
      text-align: center;
      display: inline;
    }
    .pagination > ul > li > a.page-numbers,
    .pagination > ul > li > span.current{
      display: block;
      height: auto;
    }
    .pagination > ul > li > span.current *{
      color: gray;
      border-color: gray;
      pointer-events: none;
    }
    p[dir=ltr]{
      font-family: roboto, Arial !important;
      dir: ltr !important;
      text-align:left !important;
      padding : 12px;
    }
      </style>";
  }
}

return new stock_log;
