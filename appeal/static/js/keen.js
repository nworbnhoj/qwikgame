docReady(event => {
    initPage();
    try {
      const GAME_SELECT = document.getElementById('id_game');
      GAME_SELECT.addEventListener('change', changeGame);
      const VENUE_SELECT = document.getElementById('id_venue');
      VENUE_SELECT.addEventListener('change', changePlace);
      const MAP_ELEMENT = document.getElementById("map");
      const SHOWMAP_RADIO = document.getElementById('id_venue_0');
      const SHOWMAP_DIV = SHOWMAP_RADIO.parentElement.parentElement;
      SHOWMAP_DIV.insertAdjacentElement('afterend', MAP_ELEMENT);
      SHOWMAP_RADIO.removeEventListener('click', form_shut);
      MAP_ELEMENT.addEventListener('change', (event)=>{event.stopPropagation()});
    } catch (error) {
      console.log(error);
      return null;
    }
});


winReady(event => {});


window.onload = function() {
  document.querySelectorAll("form:has( .by_day)").forEach(function(form){
    const VENUE_SELECT = document.getElementById('id_venue');
    if (VENUE_SELECT){
        VENUE_SELECT.addEventListener("change", function(e) {
          place = this.querySelector("input[type='radio']:checked");
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
  const VENUE_SELECT = document.getElementById('id_venue')
  const PLACE_FIELDSET = VENUE_SELECT.closest('fieldset')
  const PLACE_LEGEND = PLACE_FIELDSET.querySelector('legend');
  const PROMPT_DIV = PLACE_LEGEND.querySelector('div.prompt');
  if (PROMPT_DIV){
    const SELECTED_OPTION = VENUE_SELECT.querySelector("input[type='radio']:checked")
    if (SELECTED_OPTION){    
      const PHONE = SELECTED_OPTION.dataset.phone;
      const URL = SELECTED_OPTION.dataset.url
      const LINK_A = PROMPT_DIV.querySelector('a');
      LINK_A.href= URL;
    }
  }
}

function changeGame(event){
  const GAME_SELECT = document.getElementById('id_game');
  const SELECTED_OPTION = GAME_SELECT.querySelector("input[type='radio']:checked");
  const GAME = SELECTED_OPTION.value;
  const VENUE_SELECT = document.getElementById('id_venue');
  let skipped = false;
  VENUE_SELECT.querySelectorAll("input[type='radio']").forEach((option) => {
    if (skipped){
      const PLACE_GAMES = option.dataset.games;
      const OPTION_DIV = option.parentElement.parentElement
      OPTION_DIV.style.display = PLACE_GAMES.includes(GAME) ? 'block': 'none';
    }
    skipped = true;
  });
}

function setPlace(mark){
  const PLACEID = mark.placeid;        
  const VENUE_SELECT = document.getElementById('id_venue');
  let radio = VENUE_SELECT.querySelector("[value='"+PLACEID+"']")
  if (!radio){
    // clone the SHOW_MAP option to accomodate the Map selected place
    const SHOWMAP_RADIO = document.getElementById('id_venue_0');
    const SHOWMAP_DIV = SHOWMAP_RADIO.parentElement.parentElement;
    const SHOWMAP_LABEL = SHOWMAP_DIV.querySelector('label');
    const CLONE = SHOWMAP_DIV.cloneNode(true);
    SHOWMAP_RADIO.id = 'id_venue_showmap';
    SHOWMAP_LABEL.setAttribute('for', SHOWMAP_RADIO.id);
    SHOWMAP_DIV.parentElement.insertBefore(CLONE, SHOWMAP_DIV.nextElementSibling);
    CLONE.querySelector('label').lastChild.data = mark.name;
    radio = CLONE.querySelector("input[type='radio']");
    radio.setAttribute('value', PLACEID);
    radio.setAttribute('data-placeid', PLACEID);
    radio.setAttribute('data-hours', mark.hours);
    radio.setAttribute('data-now_weekday', mark.weekday);
    radio.setAttribute('data-now_hour', mark.hour);
    radio.setAttribute('data-phone', mark.phone);
    radio.setAttribute('data-url', mark.url);
    radio.addEventListener('click', form_shut);
  }
  VENUE_SELECT.value = PLACEID
  updatePlaceHours(radio);
  VENUE_SELECT.dispatchEvent(new Event('change'));
  showMap(false);
  form_shut(VENUE_SELECT);
}

function closeMap(){
  const VENUE_SELECT = document.getElementById('id_venue');
  VENUE_SELECT.value = 'placeid'
  showMap(false);
}
