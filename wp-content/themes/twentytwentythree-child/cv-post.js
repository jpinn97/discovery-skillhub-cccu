(function ($) {
  $(document).ready(function () {
    console.log("Document is ready");

    $(document).on("submit", "#cv-post", function (event) {
      console.log("Form submit event triggered");
      event.preventDefault(); // prevent the form from submitting normally

      // gather form data
      var formData = new FormData(this);

      // Add the action name to formData
      formData.append("action", "handle_cv_form_submission");

      // send AJAX request
      $.ajax({
        url: "/wp-admin/admin-ajax.php", // Use the WordPress AJAX URL
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          // handle successful response from server
          console.log(response);
          if (response.success) {
            alert("Your CV has been submitted successfully!");
          } else {
            alert("An Error Occurred! Please try again later.");
          }
        },
        error: function (xhr, status, error) {
          // handle error response from server
          console.log(error);
          alert("An Error Occurred! Please try again later.");
        },
      });
    });
  });
})(jQuery);
