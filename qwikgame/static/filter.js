docReady(event => {
    initPage();
    const MAP_OPTION = document.querySelector("[name='place'][value='show-map']");
    MAP_OPTION.addEventListener('input', changeMapRadio);
    MAP_OPTION.addEventListener('deselect', changeMapRadio);
    positionMapBelowField(MAP_OPTION);
});


winReady(event => {});


function initPage(){
    // document.getElementById('name').focus();
}


function changeMapRadio(event){
  if(event.target.checked){
    showMap(this.value === 'show-map');
  } else {
    showMap(false)
  }
}


function setPlaceDefault(){
    setPlaceOption("ANY");
}