jQuery(document).ready(function ($) {
  const loginForm = $(".um-form");
  const emailInput = $("#user_email-6");
  const passwordInput = $("#user_password-6");
  const loginButton = $("#um-submit-btn"); // Add a selector for the login button

  if (loginForm.length) {
    loginForm.unbind("submit").bind("submit", function (event) {
      if (!emailInput.val() || !passwordInput.val()) {
        event.preventDefault(); // cancel the form submission
        alert("Please fill in all required fields.");
        loginButton.removeAttr("disabled"); // Re-enable the login button
      }
    });
  } else {
    alert("loginForm not found");
  }
});
