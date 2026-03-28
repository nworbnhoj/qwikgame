docReady(event => {
    initPage();
    try {
      const GAME_SELECT = document.getElementById('id_game');
      GAME_SELECT.addEventListener('change', changeGame);
      const PLACE_SELECT = document.getElementById('id_place');
      PLACE_SELECT.addEventListener('change', changePlace);
      const MAP_ELEMENT = document.getElementById("map");
      const SHOWMAP_RADIO = document.getElementById('id_place_0');
      const SHOWMAP_DIV = SHOWMAP_RADIO.parentElement.parentElement;
      SHOWMAP_DIV.insertAdjacentElement('afterend', MAP_ELEMENT);
      SHOWMAP_RADIO.removeEventListener('click', form_shut);
    } catch (error) {
      console.log(error);
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
  const PLACE_SELECT = document.getElementById('id_place')
  const PLACE_FIELDSET = PLACE_SELECT.closest('fieldset')
  const PLACE_LEGEND = PLACE_FIELDSET.querySelector('legend');
  const SELECTED_OPTION = PLACE_SELECT.querySelector("input[type='radio']:checked")
  const PHONE = SELECTED_OPTION.dataset.phone;
  const URL = SELECTED_OPTION.dataset.url
  const LINK = "<a href='" + URL + "' target='_blank'>" + URL + "</a>";
  const PROMPT = "Check Venue availability: " + PHONE + " " + LINK;
  const PROMPT_ELEMENT_ID = "id_availability_prompt"
  let promptElement = document.getElementById(PROMPT_ELEMENT_ID)
  if (!promptElement){
    promptElement = document.createElement("p");
    promptElement.id = PROMPT_ELEMENT_ID
    PLACE_LEGEND.append(promptElement);
  }
  promptElement.innerHTML = PROMPT
}

function changeGame(event){
  const GAME_SELECT = document.getElementById('id_game');
  const SELECTED_OPTION = GAME_SELECT.querySelector("input[type='radio']:checked");
  const GAME = SELECTED_OPTION.value;
  const PLACE_SELECT = document.getElementById('id_place');
  let skipped = false;
  PLACE_SELECT.querySelectorAll("input[type='radio']").forEach((option) => {
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
  const PLACE_SELECT = document.getElementById('id_place');
  let radio = PLACE_SELECT.querySelector("[value='"+PLACEID+"']")
  if (radio){
    PLACE_SELECT.value = PLACEID
  } else {
    PLACE_SELECT.value = "placeid"
    // populate the (hidden) placeid input with the new placeid
    const PLACEID_INPUT = document.getElementById('id_placeid');
    PLACEID_INPUT.value = PLACEID;
    // clone the SHOW_MAP option to accomodate the Map selected place
    const SHOWMAP_RADIO = document.getElementById('id_place_0');
    const SHOWMAP_DIV = SHOWMAP_RADIO.parentElement.parentElement;
    const SHOWMAP_LABEL = SHOWMAP_DIV.querySelector('label');
    const CLONE = SHOWMAP_DIV.cloneNode(true);
    SHOWMAP_RADIO.id = 'id_place_showmap';
    SHOWMAP_LABEL.setAttribute('for', SHOWMAP_RADIO.id);
    SHOWMAP_DIV.parentElement.insertBefore(CLONE, SHOWMAP_DIV.nextElementSibling);
    CLONE.querySelector('label').lastChild.data = mark.name;
    radio = CLONE.querySelector("input[type='radio']");
    radio.setAttribute('value', 'placeid');
    radio.setAttribute('data-placeid', PLACEID);
    radio.setAttribute('data-hours', mark.hours);
    radio.setAttribute('data-now_weekday', mark.weekday);
    radio.setAttribute('data-now_hour', mark.hour);
    radio.setAttribute('data-phone', mark.phone);
    radio.setAttribute('data-url', mark.url);
    radio.addEventListener('click', form_shut);
  }
  updatePlaceHours(radio);
  PLACE_SELECT.dispatchEvent(new Event('change'));
  showMap(false);
  form_shut(PLACE_SELECT);
}

function closeMap(){
  const PLACE_SELECT = document.getElementById('id_place');
  PLACE_SELECT.value = 'placeid'
  showMap(false);
}
