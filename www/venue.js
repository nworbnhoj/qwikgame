ready(event => {
    initPage();
});


function addMoreListeners(){
    for (var elem of document.querySelectorAll('.revert')) {
        elem.addEventListener('click', clickRevert, false);
    }
    for (var elem of document.querySelectorAll('input.guess')) {
        elem.addEventListener('keydown', keydownInput, false);
    }

    addEvent(document.getElementById('show-edit-venue'),     'click',   clickEdit);
    addEvent(document.getElementById('venue-cancel'),        'click',   clickCancel);
    addEvent(document.getElementById('input#venue-country'), 'keydown', keydownCountry);
}



function initPage(){
    initMap();
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


function keydownInput(){
    guessPlace(
        document.getElementById('input#venue-name').value,
        document.getElementById('input#venue-locality').value,
        document.getElementById('input#venue-admin1').value,
        document.getElementById('input#venue-country').value,
        document.getElementById('input#venue-guess').value
    );
}



function keydownCountry(){
    this.value = this.value.toLocaleUpperCase('en-US');
}



var MSqC = {lat: -36.4497857, lng: 146.43003739999995};


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



function guessPlace(name, locality, admin1, country, div){
    var url = "json/address-autocomplete.php";
    var input = name+', '+locality+', '+admin1+', '+country;
    $.getJSON(url, {input: input}, function(json){
        div.empty();
        div.append($("<hr>"));
        if (json.status == 'OK'){
            var predictions = json.predictions;
            for (i = 0; i < predictions.length; i++) {
                var prediction = predictions[i];
                div.append($('<button/>')
                    .text(prediction.description)
                    .attr('type', 'submit')
                    .attr('class', 'venue guess')
                    .attr('name', 'placeid')
                    .attr('value', prediction.place_id)
                    .click(function(){
                        $('input.guess').removeAttr('required');
                    })
                );
            }
            div.append($("<br><img src='img/powered-by-google.png'>"));
        }
    });
}




