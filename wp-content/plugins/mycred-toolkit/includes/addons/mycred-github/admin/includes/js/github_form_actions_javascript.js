jQuery(document).ready(function($) {
  $('#github_repositories').select2({
    placeholder: 'Select repositories '
  });
  $("#btn-refresh-repositories").click(function() {
    $("#btn-refresh-repositories").attr("disabled", true);
    $("#btn-refresh-repositories").append(
      `<div class="loader float-left"></div>`
    );
    jQuery.post(
      ajaxurl,
      {
        action: "mg_refresh_repositories_action"
      },
      function(response) {
        location.reload();
      }
    );
  });
  $("#btn-disconnect").click(function() {
    $("#btn-disconnect").attr("disabled", true);
    $("#btn-disconnect").append(`<div class="loader float-left"></div>`);
    jQuery.post(
      ajaxurl,
      {
        action: "mg_disconnect_action"
      },
      function(response) {
        location.reload();
      }
    );
  });

});

