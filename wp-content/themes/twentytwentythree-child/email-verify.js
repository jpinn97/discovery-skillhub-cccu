jQuery("#resend-verification-email-btn").on("click", function () {
  var user_id = um.user("ID"); // Get the current user ID using the Ultimate Member JavaScript API
  var data = {
    action: "send_verification_email", // Set the AJAX action
    user_id: user_id, // Pass the user ID as a parameter
  };

  jQuery.post(ajaxurl, data, function (response) {
    // Display a success message to the user
    alert("A new verification link has been sent to your email address.");
  });
});
