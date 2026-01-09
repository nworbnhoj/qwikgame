// additional logic for hour toggles when an all_day is toggled

if (typeof toggleAllDay == "undefined") {

  // update an AllDay toggle to be consistent with the Hour checkboxes
  function updateAllDay(all_day) {
    if (!all_day) {
      return;
    }
    try {
      // check all_day if every hour is checked
      let hours = all_day.closest('div.by_day').querySelector('div.radio_block').children;
      for (hour of hours) {
        var checkbox = hour.firstElementChild;
        if (!checkbox.disabled && !checkbox.checked) {
          return;
        }
      }
      toggle_check(all_day);
    } catch (e) {
      console.log(e);
    }
  }

  // promote a change in an Hour checkbox up to the AllDay toggle
  function boggleHour(event) {
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

  // cascade a change in the AllDay toggle to the Hour checkboxes
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
    } catch (e) {
      console.log(e);
    }
  }

  window.addEventListener("load", function() {
    document.querySelectorAll('label.toggle.all_day').forEach(function(all_day) {
      updateAllDay(all_day)
      all_day.addEventListener("click", toggleAllDay);
      the_day = all_day.parentElement.nextElementSibling;
      the_day.querySelectorAll('label.toggle.hour').forEach(function(hour) {
        hour.addEventListener("click", boggleHour);
      });
    });
  });

}