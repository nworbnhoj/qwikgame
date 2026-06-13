// additional logic for hour and all_day toggles when an all_week is toggled
if (typeof toggleAllWeek == "undefined") {
    function isAllWeek(all_week) {
        if ('updatePromise' in all_week) {
            return all_week.updatePromise;
        }
        console.warn("isAllWeek() called before init");
        return Promise.resolve(false);
    }
    // update an AllWeek toggle to be consistent with the AllDay toggles
    function updateAllWeek(all_week) {
        all_week.updatePromise = new Promise((resolve, reject) => {
            try {
                const FIELD = all_week.closest('div.field');
                // uncheck all_day if a single hour is unchecked
                if (FIELD.querySelector("label.all_day input[type='checkbox']:not(:checked):not(:disabled)")) {
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
        return all_week.updatePromise;
    }
    // cascade a change in the AllWeek toggle DOWN to the AllDay toggles
    function toggleAllWeek(event) {
        try {
            const ALL_WEEK = event.currentTarget;
            const ALL_WEEK_CHECKED = toggle_checked(ALL_WEEK);
            const FIELD = ALL_WEEK.closest('div.field');
            FIELD.querySelectorAll("label.toggle.all_day").forEach((all_day) => {
                all_day.querySelector("input[type='checkbox']:not(:disabled)").checked = ALL_WEEK_CHECKED;
                all_day.updatePromise = Promise.resolve(ALL_WEEK_CHECKED);
                const BY_HOUR = all_day.closest('div.on_day').querySelector('div.by_hour');
                BY_HOUR.querySelectorAll("input[type='checkbox']:not(:disabled)").forEach((hour) => {
                    hour.checked = ALL_WEEK_CHECKED;
                });
            });
            ALL_WEEK.updatePromise = Promise.resolve(ALL_WEEK_CHECKED);
        } catch (e) {
            console.log(e);
        }
    }
    document.addEventListener("DOMContentLoaded", () => {
        // initialize all label.toggle.all_week
        document.querySelectorAll('label.toggle.all_week').forEach((all_week) => {
            const FIELD = all_week.closest('div.field');
            let allDayPromises = [];
            FIELD.querySelectorAll('label.toggle.all_day').forEach((all_day) => {
                allDayPromises.push(updateAllDay(all_day));
            });
            Promise.all(allDayPromises).then((values) => {
                updateAllWeek(all_week)
            });
        });
        // setup event listeners to keep Days & Weeks in sync
        document.querySelectorAll('label.toggle.all_week').forEach((all_week) => {
            // propagate a click on an AllWeek toggle DOWN to the AllDay toggles
            all_week.addEventListener("click", toggleAllWeek);
            const FIELD = all_week.closest('div.field');
            // propagate a click on the AllDay toggle UP to the AllWeek toggle
            FIELD.querySelectorAll('label.toggle.all_day').forEach((all_day) => {
                all_day.addEventListener("click", () => {
                    updateAllWeek(all_week);
                });
            });
            // propagate a click on an Hour toggle UP to the AllWeek toggle
            FIELD.querySelectorAll('.by_day').forEach((by_day) => {
                let all_day = by_day.querySelector('label.toggle.all_day');
                by_day.querySelectorAll('label.toggle.hour').forEach((hour) => {
                    hour.addEventListener("click", () => {
                        updateAllDay(all_day).then(updateAllWeek(all_week));
                    });
                });
            });
        });
    });
}