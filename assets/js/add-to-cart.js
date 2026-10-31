/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Date Created: 2025/07/16 00:56:37
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/08/23 18:46:15
 */

jQuery.noConflict();
(function ($) {
  $(function () {
    const $sessions = $(".sticky-desktop select[name='attribute_pa_sessions']");
    const $beginner = $(".sticky-desktop .beiginner_checkbox");
    const $addBtn = $(".sticky-desktop .single_add_to_cart_button");
    const $priceBox = $(".sticky-desktop .woocommerce-variation-price, .sticky-desktop .price-single");
    if (!$addBtn.data("original-text")) {
      $addBtn.data("original-text", $addBtn.text());
      $addBtn.data("original-text", "افزودن به سبد خرید");
    }
    function checkDisabledCondition() {
      const isBeginner = $beginner.is(":checked");
      const session = $sessions.val();
      if (isBeginner && session === "4day") {
        $addBtn.prop("disabled", true).addClass("disabled").text("ناموجود");
        $priceBox.html('<span class="price out-of-stock">ناموجود</span>');
      } else {
        $addBtn.prop("disabled", false).removeClass("disabled").text($addBtn.data("original-text") || "ثبت نام کن");
      }
    }
    // $sessions.on("change", checkDisabledCondition);
    // $beginner.on("change", checkDisabledCondition);
    // checkDisabledCondition();
    $(document).on("click tap", ".toggle-open-cart", function (e) {
      e.preventDefault();
      $(".mobile-fixed-cart-wrapper").toggleClass("show");
    });
    setTimeout(function () {
      if ($(".woocommerce-variation.single_variation[role='alert']").length) {
        $(".woocommerce-variation.single_variation[role='alert']").insertAfter($(".sticky-desktop .elementor-last-divider").first());
      }
      if ($("select[name='attribute_pa_online-term']").length) {
        const $dropdown = $("select[name='attribute_pa_online-term']");
        const $checkbox = $(".beiginner_checkbox");
        // Function to update dropdown based on checkbox state
        const updateDropdown = () => {
          if ($checkbox.is(":checked")) {
            $dropdown.val("basic").trigger("change");
          } else {
            $dropdown.val("advance").trigger("change");
          }
        };
      }
    }, 500);

    $(document).on("click", ".ajax-quick-add-to-cart-session > a.elementor-button", function (e) {
      e.preventDefault();
      const $btn = $(this);
      const url = $btn.attr("href");
      const match = url.match(/[?&]add-to-cart=(\d+)/);
      if (!match) return;
      $btn.addClass("loading-spinner");
      const productId = match[1];
      const data = {
        "quantity": 1,
        "action": "woodmart_ajax_add_to_cart",
        "add-to-cart": productId,
      };
      $.post(wc_add_to_cart_params.ajax_url, data, function (response) {
        $btn.removeClass("loading-spinner");
        if (response && response.error && response.product_url) {
          window.location = response.product_url;
          return;
        }
        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, null]);
        // Optionally show a message or update cart UI here
      });
    });


  });
})(jQuery);
jQuery(function ($) {
  function isMobile() {
    return window.innerWidth <= 768;
  }
  function scrollToAddToCart() {
    var $form = $("form.cart:visible").first();
    if ($form.length) {
      $('html,body').animate({ scrollTop: $form.offset().top - 40 }, 400);
    }
  }
  function updateStickyBar() {
    if (!isMobile() || !$("form.cart").length) {
      $(".sticky-bottom-bar-add2cart").hide();
      return;
    }
    if (isMobile() && !$(".new-place-for-add2cart .sticky-desktop").length) {
      $(".sticky-desktop").appendTo(".new-place-for-add2cart");
    }
    $(".subscription-details").remove();
    var $product = window.wc_product || window.product || null;
    var $form = $("form.cart:visible").first();
    var productType = $form.data("product_type") || ($product && $product.type) || $("body").hasClass("product-type-variable") ? "variable" : "simple";
    var productId = $form.find('input[name="product_id"]').val() || $form.find('button[name="add-to-cart"]').val() || $("input#product_id").val() || window.product_id || window.wc_product_id || '';
    var priceHtml = '';
    var btnText = '';
    var isVariable = $("body").hasClass("product-type-variable") || $("body").hasClass("product-type-variable-subscription") || productType === "variable" || productType === "variable-subscription";
    if (isVariable) {
      // Try to get min price from variations
      var $minPrice = $(".woocommerce-variation-price .price, .price .amount, .summary .price .amount").first().html();
      if (!$minPrice) {
        $minPrice = $(".price .amount").first().html();
      }
      if (!$minPrice) {
        $minPrice = $(".price").first().html();
      }
      priceHtml = $minPrice ? "<span class='starting-price'>شروع از</span>" + $minPrice : "لطفا روز، کلاس یا تعداد جلسات را تغییر دهید.";
      btnText = "ثبت نام کلاس";

    } else {
      // Simple product
      var $price = $(".price-single").first().html() || $(".price").first().html();
      priceHtml = $price ? $price : "لطفا روز، کلاس یا تعداد جلسات را تغییر دهید.";
      btnText = "ثبت نام کلاس";
    }

    var $stickyBtn = $(".sticky-bottom-bar-add2cart .sticky-bar-add-to-cart-btn");
    // Listen for variation changes
    if (isVariable) {
      var $variationBtn = $form.find('.single_add_to_cart_button');
      var variationId = $form.find('input.variation_id').val();
      if (variationId) {
        setTimeout(function () {
          if ($variationBtn.hasClass('disabled')) {
            $stickyBtn.addClass('disabled');
          } else {
            $stickyBtn.removeClass('disabled');
          }
        }, 10);
      } else {
        $stickyBtn.removeClass('disabled');
      }
      // Initial state
      var $variationBtn = $form.find('.single_add_to_cart_button');
      if (!$form.find('input.variation_id').val()) {
        // $stickyBtn.addClass('disabled');
      } else if ($variationBtn.hasClass('disabled') && $form.find('input.variation_id').val() != 0) {
        $stickyBtn.addClass('disabled');
      }
    }

    $(".sticky-bottom-bar-add2cart .sticky-bar-price").html(priceHtml);
    $(".sticky-bottom-bar-add2cart .sticky-bar-add-to-cart-btn").text(btnText);
    if (isVariable && $form.find("table.variations tr").length <= 1 || $form.find("input.variation_id").val() != 0) { $(".starting-price").hide(); }
    $form.find('.single_add_to_cart_button').first().hide();
    $(".sticky-bottom-bar-add2cart").show();
  }
  if ($(".stock.out-of-stock.wd-style-default").length) {
    $(".sticky-bottom-bar-add2cart .sticky-bar-price").html("لطفا روز، کلاس یا تعداد جلسات را تغییر دهید.").addClass("out-of-stock");
    $(".sticky-bottom-bar-add2cart .sticky-bar-add-to-cart-btn").text("تکمیل ظرفیت").addClass("disabled").prop("disabled", true);
    $(".sticky-bottom-bar-add2cart").show();
  }
  // On click
  $(".sticky-bottom-bar-add2cart .sticky-bar-add-to-cart-btn").off("click").on("click", function (e) {
    e.preventDefault();
    var $form = $("form.cart:visible").first();
    var $btn = $form.find('.single_add_to_cart_button').first();
    var $stickyBtn = $(".sticky-bottom-bar-add2cart .sticky-bar-add-to-cart-btn");
    var $product = window.wc_product || window.product || null;
    var productType = $form.data("product_type") || ($product && $product.type) || $("body").hasClass("product-type-variable") ? "variable" : "simple";
    var productId = $form.find('input[name="product_id"]').val() || $form.find('button[name="add-to-cart"]').val() || $("input#product_id").val() || window.product_id || window.wc_product_id || '';
    var isVariable = $("body").hasClass("product-type-variable") || $("body").hasClass("product-type-variable-subscription") || productType === "variable" || productType === "variable-subscription";
    if ($(this).hasClass('disabled')) {
      var formOffset = $form.offset().top - 80;
      $('html,body').animate({ scrollTop: formOffset }, 400);
      e.preventDefault();
      return false;
    }
    /*
      if (isVariable) {
        scrollToAddToCart();
        return;
      }
      // Simple product: add to cart via AJAX
      var productId = $form.find('input[name="product_id"]').val() || $form.find('button[name="add-to-cart"]').val() || $("input#product_id").val() || window.product_id || window.wc_product_id || '';
      if (!productId) return;
      var qty = $form.find('input.qty').val() || 1;
      var data = { "quantity": qty, "action": "woodmart_ajax_add_to_cart", "add-to-cart": productId, };
      $.post(wc_add_to_cart_params.ajax_url, data, function (response) {
        if (response && response.error && response.product_url) {
          window.location = response.product_url;
          return;
        }
        // Trigger WooCommerce events
        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $form]);
        // Optionally show a message or update cart
      });
    */

    if (productType === 'simple') {
      $btn.trigger('click');
    } else {
      var variationSelected = $form.find('input.variation_id').val();
      var $variationBtn = $form.find('.single_add_to_cart_button');
      if (!variationSelected || variationSelected === '0') {
        $(".sticky-bottom-bar-add2cart .sticky-bar-price").html("لطفا روز، کلاس یا تعداد جلسات را تغییر دهید.").addClass("out-of-stock");
        var formOffset = $form.offset().top - 80;
        $('html,body').animate({ scrollTop: formOffset }, 400);
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


  // Update sticky bar on load and on variation change
  $(document).on("found_variation show_variation hide_variation change input", "form.cart", function () {
    setTimeout(updateStickyBar, 100);
  });
  $(window).on("resize orientationchange", function () {
    setTimeout(updateStickyBar, 100);
  });
  // Initial
  setTimeout(updateStickyBar, 300);
  $(".acf-weekly-plan").hide();
  function toggleWeeklyPlan() {
    var $form = $("form.cart:visible").first();
    var isVariable = $("body").hasClass("product-type-variable") || $("body").hasClass("product-type-variable-subscription");
    if (isVariable) {
      // Show only if a variation is selected
      var selected = $form.find('input.variation_id').val();
      if (selected && selected !== "0" && selected !== "") {
        $(".acf-weekly-plan").show();
      } else {
        $(".acf-weekly-plan").hide();
      }
    } else {
      // Simple product: always show
      $(".acf-weekly-plan").show();
    }
  }
  $(document).on("found_variation show_variation hide_variation change input", "form.cart", function () {
    setTimeout(toggleWeeklyPlan, 100);
  });
  $(window).on("resize orientationchange", function () {
    setTimeout(toggleWeeklyPlan, 100);
  });
  setTimeout(toggleWeeklyPlan, 300);
});