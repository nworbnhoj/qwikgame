docReady(event => {
    initPage();
});

winReady(event => {
    venuesMap();
});


function addMoreListeners(){}


function initPage(){}



function venuesMap() {
    const MAP_ELEMENT = document.getElementById('map');
    const LAT = parseFloat(document.getElementById('lat').value);
    const LNG = parseFloat(document.getElementById('lng').value);
    const CENTER = {lat: LAT, lng: LNG};
    const MAP = new google.maps.Map(MAP_ELEMENT, {zoom: 10, center: CENTER, mapTypeID: 'ROADMAP'});
    const GAME = document.getElementById('game').value;
    showMarkers(MAP, GAME);
}


