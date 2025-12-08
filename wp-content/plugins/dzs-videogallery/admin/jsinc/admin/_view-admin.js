export function dzs_admin_addWarningForUpdate() {

  var $ = jQuery;
  if ($('#welcome-panel').length) {


    var _c = $('#welcome-panel');


    _c.before('<div id="welcome-panel" class="dzsvg-update-available welcome-panel">\n' +
      '<input type="hidden" id="welcomepanelnonce" name="welcomepanelnonce" value="5855c9d8b6">\t\t<a class="welcome-panel-close" href="#" aria-label="Dismiss the welcome panel">Dismiss</a>\n' +
      '\t\t\t<div class="welcome-panel-content"><a class="update-available" href="admin.php?page=dzsvg-autoupdater" aria-label="Update video gallery">Update available</a> for <strong>Video Gallery WordPress</strong>\n' +

      '\t</div>\n' +
      '\t\t</div>')


    return;
  }



  if ($('tr[data-slug=dzs-video-gallery]').length) {

    var _c = $('tr[data-slug=dzs-video-gallery]').eq(0);

    _c.find('.row-actions').after('<div class="row-actions visible"><span class="deactivate"><a class="update-available" href="admin.php?page=dzsvg-autoupdater" aria-label="Update video gallery">Update available</a></span></div>');
  } else {


    if (jQuery('.version-number').length) {
      jQuery('.version-number').append('<span class="new-version info-con" style="width: auto;"> <span class="new-version-text">/ new version ' + data + '</span><div class="sidenote">Download the new version by going to your CodeCanyon accound and accessing the Downloads tab.</div></div> </span>')

      const $theListVg = $('#the-list > #dzs-video-gallery');
      if (!$theListVg.next().hasClass('plugin-update-tr')) {
        $theListVg.addClass('update');
        $theListVg.after('<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message">There is a new version of DZS Video Gallery available. <form action="admin.php?page=dzsvg-autoupdater" class="mainsettings" method="post"> &nbsp; <br> <button class="button-primary" name="action" value="dzsvg_update_request">Update</button></form></td></tr>');
      }
    }
  }


}