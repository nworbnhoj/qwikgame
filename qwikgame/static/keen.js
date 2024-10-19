docReady(event => {
    initPage();
    const MAP_OPTION = document.querySelector("[name='place'][value='show-map']");
    MAP_OPTION.addEventListener('input', changeMapRadio);
    MAP_OPTION.addEventListener('deselect', changeMapRadio);
});


winReady(event => {
  venuesMap(showUnitCluster=false);
  if (!qwikMap){
    for(input of document.querySelectorAll("input[value='show-map']")){
        input.parentElement.remove(input);
        console.log("removed input[value='show-map']")
    }
  }
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


function changeMapRadio(event){
  if(event.target.checked){
    showMap(this.value === 'show-map', event.target);
  } else {
    showMap(false)
  }
}


function mapShortcut(){
    let map = document.getElementById('map');
    if(map.style.display == 'none' && this.options.length <= 2){
        showMap(true);
    }
}


function showMap(show=true, target){
    let map = document.getElementById('map');
    if (qwikMap && map){
        if (show){
            showMapBelowField(target);
            map.style.display = 'block';
            map.focus();
        } else {
            map.style.display = 'none';
        }
    } else {        
        console.log("failed to show map"); 
    }
}

function setPlaceDefault(){
    setPlaceOption();
}
