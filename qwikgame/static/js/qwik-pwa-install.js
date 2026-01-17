
///////////////// PWA Install functions ///////////////////

let installPrompt = null;
const PWA_TRIGGER = document.getElementById("install_pwa");
if (PWA_TRIGGER){
  const PWA_INPUT = document.getElementById("pwa_input");

  window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault();
    installPrompt = event;
    PWA_TRIGGER.disabled = false;
    PWA_INPUT.checked = false;
    console.log("PWA installPrompt ready");
  });

  PWA_TRIGGER.addEventListener("click", async () => {
    if (installPrompt) {
      const RESULT = await installPrompt.prompt();
      console.log(`Install prompt was: ${RESULT.outcome}`);
      installPrompt = null;
      PWA_TRIGGER.classList.add('installed')
    } else {
      console.log("PWA installPrompt missing");
    }
  });
  
  window.addEventListener("appinstalled", () => {
    console.log("PWA installed");
    detectAppState().then(state => {
      console.log('PWA State:', state); 
    });
    installPrompt = null;
    PWA_TRIGGER.classList.add('installed')
  });

  detectAppState().then(state => {
    console.log('PWA State:', state);
    let show = ".pwa_install";
    switch(state) {

      case "active_pwa":
        show = ".pwa_active";
        PWA_TRIGGER.classList.add('installed');
        PWA_INPUT.disabled = true;
        break;

      case "installed_pwa":
        show = ".pwa_installed";
        PWA_TRIGGER.classList.add('installed');
        PWA_INPUT.disabled = true;
        break;

      case "uninstalled_pwa":
        if (/Chrome|Edg|OPR/i.test(navigator.userAgent)) {
          show = ".pwa_install";
        } else if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
          show = ".pwa_ios";
        } else if (/Firefox/i.test(navigator.userAgent)) {
          show = ".pwa_install";
        } else {
          show = ".pwa_unsupported"
        }
        break;

      case "standard_mobile_website":
        if (/Firefox/i.test(navigator.userAgent)) {
          show = ".pwa_ff";
          PWA_INPUT.checked = true;
          PWA_TRIGGER.disabled = true;
        } else {
          show = ".pwa_err";
        }
        break;
    }
    document.querySelectorAll(show).forEach((element) => {
        element.classList.remove('hidden');
    });
  });

  // https://www.javaspring.net/blog/javascript-to-check-if-pwa-or-mobile-web/
  // Returns:
  // active_pwa: The app is installed on the user’s device (home screen) and
  //     running in standalone, minimal-ui, or fullscreen mode (no browser
  //     chrome like address bars).
  // installed_pwa: The app is installed but not the providing this session.
  // uninstalled_pwa: The app has PWA capabilities (manifest + service worker)
  //     but hasn’t been installed. It’s accessed via a browser, with standard
  //     browser UI.
  // standard_mobile_website: No PWA capabilities (no manifest, no service
  //     worker). Runs as a regular website in a mobile browser.
  async function detectAppState() {
    // Check for installed PWA
    const isActive = window.matchMedia('(display-mode: standalone)').matches || 
                     window.matchMedia('(display-mode: minimal-ui)').matches || 
                     window.matchMedia('(display-mode: fullscreen)').matches || 
                     (typeof navigator !== 'undefined' && navigator.standalone);
    if (isActive) return 'active_pwa';

    // Check for an installed related App
    const hasInstalledRelatedApps = !!(await navigator.getInstalledRelatedApps?.())?.length;
    if (hasInstalledRelatedApps) return 'installed_pwa';
   
    // Check for uninstalled PWA (installable)
    const hasManifest = document.querySelector('link[rel="manifest"]') !== null;
    const hasServiceWorker = 'serviceWorker' in navigator && 
                            (await navigator.serviceWorker.getRegistration()) !== null;
    const supportsBeforeInstallPrompt = 'onbeforeinstallprompt' in window;
    if (hasManifest && hasServiceWorker && supportsBeforeInstallPrompt) {
      return 'uninstalled_pwa';
    }
   
    // Otherwise, it's a standard mobile website
    return 'standard_mobile_website';
  }

}
