<?php
/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Date Created: 2025/05/25 19:05:33
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/08/20 15:12:19
 */
use PigmentDev\MNStudio_Pro;
defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>BlackSwan MNStudio Pro :: Developed by Amirhp-com (<a href='https://amirhp.com/'>https://amirhp.com/</a>)</small>");
class class_sticky_add2cart extends MNStudio_Pro {
  protected $td = "mnstudio";
  protected $db_slug = "mnstudio";
  protected $setting_key = "mnstudio__general_settings";
  public function __construct() {
    parent::__construct(false);
    add_action("init", array($this, "add_sticky_bottom_add2cart_button"));
  }
  public function add_sticky_bottom_add2cart_button() {
    add_action('wp_footer', function () {
      global $product;
      if (!$product) return;

      // Check if variation_price_ui option is enabled
      $use_new_ui = ("yes" == $this->read("variation_price_ui"));
      ?>
      <style>
        @media (max-width: 768px) {
          .pigment-sticky-add2cart-bar {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100vw;
            z-index: 99999;
            background: #fff;
            box-shadow: 0 -2px 10px #0001;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 1rem;
            min-height: 64px;
            direction: ltr;
            font-family: var(--e-global-typography-1fb58e2-font-family) !important;
          }

          .pigment-sticky-add2cart-bar .sticky-price {
            flex: 1 1 33%;
            font-size: 1rem;
            font-weight: 500;
            color: #333;
            text-align: right;
            line-height: 1.5;
          }

          .pigment-sticky-add2cart-bar .sticky-btn {
            flex: 1 1 66%;
            text-align: right;
          }

          .pigment-sticky-add2cart-bar .sticky-btn button,
          .pigment-sticky-add2cart-bar .sticky-btn .single_add_to_cart_button {
            min-width: 120px;
            font-size: 14px;
            padding: 0.7em 1.5em;
            border-radius: 16px;
            color: #030712;
            font-weight: 500;
            padding: 16px 24px;
            direction: rtl;
          }

          .single_add_to_cart_button.button.alt::before {
            content: "";
            background: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cg clip-path='url(%23clip0_478_7879)'%3E%3Cpath d='M3 5.74609L5.02379 6.09636L5.96076 17.2593C6.03568 18.17 6.79849 18.8705 7.71211 18.8676H18.3263C19.199 18.8696 19.9395 18.2274 20.063 17.3634L20.9864 10.9826C21.0895 10.2695 20.5953 9.60783 19.8821 9.50469C19.8198 9.49593 5.34876 9.49107 5.34876 9.49107' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='M14.6271 14.049H11.4863' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='M7.23825 22.7452V22.8633M7.71701 22.7706C7.71701 23.0365 7.50125 23.2521 7.23531 23.2521C6.96937 23.2521 6.75391 23.0365 6.75391 22.7706C6.75391 22.5046 6.96937 22.2891 7.23531 22.2891C7.50125 22.2891 7.71701 22.5046 7.71701 22.7706Z' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='M18.2617 22.7452V22.8633M18.7405 22.7706C18.7405 23.0365 18.5247 23.2521 18.2588 23.2521C17.9928 23.2521 17.7773 23.0365 17.7773 22.7706C17.7773 22.5046 17.9928 22.2891 18.2588 22.2891C18.5247 22.2891 18.7405 22.5046 18.7405 22.7706Z' stroke='black' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/g%3E%3Cdefs%3E%3CclipPath id='clip0_478_7879'%3E%3Crect width='24' height='24' fill='white'/%3E%3C/clipPath%3E%3C/defs%3E%3C/svg%3E%0A") no-repeat center/cover;
            width: 24px;
            height: 24px;
            opacity: 1;
            position: relative;
            border-radius: 0 !important;
            vertical-align: middle;
            top: -3px;
            margin-inline-end: 12px;
          }

          .pigment-sticky-add2cart-bar .sticky-btn .single_add_to_cart_button.disabled {
            background: #ccc !important;
          }

          body.pigment-sticky-add2cart-active {
            padding-bottom: 70px !important;
          }

          .pigment-sticky-add2cart-bar .real-price {
            direction: rtl;
          }
        }
      </style>
      <script>
        (function($) {
          $(document).ready(function() {
            if (window.innerWidth > 768) return;

            var $form = $('.elementor-add-to-cart form.cart');
            if (!$form.length) return;

            var productType = '<?php echo esc_js($product->get_type()); ?>';
            var $btn = $form.find('.single_add_to_cart_button').first();
            var $prcs = $form.find('.price-wrapper-newui');
            var btnClasses = $btn.attr('class');
            var priceHtml = '';
            var priceText = '';
            var buttonHtml = $btn.length ? $btn.prop('outerHTML') : '';
            var useNewUI = <?php echo json_encode($use_new_ui); ?>;

            // Add marker class and hide original button
            $form.addClass('has-bottom-bar');
            $btn.hide();

            // Get initial price
            if (useNewUI) {
              if (productType === 'simple') {
                // For simple products, use .price-wrapper-newui if present
                var $priceEl = $form.find('.price-wrapper-newui').first();
                priceHtml = $priceEl.length ? $priceEl.html() : '';
                priceText = $priceEl.length ? $priceEl.text() : '';
              } else {
                // For variable products, use .price-wrapper-newui.var-id-XXX if selected, else fallback
                var variationId = $form.find('input.variation_id').val();
                if (variationId) {
                  var $priceEl = $form.find('.price-wrapper-newui.var-id-' + variationId).first();
                  priceHtml = $priceEl.length ? $priceEl.html() : '';
                  priceText = $priceEl.length ? $priceEl.text() : '';
                } else {
                  var $priceEl = $form.find('.price-wrapper-newui').first();
                  priceHtml = $priceEl.length ? $priceEl.html() : '';
                  priceText = $priceEl.length ? $priceEl.text() : '';
                }
              }
            } else {
              if (productType === 'simple') {
                var $priceEl = $form.find('.woocommerce-Price-amount').first();
                priceHtml = $priceEl.length ? $priceEl.parent().html() : '';
                priceText = $priceEl.length ? $priceEl.text() : '';
              } else {
                priceHtml = $form.find('.woocommerce-variation-price').html() || '';
                priceText = $form.find('.woocommerce-variation-price .woocommerce-Price-amount').text() || '';
              }
            }

            // Build sticky bar with copied button classes
            var $bar = $('<div class="pigment-sticky-add2cart-bar"></div>');
            $bar.append('<div class="sticky-price">' + (priceHtml || priceText) + '</div>');
            $bar.append('<div class="sticky-btn"><button type="submit" class="' + btnClasses + '">' + $btn.text() + '</button></div>');
            $('body').append($bar).addClass('pigment-sticky-add2cart-active');

            var $stickyBtn = $bar.find('button.single_add_to_cart_button');

            // Sync button click
            $stickyBtn.on('click tap', function(e) {
              if (productType === 'simple') {
                $btn.trigger('click');
              } else {
                var variationSelected = $form.find('input.variation_id').val();
                var $variationBtn = $form.find('.single_add_to_cart_button');
                if (!variationSelected || variationSelected === '0') {
                  $bar.find('.sticky-price').text('ابتدا یک گزینه را انتخاب کنید');
                  var formOffset = $form.offset().top - 80;
                  $('html,body').animate({
                    scrollTop: formOffset
                  }, 400);
                  e.preventDefault();
                  return false;
                } else if ($variationBtn.hasClass('disabled')) {
                  $stickyBtn.addClass('disabled');
                  e.preventDefault();
                  return false;
                } else {
                  $btn.trigger('click');
                }
              }
              e.preventDefault();
              return false;
            });

            // Listen for variation changes
            if (productType === 'variable') {
              $form.on('show_variation hide_variation found_variation', function(e, variation) {
                var $variationBtn = $form.find('.single_add_to_cart_button');
                var variationId = $form.find('input.variation_id').val();
                var $price;
                if (useNewUI && variationId) {
                  $price = $form.find('.price-wrapper-newui.var-id-' + variationId).html() || '';
                } else if (useNewUI) {
                  $price = $form.find('.price-wrapper-newui').first().html() || '';
                } else {
                  $price = $form.find('.woocommerce-variation-price').html() || '';
                }
                if (variationId && $price) {
                  setTimeout(function() {
                    $bar.find('.sticky-price').html($price);
                    if ($variationBtn.hasClass('disabled')) {
                      $stickyBtn.addClass('disabled');
                    } else {
                      $stickyBtn.removeClass('disabled');
                    }
                  }, 10);
                } else {
                  $bar.find('.sticky-price').text('ابتدا یک گزینه را انتخاب کنید');
                  $stickyBtn.removeClass('disabled');
                }
              });
              // Initial state
              var $variationBtn = $form.find('.single_add_to_cart_button');
              if (!$form.find('input.variation_id').val()) {
                if (useNewUI) {
                  var $priceEl = $form.find('.price-wrapper-newui').first();
                  $bar.find('.sticky-price').html($priceEl.length ? $priceEl.html() : 'ابتدا یک گزینه را انتخاب کنید');
                } else {
                  $bar.find('.sticky-price').text('ابتدا یک گزینه را انتخاب کنید');
                }
                // $stickyBtn.addClass('disabled');
              } else if ($variationBtn.hasClass('disabled') && $form.find('input.variation_id').val() != 0) {
                $stickyBtn.addClass('disabled');
              }
            }

            // Sync button state on page load
            if ($btn.hasClass('disabled')) {
              // $stickyBtn.removeClass('disabled');
            }

            // Hide sticky bar if not purchasable or out of stock
            if (!<?php echo json_encode($product->is_purchasable()); ?> || !<?php echo json_encode($product->is_in_stock()); ?>) {
              $bar.hide();
            }
          });
        })(jQuery);
      </script>
      <?php
    }, 99999);
  }
}
return new class_sticky_add2cart;
