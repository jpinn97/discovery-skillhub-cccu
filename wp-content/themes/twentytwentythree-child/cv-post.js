$(document).ready(function () {
  $("#cv-post").submit(function (event) {
    event.preventDefault(); // prevent the form from submitting normally

    // gather form data
    var formData = new FormData(this);

    // send AJAX request
    $.ajax({
      url: "cv_builder.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        // handle successful response from server
        console.log(response);
      },
      error: function (xhr, status, error) {
        // handle error response from server
        console.log(error);
      },
    });
  });
});
