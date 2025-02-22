///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////// SERVICE WORKER EVENTS

const VERSION = "{{ version }}";

/////////////////////////////////////////////////////////////////////// INSTALL
self.addEventListener('install', (event) => {
 console.log("Service Worker installed.");
});


///////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////// WORKBOX

importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.3.0/workbox-sw.js');

if (workbox) {
  console.log(`Workbox loaded 🎉`);
} else {
  console.log(`Warning: Workbox didn't load 😬`);
}


const appShell = [
    '{{ css_all_min_url }}',
    '{{ css_map_url }}',
    '{{ css_qwik_url }}',
    '{{ css_reset_url }}',
    '{{ css_small_screen_url }}',
    '{{ favicon_url }}',
    '{{ js_map_url }}',
    '{{ js_qwik_url }}',
    '{{ logo_url }}',
    '{{ logo_152_url }}',
    '{{ logo_192_url }}',
    '{{ logo_512_url }}',
    '{{ manifest_url }}',
    // '{{ offline_url }}',
].map((partialUrl) => `${location.protocol}//${location.host}${partialUrl}`);

// Precache the shell.
workbox.precaching.precacheAndRoute(appShell.map(url => ({
    url,
    revision: null,
})));

// Serve the app shell from the cache.
workbox.routing.registerRoute(({url}) => appShell.includes(url), new workbox.strategies.CacheOnly());
console.log("serviceWorker loaded.");



