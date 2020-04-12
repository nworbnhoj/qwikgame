docReady(event => {
    initPage();
    addListeners(document.querySelectorAll('.revert'),       'click', clickRevert);
    addListeners(document.querySelectorAll('input.guess'), 'keydown', keydownGuess);

    document.getElementById('show-edit-venue').addEventListener('click', clickEdit,      false);
    document.getElementById('venue-cancel').addEventListener(   'click', clickCancel,    false);
    document.getElementById('venue-country').addEventListener('keydown', keydownCountry, false);
});


winReady(event => {
    initMap();
});


function initPage(){
}


function clickEdit(){
    document.getElementById('display-venue-div').style.display = 'none';
    document.getElementById('edit-venue-div').style.display = 'block';
}


function clickCancel(){
    document.getElementById('display-venue-div').style.display = 'block';
    document.getElementById('edit-venue-div').style.display = 'none';
}


function clickRevert(){
    var id = this.getAttribute('id');
    var val = this.getAttribute('val');
    document.getElementById(id).value = val;
    document.getElementById('edit-venue-form').style.display = 'block';
}


function initMap() {
    var mapElement = document.getElementById('map');
    var latInput = document.getElementById('venue-lat');
    var lngInput = document.getElementById('venue-lng');
    if (!mapElement){return;}
    if (!latInput){return;}
    if (!lngInput){return;}

    var site = MSqC;   // default value
    var lat = latInput.value;
    var lng = lngInput.value;

    if (isNumeric(lat) && isNumeric(lng)){
        site = {lat: Number(lat), lng: Number(lng)};
    } else {
        var geocoder = new google.maps.Geocoder();
        var name    = document.getElementById('venue-name').value;
        var address = document.getElementById('venue-address').value;
        var country = document.getElementById('venue-country').value;
        var point = {address: name+", "+address+", "+country };

        geocoder.geocode(point, function(results, status) {
            if (status === 'OK') {
                site = results[0].geometry.location;
                latInput.value = site.lat();
                lngInput.value = site.lng();
            } else {
                alert('Geocoding failed: ' + status);
            }
        });
    }

    var map = new google.maps.Map(mapElement, {zoom:14, center:site});
    var marker = new google.maps.Marker({map:map, position:site, draggable:true});
    google.maps.event.addListener(marker, 'dragend', function(evt){
        latInput.value = evt.latLng.lat();
        lngInput.value = evt.latLng.lng();
    });
}



