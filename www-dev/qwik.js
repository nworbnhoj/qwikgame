function ctaKeen(event) {
  event.currentTarget.style.display = "none";
  let fwd_event = new MouseEvent(event.type, event);
  document.getElementById('cta_keen').dispatchEvent(fwd_event);
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

function hideDropdown(event) {
  document.querySelectorAll('.options').forEach(function(dropdown) {
    if (!dropdown.parentElement.contains(event.target)) {
      dropdown.classList.add('hidden');
    }
  });
}

function hideParent(event) {
  event.currentTarget.parentElement.style.display = "none";
}

function href(event) {
  let element = event.currentTarget;
  window.location.href = element.dataset.href;
}

function nextDetail(event) {
  var list = document.getElementById("list_bar").querySelectorAll('[name=list]');
  for (let i = 0; i < list.length; i++) {
    if (list[i].checked) {
      list[i].checked = false;
      list[(i + 1 < list.length) ? i + 1 : 0].checked = true;
      break;
    }
  }
  showDetail();
}

function previousDetail(event) {
  var list = document.getElementById("list_bar").querySelectorAll('[name=list]');
  for (let i = 0; i < list.length; i++) {
    if (list[i].checked) {
      list[i].checked = false;
      list[(i - 1 < 0) ? list.length - 1 : i - 1].checked = true;
      break;
    }
  }
  showDetail();
}

function showDetail() {
  // on mobile, hide the list_bar and show the detail
  var width_600 = window.matchMedia("only screen and (max-width: 600px)").matches;
  var width_768 = window.matchMedia("only screen and (max-width: 768px)").matches;
  // var width_992 = window.matchMedia("only screen and (max-width: 992px)").matches;
  // var width_1200 = window.matchMedia("only screen and (max-width: 1200px)").matches;
  var list_bar = document.getElementById("list_bar");
  var detail = document.getElementById("detail");
  if (width_600 || width_768) { // mobile
    list_bar.style.display = "none";
    detail.style.display = "flex";
  } else { // desktop
    list_bar.style.display = "flex";
    detail.style.display = "flex";
  }

  // show the selected detail, and hide all the others
  list_bar.querySelectorAll('[name=list]').forEach(function(radio) {
    if (radio.checked) {
      document.getElementById(radio.dataset.id).classList.remove("hidden");
    } else {
      document.getElementById(radio.dataset.id).classList.add("hidden");
    }
  })
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

function showOptions(event) {
  let select = event.currentTarget;
  let options = select.nextElementSibling;
  if (select.dataset.expanded == "false") {
    options.classList.remove('hidden');
    select.dataset.expanded = "true";
  } else {
    options.classList.add('hidden');
    select.dataset.expanded = "false";
  }
}

function showGameEdit(event) {
  let detail = event.currentTarget.closest('.detail_n');
  detail.querySelector('.detail_form').classList.remove('hidden');
  detail.querySelector('.detail_summary').classList.add('hidden');
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
      let all_week = all_day.closest('div.detail_n').querySelector("label.toggle.all_week");
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
    let all_week = all_day.closest('div.detail_n').querySelector("label.toggle.all_week");
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
    let detail = button.closest('.detail_n');
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
    let all_week = all_day.closest('div.detail_n').querySelector("label.toggle.all_week");
    updateAllWeek(all_week);
  } catch (e) {
    console.log(e);
  }
}

// update an all_week toggle to be consistent with the all_day toggles
function updateAllWeek(all_week) {
  try {
    // check all_week if every all_day is checked
    let days = all_week.closest('div.detail_n').querySelectorAll("div.by_day");
    for (day of days) {
      all_day = day.querySelector("label.toggle.all_day");
      if (!toggle_disabled(all_day) && !toggle_checked(all_day)) {
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
  document.addEventListener('click', hideDropdown);
  document.querySelectorAll('.keen_mobile').forEach(function(proxy) {
    proxy.onclick = ctaKeen;
  });
  document.querySelectorAll('div.delete').forEach(function(button) {
    button.onclick = hideParent;
  });
  document.querySelectorAll('div.head_fwd').forEach(function(button) {
    button.onclick = nextDetail;
  });
  document.querySelectorAll('div.head_back').forEach(function(button) {
    button.onclick = previousDetail;
  });
  document.querySelectorAll('div.show_group').forEach(function(button) {
    button.onclick = showGroup;
  });
  document.querySelectorAll('div.show-next-parent-sibling').forEach(function(button) {
    button.onclick = showNextParentSibling;
  });
  document.querySelectorAll('div.show-next-sibling').forEach(function(button) {
    button.onclick = showNextParentSibling;
  });
  document.querySelectorAll('div.select_head').forEach(function(select) {
    select.onclick = showOptions;
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
  document.querySelectorAll('option').forEach(function(option) {
    option.onclick = showOptions;
  });
  document.querySelectorAll('.show_friend_add').forEach(function(element) {
    element.onclick = showFriendAdd;
  });
  document.querySelectorAll('.show_friend_invite').forEach(function(element) {
    element.onclick = showFriendInvite;
  })
  document.querySelectorAll('.schedule_edit').forEach(function(div) {
    div.onclick = showGameEdit;
  })
  document.querySelectorAll('.toggle_previous_sibling').forEach(function(toggle) {
    toggle.onclick = togglePreviousSibling;
  })
}