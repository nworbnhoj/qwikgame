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

function setPlace(mark){
  const PLACEID = mark.placeid;        
  const PLACE_SELECT = document.getElementById('id_place');
  let option = PLACE_SELECT.querySelector("[value='"+PLACEID+"']")
  if (option){
    PLACE_SELECT.value = PLACEID
  } else {
    PLACE_SELECT.value = "placeid"
    // populate the (hidden) placeid input with the new placeid
    const PLACEID_INPUT = document.getElementById('id_placeid');
    PLACEID_INPUT.value = PLACEID;
    // configure the temporary placeid option in the place drop-down field
    option = PLACE_SELECT.querySelector("[value='placeid']");
    option.textContent = mark.name;
    option.setAttribute('data-placeid', PLACEID);
  }
  PLACE_SELECT.dispatchEvent(new Event('change'));
  showMap(false);
}

function setPlaceDefault(){
    setPlaceOption("ANY");
}