(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.htmlEntities = htmlEntities;
exports.dzsvg_click_open_embed_ultibox = dzsvg_click_open_embed_ultibox;

var _esjquery = require("../shared/esjquery/js/_esjquery");

function htmlEntities(str) {
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
/**
 *
 * todo: move to separate part
 // -- this is used for video gallery
 // -- @triggered on clicking multisharer button in dzsvg
 */


function dzsvg_click_open_embed_ultibox() {
  const $socialDzsvgBox = jQuery('.dzs-social-box--main-con').eq(0);

  var _t = jQuery(this);

  var $targetGallery = null,
      $targetPlayer = null;
  $socialDzsvgBox.removeAttr('hidden');

  var _par_par_par = _t.parent().parent().parent();

  var _par_par_par_par = _t.parent().parent().parent().parent();

  var _par_par_par_par_par = _t.parent().parent().parent().parent().parent(); // -- if this is a button in video player ?


  if (_par_par_par.hasClass('vplayer')) {
    $targetPlayer = _par_par_par;
  } else {
    if (_par_par_par_par.hasClass('vplayer')) {
      $targetPlayer = _par_par_par_par;
    }
  } // -- if this is a button in video gallery ?


  if (_par_par_par_par_par.hasClass('videogallery')) {
    $targetGallery = _par_par_par_par_par;
  }

  let stringEmbedCodeForSocialNetworks = '';

  if (window.dzsvg_social_feed_for_social_networks) {
    stringEmbedCodeForSocialNetworks = window.dzsvg_social_feed_for_social_networks;
  }

  stringEmbedCodeForSocialNetworks = stringEmbedCodeForSocialNetworks.replace(/&quot;/g, '\'');
  stringEmbedCodeForSocialNetworks = stringEmbedCodeForSocialNetworks.replace('onclick=""', '');
  $socialDzsvgBox.find('.social-networks-con').html(stringEmbedCodeForSocialNetworks);
  let stringEmbedCodeForShareLink = '';

  if (window.dzsvg_social_feed_for_share_link) {
    stringEmbedCodeForShareLink = window.dzsvg_social_feed_for_share_link;
  }

  if (stringEmbedCodeForShareLink) {
    stringEmbedCodeForShareLink = stringEmbedCodeForShareLink.replace('{{replacewithcurrurl}}', window.location.href);
    $socialDzsvgBox.find('.share-link-con').html(stringEmbedCodeForShareLink);
  }

  let stringEmbedCodeForEmbedLink = '';

  if (window.dzsvg_social_feed_for_embed_link) {
    stringEmbedCodeForEmbedLink = window.dzsvg_social_feed_for_embed_link;
  } // -- for single video player


  if (($targetPlayer || $targetGallery) && stringEmbedCodeForEmbedLink) {
    if ($targetPlayer && $targetPlayer.data('embed_code')) {
      jQuery('.embed-link-con').show();
      stringEmbedCodeForEmbedLink = stringEmbedCodeForEmbedLink.replace('{{replacewithembedcode}}', htmlEntities($targetPlayer.data('embed_code')));
      $socialDzsvgBox.find('.embed-link-con').html(stringEmbedCodeForEmbedLink);
    } else {
      if ($targetGallery && stringEmbedCodeForEmbedLink) {
        var iframe_code = '<div style="width: 100%; padding-top: 67.5%; position: relative;"><iframe src=\'' + dzsvg_settings.dzsvg_site_url + '?action=embed_dzsvg&type=' + 'gallery' + '&id=' + $targetGallery.attr('data-dzsvg-gallery-id') + '\'  width="100%" style="position:absolute; top:0; left:0; width: 100%; height: 100%;" scrolling="no" frameborder="0" allowfullscreen allow></iframe>';
        var encodedStr = String(iframe_code).replace(/[\u00A0-\u9999<>\&]/gim, function (i) {
          return '&#' + i.charCodeAt(0) + ';';
        });
        stringEmbedCodeForEmbedLink = stringEmbedCodeForEmbedLink.replace('{{replacewithembedcode}}', encodedStr); // -- inspired from php @generate_embed_code

        $socialDzsvgBox.find('.embed-link-con').html(stringEmbedCodeForEmbedLink);
        setTimeout(function () {
          $socialDzsvgBox.addClass('loaded-item');
        }, 200);
      } else {
        // -- hide embed link if we do not have embed_code
        jQuery('.embed-link-con').hide();
      }
    }
  }

  setTimeout(function () {
    $socialDzsvgBox.addClass('loading-item');
  }, 100);
  setTimeout(function () {
    $socialDzsvgBox.addClass('loaded-item');
  }, 200);
}

const dzsvg_multiSharer_init = () => {
  (0, _esjquery.$es)(document).on('click', '.dzs-social-box--invoke-btn .handle', dzsvg_click_open_embed_ultibox);
};

(0, _esjquery.documentReady)(() => {
  dzsvg_multiSharer_init();
});

},{"../shared/esjquery/js/_esjquery":2}],2:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.documentReady = documentReady;
exports.$es = void 0;
/**
 * v1.0.0
 * @type {esJquery}
 */

let esJquery = class {
  /**
   *
   * @param {string|esJquery} selector
   */
  constructor(selector) {
    if (typeof selector == 'string') {
      this.$el = document.querySelector(selector);
      this.$els = document.querySelectorAll(selector);
    } else {
      this.$el = selector;

      if (isNodeList(selector)) {
        this.$el = selector[0];
      }

      this.$els = [];

      if (isNodeList(selector)) {
        selector.forEach(_el => {
          this.$els.push(_el);
        });
      } else {
        this.$els.push(this.$el);
      }
    }
  }
  /**
   *
   * @param {number} indexNr
   * @returns {esJquery}
   */


  eq(indexNr) {
    if (this.$els.length < indexNr - 1) {
      return $es(this.$els[this.$els.length - 1]);
    }

    return $es(this.$els[indexNr]);
  }

  length() {
    return this.$els.length;
  }

  hide() {
    this.$els.forEach(function (el) {
      el.style.display = 'none';
    });
  }

  clone() {
    // -- @only works on first element
    const cln = this.$el.cloneNode(true);
    this.$el.parentNode.appendChild(cln);
    return cln;
  }

  show() {
    this.$els.forEach(function (el) {
      el.style.display = '';
    });
  }

  addClass(arg) {
    this.$els.forEach(function (el3) {
      el3.classList.add(arg);
    });
    return this;
  }

  removeClass(arg) {
    this.$els.forEach(function (el3) {
      const classesToRemove = arg.split(' ');
      classesToRemove.forEach(classToRemove => {
        el3.classList.remove(classToRemove);
      });
    });
    return this;
  }

  prepend(html) {
    if (typeof html == 'string') {
      const _tempKid = document.createElement("div");

      this.$els.forEach(function (el) {
        try {
          el.appendChild(_tempKid);
          el.insertBefore(_tempKid, el.firstChild);
          _tempKid.outerHTML = html;
        } catch (err) {
          console.error('something went wrong .. ', err, this, el);
        }
      });
    }
  }
  /**
   *
   * @param {number} index
   * @returns {Element}
   */


  get(index) {
    if (index === 0) {
      return this.$el;
    }
  }
  /**
   *
   * @param {function} callback
   */


  each(callback) {
    this.$els.forEach(el => {
      callback($es(el));
    });
  }

  text() {
    if (arguments.length === 0) {
      return this.$el.textContent;
    } else {
      this.$el.textContent = arguments[0];
      return this;
    }
  }

  find(selector) {
    if (this.$el) {
      return new esJquery(this.$el.querySelectorAll(selector));
    }

    return new esJquery(this.$el);
  }

  append(html) {
    if (typeof html == 'string') {
      const _tempKid = document.createElement("div");

      _tempKid.innerHTML = html;
      this.$els.forEach(function (el) {
        try {
          el.appendChild(_tempKid);
        } catch (err) {
          console.error('something went wrong .. ', err, this, el);
        }
      });
    }
  }
  /**
   *
   * @param {string} actionType
   */


  trigger(actionType) {
    var event = new CustomEvent(actionType, {
      bubbles: true,
      detail: 'event'
    });
    this.$el.dispatchEvent(event);
  }

  on(evt, sel, handler) {
    this.$el.addEventListener(evt, function (event) {
      var t = event.target;

      while (t && t !== this) {
        if (t.matches(sel)) {
          handler.call(t, event);
        }

        t = t.parentNode;
      }
    });
    return this;
  }

  hasClass(arg) {
    if (this.$el) {
      return this.$el.classList.contains(arg);
    }

    return $es(null);
  }

  html() {
    const attrArgs = arguments;

    if (attrArgs.length === 0) {
      return this.$el.innerHTML;
    }

    if (attrArgs.length === 1) {
      if (this.$el) {
        this.$el.innerHTML = attrArgs[0];
      }

      return this;
    }
  }

  getLast() {
    if (this.$els.length) {
      return $es(this.$els[this.$els.length - 1]);
    }

    return null;
  }

  val() {
    const attrArgs = arguments;

    if (attrArgs.length === 0) {
      return this.$el.value ? this.$el.value : '';
    }

    if (attrArgs.length === 1) {
      return this.$el.getAttribute(attrArgs[0]);
    }

    return this;
  }

  attr() {
    const attrArgs = arguments;

    if (attrArgs.length === 0) {
      console.log('no attrArgs.. ');
    }

    if (attrArgs.length === 1) {
      return this.$el.getAttribute(attrArgs[0]);
    }

    if (attrArgs.length === 2) {
      this.$els.forEach(function (el) {
        if (el) {
          el.setAttribute(attrArgs[0], attrArgs[1]);
        }
      });
    }

    return $es(this.$el);
  }

  remove() {
    this.$el.remove();
  }

  parent() {
    if (this.$el) {
      return $es(this.$el.parentNode);
    }

    return $es(null);
  }

  prev() {
    return $es(this.$el.previousElementSibling);
  }
  /**
   *
   * @returns {Element}
   */


  getEl() {
    return this.$el;
  }

  children() {
    return $es(this.$el.childNodes);
  }

  serialize() {
    var form = this.$el;
    var field,
        query = '';

    if (typeof form == 'object' && form.nodeName === "FORM") {
      for (let i = 0; i <= form.elements.length - 1; i++) {
        field = form.elements[i];

        if (field.name && field.type !== 'file' && field.type !== 'reset') {
          if (field.type === 'select-multiple') {
            for (let j = 0; j <= form.elements[i].options.length - 1; j++) {
              if (field.options[j].selected) {
                query += '&' + field.name + "=" + encodeURIComponent(field.options[j].value).replace(/%20/g, '+');
              }
            }
          } else {
            if (field.type !== 'submit' && field.type !== 'button') {
              if (field.type !== 'checkbox' && field.type !== 'radio' || field.checked) {
                query += '&' + field.name + "=" + encodeURIComponent(field.value).replace(/%20/g, '+');
              }
            }
          }
        }
      }
    }

    return query.substr(1);
  }

};

function isNodeList(nodes) {
  var stringRepr = Object.prototype.toString.call(nodes);
  return typeof nodes === 'object' && /^\[object (HTMLCollection|NodeList|Object)\]$/.test(stringRepr) && typeof nodes.length === 'number' && (nodes.length === 0 || typeof nodes[0] === "object" && nodes[0].nodeType > 0);
}
/**
 * jQuery, but in es6
 * @returns {esJquery} esjQuery
 * @param arg
 */


const $es = arg => {
  return new esJquery(arg);
};

exports.$es = $es;

function documentReady(callback) {
  new Promise((resolutionFunc, rejectionFunc) => {
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
      resolutionFunc('interactive');
    }

    document.addEventListener('DOMContentLoaded', () => {
      resolutionFunc('DOMContentLoaded');
    }, false);
    setTimeout(() => {
      resolutionFunc('timeout');
    }, 5000);
  }).then(resolution => {
    callback(resolution);
  }).catch(err => {
    callback(err);
  });
}

window.es_document_ready = documentReady;
/** call ajax function
 * {
    'type':'GET',
    'url':'/',
    'data':{},
    'success':null
  }
 */

window.es_ajax = function (pargs) {
  var margs = {
    'method': 'GET',
    'url': '/',
    'data': {},
    'success': null
  };

  if (pargs) {
    margs = Object.assign(margs, pargs);
  }

  let xhr = new XMLHttpRequest();
  xhr.open(margs.method, margs.url);
  var form_data = new FormData();

  for (var key in margs.data) {
    form_data.append(key, margs.data[key]);
  }

  xhr.send(form_data);
  xhr.addEventListener('load', handle_loaded);

  function handle_loaded(e) {
    if (xhr.status !== 200) {
      if (margs.error) {
        margs.error(e, this);
      }
    } else {
      // show the result
      if (margs.success) {
        margs.success(e, this);
      }
    }
  }
};

window.get_query_arg = function (purl, key) {
  if (purl.indexOf(key + '=') > -1) {
    var regexS = "[?&]" + key + "(.+?)(?=&|$)";
    var regex = new RegExp(regexS);
    var regtest = regex.exec(purl);

    if (regtest != null) {
      if (regtest[1]) {
        return regtest[1].replace(/=/g, '');
      } else {
        return '';
      }
    }
  }
};
/**
 *
 * @param {string} purl
 * @param {string} key
 * @param {string} value
 * @returns {*}
 */


window.add_query_arg = function (purl, key, value) {
  key = encodeURIComponent(key);
  value = encodeURIComponent(value);
  var s = purl;
  var pair = key + "=" + value;
  var r = new RegExp("(&|\\?)" + key + "=[^\&]*");
  s = s.replace(r, "$1" + pair);
  var addition = '';

  if (s.indexOf(key + '=') > -1) {} else {
    if (s.indexOf('?') > -1) {
      addition = '&' + pair;
    } else {
      addition = '?' + pair;
    }

    s += addition;
  }

  if (value === 'NaN') {
    var regex_attr = new RegExp('[\?|\&]' + key + '=' + value);
    s = s.replace(regex_attr, '');
  }

  return s;
};

},{}]},{},[1])


//# sourceMappingURL=dzsvg-multisharer.js.map