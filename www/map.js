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
const QWIK_MARKS = new Map();
const QWIK_REGION = new Map();                  // regionKey:Set(subKeys)
const MAP_REGION = new Map();               // observable subset of QWIK_REGION
const SEARCH_MARKERS = [];
const VENUE_ICON = "https://www.qwikgame.org/img/qwik.pin.21x35.png";
const REGION_ICON = "https://www.qwikgame.org/img/qwik.cluster.24x24.png";
const PLACE_ICON = "https://www.qwikgame.org/img/qwik.place.24x24.png";
const MAP_OPTIONS = {
  zoom: 10,
  mapTypeID: 'ROADMAP',
  mapTypeControl: false,
  streetViewControl: false,
  zoomControl: true
};

var qwikMap;
var qwikInfowindow;
    

function venuesMap() {
    const MAP_ELEMENT = document.getElementById('map');
    const LAT = parseFloat(document.getElementById('lat').value);
    const LNG = parseFloat(document.getElementById('lng').value);
    const CENTER = (!isNaN(LAT) && !isNaN(LNG)) ? {lat: LAT, lng: LNG} : MSqC;
    qwikMap = new google.maps.Map(MAP_ELEMENT, MAP_OPTIONS);
    qwikInfowindow = new google.maps.InfoWindow({content: "<div></div>"});
    const MAP = qwikMap;
    const INFOWINDOW = qwikInfowindow;
    MAP.setCenter(CENTER);
    
    const GAME_SELECTS = document.querySelectorAll("select[name=game]");
    addListeners(GAME_SELECTS, 'change', changeGame);

    // setup Places search box in map
    const INPUT = document.getElementById("map-search");
    const SEARCHBOX = new google.maps.places.SearchBox(INPUT);
    MAP.controls[google.maps.ControlPosition.TOP_LEFT].push(INPUT);
    MAP.addListener("bounds_changed", () => {
        SEARCHBOX.setBounds(MAP.getBounds());
    });
    SEARCHBOX.addListener("places_changed", () => {
        searchChangeHandler(SEARCHBOX.getPlaces());
    });
    
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
 * Relocates the Map Element (contains global qwikMap) to immediately after
 * the Form Element containing the supplied element.
 *
 * element DOM Element indicating the form to locate the Map after
 * display boolean true to display the Map immediately on relocation
 * @global qwikMap google.maps.Map
 * @return null
 *
 * https://stackoverflow.com/questions/4793604/how-to-insert-an-element-after-another-element-in-javascript-without-using-a-lib
 *****************************************************************************/
function showMapBelowForm(element, display=true){
  if(element){
    try {
      const MAP = qwikMap;
      const PRE_GAME = game();
      const MAP_ELEMENT = document.getElementById("map");
      const FORM = element.closest("form");
      FORM.parentElement.insertBefore(MAP_ELEMENT, FORM.nextSibling);
      if(game() !== PRE_GAME){
        clearMarks();
      }
      MAP_ELEMENT.style.display = display ? "block" : "none";
      google.maps.event.trigger(MAP, 'resize');
      return MAP_ELEMENT;
    } catch (error) {
      console.log("Warning: failed to relocate map - missing map|form|game");
      return null;
    }
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
    const FORM = MAP_ELEMENT.previousSibling;
    const GAME = FORM.querySelector("[name=game]").value;
    return GAME;
  }
  catch (error) {
    return null;
  }
}


function mapIdleHandler(){
  const MAP = qwikMap;
  const GAME = game();
  if(typeof MAP === 'undefined' || typeof GAME === 'undefined'){ return; }
  const CENTER = MAP.getCenter();
  const LAT = Number((CENTER.lat()).toFixed(3));
  const LNG = Number((CENTER.lng()).toFixed(3));
  const AVOIDABLE = getAvoidable(LAT, LNG);
  fetchMarks(GAME, LAT, LNG, null, AVOIDABLE);
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
    
    const MARKER = new google.maps.Marker({
      map: MAP,
      icon: PLACE_ICON,
      position: place.geometry.location
    });
    
    SEARCH_MARKERS.push(MARKER);
        
    google.maps.event.addListener(MARKER, 'click', () => {
      clickSearchMarker(place);
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
    clickPOI(event.placeId, event.latLng);
    event.stop();
  }
}


function changeGame(){
  clearMarks();
  resetVenues();
}    


function resetVenues(){
    const VENUE_SELECT = document.getElementById('venue-select');
    for(const OPTION of VENUE_SELECT.querySelectorAll(':not([id])')){
        OPTION.parentNode.removeChild(OPTION);
    }
    document.getElementById('venue-prompt').selected=true;
    venuesMap();
}


function clickRegionMarker(mark){
  const MAP = qwikMap;
  const INFOWINDOW = qwikInfowindow;
  MAP.panTo(mark.marker.getPosition());
  const TEMPLATE = document.getElementById("infowindow-region");
  const FRAG = TEMPLATE.content.cloneNode(true);

  const SPAN_NAME = FRAG.getElementById("map-mark-region-name");
  SPAN_NAME.textContent = mark.name;

  const SPAN_NUM = FRAG.getElementById("map-mark-region-venues");
  SPAN_NUM.textContent = mark.num + '';       // + '' is a string conversion
  
  INFOWINDOW.setOptions({
    content: FRAG.firstElementChild,
    position: mark.center,
    pixelOffset: new google.maps.Size(0,-30)
  });
  INFOWINDOW.open(MAP);
}


function clickVenueMarker(mark){
  const INFOWINDOW = qwikInfowindow;
  const MAP = qwikMap;
  MAP.panTo(mark.marker.getPosition());
  const TEMPLATE = document.getElementById("infowindow-venue");
  const FRAG = TEMPLATE.content.cloneNode(true);

  const LINK = FRAG.getElementById("map-mark-venue-link");
  LINK.textContent = mark.name;
  LINK.href = "";
  LINK.addEventListener('click', (event) => {
    clickMapMarkVenue(event, mark.key);
  });

  const SPAN = FRAG.getElementById("map-mark-venue-players");
  SPAN.textContent = mark.num + '';       // + '' is a string conversion

  INFOWINDOW.setOptions({
    content: FRAG.firstElementChild,
    position: mark.center,
    pixelOffset: new google.maps.Size(0,-30)
  });
  INFOWINDOW.open(MAP);
}



function clickSearchMarker(place){
  const MAP = qwikMap;
  MAP.panTo(place.geometry.location);
  showInfowindowPlace(place);
}


function clickPOI(placeId, latLng){
  const MAP = qwikMap;
  MAP.panTo(latLng);
  showInfowindowPlaceId(placeId);
}


function showInfowindowPlace(place){
  const MAP = qwikMap;
  const INFOWINDOW = qwikInfowindow;
  const TEMPLATE = document.getElementById("infowindow-poi");
  const FRAG = TEMPLATE.content.cloneNode(true);

  FRAG.getElementById("poi-name").textContent = place.name;
  const LINK = FRAG.getElementById("poi-link");
  LINK.setAttribute("placeid", place.place_id);
  LINK.setAttribute("venuename", place.name);
  LINK.setAttribute("vid", vid(place.address_components, place.name));
  INFOWINDOW.setOptions({
    content: FRAG.firstElementChild,
    position: place.geometry.location,
    pixelOffset: new google.maps.Size(0,-24)
  });
  INFOWINDOW.open(MAP);
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


function showInfowindowPlaceId(placeId){
  const MAP = qwikMap;   
  const PLACE_SERVICES = new google.maps.places.PlacesService(MAP);
  const REQUEST = { placeId: placeId, fields: ['place_id', 'name', 'geometry', 'address_components']};
  PLACE_SERVICES.getDetails(REQUEST, (place, status) => {
    if (status === "OK") {
      showInfowindowPlace(place);
    } else {
      console.log(status);
    }
  });
}


function clickCreateVenue(event){
  event.preventDefault();
  let placeId = event.target.getAttribute("placeid");
  let name = event.target.getAttribute("venuename");
  let vid = event.target.getAttribute("vid");
    
  // add a new option to venueSelect and select it
  let venueSelect = document.getElementById('venue-select');
  let option = document.createElement('option');
  option.value = vid;
  option.text = name;
  venueSelect.add(option);
  venueSelect.value = vid;
  document.getElementById('placeid').value = placeId;

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
  fetchMarks(GAME, null, null, superKey(KEY), '');
  showMapBelowForm(ELEMENT);
  addVenueMark(
    KEY,
    ELEMENT.dataset.lat,
    ELEMENT.dataset.lng,
    ELEMENT.dataset.num,
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
      fetchMarks(game, null, null, KEY, superKey(KEY));
    }
  }
}


/******************************************************************************
 * A String of regions (locality|admin1|country) for which Marks have already
 * been obtained - and that include the lat-lng coords. 
 * @param marks Object 
 * @param suffix String 
 * @return String of regions (locality|admin1|country)
 *****************************************************************************/
function getAvoidable(lat, lng){
  if(!lat || !lng){ return ''; }
  const AVOID = new Set();
  const LATLNG = gLatLng(lat, lng);
  for(const [KEY, SUB_KEYS] of QWIK_REGION){
    if(SUB_KEYS.size > 0){
      const MARK = QWIK_MARKS.get(KEY);
      if (MARK
      && MARK.bounds
      && MARK.bounds.contains(LATLNG)){
        AVOID.add(KEY);
      }
    }
  }
  return Array.from(AVOID).join(":");
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
    if(REGION_AREA > MAP_AREA){             // should the subMarkers be shown? 
      MARK.marker.setVisible(false);
      hideSuperMarkers(KEY, KEYS);
      visibleMarks.concat(showSubMarkers(SUB_KEYS, GAME, MAP_BOUNDS));
    } else if(SUB_KEYS.size === 1){    // is there only 1 subMarker?
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
        fetchMarks(game, null, null, KEY, superKey(KEY));
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
 * @param avoidable String
 * @return 
 *****************************************************************************/
function fetchMarks(game, lat, lng, region, avoidable){
  if(game === null || typeof game === 'undefined'){ return; }  
  if(region !== null && !isRegion(region)){ return; }
  if(region !== null && avoidable.includes(region)){ return; }
  lng = lng>180 ? (lng+180)%360 - 180: (lng<-180 ? (lng-180)%-360 + 180 : lng);
  const PARAMS = region === null ? 
                 {game:game, lat:lat, lng:lng, avoidable:avoidable} :
                 {game:game, region:region, avoidable:avoidable} ;              
  const ESC = encodeURIComponent;
  const QUERY = Object.keys(PARAMS).map(k => ESC(k) + '=' + ESC(PARAMS[k])).join('&');
  const PATH = 'json/venue.marks.php?'+QUERY;
  qwikJSON(PATH, receiveMarks);
  // report to console
  const LOC = region ? region : "lat:"+lat.toFixed(2)+" lng:"+lng.toFixed(2);
  console.log("fetching marks for "+LOC+" # "+avoidable);
    
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
  const COUNTRY = json.country !== null ? json.country : '';
  const ADMIN1 = json.admin1 !== null ? json.admin1+'|' : '';
  const LOCALITY = json.locality !== null ? json.locality+'|' : '';
  switch (json.status){
    case 'OK':
      if(typeof json.game === 'undefined' || json.game === null){ return ; }
      if(typeof json.marks === 'undefined' || json.marks === null){ return; }
      const GAME = game();
      if(json.game !== GAME){ return; }
      const MAP = qwikMap;
      const NEW_MARKS = new Map(Object.entries(json.marks));
      for(let [key, mark] of NEW_MARKS){
        if(!QWIK_MARKS.has(key)){
          addMark(key, mark, GAME);
        }
      }
      console.log("received "+NEW_MARKS.size+" marks for "+LOCALITY+ADMIN1+COUNTRY);
      const VISIBLE = showMarks();
      fetchSubKeys(VISIBLE, GAME);              // prepare for possible zoom-in
      break;
    default:
  }
}



function addMark(key, mark, game){
  const MAP = qwikMap;
  if (typeof key === 'undefined' || typeof mark === 'undefined'){
    console.log("Warning: addMark() called without required parameters "+key+" "+mark);
    return;
  }
  if (!isNumeric(mark.lat) || !isNumeric(mark.lng)){
    console.log("Warning: Received mark without lat-lng "+key);
    return;
  }  
  if(QWIK_MARKS.has(key)){ return; }
  
  QWIK_MARKS.set(key, mark);
  endowMark(key, mark);
  addToRegion(key, QWIK_REGION);
  addToRegion(key, MAP_REGION);
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
  mark.name = K[0];
  if(K.length === 4){  // venue Mark
    mark.marker.setIcon(VENUE_ICON);
    google.maps.event.addListener(mark.marker, 'click', () => {
      clickVenueMarker(mark);
    });      
  } else {  // metaMark
    mark.marker.setIcon(REGION_ICON);
    mark.bounds = markBounds(mark);
    mark.area = degArea(mark.bounds);
    google.maps.event.addListener(mark.marker, 'click', () => {
      clickRegionMarker(mark);
    }); 
  }
  return mark;
}


function addVenueMark(key, lat, lng, num, game){
  fetchMarks(game, null, null, key, '');
  const SUPER_KEY = superKey(key);
  if (!QWIK_REGION.has(SUPER_KEY)){
    addRegionMark(SUPER_KEY, lat, lng, game);
  }
  const NAME = key.split("|")[0];  
  const MARK = {lat:lat, lng:lng, name:NAME, num: num};
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
  const MARK = {lat:lat, lng:lng, name:NAME, num: 1, n:N, e:E, s:S, w:W};
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


function gLatLng(lat, lng){
  if(!isNumeric(lat) || !isNumeric(lng)){ return null; }
  return new google.maps.LatLng(Number(lat), Number(lng));
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


