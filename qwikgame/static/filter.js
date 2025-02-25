docReady(event => {
    initPage();
    try {
      const PLACE_SELECT = document.getElementById('id_place');
      PLACE_SELECT.addEventListener('change', changePlace);
      const PLACE_FIELD = PLACE_SELECT.parentElement
      const MAP_ELEMENT = document.getElementById("map");
      PLACE_FIELD.appendChild(MAP_ELEMENT);
    } catch (error) {
      console.log("Warning: failed to relocate map - missing map|form|game");
      return null;
    }
});


winReady(event => {});


function initPage(){
    // document.getElementById('name').focus();
}

function changePlace(event){
  showMap(event.target.value === 'show-map');
}


function setPlaceDefault(){
    setPlaceOption("ANY");
}