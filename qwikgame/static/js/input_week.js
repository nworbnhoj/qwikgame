// additional logic for hour and all_day toggles when an all_week is toggled

if (typeof toggleAllWeek == "undefined") {

  // update an AllWeek toggle to be consistent with the AllDay toggles
  function updateAllWeek(all_week) {
    if (!all_week || all_week.tagName !== 'LABEL') {
      console.warn("updateAllWeek() called with " + all_week);
      return;
    }
    if ('updatePromise' in all_week) {
      return all_week.updatePromise;
    }
    all_week.updatePromise = new Promise((resolve, reject) => {
      try {
        const FIELD = all_week.closest('div.field');
        // uncheck all_day if a single hour is unchecked
        if (FIELD.querySelector("label.all_day input[type='checkbox']:not(:checked):not(:disabled)")){
          toggle_uncheck(all_week);
          resolve(false);
        } else {
          toggle_check(all_week);
          resolve(true);
        }
      } catch (e) {
        console.log(e);
        reject(e);
      }
    });
    all_week.updatePromise.then(() => { delete all_week.updatePromise });
    return all_week.updatePromise;
  }

  // cascade a change in the AllWeek toggle DOWN to the AllDay toggles
  function toggleAllWeek(event) {
    try {
      const ALL_WEEK = event.currentTarget;
      const ALL_WEEK_CHECKED = toggle_checked(ALL_WEEK);
      const FIELD = ALL_WEEK.closest('div.field');
      FIELD.querySelectorAll("input[type='checkbox']:not(:disabled)").forEach((day) => {
        day.checked = ALL_WEEK_CHECKED;
      });
    } catch (e) {
      console.log(e);
    }
  }

  document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('label.toggle.all_week').forEach(function(all_week) {
      all_week.addEventListener("click", toggleAllWeek);
      field = all_week.closest('div.field');
      // propagate a click on the AllDay toggle up to the AllWeek toggle
      field.querySelectorAll('label.toggle.all_day').forEach(function(all_day) {
        all_day.addEventListener("click", () => {
          updateAllWeek(all_week);
        });
      });
      // propagate a click on an Hour toggle up to the AllWeek toggle
      field.querySelectorAll('.by_day').forEach(function(by_day) {
        let all_day = by_day.querySelector('label.toggle.all_day');
        by_day.querySelectorAll('label.toggle.hour').forEach(function(hour) {
          hour.addEventListener("click", () => {
            updateAllDay(all_day).then(updateAllWeek(all_week));
          });
        });      
      });
    });
  });

  window.addEventListener("load", function() {
    document.querySelectorAll('label.toggle.all_week').forEach(function(all_week) {
      const FIELD = all_week.closest('div.field');
      let allDayPromises = [];
      FIELD.querySelectorAll('label.toggle.all_day').forEach(function(all_day) {
        allDayPromises.push(updateAllDay(all_day));
      });
      Promise.all(allDayPromises).then((values) => {
        updateAllWeek(all_week)
      });
    });
  });
}