docReady(event => {
    initPage();
    document.getElementById('login-toggle').addEventListener('click', clickLoginToggle,     false);
    document.getElementById('lang-icon').addEventListener(   'click', clickButtonLanguage,  false);
    document.getElementById('lang-select').addEventListener('change', changeSelectLanguage, false);
    document.getElementById('venue-select').addEventListener('click', clickShowMapOption);
    document.getElementById('venue-select').addEventListener('change', clickShowMapOption);
    document.getElementById('venue-select').addEventListener('focus', mapShortcut);
    document.getElementById('manager-toggle').addEventListener('click', clickManagerToggle, false);
    document.getElementById('player-toggle').addEventListener('click', clickPlayerToggle,   false);
});


winReady(event => {
  venuesMap();
});


function initPage(){
    for (var elem of document.querySelectorAll('span.anon')) {
        elem.style.color = getRandomColor();
    }
    document.getElementById('name').focus();
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


function clickManagerToggle(){
    var playerForm = document.getElementById('player-form');
    if(window.getComputedStyle(playerForm).display !== 'none') {
        playerForm.style.display = 'none';
        document.getElementById('manager-toggle').style.display = 'none';
        document.getElementById('manager-form').style.display = 'block';
        document.getElementById('player-toggle').style.display = 'block';
    }
    document.getElementById('name').focus();
}


function clickPlayerToggle(){
    var managerForm = document.getElementById('manager-form');
    if(window.getComputedStyle(managerForm).display !== 'none') {
        managerForm.style.display = 'none';
        document.getElementById('player-toggle').style.display = 'none';
        document.getElementById('player-form').style.display = 'block';
        document.getElementById('manager-toggle').style.display = 'block';
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


function clickMapMarkVenue(event, venueId){
  event.preventDefault();
    
  // add a new option to venueSelect and select it
  let venueSelect = document.getElementById('venue-select');
  let option = document.createElement('option');
  option.value = venueId;
  option.text = venueId.split('|')[0];
  venueSelect.add(option);
  venueSelect.value=venueId;

  showMap(false);
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
        showMapBelowForm(target);
        map.style.display = 'block';
        map.focus();
    } else {
        map.style.display = 'none';
    }
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
