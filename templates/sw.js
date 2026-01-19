///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////// SERVICE WORKER EVENTS

const VERSION = "{{ version }}";

/////////////////////////////////////////////////////////////////////// INSTALL

self.addEventListener('install', (event) => {
 console.log("Qwik Service Worker installed.");
});


////////////////////////////////////////////////////////////////////// ACTIVATE

self.addEventListener('activate', event => {

});

///////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////// WORKBOX

// https://www.freecodecamp.org/news/implement-a-service-worker-with-workbox-in-a-pwa/#heading-introduction-to-workbox

importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.3.0/workbox-sw.js');


self.skipWaiting();
workbox.core.clientsClaim();


if (workbox) {
  console.log(`Workbox loaded 🎉`);

  const appShell = [
      '{{ css_all_min_url }}',
      '{{ css_map_url }}',
      '{{ css_qwik_url }}',
      '{{ css_reset_url }}',
      '{{ css_small_screen_url }}',
      '{{ css_welcome_url }}',
      '{{ favicon_url }}',
      '{{ font_astrospace_url }}',
      '{{ font_fa_brands_url }}',
      '{{ font_fa_regular_url }}',
      '{{ font_fa_solid_url }}',
      '{{ font_notosans_url }}',
      '{{ icon_url }}',
      '{{ icon_152_url }}',
      '{{ icon_192_url }}',
      '{{ icon_512_url }}',
      '{{ img_creativecommons_url }}',
      '{{ js_map_url }}',
      '{{ js_qwik_url }}',
      '{{ js_qwik_json_url }}',
      '{{ js_qwik_pwa_install_url }}',
      '{{ js_qwik_sw_load_url }}',
      '{{ logo_url }}',
      '{{ logo_152_url }}',
      '{{ logo_192_url }}',
      '{{ logo_512_url }}',
      '{{ manifest_url }}',
      '{{ mp3_chime_url }}',
       '{{ welcome_url }}',
      // '{{ offline_url }}',
  ].map((partialUrl) => `${location.protocol}//${location.host}${partialUrl}`);

  // Precache the shell.
  workbox.precaching.precacheAndRoute(appShell.map(url => ({
      url,
      revision: VERSION,
  })));

  // Serve the app shell from the cache.
  workbox.routing.registerRoute(
    ({url}) => appShell.includes(url),
    new workbox.strategies.CacheOnly()
  );


  // Cache API requests 
  workbox.routing.registerRoute(
    ({ url }) => url.origin.startsWith('/api/'),
    new workbox.strategies.NetworkFirst({
      cacheName: 'api-cache',
      plugins: [
        new workbox.expiration.ExpirationPlugin({
          maxAgeSeconds: 24 * 60 * 60,
          maxEntries: 10,
        }),
      ],
    })
  );


  // Cache images
  workbox.routing.registerRoute(
    ({ request }) => request.destination === 'image',
    new workbox.strategies.StaleWhileRevalidate({
      cacheName: 'image-cache',
    })
  );
  // Serve HTML pages with Network First and offline fallback
  workbox.routing.registerRoute(
    ({ request }) => request.mode === 'navigate',
    async ({ event }) => {
      try {
        const response = await workbox.strategies.networkFirst({
          cacheName: 'pages-cache',
          plugins: [
            new workbox.expiration.ExpirationPlugin({
              maxEntries: 50,
            }),
          ],
        }).handle({ event });
        return response || await caches.match('/offline.html');
      } catch (error) {
        return await caches.match('/offline.html');
      }
    }
  );


} else {
  console.log(`Warning: Workbox didn't load 😬`);
}




/////////////////////////////////////////////////////////////////////// WEBPUSH

// Reload url in all windowClients
self.addEventListener("push", (event) => {
  console.log("Push received");
  event.waitUntil(reload_clients())
  event.waitUntil(sound_chime())
})


function reload_clients() {
  console.log("Reload clients triggered");
  self.clients.matchAll({type: 'window'}).then(clients => {
    clients.forEach(client => {
      client.navigate(client.url);
    });
  })
}


async function sound_chime() {
  console.log("Chime triggered")
  self.clients.matchAll({type: 'window'}).then(clients => {
    if (clients.length > 0) {
      clients[0].postMessage({type: 'chime'});
    }
  });
}


console.log("Qwik Service Worker loaded.");
