const EMAIL_CKB = document.getElementById('id_notify_email');
const HIDDEN_WEBPUSH_BTN = document.getElementById('webpush-subscribe-button');
const LANGUAGE_SEL = document.getElementById('id_language');
const LOCATION_CKB = document.getElementById('id_location_auto');
const PRIVATE_FRM = document.forms["private"];
const PUSH_SUPPORT = 'serviceWorker' in navigator && 'PushManager' in window && "Notification" in window;
const WEBPUSH_CKB = document.getElementById('id_notify_push');
var webpush_clicked = false;


docReady(event => {
  initPage();
  EMAIL_CKB.addEventListener('change', autoSubmit, false);
  LOCATION_CKB.addEventListener('change', autoSubmit, false);
  LANGUAGE_SEL.addEventListener('change', autoSubmit, false);
  HIDDEN_WEBPUSH_BTN.addEventListener("click", (event) => {
    webpush_clicked = true;
    showLoader(event.target, 4000);
  });
});


winReady(event => {});


function autoSubmit(){
  const DATA = {
    email:EMAIL_CKB.checked,
    push:WEBPUSH_CKB.checked,
    location:LOCATION_CKB.checked,
    language:LANGUAGE_SEL.value
  };
  console.log("Submitting: ", DATA);
  PRIVATE_FRM.submit();
}


const observer = new MutationObserver((event) => {
  if (updateNotifyPush() && webpush_clicked){
    autoSubmit();
  }
});
observer.observe(HIDDEN_WEBPUSH_BTN, {childList: true,});


function initPage(){
  if (PUSH_SUPPORT){
    updateNotifyPush()
  } else {
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
  return CHANGED;
}