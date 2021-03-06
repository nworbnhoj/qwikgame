docReady(event => {
    initPage();
    addListeners(document.querySelectorAll('.tr-toggle'), 'click', clickTrToggle);
    document.getElementById('hr-any').addEventListener(   'click', clickAnytime,  false);
    document.getElementById('venue-select').addEventListener('click', clickShowMapOption);
    document.getElementById('venue-select').addEventListener('change', clickShowMapOption);
});


winReady(event => {
  venuesMap();
});



function initPage(){}


function clickAnytime(){
    if (this.checked){
        document.getElementById('hr').style.display = 'none';
    } else {
        document.getElementById('hr').style.display = 'block';
    }
}



function clickTrToggle(){
    const ALL_HOURS = 16777215; // binary 11111111111111111111
    var tr = this.parentNode;
    var input = tr.firstElementChild;
    var last = tr.lastElementChild;
    if (input.getAttribute('value') != ALL_HOURS){
        var on = 1;
        var color = 'DarkOrange';
        input.setAttribute('value', ALL_HOURS); 
    } else {
        var on = 0;
        var color = 'LightGrey';
        input.setAttribute('value', 0);
    }
    for (var td of tr.children) {
        td.style.backgroundColor = color;
        td.setAttribute('on', on);        
    }
}


function clickShowMapOption(event){
  showMap(this.value === 'show-map', event.target);
}


function clickMapMarkVenue(event, venueId){
  event.preventDefault();
    
  // add a new option to venueSelect and select it
  let venueSelect = document.getElementById('venue-select');
  let option = document.createElement('option');
  option.value = venueId;
  option.text = venueId.split('|')[0];
  venueSelect.add(option);
  venueSelect.value=venueId;

  showMap(false);
}


function showMap(show, target){
  if (show){
    showMapBelowForm(target);
    document.getElementById('map').style.display = 'block';
    document.getElementById('hr').style.display = 'none';    
  } else {   // hide the map div and show the other form elements
    document.getElementById('map').style.display = 'none';
    if(document.getElementById('hr-any').checked){    
    } else {
        document.getElementById('hr').style.display = 'block';  
    }
  }
}


