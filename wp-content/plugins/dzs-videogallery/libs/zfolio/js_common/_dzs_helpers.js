/**
 * formats the time
 * @param {number} arg
 * @returns {string}
 */
export function formatTime(arg) {

  var s = Math.round(arg);
  var m = 0;
  if (s > 0) {
    while (s > 59) {
      m++;
      s -= 60;
    }
    return String((m < 10 ? "0" : "") + m + ":" + (s < 10 ? "0" : "") + s);
  } else {
    return "00:00";
  }
}

/**
 *
 * @param {string|number} arg
 * @returns {string|*}
 */
export function sanitizeToCssPx(arg) {

  if (String(arg).indexOf('%') > -1 || String(arg).indexOf('em') > -1 || String(arg).indexOf('px') > -1 || String(arg).indexOf('auto') > -1) {
    return arg;
  }
  return arg + 'px';
}


export function format_to_seconds(arg) {

  var argsplit = String(arg).split(':');
  argsplit.reverse();
  var secs = 0;

  if (argsplit[0]) {
    argsplit[0] = String(argsplit[0]).replace(',', '.');
    secs += Number(argsplit[0]);
  }
  if (argsplit[1]) {
    secs += Number(argsplit[1]) * 60;
  }
  if (argsplit[2]) {
    secs += Number(argsplit[2]) * 60;
  }


  return secs;
}

/**
 *
 * @param {string} scriptSrc if no script src - it will just look for var
 * @param {string} checkForVar must be on window property
 * @returns {Promise<any>}
 */
export const loadScriptIfItDoesNotExist = (scriptSrc, checkForVar) => {
  const CHECK_INTERVAL = 50;
  const TIMEOUT_MAX = 5000;
  let checkInterval = 0;
  const loadScript = (scriptSrc, resolve, reject) => {
    var script = document.createElement('script');
    script.onload = function () {
      resolve('loadfromload');
    };
    script.onerror = function () {
      reject();
    };
    script.src = scriptSrc;
    document.head.appendChild(script);
  }

  return new Promise((resolve, reject) => {
    let isAlreadyLoaded = false;
    let isGoingToLoadScript = false;

    function checkIfVarExists() {
      if (window[checkForVar]) {
        clearInterval(checkInterval);
        resolve('loadfromvar');
        return true;
      }
      return false;
    }

    isAlreadyLoaded = checkIfVarExists();
    if (!isAlreadyLoaded) {
      isGoingToLoadScript = true;

      checkInterval = setInterval(checkIfVarExists, CHECK_INTERVAL);

      setTimeout(() => {
        clearInterval(checkInterval);
        reject('timeout');
      }, TIMEOUT_MAX);
    }
    if (!checkForVar) {
      isGoingToLoadScript = true;
    }
    if (!scriptSrc) {
      isGoingToLoadScript = false;
    }

    if (isGoingToLoadScript) {
      clearInterval(checkInterval);
      loadScript(scriptSrc, resolve, reject);
    }

  })
}


export function stringUtilGetSkinFromClass(cclass){
  var arr = /(skin.*?)( |$)/.exec(cclass);

  if(arr && arr[1]){
    return arr[1];
  }

  return '';
}

export function add_query_arg(purl, key, value) {
  key = encodeURIComponent(key);
  value = encodeURIComponent(value);


  var s = purl;
  var pair = key + "=" + value;

  var r = new RegExp("(&|\\?)" + key + "=[^\&]*");



  s = s.replace(r, "$1" + pair);
  var addition = '';
  if (s.indexOf(key + '=') > -1) {


  } else {
    if (s.indexOf('?') > -1) {
      addition = '&' + pair;
    } else {
      addition = '?' + pair;
    }
    s += addition;
  }

  //if value NaN we remove this field from the url
  if (value === 'NaN') {
    var regex_attr = new RegExp('[\?|\&]' + key + '=' + value);
    s = s.replace(regex_attr, '');
  }



  return s;
}



export function get_query_arg(purl, key) {
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