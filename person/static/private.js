docReady(event => {
  initPage();
  // document.getElementById('id_notify_email').addEventListener('click', autoSubmit, false);
  // document.getElementById('id_notify_push').addEventListener('click', autoSubmit, false);
  // document.getElementById('webpush-subscribe-button').addEventListener('click', autoSubmit, false);
  // document.getElementById('id_location_auto').addEventListener('click', autoSubmit, false);
  // document.getElementById('id_language').addEventListener('change', autoSubmit, false);
});


winReady(event => {});


function autoSubmit(){
  updateNotifyPush()
  this.form.submit();
}

const observer = new MutationObserver((event) => {
  console.log(event);
  updateNotifyPush()
});
observer.observe(document.getElementById('webpush-message'), {childList: true,});

function initPage(){
  updateNotifyPush()
}

// Link the hidden WebPush UI to the visible qwikgame UI
function updateNotifyPush() {
  const granted = Notification.permission === 'granted';
  const btn_txt = document.getElementById('webpush-subscribe-button').innerText;
  const subscribed = btn_txt.includes('Unsubscribe');
  const push_checkbox = document.getElementById('id_notify_push');
  push_checkbox.disabled = !('serviceWorker' in navigator && 'PushManager' in window && "Notification" in window);
  push_checkbox.checked = granted && subscribed;
  const msg_txt = document.getElementById('webpush-message').innerText;
  const push_info = document.getElementById('id_notify_push_info');
  push_info.innerText = msg_txt ? msg_txt : 'click to enable push notifications';
}