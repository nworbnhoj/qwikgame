const MSqC = {lat: -36.4497, lng: 146.4300};
const Sofia = {lat: 42.6977, lng: 23.3217};


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


// adds an event Listener with callback on a NodeList or HTMLCollection 
function addListeners(elements, event, callback){
    const array = [...elements];
    array.forEach(function (element, index) {
        element.addEventListener(event, callback, false);
    });
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



///////////////// Form Animation functions ///////////////////


  // open one field in the form and close the others
  function field_focus(field){
    if (field instanceof HTMLElement) {
      shut = !field.classList.contains('open');
      form_shut(field);   
      if (shut) {
        field_open(field);
      }
    } else {
      console.log('WARN: invalid parameter');
    }
  }

  function field_focus_first(){
    const FORM = document.querySelector('div.detail form');
    if (FORM){
      const ERROR = FORM.querySelector('span.error');
      if (ERROR) {
        field_focus(ERROR.closest('div.field'));
        return;        
      }
      for (const FIELD of FORM.querySelectorAll('div.required')) {
        const FIELDSET = FIELD.querySelector('fieldset')
        if (FIELDSET){
          if (!FIELDSET.querySelectorAll("input[type='checkbox']:checked, input[type='radio']:checked")){
            field_focus(FIELDSET);
            return;
          }
        } else {
          const INPUT = FIELD.querySelector('input');
          if (INPUT && INPUT.value === ''){
            field_focus(INPUT.closest('div.field'));
            return;
          }
        }
      };
      // fallback if no field is required
      const INPUT = FORM.querySelector("input:not([type='hidden']):first-of-type");
      if (INPUT){
        let field = INPUT.closest('fieldset');
        field = field ? field : INPUT.closest('div.field');
        if (field){
          field_focus(field);
        }
      }
    }
  }

  // open one field in the form
  function field_open(field){
    if (field instanceof HTMLElement) {
      field.classList.add('open');
      field_prompt_show(field, false);
    } else {
      console.log('WARN: invalid parameter');
    }
  }

  function field_prompt_show(field, show=true){
    const PROMPT = field.querySelector('div.prompt');
    if (PROMPT){
      const SELECTED_OPTION = field.querySelector("input[type='radio']:checked");
      if (SELECTED_OPTION && show){
        PROMPT.classList.remove('hidden');
      } else {
        PROMPT.classList.add('hidden');
      }
    }
  }

  // shut all field in the form containing the element
  function form_shut(event_or_element){
    if (event_or_element instanceof HTMLElement) {
      element = event_or_element;
    } else {
      element = event_or_element.currentTarget;
    }
    if (element instanceof HTMLElement) {
      form = element.closest('form');
      if (form){
        form.querySelectorAll('.open').forEach((open) => {
            open.classList.remove('open');
            field_label_update(open);
            field_prompt_show(field);
        });
      } else {
        console.log('WARN: missing form');
      }
    } else {
      console.log('WARN: invalid parameter');
    }
  }

  // update the current value in the Field Label
  function field_label_update(event_or_element){
    if (event_or_element instanceof HTMLElement) {
      element = event_or_element;
    } else {
      element = event_or_element.currentTarget;
    }
    field = element.closest('fieldset');
    field = field ? field : element.closest('div.field');
    if (field){
      error = field.querySelector('span.error');
      const ERROR = error && error.textContent ? error : '';
      const TEXTAREA = field.querySelector('textarea')
      if (TEXTAREA){
        label = field.querySelector("div.label");
        if (label){
          pending = label.parentElement.querySelector('span.pending');
          if (pending){
            pre_pending = pending.textContent;
            text = TEXTAREA.value.slice(0,30);
            ellipsis = TEXTAREA.value.length > text.length ? '...' : '' 
            pending.textContent = text + ellipsis;
          }
        }
        return;
      }
      const INPUT = field.querySelector('input')
      const REQUIRED = INPUT.closest('div.required');
      let by_input_name = true;
      switch (INPUT.name){
        case 'all_day':
          label = field.querySelector('div.label');
          pending = label.querySelector('span.pending');
          pre_pending = pending.textContent;
          const BY_DAY = INPUT.closest('.by_day');
          sum_input_by_day(BY_DAY).then((sum) => {pending.textContent = sum});
          if (ERROR && (pending.textContent !== pre_pending)){
            ERROR.textContent = '';
          }
          break;
        case 'all_week':
          label = field.querySelector('div.label');
          pending = label.querySelector('span.pending');
          pre_pending = pending.textContent;
          const BY_WEEK = INPUT.closest('.by_week');
          sum_input_by_week(BY_WEEK).then((sum) => {pending.textContent = sum});
          if (ERROR && (pending.textContent !== pre_pending)){
            ERROR.textContent = '';
          }
        break;
        case 'socials':
          legend = field.querySelector('legend');
          pending = legend.querySelector('span.pending');
          check = INPUT.closest('.negate_pending') ? ':not(:checked)' : ':checked';
          checkboxes = field.querySelectorAll("input[type='checkbox']" + check);
          if (legend && pending && check && checkboxes){
            pre_pending = pending.textContent;
            pending.textContent = sum_url_checkbox(checkboxes);
          }
          if (ERROR && (pending.textContent !== pre_pending)){
            ERROR.textContent = '';
          }
        break;
        case 'strengths':
          legend = field.querySelector('legend');
          pending = legend.querySelector('span.pending');
          checkboxes = field.querySelectorAll("input[type='checkbox']");
          if (legend && pending && check && checkboxes){
            pre_pending = pending.textContent;
            pending.textContent = sum_strength_checkbox(checkboxes);
          }
          if (ERROR && (pending.textContent !== pre_pending)){
            ERROR.textContent = '';
          }
        break;
        default:
          by_input_name = false;
      }
      if (by_input_name){ return; }
      pre_pending = '';
      switch (INPUT.type){
        case 'checkbox':
          legend_or_label = field.querySelector(':scope > legend');
          legend_or_label = legend_or_label ? legend_or_label : field.querySelector(':scope > div.label');
          if (legend_or_label){
            pending = legend_or_label.querySelector('span.pending');
            check = INPUT.closest('.negate_pending') ? ':not(:checked)' : ':checked';
            checkboxes = field.querySelectorAll("input[type='checkbox']" + check);
            if (pending){
              pre_pending = pending.textContent;
              optional = REQUIRED ? '' : 'optional';
              pending.textContent = checkboxes.length > 0 ? sum_input_checkbox(checkboxes) : optional;
            }
          }
          break;
        case 'radio':
          legend_or_label = field.querySelector(':scope > legend');
          legend_or_label = legend_or_label ? legend_or_label : field.querySelector(':scope > div.label');
          if (legend_or_label){
            pending = legend_or_label.querySelector('span.pending');
            if (pending){
              pre_pending = pending.textContent;
              checked = field.querySelector("input[type='radio']:checked");
              pending.textContent = sum_input_radio(checked);
            }
          }
          break;
        case 'email':
          email = field.querySelector("input[type='email']");
          label = field.querySelector("div.label");
          if (label){
            pending = label.parentElement.querySelector('span.pending');
            if (pending){
              pre_pending = pending.textContent;
              pending.textContent = sum_input_email(email);
            }
          }
          break;
        case 'range':
          range = field.querySelector("input[type='range']");
          label = field.querySelector("div.label");
          if (label){
            pending = label.querySelector('span.pending');
            if (pending){
              pre_pending = pending.textContent;
              pending.textContent = sum_input_range(range);
            }
          }
          break;
        case 'text':
          text = field.querySelector("input[type='text']");
          label = field.querySelector("div.label");
          if (label){
            pending = label.parentElement.querySelector('span.pending');
            if (pending){
              pre_pending = pending.textContent;
              pending.textContent = sum_input_text(text);
            }
          }
          break;
        default:
          console.debug('WARNING: unsupported input type: ' + INPUT.type);
      }
      if (ERROR && (pending.textContent !== pre_pending)){
        ERROR.textContent = '';
      }
    }
  }


  function sum_input_by_day(by_day){
    if (!by_day || !by_day.classList.contains('by_day')) {
      console.warn("sum_input_by_day() called with " + by_day);
      return;
    }
    let ALL_DAY = by_day.querySelector("label.toggle.all_day");
    return isAllDay(ALL_DAY).then((checked) => {
      if (checked) {
        return ALL_DAY.textContent.trim();
      } else {
        let hrs = '';
        by_day.querySelectorAll('label.hour').forEach((label_hour) => {
          label_hour.querySelectorAll("input[type='checkbox']:checked:not(:disabled)").forEach(() => {
            hrs += label_hour.textContent.trim() + ' ';
          });
        });
        return hrs;
      }
    });
  }


  function sum_input_by_week(by_week){
    if (!by_week || !by_week.classList.contains('by_week')) {
      console.warn("updateAllWeek() called with " + by_week);
      return;
    }
    const ALL_WEEK = by_week.querySelector("label.toggle.all_week");
    return isAllWeek(ALL_WEEK).then((checked) => {
      if (checked) {
        return ALL_WEEK.textContent.trim();
      } else {
        const HAS_DIGITS = /\d/;
        let allDayPromises = new Array(7);
        let labels = new Array(7);
        const DAY_BOX = by_week.nextElementSibling;
        DAY_BOX.querySelectorAll('div.label').forEach((day_label, d) => {
          labels[d] = day_label.textContent.trim();
          let by_day = day_label.nextElementSibling.nextElementSibling;
          allDayPromises[d] = sum_input_by_day(by_day);
        });
        return Promise.all(allDayPromises).then((sums) => {
          let summary = ' ';
          sums.forEach((sum, s) => {
            if (sum.trim().length == 0) {
              // noop
            } else if (HAS_DIGITS.test(sum)){
              let ddd = labels[s].substring(0,3);
              summary +=  ` ${ddd} (${sum}) `;
            } else {
              summary += labels[s] + ' ';
            }
          });
          return summary;
        });
      }
    });
  }


  function sum_input_checkbox(checkboxes){
    if (checkboxes){
      sum = '';
      if (checkboxes.length === 0){
        sum = "";
      } else {
        checkboxes.forEach((checkbox) => {
          sum += checkbox.labels[0].textContent + ', ';
        });
      }
      return sum;
    }
  }


  function sum_strength_checkbox(checkboxes){
    if (checkboxes){
      sum = '';
      if (checkboxes.length === 0){
        sum = "None";
      } else {
        checkboxes.forEach((checkbox) => {
          sum += checkbox.labels[0].textContent.trim() + ', ';
          // sum += '|' +checkbox.labels[0].textContent.trim();
          });
      }
      sum = sum.replaceAll(/([\s\w]+)(:.+?,)/g, ' $1,');
      // sum = sum.replaceAll(/\|([\s\w]+): much-stronger/g, ' ++$1++');
      // sum = sum.replaceAll(/\|([\s\w]+): stronger/g, ' +$1+');
      // sum = sum.replaceAll(/\|([\s\w]+): matched/g, ' =$1=');
      // sum = sum.replaceAll(/\|([\s\w]+): weaker/g, ' -$1-');
      // sum = sum.replaceAll(/\|([\s\w]+): much-weaker/g, ' --$1--');
      return sum;
    }
  }


  function sum_url_checkbox(checkboxes){
    if (checkboxes){
      sum = '';
      if (checkboxes.length === 0){
        sum = "None";
      } else {
        checkboxes.forEach((checkbox) => {
          url = new URL(checkbox.labels[0].textContent);
          sum += url.host + ' ';
        });
      }
      return sum;
    }
  }


  function sum_input_email(email){
    if (email){
      return email.value;
    }
  }


  function sum_input_radio(radio){
    if (radio){
      return radio.labels[0].textContent;
    }
  }


  function sum_input_range(range){
    if (range){
      return slider(range);
    }
  }


  function sum_input_text(text){
    if (text){
      return text.value;
    }
  }

  // auto close Field when value is selected
  function form_input_auto(input){
    switch (input.type){
      case 'checkbox':
        input.addEventListener('change', field_label_update);
        break;
      case 'radio':
        input.addEventListener('click', form_shut);
        break;
      case 'text':
        input.addEventListener('change', form_shut);
        break;
      default:
        console.debug('INFO: no auto-close for input type: ' + input.type);
    }
  }


////////////////  ////////////////////////////



function closeStuff(event) {
  let target = event.target;
  if (target){
    if (!(target.classList.contains('info') || target.parentElement.classList.contains('info'))){
      document.querySelectorAll('.info_text').forEach(function(info) {
        info.classList.add('hidden');
      })
    }
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

function slider(range) {
  var label = range.value;
  var options = range.closest('div.field').querySelector('div.range_options')
  var i=0;
  for ( const option of options.children) {
    if (i == range.value) {
      option.classList.remove('invisible');
      label = option.textContent;
    } else {
      option.classList.add('invisible');
    }
    i++;
  }
  return label;
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

function updatePlaceHours(place){
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
      label_sub = day.closest('div.field').querySelector('.label_sub')
      if (label_sub){
        label_sub.innerText = WEEKDAY[(now_weekday + offset) % 7];
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

function showGroup(event) {
  let button = event.currentTarget;
  button.nextElementSibling.classList.toggle('hidden');
  button.lastElementChild.classList.toggle('flip');
}

function showLoader(input, timeout=20000) {
  label = input.closest('label')
  label.classList.add("loader");
  setTimeout(() => unLoader(label), timeout);
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

function unLoader(label){
  label.classList.remove("loader")
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

function slide(slider) {
  const FIELD = slider.closest('.field');
  const OPTIONS = FIELD.querySelector('.range_options');
  if (OPTIONS) {
    WORDS = OPTIONS.children;
    for (let i = 0; i < WORDS.length; i++) {
      var word = WORDS.item(i);
      if (i == slider.value) {
        word.classList.remove('invisible');
      } else {
        word.classList.add('invisible');
      }
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
  if (toggle && toggle.tagName === 'LABEL') {
    if (toggle_checked(toggle)) {
      return toggle_uncheck(toggle);
    } else {
      return toggle_check(toggle);
    }
  }
  console.warn("toggle() called with " + toggle);
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
  if (toggle && toggle.tagName === 'LABEL') {
    return toggle.firstElementChild.checked;
  }
  console.warn("toggle_checked() called with " + toggle);
}

// check the label.toggle
function toggle_check(toggle) {
  if (toggle && toggle.tagName === 'LABEL') {
    toggle.firstElementChild.checked = "checked";
    return true;
  }
  console.warn("toggle_check() called with " + toggle);
}

// uncheck the label.toggle
function toggle_uncheck(toggle) {
  if (toggle && toggle.tagName === 'LABEL') {
    toggle.firstElementChild.checked = null;
    return false;
  }
  console.warn("toggle_check() called with " + toggle);
}


// return true if the label.toggle is disabled
function toggle_disabled(toggle) {
  if (toggle && toggle.tagName === 'LABEL') {
    return toggle.firstElementChild.disabled;
  }
  console.warn("toggle_disabled() called with " + toggle);
}


function unreadRemove(event) {
  event.currentTarget.classList.remove('unread');
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


docReady(event => {
  document.querySelectorAll('time').forEach($e => {
    const date = new Date($e.dateTime);
    $e.innerHTML = date.toLocaleTimeString([], { weekday: "short", hour: "2-digit", minute: "2-digit",  hour12: false });
  });
  document.addEventListener('click', closeStuff);
  document.querySelectorAll('div.drop').forEach(function(element) {
    element.addEventListener('click', drop);
  });
  document.querySelectorAll('div.drop_down').forEach(function(drop_down) {
    dropDownUpdate(drop_down);
  });
  document.querySelectorAll('div.down label').forEach(function(element) {
    element.addEventListener('click', downClick);
  });
  document.querySelectorAll('.cta_mobile').forEach(function(element) {
    element.addEventListener('click', ctaKeen);
  });
  document.querySelectorAll('div.head_fwd').forEach(function(element) {
    element.addEventListener('click', nextDetail);
  });
  document.querySelectorAll('div.head_back').forEach(function(element) {
    element.addEventListener('click', previousDetail);
  });
  document.querySelectorAll('div.info').forEach(function(element) {
    element.addEventListener('click', showInfo);
  });
  document.querySelectorAll('.show_group').forEach(function(element) {
    element.addEventListener('click', showGroup);
  });
  document.querySelectorAll('div.show-next-parent-sibling').forEach(function(element) {
    element.addEventListener('click', showNextParentSibling);
  });
  document.querySelectorAll('div.show-next-sibling').forEach(function(element) {
    element.addEventListener('click', showNextSibling);
  });
  document.querySelectorAll('div.closer').forEach(function(element) {
    element.addEventListener('click', close);
  });
  document.querySelectorAll('div.checkbox_wrap').forEach(function(element) {
    element.addEventListener('click', check);
  });
  // store text input across page reload https://darekkay.com/blog/preserve-form-values/
  document.querySelectorAll("input[type='text']").forEach(function(input) {
    addEventListener("input", (event) => {
      history.replaceState({ query: input.value }, "");
    });
  });
  // recover text input across page reload https://darekkay.com/blog/preserve-form-values/
  window.addEventListener("pageshow", () => {
    const query = history.state?.query;
    if (query) {
      document.querySelector("input[type='text']").value = query;
    };
  });
  document.querySelectorAll('div.unread').forEach(function(element) {
    element.addEventListener('click', unreadRemove);
  });
  document.querySelectorAll('[name=list]').forEach(function(element) {
    element.addEventListener('click', showDetail);
  });
  document.querySelectorAll('div.tab').forEach(function(element) {
    element.addEventListener('click', openTab);
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
  document.querySelectorAll('.toggle_previous_sibling').forEach(function(element) {
    element.addEventListener('click', togglePreviousSibling);
  });
  document.querySelectorAll('div.field div.label').forEach(      
    (div_label) => {
      div_label.addEventListener('click', ({currentTarget}) => {
        field_focus(currentTarget.closest('div.field'));
      });
    });
  document.querySelectorAll('fieldset legend').forEach(        
    (legend) => {
      legend.addEventListener('click', ({currentTarget}) => {
        field_focus(currentTarget.closest('fieldset'));
      });
    });
  document.querySelectorAll("form input").forEach(        
    (input) => {
      form_input_auto(input);
    });
  document.querySelectorAll("form fieldset").forEach(        
    (fieldset) => {
      field_prompt_show(fieldset);
    });
  dropInit();
  field_focus_first(); 
});


winReady(event => {
  document.querySelectorAll("form fieldset, form div.field").forEach(        
    (field) => {
      field_label_update(field);
    });
});