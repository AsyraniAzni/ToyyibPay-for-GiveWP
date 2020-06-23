jQuery(function ($) {
  init_toyyibpay_meta();
  $(".toyyibpay_customize_toyyibpay_donations_field input:radio").on("change", function () {
    init_toyyibpay_meta();
  });

  function init_toyyibpay_meta() {
    if ("enabled" === $(".toyyibpay_customize_toyyibpay_donations_field input:radio:checked").val()) {
      $(".toyyibpay_api_key_field").show();
      $(".toyyibpay_category_code_field").show();
      $(".toyyibpay_name_field").show();
      $(".toyyibpay_description_field").show();
      $(".toyyibpay_payment_channel_field").show();
      $(".toyyibpay_collect_billing_field").show();
    } else {
      $(".toyyibpay_api_key_field").hide();
      $(".toyyibpay_category_code_field").hide();
      $(".toyyibpay_name_field").hide();
      $(".toyyibpay_description_field").hide();
      $(".toyyibpay_payment_channel_field").hide();
      $(".toyyibpay_collect_billing_field").hide();
    }
  }
});