docReady(event => {
    initPage();
    try {
      const GAME_SELECT = document.getElementById('id_game');
      GAME_SELECT.addEventListener('change', changeGame);
      const PLACE_INPUT =document.getElementById('id_place');
      const PLACE_FIELD = PLACE_INPUT.parentElement
      const MAP_ELEMENT = document.getElementById("map");
      PLACE_FIELD.insertAdjacentElement('afterend', MAP_ELEMENT);
    } catch (error) {
      console.log("Warning: failed to relocate map - missing map|form|game");
      return null;
    }
});


winReady(event => {
  // https://stackoverflow.com/questions/1462138/event-listener-for-when-element-becomes-visible
  respondToResize = function(element, callback) {
    var options = { root: document.documentElement }
    var observer = new ResizeObserver((entries, observer) => {
      entries.forEach(entry => {
        callback(entry.contentRect.width > 0);
      });
    }, options);
    observer.observe(element);
  }
});



function initPage(){
    showMap();
}


function changeGame(event){
  const GAME_SELECT = document.getElementById('id_game');
  const SELECTED_OPTION = GAME_SELECT.querySelector('option:checked');
  const GAME = SELECTED_OPTION.value;
  const PLACE_SELECT = document.getElementById('id_place');
  for (var i = 3; i < PLACE_SELECT.options.length; i++) {
    var place_option = PLACE_SELECT.options[i]
    var place_games = place_option.dataset.games;
    place_option.style.display = place_games.includes(GAME) ? 'block': 'none';
  }
}

function setPlace(mark){
  const PLACEID = mark.placeid;        
  const PLACE_INPUT = document.getElementById('id_place');
  PLACE_INPUT.value = mark.name;
  // populate the (hidden) placeid input with the new placeid
  const PLACEID_INPUT = document.getElementById('id_placeid');
  PLACEID_INPUT.value = PLACEID;
}

function closeMap(){
  alert('Please select a Place');
}
