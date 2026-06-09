const CACHE_VERSION = '1.7.86.2';

const BASE_CACHE_FILES = [
    'https://buzzjuice.net/wp-content/uploads/2026/04/BuzzJuice-Logo-2.03-icon192x192.png',
'https://buzzjuice.net/wp-content/uploads/2026/04/BuzzJuice-Logo-2.03-icon192x192.png',
'https://buzzjuice.net/wp-content/uploads/2026/04/BuzzJuice-Logo-2.03-icon512x512.png',
'https://buzzjuice.net/wp-content/uploads/2026/04/BuzzJuice-Logo-2.03-icon512x512.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1136x640.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_640x1136.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2688x1242.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1792x828.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1125x2436.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_828x1792.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2436x1125.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1242x2208.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2208x1242.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1334x750.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_750x1334.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2732x2048.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2048x2732.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2388x1668.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1668x2388.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2224x1668.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1242x2688.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1668x2224.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1536x2048.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2048x1536.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1170x2532.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2532x1170.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2778x1284.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2532x1170.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2556x1179.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1179x2556.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_2796x1290.png',
'https://buzzjuice.net/wp-content/uploads/pwa-splash-screen/splashscreens/icon_1290x2796.png',
'https://buzzjuice.net/wp-content/uploads/2026/04/BuzzJuice-Logo-2.03-icon512x512.png',
'https://buzzjuice.net/2026/02/06/classies-chronicles-the-foundation-phase/',
'https://buzzjuice.net/2025/11/15/patient-infatuation/',
'https://buzzjuice.net/about-us/',
'https://buzzjuice.net/activate/',

];

const OFFLINE_CACHE_FILES = [
     'https://buzzjuice.net/',
];

const NOT_FOUND_CACHE_FILES = [
    'https://buzzjuice.net/',
];

const OFFLINE_PAGE = 'https://buzzjuice.net/';
const NOT_FOUND_PAGE = 'https://buzzjuice.net/';

const CACHE_VERSIONS = {
    content: 'content-v' + CACHE_VERSION,
    notFound: '404-v' + CACHE_VERSION,
    offline: 'offline-v' + CACHE_VERSION,
};

// Define MAX_TTL's in SECONDS for specific file extensions
const MAX_TTL = {
    '/': 3600,
    html: 3600,
    json: 86400,
    js: 86400,
    css: 86400,
    png: 86400,
    jpg: 86400,
};

const CACHE_STRATEGY = {
    default: 'staleWhileRevalidate',
    css_js: 'cacheFirst',
    images: 'cacheFirst',
    fonts: 'cacheFirst',
};

const CACHE_BLACKLIST =  [
//    (str) => {
//        return !str.includes('/wp-admin/') || !str.startsWith('https://buzzjuice.net//wp-admin/');
//    },
];
const neverCacheUrls = [/\/wp-admin/,/\/wp-login/,/preview=true/,/\/cart/,/ajax/,/login/,/https:\/\/buzzjuice.net\/courses\/academy-dashboard\//,/https:\/\/buzzjuice.net\/academy\/create-new-forum\//,/https:\/\/buzzjuice.net\/account-management\//,/https:\/\/buzzjuice.net\/wp-login.php/,/https:\/\/buzzjuice.net\/wp-admin\//,/https:\/\/buzzjuice.net\/shop-account\//,/https:\/\/buzzjuice.net\/streams/,/https:\/\/buzzjuice.net\/social/,/https:\/\/buzzjuice.net\/course/,/https:\/\/buzzjuice.net\/affiliate-area\//];

const PWA_VISIBILITY_BYPASS_PATHS = [];

/**
 * Same path rules as frontend visibility_excludes: exact path or subpaths.
 * @param {string} pathname request URL pathname
 * @returns {boolean}
 */
function pwaForWpVisibilityBypassPath(pathname) {
    if (!PWA_VISIBILITY_BYPASS_PATHS.length) {
        return false;
    }
    var norm = pathname.replace(/\/+$/, '');
    if (norm === '') {
        norm = '/';
    }
    for (var i = 0; i < PWA_VISIBILITY_BYPASS_PATHS.length; i++) {
        var prefix = String(PWA_VISIBILITY_BYPASS_PATHS[i]).replace(/\/+$/, '');
        if (norm === prefix || norm.indexOf(prefix + '/') === 0) {
            return true;
        }
    }
    return false;
}

const SUPPORTED_METHODS = [
    'GET',
];
// Check if current url is in the neverCacheUrls list
function pwaForWpcheckNeverCacheList(url) {
    if ( this.match(url) ) {
        return false;
    }
    return true;
}
/**
 * pwaForWpisBlackListed
 * @param {string} url
 * @returns {boolean}
 */
function pwaForWpisBlackListed(url) {
    return (CACHE_BLACKLIST.length > 0) ? !CACHE_BLACKLIST.filter((rule) => {
        if(typeof rule === 'function') {
            return !rule(url);
        } else {
            return false;
        }
    }).length : false
}

/**
 * pwaForWpgetFileExtension
 * @param {string} url
 * @returns {string}
 */
function pwaForWpgetFileExtension(url) {
    
    if (typeof url === 'string') {
     
        let split_two = url.split('?');
        let split_url = split_two[0];

        let extension = split_url.split('.').reverse()[0].split('?')[0];		
        return (extension.endsWith('/')) ? '/' : extension;
        
    }else{
        return null;
    }            
}
/**
 * pwaForWpgetTTL
 * @param {string} url
 */
function pwaForWpgetTTL(url) {
    if (typeof url === 'string') {
        let extension = pwaForWpgetFileExtension(url);
        if (typeof MAX_TTL[extension] === 'number') {
            return MAX_TTL[extension];
        } else {
            return MAX_TTL["/"];
        }
    } else {
        return MAX_TTL["/"];
    }
}

/**
 * pwaForWpinstallServiceWorker
 * @returns {Promise}
 */
function pwaForWpinstallServiceWorker() {
    return Promise.all(
        [
            caches.open(CACHE_VERSIONS.content)
                .then(
                    (cache) => {
                        
                        if(BASE_CACHE_FILES.length >0){
                        
                            for (var i = 0; i < BASE_CACHE_FILES.length; i++) {
                            
                             pwaForWpprecacheUrl(BASE_CACHE_FILES[i]) 
                       
                            }
                            
                        }
                        
                        //return cache.addAll(BASE_CACHE_FILES);
                    }
                ),
            caches.open(CACHE_VERSIONS.offline)
                .then(
                    (cache) => {
                        return cache.addAll(OFFLINE_CACHE_FILES);
                    }
                ),
            caches.open(CACHE_VERSIONS.notFound)
                .then(
                    (cache) => {
                        return cache.addAll(NOT_FOUND_CACHE_FILES);
                    }
                )
        ]
    )
        .then(() => {
            return self.skipWaiting();
        });
}

/**
 * pwaForWpcleanupLegacyCache
 * @returns {Promise}
 */
function pwaForWpcleanupLegacyCache() {

    let currentCaches = Object.keys(CACHE_VERSIONS)
        .map(
            (key) => {
                return CACHE_VERSIONS[key];
            }
        );

    return new Promise(
        (resolve, reject) => {

            caches.keys()
                .then(
                    (keys) => {
                        return legacyKeys = keys.filter(
                            (key) => {
                                return !~currentCaches.indexOf(key);
                            }
                        );
                    }
                )
                .then(
                    (legacy) => {
                        if (legacy.length) {
                            Promise.all(
                                legacy.map(
                                    (legacyKey) => {
                                        return caches.delete(legacyKey)
                                    }
                                )
                            )
                                .then(
                                    () => {
                                        resolve()
                                    }
                                )
                                .catch(
                                    (err) => {
                                        reject(err);
                                    }
                                );
                        } else {
                            resolve();
                        }
                    }
                )
                .catch(
                    () => {
                        reject();
                    }
                );

        }
    );
}

function pwaForWpprecacheUrl(url) {

    if(!pwaForWpisBlackListed(url)) {
        caches.open(CACHE_VERSIONS.content)
            .then((cache) => {
                cache.match(url)
                    .then((response) => {
                        if(!response) {
                            return fetch(url)
                        } else {
                            // already in cache, nothing to do.
                            return null
                        }
                    })
                    .then((response) => {
                        if(response) {						                                                     
                             fetch(url).then(dataWrappedByPromise => dataWrappedByPromise.text())									
                             .then(data => {
							 if(data){
                                const regex = /<img[^>]+src="(https:\/\/[^">]+)"/g;
                                let m;
                                while ((m = regex.exec(data)) !== null) {
                                    if (m.index === regex.lastIndex) {
                                            regex.lastIndex++;
                                    }
                                    m.forEach((match, groupIndex) => {
                                            if(groupIndex == 1){
                                                if(new URL(match).origin == location.origin){
                                                    fetch(match).
                                                            then((imagedata) => {
                                                                    //console.log(imagedata);
                                                                    cache.put(match, imagedata.clone());

                                                    });
                                                }
                                            }
                                    });
                                }


                            }


					});
											                                                                                						
                            return cache.put(url, response.clone());
                        } else {
                            return null;
                        }
                    });
            })
    }
}

var fetchRengeData = function(event){
    var pos = Number(/^bytes\=(\d+)\-$/g.exec(event.request.headers.get('range'))[1]);
            console.log('Range request for', event.request.url, ', starting position:', pos);
            event.respondWith(
              caches.open(CACHE_VERSIONS.content)
              .then(function(cache) {
                return cache.match(event.request.url);
              }).then(function(res) {
                if (!res) {
                  return fetch(event.request)
                  .then(res => {
                    return res.arrayBuffer();
                  });
                }
                return res.arrayBuffer();
              }).then(function(ab) {
                return new Response(
                  ab.slice(pos),
                  {
                    status: 206,
                    statusText: 'Partial Content',
                    headers: [
                      // ['Content-Type', 'video/webm'],
                      ['Content-Range', 'bytes ' + pos + '-' +
                        (ab.byteLength - 1) + '/' + ab.byteLength]]
                  });
              }));
}

let cachingStrategy = {
        notGetMethods: function(event){
            // If non-GET request, try the network first, fall back to the offline page
            if (event.request.method !== 'GET') {
                event.respondWith(
                    fetch(event.request)
                        .catch(error => {
                            return caches.match(offlinePage);
                        })
                );
                return false;
            }
        },

        fetchFromCache: function(event){
           /* return new Promise(
                            (resolve) => {*/
                return caches.open(CACHE_VERSIONS.content)
                    .then(
                        (cache) => {

                            return cache.match(event.request)
                                .then(
                                    (response) => {

                                        if (response) {

                                            let headers = response.headers.entries();
                                            let date = null;

                                            for (let pair of headers) {
                                                if (pair[0] === 'date') {
                                                    date = new Date(pair[1]);
                                                }
                                            }

                                            if (date) {
                                                let age = parseInt((new Date().getTime() - date.getTime())/1000);
                                                let ttl = pwaForWpgetTTL(event.request.url);

                                                if (age > ttl) {

                                                    return new Promise(
                                                        (resolve) => {

                                                            return fetch(event.request.clone())
                                                                .then(
                                                                    (updatedResponse) => {
                                                                        if (updatedResponse) {
                                                                            cache.put(event.request, updatedResponse.clone());
                                                                            resolve(updatedResponse);
                                                                        } else {
                                                                            resolve(response)
                                                                        }
                                                                    }
                                                                )
                                                                .catch(
                                                                    () => {
                                                                        resolve(response);
                                                                    }
                                                                );

                                                        }
                                                    )
                                                        .catch(
                                                            (err) => {
                                                                return response;
                                                            }
                                                        );
                                                } else {
                                                    return response;
                                                }

                                            } else {
                                                return response;
                                            }

                                        } else {
                                            return null;
                                        }
                                    }
                                )
                                .then(
                                    (response) => {
                                        if (response) {
                                            return response;
                                        } else {
                                            return fetch(event.request.clone(), {cache: "no-store"})
                                                .then(
                                                    (response) => {

                                                        if(response.status < 300) {
                                                            if (~SUPPORTED_METHODS.indexOf(event.request.method) && !pwaForWpisBlackListed(event.request.url)) {
                                                                cache.put(event.request, response.clone());
                                                            }
                                                                return response;
                                                        } else {
                                                            return caches.open(CACHE_VERSIONS.notFound).then((cache) => {
                                                                return cache.match(NOT_FOUND_PAGE);
                                                            })
                                                        }
                                                    }
                                                )
                                                .then((response) => {
                                                    if (response) {
                                                        return response;
                                                    }
                                                    return cachingStrategy.Offlinepage();
                                                })
                                                .catch(
                                                    () => {
                                                        return cachingStrategy.Offlinepage();
                                                    }
                                                );
                                        }
                                    }
                                )
                                .catch(
                                    (error) => {
                                        console.error('  Error in fetch handler:', error);
                                        return cachingStrategy.Offlinepage();
                                    }
                                );
                        }
                    )
            /*})*/

        },
        fetchnetwork: function(event){
            return caches.open(CACHE_VERSIONS.content)
                    .then(
                        (cache) => {
                           return fetch(event.request.clone()).then(function (response) {

                                if(response.status < 300) {
                                    if (~SUPPORTED_METHODS.indexOf(event.request.method) && !pwaForWpisBlackListed(event.request.url)) {
                                        cache.put(event.request, response.clone());
                                    }
                                        return response;
                                }else if(response.status==404){
                                    return cachingStrategy.Notfoundpage();
                                }
                                return cache.match(event.request).then(function (cached) {
                                    return cached || cachingStrategy.Offlinepage();
                                });
                              }).catch(
                                   function () {
                                        return cache.match(event.request).then(function (cached) {
                                            return cached || cachingStrategy.Offlinepage();
                                        });
                                    }
                              );
                        }
                    ).catch(
                           (err) => {
                                return cachingStrategy.Offlinepage();
                            }
                      )
        },
        addCache: function(event,updatedResponse){
            cache.put(event.request, updatedResponse.clone());
             resolve(updatedResponse);
        },
        Offlinepage: function(){
            var fallbackHtml = '<html><head><title>Offline</title></head><body><h1>You are offline</h1><p>Please check your internet connection.</p></body></html>';
            var fallback = new Response(fallbackHtml, {
                status: 503,
                statusText: 'Service Unavailable',
                headers: new Headers({
                    'Content-Type': 'text/html; charset=UTF-8'
                })
            });
            return caches.open(CACHE_VERSIONS.offline).then(function (cache) {
                return cache.match(OFFLINE_PAGE).then(function (r) {
                    return r || cache.match(OFFLINE_PAGE, { ignoreSearch: true });
                });
            }).then(function (response) {
                return response || fallback;
            }).catch(function () {
                return fallback;
            });
        },
        Notfoundpage: function(){
            return caches.open(CACHE_VERSIONS.notFound).then((cache) => {
                return cache.match(NOT_FOUND_PAGE);
            }).then(function (response) {
                if (response) {
                    return response;
                }
                return new Response('<html><head><title>Page Not Found</title></head><body><h1>404 - Page Not Found</h1></body></html>', {
                    status: 404,
                    statusText: 'Not Found',
                    headers: new Headers({
                        'Content-Type': 'text/html; charset=UTF-8'
                    })
                });
            });
        },
        /*Strategies*/
        networkOnlyStrategy: function(event){
            return caches.open(CACHE_VERSIONS.content)
                    .then(
                        (cache) => {
                           return fetch(event.request.clone()).then(function (response) {
                                if(response.status < 300) {
                                    if (~SUPPORTED_METHODS.indexOf(event.request.method) && !pwaForWpisBlackListed(event.request.url)) {
                                        cache.put(event.request, response.clone());
                                    }
                                    return response;
                                }else if(response.status==404){
                                    return cachingStrategy.Notfoundpage();
                                }
                                return cache.match(event.request).then(function (cached) {
                                    return cached || cachingStrategy.Offlinepage();
                                });
                              }).catch(
                                (err) => {
                                        return cachingStrategy.Offlinepage();
                                    }
                              )
                        }
                    ).catch(
                        (err) => {
                           return cachingStrategy.Offlinepage()
                        }
                    );
        },
        cacheFirstStrategy: function(events){
            return cachingStrategy.fetchFromCache(events).catch(
                        (err) => {
                           return cachingStrategy.Offlinepage()
                        }
                    );
        },
        NeworkFirstStrategy: function(events){
            return cachingStrategy.fetchnetwork(events).catch(
                        (err) => {
                            return cachingStrategy.fetchFromCache(events)
                        }
                    ).catch(
                        (err) => {
                           return cachingStrategy.Offlinepage()
                        }
                    );
        }


}

/**
 * Guarantees event.respondWith() always receives a Promise<Response>.
 * Fixes networkFirst when a branch resolves with undefined (e.g. missing 404/offline cache).
 */
function pwaForWpEnsureResponse(promise) {
    return Promise.resolve(promise).then(function (res) {
        if (res && res instanceof Response) {
            return res;
        }
        return cachingStrategy.Offlinepage();
    }).catch(function () {
        return cachingStrategy.Offlinepage();
    });
}


self.addEventListener(
    'install', event => {
        event.waitUntil(
            Promise.all([
                pwaForWpinstallServiceWorker(),
                self.skipWaiting(),
            ])
        );
    }
);

// The activate handler takes care of cleaning up old caches.
self.addEventListener(
    'activate', event => {
        event.waitUntil(
            Promise.all(
                [
                    pwaForWpcleanupLegacyCache(),
                    self.clients.claim(),
                    self.skipWaiting(),
                ]
            )
                .catch(
                    (err) => {
                        self.skipWaiting();
                    }
                )
        );
    }
);
self.addEventListener('online', event => {
    if (navigator.onLine && navigator.standalone === true) {
        isReachable(event.request.url).then(function(online) {
          if (online) {
            //handle online status
            caches.delete(event.request.url);
            console.log('online');
          } else {
            console.log('no connectivity');
          }
        });
    } else {
        //handle offline status
        console.log('offline');
    }
});

function isReachable(url) {
  /**
   * Note: fetch() still "succeeds" for 404s on subdirectories,
   * which is ok when only testing for domain reachability.
   */
  return fetch(url, { method: 'HEAD', mode: 'no-cors' })
    .then(function(resp) {
      return resp && (resp.ok || resp.type === 'opaque');
    })
    .catch(function(err) {
      console.warn('[conn test failure]:', err);
    });
}

self.addEventListener(
    'fetch', event => {
        // Return if the current request url is in the never cache list
        if ( ! neverCacheUrls.every(pwaForWpcheckNeverCacheList, event.request.url) ) {
           //console.log( 'PWA ServiceWorker: URL exists in excluded list of cache.' + event.request.url);
          return;
        }
        if(! neverCacheUrls.every(pwaForWpcheckNeverCacheList, event.request.referrer) ){
           //console.log( 'PWA ServiceWorker: Ref-URL exists in excluded list of cache.' + event.request.referrer);
            return;
        }
        if(pwaForWpisBlackListed(event.request.url)){
            return;   
        }

        const url = new URL(event.request.url);

        if (pwaForWpVisibilityBypassPath(url.pathname)) {
            return;
        }

        path_array = url.pathname.split('/')
        if (event.request.method === 'POST' && path_array.includes('activity')) {
            return event.respondWith((async () => {
                const formData = await event.request.formData();
                const title = formData.get('title');
                const text = formData.get('text');
                const link = formData.get('url');
                const image = formData.get('externalMedia');
                const reader = new FileReader();

                reader.onload = async (event) => {
                    const imageData = event.target.result;

                    const db = await openDatabase();
                    await saveData(db, {
                        id: 'sharedData',
                        title: title,
                        text: text,
                        url: link,
                        image: imageData
                    });

                    console.log('Data saved to IndexedDB');
           
                    // return Response.redirect('/?share-target', 303);
                    return Response.redirect(url.pathname+'/?share-target', 303);
                };

                reader.readAsDataURL(image);
                return Response.redirect(url.pathname+'/?share-target', 303);
                // return Response.redirect('/?share-target', 303);
            })());
        }
        
        // Return if request url protocal isn't http or https
        if ( ! event.request.url.match(/^(http|https):\/\//i) )
            return;
        if ( event.request.referrer.match(/^(wp-admin):\/\//i) )
            return;
                       
        

        if(event.request.method !== 'GET' ){
            event.respondWith(
                fetch(event.request)
                    .catch(error => {
                        return caches.open(CACHE_VERSIONS.offline).then(function(cache) {
                                        return cache.match(OFFLINE_PAGE);
                                      }).then(function(res) {
                                        return res || new Response('', { status: 503, statusText: 'Service Unavailable' });
                                      });
                    })
            );
            return false;
        }
        const destination = event.request.destination;
        switch (destination) {
            case 'style':
            case 'script':
              cachingStrategyType = CACHE_STRATEGY.css_js;
              break;
            case 'document':
              cachingStrategyType = CACHE_STRATEGY.default
              break;
            case 'image': 
                cachingStrategyType = CACHE_STRATEGY.images;
              break;
            case 'font': 
                cachingStrategyType = CACHE_STRATEGY.fonts;
            break;
            // All `XMLHttpRequest` or `fetch()` calls where
            // `Request.destination` is the empty string default value
            default: 
              cachingStrategyType = CACHE_STRATEGY.default
        }
        var cache = null;
        switch(cachingStrategyType){
            case "networkFirst":
               cache = cachingStrategy.NeworkFirstStrategy(event)
            break;
            case "networkOnly":
               cache = cachingStrategy.networkOnlyStrategy(event)
            break;
            //break;
            case "cacheFirst":
            case "staleWhileRevalidate": 
            default:
               cache = cachingStrategy.cacheFirstStrategy(event)
            break;
        }
        event.respondWith(pwaForWpEnsureResponse(cache));

    }
);

function openDatabase() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open('shareTargetDB', 1);
  
      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        db.createObjectStore('sharedData', { keyPath: 'id' });
      };
  
      request.onsuccess = (event) => {
        resolve(event.target.result);
      };
  
      request.onerror = (event) => {
        reject(event.target.error);
      };
    });
  }
  
  function saveData(db, data) {
    return new Promise((resolve, reject) => {
      const transaction = db.transaction('sharedData', 'readwrite');
      const store = transaction.objectStore('sharedData');
  
      const request = store.put(data);
  
      request.onsuccess = () => {
        resolve();
      };
  
      request.onerror = (event) => {
        reject(event.target.error);
      };
    });
  }


self.addEventListener('message', (event) => {

    if(
        typeof event.data === 'object' &&
        typeof event.data.action === 'string'
    ) {
        switch(event.data.action) {
            case 'cache' :               
                pwaForWpprecacheUrl(event.data.url);
                break;
            
            default :
                console.log('Unknown action: ' + event.data.action);
                break;
        }
    }

});


