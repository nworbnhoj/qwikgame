const LOGOUT_FRM = document.getElementById('logout_form');

docReady(event => {
  LOGOUT_FRM.addEventListener('submit', clear_cache,false);
});


winReady(event => {});


// deletes the named cache or ALL caches by default
function clear_cache(name) {
  let caches = window.caches;
  caches.keys().then(cacheNames => {
    if (typeof name === 'string') {
      if (cacheNames.includes(name)) {
        cacheNames = [name];
      } else {
        console.log("WARNING: failed to delete missing cache: ", name);
        return;
      }
    }
    cacheNames.forEach(cacheName => {
      caches.delete(cacheName).then(deleted => {
        if (deleted){
          console.log("deleted cache: ", cacheName);
        } else {
          console.log("WARNING: failed to delete cache: ", cacheName);
        }
      });
    });
  });
}