docReady(event => {
    initPage();
    const MAP_OPTION = document.querySelector("[name='place'][value='show-map']");
    MAP_OPTION.addEventListener('input', changeMapRadio);
    MAP_OPTION.addEventListener('deselect', changeMapRadio);
});


winReady(event => {});


function initPage(){
    // document.getElementById('name').focus();
}


function changeMapRadio(event){
  if(event.target.checked){
    showMap(this.value === 'show-map', event.target);
  } else {
    showMap(false)
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
        alert('sorry - the map is not available')    
        console.log("failed to show map"); 
    }
}

function setPlaceDefault(){
    setPlaceOption();
}
