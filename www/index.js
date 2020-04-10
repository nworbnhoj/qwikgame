docReady(event => {
    initPage();
});

winReady(event => {});


function addMoreListeners(){
    addEvent(document.getElementById('login-toggle'), 'click'  , clickLoginToggle);
    addEvent(document.getElementById('lang-icon'),    'click',  clickButtonLanguage);
    addEvent(document.getElementById('lang-select'),  'change', changeSelectLanguage);
}


function initPage(){
    for (var elem of document.querySelectorAll('span.anon')) {
        elem.style.color = getRandomColor();
    }
    document.getElementById('register-email').focus();
}


function clickLoginToggle(){
    var loginForm = document.getElementById('login-form');
    var registerForm = document.getElementById('register-form');
    if(window.getComputedStyle(loginForm).display !== 'none') {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        document.getElementById('register-email').focus();
    } else {
        registerForm.style.display = 'none';
        loginForm.style.display = 'block';
        document.getElementById('login-email').focus();
    }
}


function clickButtonLanguage(){
    toggle(this);
    var select = document.getElementById('lang-select');
    toggle(select);
    select.focus();
}


function changeSelectLanguage(){
    clearCache('pages');  // ensure that current language pages deleted from cache
    document.getElementById('lang-form').submit();
}


// https://stackoverflow.com/questions/1484506/random-color-generator-in-javascript
function getRandomColor() {
    var letters = '789ABCD';
    var color = '#';
    for (var i = 0; i < 6; i++ ) {
        color += letters[Math.floor(Math.random() * 6)];
    }
    return color;
}
