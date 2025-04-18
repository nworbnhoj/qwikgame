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


winReady(event => {
  // https://stackoverflow.com/questions/1462138/event-listener-for-when-element-becomes-visible
  respondToVisibility = function(element, callback) {
    var options = { root: document.documentElement }
    var observer = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        callback(entry.intersectionRatio > 0);
      });
    }, options);
    observer.observe(element);
  }

  const friends_field = document.getElementById("id_friends").closest("div.field")
  respondToVisibility(friends_field, visible => {
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


function setPlaceDefault(){
    setPlaceOption();
}
