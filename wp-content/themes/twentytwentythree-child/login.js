const loginForm = document.getElementById("um_form_6");
const emailInput = document.getElementById("user_email");
const passwordInput = document.getElementById("user_password");

loginForm.addEventListener("um-submit-btn", function(event) {
  if (!emailInput.value || !passwordInput.value) {
    event.preventDefault(); // cancel the form submission
    alert("Please fill in all required fields.");
  }
});
