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
}


function setPlaceDefault(){
    setPlaceOption();
}
