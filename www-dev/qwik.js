function ctaKeen(event) {
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
  event.currentTarget.parentElement.classList.add('hidden');
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

function toggleAllDay(event) {
  let button = event.currentTarget;
  var checked = button.firstElementChild.checked;
  var hours = button.closest('.by_day').querySelector('.radio_block').children;
  for (hour of hours) {
    var checkbox = hour.firstElementChild;
    if (!checkbox.disabled) {
      checkbox.checked = checked;
    }
  }
}

function toggleAllWeek(event) {
  let button = event.currentTarget;
  var checked = button.firstElementChild.checked;
  let detail = button.closest('.detail_n');
  detail.querySelectorAll('.all_day').forEach(function(button) {
    button.firstElementChild.checked = checked;
  })
  detail.querySelectorAll('.hour_grid').forEach(function(radio_block) {
    for (hour of radio_block.children) {
      hour.firstElementChild.checked = checked;
    }
  })
}

function togglePreviousSibling(event) {
  let toggle = event.currentTarget;
  toggle.querySelectorAll('.tog').forEach(function(tog){
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
  document.querySelectorAll('button.delete').forEach(function(button) {
    button.onclick = hideParent;
  });
  document.querySelectorAll('button.head_fwd').forEach(function(button) {
    button.onclick = nextDetail;
  });
  document.querySelectorAll('button.head_back').forEach(function(button) {
    button.onclick = previousDetail;
  });
  document.querySelectorAll('button.show-group').forEach(function(button) {
    button.onclick = showGroup;
  });
  document.querySelectorAll('button.show-next-parent-sibling').forEach(function(button) {
    button.onclick = showNextParentSibling;
  });
  document.querySelectorAll('button.show-next-sibling').forEach(function(button) {
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
  document.querySelectorAll('label.toggle.button.all_day').forEach(function(button) {
    button.onclick = toggleAllDay;
  });
  document.querySelectorAll('label.toggle.button.all_week').forEach(function(button) {
    button.onclick = toggleAllWeek;
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
  document.querySelectorAll('.toggle_previous_sibling').forEach(function(toggle){
    toggle.onclick = togglePreviousSibling;
  })
}