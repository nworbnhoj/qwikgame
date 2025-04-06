const MSqC = {lat: -36.4497, lng: 146.4300};
const Sofia = {lat: 42.6977, lng: 23.3217};

///////////////// Service Worker functions ///////////////////

// Registering Service Worker
if('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').then(registration => {
          console.log("serviceWorker registered.");
        }, function(err) {
          console.log("WARNING: serviceWorker registration failed.");
        });
    });
};


///////////////// App Install functions ///////////////////

let installPrompt = null;
const INSTALL_BUTTON = document.getElementById("install_app");
if (INSTALL_BUTTON){
  window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault();
    installPrompt = event;
  });

  INSTALL_BUTTON.addEventListener("click", async () => {
    if (installPrompt) {
      const RESULT = await installPrompt.prompt();
      console.log(`Install prompt was: ${RESULT.outcome}`);
      disableInAppInstallPrompt();
    } else if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        const IOS = document.getElementById("ios_install_app");
        if (IOS){
          INSTALL_BUTTON.classList.add('hidden')
          IOS.classList.remove('hidden')
        }
    } else if (/Firefox/i.test(navigator.userAgent)) {
        const FIREFOX = document.getElementById("firefox_install_app");
        if (FIREFOX){
          INSTALL_BUTTON.classList.add('hidden')
          FIREFOX.classList.remove('hidden')
        }
    } else {
        const UNSUPPORTED = document.getElementById("unsupported_install_app");
        if (UNSUPPORTED){
          INSTALL_BUTTON.classList.add('hidden')
          UNSUPPORTED.classList.remove('hidden')
        }      
    }
  });

  window.addEventListener("appinstalled", () => {
    disableInAppInstallPrompt();
  });

  function disableInAppInstallPrompt() {
    installPrompt = null;
    INSTALL_BUTTON.classList.add("hidden");
  }
}


///////////////// DOM Ready functions ///////////////////

// https://stackoverflow.com/questions/6348494/addeventlistener-vs-onclick
function docReady(callbackFunction){
  if(document.readyState != 'loading')
    callbackFunction(event)
  else
    document.addEventListener("DOMContentLoaded", callbackFunction)
}

  
function winReady(callbackFunction){
  if(document.readyState == 'complete')
    callbackFunction(event);
  else
    window.addEventListener("load", callbackFunction);
}


// A general DOM document ready function
docReady(event => {
    refreshData();
    dropInit();
});


// A general DOM Window ready function
winReady(event => {});


// adds an event Listener with callback on a NodeList or HTMLCollection 
function addListeners(elements, event, callback){
    const array = [...elements];
    array.forEach(function (element, index) {
        element.addEventListener(event, callback, false);
    });
}


//////////////// EVENT ACTIONS ////////////////////////////

function refreshData(){
  refreshRecords();
  refreshOptions();
  refreshDatalists();
}


function refreshRecords(){
  for (var base of document.querySelectorAll('div.base.json')) {
    if(!activeUndo(base)){
      replicateBase(base);
    }
  }
}


function refreshOptions(){
  for (var select of document.querySelectorAll('select.json')) {
    fillOptions(select);
  }
  for (var optgroup of document.querySelectorAll('optgroup.json')) {
    fillOptions(optgroup);
  }
}


function refreshDatalists(){
  for (var datalist of document.querySelectorAll('datalist')) {
    fillDatalist(datalist);
  }
}


///////////////// DOM helper functions ///////////////////


// returns the next sibling element matching selector, or null otherwise
function nextSibling(element, selector) {
  if (!element || !selector) return null;
  var sibling = element.nextElementSibling;
  if (!sibling || sibling.matches(selector)) return sibling;
  return nextSibling(sibling, selector);
};


// toggle the element visibility
function toggle(element){
    if(window.getComputedStyle(element).display !== 'none') {
        element.style.display = 'none';
        return;
    }
    element.style.display = 'block';
}



///////////////// JSON functions ///////////////////


function initJSON(parentElement){
    addListeners(parentElement.querySelectorAll('button.help'), 'click', clickButtonHelp);

    // call moreInitJSON() if it has been defined somewhere
    if (typeof moreInitJSON == 'function') { moreInitJSON(parentElement); }
}


function setInnerJSON(json, element){
    element.innerHTML = json;
    initJSON(element);
}


function replaceRecords(json, base){
    if(window.activeUndo(base)){ return; }
    
    var parentNode = base.parentNode;
    if(!parentNode.contains(document.activeElement)){
      while (parentNode.childNodes.length) {
        parentNode.removeChild(parentNode.firstChild);  // removes Listeners
      }
      parentNode.innerHTML = json.trim();
      parentNode.insertBefore(base, parentNode.firstChild);
      initJSON(parentNode);
    }
}


function replicateBase(base){
    var id = base.getAttribute('id');
    if (!id){
        console.log("Failed to replicate base: missing id attribute.");
        return false;
    }
    var baseHtml = base.outerHTML;
    var params = {html:baseHtml};
    var esc = encodeURIComponent;
    var path = 'api/listing/'+id+'/';
    const TOKEN = document.getElementsByName('csrfmiddlewaretoken').item(0).value;
    qwikJSON(path, params, TOKEN, replaceRecords, base);
}


function fillOptions(element){
    var id = element.getAttribute('id');
    if (!id){
        console.log("Failed to fill options: missing id attribute.");
        return false;
    }
    const FORM = element.closest("form");
    const GAME_ELEMENT = FORM.querySelector("[name=game]");
    let PARAM = (GAME_ELEMENT !== null) ? {'game': GAME_ELEMENT.value} : {} ;    
    var path = 'api/options/'+id+'/';
    const TOKEN = document.getElementsByName('csrfmiddlewaretoken').item(0).value;
    qwikJSON(path, PARAM, TOKEN, setInnerJSON, element);
}


function fillDatalist(datalist){
    var id = datalist.getAttribute('id');
    if (!id){
        console.log("Failed to fill datalist: missing id attribute.");
        return false;
    }
    var input = document.querySelector("[list='"+id+"']");
    if (!input){
        console.log("Failed to fill datalist: unable to find input with list=".id);
        return false;
    }
    var selectGame = input.form.querySelector(".select-game");
    if (selectGame){
        var game = selectGame.value;
        PARAM = {"game" : game};
    } else {
        console.log("Failed to fill datalist: unable to find .select-game");        
        PARAM = {};
    }
    var path = "api/options/"+id+"/";
    const TOKEN = document.getElementsByName('csrfmiddlewaretoken').item(0).value;
    qwikJSON(path, PARAM, TOKEN, setInnerJSON, datalist);
}


function qwikJSON(path, params, csrftoken, callback, element){
    var protocol = window.location.protocol;
    var host = window.location.host;
    var url = protocol + "//" + host + "/" + path;
    args = Array.prototype.slice.call(arguments);
    args.splice(1, 1);
    let json = JSON.stringify(params)
    var xhr = new XMLHttpRequest();
    xhr.callback = callback;
    xhr.arguments = args;
    xhr.onload = xhrSuccess;
    xhr.onerror = xhrError;
    xhr.open("POST", url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-CSRFToken', csrftoken);
    xhr.send(JSON.stringify(params));
}


function xhrSuccess() {
  if (this.status == 200) {
    var args = this.arguments;
    var url = args[0].split("?")[0];
    var json = JSON.parse(this.responseText);
    args[0] = !json ? '' : json ;  // replace url with json in args
    this.callback.apply(this, args);
    var length = nFormatter(this.responseText.length, 1);
    console.log("json reply from "+url+" ("+length+")");
  } else {
    console.log("json request failed with status: " + this.status)
  }
}


function xhrError() {
    console.error("failed to get JSON: " . this.statusText);
}


///////////////// Generic helper functions ///////////////////


// https://stackoverflow.com/questions/1912501/unescape-html-entities-in-javascript/34064434#34064434
function htmlDecode(input) {
  var doc = new DOMParser().parseFromString(input, "text/html");
  return doc.documentElement.textContent;
}


// https://stackoverflow.com/questions/18082/validate-decimal-numbers-in-javascript-isnumeric
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}


// https://stackoverflow.com/questions/13/determine-a-users-timezone/5492192#5492192
function TimezoneDetect(){
    var dtDate = new Date('1/1/' + (new Date()).getUTCFullYear());
    var intOffset = 10000; //set initial offset high so it is adjusted on the first attempt
    var intMonth;
    var intHoursUtc;
    var intHours;
    var intDaysMultiplyBy;

    //go through each month to find the lowest offset to account for DST
    for (intMonth=0;intMonth < 12;intMonth++){
        //go to the next month
        dtDate.setUTCMonth(dtDate.getUTCMonth() + 1);

        //To ignore daylight saving time look for the lowest offset.
        //Since, during DST, the clock moves forward, it'll be a bigger number.
        if (intOffset > (dtDate.getTimezoneOffset() * (-1))){
            intOffset = (dtDate.getTimezoneOffset() * (-1));
        }
    }

    return intOffset;
}


// https://stackoverflow.com/questions/26361649/how-to-handle-right-to-left-text-input-fields-the-right-way?noredirect=1&lq=1
function rtl(element){   
    if(element.setSelectionRange){
        element.setSelectionRange(0,0);
    }
}


// https://stackoverflow.com/questions/9461621/format-a-number-as-2-5k-if-a-thousand-or-more-otherwise-900#9462382
function nFormatter(num, digits) {
  var si = [
    { value: 1, symbol: "" },
    { value: 1E3, symbol: "k" },
    { value: 1E6, symbol: "M" },
    { value: 1E9, symbol: "G" },
    { value: 1E12, symbol: "T" },
    { value: 1E15, symbol: "P" },
    { value: 1E18, symbol: "E" }
  ];
  var rx = /\.0+$|(\.[0-9]*[1-9])0+$/;
  var i;
  for (i = si.length - 1; i > 0; i--) {
    if (num >= si[i].value) {
      break;
    }
  }
  return (num / si[i].value).toFixed(digits).replace(rx, "$1") + si[i].symbol;
}



////////////////  ////////////////////////////



function closeStuff(event) {
  let target = event.target;
  if (!(target.classList.contains('info') || target.parentElement.classList.contains('info'))){
    document.querySelectorAll('.info_text').forEach(function(info) {
        info.classList.add('hidden');
    })
  }
  document.querySelectorAll('.down').forEach(function(dropdown) {
    if (!dropdown.parentElement.contains(event.target)) {
      dropdown.classList.add('hidden');
    }
  });
}

function ctaKeen(event) {
  event.currentTarget.style.display = "none";
  let fwd_event = new MouseEvent(event.type, event);
  document.getElementById('cta_keen').dispatchEvent(fwd_event);
}

function drop(event) {
  let drop = event.currentTarget;
  let drop_down = drop.closest('.drop_down')
  if (!drop_down.classList.contains('disabled')) {
    let down = drop.nextElementSibling;
    if (drop.dataset.down == "false") {
      drop.dataset.down = "true";
      down.classList.remove('hidden');
    } else {
      drop.dataset.down = "false";
      down.classList.add('hidden');
    }
  }
}

// update a drop_down display to be consistent with the selected options
function dropDownUpdate(drop_down){
  let drop_up = drop_down.querySelector('.drop_up');
  if (drop_up) {
    let drop_up_txt = drop_up.dataset.action;
    drop_down.querySelectorAll('input:checked').forEach(function(checked){
        drop_up_txt += checked.parentElement.innerText + " ";
    });
    drop_up_txt = drop_up_txt.replaceAll("\n", "");
    drop_up_txt = drop_up_txt.trim();
    drop_up.innerText = drop_up_txt;
  } else {
    console.log("failed to get drop_up for drop_down");
  }
}

function downClick(event){
  let option = event.currentTarget;
  dropDownUpdate(option.closest('.drop_down'));
  option.closest('.down').classList.add('hidden');
}

function dropInit() {
  const DROP_DOWNS = document.querySelectorAll('.drop_down');
  DROP_DOWNS.forEach(function(dd){
    dd.querySelectorAll("input[type='radio']").forEach(function(radio){
      radio.addEventListener('input', deselectDropRadio);
    });
  });
}

function deselectDropRadio(event){
  const RADIO = event.target;
  const DROP_DOWN = RADIO.closest('.drop_down');
  const RADIOS = DROP_DOWN.querySelectorAll("input[type='radio']");
  RADIOS.forEach(function(radio){
    if (!radio.checked){
      radio.dispatchEvent(new Event("deselect"));
    }
  });
}

function enableElement(element, enable) {
  if (enable) {
    element.classList.remove('disabled');
    element.querySelectorAll('input').forEach(function(input) {
      input.disabled = false;
    });
    element.querySelectorAll('.disabled').forEach(function(disabled) {
      disabled.classList.remove('disabled');
    });
  } else {
    element.classList.add('disabled');
    element.querySelectorAll('input').forEach(function(input) {
      input.disabled = true;
    });
    element.querySelectorAll('h6, div').forEach(function(enabled) {
      enabled.classList.add('disabled');
    });
  }
}

function enableInviteFriend(event) {
  let element = event.currentTarget;
  enableElement(document.getElementById("invite_friend_body"), element.checked);
  enableElement(document.getElementById("rival_skill_body"), !element.checked);
}

function close(event) {
  event.currentTarget.closest('.closable').classList.toggle('hidden');
}

function handlePlaceChange(place){
  hours = [];
  if (place.dataset.hasOwnProperty('hours')){
    hours = place.dataset.hours.split(',')
    hours = hours.flatMap(x => [parseInt(x)]);
  }
  now_weekday = undefined
  if (place.dataset.hasOwnProperty('now_weekday')){
    now_weekday = parseInt(place.dataset.now_weekday);
    now_weekday = Number.isInteger(now_weekday) ? now_weekday % 7 : undefined;
  }
  now_hour = undefined
  if (place.dataset.hasOwnProperty('now_hour')){
    now_hour = parseInt(place.dataset.now_hour);
    now_hour = Number.isInteger(now_hour) ? now_hour % 24 : undefined;
  }
  setDayFields(hours, now_weekday, now_hour);
}

function closeStuff(event) {
  let target = event.target;
  if (!(target.classList.contains('info') || target.parentElement.classList.contains('info'))){
    document.querySelectorAll('.info_text').forEach(function(info) {
        info.classList.add('hidden');
    })
  }
  document.querySelectorAll('.down').forEach(function(dropdown) {
    if (!dropdown.parentElement.contains(event.target)) {
      dropdown.classList.add('hidden');
    }
  });
}

function check(event) {
  let element = event.currentTarget;
  element.querySelector("input[type='checkbox']").checked=true;
}

function nextDetail(event) {
  let current_id = event.currentTarget.closest('.detail_n').id;
  let detail_id = Array.from(document.querySelectorAll('.detail_n'));
  let i = detail_id.findIndex(function(detail) { return detail.id == current_id;});
  let next_id = detail_id[(i + 1 < detail_id.length) ? i + 1 : 0].id;
  document.querySelectorAll('input[name=list]').forEach(function(input){
    input.checked = (input.dataset && (input.dataset.id == next_id));
  });
  showDetail();
}

function openTab(event) {
  let tab = event.currentTarget;
  let tab_area = tab.closest('.tab_area');
  let tabs = tab_area.querySelectorAll(".tab");
  let areas = tab_area.querySelectorAll(".area");
  for (i = 0; i < tabs.length; i++) {
    if (tabs[i] === tab) {
      tabs[i].classList.add('active');
      areas[i].classList.remove('hidden');
    } else {
      tabs[i].classList.remove('active');
      areas[i].classList.add('hidden');
    }
  }
}

function previousDetail(event) {
  let current_id = event.currentTarget.closest('.detail_n').id;
  let detail_id = Array.from(document.querySelectorAll('.detail_n'));
  let i = detail_id.findIndex(function(detail) { return detail.id == current_id;});
  let next_id = detail_id[(i - 1 < 0) ? detail_id.length - 1 : i - 1].id;
  document.querySelectorAll('input[name=list]').forEach(function(input){
    input.checked = (input.dataset && (input.dataset.id == next_id));
  });
  showDetail();
}

function range(slider) {
  var options = slider.closest('div.field').querySelector('div.range_options')
  for (let i = 0; i < options.length; i++) {
    var option = options.item(i);
    if (i == slider.value) {
      option.classList.remove('invisible');
    } else {
      option.classList.add('invisible');
    }
  }
}

function setAllWeek(button, checked) {
  if (button.firstElementChild.checked != checked) {
    button.dispatchEvent(new MouseEvent("click", {
      "view": window,
      "bubbles": true,
      "cancelable": false
    }));
  }
}

// shows only the Venue open-hours in DayFields
// hours24x7 is an array[int:7] representing 7 days of open hours in the
//     24 least significant bits (--------012345....23)
// today is the current weekday at the Venue [0..6]
function setDayFields(hours24x7, now_weekday, now_hour){
  document.querySelectorAll(".by_hour").forEach(function(day){
    var open_day = false;
    var open_hour = false;
    var weekday = now_weekday;
    var offset = undefined;
    if ('weekday' in day.dataset){
      var wd = parseInt(day.dataset.weekday);
      weekday = Number.isInteger(wd) ? wd : undefined;
    } else if (Number.isInteger(now_weekday) && 'offsetday' in day.dataset){
      offset = parseInt(day.dataset.offsetday);
      weekday = Number.isInteger(offset) ? now_weekday + offset : now_weekday; 
    }
    const WEEKDAY = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    if (Number.isInteger(weekday) && Number.isInteger(offset)){
      weekday = weekday % 7
      // set the week day in the DayField Field Label
      sub_text = day.closest('div.field').querySelector('.sub_text')
      if (sub_text){
        sub_text.innerText = WEEKDAY[now_weekday + offset];
      }
    }
    day.querySelectorAll(".hour_grid input").forEach(function(input) {
      hr = parseInt(input.parentElement.innerText);
      if (Number.isInteger(weekday)){
        if (weekday in hours24x7){
          hours = hours24x7[weekday]
          // hide hour buttons when venue is closed
          if (hours >> (23 - hr) & 1){
            input.classList.remove('hidden');
            open_day = true;
          } else {
            input.classList.add('hidden');
          }
        }
      }
      if (Number.isInteger(offset)){
        // disable hour buttons when time is passed
        input.disabled = (offset == 0) && (hr <= now_hour);
        unavailable = input.disabled || input.classList.contains('hidden');
        open_hour = open_hour || !unavailable;
      }
    });
    by_day = day.closest('.by_day')
    if (open_day && open_hour){
      by_day.querySelector('.on_day').classList.remove('hidden')
      by_day.querySelector('.no_day').classList.add('hidden')
    } else {
      by_day.querySelector('.on_day').classList.add('hidden')
      by_day.querySelector('.no_day').classList.remove('hidden')
    }
  });
}


function showDetail() {
  // on mobile, hide the list and show the detail
  var width_600 = window.matchMedia("only screen and (max-width: 600px)").matches;
  var width_768 = window.matchMedia("only screen and (max-width: 768px)").matches;
  // var width_992 = window.matchMedia("only screen and (max-width: 992px)").matches;
  // var width_1200 = window.matchMedia("only screen and (max-width: 1200px)").matches;
  var list = document.getElementById("list");
  var detail = document.getElementById("detail");
  if (width_600 || width_768) { // mobile
    list.style.display = "none";
    detail.style.display = "flex";
  } else { // desktop
    list.style.display = "flex";
    detail.style.display = "flex";
  }

  // show the selected detail, and hide all the others
  list.querySelectorAll('[name=list]').forEach(function(radio) {
    if (radio.checked) {
      document.getElementById(radio.dataset.id).classList.remove("hidden");
    } else {
      document.getElementById(radio.dataset.id).classList.add("hidden");
    }
  })
}

function showBlockAdd(event) {
  document.getElementById('block_list').classList.add('hidden');
  document.getElementById('block_add').classList.remove('hidden');
}

function showBlockList(event) {
  document.getElementById('block_add').classList.add('hidden');
  document.getElementById('block_list').classList.remove('hidden');
}

function showFriendAdd(event) {
  document.getElementById('friend_invite').classList.add('hidden');
  document.getElementById('friend_add').classList.remove('hidden');
}

function showFriendInvite(event) {
  document.getElementById('friend_add').classList.add('hidden');
  document.getElementById('friend_invite').classList.remove('hidden');
}

function showGroup(event) {
  let button = event.currentTarget;
  button.nextElementSibling.classList.toggle('hidden');
  button.lastElementChild.classList.toggle('flip');
}

function showLoader(input) {
  label = input.closest('label')
  label.classList.add("loader");
  setTimeout(() => label.classList.remove("loader"), 20000);
}

function showNextParentSibling(event) {
  let button = event.currentTarget;
  let next_sibling = button.parentNode.nextElementSibling;
  next_sibling.classList.toggle('hidden');
}

function showNextSibling(event) {
  let button = event.currentTarget;
  let next_sibling = button.nextElementSibling;
  next_sibling.classList.toggle('hidden');
}

function showInfo(event) {
  event.currentTarget.parentElement.querySelector('div.info_text').classList.toggle('hidden');
}

function showAvailableEdit(event) {
  document.querySelector('.available_edit').classList.remove('hidden');
  document.querySelector('.available_view').classList.add('hidden');
}

function slide(slider) {
  var words = slider.previousElementSibling.children;
  for (let i = 0; i < words.length; i++) {
    var word = words.item(i);
    if (i == slider.value) {
      word.classList.remove('invisible');
    } else {
      word.classList.add('invisible');
    }
  }
}

// these toggle function are specific to the pattern
//  <lable class='toggle'>     
//    <input type="checkbox">
//    <div class="button">Click me</div>
//  </label>

// toggle the check in a label.toggle
function toggle (toggle) {
  let input = toggle.firstElementChild;
  if (toggle_checked(toggle)) {
    toggle_uncheck(toggle);
  } else {
    toggle_check(toggle);
  }
}

// validate the html structure of a label.toggle
function toggle_valid(toggle) {
  try {
    if (toggle.nodeType == "label" &&
      toggle.classList.contains("toggle") &&
      toggle.classList.firstElementChild.nodeType == "input") {
      alert("true");
      return true;
    }
  } finally {
    alert("invalid toggle: " + toggle);
    console.log("invalid toggle: " + toggle);
  }
  try {
    console.log("invalid toggle" + toggle.outerHTML);
  } finally {}
  return false;
}

// return true if the label.toggle is checked
function toggle_checked(toggle) {
  return toggle.firstElementChild.checked;
}

// check the label.toggle
function toggle_check(toggle) {
  toggle.firstElementChild.checked = "checked";
}

// uncheck the label.toggle
function toggle_uncheck(toggle) {
  toggle.firstElementChild.checked = null;
}


// return true if the label.toggle is disabled
function toggle_disabled(toggle) {
  return toggle.firstElementChild.disabled;
}

// additional logic for all_day and all_week toggles when an hour is toggled
function toggleHour(event) {
  try {
    let hour = event.currentTarget;
    let by_day = hour.closest(".by_day")
    if (by_day){
      all_day = by_day.querySelector("label.toggle.all_day");
      if (all_day){
        if (toggle_checked(hour)) {
          updateAllDay(all_day);
        } else {
          toggle_uncheck(all_day);
          let all_week = all_day.closest('div.field').querySelector("label.toggle.all_week");
          if (all_week){
            toggle_uncheck(all_week);
          }
        }
      }
    }
  } catch (e) {
    console.log(e);
  }
}

// additional logic for hour and all_week toggles when an all_day is toggled
function toggleAllDay(event) {
  try {
    let all_day = event.currentTarget;
    let all_day_checked = toggle_checked(all_day);
    var hours = all_day.closest('div.by_day').querySelector('div.radio_block').children;
    for (hour of hours) {
      var checkbox = hour.firstElementChild;
      if (!checkbox.disabled) {
        checkbox.checked = all_day_checked;
      }
    }
    let all_week = all_day.closest('div.field').querySelector("label.toggle.all_week");
    if (all_day_checked) {
      updateAllWeek(all_week);
    } else {
      if (all_week){
        toggle_uncheck(all_week);
      }
    }
  } catch (e) {
    console.log(e);
  }
}

// additional logic for hour and all_day toggles when an all_week is toggled
function toggleAllWeek(event) {
  try {
    let button = event.currentTarget;
    var checked = button.firstElementChild.checked;
    let detail = button.closest('div.field');
    detail.querySelectorAll('.all_day').forEach(function(button) {
      button.firstElementChild.checked = checked;
    })
    detail.querySelectorAll('div.hour_grid').forEach(function(radio_block) {
      for (hour of radio_block.children) {
        hour.firstElementChild.checked = checked;
      }
    })
  } catch (e) {
    console.log(e);
  }
}

function unreadRemove(event) {
  event.currentTarget.classList.remove('unread');
}

// update ALL all_day (and all_week) toggle to be consistent with the hour toggles 
function updateAllHour() {
    for (all_day of document.querySelectorAll("label.toggle.all_day")) {
        updateAllDay(all_day)
    }
}

// update an all_day (and all_week) toggle to be consistent with the hour toggles 
function updateAllDay(all_day) {
  try {
    // check all_day if every hour is checked
    let hrs = all_day.closest('.by_day').querySelector('div.radio_block').children;
    for (hr of hrs) {
      if (!toggle_disabled(hr) && !toggle_checked(hr)) {
        return;
      }
    }
    toggle_check(all_day);
    let all_week = all_day.closest('div.field').querySelector("label.toggle.all_week");
    updateAllWeek(all_week);
  } catch (e) {
    console.log(e);
  }
}

// update an all_week toggle to be consistent with the all_day toggles
function updateAllWeek(all_week) {
  if (!all_week) {
    return;
  }
  try {
    // check all_week if every all_day is checked
    let days = all_week.closest('div.field').querySelectorAll("label.toggle.all_day");
    for (day of days) {
      if (!toggle_disabled(day) && !toggle_checked(day)) {
        return;
      }
    }
    toggle_check(all_week);
  } catch (e) {
    console.log(e);
  }
}


function togglePreviousSibling(event) {
  let toggle = event.currentTarget;
  toggle.querySelectorAll('.tog').forEach(function(tog) {
    tog.classList.toggle('hidden');
  });
  let previous_sibling = toggle.previousElementSibling;
  previous_sibling.classList.toggle('hidden');
}


// https://stackoverflow.com/questions/43043113/how-to-force-reloading-a-page-when-using-browser-back-button
// Handle page load from cache after Browser Forward / Back button
window.onpageshow = function(event) {
    if (event.persisted) {
      document.querySelectorAll(".loader").forEach(function(loader) {
        loader.classList.remove("loader");    // to reset loader
        window.location.reload();  // to reshow .btn.special1.mobile
      })
    }
};


window.onload = function() {
  updateAllHour();
  document.querySelectorAll('time').forEach($e => {
    const date = new Date($e.dateTime);
    $e.innerHTML = date.toLocaleTimeString([], { weekday: "short", hour: "2-digit", minute: "2-digit",  hour12: false });
  });
  document.addEventListener('click', closeStuff);
  document.querySelectorAll('div.drop').forEach(function(button) {
    button.onclick = drop;
  });
  document.querySelectorAll('div.drop_down').forEach(function(drop_down){
    dropDownUpdate(drop_down);
  });
  document.querySelectorAll('div.down label').forEach(function(div){
    div.onclick = downClick;
  });
  document.querySelectorAll('.cta_mobile').forEach(function(proxy) {
    proxy.onclick = ctaKeen;
  });
  document.querySelectorAll('div.head_fwd').forEach(function(button) {
    button.onclick = nextDetail;
  });
  document.querySelectorAll('div.head_back').forEach(function(button) {
    button.onclick = previousDetail;
  });
  document.querySelectorAll('div.info').forEach(function(button) {
    button.onclick = showInfo;
  });
  document.querySelectorAll('.show_group').forEach(function(button) {
    button.onclick = showGroup;
  });
  document.querySelectorAll('div.show-next-parent-sibling').forEach(function(button) {
    button.onclick = showNextParentSibling;
  });
  document.querySelectorAll('div.show-next-sibling').forEach(function(button) {
    button.onclick = showNextParentSibling;
  });
  document.querySelectorAll('div.closer').forEach(function(div) {
    div.onclick = close;
  });
  document.querySelectorAll('div.checkbox_wrap').forEach(function(div) {
    div.onclick = check;
  });
  document.querySelectorAll('input.enable-invite-friend').forEach(function(checkbox) {
    checkbox.oninput = enableInviteFriend;
  });
  document.querySelectorAll('label.toggle span.stop-prop').forEach(function(hr) {
    hr.onclick = function(event) {
      event.stopPropagation()
    };
  });
  document.querySelectorAll('div.unread').forEach(function(button) {
    button.onclick = unreadRemove;
  });
  document.querySelectorAll('label.toggle.hour').forEach(function(hour) {
    hour.onclick = toggleHour;
  });
  document.querySelectorAll('label.toggle.all_day').forEach(function(all_day) {
    all_day.onclick = toggleAllDay;
  });
  document.querySelectorAll('label.toggle.all_week').forEach(function(all_week) {
    all_week.onclick = toggleAllWeek;
  });
  document.querySelectorAll('[name=list]').forEach(function(list_radio) {
    list_radio.onclick = showDetail;
  });
  document.querySelectorAll('.show_block_add').forEach(function(element) {
    element.onclick = showBlockAdd;
  });
  document.querySelectorAll('.show_block_list').forEach(function(element) {
    element.onclick = showBlockList;
  });
  document.querySelectorAll('.show_friend_add').forEach(function(element) {
    element.onclick = showFriendAdd;
  });
  document.querySelectorAll('.show_friend_invite').forEach(function(element) {
    element.onclick = showFriendInvite;
  });
  document.querySelectorAll('.show_next_parent_sibling').forEach(function(element) {
    element.onclick = showNextParentSibling;
  });
  document.querySelectorAll('.edit_available').forEach(function(div) {
    div.onclick = showAvailableEdit;
  });
  document.querySelectorAll('div.tab').forEach(function(div) {
    div.onclick = openTab;
  });
  document.querySelectorAll("a.btn").forEach(function(element) {
    element.addEventListener("click", () => {
      element.classList.add("loader");
    });
  });
  document.querySelectorAll("input[type='submit']").forEach(function(input) {
    input.addEventListener("click", () => {
      showLoader(input);
    });
  });
  document.querySelectorAll('.toggle_previous_sibling').forEach(function(toggle) {
    toggle.onclick = togglePreviousSibling;
  });
  document.querySelectorAll("form:has( .by_day)").forEach(function(form){
    const PLACE_SELECT = document.getElementById('id_place');
    if (PLACE_SELECT){
        PLACE_SELECT.addEventListener("change", function(e) {
          place = this.options[this.selectedIndex]
          handlePlaceChange(place);
        });
    }
  });
}
