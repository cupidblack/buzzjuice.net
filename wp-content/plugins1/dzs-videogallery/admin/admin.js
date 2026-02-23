"use strict";
var sliderIndex = 0;
var itemIndex = [0];
var currSlider_nr = -1;
var currSlider;
var targetInput;
var global_items = 0;

/* @projectDescription jQuery Serialize Anything - Serialize anything (and not just forms!)
 * @author Bramus! (Bram Van Damme)
 * @version 1.0
 * @website: https://www.bram.us/
 * @license : BSD
 */

(function ($) {

  $.fn.serializeAnything = function () {

    var toReturn = [];
    var els = $(this).find(':input').get();

    $.each(els, function () {
      if (this.name && !this.disabled && (this.checked || /select|textarea/i.test(this.nodeName) || /text|hidden|password/i.test(this.type))) {
        var val = $(this).val();
        toReturn.push(encodeURIComponent(this.name) + "=" + encodeURIComponent(val));
      }
    });

    return toReturn.join("&").replace(/%20/g, "+");

  }

})(jQuery);