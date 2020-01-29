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
  /(?:\.json\.php)$/,
  new workbox.strategies.NetworkFirst({
    cacheName: 'json',
  })
);


workbox.routing.registerRoute(
  /(?:account|favorite|friend|info|match|upload)\.php$/,
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'json-updated',
  })
);


workbox.precaching.precacheAndRoute([]);



console.log("Qwikgame Service Worker loaded.");



