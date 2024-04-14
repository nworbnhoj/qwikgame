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
  let down = drop.nextElementSibling;
  if (drop.dataset.down == "false") {
    drop.dataset.down = "true";
    down.classList.remove('hidden');
  } else {
    drop.dataset.down = "false";
    down.classList.add('hidden');
  }
}

// update a drop_down display to be consistent with the selected options
function dropDownUpdate(drop_down){
  try {
    let drop_up = drop_down.querySelector('.drop_up');
    let drop_up_txt = drop_up.dataset.action;
    drop_down.querySelectorAll('input:checked').forEach(function(checked){
        drop_up_txt += checked.parentElement.innerText + " ";
    });
    drop_up_txt = drop_up_txt.replaceAll("\n", "");
    drop_up_txt = drop_up_txt.trim();
    drop_up.innerText = drop_up_txt;
  } catch (e) {
    console.log(e);
  }
}

function downClick(event){
  let option = event.currentTarget;
  dropDownUpdate(option.closest('.drop_down'));
  option.closest('.down').classList.add('hidden');
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

function check(event) {
  let element = event.currentTarget;
  element.querySelector("input[type='checkbox']").checked=true;
}

function href(event) {
  let element = event.currentTarget;
  window.location.href = element.dataset.href;
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
  event.currentTarget.querySelector('div.info_text').classList.toggle('hidden');
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
    let all_day = hour.closest(".by_day").querySelector("label.toggle.all_day");
    if (toggle_checked(hour)) {
      updateAllDay(all_day);
    } else {
      toggle_uncheck(all_day);
      let all_week = all_day.closest('div.field').querySelector("label.toggle.all_week");
      toggle_uncheck(all_week);
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
    var hours = all_day.closest('.by_day').querySelector('div.radio_block').children;
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
      toggle_uncheck(all_week);
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

window.onload = function() {
  updateAllHour();
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
  document.querySelectorAll('.href').forEach(function(element) {
    element.onclick = href;
  });
  document.querySelectorAll('input.enable-invite-friend').forEach(function(checkbox) {
    checkbox.oninput = enableInviteFriend;
  });
  document.querySelectorAll('label.toggle div.button').forEach(function(hr) {
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
  document.querySelectorAll('.toggle_previous_sibling').forEach(function(toggle) {
    toggle.onclick = togglePreviousSibling;
  });
}