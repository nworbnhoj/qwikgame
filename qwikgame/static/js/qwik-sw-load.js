///////////////// Service Worker Load functions //////////////////

const REFRESH_FAST = 60; // every minute
const REFRESH_SLOW = 900; // every 15 minutes


// Registering Service Worker
if('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').then(registration => {
      console.log("serviceWorker registered");
      if ('PushManager' in window) {
        if (Notification.permission === 'granted') {
          registration.pushManager.getSubscription().then(subscription => {
            if (subscription) {
              console.log("WebPush subscribed");
              setMetaRefresh(REFRESH_SLOW);
            } else if (typeof subscribe === 'function') {
              subscribe(registration);
              console.log("WebPush subscription underway");
            }
          });
        } else {
          console.log("WebPush unauthorised");
          setMetaRefresh(REFRESH_FAST);
        }
      } else {
        console.log("WebPush unsupported");
        setMetaRefresh(REFRESH_FAST);
      }
    }, function(err) {
      console.log("WARNING: serviceWorker registration failed.");
    });
  });
  window.addEventListener('focus', () => {
      navigator.serviceWorker.getRegistration("/webpush/service-worker.js").then((registration) => {
          if (registration) {
              closeNotifications(registration);
          }
      });
  });
};


function closeNotifications(registration) {
  registration.getNotifications().then((notifications) => {
    for (notification in notifications) {
      try {
        notification.close();
      } catch (e) {
        console.info("failed to close notifications");
        break;
      }
    }
  });
}


function setMetaRefresh(seconds) {
  for (var refresh of document.querySelectorAll("meta[http-equiv='refresh'")) {
    refresh.content = seconds;
  }
}
