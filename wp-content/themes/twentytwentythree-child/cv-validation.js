// Wait for jQuery to be loaded before running code
jQuery(document).ready(function ($) {
  // Load degree titles and subjects from external JSON file
  $.getJSON(information.degreesJSON, function (data) {
    // Populate title dropdown options
    var titleOptions = "";
    $.each(data.titles, function (index, title) {
      titleOptions += '<option value="' + title + '">' + title + "</option>";
    });

    // Populate degree title dropdown options
    var degreeTitleOptions = "";
    $.each(data.degree_title, function (index, degree_title) {
      degreeTitleOptions +=
        '<option value="' + degree_title + '">' + degree_title + "</option>";
    });
    $("#degree_title").append(degreeTitleOptions);

    // Populate degree subject dropdown options
    var subjectOptions = "";
    $.each(data.subject, function (index, subject) {
      subjectOptions +=
        '<option value="' + subject + '">' + subject + "</option>";
    });
    $("#subject").append(subjectOptions);

    // Populate university dropdown options
    var universityOptions = "";
    $.each(data.university, function (index, university) {
      universityOptions +=
        '<option value="' + university + '">' + university + "</option>";
    });
    $("#university").append(universityOptions);
  });

  // Enable searching of degree title dropdown
  $("#degree_title").select2({
    placeholder: "Select a degree title",
    allowClear: true,
    tags: true,
  });

  // Enable searching of degree subject dropdown
  $("#subject").select2({
    placeholder: "Select a degree subject",
    allowClear: true,
    tags: true,
  });

  // Enable searching of university dropdown
  $("#university").select2({
    placeholder: "Select a university",
    allowClear: true,
    tags: true,
  });

  // Add custom validation method for UK phone numbers
  $.validator.addMethod(
    "phoneUK",
    function (phone_number, element) {
      phone_number = phone_number.replace(/\s+/g, "");
      return (
        this.optional(element) ||
        (phone_number.length > 9 &&
          phone_number.match(/^(((\+44)? ?(\(0\))?|0)( ?[0-9]{3,4}){3})$/))
      );
    },
    "Please enter a valid UK phone number"
  );

  // Add custom validation method for file upload size
  $.validator.addMethod(
    "filesize",
    function (value, element, param) {
      return (
        this.optional(element) || element.files[0].size <= param * 1024 * 1024
      );
    },
    "File size must be less than {0} MB"
  );

  // Add form validation rules
  $("#cv-post").validate({
    rules: {
      email: {
        required: true,
        email: true,
      },
      phone: {
        required: true,
        phoneUK: true,
      },
      cv: {
        required: true,
        accept:
          "application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        filesize: 10, // Maximum file size in MB
      },
      coverletter: {
        accept:
          "application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        filesize: 10, // Maximum file size in MB
      },
    },
    messages: {
      email: {
        required: "Please enter your email address",
        email: "Please enter a valid email address",
      },
      phone: {
        required: "Please enter your phone number",
        phoneUK: "Please enter a valid UK phone number",
      },
      cv: {
        required: "Please upload your CV",
        accept: "File must be in PDF or DOC format",
        filesize: "File size must be less than {0} MB",
      },
      coverletter: {
        accept: "File must be in PDF or DOC format",
        filesize: "File size must be less than {0} MB",
      },
    },
  });
});
