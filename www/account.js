docReady(event => {
    initPage();
});


winReady(event => {});


function addMoreListeners(){
  addEvent(document.getElementById('notify-push')         , 'click' , clickNotifyPush);
  addEvent(document.getElementById('notify-push-label')   , 'click' , clickNotifyPushLabel);
  addEvent(document.getElementById('account-private-form'), 'submit', submitAccountPrivateForm);
  addEvent(document.getElementById('select-language'),      'change', changeSelectLanguage);
  addEvent(document.getElementById('account-logout-form'),  'submit', submitAccountLogoutForm);
}



function initPage(){
  if (!("Notification" in window) | !('PushManager' in window)) {
    document.getElementById('notify-push').disabled = true;
  } else if ("Notification" in window){
    if (Notification.permission === 'granted'){
      getPushSubscription(true);  // get subscription and populate fields (endpoint, token & key)
    }
  }
}


function checkQuit(){
  return confirm("This will cancel all of your matches and delete your account\nAre you sure?");
}


/* The notify-push checkbox requests notifications to the CURRENT Browser.
 * Browser notifications are independent so it is OK (expected) that the notify-push checkbox
 * will be different in different browsers and devices simultaniously for the same Player.
 * The notify-push checkbox may be disabled if the current browser does not support notifications.
 */
function clickNotifyPush(){
  const notifyPushCheckbox = document.getElementById('notify-push');
  if (notifyPushCheckbox.checked){
    if(Notification.permission === 'denied'){ // requires a manual change in browser
      const name = browserName();
      alert("Currently, 'notifications' are denied in "+name+" browser preferences");
      notifyPushCheckbox.checked = false;
    } else {
      Notification.requestPermission()
      .then(function (permission) {
        if (permission === "denied") {  // User baulked and changed their mind
          notifyPushCheckbox.checked = false;
        } else {
          getPushSubscription(false);
          if (notifyPushCheckbox.disabled){
            const name = browserName();
            alert("Failed to setup notifications in "+name+" browser.");
          }
        }
      });
    }
  } else {  // #notify-push !checked
    clearPushSubscription();
  }
}


function clickNotifyPushLabel(){
  if (document.getElementById('notify-push').disabled){
    alert('Unfortunately, this browser does not support push notifications.');
  }
}


function submitAccountPrivateForm(){
  if (!document.getElementById('notify-push').checked){
    clearPushSubscription();
  }
}


function changeSelectLanguage(){
    clearCache('pages');  // ensure that current language pages deleted from cache
    clearCache('json');
}


function submitAccountLogoutForm(){
  clearCache('pages');
  clearCache('json')
  var endpoint = document.getElementById('push-endpoint').value;
  document.getElementById('logout-endpoint') = endpoint;
}




/* Request a Push Subscription and populate fields ready for submission
 * @param syncWithServer true will  ensure #notify-push checkbox reflects curent server state
 * fields #push-endpoint #push-token & #push-key hold Subscription details from client 
 * field #push-endpoint-sack holds all Push Subscriptions currently held at server 
 * field #notify-push holds user request for push notifications
 */
function getPushSubscription(syncWithServer){
  navigator.serviceWorker.ready
  .then(function(serviceWorkerRegistration) {  // request a push subscription
    var vapidPublicKey = "BFhoDaEHuf_ZF_OxnuPY9h5Jgb8f0-dKLwZFsedRDYJb0C_XDeCLeWijpYZPzQuYDE0tYKoBa8BFZxeoB6VCxII";
    var serverKey = urlBase64ToUint8Array(vapidPublicKey);
    var subscriptionParam = {userVisibleOnly: true, applicationServerKey: serverKey};
    serviceWorkerRegistration.pushManager.subscribe(subscriptionParam)
    .then(function(subscription) {  // prepare subscription details for submission to server
      document.getElementById('push-endpoint').value = subscription.endpoint;
      const token = subscription.getKey('auth');
      const encodedToken = token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null;
      document.getElementById('push-token').value = encodedToken ;
      const key = subscription.getKey('p256dh');
      const encodedKey = key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null ;
      document.getElementById('push-key').value = encodedKey ;

      if (syncWithServer){  // check notify-push checkbox if endpoint exists at server
        const pushSack = document.getElementById('push-endpoint-sack').value;
        const pushEnabled = pushSack.search(subscription.endpoint) >= 0;
        document.getElementById('notify-push').checked = pushEnabled;
      }

    })
    .catch(e => {
      console.log('Failed to create push subscription', e);
      const notifyPushCheckbox = document.getElementById('notify-push');
      notifyPushCheckbox.checked = false;
      notifyPushCheckbox.disabled = true;
    });
  })
 .catch(e => {
   console.log('Failed to create push subscription', e);
   const notifyPushCheckbox = document.getElementById('notify-push');
   notifyPushCheckbox.checked = false;
   notifyPushCheckbox.disabled = true;
  });
}



/* Clear Push Subscription fields ready for submission
 * Field IDs: push-token & push-key
 */
function clearPushSubscription(){
  document.getElementById('push-key').value = '' ;    // remove subscription at server on Submit
  document.getElementById('push-token').value = '' ;  // remove subscription at server on Submit
}


// https://github.com/Minishlink/web-push-php-example/blob/master/src/app.js
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}


// https://stackoverflow.com/questions/9847580/how-to-detect-safari-chrome-ie-firefox-and-opera-browser
function browserName(){
  var isOpera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;
  var isFirefox = typeof InstallTrigger !== 'undefined';
  var isSafari = /constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!window['safari'] || (typeof safari !== 'undefined' && safari.pushNotification));
  var isIE = /*@cc_on!@*/false || !!document.documentMode;
  var isEdge = !isIE && !!window.StyleMedia;
  var isChrome = !!window.chrome && (!!window.chrome.webstore || !!window.chrome.runtime);
  var isEdgeChromium = isChrome && (navigator.userAgent.indexOf("Edg") != -1);
  var isBlink = (isChrome || isOpera) && !!window.CSS;

  return isOpera      ? 'Opera'       : (
         isFirefox    ? 'Firefox'     : (
         isSafari     ? 'Safari'      : (
         isIE         ? 'Explorer'    : (
         isEdge       ? 'Edge'        : (
         isChrome     ? 'Chrome'      : (
         isEdgeChrome ? 'Edge Chrome' : (
         isBlink      ? 'Blink' : 'unrecognised' )))))));
}



