docReady(event => {
    initPage();
    try {
      const GAME_SELECT = document.getElementById('id_game');
      GAME_SELECT.addEventListener('change', changeGame);
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

  const friends_field = document.getElementById("id_friends").closest("div.field")
  respondToResize(friends_field, visible => {
    const submit = document.getElementById("appeal_submit");
    if(visible) {
      submit.querySelector(".appeal_all").hidden = true;
      submit.querySelector(".appeal_friends").hidden = false;
     } else {
      submit.querySelector(".appeal_all").hidden = false;
      submit.querySelector(".appeal_friends").hidden = true;
     }
  });
});


window.onload = function() {
  document.querySelectorAll("form:has( .by_day)").forEach(function(form){
    const PLACE_SELECT = document.getElementById('id_place');
    if (PLACE_SELECT){
        PLACE_SELECT.addEventListener("change", function(e) {
          place = this.options[this.selectedIndex]
          updatePlaceHours(place);
        });
    }
  });
}


function initPage(){
    // document.getElementById('name').focus();
}

function changePlace(event){
  showMap(event.target.value === 'show-map');
  // insert Venue availability prompt
  const PLACE_SELECT = document.getElementById('id_place')
  const SELECTED_OPTION = PLACE_SELECT.querySelector('option:checked')
  const PHONE = SELECTED_OPTION.dataset.phone;
  const URL = SELECTED_OPTION.dataset.url
  const LINK = "<a href='" + URL + "' target='_blank'>" + URL + "</a>";
  const PROMPT = "Check Venue availability: " + PHONE + " " + LINK;
  const PROMPT_ELEMENT_ID = "id_availability_prompt"
  let promptElement = document.getElementById(PROMPT_ELEMENT_ID)
  if (!promptElement){
    promptElement = document.createElement("p");
    promptElement.id = PROMPT_ELEMENT_ID
    PLACE_SELECT.insertAdjacentElement('afterend', promptElement)
  }
  promptElement.innerHTML = PROMPT
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

function updatePlaceHours(place){
  hours = [];
  if (place.dataset.hasOwnProperty('hours')){
    hours = place.dataset.hours.split(',')
    hours = hours.flatMap(x => [parseInt(x)]);
  }
  now_weekday = undefined
  if (place.dataset.hasOwnProperty('now_weekday')){
    now_weekday = parseInt(place.dataset.now_weekday);
    now_weekday = Number.isInteger(now_weekday) ? now_weekday % 7 : undefined;
  }
  now_hour = undefined
  if (place.dataset.hasOwnProperty('now_hour')){
    now_hour = parseInt(place.dataset.now_hour);
    now_hour = Number.isInteger(now_hour) ? now_hour % 24 : undefined;
  }
  setDayFields(hours, now_weekday, now_hour);
}

function setPlaceDefault(){
    setPlaceOption();
}
