
///////////////// PWA Install functions ///////////////////

let installPrompt = null;
const INSTALL_BUTTON = document.getElementById("install_app");
if (INSTALL_BUTTON){

  window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault();
    installPrompt = event;
    INSTALL_BUTTON.disabled = false;
    hidePWAInfo();
    console.log("PWA installPrompt ready");
  });

  INSTALL_BUTTON.addEventListener("click", async () => {
    if (installPrompt) {
      const RESULT = await installPrompt.prompt();
      console.log(`Install prompt was: ${RESULT.outcome}`);
      disableInAppInstallPrompt();
    } else {
      showPWAInfo();
      console.log("PWA installPrompt missing");
    }
  });
  
  window.addEventListener("appinstalled", () => {
    disableInAppInstallPrompt();
    showInstalled();
    console.log("PWA installed");
    detectAppState().then(state => {
      console.log('Current App State:', state); 
    });
  });

  detectAppState().then(state => {
    console.log('Current App State:', state); 
    switch(state) {

      case "active_pwa":
        hidePWAInfo();
        showInstalled()
        break;

      case "installed_pwa":
        hidePWAInfo();
        showInstalled()
        break;

      case "uninstalled_pwa":
        showPWAInfo();
        break;

      case "standard_mobile_website":
        showPWAInfo();
        break;

      default:
        showPWAInfo();
    }
  });

  function disableInAppInstallPrompt() {
    installPrompt = null;
    INSTALL_BUTTON.disabled = true;
  }

  function hidePWAInfo() {
    const PWAFAIL = document.querySelectorAll("p.pwa-fail");
    PWAFAIL.forEach((msg) => {
      msg.classList.add('hidden');
    }); 
  }

  function showInstalled(){
    const INSTALLED = document.getElementById("app_installed");
    if (INSTALLED){
      INSTALL_BUTTON.disabled = true
      INSTALLED.classList.remove('hidden')
    }

  }

  function showPWAInfo() {
    if (/Chrome|Edg|OPR/i.test(navigator.userAgent)) {
      return;
    } else if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
      const IOS = document.getElementById("ios_install_app");
      if (IOS){
        INSTALL_BUTTON.classList.add('hidden')
        IOS.classList.remove('hidden')
      }
    } else if (/Firefox/i.test(navigator.userAgent)) {
      const FIREFOX = document.getElementById("firefox_install_app");
      if (FIREFOX){
        INSTALL_BUTTON.disabled = true
        FIREFOX.classList.remove('hidden')
      }
    } else {
      const UNSUPPORTED = document.getElementById("unsupported_install_app");
      if (UNSUPPORTED){
        INSTALL_BUTTON.disabled = true
        UNSUPPORTED.classList.remove('hidden')
      }    
    }
  }

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
