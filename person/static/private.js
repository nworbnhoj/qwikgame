const HIDDEN_WEBPUSH_BTN = document.getElementById('webpush-subscribe-button');
const LANGUAGE_SEL = document.getElementById('id_language');
const PRIVATE_FRM = document.forms["private"];
const PUSH_SUPPORT = 'serviceWorker' in navigator && 'PushManager' in window && "Notification" in window;
// the three i18n webpush checkboxes all equivalently represent the Players server preference for Push notifications
const WEBPUSH_CHECKBOXES = document.querySelectorAll("input[type='checkbox'][value='push']");
const WEBPUSH_CKB = WEBPUSH_CHECKBOXES.item(0);
const WEBPUSH_CKB_UNSUPPORTED = WEBPUSH_CHECKBOXES.item(1);
const WEBPUSH_CKB_UNPERMITTED = WEBPUSH_CHECKBOXES.item(2);
let webpush_ckb = WEBPUSH_CKB;
var webpush_clicked = false;

docReady(event => {
    pickWebpushCheckbox();
    webpush_ckb.addEventListener("click", (event) => {
        HIDDEN_WEBPUSH_BTN.click();
    });
});

winReady(event => {});

const observer = new MutationObserver((event) => {
    pickWebpushCheckbox();
});

observer.observe(HIDDEN_WEBPUSH_BTN, {
    childList: true,
});

// Show a single webpush checkbox that represents the current browser support/permission status
function pickWebpushCheckbox() {
    const CHECKED = document.querySelector("input[type='checkbox'][value='push']:checked");
    document.querySelectorAll("input[type='checkbox'][value='push']").forEach((cb) => {
        cb.checked = false;
        cb.closest('div').hidden = true;
    })
    if (!PUSH_SUPPORT) {
        webpush_ckb = WEBPUSH_CKB_UNSUPPORTED;
        console.log('INFO: Push not supported.');
    } else if (Notification.permission === 'denied') {
        webpush_ckb = WEBPUSH_CKB_UNPERMITTED;
        console.log('INFO: Push permission denied.');
    } else if (Notification.permission === 'granted'){
        webpush_ckb = WEBPUSH_CKB;
    } else if (Notification.permission === 'default') {
        webpush_ckb = WEBPUSH_CKB;
        if (CHECKED) {
            Notification.requestPermission().then((permission) => {
                if (permission !== 'default'){
                    pickWebpushCheckbox();
                }
            })
        }
    } 
    webpush_ckb.closest('div').removeAttribute('hidden');
    webpush_ckb.checked = CHECKED;
    updateNotifyPush()
}