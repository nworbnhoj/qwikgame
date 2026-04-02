const HIDDEN_WEBPUSH_BTN = document.getElementById('webpush-subscribe-button');
const LANGUAGE_SEL = document.getElementById('id_language');
const PRIVATE_FRM = document.forms["private"];
const PUSH_SUPPORT = 'serviceWorker' in navigator && 'PushManager' in window && "Notification" in window;
const WEBPUSH_CKB = document.querySelector("input[type='checkbox'][value='push']");
var webpush_clicked = false;


docReady(event => {
  initPage();
  WEBPUSH_CKB.addEventListener("click", (event) => {
    HIDDEN_WEBPUSH_BTN.click();
  });
});


winReady(event => {});

const observer = new MutationObserver((event) => {
  WEBPUSH_CKB.disabled = HIDDEN_WEBPUSH_BTN.disabled
  updateNotifyPush()
});
observer.observe(HIDDEN_WEBPUSH_BTN, {childList: true,});


function initPage(){
  if (PUSH_SUPPORT){
    updateNotifyPush()
  } else {
    console.log('WARN: Push not supported.');
    console.log('ServiceWorker: ' + 'serviceWorker' in navigator );
    console.log('PushManager: ' + 'PushManager' in window);
    console.log('Notification: ' + 'Notification' in window);
    WEBPUSH_CKB.disabled = true;
  }
}


// update the visible qwikgame UI to reflect the hidden WebPush UI
function updateNotifyPush() {
  const WAS_CHECKED = WEBPUSH_CKB.checked;
  const granted = Notification.permission === 'granted';
  const subscribed = HIDDEN_WEBPUSH_BTN.innerText.includes('Unsubscribe');
  WEBPUSH_CKB.checked = granted && subscribed;
  const CHANGED = !(WEBPUSH_CKB.checked === WAS_CHECKED);
  console.log('Notification permission: ' + granted);
  console.log('WebPush subscribed: ' + subscribed);
  return CHANGED;
}