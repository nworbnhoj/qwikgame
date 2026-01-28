
///////////////// PWA Install functions ///////////////////

let installPrompt = null;
const PWA_INSTALL = document.getElementById("install_pwa");
if (PWA_INSTALL){

  window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault();
    installPrompt = event;
    pwa_show(".pwa_install");
    PWA_INSTALL.disabled = false;
    console.log("PWA installPrompt ready");
  });

  PWA_INSTALL.addEventListener("click", async () => {
    if (installPrompt) {
      const RESULT = await installPrompt.prompt();
      console.log(`Install prompt was: ${RESULT.outcome}`);
      installPrompt = null;
      pwa_show(".pwa_active");
      PWA_INSTALL.classList.add('installed');
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
    PWA_INSTALL.classList.add('installed')
  });

  detectAppState().then(state => {
    console.log('PWA State:', state);
    switch(state) {

      case "active_pwa":
        pwa_show(".pwa_active");
        PWA_INSTALL.classList.add('installed');
        break;

      case "installed_pwa":
        pwa_show(".pwa_installed");
        PWA_INSTALL.classList.add('installed');
        break;

      case "uninstalled_pwa":
        pwa_show(".pwa_install");
        break;

      case "standard_mobile_website":
        if (/Chrome|Edg|OPR/i.test(navigator.userAgent)) {
          pwa_show(".pwa_brave");
          PWA_INSTALL.disabled = true;
        } else if (/Firefox/i.test(navigator.userAgent)) {
          pwa_show(".pwa_ff");
          PWA_INSTALL.disabled = true;
        } else if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
          PWA_INSTALL.hidden = true;
          document.querySelector("label[for='pwa_ios']").hidden = false;
        } else {
          pwa_show(".pwa_unsupported");
          PWA_INSTALL.disabled = true;
          console.log("PWA unsupported userAgent: ", navigator.userAgent);
        }
        break;
    }
  });


  function pwa_show(klass) {
    console.log("pwa_show(",klass,")");
    for (const child of PWA_INSTALL.children) {
      child.hidden = true;
    }
    document.querySelectorAll(klass).forEach((element) => {
        element.hidden = false;
    });
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
