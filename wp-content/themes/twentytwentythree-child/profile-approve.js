// In a custom JS file or a script tag in your theme
jQuery(document).ready(function ($) {
  $("body").on(
    "click",
    '.um-profile-edit-a[data-key="approve_profile"]',
    function (e) {
      e.preventDefault();

      var user_id = um_scripts.profile_id;

      $.ajax({
        url: um_scripts.ajaxurl,
        method: "POST",
        data: {
          action: "um_approve_profile",
          user_id: user_id,
          nonce: um_scripts.nonce,
        },
        success: function (response) {
          if (response.success) {
            alert("Profile approved successfully.");
            location.reload();
          } else {
            alert("Error: " + response.data.message);
          }
        },
      });
    }
  );
});
