jQuery(document).ready(function ($) {
  $("#resend-verification-email-btn").on("click", function () {
    $.ajax({
      url: ajaxurl,
      method: "POST",
      data: {
        action: "send_verification_email_um",
      },
      success: function (response) {
        if (response.success) {
          alert(response.data.message);
        } else {
          alert(response.data.message);
        }
      },
      error: function () {
        alert("An error occurred. Please try again later.");
      },
    });
  });
});
