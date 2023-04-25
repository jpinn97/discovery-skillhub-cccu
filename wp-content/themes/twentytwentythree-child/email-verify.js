jQuery(document).ready(function ($) {
  $("#resend-verification-email-btn").on("click", function () {
    $.ajax({
      url: emailVerifyData.ajaxurl,
      method: "POST",
      data: {
        action: "send_verification_email_um",
      },
      success: function (response) {
        if (response.success) {
          alert("Verification email sent successfully!");
        } else {
          alert("Error: " + response.data.message);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log("Error: ", errorThrown);
        console.log("jqXHR: ", jqXHR);
        console.log("textStatus: ", textStatus);
        alert("An error occurred. Please try again later.");
      },
    });
  });
});
