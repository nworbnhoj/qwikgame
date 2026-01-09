// additional logic for hour and all_day toggles when an all_week is toggled

if (typeof toggleAllWeek == "undefined") {

  // update an AllWeek toggle to be consistent with the AllDay toggles
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

  // promote a change in an Hour up to the AllWeek toggle
  function hoggleHour(event) {
    try {
      let hour = event.currentTarget;
      let all_day = hour.closest(".by_day").querySelector("label.toggle.all_day");
      if (toggle_checked(hour)) {
        updateAllDay(all_day);
        let all_week = all_day.closest('div.field').querySelector("label.toggle.all_week");
        if (toggle_checked(all_day)){
          updateAllWeek(all_week);
        } else {
          toggle_uncheck(all_week);
        }
      } else {
        toggle_uncheck(all_day);
      }
    } catch (e) {
      console.log(e);
    }
  }

  // promote a change in an AllDay checkbox up to the AllWeek toggle
  function boggleAllDay(event) {
    try {
      let all_day = event.currentTarget;
      let all_week = all_day.closest('div.field').querySelector("label.toggle.all_week");
      if (toggle_checked(all_day)) {
        updateAllWeek(all_week);
      } else {
        toggle_uncheck(all_week);
      }
    } catch (e) {
      console.log(e);
    }
  }

  // cascade a change in the AllWeek toggle to the AllDay toggles
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

  window.addEventListener("load", function() {
    document.querySelectorAll('label.toggle.all_week').forEach(function(all_week) {
      updateAllWeek(all_week)
      all_week.addEventListener("click", toggleAllWeek);
      the_week = all_week.closest('.by_week').nextElementSibling;
      the_week.querySelectorAll('label.toggle.all_day').forEach(function(all_day) {
        all_day.addEventListener("click", boggleAllDay);
      });
      all_hours = the_week.querySelectorAll('label.toggle.hour').forEach(function(hour) {
        hour.addEventListener('click', hoggleHour);
      });
    });
  });

}