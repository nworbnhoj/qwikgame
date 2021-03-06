///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////// SERVICE WORKER EVENTS


/////////////////////////////////////////////////////////////////////// INSTALL
self.addEventListener('install', (event) => {
 console.log("Service Worker installed.");
});


////////////////////////////////////////////////////////////////////////// PUSH
self.addEventListener('push', function(e) {
  var payload = e.data.json();

  var options = {
    body: payload.body,
    badge: 'img/qwik.icon.48x48.png',
    icon: 'img/qwik.icon.120x120.png',
    vibrate: [64, 32, 16, 8, 4]
  };
  e.waitUntil(
    self.registration.showNotification(payload.title, options)
  );
});


//////////////////////////////////////////////////////////// NOTIFICATION-CLICK
self.addEventListener('notificationclick', function(event) {
  var notification = event.notification;
  switch (event.action) {
    case 'close':
      notification.close();
      break;
    default:
      notification.close();
      const url = new URL("/match.php", self.location.origin).href;
      promiseChain = focusWindow(url);
  }
  event.waitUntil(promiseChain);
});


function focusWindow(url){
  return clients.matchAll({
    type: 'window',
    includeUncontrolled: true
  }).then((windowClients) => {
    let matchingClient = null;

    for (let i = 0; i < windowClients.length; i++) {
      const windowClient = windowClients[i];
      if (windowClient.url === url) {
        matchingClient = windowClient;
        break;
      }
    }

    if (matchingClient) {
      return matchingClient.focus();
    } else {
      return clients.openWindow(url);
    }
  });
}


/////////////////////////////////////////////////////////////////////// MESSAGE
self.addEventListener('message', function handler (event) {
    switch (event.data.command) {
        case 'clearCache':
            if (event.data.key) {
                caches.delete(event.data.key);
            } else {
                clearAllCaches();
            }
            break;
        default:
            throw 'no aTopic on incoming message to ChromeWorker';
    }
});



// https://stackoverflow.com/questions/54376355/clear-workbox-cache-of-all-content
function clearAllCaches(){
  caches.keys().then(cacheNames => {
    cacheNames.forEach(cacheName => {
      caches.delete(cacheName);
    });
  });
}


///////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////// WORKBOX

importScripts('https://storage.googleapis.com/workbox-cdn/releases/4.3.1/workbox-sw.js');


// Cache the Google Fonts stylesheets with a stale-while-revalidate strategy.
workbox.routing.registerRoute(
  /^https:\/\/fonts\.googleapis\.com/,
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'google-fonts-stylesheets',
  })
);


// Cache the underlying font files with a cache-first strategy for 1 year.
workbox.routing.registerRoute(
  /^https:\/\/fonts\.gstatic\.com/,
  new workbox.strategies.CacheFirst({
    cacheName: 'google-fonts-webfonts',
    plugins: [
      new workbox.cacheableResponse.Plugin({
        statuses: [0, 200],
      }),
      new workbox.expiration.Plugin({
        maxAgeSeconds: 60 * 60 * 24 * 365,
        maxEntries: 30,
      }),
    ],
  })
);


// Cache the font-awesome stylesheet with a stale-while-revalidate strategy.
workbox.routing.registerRoute(
  /\/\/netdna\.bootstrapcdn\.com\/font-awesome\/(.*?)\/css\/font-awesome.min.css/,
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'font-awesome-stylesheets',
  })
);


// Cache the underlying font-awesome files with a cache-first strategy for 1 year.
workbox.routing.registerRoute(
  /^https:\/\/netdna\.bootstrapcdn\.com/,
  new workbox.strategies.CacheFirst({
    cacheName: 'font-awesome-icons',
    plugins: [
      new workbox.cacheableResponse.Plugin({
        statuses: [0, 200],
      }),
      new workbox.expiration.Plugin({
        maxAgeSeconds: 60 * 60 * 24 * 365,
        maxEntries: 30,
      }),
    ],
  })
);


workbox.routing.registerRoute(
  /\.(?:png|gif|jpg|jpeg|webp|svg)$/,
  new workbox.strategies.CacheFirst({
    cacheName: 'images',
    plugins: [
      new workbox.expiration.Plugin({
        maxEntries: 60,
        maxAgeSeconds: 30 * 24 * 60 * 60, // 30 Days
      }),
    ],
  })
);


workbox.routing.registerRoute(
  /\.(?:js|css)$/,
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'static-resources',
  })
);


workbox.routing.registerRoute(	
  /(?:json\.php)$/,
  new workbox.strategies.NetworkFirst({
    cacheName: 'json',
  })
);


workbox.routing.registerRoute(
  /(?:favorite|friend|info|match|upload)\.php$/,
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'pages',
  })
);


console.log("serviceWorker loaded.");



