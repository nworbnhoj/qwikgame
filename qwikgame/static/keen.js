docReady(event => {
    initPage();
    const MAP_OPTION = document.querySelector("[name='place'][value='show-map']");
    MAP_OPTION.addEventListener('input', changeMapRadio);
    MAP_OPTION.addEventListener('deselect', changeMapRadio);
    positionMapBelowField(MAP_OPTION);
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


function changeMapRadio(event){
  if(event.target.checked){
    showMap(this.value === 'show-map');
  } else {
    showMap(false)
  }
}


function setPlaceDefault(){
    setPlaceOption();
}
