
"use strict";
function get_query_arg(purl, key) {
  if (purl.indexOf(key + '=') > -1) {
    var regexS = "[?&]" + key + "=.+";
    var regex = new RegExp(regexS);
    var regtest = regex.exec(purl);

    if (regtest != null) {
      var splitterS = regtest[0];
      if (splitterS.indexOf('&') > -1) {
        var aux = splitterS.split('&');
        splitterS = aux[1];
      }
      var splitter = splitterS.split('=');

      return splitter[1];

    }
  }
}


jQuery(document).ready(function ($) {
  var cthis = null;

  var the_player_id = 1;

  if (window.dzsuploader_single_init) {
    window.dzsuploader_single_init('.dzs-single-upload', {
      action_file_uploaded: action_file_uploaded
      , action_file_upload_start: action_file_upload_start
    });
  }


  $(document).on('change', '.submit-track-form select[name=type],.submit-track-form *[name=source]', handle_change);
  $(document).on('submit', 'form.submit-track-form', handle_change);
  $(document).on('click', '.cancel-upload-btn , .submit-track-form .btn-submit, .submit-track-form .single-submit-for-main-media', handle_click);


  setTimeout(function () {

    $(document).on('change', '.tags-display-select select', handle_change);
  }, 2000);

  $(document).on('click', '.btn-like', click_like);

  $('.shortcode-upload').addClass('loaded');


  function click_like() {
    var _t = $(this);
    cthis = _t.parent().parent();

    the_player_id = $('.from-parse-videoitem').eq(0).attr('data-postid');


    if (cthis.hasClass('mainvp-con') == false) {
      return false;
    }

    if (_t.hasClass('active')) {
      ajax_retract_like();
    } else {
      ajax_submit_like();
    }
  }


  function ajax_submit_like(argp) {
    // -- only handles ajax call + result
    var mainarg = argp;
    var data = {
      action: 'dzsvp_submit_like',
      postdata: mainarg,
      playerid: the_player_id
    };


    if (cthis.hasClass('mainvp-con') == false) {
      return false;
    } else {
    }
    ;


    $.ajax({
      type: "POST",
      url: dzsvg_settings.site_url + 'index.php?dzsvg_action=dzsvp_submit_like',
      data: data,
      success: function (response) {

        cthis.find('.btn-like').addClass('active');
        var auxlikes = cthis.find('.counter-likes .the-number').html();
        auxlikes = parseInt(auxlikes, 10);
        auxlikes++;
        cthis.find('.counter-likes .the-number').html(auxlikes);
      },
      error: function (arg) {
        ;


        cthis.find('.btn-like').addClass('active');
        var auxlikes = cthis.find('.counter-likes .the-number').html();
        auxlikes = parseInt(auxlikes, 10);
        auxlikes++;
        cthis.find('.counter-likes .the-number').html(auxlikes);
      }
    });
  }

  function ajax_retract_like(argp) {
    // -- only handles ajax call + result
    var mainarg = argp;
    var data = {
      action: 'dzsvp_retract_like',
      postdata: mainarg,
      playerid: the_player_id
    };


    $.ajax({
      type: "POST",
      url: dzsvg_settings.site_url + 'index.php?dzsvg_action=dzsvp_retract_like',
      data: data,
      success: function (response) {

        cthis.find('.btn-like').removeClass('active');
        var auxlikes = cthis.find('.counter-likes .the-number').html();
        auxlikes = parseInt(auxlikes, 10);
        auxlikes--;
        cthis.find('.counter-likes .the-number').html(auxlikes);
      },
      error: function (arg) {
        ;

        cthis.find('.btn-like').removeClass('active');
        var auxlikes = cthis.find('.counter-likes .the-number').html();
        auxlikes = parseInt(auxlikes, 10);
        auxlikes--;
        cthis.find('.counter-likes .the-number').html(auxlikes);
      }
    });
  }


  function show_notice(response) {


    var _feedbacker = $('.feedbacker').eq(0);



    if (typeof response == 'object') {
      if (response.report == 'success') {

        _feedbacker.removeClass('is-error');
        _feedbacker.addClass('active');
        _feedbacker.html(response.text);
        _feedbacker.fadeIn('fast');

        setTimeout(function () {

          _feedbacker.fadeOut('slow');
          _feedbacker.removeClass('active');
        }, 1500)
      }
      if (response.report == 'error') {

        _feedbacker.addClass('is-error');
        _feedbacker.html(response.text);
        _feedbacker.fadeIn('fast');
        _feedbacker.addClass('active');

        setTimeout(function () {

          _feedbacker.fadeOut('slow');
          _feedbacker.removeClass('active');
        }, 1500)
      }
    } else {
      if (response.indexOf('error -') == 0) {
        _feedbacker.addClass('is-error');
        _feedbacker.html(response.substr(7));
        // _feedbacker.fadeIn('fast');
        _feedbacker.addClass('active');

        setTimeout(function () {

          // _feedbacker.fadeOut('slow');
          _feedbacker.removeClass('active');
        }, 1500)
      }
      if (response.indexOf('success -') == 0) {
        _feedbacker.removeClass('is-error');
        _feedbacker.html(response.substr(9));
        // _feedbacker.fadeIn('fast');

        _feedbacker.addClass('active');
        setTimeout(function () {

          // _feedbacker.fadeOut('slow');
          _feedbacker.removeClass('active');
        }, 1500)
      }
    }


  }


  function handle_change(e) {

    var _t = $(this);
    var _con = null;

    if (e.type == 'submit') {


      if (_t.hasClass('submit-track-form')) {



        if (_t.find('*[name=source]').eq(0).val() == '') {


          show_notice("success - Source field cannot be blank");
          return false;

        }

        if (_t.find('*[name=title]').eq(0).val() == '') {


          show_notice("success - Title field cannot be blank");
          return false;

        }

      }
    }
    if (e.type == 'change') {
      if (_t.hasClass('dzsvg-change-playlist')) {


        if (get_query_arg(window.location.href, 'dzsvg_gallery_slug') != _t.val()) {


          var newurl = window.location.href;

          newurl = add_query_arg(newurl, 'the-video', 'NaN')
          newurl = add_query_arg(newurl, 'dzsvg_gallery_slug', _t.val())
          if (get_query_arg(window.location.href, 'dzsvg_gallery_slug') == '') {

            newurl = add_query_arg(newurl, 'dzsvg_gallery_slug', 'NaN')
          }


          window.location.href = newurl;
        }
      }
      if (_t.attr('name') == 'is_buyable') {

        if (_t.prop('checked')) {

          $('.price-conglomerate').addClass('active');
        } else {
          // -- you can see typing is slow now ... lets see later...
          $('.price-conglomerate').removeClass('active');
        }
      }
      if (_t.attr('name') == 'thumbnail') {

        if (_t.val()) {
          if (_t.parent().find('.preview-thumb-con').length > 0) {
            var _cach = _t.parent().find('.preview-thumb-con').eq(0);

            _cach.addClass('has-image');
            _cach.css('background-image', 'url(' + _t.val() + ')');
          }
        }
      }
      if (_t.attr('name') == 'type') {

        if (_t.parent().parent().hasClass('submit-track-form')) {
          _con = _t.parent().parent();


          _con.removeClass('type-video type-youtube  type-vimeo ');

          _con.addClass('type-' + _t.val());

          var uploader_type = _t.val();
          if (_t.val() === 'video') {
          }

          if (_t.val() === 'youtube') {
          }
        }
      }
      if (_t.attr('name') === 'source') {
        var _con = null;


        if (_t.parent().parent().parent().hasClass('submit-track-form')) {
          _con = _t.parent().parent().parent();
        }
        if (_con.hasClass('type-youtube') || _con.hasClass('type-vimeo')) {
        }


        upload_hide_upload_field(_t, {'called_from': 'change source'});
      }
    }
  }


  function cancel_submit(_t) {


    var _c = $('.dzs-upload-con').eq(0);

    _c.removeClass('disabling');
    _c.css('height', 'auto');


    var _con = null;

    if (_t.parent().parent().parent().hasClass('submit-track-form')) {
      _con = _t.parent().parent().parent();
    }


    if (_con) {
      _con.removeClass('phase2');
      //_con.slideUp('fast');
    }

    var _cach = _t.parent().parent();
    if (_cach.hasClass('parameters-con')) {
      _cach.find('.main-upload-options-con').eq(0).removeClass('active').slideUp('fast');
    }

  }

  function handle_click(e) {

    var _t = $(this);
    var _con = null;

    if (e.type == 'click') {


      if (_t.hasClass('cancel-upload-btn')) {

        cancel_submit(_t);


        return false;

      }
      if (_t.hasClass('btn-submit')) {


        var _c = $('.id-upload-mp3').eq(0);
        upload_hide_upload_field(_c, {'called_from': 'btn-submit'});
        return false;
      }
      if (_t.hasClass('single-submit-for-main-media')) {


        var _c = $('.id-upload-mp3').eq(0);
        upload_hide_upload_field(_c, {'called_from': 'submit_for_main_media'});
        return false;
      }


    }
  }


  function init_tinymces(_con) {


    _con.find('.with-tinymce').each(function () {
      var _t = $(this);

      var _con = _t.parent().parent().parent().parent();


      var trackid = (_con.find('*[name=track_id]').eq(0).val());

      _t.attr('id', 'fortinymce' + trackid);
      init_try_tinymce(_t);
    })
  }

  function upload_hide_upload_field(arg, pargs) {


    var margs = {
      'called_from': 'default'
    }


    if (pargs) {
      margs = $.extend(margs, pargs);
    }


    var _t = arg;
    var _con = null;
    var type = 'video';
    var _mainUploadOptionsCon = null;

    if (_t.parent().parent().parent().hasClass('submit-track-form')) {
      _con = _t.parent().parent().parent();


      _mainUploadOptionsCon = _con.find('.main-upload-options-con');


    }

    var tval = '';

    if (_t.val) {
      tval = _t.val();
    }




    if (_con) {


      var source = _con.find('*[name=source]').eq(0).val();


      if (source) {

      } else {


        if (_con.hasClass('type-youtube') || _con.hasClass('type-vimeo')) {

          show_notice('error - ' + 'Input a id or link')
          return false;
        }
      }


      if (_con.hasClass('type-youtube')) {
        type = 'youtube';
      }

      if (_con.hasClass('type-vimeo')) {
        type = 'vimeo';
      }

      // - subitting
      _mainUploadOptionsCon.addClass('active');
      _mainUploadOptionsCon.show();

      var ch = _mainUploadOptionsCon.height();

      _mainUploadOptionsCon.css('height', '0');


      _mainUploadOptionsCon.animate({
        'height': ch
      }, {
        queue: false
        , duration: 300
        , complete: function () {
          $(this).css('height', 'auto');
        }
      });


      var _auxcon = _con;
      setTimeout(function () {


        if (_auxcon.find('.dzs-tabs').get(0).api_handle_resize) {

          _auxcon.find('.dzs-tabs').get(0).api_handle_resize();
        }
      }, 50);
      setTimeout(function () {


        if (_auxcon.find('.dzs-tabs').get(0).api_handle_resize) {

          _auxcon.find('.dzs-tabs').get(0).api_handle_resize();
          _auxcon.find('.dzs-tabs').eq(0).find('.tab-content').eq(0).addClass('active');
        }
      }, 150);
      setTimeout(function () {

        _auxcon.addClass('phase2');
      }, 100);


      // -- try to generate image
      if (window.dzsvp_try_to_generate_image == 'on') {


        $('.dzs-single-upload-preview-img').addClass('generating-thumb');


        // source = _auxcon.find('*[name=source]').eq(0).val();


        if (type == 'video') {


          var aux43 = '<div class="screenshot-canvas-con';


          if (window.dzsvg_settings && window.dzsvg_settings.debug_mode == 'on') {
            aux43 += ' debug-mode';
          }


          aux43 += '"><video width="600" height="400" src="' + source + '"></video><canvas width="600" height="400"></canvas></div>';


          _auxcon.after(aux43);


          var _c = _auxcon.next();
          var canvas = _c.find('canvas').get(0);
          var ctx = canvas.getContext('2d');
          var video = _c.find('video').get(0);

          _c.find('video').get(0).currentTime = 5;


          setTimeout(function () {

            ctx.drawImage(video, 0, 0);


            var xhr = null;

            var canvasData = canvas.toDataURL("image/png");
            var xmlHttpReq = false;
            if (window.XMLHttpRequest) {
              xhr = new XMLHttpRequest();
            }

            xhr.open('POST', dzsvg_settings.dzsvg_site_url + '?dzsvg_action=savescreenshot&name=' + source, false);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {

              var response = xhr.responseText;

              if (xhr.readyState == 4 && xhr.status == 200) {

                _auxcon.find('*[name=thumbnail]').val(response);
                _auxcon.find('*[name=thumbnail]').trigger('change');
                _auxcon.find('.dzs-single-upload-preview-img').css('background-image', 'url(' + response + ')');
                $('.dzs-single-upload-preview-img').removeClass('generating-thumb');

                $('.screenshot-canvas-con:not(.debug-mode)').remove();

              }
            }
            xhr.send("imgData=" + canvasData);

          }, 4500);

        }


        if (type === 'youtube') {


          if (source.indexOf('youtube.com') > -1) {
            source = get_query_arg(source, 'v');
          } else {
            if (source.indexOf('youtu.be/') > -1) {
              source = source.replace('https://youtu.be/', '');
            }
          }


          var response = 'https://img.youtube.com/vi/' + source + '/0.jpg';
          // $thumb = "";

          _auxcon.find('*[name=thumbnail]').val(response);
          _auxcon.find('*[name=thumbnail]').trigger('change');
          _auxcon.find('.dzs-single-upload-preview-img').css('background-image', 'url(' + response + ')');
          $('.dzs-single-upload-preview-img').removeClass('generating-thumb');


        }
        if (type === 'vimeo') {

          source = source.replace('https://vimeo.com/', '');
          var data = {
            action: 'nothing'
          };


          $.ajax({
            type: "POST",
            url: dzsvg_settings.dzsvg_site_url + '?dzsvg_action=get_vimeo_thumb&vimeo_id=' + source,
            data: data,
            success: function (response) {

              var _auxcon = $(document);

              _auxcon.find('*[name=thumbnail]').val(response);
              _auxcon.find('*[name=thumbnail]').trigger('change');
              _auxcon.find('.dzs-single-upload-preview-img').css('background-image', 'url(' + response + ')');
              $('.dzs-single-upload-preview-img').removeClass('generating-thumb');

            }
          })
        }

      }


    }

    if (_t.parent().hasClass('dzs-upload-con')) {
      _con = _t.parent();

      _con.addClass('disabling');

      _con.animate({
        'height': 0
      }, {
        queue: false
        , duration: 300
      });
    }
  }

  function init_try_tinymce(_c) {

    if (_c.hasClass('tinymce-activated')) {
      return false;
    }

    if (window.tinyMCE) {


      tinyMCE.baseURL = window.dzsvp_plugin_url + 'tinymce';
      tinyMCE.init({
        selector: '#' + _c.attr('id')
        , base: window.dzsvp_plugin_url + 'tinymce/'
        , menubar: false
        , toolbar: 'styleselect | bold italic | link image code bullist numlist'
        , plugins: 'code,lists,link,textcolor,wordcount'
        , selection_toolbar: 'bold italic | quicklink h2 h3 blockquote code fontsize '
      });

      _c.addClass('tinymce-activated');


    } else {

      if (window.tinymce_trying_to_load != true) {

        window.tinymce_trying_to_load = true;

        $.getScript(window.dzsvp_plugin_url + 'tinymce/tinymce.min.js', function (data, textStatus, jqxhr) {

          init_try_tinymce(_c);
        })
      }
    }
  }


  function action_file_upload_start(pfile, pargs) {


    var margs = {
      'called_from': 'default'
    }


    if (pargs) {
      margs = $.extend(margs, pargs);
    }


    var uploader_type = 'video';



    if (margs.cthis && margs.cthis.hasClass && margs.cthis.hasClass('single-upload-for-main-media')) {


      if (uploader_type == 'video') {
        var filename = String(pfile.name).toLowerCase();



        if (String(filename).indexOf('.mp4') > -1 || String(filename).indexOf('.m4v') > -1 || String(filename).indexOf('.mov') > -1) {
          upload_hide_upload_field($('input[name="source"]'), {'called_from': 'action_file_upload_start'});
          $('.main-upload-options').addClass('loader-active');
          window.dzs_uploader_force_progress($('.main-upload-options'));
        } else {

          show_notice('error - ' + 'Only videos are allowed!')

          window.dzsuploader_stop = true;

        }
        var name = String(filename);
        name = name.replace('.mp3', '');
        name = name.replace('.mp4', '');
        name = name.replace('.m4v', '');
        name = name.replace('.mov', '');


        if (pargs.cthis.prev().hasClass('id-upload-mp3')) {

          $('*[name="title"]').val(name);
        }
      }


      var _c = $('.main-upload-options').eq(0);


      init_tinymces(_c);

      _c.css('height', 'auto');

      var h = (_c.height());


      _c.css('height', '1px');

      setTimeout(function () {
        _c.animate({
          'height': h
        }, {
          queue: false
          , duration: 300
          , complete: function () {
            $(this).css('height', 'auto');
          }
        });

        _c.addClass('main-option-active');
      }, 100);
      _c.addClass('main-option-active');


    }




  }


  function action_file_uploaded(argresp, pargs, matches) {

    var uploader_type = 'video';


    if (argresp.report == 'error') {
      show_notice(argresp);


      $('.cancel-upload-btn').eq(0).trigger('click');


    }




    if (uploader_type == 'album') {

      var name = String(pargs.file.name);
      name = name.replace('.mp3', '');

      var _c = $('.upload-track-options-con').eq(0);
      _c.find('input[name*="track_title"]').each(function () {
        var _t2 = $(this);

        if (name == _t2.val()) {
          var _c2 = _t2.parent().parent();

          _c2.find('input[name*="track_source"]').eq(0).val(pargs.final_location);
        }

      })
    }


    var _c = $('.main-upload-options').eq(0);
    _c.addClass('main-option-active');



  }

  $('.simple-fade-carousel').each(function () {
    var cthis = $(this);
    var currNr = 0;
    var time_interval = 5000;
    var int_changer = 0;

    cthis.children().eq(currNr).addClass('active');

    int_changer = setInterval(function () {
      currNr++;
      if (currNr >= cthis.children().length) {
        currNr = 0;
      }
      ;
      cthis.children().removeClass('active');
      cthis.children().eq(currNr).addClass('active');

    }, time_interval);

  })


  function load_statistics(_con) {


    if (window.google && window.google.charts) {


      if (window.google.visualization) {



        var data = {
          action: 'dzsvg_ajax_get_statistics_html',
          postdata: _con.find('.stats-btn').eq(0).attr('data-playerid')
        };


        $.ajax({
          type: "POST",
          url: window.dzsvg_site_url + '/?dzsvg_action=load_charts_html',
          data: data,
          success: function (response) {



            _con.append('<div class="stats-container">' + response + '</div>')

            setTimeout(function () {

              var _c = _con.find('.stats-container');
              _c.addClass('loaded');


              var auxr = /<div class="hidden-data">(.*?)<\/div>/g;
              var aux = auxr.exec(response);


              var aux_resp = '';
              if (aux[1]) {
                aux_resp = aux[1];
              }


              var resp_arr = [];

              try {
                resp_arr = JSON.parse(aux_resp);
              } catch (err) {

              }


              var arr = [];


              arr[0] = [];
              for (var i in resp_arr['labels']) {



                arr[0].push(resp_arr['labels'][i]);
              }
              for (var i in resp_arr['lastdays']) {


                i = parseInt(i, 10);

                arr[i + 1] = [];
                for (var j in resp_arr['lastdays'][i]) {

                  j = parseInt(j, 10);

                  var val4 = (resp_arr['lastdays'][i][j]);

                  if (j != 0) {

                    val4 = parseFloat(val4);
                  }


                  if (!isNaN(val4) ) {
                    resp_arr['lastdays'][i][j] = val4;
                  }
                  arr[i + 1].push(resp_arr['lastdays'][i][j]);
                }

              }


              var data = google.visualization.arrayToDataTable(arr);

              var options = {

                backgroundColor: '#444444'
                , height: '300'
                , legend: {position: 'top', maxLines: 1}
                , chart: {
                  title: 'Track Performance'
                  , backgroundColor: '#444444'
                }
                , chartArea: {
                  backgroundColor: '#444444'
                }
                , tooltip: {isHtml: true}
              };


              var chart = new google.visualization.AreaChart(_con.find('.trackchart').get(0));
              chart.draw(data, options);


              auxr = /<div class="hidden-data-time-watched">(.*?)<\/div>/g;

              aux = auxr.exec(response);


              aux_resp = '';
              if (aux[1]) {
                aux_resp = aux[1];
              }


              resp_arr = [];


              try {
                resp_arr = JSON.parse(aux_resp);
              } catch (err) {

              }



              arr = [];


              arr[0] = [];
              for (var i in resp_arr['labels']) {




                arr[0].push(resp_arr['labels'][i]);
              }
              for (var i in resp_arr['lastdays']) {


                i = parseInt(i, 10);

                arr[i + 1] = [];
                for (var j in resp_arr['lastdays'][i]) {

                  j = parseInt(j, 10);


                  var val4 = (resp_arr['lastdays'][i][j]);

                  if (j != 0) {

                    val4 = parseInt((parseFloat(val4) / 60), 10);
                  }


                  if (isNaN(val4) == false) {
                    resp_arr['lastdays'][i][j] = val4;
                  }
                  arr[i + 1].push(resp_arr['lastdays'][i][j]);
                }

              }



              data = google.visualization.arrayToDataTable(arr);

              options = {

                color: '#bcb36b'
                , colors: ['#e0d365', '#e6693e', '#ec8f6e', '#f3b49f', '#f6c7b6']
                , backgroundColor: '#444444'
                , height: '300'
                , legend: {position: 'top', maxLines: 3}
                , bar: {groupWidth: "70%"}
                , chart: {
                  title: 'Track Performance'
                  , backgroundColor: '#444444'
                }
                , chartArea: {
                  backgroundColor: '#444444'
                }
                , tooltip: {isHtml: true}
              };


              var chart2 = new google.visualization.ColumnChart(_con.find('.trackchart-time-watched').get(0));
              chart2.draw(data, options);


              auxr = /<div class="hidden-data-month-viewed">(.*?)<\/div>/g;

              aux = auxr.exec(response);


              aux_resp = '';
              if (aux[1]) {
                aux_resp = aux[1];
              }


              resp_arr = [];


              try {
                resp_arr = JSON.parse(aux_resp);
              } catch (err) {

              }



              arr = [];


              arr[0] = [];
              for (var i in resp_arr['labels']) {




                arr[0].push(resp_arr['labels'][i]);
              }
              for (var i in resp_arr['lastdays']) {


                i = parseInt(i, 10);

                arr[i + 1] = [];
                for (var j in resp_arr['lastdays'][i]) {

                  j = parseInt(j, 10);


                  var val4 = (resp_arr['lastdays'][i][j]);

                  if (j != 0) {

                    val4 = parseFloat(val4);
                  }


                  if (isNaN(val4) == false) {
                    resp_arr['lastdays'][i][j] = val4;
                  }
                  arr[i + 1].push(resp_arr['lastdays'][i][j]);
                }

              }



              data = google.visualization.arrayToDataTable(arr);

              options = {

                color: '#bcb36b'
                , colors: ['#66a4e0', '#e6693e', '#ec8f6e', '#f3b49f', '#f6c7b6']
                , backgroundColor: '#444444'
                , height: '300'
                , legend: {position: 'top', maxLines: 3}
                , bar: {groupWidth: "70%"}
                , chart: {
                  title: 'Track Performance'
                  , backgroundColor: '#444444'
                }
                , chartArea: {
                  backgroundColor: '#444444'
                }
                , tooltip: {isHtml: true}
              };



              var chart3 = new google.visualization.ColumnChart(_con.find('.trackchart-month-viewed').get(0));
              chart3.draw(data, options);


              _c.slideDown("fast");

              setTimeout(function () {

                $(this).css('height', 'auto');
              }, 400);



            }, 100);


          },
          error: function (arg) {


          }
        });


      } else {
        google.charts.load('current', {packages: ['corechart', 'bar']});
        google.charts.setOnLoadCallback(function () {
          load_statistics(_con);
        });
      }


    } else {

      if (window.dzsvg_loading_google_charts) {


      } else {


        var url = 'https://www.gstatic.com/charts/loader.js';

        $.ajax({
          url: url,
          dataType: "script",
          success: function (arg) {





          }
        });


        window.dzsvg_loading_google_charts = true;
      }

      setTimeout(function () {
        load_statistics(_con)
      }, 1000);
    }

  }


  function draw_chart_for_con(_con) {
    var data = google.visualization.arrayToDataTable(
    );
  }


  $(document).on('click', '.stats-btn', handle_mouse);

  function handle_mouse(e) {

    var _t = $(this);
    if (_t.hasClass('stats-btn')) {


      var _con = _t.parent();

      if (_t.hasClass('disabled')) {
        return false;
      }
      _t.addClass('disabled');
      setTimeout(function () {
        _t.removeClass('disabled')
      }, 2000)

      if (_con.find('.stats-container').length) {

        _t.removeClass('active');
        _con.find('.stats-container').each(function () {
          var _t2 = $(this);
          _t2.addClass('transitioning-out').removeClass('loaded');



          _t2.slideUp("fast");


          setTimeout(function () {
            _con.find('.stats-container.transitioning-out').remove()
          }, 400)
        })
      } else {

        _t.addClass('active');
        load_statistics(_con);
      }

    }
  }
});


function onYtEvent(e) {
  
}


