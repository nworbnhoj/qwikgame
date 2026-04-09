docReady(event => {
    initPage();
    try {
      const GAME_SELECT = document.getElementById('id_game');
      GAME_SELECT.addEventListener('change', changeGame);
      const PLACE_INPUT =document.getElementById('id_place');
      const PLACE_FIELD = PLACE_INPUT.parentElement
      const MAP_ELEMENT = document.getElementById("map");
      const SHOWMAP_RADIO = document.getElementById('id_place_1');
      const SHOWMAP_DIV = SHOWMAP_RADIO.parentElement.parentElement;
      SHOWMAP_DIV.insertAdjacentElement('afterend', MAP_ELEMENT);
      SHOWMAP_RADIO.removeEventListener('click', form_shut);
      MAP_ELEMENT.addEventListener('change', (event)=>{event.stopPropagation()});
    } catch (error) {
      console.log("Warning: failed to relocate map - missing map|form|game");
      return null;
    }
});


winReady(event => {});



function initPage(){
    showMap();
}


function changeGame(event){
  const GAME_SELECT = document.getElementById('id_game');
  const SELECTED_OPTION = GAME_SELECT.querySelector("input[type='radio']:checked");
  const GAME = SELECTED_OPTION.value;
}

function setPlace(mark){
  const PLACEID = mark.placeid;        
  const PLACE_INPUT = document.getElementById('id_place');
  PLACE_INPUT.value = mark.name;
  // populate the (hidden) placeid input with the new placeid
  const PLACEID_INPUT = document.getElementById('id_placeid');
  PLACEID_INPUT.value = PLACEID;
  closeMap()
}

function closeMap(){
  showMap(false);
}
