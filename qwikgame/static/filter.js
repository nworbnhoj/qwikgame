docReady(event => {
    initPage();
    document.getElementById('id_venue_1').addEventListener('click', clickShowMapOption);
    // document.getElementById('id_venue_1').addEventListener('change', clickShowMapOption);
    // document.getElementById('id_venue_1').addEventListener('focus', mapShortcut);
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


function clickMapMarkVenue(event, venueId){
  event.preventDefault();
  let placeid = event.target.getAttribute("placeid");
  let name = event.target.getAttribute("venuename");
  
  // add a new option to venueSelect and select it
  let id = 'id_venue'
  let venueSelect = document.getElementById('id_venue');
  let ord = venueSelect.childElementCount;
  let newId = id + '_' + ord;
  let newOption = venueSelect.lastElementChild.cloneNode(true);
  let newInput = newOption.querySelector('input');
  newInput.id = newId;
  newInput.value = "placeid";
  newInput.checked = true;
  let newLabel = newOption.querySelector('label');
  newLabel.textContent = name; // removes inner <input>
  newLabel.setAttribute('for', newId);
  newLabel.appendChild(newInput)

  // add event listener and send click event to select
  newLabel.onclick = downClick
  venueSelect.appendChild(newOption);
  newLabel.dispatchEvent(new MouseEvent("click", {
    "view": window,
    "bubbles": true,
    "cancelable": false
  }));

  let placeidInput = document.getElementById('id_placeid');
  placeidInput.value = placeid;

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
        showMapBelowField(target);
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
