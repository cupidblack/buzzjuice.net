"use strict";
/*
 * jQuery Form Plugin
 * version: 2.28 (10-MAY-2009)
 * @requires jQuery v1.2.2 or later
 *
 * Examples and documentation at: http://malsup.com/jquery/form/
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
'use strict';
var uploadersettings = {
  fakeupload: false
};
window.dzsuploader_stop = false; // -- can force a stop from outside


var dzs_upload_current_form = null;
(function ($) {

  /**
   * ajaxSubmit() provides a mechanism for immediately submitting
   * an HTML form using AJAX.
   */
  $.fn.ajaxSubmit = function (options) {
    // -- fast fail if nothing selected (http://dev.jquery.com/ticket/2752)
    if (!this.length) {
      log('ajaxSubmit: skipping submit process - no element selected');
      return this;
    }

    if (typeof options == 'function')
      options = {success: options};

    var url = $.trim(this.attr('action'));
    if (url) {
      // clean url (don't include hash vaue)
      url = (url.match(/^([^#]+)/) || [])[1];
    }
    url = url || window.location.href || ''

    options = $.extend({
      url: url,
      type: this.attr('method') || 'GET'
    }, options || {});

    // hook for manipulating the form data before it is extracted;
    // convenient for use with rich editors like tinyMCE or FCKEditor
    var veto = {};
    this.trigger('form-pre-serialize', [this, options, veto]);
    if (veto.veto) {
      log('ajaxSubmit: submit vetoed via form-pre-serialize trigger');
      return this;
    }

    // provide opportunity to alter form data before it is serialized
    if (options.beforeSerialize && options.beforeSerialize(this, options) === false) {
      log('ajaxSubmit: submit aborted via beforeSerialize callback');
      return this;
    }

    var a = this.formToArray(options.semantic);
    if (options.data) {
      options.extraData = options.data;
      for (var n in options.data) {
        if (options.data[n] instanceof Array) {
          for (var k in options.data[n])
            a.push({name: n, value: options.data[n][k]});
        } else
          a.push({name: n, value: options.data[n]});
      }
    }

    // give pre-submit callback an opportunity to abort the submit
    if (options.beforeSubmit && options.beforeSubmit(a, this, options) === false) {
      log('ajaxSubmit: submit aborted via beforeSubmit callback');
      return this;
    }

    // fire vetoable 'validate' event
    this.trigger('form-submit-validate', [a, this, options, veto]);
    if (veto.veto) {
      log('ajaxSubmit: submit vetoed via form-submit-validate trigger');
      return this;
    }

    var q = $.param(a);

    if (options.type.toUpperCase() == 'GET') {
      options.url += (options.url.indexOf('?') >= 0 ? '&' : '?') + q;
      options.data = null;  // data is null for 'get'
    } else
      options.data = q; // data is the query string for 'post'

    var $form = this, callbacks = [];
    if (options.resetForm)
      callbacks.push(function () {
        $form.resetForm();
      });
    if (options.clearForm)
      callbacks.push(function () {
        $form.clearForm();
      });

    // perform a load on the target only if dataType is not provided
    if (!options.dataType && options.target) {
      var oldSuccess = options.success || function () {
      };
      callbacks.push(function (data) {
        $(options.target).html(data).each(oldSuccess, arguments);
      });
    } else if (options.success)
      callbacks.push(options.success);

    options.success = function (data, status) {
      for (var i = 0, max = callbacks.length; i < max; i++)
        callbacks[i].apply(options, [data, status, $form]);
    };

    // are there files to upload?
    var files = $('input:file', this).fieldValue();
    var found = false;
    for (var j = 0; j < files.length; j++)
      if (files[j])
        found = true;

    var multipart = false;



    if (options.iframe || found || multipart) {

      if (options.closeKeepAlive)
        $.get(options.closeKeepAlive, fileUpload);
      else
        fileUpload();
    } else
      $.ajax(options);

    // fire 'notify' event
    this.trigger('form-submit-notify', [this, options]);
    return this;


    // private function for handling file uploads (hat tip to YAHOO!)
    function fileUpload() {
      var form = $form[0];

      if ($(':input[name=submit]', form).length) {
        alert('Error: Form elements must not be named "submit".');
        return;
      }

      var opts = $.extend({}, $.ajaxSettings, options);
      var s = $.extend(true, {}, $.extend(true, {}, $.ajaxSettings), opts);

      var id = 'jqFormIO' + (new Date().getTime());
      var $io = $('<iframe id="' + id + '" name="' + id + '" src="about:blank" />');
      var io = $io[0];

      $io.css({position: 'absolute', top: '-1000px', left: '-1000px'});

      var xhr = { // mock object
        aborted: 0,
        responseText: null,
        responseXML: null,
        status: 0,
        statusText: 'n/a',
        getAllResponseHeaders: function () {
        },
        getResponseHeader: function () {
        },
        setRequestHeader: function () {
        },
        abort: function () {
          this.aborted = 1;
          $io.attr('src', 'about:blank'); // abort op in progress
        }
      };
      var g = opts.global;
      // trigger ajax global events so that activity/block indicators work like normal
      if (g && !$.active++)
        $.event.trigger("ajaxStart");
      if (g)
        $.event.trigger("ajaxSend", [xhr, opts]);

      if (s.beforeSend && s.beforeSend(xhr, s) === false) {
        s.global && $.active--;
        return;
      }
      if (xhr.aborted)
        return;

      var cbInvoked = 0;
      var timedOut = 0;

      // add submitting element to data if we know it
      var sub = form.clk;
      if (sub) {
        var n = sub.name;
        if (n && !sub.disabled) {
          options.extraData = options.extraData || {};
          options.extraData[n] = sub.value;
          if (sub.type == "image") {
            options.extraData[name + '.x'] = form.clk_x;
            options.extraData[name + '.y'] = form.clk_y;
          }
        }
      }

      // take a breath so that pending repaints get some cpu time before the upload starts
      setTimeout(function () {
        // make sure form attrs are set
        var t = $form.attr('target'), a = $form.attr('action');

        // update form attrs in IE friendly way
        form.setAttribute('target', id);
        if (form.getAttribute('method') !== 'POST')
          form.setAttribute('method', 'POST');
        if (form.getAttribute('action') != opts.url)
          form.setAttribute('action', opts.url);

        // ie borks in some cases when setting encoding
        if (!options.skipEncodingOverride) {
          $form.attr({
            encoding: 'multipart/form-data',
            enctype: 'multipart/form-data'
          });
        }

        // support timout
        if (opts.timeout)
          setTimeout(function () {
            timedOut = true;
            cb();
          }, opts.timeout);

        // add "extra" data to form if provided in options
        var extraInputs = [];
        try {
          if (options.extraData)
            for (var n in options.extraData)
              extraInputs.push(
                $('<input type="hidden" name="' + n + '" value="' + options.extraData[n] + '" />')
                  .appendTo(form)[0]);

          // add iframe to doc and submit the form
          $io.appendTo('body');
          io.attachEvent ? io.attachEvent('onload', cb) : io.addEventListener('load', cb, false);
          form.submit();
        } finally {
          // reset attrs and remove "extra" input elements
          form.setAttribute('action', a);
          t ? form.setAttribute('target', t) : $form.removeAttr('target');
          $(extraInputs).remove();
        }
      }, 10);

      var nullCheckFlag = 0;

      function cb() {
        if (cbInvoked++)
          return;

        io.detachEvent ? io.detachEvent('onload', cb) : io.removeEventListener('load', cb, false);

        var ok = true;
        try {
          if (timedOut)
            throw 'timeout';
          // extract the server response from the iframe
          var data, doc;

          doc = io.contentWindow ? io.contentWindow.document : io.contentDocument ? io.contentDocument : io.document;

          if ((doc.body == null || doc.body.innerHTML == '') && !nullCheckFlag) {
            // in some browsers (cough, Opera 9.2.x) the iframe DOM is not always traversable when
            // the onload callback fires, so we give them a 2nd chance
            nullCheckFlag = 1;
            cbInvoked--;
            setTimeout(cb, 100);
            return;
          }

          xhr.responseText = doc.body ? doc.body.innerHTML : null;
          xhr.responseXML = doc.XMLDocument ? doc.XMLDocument : doc;
          xhr.getResponseHeader = function (header) {
            var headers = {'content-type': opts.dataType};
            return headers[header];
          };

          if (opts.dataType == 'json' || opts.dataType == 'script') {
            var ta = doc.getElementsByTagName('textarea')[0];
            xhr.responseText = ta ? ta.value : xhr.responseText;
          } else if (opts.dataType == 'xml' && !xhr.responseXML && xhr.responseText != null) {
            xhr.responseXML = toXml(xhr.responseText);
          }
          data = xhr.responseText;
        } catch (e) {
          ok = false;
        }
        if (ok) {
          opts.success(data, 'success');
          if (g)
            $.event.trigger("ajaxSuccess", [xhr, opts]);
        }
        if (g)
          $.event.trigger("ajaxComplete", [xhr, opts]);
        if (g && !--$.active)
          $.event.trigger("ajaxStop");
        if (opts.complete)
          opts.complete(xhr, ok ? 'success' : 'error');

        // clean up
        setTimeout(function () {
          $io.remove();
          xhr.responseXML = null;
        }, 2000);
      }
      ;

      function toXml(s, doc) {
        if (window.ActiveXObject) {
          doc = new ActiveXObject('Microsoft.XMLDOM');
          doc.async = 'false';
          doc.loadXML(s);
        } else
          doc = (new DOMParser()).parseFromString(s, 'text/xml');
        return (doc && doc.documentElement && doc.documentElement.tagName != 'parsererror') ? doc : null;
      }
      ;
    }
    ;
  };


  /**
   * formToArray() gathers form element data into an array of objects that can
   * be passed to any of the following ajax functions: $.get, $.post, or load.
   * Each object in the array has both a 'name' and 'value' property.  An example of
   * an array for a simple login form might be:
   *
   * [ { name: 'username', value: 'jresig' }, { name: 'password', value: 'secret' } ]
   *
   * It is this array that is passed to pre-submit callback functions provided to the
   * ajaxSubmit() and ajaxForm() methods.
   */
  $.fn.formToArray = function (semantic) {
    var a = [];
    if (this.length == 0)
      return a;

    var form = this[0];
    var els = semantic ? form.getElementsByTagName('*') : form.elements;
    if (!els)
      return a;
    for (var i = 0, max = els.length; i < max; i++) {
      var el = els[i];
      var n = el.name;
      if (!n)
        continue;

      if (semantic && form.clk && el.type == "image") {
        // handle image inputs on the fly when semantic == true
        if (!el.disabled && form.clk == el) {
          a.push({name: n, value: $(el).val()});
          a.push({name: n + '.x', value: form.clk_x}, {name: n + '.y', value: form.clk_y});
        }
        continue;
      }

      var v = $.fieldValue(el, true);
      if (v && v.constructor == Array) {
        for (var j = 0, jmax = v.length; j < jmax; j++)
          a.push({name: n, value: v[j]});
      } else if (v !== null && typeof v != 'undefined')
        a.push({name: n, value: v});
    }

    if (!semantic && form.clk) {
      // input type=='image' are not found in elements array! handle it here
      var $input = $(form.clk), input = $input[0], n = input.name;
      if (n && !input.disabled && input.type == 'image') {
        a.push({name: n, value: $input.val()});
        a.push({name: n + '.x', value: form.clk_x}, {name: n + '.y', value: form.clk_y});
      }
    }
    return a;
  };


  $.fn.fieldValue = function (successful) {
    for (var val = [], i = 0, max = this.length; i < max; i++) {
      var el = this[i];
      var v = $.fieldValue(el, successful);
      if (v === null || typeof v == 'undefined' || (v.constructor == Array && !v.length))
        continue;
      v.constructor == Array ? $.merge(val, v) : val.push(v);
    }
    return val;
  };

  /**
   * Returns the value of the field element.
   */
  $.fieldValue = function (el, successful) {
    var n = el.name, t = el.type, tag = el.tagName.toLowerCase();
    if (typeof successful == 'undefined')
      successful = true;

    if (successful && (!n || el.disabled || t == 'reset' || t == 'button' ||
      (t == 'checkbox' || t == 'radio') && !el.checked ||
      (t == 'submit' || t == 'image') && el.form && el.form.clk != el ||
      tag == 'select' && el.selectedIndex == -1))
      return null;

    if (tag == 'select') {
      var index = el.selectedIndex;
      if (index < 0)
        return null;
      var a = [], ops = el.options;
      var one = (t == 'select-one');
      var max = (one ? index + 1 : ops.length);
      for (var i = (one ? index : 0); i < max; i++) {
        var op = ops[i];
        if (op.selected) {
          var v = op.value;
          if (!v) // extra pain for IE...
            v = (op.attributes && op.attributes['value'] && !(op.attributes['value'].specified)) ? op.text : op.value;
          if (one)
            return v;
          a.push(v);
        }
      }
      return a;
    }
    return el.value;
  };

  $.fn.clearForm = function () {
    return this.each(function () {
      $('input,select,textarea', this).clearFields();
    });
  };

  /**
   * Clears the selected form elements.
   */
  $.fn.clearFields = $.fn.clearInputs = function () {
    return this.each(function () {
      var t = this.type, tag = this.tagName.toLowerCase();
      if (t == 'text' || t == 'password' || tag == 'textarea')
        this.value = '';
      else if (t == 'checkbox' || t == 'radio')
        this.checked = false;
      else if (tag == 'select')
        this.selectedIndex = -1;
    });
  };

  /**
   * Resets the form data.  Causes all form elements to be reset to their original value.
   */
  $.fn.resetForm = function () {
    return this.each(function () {
      // guard against an input with the name of 'reset'
      // note that IE reports the reset function as an 'object'
      if (typeof this.reset == 'function' || (typeof this.reset == 'object' && !this.reset.nodeType))
        this.reset();
    });
  };

  $.fn.selected = function (select) {
    if (select == undefined)
      select = true;
    return this.each(function () {
      var t = this.type;
      if (t == 'checkbox' || t == 'radio')
        this.checked = select;
      else if (this.tagName.toLowerCase() == 'option') {
        var $sel = $(this).parent('select');
        if (select && $sel[0] && $sel[0].type == 'select-one') {
          // deselect all other options
          $sel.find('option').selected(false);
        }
        this.selected = select;
      }
    });
  };

// set $.fn.ajaxSubmit.debug to true to enable debug logging
  function log() {
    if ($.fn.ajaxSubmit.debug)
      window.console.log('[jquery.form] ' + Array.prototype.join.call(arguments, ''));
  }
  ;


})(jQuery);


function getMatches(string, regex, index) {
  index || (index = 1); // default to the first capturing group
  var matches = [];
  var match;
  var i = 0;
  while (match = regex.exec(string)) {

    if (match.length > 2) {

      matches.push(match);
    } else {
      matches.push(match[index]);
    }


    i++;
  }
  return matches;
}


/*
 * DZS Upload
 * version: 1.0
 * author: digitalzoomstudio
 * website: http://digitalzoomstudio.net
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
window.dzs_upload_target = "";
var i = 0,
  j = 0;
var target_field;



(function ($) {
  if (window.dzs_php_loc) {
    window.dzs_phpfile_path = window.dzs_php_loc;
  }

  var uploadfileloc = 'upload.php';


  $.fn.formUploader = function (o) {

    var defaults = {
        targetfield: undefined
        , targetfeedback: undefined
        , phplocation: undefined
      },
      o = $.extend(defaults, o);


    this.each(function () {
      //we cache the element
      var _t = $(this);

      var cthis = _t;

      var _targetInput = null;
      var _par = _t.parent();

      if (_t.hasClass('dzs-upload-converted')) {

      } else {
        init();
      }

      function init() {
        _t.addClass('dzs-upload-converted');

        var auxtarget = null;
        if (o.targetfeedback != undefined) {
          auxtarget = o.targetfeedback;
        } else {
          if (_t.next().hasClass('feedback')) {
            auxtarget = _t.next();
          }
        }


        if (_par.hasClass('dzs-upload-con')) {
          _targetInput = _par.find('input').eq(0);
        } else {
          if (_t.prev()) {
            _targetInput = _t.prev();
          }
        }


        if (is_ie8()) {
          _t.find('.btn_upl').css('display', 'none');
          _t.find('.file_field').eq(0).css('visibility', 'visible');
        }


        if (window.dzs_phpfile_path) {
          uploadfileloc = window.dzs_phpfile_path;
        }

        var options = {
          target: auxtarget, //Div tag where content info will be loaded in
          url: uploadfileloc, //The php file that handles the file that is uploaded
          beforeSubmit: function () {
          },
          success: function (e) {
            //Here code can be included that needs to be performed if Ajax request was successful

            target_field.trigger('change');

          }
        };

        _t.children('form').unbind();
        _t.children('form').submit(function () {
          _t.children('form').ajaxSubmit(options);
          return false;
        });


        _t.find('.file_field').unbind();
        _t.find('.file_field').bind('change', changeTargetField)

        function changeTargetField(e) {
          var _c = jQuery(this);

          var aux = _c.val();
          if (aux.indexOf('/') > -1)
            aux = aux.split('/');
          else
            aux = aux.split('\\');

          let auxfinal = aux[aux.length - 1];

          window.dzs_upload_target = auxfinal;

          var aux2 = window.dzs_upload_path;


          if (aux2 == undefined) {
            aux2 = '';
          }

          auxfinal = auxfinal.split(" ").join("_");


          if (_targetInput.attr('type') == 'text') {
            _targetInput.val(aux2 + auxfinal);
          }
          if (_targetInput.hasClass('textinput')) {
            _targetInput.val(aux2 + auxfinal);
          }
          _c.parent().submit();

        }


        _t.find('.btn_upl').unbind();
        _t.find('.btn_upl').bind('click', function (e) {
          jQuery(this).next().click();
        });


      }
    })
  };


  function uploadFile(file, pargs) {
    var xhr;

    var margs = {
      par: null
      , cthis: null
      , _feedback: null
      , _progress: null
      , _targetInput: null
      , _imgPreview: null
      , index: null
      , action_file_uploaded: null
      , action_file_upload_progress: null
      , action_file_upload_start: null
    };

    margs = $.extend(margs, pargs);


    // -- Uploading - for Firefox, Google Chrome and Safari


    xhr = new XMLHttpRequest();





    window.dzs_uploader_force_progress = function (arg) {
      margs._progress = arg;
    }

    window.dzsuploader_stop = false;
    if (margs.action_file_upload_start) {
      margs.action_file_upload_start(file, margs)
    }


    if (window.dzsuploader_stop) {
      return false;
    }


    if (margs.cthis && margs.cthis.hasClass('dzs-multi-upload')) {
      margs._progress = margs.cthis.find('.upload-list .prefile').eq(margs.index);
    }




    // ====File uploaded
    if (margs._progress) {
      xhr.upload.addEventListener("progress", xhr_progress, false);
      margs._progress.addClass('active');
    }

    xhr.addEventListener("load", xhr_loaded, false);


    function xhr_progress(e) {
      if (e.lengthComputable) {
        var percentComplete = Math.round(e.loaded * 100 / e.total);

        if (margs._progress.find('.dzs-upload--progress--barprog').length) {
          margs._progress.find('.dzs-upload--progress--barprog').animate({
            'width': (percentComplete + '%')
          }, {
            queue: false
            , duration: 50
          })
        } else {
          margs._progress.css({
            'width': (percentComplete + '%')
          })
        }


        margs._progress.addClass('active');
        margs._progress.addClass('loader-active');
        if (percentComplete >= 100) {
          setTimeout(function () {

            margs._progress.removeClass('active');
            margs._progress.removeClass('loader-active');
          }, 100)
        }


        if (margs.action_file_upload_progress) {
          margs.action_file_upload_progress(percentComplete, margs)
        }


      } else {
        console.info('not able to calculate');
      }
    }


    function parse_response(response) {

      var arg = {};
      try {
        arg = JSON.parse(response);


      } catch (err) {
        console.log('did not parse', response);
      }

      return arg;
    }


    function xhr_loaded(presponse) {




      var filename = file.name;
      var ftype = '';
      var resp = presponse.currentTarget.responseText;

      var auxa = /{{filename-(.*?)}}/g.exec(resp);


      if (auxa) {
        if (auxa[1]) {
          filename = auxa[1];
        }
      }



      if (presponse && presponse.currentTarget) {

        if (presponse.currentTarget.responseText) {


          var resp = parse_response(presponse.currentTarget.responseText)
          if (resp.report == 'error') {

            if (margs._feedback) {
              margs._feedback.html(String(resp.text));
              setTimeout(function () {
                margs._feedback.html('');
              }, 1500);


            }
            if (margs.action_file_uploaded) {
              margs.action_file_uploaded(resp, margs)
            }




            return false;
          }
        }
      }


      if (file.type) {
        ftype = file.type;
      }


      if (margs.cthis) {

        if (margs.cthis.hasClass('only-image')) {
          if (ftype != 'image/png' && ftype != 'image/jpeg' && ftype != 'image/gif') {
            if (margs._feedback) {
              margs._feedback.html('<div class="error">only image allowed</div>');

              setTimeout(function () {
                margs._feedback.html('');
              }, 1500);


              return false;
            }
            ;
          }
        }

      }


      if (margs._feedback) {
        margs._feedback.html('<div class="success">file uploaded</div>');
        setTimeout(function () {
          margs._feedback.html('');
        }, 1500);
      }
      ;
      //success file upload function
      // --- tests uplkad speed test  // unasable unsable
      if (margs != undefined && margs.uploadlist != undefined) {
        for (i = 0; i < $(margs.uploadlist).children().length; i++) {
          var _cache = $(margs.uploadlist).children().eq(i);
          if (_cache.attr('rel') == file.name) {
            _cache.slideUp('slow');
          }
        }
      }

      if (typeof global_dzsmultiupload == 'function') {
        var aux = filename;
        aux = aux.split(' ').join('_');
        margs.file = file;
        global_dzsmultiupload(aux, margs);
      }


      var regex = /{{([\s|\S]*?)-([\s|\S]*?)}}/g;

      var matches = getMatches(resp, regex, 1);

      var final_location = '';

      if (margs._targetInput) {
        var auxfinal = '';
        var aux2 = window.dzs_upload_path;
        if (aux2) {
          aux2 = '';
        }
        if (filename.indexOf('https://') === 0 || filename.indexOf('http://') === 0) {
          aux2 = '';
        }

        auxfinal = aux2 + filename;
        auxfinal = auxfinal.split(" ").join("_");
        if (margs._targetInput && margs._targetInput.hasClass('the-real-uploader') === false) {

          margs._targetInput.val(auxfinal);
          margs._targetInput.trigger('change');
        }
        final_location = auxfinal;


        margs.file = file;
        if (margs._targetInput && margs._targetInput.hasClass('the-real-uploader') == false && typeof margs._targetInput.get(0).api_finished_upload == 'function') {
          var aux = filename;
          aux = aux.split(' ').join('_');
          margs.filename = aux;
          if (margs._targetInput) {
            margs._targetInput.get(0).api_finished_upload(aux, margs, matches);
          }
        }
      }
      if (margs._imgPreview) {
        var auxfinal = '';
        var aux2 = window.dzs_upload_path;
        if (aux2 == undefined) {
          aux2 = '';
        }
        auxfinal = aux2 + filename;
        auxfinal = auxfinal.split(" ").join("_");

        margs._imgPreview.css({
          'background-image': 'url(' + auxfinal + ')'
        })
      }

      window.dzs_upload_target = auxfinal;


      if (margs._progress) {
        margs._progress.find('.dzs-upload--progress--barprog').css({
          'width': '0%'
        })
      }


      margs.final_location = final_location;
      margs.responseText = this.responseText;

      if (margs.action_file_uploaded) {
        margs.action_file_uploaded(resp, margs, matches)
      }


    }


    // end complete


    xhr.open("post", uploadfileloc, true);

    var fname = '';
    if (file.fileName) {
      fname = file.fileName;
    }
    if (file.name != undefined) {
      fname = file.name;
    }
    var fsize = '';
    var ftype = '';
    if (file.fileSize != undefined) {
      fsize = file.fileSize;
    }
    if (file.size != undefined) {
      fsize = file.size;
    }
    if (file.Type != undefined) {
      ftype = file.fileType;
    }
    if (file.type != undefined) {
      ftype = file.type;
    }




    var aux = fname.split('.');


    fname = '';
    for (var i3 = 0; i3 < aux.length; i3++) {


      if (i3 < aux.length - 1) {
        fname += sanitize_path(aux[i3]) + '.';
      } else {

        fname += aux[i3];

      }
    }


    var upload_loc = 'upload/';


    if (window.dzs_upload_realpath) {
      upload_loc = window.dzs_upload_realpath;
    }




    xhr.setRequestHeader("X-File-Name", fname);
    xhr.setRequestHeader("X-File-Size", fsize);
    xhr.setRequestHeader("X-File-Type", ftype);

    var upload_from = '';
    var upload_last_val = '';
    if (margs._targetInput) {
      if (margs._targetInput.attr('data-upload_from')) {
        upload_from = margs._targetInput.attr('data-upload_from');
      }
      if (margs._targetInput.val()) {
        upload_last_val = margs._targetInput.val();
      }
    }

    if (upload_from) {

      xhr.setRequestHeader("X-Upload-From", upload_from);
    }

    if (upload_last_val) {

      xhr.setRequestHeader("X-Upload-Last-Val", upload_last_val);
    }


    // Set appropriate headers
    //firefox uses file.name webkit uses file.fileName


    // Send the file (doh)

    var formData = new FormData();
    formData.append('param1', 'myParam');
    formData.append('upload_path', upload_loc);
    formData.append('myfile', file);

    formData.append('filename', fname);

    if (dzs_upload_current_form) {

      formData.append('formdata', dzs_upload_current_form.serializeAnything());
    }

    xhr.send(formData);
  }

  function sanitize_path(arg) {
    arg = arg.replace(/[^ -~]+/g, "");

    return arg;
  }


  /// ---------- single uploader hier
  // -----------
  $.fn.singleUploader = function (o) {

    var defaults = {
        targetfield: undefined
        , targetfeedback: undefined
        , action_file_uploaded: null
        , action_file_upload_start: null
        , action_file_upload_progress: null
      },
      o = $.extend(defaults, o);
    this.each(function () {

      var _t
        , cthis
        , _con
        , _par
        , _feedback = null
        , _progress = null
        , _targetInput = null
        , fileUploader = null
        , fileUploaderWrap = null
        , _imgPreview = null
      ;



      cthis = $(this);


      if (cthis.hasClass('treated')) {
        return;
      }

      cthis.addClass('treated');

      _par = cthis.parent();


      if (_par.hasClass('dzs-upload-con')) {

        if (_par.find('.target-field').length > 0) {

          _targetInput = _par.find('.target-field').eq(0);
        } else {

          _targetInput = _par.find('input').eq(0);
        }
      } else {
        if (cthis.prev()) {
          _targetInput = cthis.prev();
        }
      }


      if (_par.hasClass('dzs-upload-con')) {
        _feedback = _par.find('.feedback').eq(0);
      } else {
        if (cthis.next().hasClass('feedback')) {
          _feedback = cthis.next();
        }
      }


      if (_par.hasClass('dzs-upload-con')) {
        if (_par.find('.dzs-single-upload-preview-img').length > 0) {
          _imgPreview = _par.find('.dzs-single-upload-preview-img').eq(0);


          if (_targetInput && _targetInput.val()) {
            _imgPreview.css({
              'background-image': 'url(' + _targetInput.val() + ')'
            })
          }
        }
      }


      if (_par.hasClass('dzs-upload-con') && _par.find('.dzs-upload--progress').length > 0) {
        _progress = _par.find('.dzs-upload--progress').eq(0);
      } else {

        // -- dzs-upload--progress--bar
        if ($('body').children('*[class*="dzs-upload--progress"]').length > 0) {
          _progress = $('body').children('*[class*="dzs-upload--progress"]').eq(0);
        }
      }

      if (_progress) {


      }


      if (window.dzs_phpfile_path) {
        uploadfileloc = window.dzs_phpfile_path;
      }
      ;

      fileUploader = cthis.find('input[type=file]').eq(0);
      fileUploaderWrap = null

      if (!cthis.hasClass('drag-drop')) {

        fileUploader.wrap('<div class="single-uploader-wrap"></div>');
        fileUploader.css('display', 'none');
        fileUploaderWrap = fileUploader.parent();


        var aux = '';


        var text_upload = 'Upload';


        if (cthis.hasClass('debug-target')) {
          // test typing
        }
        // tests typing is very slow
        // -- testst test


        if (_par.find('.feed-translate-upload').length) {
          text_upload = _par.find('.feed-translate-upload').eq(0).html();
          _par.find('.feed-translate-upload').remove();
        }

        aux += '<input type="button" value="' + text_upload + '" class="btn_upl';
        if (cthis.attr('data-buttonextraclass')) {
          aux += ' ' + cthis.attr('data-buttonextraclass');
        }
        aux += '"/>';

        if (_par.find('.feed-upload-btn-location').length) {

          _par.find('.feed-upload-btn-location').after(aux);
          _par.find('.feed-upload-btn-location').remove();
        } else {

          fileUploaderWrap.prepend(aux);
        }

      }

      if (is_ie() && version_ie() < 9) {
//                cthis.html('Sorry - Opera and IE do not support multi upload');
//                return;
      }


      filesUpload = null;

      if (_par) {

        _par.off('click', '.btn_upl');

        _par.on('click', '.btn_upl', function (e) {
          _par.find('input[type="file"]').eq(0).trigger('click');
        });

        _par.on('click', 'input', function (e) {

        });

      }
      var filesUpload = fileUploader.get(0);
      if (filesUpload == null) {
        return;
      }


      filesUpload.addEventListener("change", function () {
        parseFiles(this.files);
      }, false);



      if (typeof cthis.find('.dzs-single-upload--areadrop').get(0) != "undefined") {

        var droparea = cthis.find('.dzs-single-upload--areadrop').get(0);



        droparea.addEventListener("dragleave", function (e) {
          if (e.target && e.target === droparea) {
            $(this).removeClass('over');
          }
          e.preventDefault();
          e.stopPropagation();
        }, false);

        droparea.addEventListener("dragenter", function (e) {
          $(this).addClass('over');
          e.preventDefault();
          e.stopPropagation();
        }, false);

        droparea.addEventListener("dragover", function (e) {
          e.preventDefault();
          e.stopPropagation();
        }, false);

        droparea.addEventListener("drop", function (e) {
          $(this).removeClass('over');
          parseFiles(e.dataTransfer.files);
          e.preventDefault();
          e.stopPropagation();
        }, false);
      }


      function parseFiles(files) {
        if (typeof files !== "undefined") {
          for (let i = 0; i < files.length; i++) {
            var args = {
              'called_from': 'default'
            };
            args.cthis = cthis;



            if (_feedback) {
              args._feedback = _feedback;
            }
            if (_progress) {
              args._progress = _progress;
            }
            if (_targetInput) {
              args._targetInput = _targetInput;
            }
            if (_imgPreview) {
              args._imgPreview = _imgPreview;
            }
            if (args.cthis.attr('data-called_from')) {
              args.called_from = args.cthis.attr('data-called_from');
            }

            if (o.action_file_uploaded) {
              args.action_file_uploaded = o.action_file_uploaded;
            }

            if (o.action_file_upload_start) {
              args.action_file_upload_start = o.action_file_upload_start;
            }
            if (o.action_file_upload_progress) {
              args.action_file_upload_progress = o.action_file_upload_progress;
            }

            uploadFile(files[i], args);

          }
        }
      }


    });
  };//--end fn.singleUploader


  $.fn.multiUploader = function (o) {

    var defaults = {
        targetfield: undefined
        , targetfeedback: undefined
      },
      o = $.extend(defaults, o);
    var _t
      , _con
      , _par
      , cthis
    ;
    this.each(function () {
      //we cache the element
      _t = $(this).find('.multi-uploader');
      cthis = $(this);
      _par = cthis.parent();

      if (window.dzs_phpfile_path) {
        uploadfileloc = window.dzs_phpfile_path;
      }

      _t.wrap('<div class="multi-uploader-wrap"></div>');
      _t.css('visibility', 'hidden');
      _con = _t.parent();


      var text_upload = 'Upload';


      if (cthis.find('.feed-translate-upload').length) {
        text_upload = cthis.find('.feed-translate-upload').eq(0).val();
      }

      _con.prepend('<input type="button" value="' + text_upload + '" class="btn_upl"/>');

      if (is_ie() && version_ie() < 10) {
        _con.parent().parent().html('Sorry -  IE earlier then 10 do not support multi upload');
        return;
      }


      let _progress = null;
      if (_par.hasClass('dzs-upload-con') && _par.find('.dzs-upload--progress').length > 0) {
        _progress = _par.find('.dzs-upload--progress').eq(0);
      } else {
        if ($('body').children('*[class*="dzs-upload--progress"]').length > 0) {
          _progress = $('body').children('*[class*="dzs-upload--progress"]').eq(0);
        }
      }
      ;

      _con.find('.btn_upl').bind('click', function (e) {
        $(this).next().click();
      });
      var filesUpload = _con.find(".files-upload").eq(0).get(0);
      if (filesUpload == null) {
        return;
      }
      var droparea = $('.droparea').eq(0)[0];
      var uploadlist = $('.upload-list').eq(0)[0];
      var gfiles = [];




      function parseFiles(files) {
        if (typeof files !== "undefined") {
          $(uploadlist).find('.dummy').remove();
          for (i = 0; i < files.length; i++) {
            $(uploadlist).append('<li class="prefile" rel="' + files[i].name + '"><fig class="dzs-upload--progress--barprog"></fig><span class="the-text">' + files[i].name + '</span></li>');
            gfiles.push(files[i])
          }
        }
      };

      filesUpload.addEventListener("change", function () {
        parseFiles(this.files);
      }, false);

      droparea.addEventListener("dragleave", function (e) {
        if (e.target && e.target === droparea) {
          $(this).removeClass('over');
        }
        e.preventDefault();
        e.stopPropagation();
      }, false);

      droparea.addEventListener("dragenter", function (e) {
        $(this).addClass('over');
        e.preventDefault();
        e.stopPropagation();
      }, false);

      droparea.addEventListener("dragover", function (e) {
        e.preventDefault();
        e.stopPropagation();
      }, false);

      droparea.addEventListener("drop", function (e) {
        $(this).removeClass('over');
        parseFiles(e.dataTransfer.files);
        e.preventDefault();
        e.stopPropagation();
      }, false);
      $('.upload-button').click(function () {
//                _targetInput = null;

        if (gfiles !== undefined) {
          for (i = 0; i < gfiles.length; i++) {
            var args = {};
            args.uploadlist = uploadlist;
            args.cthis = cthis;

            args.index = i;




            uploadFile(gfiles[i], args);
          }

        }
        gfiles = [];
        return false;
      })


    });
  };//--end fn.multiUploader
  window.dzsuploader_form_init = function (selector, settings) {
    $(selector).formUploader(settings);
  };
  window.dzsuploader_single_init = function (selector, settings) {
    $(selector).singleUploader(settings);
  };
  window.dzsuploader_multi_init = function (selector, settings) {
    $(selector).multiUploader(settings);
  };
})(jQuery);

function hideFeedbacks() {
  jQuery('.feedback').html('');
}

function hideFeedbacksCall() {
  setTimeout(hideFeedbacks, 2000);
}
;
top.hideFeedbacksCall = hideFeedbacksCall;


function is_ios() {
  return ((navigator.platform.indexOf("iPhone") != -1) || (navigator.platform.indexOf("iPod") != -1) || (navigator.platform.indexOf("iPad") != -1)
  );
}

function is_android() {
  return (navigator.platform.indexOf("Android") != -1);
}

function is_ie() {
  if (navigator.appVersion.indexOf("MSIE") != -1) {
    return true;
  }
  ;
  return false;
}
;


function is_ie8() {
  if (is_ie() && version_ie() < 9) {
    return true;
  }
  ;
  return false;
}

function is_firefox() {
  if (navigator.userAgent.indexOf("Firefox") != -1) {
    return true;
  }
  ;
  return false;
}
;

function is_opera() {
  if (navigator.userAgent.indexOf("Opera") != -1) {
    return true;
  }
  ;
  return false;
}
;

function is_chrome() {
  return navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
}
;

function is_safari() {
  return navigator.userAgent.toLowerCase().indexOf('safari') > -1;
}
;

function version_ie() {
  return parseFloat(navigator.appVersion.split("MSIE")[1]);
}
;

function version_firefox() {
  if (/Firefox[\/\s](\d+\.\d+)/.test(navigator.userAgent)) {
    var aversion = new Number(RegExp.$1);
    return (aversion);
  }
  ;
}
;

function version_opera() {
  if (/Opera[\/\s](\d+\.\d+)/.test(navigator.userAgent)) {
    var aversion = new Number(RegExp.$1);
    return (aversion);
  }
  ;
}
;


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