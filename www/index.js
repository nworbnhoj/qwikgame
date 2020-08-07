docReady(event => {
    initPage();
    document.getElementById('login-toggle').addEventListener('click', clickLoginToggle,     false);
    document.getElementById('lang-icon').addEventListener(   'click', clickButtonLanguage,  false);
    document.getElementById('lang-select').addEventListener('change', changeSelectLanguage, false);
    document.getElementById('venue-select').addEventListener('change', changeVenueSelect);
    document.getElementById('venue-select').addEventListener('focus', mapShortcut);
});


winReady(event => {
  venuesMap();
});


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


function changeVenueSelect(){
  showMap(this.value === 'show-map');
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


function showMap(show=true){
    let map = document.getElementById('map');
    if (show){
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
