/*
* @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Date Created: 2021/07/30 00:17:35
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/02/12 22:57:05
 */
(function ($) {
  $(document).ready(function () {
    var _delivery_pro = null;
    var export_title = _dt_object.str2,
      export_title_html =
        "<style>h1{text-align: center !important;}body{padding: 1rem;}</style><h2 align='center'>" +
        _dt_object.str1 +
        "</span></h2>",
      export_subtitle = _dt_object.str3,
      export_filename = _dt_object.str4,
      export_excelsheetname = _dt_object.str6,
      export_footer = _dt_object.str5,
      export_footer_html = "<h2 align='center'>" + export_footer + "</h2>",
      errorTxt = _dt_object.errorTxt,
      cancelTtl = _dt_object.cancelTtl,
      confirmTxt = _dt_object.confirmTxt,
      successTtl = _dt_object.successTtl,
      submitTxt = _dt_object.submitTxt,
      okTxt = _dt_object.okTxt,
      closeTxt = _dt_object.closeTxt,
      cancelbTn = _dt_object.cancelbTn,
      sendTxt = _dt_object.sendTxt,
      titleTx = _dt_object.titleTx,
      expireNowE = _dt_object.expireNowE,
      txtYes = _dt_object.txtYes,
      txtNop = _dt_object.txtNop;

    jconfirm.defaults = {
      title: "",
      titleClass: "",
      type: "blue", // red green orange blue purple dark
      typeAnimated: true,
      draggable: true,
      dragWindowGap: 15,
      dragWindowBorder: true,
      animateFromElement: true,
      smoothContent: true,
      content: "",
      buttons: {},
      defaultButtons: {
        ok: {
          keys: ["enter"],
          text: okTxt,
          action: function () { },
        },
        close: {
          keys: ["enter"],
          text: closeTxt,
          action: function () { },
        },
        cancel: {
          keys: ["esc"],
          text: cancelbTn,
          action: function () { },
        },
      },
      contentLoaded: function (data, status, xhr) { },
      icon: "",
      lazyOpen: false,
      bgOpacity: null,
      theme: "modern",
      /*light dark supervan material bootstrap modern*/
      animation: "scale",
      closeAnimation: "scale",
      animationSpeed: 400,
      animationBounce: 1,
      rtl: $("body").is(".rtl") ? true : false,
      container: "body",
      containerFluid: false,
      backgroundDismiss: false,
      backgroundDismissAnimation: "shake",
      autoClose: false,
      closeIcon: null,
      closeIconClass: false,
      watchInterval: 100,
      columnClass: "m",
      boxWidth: "500px",
      scrollToPreviousElement: true,
      scrollToPreviousElementAnimate: true,
      useBootstrap: false,
      offsetTop: 40,
      offsetBottom: 40,
      bootstrapClasses: {
        container: "container",
        containerFluid: "container-fluid",
        row: "row",
      },
      onContentReady: function () { },
      onOpenBefore: function () { },
      onOpen: function () { },
      onClose: function () { },
      onDestroy: function () { },
      onAction: function () { },
      escapeKey: true,
    };

    if ($("#exported_data").length) {
      var table = $("#exported_data").DataTable({
        aaSorting: [[0, "desc"]],
        language: {
          emptyTable: _dt_object.tbl1,
          info: _dt_object.tbl2,
          infoEmpty: _dt_object.tbl3,
          infoFiltered: _dt_object.tbl4,
          infoPostFix: "",
          thousands: ",",
          lengthMenu: _dt_object.tbl5,
          loadingRecords: _dt_object.tbl6,
          processing: _dt_object.tbl7,
          search: _dt_object.tbl8,
          zeroRecords: _dt_object.tbl9,
          paginate: {
            first: _dt_object.tbl10,
            last: _dt_object.tbl11,
            next: _dt_object.tbl12,
            previous: _dt_object.tbl13,
          },
          aria: {
            sortAscending: _dt_object.tbl14,
            sortDescending: _dt_object.tbl15,
          },
        },
        select: true,
        paging: false,
        fixedHeader: true,
        responsive: false,
        searchHighlight: true,
        dom: "Bfrtip",
        fnInitComplete: function (oSettings, json) {
          $(".dt-button.buttons-excel").prepend(
            '<i style="margin-inline-end: 4px;" class="fa fa-file-excel"></i>'
          );
          $(".dt-button.buttons-csv").prepend(
            '<i style="margin-inline-end: 4px;" class="fa fa-file-csv"></i>'
          );
          $(".dt-button.buttons-copy").prepend(
            '<i style="margin-inline-end: 4px;" class="fa fa-copy"></i>'
          );
          $(".dt-button.buttons-print").prepend(
            '<i style="margin-inline-end: 4px;" class="fa fa-print"></i>'
          );
          $(".dt-button.buttons-collection").prepend(
            '<i style="margin-inline-end: 4px;" class="fa fa-columns"></i>'
          );
          $("#exported_data_filter label").css("font-size", "0");
          $("#exported_data_filter input").addClass("form-control").attr("placeholder", $("#exported_data_filter").text().replace(":", " ..."));

          calc_totals();
        },
        buttons: [
          {
            extend: "excel",
            text: _dt_object.tbl19,
            title: export_title,
            footer: false,
            header: true,
            sheetName: export_excelsheetname,
            messageTop: _dt_object.str1,
            messageBottom: export_footer + `\n--------------------------\n${$("thead .item_th_price").text()}: ${calc_totals()}`,
            filename: export_filename,
            exportOptions: {
              columns: "thead th:not(.noExport)",
            },
          },
          {
            extend: "csv",
            text: _dt_object.tbl18,
            bom: true,
            filename: export_filename,
            exportOptions: {
              columns: "thead th:not(.noExport)",
            },
          },
          {
            extend: "copy",
            footer: false,
            header: true,
            messageTop: export_title,
            title: _dt_object.str1,
            messageBottom: export_footer + `\n--------------------------\n${$("thead .item_th_price").text()}: ${calc_totals()}`,
            text: _dt_object.tbl16,
            exportOptions: {
              columns: "thead th:not(.noExport)",
            },
          },
          {
            extend: "print",
            text: _dt_object.tbl17,
            title: export_title,
            footer: false,
            header: true,
            messageTop: export_title_html,
            messageBottom:
              export_footer_html +
              `<style>h1, h2, h3 { font-size: 1.05rem; }</style><hr><h2 align='center'>${$("thead .item_th_price").text()}: ${calc_totals()}</h2>`,
            autoPrint: false,
            exportOptions: {
              columns: "thead th:not(.noExport)",
            },
          },
          { extend: "colvis", text: _dt_object.tbl177 },
        ],
      });
      table.on("draw", function () {
        var body = $(table.table().body());
        body.unhighlight();
        body.highlight(table.search());
        calc_totals();
      });
    }
    function calc_totals() {
      var $total_price = 0;
      $(".count-total-price").each(function (indexInArray, valueOfElement) {
        $total_price += parseFloat($(this).val());
      });
      var $total = (new Intl.NumberFormat('en-US')).format($total_price) + " " + _dt_object.currency;
      $("tfoot .item_th_price").html($total);
      return $total;
    }
    var $success_color = "rgba(21, 139, 2, 0.8)";
    var $error_color = "rgba(139, 2, 2, 0.8)";
    var $info_color = "rgba(2, 133, 139, 0.8)";
    if (!$("toast").length) {
      $(document.body).append($("<toast>"));
    }
    if (typeof has_result !== "undefined" && has_result === true) {
      $("#advanced_search").addClass("has-result");
      $("#search_form").removeClass("closed");
    }
    $(document).on("change", "#itemsperpagedisplay", function () {
      per_page = $(this).val();
      window.location.assign(`${_dt_object.href}&num=1&per_page=${per_page}`);

    });
    $(document).on("click tap", ".item_td__sharp_action .edit--entry", function (e) {
      e.preventDefault();
      var me = $(this);
      var id = me.data("id");
      var json = me.parents("tr").data("json");
      $("tr.item_tr_" + id).addClass("highlight");
      var jc = $.confirm({
        title: "<br>" + _dt_object.edit,
        content: $("#addnewshelfform").html(),
        onContentReady: function () {
          var form = this.$content.find(".addnewshelf");
          $(form).find("input.user_id").val(json.user_id).trigger("change");
          $(form).find("#user_dropdown").val(json.user_id).trigger("change");
          $(form).find("textarea.order_id").val(json.order_id).trigger("change");
          $(form).find("input.price").val(json.price).trigger("change");
          $(form).find("#user_dropdown").on("change", function (e) {
            $(form).find("input.user_id").val($(this).val()).trigger("change");
          });
          setTimeout(function () { $(form).find("input.barcode").select().focus(); }, 100);
        },
        boxWidth: "400px",
        type: "purple",
        icon: "fas fas fa-user-edit",
        closeIcon: false,
        animation: "scale",
        buttons: {
          no: {
            text: cancelbTn,
            btnClass: "btn-gray",
            keys: ["esc"],
            action: function () {
              $("tr.item_tr_" + id).removeClass("highlight");
              jc.close();
              return true;
            }
          },
          yes: {
            text: submitTxt,
            btnClass: "btn-purple",
            keys: ["enter"],
            action: function () {
              var form = this.$content.find(".addnewshelf");
              var user_id = $(form).find("input.user_id").val();
              var order_id = $(form).find("textarea.order_id").val();
              var price = $(form).find("input.price").val();

              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              $.ajax({
                type: 'POST',
                dataType: "json",
                url: _dt_object.ajax,
                data: {
                  action: _dt_object.td,
                  nonce: _dt_object.nonce,
                  table: _dt_object.table,
                  wparam: _dt_object.wparam,
                  id: id,
                  lparam: "edit_entry",
                  user_id: user_id,
                  order_id: order_id,
                  price: price,
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    show_modal_alert(_dt_object.successTtl, result.data.msg, 'fas fa-check-circle', 'green');
                    $(`#exported_data tbody tr.item_tr_${id} .item_td_date_created`).html(result.data.date);
                    $(`#exported_data tbody tr.item_tr_${id} .item_td_user_id`).html(result.data.user_name);
                    $(`#exported_data tbody tr.item_tr_${id} .item_td_order_id`).html(result.data.order_id);
                    $(`#exported_data tbody tr.item_tr_${id} .item_td_price`).html(result.data.price);
                    $(`#exported_data tbody tr.item_tr_${id}`).addClass("modified");
                    $(`#exported_data tbody tr.item_tr_${id}`).data("json", {
                      "date_created": result.data.date,
                      "user_id": result.data.user_id,
                      "order_id": result.data.order_id,
                      "price": result.data.price_raw
                    });
                  }
                  else {
                    show_modal_alert(_dt_object.errorTxt, result.data.msg, 'fas fa-exclamation-triangle', 'red');
                  }
                },
                error: function (result) {
                  jc.close();
                  console.error(result);
                  show_modal_alert(_dt_object.successTtl, _dt_object.UnknownErr, 'fas fa-check-circle', 'green');
                },
                complete: function (result) {
                  $("tr.item_tr_" + id).removeClass("highlight");
                  jc.close();
                }
              });
              return false;
            }
          },
        },
      });
    });
    $(document).on("click tap", ".item_td__sharp_action .raw--entry", function (e) {
      e.preventDefault();
      var me = $(this);
      var id = me.data("id");
      show_modal_alert("", $("tr.item_tr_" + id).data("json").log_action, "", "dark", "900px");
      // show_modal_alert("", $("template#item_tr_"+id).html(), "", "dark","900px")
    });
    $(document).on("click tap", ".dataTables_wrapper td .popup--entry", function (e) {
      e.preventDefault();
      var me = $(this);
      show_modal_alert(me.data("name"), me.data("content"), "", "dark", "900px");
    });
    $(document).on("click tap", ".item_td__sharp_action .delete--entry", function (e) {
      e.preventDefault();
      var me = $(this);
      var id = me.data("id");
      $("tr.item_tr_" + id).addClass("highlight");
      var jc = $.confirm({
        title: `<br>${_dt_object.deleteConfirmTitle}`,
        content: _dt_object.deleteConfirmation.replace(
          "%s",
          `<u><strong>${id}</strong></u>`
        ),
        icon: "fas fa-trash-alt",
        closeIcon: 0,
        type: "red",
        boxWidth: "600px",
        onContentReady: function () { },
        buttons: {
          no: {
            text: _dt_object.txtNop,
            btnClass: "btn-green",
            keys: ["esc"],
            action: function () {
              $("tr.item_tr_" + id).removeClass("highlight");
              return true;
              jc.close();
            },
          },
          yes: {
            text: _dt_object.txtYes,
            btnClass: "btn-red",
            keys: ["enter"],
            action: function () {
              jc.showLoading(true);
              if (_delivery_pro != null) { _delivery_pro.abort(); }
              _delivery_pro = $.ajax({
                type: "POST",
                dataType: "json",
                url: _dt_object.ajax,
                data: {
                  action: _dt_object.td,
                  nonce: _dt_object.nonce,
                  wparam: _dt_object.wparam,
                  table: _dt_object.table,
                  lparam: "delete_item",
                  dparam: id,
                },
                success: function (result) {
                  if (result.success === true) {
                    $("tr.item_tr_" + id).remove();
                    show_modal_alert(
                      _dt_object.successTtl,
                      result.data.msg,
                      "fas fa-check-circle",
                      "green"
                    );
                  } else {
                    show_modal_alert(
                      _dt_object.errorTxt,
                      result.data.msg,
                      "fas fa-exclamation-triangle",
                      "red"
                    );
                  }
                },
                error: function (result) {
                  show_modal_alert(
                    _dt_object.errorTxt,
                    _dt_object.error,
                    "fas fa-exclamation-triangle",
                    "red"
                  );
                },
                complete: function (result) {
                  $("tr.item_tr_" + id).removeClass("highlight");
                  jc.close();
                },
              });
              return false;
            },
          },
        },
      });
    }
    );
    $(document).on("click tap", "a[href='#advanced_search']", function (e) {
      e.preventDefault();
      $("#search_form").toggleClass("closed");
    });
    $(document).on("click tap", "a[href='#close_form_search']", function (e) {
      e.preventDefault();
      $("#search_form").addClass("closed");
    });
    $(document).on("click tap", "a[href='#submit_form_search']", function (e) {
      e.preventDefault();
      var user_dropdown = $("#search_form [name='user_dropdown']").val();
      var order_id = $("#search_form [name='order_id']").val();
      var price = $("#search_form [name='price']").val();
      var start_date = $("#search_form [name='start_date']").val();
      var end_date = $("#search_form [name='end_date']").val();
      var note = $("#search_form [name='note']").val();
      var exact = $("#search_form [name='exact']").prop("checked") ? "yes" : "";
      var relation = $("#search_form [name='relation']").prop("checked") ? "AND" : "";
      if (user_dropdown == 0) { user_dropdown = ""; }
      window.location.assign(`${_dt_object.href}&num=1&user_id=${user_dropdown}&order_id=${order_id}&price=${price}&note=${note}&start_date=${start_date}&end_date=${end_date}&exact=${exact}&relation=${relation}`);
    });
    $(document).on("click tap", "#search_form [href='#submit_form_search_queue']", function (e) {
      e.preventDefault();

      let baseUrl = _dt_object.href; // Base URL
      let params = new URLSearchParams();

      // Iterate over all input fields inside #search_form
      $("#search_form input, #search_form select").each(function () {
        let name = $(this).attr("name");
        let value = $(this).val();

        if ($(this).is(":checkbox")) {
          value = $(this).prop("checked") ? "yes" : "";
        }

        if (name && value) {
          params.append(name, value);
        }
      });

      // Construct final URL
      let finalUrl = `${baseUrl}&num=1&${params.toString()}`;

      // Redirect to the constructed URL
      window.location.assign(finalUrl);
    });
    $(document).on("click tap", "td.item_td_log_description", function (e) {
      e.preventDefault();
      var json = $(this).parents("tr").first().data("json");
      $.alert(json.log_description);
    });

    $(document).on("click tap", "#select_all", function (e) {
      e.preventDefault();
      if ($("input[name*=selected_id]").length) {
        var status = $("input[name*=selected_id]").first().prop("checked");
        var ids = $("input[name*=selected_id]")
          .prop("checked", !status)
          .trigger("change");
      } else {
        show_toast("No item in page to select", $error_color);
      }
    });
    $(document).on("click tap", "#delete_selected", function (e) {
      e.preventDefault();
      let me = $(this);
      var ids_array = [];
      var ids = $("input[name*=selected_id]:checked");
      if (ids.length < 1) {
        show_toast(_dt_object.nosel, $error_color);
        return false;
      }
      $(`tr.highlight`).removeClass("highlight");
      $.each(ids, function (index, val) {
        ids_array.push($(val).val());
        $(`tr.item_tr_${$(val).val()}`).addClass("highlight");
      });
      var jc = $.confirm({
        title: "<br>" + _dt_object.deleteConfirmTitle,
        content: _dt_object.deleteConfirmation.replace(
          "%s",
          `${ids_array.join(", ")}`
        ),
        boxWidth: "600px",
        icon: "fas fa-trash-alt",
        type: "red",
        closeIcon: false,
        animation: "scale",
        buttons: {
          no: {
            text: txtNop,
            btnClass: "btn-green",
            keys: ["n", "esc"],
            action: function () {
              $(`tr.highlight`).removeClass("highlight");
            },
          },
          yes: {
            text: txtYes,
            btnClass: "btn-red",
            keys: ["y", "enter"],
            action: function () {
              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              jc.setBoxWidth("400px");
              $.ajax({
                type: "POST",
                dataType: "json",
                url: _dt_object.ajax,
                data: {
                  action: _dt_object.td,
                  nonce: _dt_object.nonce,
                  wparam: _dt_object.wparam,
                  table: _dt_object.table,
                  lparam: "delete_item_array",
                  dparam: ids_array,
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    $.each(ids_array, function (index, val) {
                      $(`#exported_data .item_tr_${val}`).remove();
                    });
                    $.confirm({
                      title: successTtl,
                      content: result.data.msg,
                      icon: "fas fa-check-circle",
                      type: "green",
                      boxWidth: "400px",
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ["enter", "esc"],
                          action: function () { },
                        },
                      },
                    });
                  } else {
                    $.confirm({
                      title: errorTxt,
                      content: result.data.msg,
                      icon: "fa fa-exclamation-triangle",
                      type: "red",
                      boxWidth: "400px",
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ["enter", "esc"],
                          action: function () { },
                        },
                      },
                    });
                  }
                },
                error: function (result) {
                  jc.close();
                  $.confirm({
                    title: errorTxt,
                    content: _dt_object.UnknownErr,
                    icon: "fa fa-exclamation-triangle",
                    type: "red",
                    boxWidth: "400px",
                    buttons: {
                      close: {
                        text: closeTxt,
                        keys: ["enter", "esc"],
                        action: function () { },
                      },
                    },
                  });
                },
                complete: function (result) {
                  $(`tr.highlight`).removeClass("highlight");
                },
              });
              return false;
            },
          },
        },
      });
    });

    $(document).on("click tap", "#empty_db_truncate", function (e) {
      e.preventDefault();
      var me = $(this);
      var jc = $.confirm({
        title: "<br>" + _dt_object.clearDBConfTitle,
        content: _dt_object.clearDBConfirmation,
        boxWidth: "600px",
        icon: "fas fa-trash-alt",
        closeIcon: false,
        type: "red",
        animation: "scale",
        buttons: {
          no: {
            text: txtNop,
            btnClass: "btn-green",
            keys: ["n", "esc"],
            action: function () { },
          },
          yes: {
            text: txtYes,
            btnClass: "btn-red",
            keys: ["y", "enter"],
            action: function () {
              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              jc.setBoxWidth("400px");
              $.ajax({
                type: "POST",
                dataType: "json",
                url: _dt_object.ajax,
                data: {
                  action: _dt_object.td,
                  nonce: _dt_object.nonce,
                  table: _dt_object.table,
                  wparam: _dt_object.wparam,
                  lparam: "clear_db",
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    $.confirm({
                      title: successTtl,
                      content: result.data.msg,
                      icon: "fas fa-check-circle",
                      type: "green",
                      boxWidth: "400px",
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ["enter", "esc"],
                          action: function () {
                            window.location.href = _dt_object.href;
                          },
                        },
                      },
                    });
                  } else {
                    $.confirm({
                      title: errorTxt,
                      content: result.data.msg,
                      icon: "fa fa-exclamation-triangle",
                      type: "red",
                      boxWidth: "400px",
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ["enter", "esc"],
                          action: function () { },
                        },
                      },
                    });
                  }
                },
                error: function (e) {
                  console.error(e);
                  jc.close();
                  $.confirm({
                    title: errorTxt,
                    content: _dt_object.UnknownErr,
                    icon: "fa fa-exclamation-triangle",
                    type: "red",
                    boxWidth: "400px",
                    buttons: {
                      close: {
                        text: closeTxt,
                        keys: ["enter", "esc"],
                        action: function () { },
                      },
                    },
                  });
                },
                complete: function (e) { },
              });
              return false;
            },
          },
        },
      });
    });
    $(document).on("click tap", "#rest_settings_all", function (e) {
      e.preventDefault();
      var me = $(this);
      var jc = $.confirm({
        title: _dt_object.clearResetSettings,
        content: _dt_object.clearResetSettiConfrm,
        boxWidth: "600px",
        icon: "fas fa-trash-alt",
        closeIcon: false,
        type: "red",
        animation: "scale",
        buttons: {
          no: {
            text: txtNop,
            btnClass: "btn-green",
            keys: ["n", "esc"],
            action: function () { },
          },
          yes: {
            text: txtYes,
            btnClass: "btn-red",
            keys: ["y", "enter"],
            action: function () {
              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              jc.setBoxWidth("400px");
              $.ajax({
                type: "POST",
                dataType: "json",
                url: _dt_object.ajax,
                data: {
                  action: _dt_object.td,
                  nonce: _dt_object.nonce,
                  wparam: _dt_object.wparam,
                  lparam: "clear_settings",
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    $.confirm({
                      title: successTtl,
                      content: result.data.msg,
                      icon: "fas fa-check-circle",
                      type: "green",
                      boxWidth: "400px",
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ["enter", "esc"],
                          action: function () {
                            window.location.href = _dt_object.href;
                          },
                        },
                      },
                    });
                  } else {
                    $.confirm({
                      title: errorTxt,
                      content: result.data.msg,
                      icon: "fa fa-exclamation-triangle",
                      type: "red",
                      boxWidth: "400px",
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ["enter", "esc"],
                          action: function () { },
                        },
                      },
                    });
                  }
                },
                error: function (e) {
                  console.error(e);
                  jc.close();
                  $.confirm({
                    title: errorTxt,
                    content: _dt_object.UnknownErr,
                    icon: "fa fa-exclamation-triangle",
                    type: "red",
                    boxWidth: "400px",
                    buttons: {
                      close: {
                        text: closeTxt,
                        keys: ["enter", "esc"],
                        action: function () { },
                      },
                    },
                  });
                },
                complete: function (e) { },
              });
              return false;
            },
          },
        },
      });
    });
    $(document).on("click tap", "#force_db_create", function (e) {
      e.preventDefault();
      var me = $(this);
      var jc = $.confirm({
        title: "<br>" + _dt_object.fixDBConfTitle,
        content: _dt_object.fixDBConfirmation,
        boxWidth: "600px",
        icon: "fas fa-wrench",
        closeIcon: false,
        type: "purple",
        animation: "scale",
        buttons: {
          no: {
            text: txtNop,
            btnClass: "btn-green",
            keys: ["n", "esc"],
            action: function () { },
          },
          yes: {
            text: txtYes,
            btnClass: "btn-purple",
            keys: ["y", "enter"],
            action: function () {
              $(".jconfirm-closeIcon").hide();
              jc.showLoading(true);
              jc.setBoxWidth("400px");
              $.ajax({
                type: "POST",
                dataType: "json",
                url: _dt_object.ajax,
                data: {
                  action: _dt_object.td,
                  nonce: _dt_object.nonce,
                  wparam: _dt_object.wparam,
                  table: _dt_object.table,
                  lparam: "db_recreate",
                },
                success: function (result) {
                  jc.close();
                  if (result.success === true) {
                    $.confirm({
                      title: successTtl,
                      content: result.data.msg,
                      icon: "fas fa-check-circle",
                      type: "green",
                      boxWidth: "400px",
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ["enter", "esc"],
                          action: function () {
                            window.location.href = _dt_object.href;
                          },
                        },
                      },
                    });
                  } else {
                    $.confirm({
                      title: errorTxt,
                      content: result.data.msg,
                      icon: "fa fa-exclamation-triangle",
                      type: "red",
                      boxWidth: "400px",
                      buttons: {
                        close: {
                          text: closeTxt,
                          keys: ["enter", "esc"],
                          action: function () { },
                        },
                      },
                    });
                  }
                },
                error: function (e) {
                  console.error(e);
                  jc.close();
                  $.confirm({
                    title: errorTxt,
                    content: _dt_object.UnknownErr,
                    icon: "fa fa-exclamation-triangle",
                    type: "red",
                    boxWidth: "400px",
                    buttons: {
                      close: {
                        text: closeTxt,
                        keys: ["enter", "esc"],
                        action: function () { },
                      },
                    },
                  });
                },
                complete: function (e) { },
              });
              return false;
            },
          },
        },
      });
    });

    function show_toast(data = "Sample Toast!", bg = "", delay = 4500) {
      if (!$("toast").length) {
        $(document.body).append($("<toast>"));
      } else {
        $("toast").removeClass("active");
      }
      setTimeout(function () {
        $("toast")
          .css("--toast-bg", bg)
          .html(data)
          .stop()
          .addClass("active")
          .delay(delay)
          .queue(function () {
            $(this).removeClass("active").dequeue().off("click tap");
          })
          .on("click tap", function (e) {
            e.preventDefault();
            $(this).stop().removeClass("active");
          });
      }, 200);
    }
    function copy_clipboard(data) {
      var $temp = $("<input>");
      $("body").append($temp);
      $temp.val(data).select();
      document.execCommand("copy");
      $temp.remove();
    }

    function show_modal_alert(
      title = "",
      content = "",
      icon = "fas fa-info-circle",
      type = "blue",
      boxWidth = "400px",
      $fn = null,
      theme = "modern"
    ) {
      $.confirm({
        title: `<br>${title}`,
        content: content,
        icon: icon,
        /* light dark supervan material bootstrap modern */
        theme: theme,
        type: type,
        boxWidth: boxWidth,
        buttons: {
          close: {
            btnClass: "btn-" + type,
            text: _dt_object.closeTxt,
            keys: ["enter", "esc"],
            action: $fn,
          },
        },
      });
    }
    function scroll(e, of = 0) {
      $("html, body").animate(
        {
          scrollTop: e.offset().top - of,
        },
        500
      );
    }
  });
})(jQuery);
