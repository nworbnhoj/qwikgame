/******************************************************************************
 * Provides functionality to fetch and display Venue Marks on a google Map.
 * A map idle event triggers a process to show a limited number of Markers
 * on a Google Map. Meta Markers for Country are shown preferentially, or
 * replaced by Admin1 markers when available. Likewise, meta Markers are shown
 * for Admin1 or replaced by Locality Markers. And likewise, meta Markers are
 * shown for Locality or replaced by actual Venue Markers. The raw Marks
 * (data underlying Markers) are fetched from the server on demand. A click
 * on a Marker will show a Map InfoWindow with further actions.
 *****************************************************************************/
// TODO extend QWIK_MARKS to be Game:Map(Mark) to avoid clearMarks() on Game change
const QWIK_MARKS = new Map();
const QWIK_REGION = new Map();                  // regionKey:Set(subKeys)
const MAP_REGION = new Map();               // observable subset of QWIK_REGION
const SEARCH_MARKERS = [];

var qwikMap;
var qwikInfowindow;
var mapCenterIdle = null;
    

function venuesMap() {
    if (typeof google == 'undefined'){
        console.log('failed to initiate Venue Map: google undefined');
        return;
    }
    const MAP_ELEMENT = document.getElementById('map');
    const LAT = parseFloat(document.getElementById('id_lat').value);
    const LNG = parseFloat(document.getElementById('id_lng').value);
    const CENTER = (!isNaN(LAT) && !isNaN(LNG)) ? {lat: LAT, lng: LNG} : MSqC;
    const GOOGLE_MAP_OPTIONS = {
      fullscreenControl: true,
      // fullscreenControlOptions: {
      //     position: google.maps.ControlPosition.RIGHT_BOTTOM,
      //   },
      mapTypeID: 'ROADMAP',
      mapTypeControl: false,
      streetViewControl: false,
      zoom: 10,
      zoomControl: true
    };
    qwikMap = new google.maps.Map(MAP_ELEMENT, GOOGLE_MAP_OPTIONS);
    qwikInfowindow = new google.maps.InfoWindow({content: "<div></div>"});
    const MAP = qwikMap;
    const INFOWINDOW = qwikInfowindow;
    MAP.setCenter(CENTER);
    const GAME_OPTIONS = document.querySelectorAll("input[name=game]");
    addListeners(GAME_OPTIONS, 'input', changeGame);

    // setup Places search box in map
    if (SHOW_SEARCH_BOX == 'SHOW') {
      const INPUT = document.getElementById("map-search");
      const SEARCHBOX = new google.maps.places.SearchBox(INPUT);
      MAP.controls[google.maps.ControlPosition.TOP_LEFT].push(INPUT);
      MAP.addListener("bounds_changed", () => {
          SEARCHBOX.setBounds(MAP.getBounds());
      });
      SEARCHBOX.addListener("places_changed", () => {
          searchChangeHandler(SEARCHBOX.getPlaces());
      });
    }

    //setup Close button in map
    const CLOSE = document.createElement("button");
    CLOSE.textContent = 'X';
    CLOSE.title = 'click to close map';
    CLOSE.type = 'button';
    CLOSE.classList.add('map-close');
    CLOSE.classList.add('gmnoprint');
    CLOSE.addEventListener("click", () => {
      setPlaceDefault();
    });
    const CLOSE_DIV = document.createElement("div");
    CLOSE_DIV.appendChild(CLOSE);
    MAP.controls[google.maps.ControlPosition.TOP_RIGHT].push(CLOSE_DIV);
    MAP.addListener('idle', () => {
        mapIdleHandler()
    });
    MAP.addListener('click', (event) => {
        clickHandler(event)
    });    
    MAP.addListener('bounds_changed', () => {
        updateMapRegion();
        fetchSubKeys(Array.from(MAP_REGION.keys()), game());
        showMarks();
    });    
    MAP.addListener('zoom_changed', () => {
        INFOWINDOW.close();
    });
}



/******************************************************************************
 * Relocates the Map Element (contains global qwikMap) as the last element in
 * the Form Field containing the supplied element.
 *
 * element DOM Element indicating the field to locate the Map within
 * @global qwikMap google.maps.Map
 * @return null
 *
 * https://stackoverflow.com/questions/4793604/how-to-insert-an-element-after-another-element-in-javascript-without-using-a-lib
 *****************************************************************************/
function positionMapBelowField(element){
  if(element){
    try {
      const MAP_ELEMENT = document.getElementById("map");
      const ID = element.id
      const FIELD_ID = ID.slice(0,ID.lastIndexOf('_'))
      const FIELD = document.getElementById(FIELD_ID)
      FIELD.parentElement.appendChild(MAP_ELEMENT);
    } catch (error) {
      console.log("Warning: failed to relocate map - missing map|form|game");
      return null;
    }
  }
}


/******************************************************************************
 * Make the Map Element (containing global qwikMap) visible/invisible
 *
 * show Boolean true to make map visible - invisible otherwise
 * @global qwikMap google.maps.Map
 * @return null
 *
 *****************************************************************************/
function showMap(show=true){
    let map = document.getElementById('map');
    if (!map){
        console.log("failed to show map");
        return;  
    }
    if (show){
        map.style.display = 'block';
        map.focus();
    } else {
        map.style.display = 'none';
    }
}


/******************************************************************************
 * Clears all Marks from globals QWIK_MARKS and QWIK_REGION and removes all
 * Markers from qwikMap.
 *
 * @global QWIK_MARKS Map(marker-keys : Marks)
 * @global QWIK_REGION Map(marker-key : Set(marker-sub-keys)
 * @return null
 *****************************************************************************/
function clearMarks(){
  for(const [KEY, MARK] of QWIK_MARKS){
    MARK.marker.setMap(null);    
  }
  QWIK_MARKS.clear();
  QWIK_REGION.clear();
}


/******************************************************************************
 * Discovers the Game of the Form above the Map. (ie the currently Map game)
 *
 * @return the current Game.
 *****************************************************************************/
function game(){
  try {
    const MAP_ELEMENT = document.getElementById("map");
    const FORM = MAP_ELEMENT.closest("form");
    const GAME = FORM.querySelector("[name=game]:checked").value;
    return GAME;
  }
  catch (error) {
    return;
  }
}


function mapIdleHandler(){
  const MAP = qwikMap;
  const GAME = game();
  if(typeof MAP === 'undefined' || typeof GAME === 'undefined'){ return; }
  if(updateMapCenterIdle(MAP.getCenter())){
    const CENTER = mapCenterIdle;
    const REGIONS = getRegions(CENTER);
    if (REGIONS.length == 0){
      fetchMarks(GAME, CENTER, null, REGIONS.join(":"));
    } else {
      REGIONS.sort((a,b) => b.split("|").length - a.split("|").length);
      REGION = REGIONS[0];
      if (REGION.split("|").length < 3){
        const SUB_REGIONS = Array.from(QWIK_REGION.get(REGION));
        fetchSubKeys(SUB_REGIONS, game());
      }
    }
  }
}


function updateMapCenterIdle(center){
  const CENTER = gLatLng(center.lat(), center.lng(), 3); // round to approx 100m at equator 
  if (!CENTER.equals(mapCenterIdle)){
    mapCenterIdle = CENTER;
    return true;
  }
  return false;
}


function searchChangeHandler(places){
  const MAP = qwikMap;
  if (places.length == 0) { return; }
  
  SEARCH_MARKERS.forEach(marker => {
    marker.setMap(null);
  });
  SEARCH_MARKERS.length = 0;
  
  // For each place, get the icon, name and location.
  const BOUNDS = new google.maps.LatLngBounds(null, null);
  places.forEach(place => {
    if (!place.geometry) { return; }
    var open = place.opening_hours ? 'open now' : 'closed';
    var label_origin = new google.maps.Point(13,15)
    const MARKER = new google.maps.Marker({
      icon: { url: ICON_VENUE, labelOrigin: label_origin },
      label: {text:'0', className:'qg_style_mark_label place'},
      map: MAP,
      position: place.geometry.location,
      title: place.name+'\nyou are the first player!\n'+open
    });
    
    SEARCH_MARKERS.push(MARKER);
        
    google.maps.event.addListener(MARKER, 'click', () => {
      setPlace(place.place_id, place.name)
    });    

    if (place.geometry.viewport) { // Only geocodes have viewport.
      BOUNDS.union(place.geometry.viewport);
    } else {
      BOUNDS.extend(place.geometry.location);
    }
  });
  MAP.fitBounds(BOUNDS);
}


function clickHandler(event){
  if (event.placeId) {
    switch (ONCLICK_PLACE_MARKER){
        case 'select':
          requestPlace(event.placeId)
          break;
        case 'noop':
          break;
          console.log('warning: invalid ONCLICK_PLACE_MARKER')
    }
    event.stop();
  }
}


function changeGame(){
  clearMarks();
  fetchMarks(game(), mapCenterIdle, null, '');
}


function vid(address_components, name){
  var vid = [name,'locality','admin1','XX'];
  if (Array.isArray(address_components)){
    for (i = 0; i < address_components.length; i++) {
      address_component = address_components[i];
      types = address_component.types;
      if(types.includes('locality')){
        vid[1] = address_component.short_name;
      } else if(types.includes('administrative_area_level_1')){
        vid[2] = address_component.short_name;
      } else if(types.includes('country')){
        vid[3] = address_component.short_name;
      }
    }
  }
  return vid.join('|');
}


function requestPlace(placeId){
  const MAP = qwikMap;   
  const PLACE_SERVICES = new google.maps.places.PlacesService(MAP);
  const REQUEST = { placeId: placeId, fields: ['place_id', 'name', 'geometry', 'address_components']};
  PLACE_SERVICES.getDetails(REQUEST, (place, status) => {
    if (status === "OK") {
      setPlace(place.place_id, place.name);
    } else {
      console.log(status);
    }
  });
}

function setPlace(placeid, place_name){
  const PLACE_SELECT = document.getElementById('id_place');
  const EXISTS = PLACE_SELECT.querySelector("[value='"+placeid+"']")
  if (EXISTS){
    setPlaceOption(placeid)
  } else {
    // rename the temporary placeid option in the place drop-down field
    let input = PLACE_SELECT.querySelector("[value='placeid']");
    let label =  input.parentElement;
    label.textContent = place_name; // removes inner <input>
    label.appendChild(input) // re-add inner <input>
    input.setAttribute('data-placeid', placeid);
    setPlaceOption('placeid')
    // populate the placeid input with the new placeid
    const PLACEID_INPUT = document.getElementById('id_placeid');
    PLACEID_INPUT.value = placeid;
  }
}

function setPlaceOption(placeid='placeid'){
  const PLACE_SELECT = document.getElementById('id_place');
  INPUT = PLACE_SELECT.querySelector("[value='"+placeid+"']");
  if (INPUT){
    INPUT.dispatchEvent(new MouseEvent("click", {
      "view": window,
      "bubbles": true,
      "cancelable": false
    }));
  } else {
    console.log("failed to find input[value='placeid']")
  }
  showMap(false);
}


/******************************************************************************
 * Responds to a click on the Pin icon on a qwikgame record.
 * The Map is relocated to below the Form containing the Pin, a new Venue
 * Marker is created to and places on the Map, and the Map is focussed on the
 * Pin.
 *
 * @return null
 *****************************************************************************/
function clickMapIcon(event){
  const ELEMENT = event.target;
  const KEY = ELEMENT.dataset.vid;   
  const GAME = ELEMENT.dataset.game;
  fetchMarks(GAME, null, superKey(KEY), '');
  showMapBelowForm(ELEMENT);
  addVenueMark(
    KEY,
    ELEMENT.dataset.lat,
    ELEMENT.dataset.lng,
    ELEMENT.dataset.size,
    GAME
  );
  focusOnMark(KEY, GAME);
}


/******************************************************************************
 * Fetches Marks for keys missing from the QWIK_REGION keySet.
 * @global QWIK_MARKS Map(marker-keys : Marks)
 * @keys
 * @game
 * @return null
 *****************************************************************************/
function fetchSubKeys(keys, game){
  for(const KEY of keys){
    if(!QWIK_REGION.has(KEY) && isRegion(KEY)){
      fetchMarks(game, null, KEY, superKey(KEY));
    }
  }
}


/******************************************************************************
 * An Array of known regions that include the lat-lng coords.
 * @param LatLng coordinates used to filter regions 
 * @return Array of regions (locality|admin1|country)
 *****************************************************************************/
function getRegions(lat, lng){
  if(!lat || !lng){ return []; }
  const REGIONS = new Set();
  for(const [KEY, SUB_KEYS] of QWIK_REGION){
      const MARK = QWIK_MARKS.get(KEY);
      if (MARK
      && MARK.bounds
      && MARK.bounds.contains(latlng)){
        REGIONS.add(KEY);
      }
  }
  return Array.from(REGIONS);
}


/******************************************************************************
 * Updates and returns MAP_REGION - the observable portion of the QWIK_REGION.
 * @global QWIK_REGION Map(marker-keys : Set(sub-marker-keys))
 * @return String Map(marker-keys : Set(sub-marker-keys)) for observable Marks
 *****************************************************************************/
function updateMapRegion(){
  const OBSERVABLE = new Map();
  const MAP = qwikMap;
  const BOUNDS = MAP.getBounds();
  // survey all QWIK_REGIONs for those within MAP BOUNDS
  for (const [SUPER_KEY, SUB_KEYS] of QWIK_REGION){
    for (const KEY of SUB_KEYS){
      if(!QWIK_MARKS.has(KEY)){ continue; }
      const MARK = QWIK_MARKS.get(KEY);
      if(BOUNDS.contains(MARK.center)){
        if(!OBSERVABLE.has(SUPER_KEY)){ OBSERVABLE.set(SUPER_KEY, new Set()); }
        OBSERVABLE.get(SUPER_KEY).add(KEY);
        if(!OBSERVABLE.has(KEY) && isRegion(KEY)){
          OBSERVABLE.set(KEY, new Set()); // include un-fetched regions in OBSERVABLE
        }
      }
    }
  }
  
  // SORT REGIONS from largest to smallest
  const KEYS = Array.from(OBSERVABLE.keys());
  KEYS.sort(function(a,b){
    return OBSERVABLE.get(b).size - OBSERVABLE.get(a).size
  });
  
  // clear MAP_REGION and replace with sorted OBSERVABLE (Map retains insertion order)
  MAP_REGION.clear();
  for(i=0; i<KEYS.length; i++){
    const KEY = KEYS[i];
    MAP_REGION.set(KEY, OBSERVABLE.get(KEY));
  }
  
  return MAP_REGION;
}


/******************************************************************************
 * Shows Markers on the Map representing qwikgame Venues and Venue clusters.
 * The basic idea is to preferentially show the cluster markers that represent
 * the largest number of sub-Markers (and to hide the sub-Markers). And, when
 * the Map is zoomed in to an area represented by a region-Marker, the region-
 * Marker is hidden (and the sub-Markers shown) at some suitable point.
 *
 * JSON requests are made to the server to obtain Marker data for the current
 * Map view-port. This data is stored in the global variables QWIK_MARKS 
 * Map(key:mark) and QWIK_REGION Map(key:Set(sub-key)). 
 
 * Key steps in the process include:
 * - Get global MAP_REGION, the observable subset of the global QWIK_REGION.
 *   Each region Marker key is mapped to a Set of sub-Marker keys - and the 
 *   Map keys are sorted by size of the subMarker Set.
 * - The largest region Marker is set to be visible. Each of it's
 *   super-Markers and subMarkers are set to be hidden, and removed from the 
 *   sorted list. This step is then repeated with the next largest Marker
 *   (in the reduced list).
 * - An exception to the prior step is made when the physical area
 *   represented by the region Marker is larger that of the Map view-port.
 *   In this case the region Marker is hidden and removed from the sorted
 *   list - while the subMarkers remain in the sorted list (to be processed
 *   in turn)
 *
 * @global QWIK_MARKS a Map of marker-keys to Marker-data
 * @global MAP_REGION a Map of marker-keys to a Set of sub-marker-keys
 * @return Array[key] Keys of visible markers
 *****************************************************************************/
function showMarks(){
  const GAME = game();
  let visibleMarks = [];
  const MAP = qwikMap;
  // Use the diagonal distance across the Map as a proxy of Map area
  const MAP_BOUNDS = MAP.getBounds();
  if (!MAP_BOUNDS){ return visibleMarks; }
  const NE = MAP_BOUNDS.getNorthEast();
  const SW = MAP_BOUNDS.getSouthWest();
  const MAP_AREA = haversineDistance(NE.lat(), NE.lng(), SW.lat(), SW.lng());
  
  // create a Set of Region Keys, sorted by the number of sub-Regions within  
  const OBSERVABLE = MAP_REGION;          // a sorted Map of observable regions
  const KEYS = Array.from(OBSERVABLE.keys());    // sorted keys
  while (KEYS.length > 0) {
    const KEY = KEYS.shift();
    const MARK = QWIK_MARKS.get(KEY);
    if(!MARK) { continue; }
    const SUB_KEYS = OBSERVABLE.get(KEY);
    const REGION_AREA = haversineDistance(MARK.n, MARK.e, MARK.s, MARK.w);
    if(2*REGION_AREA > MAP_AREA){             // should the subMarkers be shown? 
      MARK.marker.setVisible(false);
      hideSuperMarkers(KEY, KEYS);
      visibleMarks.concat(showSubMarkers(SUB_KEYS, GAME, MAP_BOUNDS));
    } else if(!SHOW_UNIT_REGION & SUB_KEYS.size === 1){    // is there only 1 subMarker?
      MARK.marker.setVisible(false);
      visibleMarks.concat(showSubMarkers(SUB_KEYS, GAME, MAP_BOUNDS));
    } else {         // otherwise show this Marker and hide super & sub Markers
      MARK.marker.setVisible(true);
      hideSuperMarkers(KEY, KEYS);
      hideSubMarkers(KEY, KEYS);
      visibleMarks.push(KEY);
    }
  }
  return visibleMarks;
}


/******************************************************************************
 * Shows Markers from the supplied Set of Mark keys.
 * Iterates thru the Marks:
 * - Venue Markers are set visible
 * - Region Marks are ignored other than to fetch the sub-marks if an entry
 *   does not already exist in QWIK_REGION.
 * @param marks
 * @param game
 * @global QWIK_REGION a Map of marker-keys to a Set of sub-marker-keys
 * @return Array[key] Visible Markers
 *****************************************************************************/
function showSubMarkers(keys, game, bounds){
  if(!keys) { return; }
  const VISIBLE = [];
  for(const KEY of keys){
    const MARK = QWIK_MARKS.get(KEY);
    if(MARK
    && MARK.marker
    && MARK.center
    && bounds.contains(MARK.center)){
      MARK.marker.setVisible(true);
      if (!QWIK_REGION.has(KEY) && isRegion(KEY)){
        fetchMarks(game, null, KEY, superKey(KEY));
      }
      VISIBLE.push(KEY);
    }
  }
  return VISIBLE;
}


/******************************************************************************
 * Hides all superMarkers of key.
 * Removes key from observableKeys and hides the Marker
 * @param key for which superKeys are to be hidden
 * @param observableKeys list yet to be processed by showKeys()
 *****************************************************************************/
function hideSuperMarkers(key, observableKeys){
  do {
    key = superKey(key);
    if(removeVal(key, observableKeys)){     // if this super-key was observable
      if (QWIK_MARKS.has(key)){             // hide the super-Marker
        QWIK_MARKS.get(key).marker.setVisible(false);
      }
    }
  } while (key.length > 0);
}


// hide all subMarkers of key and remove each key from keys
function hideSubMarkers(key, keys){
  if(!QWIK_REGION.has(key)){ return; }
  for (const K of QWIK_REGION.get(key)){
    removeVal(K, keys);
    const MARK = QWIK_MARKS.get(K);
    if (MARK){
      MARK.marker.setVisible(false);
    }
    hideSubMarkers(K, keys);
  }
}


function removeVal(val, array){
  let i = array.indexOf(val);
  if(i > -1){
    array.splice(i,1);
    return true;
  }
  return false;
}


/******************************************************************************
*************************** JSON FUNCTIONS ************************************
******************************************************************************/


/******************************************************************************
 * Initiates a JSON call to obtain Map Markers for Venues in the locality
 * indicated by the lat-lng coordinates or region.
 * Load on server and bandwidth can be minimised by providing a string of
 * region keys (locality|admin1:country) for marks already held in QWIK_MARKS
 * and are thus avoidable.
 * A null placeholder is added to QWIK_MARKS here in fetchMarks() and removed
 * in revieveMarks(). Multiple identical calls to fetchMarks() are thus
 * averted during the time taken by the json call and response (see showMarks())
 * @param game      String game to filter venue Markers
 * @param lat       Float latitude
 * @param lng       Float longitude
 * @param region    String [[locality|]admin1|]country
 * @param regions   String
 * @return 
 *****************************************************************************/
function fetchMarks(game, center, region, regions){
  if(game === null || typeof game === 'undefined'){ return; }  
  if(region !== null && !isRegion(region)){ return; }
  if(region !== null && regions.includes(region)){ return; }
  if(region === null && center === null){ return; }
  const TOKEN = document.getElementsByName('csrfmiddlewaretoken').item(0).value;
  const PARAMS = region === null ? 
                 {game:game, lat:center.lat(), lng:center.lng(), avoidable:regions} :
                 {game:game, region:region, avoidable:regions} ;              
  const ESC = encodeURIComponent;
  const QUERY = Object.keys(PARAMS).map(k => ESC(k) + '=' + ESC(PARAMS[k])).join('&');
  const PATH = 'api/venue_marks/';
  qwikJSON(PATH, PARAMS, TOKEN, receiveMarks);
  // report to console
  const LOC = region ? region : center;
  console.log("fetching marks for "+LOC+" # "+regions);
    
  if(region !== null
    && !QWIK_REGION.has(region)){
    QWIK_REGION.set(region, new Set()); // a placeholder to prevent duplication
  }
}


/******************************************************************************
 * A callback function to process the JSON response to fetchMarks().
 * A placeholder added to QWIK_MARKS in fetchMarks() is replaced here in
 * receiveMarks().
 * @param json
 * @return null
 *****************************************************************************/
function receiveMarks(json){
  if(typeof json.status === 'undefined' || json.status === null){ return; }
  const COUNTRY = json.country ? json.country : '';
  const ADMIN1 = json.admin1 ? json.admin1+'|' : '';
  const LOCALITY = json.locality ? json.locality+'|' : '';
  const REGION = LOCALITY+ADMIN1+COUNTRY;
  switch (json.status){
    case 'OK':
      if(typeof json.game === 'undefined' || json.game === null){ return ; }
      if(typeof json.marks === 'undefined' || json.marks === null){ return; }
      const GAME = game();
      if(json.game !== GAME){ return; }
      const MAP = qwikMap;
      const NEW_MARKS = new Map(Object.entries(json.marks));
      for(let [key, mark] of NEW_MARKS){
        addToRegion(key, QWIK_REGION);
        addToRegion(key, MAP_REGION);
        if(!QWIK_MARKS.has(key)){
          addMark(key, mark);
        }
      }
      console.log("received "+NEW_MARKS.size+" marks for "+REGION);
      const VISIBLE = showMarks();
      fetchSubKeys(VISIBLE, GAME);    // prepare for possible zoom-in
      break;
    case 'NO_RESULTS':
      // TODO initiate google search for Game Name in this area
      // alert("no results: "+REGION);
      // alert(QWIK_REGION.get(REGION));
      QWIK_REGION.set(REGION, new Set());
      MAP_REGION.set(REGION, new Set());
      break;
    default:
  }
}



function addMark(key, mark){
  if (typeof key === 'undefined' || typeof mark === 'undefined'){
    console.log("Warning: addMark() called without required parameters "+key+" "+mark);
    return;
  }
  if (!mark){ return; }
  if(QWIK_MARKS.has(key)){ return; }
  if (!isNumeric(mark.lat) || !isNumeric(mark.lng)){
    console.log("Warning: Received mark without lat-lng "+key);
    return;
  }  
  QWIK_MARKS.set(key, mark);
  endowMark(key, mark);
  return mark;
}


function addToRegion(key, region=QWIK_REGION){
  const SUPER_KEY = superKey(key);
  if(!region.has(SUPER_KEY)){ 
    region.set(SUPER_KEY, new Set());
  }
  region.get(SUPER_KEY).add(key);
}


function disposeMark(key){
  const MARK = QWIK_MARKS.get(key);
  if(typeof MARK !== 'undefined'){
    MARK.marker.setMap(null);
  }
}


function superKey(key){
  if (typeof key === 'undefined'){ return ''; }
  let i = key.indexOf("|");
  return (i>0) ? key.slice(i+1) : '';
}


function isRegion(key){
  return typeof key === 'string' && key.split("|").length < 4;
}


function isVenue(key){
  return  typeof key === 'string' && key.split("|").length === 4;
}


/******************************************************************************
 * Endows a raw Mark received by JSON withn additions required to organize and 
 * show map Markers.
 * The endowments include:
 * - a LatLng center, a google.maps.Marker, and a google.,aps.InfoWindow
 * - a label for venueMarks
 * - google.maps.Bounds, degree Area and qwik icon for metaMarks
 * @param key unique id of the Mark
 * @param mark the Mark to be endowed
 * @return Mark
 *****************************************************************************/
function endowMark(key, mark){
  const MAP = qwikMap;
  mark.key = key;
  mark.center = gLatLng(mark.lat, mark.lng);

  const OPTIONS = {position:mark.center, visible:false, map:MAP};
  mark.marker = new google.maps.Marker(OPTIONS);
  
  const K = key.split('|');
  size = mark.size.toString();
  var label_origin = new google.maps.Point(13,15)
  var onclick = 'noop';
  if(K.length === 4){  // venue Mark
    mark.marker.setIcon({ url: ICON_VENUE, labelOrigin: label_origin });
    mark.marker.setLabel({text:size, className:'qg_style_mark_label venue'});
    mark.marker.setTitle(mark.name+'\n'+size+' players\nopen: '+mark.open);
    onclick = ONCLICK_VENUE_MARKER;
  } else {  // metaMark
    mark.marker.setIcon({ url: ICON_REGION, labelOrigin: label_origin });
    mark.marker.setLabel({text:size, className:'qg_style_mark_label region', fontSize: 'large'});
    mark.marker.setTitle(mark.name+'\n'+size+' venues');
    mark.bounds = markBounds(mark);
    mark.area = degArea(mark.bounds);
    onclick = ONCLICK_REGION_MARKER;
  }
  switch (onclick){
      case 'center':
        google.maps.event.addListener(mark.marker, 'click', () => {
          qwikMap.setCenter(mark.center);
        });
        break;
      case 'select':
        google.maps.event.addListener(mark.marker, 'click', () => {
          setPlace(mark.placeid, mark.name)
        });
        break;
      case 'noop':
        break;
      default: 
        console.log('error: invalid ONCLICK_REGION_MARKER: '+ONCLICK_REGION_MARKER)
  }
  return mark;
}


function addVenueMark(key, lat, lng, size, game){
  fetchMarks(game, null, key, '');
  const SUPER_KEY = superKey(key);
  if (!QWIK_REGION.has(SUPER_KEY)){
    addRegionMark(SUPER_KEY, lat, lng, game);
  }
  const NAME = key.split("|")[0];  
  const MARK = {lat:lat, lng:lng, name:NAME, size: size};
  addMark(key, MARK, game);
  return MARK;
}


function addRegionMark(key, lat, lng, game){
  const SUPER_KEY = superKey(key);
  if (!QWIK_REGION.has(SUPER_KEY)){
    addRegionMark(SUPER_KEY, lat, lng, game);
  }
  const NAME = key.split("|")[0];
  const N = Math.round(lat) + 1;
  const E = Math.round(lng) + 1;
  const S = Math.round(lat) - 1;
  const W = Math.round(lng) - 1;
  const MARK = {lat:lat, lng:lng, name:NAME, size: 1, n:N, e:E, s:S, w:W};
  addMark(key, MARK, game);
  return MARK;
}


function focusOnMark(key, game){
  const MARK = QWIK_MARKS.get(key);
  const CENTER = MARK.center;
  if(CENTER){
    MARK.marker.setVisible(true);
    const MAP = qwikMap;
    MAP.setCenter(CENTER);
    MAP.setZoom(15);
    showMarks(game);
  }
}





/******************************************************************************
********************* GEO HELPER FUNCTIONS ************************************
******************************************************************************/

const round = (number, precision) => {
  const factor = Math.pow(10, precision);
  return Math.round(number * factor) / factor;
}


function gLatLng(lat, lng, precision = 5){ // approx 1m at equator
  if(!isNumeric(lat) || !isNumeric(lng)){ return null; }
  const LAT = round(Number(lat), precision);
  const LNG = round(Number(lng), precision);
  return new google.maps.LatLng(LAT, LNG);
}


function gBounds(sw, ne){
  if (!sw || !ne){ return null; }
  return new google.maps.LatLngBounds(sw, ne);
}


function markBounds(mark){
  let ne = gLatLng(mark.n, mark.e);
  let sw = gLatLng(mark.s, mark.w);
  return gBounds(sw, ne);
}


function degDiff(degA, degB, short=true){
  let diff = Math.abs(degA-degB);
  return short ? diff : 360-diff;
}


function degArea(bounds){
  if(!bounds){ return 0; }
  let ne = bounds.getNorthEast();
  let sw = bounds.getSouthWest();
  let width = degDiff(ne.lng(), sw.lng());	
  let height = degDiff(ne.lat(), sw.lat());
  return width * height;
}

 
/**
 * Calculates the haversine distance between point A, and B.
 * @param google.maps.LatLng latlngA point A
 * @param google.maps.LatLng latlngB point B
 * https://stackoverflow.com/a/48805273/1438864
 */
const haversineDistance = (latA, lngA, latB, lngB) => {
  const toRadian = angle => (Math.PI / 180) * angle;
  const distance = (a, b) => (Math.PI / 180) * (a - b);
  const RADIUS_OF_EARTH_IN_KM = 6371;

  const dLat = distance(latB, latA);
  const dLng = distance(lngB, lngA);

  latA = toRadian(latA);
  latB = toRadian(latB);

  // Haversine Formula
  const a =
    Math.pow(Math.sin(dLat / 2), 2) +
    Math.pow(Math.sin(dLng / 2), 2) * Math.cos(latA) * Math.cos(latB);
  const c = 2 * Math.asin(Math.sqrt(a));

  let finalDistance = RADIUS_OF_EARTH_IN_KM * c;

  return finalDistance;
};


