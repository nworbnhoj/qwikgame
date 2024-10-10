docReady(event => {
    initPage();
    document.getElementById('id_place_0').addEventListener('click', clickShowMapOption);
    // document.getElementById('id_place_1').addEventListener('change', clickShowMapOption);
    // document.getElementById('id_place_1').addEventListener('focus', mapShortcut);
});


winReady(event => {
  venuesMap();
});


function initPage(){
    // document.getElementById('name').focus();
}


function clickLoginToggle(){
    var loginForm = document.getElementById('login-form');
    var registerForm = document.getElementById('register-form');
    if(window.getComputedStyle(loginForm).display !== 'none') {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
    } else {
        registerForm.style.display = 'none';
        loginForm.style.display = 'block';
    }
    document.getElementById('name').focus();
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


function clickShowMapOption(event){
  showMap(this.value === 'show-map', event.target);
}


function mapShortcut(){
    let map = document.getElementById('map');
    if(map.style.display == 'none' && this.options.length <= 2){
        showMap(true);
    }
}


function showMap(show=true, target){
    let map = document.getElementById('map');
    if (show){
        showMapBelowField(target);
        map.style.display = 'block';
        map.focus();
    } else {
        map.style.display = 'none';
    }
}
